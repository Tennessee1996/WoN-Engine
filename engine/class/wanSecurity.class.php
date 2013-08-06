<?php

class wanSecurity
{

	protected $_xss_hash = '';

	protected $_never_allowed_str = array(
		'document.cookie'	=> '[removed]',
		'document.write'	=> '[removed]',
		'.parentNode'		=> '[removed]',
		'.innerHTML'		=> '[removed]',
		'window.location'	=> '[removed]',
		'-moz-binding'		=> '[removed]',
		'<!--'				=> '&lt;!--',
		'-->'				=> '--&gt;',
		'<![CDATA['			=> '&lt;![CDATA[',
		'<comment>'			=> '&lt;comment&gt;'
	);

	protected $_never_allowed_regex = array(
		'javascript\s*:',
		'expression\s*(\(|&\#40;)',
		'vbscript\s*:', 
		'Redirect\s+302',
		"([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
	);

	public function __construct()
	{
		$_POST = array_map(array('wanSecurity', 'xss_clean'), $_POST);
		$_GET = array_map(array('wanSecurity', 'xss_clean'), $_GET);
		$_COOKIE = array_map(array('wanSecurity', 'xss_clean'), $_COOKIE);
		$_SERVER = array_map(array('wanSecurity', 'xss_clean'), $_SERVER);
		$_SESSION = array_map(array('wanSecurity', 'xss_clean'), $_SESSION);

	}

	public function xss_hash()
	{
		if ($this->_xss_hash == '')
		{
			mt_srand();
			$this->_xss_hash = md5(time() + mt_rand(0, 1999999999));
		}

		return $this->_xss_hash;
	}

	public function xss_clean($str, $is_image = FALSE)
	{
		if (is_array($str))
		{
			while (list($key) = each($str))
			{
				$str[$key] = $this->xss_clean($str[$key]);
			}

			return $str;
		}

		$str = $this->remove_invisible_characters($str);

		$str = $this->_validate_entities($str);

		$str = rawurldecode($str);

		$str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);

		$str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array($this, '_decode_entity'), $str);

		$str = $this->remove_invisible_characters($str);

		if (strpos($str, "\t") !== FALSE)
		{
			$str = str_replace("\t", ' ', $str);
		}

		$converted_string = $str;

		$str = $this->_do_never_allowed($str);

		if ($is_image === TRUE)
		{
			$str = preg_replace('/<\?(php)/i', "&lt;?\\1", $str);
		}
		else
		{
			$str = str_replace(array('<?', '?'.'>'),  array('&lt;?', '?&gt;'), $str);
		}

		$words = array(
			'javascript', 'expression', 'vbscript', 'script', 'base64',
			'applet', 'alert', 'document', 'write', 'cookie', 'window'
		);

		foreach ($words as $word)
		{
			$temp = '';

			for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++)
			{
				$temp .= substr($word, $i, 1)."\s*";
			}

			$str = preg_replace_callback('#('.substr($temp, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
		}

		do
		{
			$original = $str;

			if (preg_match("/script/i", $str) OR preg_match("/xss/i", $str))
			{
				$str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
			}
		}
		while($original != $str);

		unset($original);

		$str = $this->_remove_evil_attributes($str, $is_image);

		$naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

		$str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);

		$str = $this->_do_never_allowed($str);

		if ($is_image === TRUE)
		{
			return ($str == $converted_string) ? TRUE: FALSE;
		}

		return $str;
	}

	public function entity_decode($str, $charset='UTF-8')
	{
		if (stristr($str, '&') === FALSE)
		{
			return $str;
		}

		$str = html_entity_decode($str, ENT_COMPAT, $charset);
		$str = preg_replace('~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str);
		return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $str);
	}

	public function sanitize_filename($str, $relative_path = FALSE)
	{
		$bad = array(
			"../",
			"<!--",
			"-->",
			"<",
			">",
			"'",
			'"',
			'&',
			'$',
			'#',
			'{',
			'}',
			'[',
			']',
			'=',
			';',
			'?',
			"%20",
			"%22",
			"%3c",		// <
			"%253c",	// <
			"%3e",		// >
			"%0e",		// >
			"%28",		// (
			"%29",		// )
			"%2528",	// (
			"%26",		// &
			"%24",		// $
			"%3f",		// ?
			"%3b",		// ;
			"%3d"		// =
		);

		if ( ! $relative_path)
		{
			$bad[] = './';
			$bad[] = '/';
		}

		$str = $this->remove_invisible_characters($str, FALSE);
		return stripslashes(str_replace($bad, '', $str));
	}

	protected function _compact_exploded_words($matches)
	{
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}

	protected function _remove_evil_attributes($str, $is_image)
	{
		$evil_attributes = array('on\w*', 'xmlns', 'formaction');

		if ($is_image === TRUE)
		{
			unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
		}

		do {
			$count = 0;
			$attribs = array();

			preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{

				$attribs[] = preg_quote($attr[0], '/');
			}

			preg_match_all("/(".implode('|', $evil_attributes).")\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is",  $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{
				$attribs[] = preg_quote($attr[0], '/');
			}

			if (count($attribs) > 0)
			{
				$str = preg_replace("/<(\/?[^><]+?)([^A-Za-z<>\-])(.*?)(".implode('|', $attribs).")(.*?)([\s><])([><]*)/i", '<$1 $3$5$6$7', $str, -1, $count);
			}

		} while ($count);

		return $str;
	}

	protected function _sanitize_naughty_html($matches)
	{
		$str = '&lt;'.$matches[1].$matches[2].$matches[3];

		$str .= str_replace(array('>', '<'), array('&gt;', '&lt;'),
							$matches[4]);

		return $str;
	}

	protected function _convert_attribute($match)
	{
		return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
	}

	protected function _decode_entity($match)
	{
		return $this->entity_decode($match[0], 'UTF-8');
	}

	protected function _validate_entities($str)
	{
		$str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xss_hash()."\\1=\\2", $str);

		$str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);

		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);

		$str = str_replace($this->xss_hash(), '&', $str);

		return $str;
	}

	protected function _do_never_allowed($str)
	{
		$str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);

		foreach ($this->_never_allowed_regex as $regex)
		{
			$str = preg_replace('#'.$regex.'#is', '[removed]', $str);
		}

		return $str;
	}

	public function remove_invisible_characters($str, $url_encoded = TRUE)
	{
		$non_displayables = array();
		
		if ($url_encoded)
		{
			$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}
		
		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do
		{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}

	public static function csrfToken()
	{
		static $token = NULL;

		if (is_null($token))
		{
			$token = self::_csrfTokenObfuscate($_SESSION['csrf']);
		}

		return $token;
	}

	private static function _csrfTokenObfuscate($token)
	{
		return sha1($_SERVER['REMOTE_ADDR'] . sha1($token) . $_SERVER['HTTP_USER_AGENT']);
	}

	private static function _csrfTokenGenerate()
	{
		$uniqid = sha1(uniqid(mt_rand(123456789,987654321)));

		return $uniqid;
	}

	public static function csrfRefresh()
	{
		if ($_SESSION['csrf_expire'] < time())
		{
			self::csrfInit();
		}
		else
		{
			$_SESSION['csrf_expire'] += 180;
		}
	}

	public function csrfInit()
	{
		$_SESSION['csrf'] = self::_csrfTokenGenerate();
		$_SESSION['csrf_expire'] = time() + 180;
	}

	public static function csrfCheck()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$gpc_token = $_POST['token'];
		}
		else
		{
			$gpc_token = $_GET['token'];
		}

		if ($gpc_token !== self::_csrfTokenObfuscate($_SESSION['csrf']))
		{
			$_SESSION['flash'] = 'Le jeton de sécurité a expiré';
			wanEngine::redirect('index.php?page=ninja');
		}
		else
		{
			return TRUE;
		}
	}

	public static function csrf()
	{
		return self::csrfCheck();
	}

	public static function link($params, $tokenize = TRUE)
	{
		$link = 'index.php?'.$params;

		if ($tokenize)
		{
			return $link .'&token='.self::csrfToken();
		}
		else
		{
			return $link;
		}
	}

	public static function post()
	{
		return '<input type="hidden" name="token" value="'.self::csrfToken().'" />';
	}
}

?>