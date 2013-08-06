<?php

class wanEngine
{
	public static $ajax = false;
	
	private function __construct() { }
	private function __clone() { }
	
	public static function setError($string = NULL)
	{
		if (!isset($string))
			$error_msg = 'Unrecognized error';
		else
			$error_msg = $string;
			
		trigger_error($error_msg, E_USER_ERROR);
	}
	
	public static function controlSession()
	{
		if ($_SESSION['control']['agent'] == md5($_SERVER['HTTP_USER_AGENT']) AND 
			$_SESSION['control']['ip'] == md5($_SERVER['REMOTE_ADDR']))
			session_regenerate_id(TRUE);
		else
		{
			$log_data = array('ninja_id' => $_SESSION['ninja']['id'], 'ninja_login' => $_SESSION['ninja']['login']);
			$log = array('ninja' => $_SESSION['ninja']['id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);
			wanLog::log($log);

			self::unsetConnect();
		}
	}

	public static function controlSessionLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> 
				a été déconnecté par le système suite à contrôle de session';
	}
	
	public static function ajax()
	{
		self::$ajax = true;
		
		if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
		{ 
			echo 'DIRECT ACCESS UNAUTHORIZED';
		}
		else
		{
			$array = array();
			$array['1'] = 'refresh.php';

			if (array_key_exists($_GET['type'], $array))
			{
				$file = 'ajax/'.$array[$_GET['type']];
				if (file_exists($file))
					require $file;
				else
					echo 'UNDEFINED ACTION';
			}
			else
			{
				echo 'UNDEFINED TYPE';
			}
		}
	}
	
	public static function isAjax()
	{
		return self::$ajax;
	}
	
	public static function myHash($stringToHash)
	{
		$privateHash = 'sha256';
		$privateKey = '829474dd77fb6ff4c367e6f9e6287df14efcd2ef';
		$privateStartSalt = 'd7c5aa3f3c4bf087b7fa3e3042a270d4';
		$privateEndSalt = '92b2b9f8d972e6bd3171f169e1ea3ce2';
		$privateString = $privateStartSalt.$stringToHash.$privateEndSalt;
		$stringToReturn = hash_hmac($privateHash,$stringToHash,$privateKey,FALSE);
		return $stringToReturn;
	}
	
	public static function generateId($size = 16)
	{
		$secret = '';
		$chaine = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$longueur_chaine = strlen($chaine);

		for($i = 1; $i <= 32; $i++)
		{
			$place_aleatoire = mt_rand(0,($longueur_chaine-1));
			$secret .= $chaine[$place_aleatoire];
		}
		
		return substr(md5(microtime().rand(101, 909).$secret), 0, $size);
	}
	
	public static function generateCode()
	{
		$chars = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'X', 'Z', 'W');
		$code = date('W');
		
		for ($x = 0; $x < 8; $x++)
		{
			$code .= $chars[shuffle($chars)];
		}
		
		return $code;
	}
	
	public static function controlPseudo($pseudo, $limit = 12)
	{
		$pattern = '/[^a-zA-Z|-|\'|é|è|ê|É|È|Ê|Ë|ò|ô|ö|Ò|Ô|Ö|ù|û|ü|Ù|Û|Ü|ç|ï|Ï|à|â|ä|À|Â|Ä|\s{1}]/';
		
		$cleaned = preg_replace($pattern, '', $pseudo);
		
		if (mb_strlen($cleaned) > $limit)
		{
			$cleaned = mb_substr($cleaned, 0, $limit);
		}
		
		return $cleaned;
	}
	
	public static function existConnect()
	{
		if (!empty($_SESSION['ninja']) AND !empty($_SESSION['control']))
			return TRUE;
		else
			return FALSE;
	}
	
	public static function unsetConnect()
	{
		unset($_SESSION);
		session_destroy();
		self::redirect();
	}
	
	public static function flash()
	{
		if (!empty($_SESSION['flash']))
		{
			echo '<div class="flash">'.$_SESSION['flash'].'</div>';
			unset($_SESSION['flash']);
		}
	}
	
	public static function previousPage()
	{
		return 'index.php?'.$_SERVER['QUERY_STRING'];
	}
	
	public static function redirect($destination = NULL)
	{
		if (self::existConnect())
		{
			if (!empty($destination))
			{
				header("Location:$destination");
				exit;
			}
			else
			{
				header("Location:index.php?page=ninja");
				exit;
			}
		}
		else
		{
			if (!empty($destination))
			{
				header("Location:$destination");
				exit;
			}
			else
			{
				header("Location:index.php?page=accueil");
				exit;
			}
		}
	}
	
	public static function myTimestamp($datetime)
	{
		list($date, $time) = explode(' ', $datetime);
		list($year, $month, $day) = explode('-', $date);
		list($hour, $minute, $second) = explode(':', $time);
		$myTimestamp = mktime($hour, $minute, $second, $month, $day, $year);
		return $myTimestamp;
	}
	
	public static function resetErrorsLog($id)
	{
		if (strtolower($_POST['captcha']) == $_SESSION['captcha'])
		{
			$requete_captcha = "UPDATE 
									wan_ninja 
								SET 
									ninja_log_errors=0 
								WHERE 
									ninja_id='$id'";

			$resultat_captcha = ktPDO::get()->exec($requete_captcha);

			echo 'Merci. Votre compte a correctement été débloqué ! Vous pouvez vous connecter.';

			$log_data['ninja_id'] = $_SESSION['ninja']['id'];
			$log_data['ninja_login'] = $_SESSION['ninja']['login'];

			wanLog::log(array('ninja' => $_SESSION['ninja']['id'], 'class' => __CLASS__, 'method' => __METHOD__, 'data' => $log_data));

			unset($_SESSION);
		}
		else
		{
			echo 'Le code de sécurité est incorrect ! Merci de ré-entrer votre identifiant et votre mot de passe dans le menu de connexion.';
		}
	}

	public static function resetErrorsLogLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'"">'.$data['ninja_login'].'</a> débloque son compte avec le Captcha';
	}
	
	/*
	 * Connexion d'un ninja
	 */
	function setConnect($post_login, $post_password)
	{
		if (!self::existConnect())
		{
			$post_login = (string) htmlentities($post_login);

			$requete_connexion = "SELECT 
									* 
								FROM 
									wan_ninja 
								WHERE 
									ninja_mail = ".ktPDO::get()->quote($post_login)." 
									OR 
									ninja_login = ".ktPDO::get()->quote($post_login)." 
								LIMIT 
									1";

			$connexionat_connexion = ktPDO::get()->query($requete_connexion);

			if (!$connexionat_connexion)
			{
				$erreur_pdo = ktPDO::get()->errorInfo();
				echo 'Erreur de connexion<br />'.ktPDO::get()->errorCode().' : '.$erreur_pdo[2];
			}
				
			else
			{
				$connexion = $connexionat_connexion->fetch(PDO::FETCH_ASSOC);

				if ($connexionat_connexion->rowCount() == 0)
				{
					echo 'Nous sommes désolé, cet identifiant n\'a pas été trouvé dans la base de donnée.';
					return;
				}
				elseif ($connexion['ninja_log_errors'] < 3)
				{
					$connexionat_connexion->closeCursor();

					if (self::myHash($post_password) == $connexion['ninja_password'])
					{
						if ($connexion['ninja_ban_statut'] == '1')
						{
							if (self::myTimestamp($connexion['ninja_ban_time']) > time())
							{
								echo 'Ce compte a été banni jusqu\'au <span style="font-weight:bold;">'.$connexion['ninja_ban_time'].'</span>.<br />';
								echo 'Motif : <span style="font-style:italic;">'.$connexion['ninja_ban_raison'].'</span>';
								return;
							}
							else
							{
								$requete_ban = "UPDATE 
													wan_ninja 
												SET 
													ninja_ban_statut='0',ninja_ban_raison='',ninja_ban_time=NOW() 
												WHERE 
													ninja_id = ".ktPDO::get()->quote($connexion['ninja_id'])."";

								$resultat_ban = ktPDO::get()->exec($requete_ban);

								$log_data['ninja_id'] = $connexion['ninja_id'];
								$log_data['ninja_login'] = $connexion['ninja_login'];
								$log_data['login_state'] = 'finished_ban_period';
								$log_data['login_ip'] = $_SERVER['REMOTE_ADDR'];
								$log_data['login_agent'] = $_SERVER['HTTP_USER_AGENT'];

								$log = array('ninja' => $connexion['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

								wanLog::log($log);

								echo 'Votre compte précédement banni a été débloqué, merci de saisir à nouveau vos identifiants afin de vous connecter.';
								return;
							}
						}
						else
						{
							$_SESSION = array();
							$_SESSION['control'] = array();
							$_SESSION['control']['agent'] = md5($_SERVER['HTTP_USER_AGENT']);
							$_SESSION['control']['ip'] = md5($_SERVER['REMOTE_ADDR']);
							$_SESSION['control']['time'] = time()+900;
							$_SESSION['ninja'] = array();
							$_SESSION['ninja']['id'] = $connexion['ninja_id'];
							$_SESSION['ninja']['login'] = $connexion['ninja_login'];
							$_SESSION['ninja']['time'] = time();
							$_SESSION['flash'] = '';
							wanSecurity::csrfInit();
							
							$requete_ninja = "UPDATE 
													wan_ninja 
												SET 
													ninja_log_errors = 0, 
													ninja_last_connect = ".time().", 
													ninja_ip = '".$_SERVER['REMOTE_ADDR']."'
												WHERE 
													ninja_login = ".ktPDO::get()->quote($connexion['ninja_login'])." 
												LIMIT 1";
							
							self::ninjaOnline(TRUE);

							$log_data['ninja_id'] = $connexion['ninja_id'];
							$log_data['ninja_login'] = $connexion['ninja_login'];
							$log_data['login_state'] = 'logged_in';
							$log_data['login_ip'] = $_SERVER['REMOTE_ADDR'];
							$log_data['login_agent'] = $_SERVER['HTTP_USER_AGENT'];

							$log = array('ninja' => $connexion['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

							wanLog::log($log);

							$resultat_ninja = ktPDO::get()->exec($requete_ninja);
							self::redirect('index.php?page=ninja');
						}
					}
					else 
					{
						echo 'Impossible de se connecter avec cet identifiant. Le mot de passe est incorrect.<br />';
						echo 'Essais restants : <span class="tablebold">'.(2-$connexion['ninja_log_errors']).'</span>';

						$log_data['ninja_id'] = $connexion['ninja_id'];
						$log_data['ninja_login'] = $connexion['ninja_login'];
						$log_data['login_state'] = 'wrong_password';
						$log_data['login_ip'] = $_SERVER['REMOTE_ADDR'];
						$log_data['login_agent'] = $_SERVER['HTTP_USER_AGENT'];

						$log = array('ninja' => $connexion['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

						wanLog::log($log);
						
						$requete_erreur = "UPDATE 
												wan_ninja 
											SET 
												ninja_log_errors = ninja_log_errors+1 
											WHERE 
												ninja_login = ".ktPDO::get()->quote($connexion['ninja_login'])." 
											LIMIT 1";

						$resultat_erreur = ktPDO::get()->exec($requete_erreur);
						return;
					}
				}
				elseif ($connexion['ninja_log_errors'] >= 3)
				{
					$_SESSION['id'] = $connexion['ninja_id'];
					$_SESSION['name'] = $connexion['ninja_login'];

					echo 'Impossible de se connecter avec cet identifiant. Ce compte a été bloqué parceque le nombre limite de connexions échouées a été atteint.<br /><br />';
					echo 'Veuillez remplir le code de sécurité suivant pour débloquer votre compte.<br /><br />';

					echo '<form action="index.php?page=connexion" method="POST"><table><tr class="tablex3">';
						echo '<td><img src="medias/captcha.php" alt="Code de sécurité CAPTCHA" /></td>';
						echo '<td>Recopier le code ici :<br /><input type="text" maxlength="7" name="captcha" /></td>';
						echo '<td><input type="submit" value="Débloquer" /></td>';
					echo '</tr></table></form>';
					return;
				}
				else
				{
					echo 'Nous sommes désolé, il est pour le moment impossible de se connecter !';
					return;
				}
			}
		}
	}

	public static function setConnectLogParse($data)
	{
		switch ($data['login_state'])
		{
			case 'finished_ban_period' :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'"">'.$data['ninja_login'].'</a> termine sa période de bannissement';
			break;
			case 'logged_in' :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'"">'.$data['ninja_login'].'</a> rejoint la partie<br />
						<em style="font-size:6px;">'.$data['login_agent'].' - '.$data['login_ip'].'</em>';
			break;
			case 'wrong_password' :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'"">'.$data['ninja_login'].'</a> se trompe de mot de passe<br />
						<em style="font-size:6px;">'.$data['login_agent'].' - '.$data['login_ip'].'</em>';
			break;
		}
	}
	
	public static function decompte($date_rebour, $redirection)
	{
		$date = date('F d, Y H:i:s', $date_rebour);

		echo "<script type=\"text/javascript\">
			var cible = new Date('".$date."');
			var time = parseInt(cible.getTime() / 1000, 10);

			function decompte()
			{
				var aujourdhui = new Date();
				time_tmp = parseInt(aujourdhui.getTime() / 1000, 10);
				restant = time - time_tmp;
		
				jour = parseInt((restant / (60 * 60 * 24)), 10);
				heure = parseInt((restant / (60 * 60) - jour * 24), 10);
				minute = parseInt((restant / 60 - jour * 24 * 60 - heure * 60), 10);
				seconde = parseInt((restant - jour * 24 * 60 * 60 - heure * 60 * 60 - minute * 60), 10);
				
				if (jour > 1)
				{
					document.getElementById('jours').innerHTML = jour+' jours';
				}
				else if (jour == 0)
				{
					document.getElementById('jours').innerHTML = '';
				}
				else
				{
					document.getElementById('jours').innerHTML = jour+' jour';
				}
				if (heure > 1)
				{
					document.getElementById('heures').innerHTML  = heure+' heures';
				}
				else if (heure == 0)
				{
					document.getElementById('heures').innerHTML  = '';
				}
				else
				{
					document.getElementById('heures').innerHTML  = heure+' heure';
				}
				if (minute > 1)
				{
					document.getElementById('minutes').innerHTML  = minute+' minutes';
				}
				else if (minute == 0)
				{
					document.getElementById('minutes').innerHTML  = '';
				}
				else
				{
					document.getElementById('minutes').innerHTML  = minute+' minute';
				}
				if (seconde > 1)
				{
					document.getElementById('secondes').innerHTML = seconde+' secondes';
				}
				else
				{
					document.getElementById('secondes').innerHTML  = seconde+' seconde';
				}
		
				if (time_tmp < time)
				{
					setTimeout('decompte()', 1000);
				}
				
				else
				{
					window.location.replace(\"".$redirection."\");
				}
			}

			setTimeout('decompte()', 500);
			</script>";
	}
	
	/*
	 * Vérifie qu'un ninja existe en fonction de son Id
	 */
	public static function engineCheckNinja($id)
	{
		$req = "SELECT ninja_id FROM wan_ninja WHERE ninja_id = ".ktPDO::get()->quote($id)." LIMIT 1";
		
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			return FALSE;
		}
		else
		{
			$res->closeCursor();
			return TRUE;
		}
	}
	
	/*
	 * Fichier des ninjas connectés
	 */
	public static function ninjaOnline($force = FALSE)
	{
		$file = 'cache/online.cache';
		$time = time() - 900;
		
		if ((file_exists($file && filemtime($file) > $time)) AND !$force)
		{
			$return = file_get_contents($file);
			$return = unserialize($return);
			return $return;
		}
		else
		{
			$req = "SELECT 
					wan_ninja.ninja_id, 
					wan_ninja.ninja_login, 
					wan_ninja.ninja_last_connect, 
					wan_ninja.ninja_admin, 
					wan_ninja.ninja_modo, 
					wan_rangs.rang_id, 
					wan_rangs.rang_nom, 
					wan_villages.village_id, 
					wan_villages.village_nom, 
					wan_villages.village_icone,
					wan_clan.clan_id, 
					wan_clan.clan_nom, 
					wan_stats.stats_id, 
					wan_stats.stats_village, 
					wan_stats.stats_clan, 
					wan_stats.stats_rang, 
					wan_stats.stats_niveau 
				FROM 
					wan_ninja 
					LEFT JOIN 
						wan_stats 
						ON 
							wan_stats.stats_id=wan_ninja.ninja_id 
					LEFT JOIN 
						wan_villages 
						ON 
							wan_villages.village_id=wan_stats.stats_village 
					LEFT JOIN 
						wan_rangs 
						ON 
							wan_rangs.rang_id=wan_stats.stats_rang 
					LEFT JOIN 
						wan_clan 
						ON 
							wan_clan.clan_id=wan_stats.stats_clan 
				WHERE 
					ninja_last_connect > '".$time."' 
				ORDER BY 
					wan_stats.stats_village 
					ASC";
					
			$res = ktPDO::get()->query($req);
			
			if (!$res OR $res->rowCount() == 0)
			{
				return FALSE;
			}
			else
			{
				$return = array();
				
				while ($ret = $res->fetch(PDO::FETCH_ASSOC))
					$return[$ret['ninja_id']] = $ret;
					
				$res->closeCursor();
				
				file_put_contents($file, serialize($return));
				
				return $return;
			}
		}
		
	}
}

/*$req = "SELECT boite_id  
FROM  wan_boite 
WHERE  boite_ninja_1 LIKE  'system'";
$res = ktPDO::get()->query($req);
$in = '';
while ($ret = $res->fetch(PDO::FETCH_ASSOC))
	$in .= "'".$ret['boite_id']."',";
$in = substr($in,0,-1);
$req = "DELETE FROM wan_lettres WHERE lettre_boite IN (".$in.") OR lettre_ninja LIKE 'system'";
$res = ktPDO::get()->exec($req);
echo 'lettres supprimées : '.$res;
$req = "DELETE FROM wan_boite WHERE boite_ninja_1 LIKE 'system'";
$res = ktPDO::get()->exec($req);
echo 'boites supprimées : '.$res;*/

?>