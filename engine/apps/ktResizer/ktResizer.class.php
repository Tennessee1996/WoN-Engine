<?php

/**

	ktResizer
	Redimmensionnement d'image en conservant les proportions et possibilité d'affiché un fond coloré 
	pour les images aux dimensions batardes
	
	Le : 26.04.2011
	Par : Akira777 (akira@aisukijapan.fr)
	
	@ Librement inspiré du script 'HolyImage' de Marcos Luiz Cassarini Taranta (marcos.taranta@gmail.com)
	
	/////// Exemple 1 : simple resize me

		$image = new ktResizer('sunset.png');
		$image->resize(640, 480);
		$image->save('new_sunset.png');
		
		echo '<img src="new_sunset.png" alt="" />';

	/////// Exemple 2 : i'm fixed

		$image = new ktResizer('beach.jpg');
		$image->setFixedSize(TRUE);							# fixed size
		$image->setFixedColor(255, 255, 128);				# "sand" like color :D
		$image->resize(800, 600);
		$image->save('new_beach.jpg');
	
		echo '<img src="new_beach.jpg" alt="">';

	/////// Exemple 3 : i'm looks like a superstar

		$image = new ktResizer('grassland.gif');
		$image->setEffect('negative');						# negative, blur, gray or relief
		$image->save(); 									# without filename argument the original file will be erased
		
		echo '<img src="grassland.jpg" alt="">';

	/////// Exemple 4 : change compression

		$image = new ktResizer('my_house.jpg');
		$image->setQuality(2);								# 3 is better, 0 is worst
		$image->save('my_house_compressed.jpg');
		
		echo '<img src="my_house_compressed.jpg" alt="">';

**/

class ktResizer
{	
	private $mime_type = NULL;   // mime-type de l'image
	private $handler = NULL;     // ressource de l'image
	private $filename = "";      // nom de l'image originale
	private $new_filename = "";  // nouveau de l'image après execution du script
	
	private $quality = NULL;     // qualité de l'image
	private $fixed_size = FALSE; // dimensions fixe ?
	private $bgR = 0;            // indice couleur rouge
	private $bgG = 0;            // indice couleur verte
	private $bgB = 0;            // indice couleur bleue
	
	/*
	 *	[méthode magique]
	 *	Initialize la classe avec le nom de fichier de l'image
	 *	@params string $filename : nom de l'image
	 *	@return void
	 *
	 */
	public function __construct($filename = "")
	{
		if (!empty($filename) AND file_exists($filename))
		{
			$this->filename = $filename;
			$this->setMimeType();
			
			if ($this->mime_type == 'image/jpeg')
				$this->handler = imagecreatefromjpeg($filename);
			else if ($this->mime_type == 'image/gif')
				$this->handler = imagecreatefromgif($filename);
			else if ($this->mime_type == 'image/png')
				$this->handler = imagecreatefrompng($filename);
			else
				trigger_error('Can\'t initialize this image', E_USER_ERROR);
		}
		else
			trigger_error('Incorrect file given', E_USER_ERROR);
	}
	
	/*
	 *	[méthode magique]
	 *	Libére la mémoire en détruisant le flux de l'image
	 *	@params void
	 *	@return void
	 *
	 */
	public function __destruct()
	{
		imagedestroy($this->handler);
	}
	
	/* 
	 *	Enregistre l'image après redimmensionnement, écrase l'originale si pas de nom de fichier spécifié
	 *	@params string $new_filename : nouveau nom de fichier
	 *	@return void
	 *
	 */
	public function save($new_filename = "")
	{
		empty($new_filename) ? $this->new_filename = $this->filename : $this->new_filename = $new_filename;
		
		if ($this->mime_type == 'image/gif')
		{
			imagegif($this->handler, $this->new_filename);
		}
		else if ($this->mime_type == 'image/jpeg')
		{
			if (isset($this->quality))
				imagejpeg($this->handler, $this->new_filename, $this->quality);
			else
				imagejpeg($this->handler, $this->new_filename);
		}
		else if ($this->mime_type == 'image/png')
		{
			if (isset($this->quality))
				imagepng($this->handler, $this->new_filename, $this->quality);
			else
				imagepng($this->handler, $this->new_filename);
		}
		else
			trigger_error('Invalid input mime type', E_USER_ERROR);
	}
	
	/* 
	 *	Envoi le contenu de l'image
	 *	@return void
	 *
	 */
	public function send()
	{
		header('Content-type: '.$this->mime_type);

		if ($this->mime_type == 'image/gif')
		{
			imagegif($this->handler);
		}
		else if ($this->mime_type == 'image/jpeg')
		{
			if (isset($this->quality))
				imagejpeg($this->handler, null, $this->quality);
			else
				imagejpeg($this->handler);
		}
		else if ($this->mime_type == 'image/png')
		{
			if (isset($this->quality))
				imagepng($this->handler, null, $this->quality);
			else
				imagepng($this->handler);
		}
		else
			trigger_error('Invalid input mime type', E_USER_ERROR);
	}
	
	/*
	 *	Ajoute un effet GD sur l'image
	 *	@params string $effect : nom de l'effet voulu
	 *	@return multiple : nom de l'effet appliqué ou FALSE si l'effet n'existe pas
	 *
	 */
	public function setEffect($effect = NULL)
	{
		if (isset($effect))
		{
			$array_effect = array('blur' => IMG_FILTER_GAUSSIAN_BLUR, 
								  'relief' => IMG_FILTER_EMBOSS, 
								  'gray' => IMG_FILTER_GRAYSCALE, 
								  'negative' => IMG_FILTER_NEGATE);
								  
			if (array_key_exists($effect, $array_effect))
			{
				imagefilter($this->handler, $array_effect[$effect]);
				return $array_effect[$effect];
			}
			else
				return FALSE;
		}
	}
	
	/*
	 *	Analyse un nom de fichier pour déterminer le mime-type adéquat
	 *	@params void
	 *	@return void
	 * 
	 */
	private function setMimeType()
	{
		$extension = explode(".", $this->filename);
		$extension = $extension[count($extension) - 1];
		
		if ($extension == 'gif')
			$this->mime_type = 'image/gif';
		else if ($extension == 'jpg' || $extension == 'jpeg')
			$this->mime_type = 'image/jpeg';
		else if ($extension == 'png')
			$this->mime_type = 'image/png';
		else
			trigger_error('Mime-type not recognized');
	}

	/*
	 *	Fixe ou non les dimensions d'une image
	 *	@params bool $bool : oui ou non
	 *	@return void
	 *
	 */
	public function setFixedSize($bool = TRUE)
	{
		if (is_bool($bool))
			$this->fixed_size = $bool;
		else
			trigger_error('Erf ! It\'s binary ! :D', E_USER_ERROR);
	}
	
	/*
	 *	Indique une couleur de fond RGB
	 *	@params int $bgR : indice de couleur rouge
	 *	@params int $bgG : indice de couleur verte
	 *	@params int $bgB : indice de couleur bleue
	 *	@return void
	 *
	 */
	public function setFixedColor($bgR = 0, $bgG = 0, $bgB = 0)
	{
		$this->bgR = $bgR;
		$this->bgG = $bgG;
		$this->bgB = $bgB;
	}
	
	/*
	 *	Attribut une compression à l'image
	 *	@params int $quality : valeur de la compression
	 *	@return bool : TRUE si la valeur a été attribuée, sinon FALSE
	 *
	 */
	public function setQuality($quality = 0)
	{
		$array_quality = array('jpeg' => array(3 => 100, 2 => 75, 1 => 50, 0 => 25), 
							   'png' => array(3 => 9, 2 => 7, 1 => 5, 0 => 3));

		if (isset($this->handler))
		{
			if ($this->mime_type == 'image/png' AND $quality >= 0 AND $quality <= 3)
				$this->quality = $array_quality['png'][$quality];
			else if ($this->mime_type == 'image/jpeg' AND $quality >= 0 AND $quality <= 3)
				$this->quality = $array_quality['jpeg'][$quality];
			else
				$this->quality = NULL;
				
			if ($this->quality != NULL)
				return TRUE;
			else
				return FALSE;
		}
		else
			trigger_error('Invalid handler', E_USER_ERROR);
	}
	
	/*
	 *	Redimensionne une image en conservant les proportions
	 *	@params int $w : largeur
	 *	@params int $h : hauteur
	 *	@return void
	 *
	 */
	public function resize($w, $h)
	{
		if (isset($this->handler))
		{
			$ow = imagesx($this->handler);
			$oh = imagesy($this->handler);
			
			$zero = false;
			
			if ($w <= 0)
			{
				$zero = true;
				$w = $ow;
			}
			
			if($h <= 0)
			{
				$zero = true;
				$h = $oh;
			}
			
			$rw = $ow / $w; 
			$rh = $oh / $h;

			$r = $rw > $rh ? $rw : $rh;
			
			$res_w = $ow / $r;
			$res_h = $oh / $r;			
			
			if ($this->fixed_size && !$zero)
			{
				$img = imagecreatetruecolor($w, $h);
				$bgcolor = imagecolorallocate($img, $this->bgR, $this->bgG, $this->bgB);
				imagefill($img, 0, 0, $bgcolor);
				imagecopyresampled($img, $this->handler, ($w - $res_w) / 2, ($h - $res_h) / 2, 0, 0, $res_w, $res_h, $ow, $oh);
			}
			else
			{
				$img = imagecreatetruecolor($res_w, $res_h);
				imagecopyresampled($img, $this->handler, 0, 0, 0, 0, $res_w, $res_h, $ow, $oh);
			}
			
			$this->handler = $img;
		}
		else
			trigger_error('Handler not exists', E_USER_ERROR);
	}
}

?>