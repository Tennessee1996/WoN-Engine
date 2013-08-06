<?php
	session_start();
	
	$largeur  = 140;
	$hauteur  = 30;
	$longueur = 7;
	
	$liste = '123456789abcdefghjkmnpqrstuvwxz';
	$code    = '';
	$counter = 0;
	$image = @imagecreate($largeur, $hauteur) or die('Un problème est survenue avec la librairie GD !');
	
	for ($i=0; $i<20; $i++)
	{
		imageline($image, mt_rand(1,$largeur), mt_rand(1,$hauteur), mt_rand(1,$largeur), mt_rand(1,$hauteur), imagecolorallocate($image, mt_rand(200,255), mt_rand(200,255),mt_rand(200,255)));
	}
	
	for ($i=0, $x=0; $i<$longueur; $i++)
	{
		$charactere = substr($liste, rand(0, strlen($liste)-1), 1);
		$x += mt_rand(15,20);
		
		imagechar($image, mt_rand(3,5), $x, mt_rand(3,9), $charactere, imagecolorallocate($image, mt_rand(0,155), mt_rand(0,155), mt_rand(0,155)));
		
		$code .= $charactere;
	}
	header('Content-Type: image/jpeg');
	imagejpeg($image);
	imagedestroy($image);
	
	$_SESSION['captcha'] = $code;
?>