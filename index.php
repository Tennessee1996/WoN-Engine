<?php

session_start();
header ("Content-Type: text/html; charset=UTF-8");
date_default_timezone_set('Europe/Paris');
require 'engine/system.inc.php';
$wan_version = '0.90';

ob_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<title>~Way of Ninja~</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="content-language" content="fr">
<meta name="language" content="fr">
<meta name="description" content="Way of Ninja est un jeu gratuit par navigateur inspiré de Naruto. (v <?php echo $wan_version; ?>)" />
<meta name="keywords" content="jeu, navigateur, gratuit, naruto, ninja, japon, élevage, web, php, ogame, francais" />
<meta name="robots" content="index, follow, all" />
<link rel="stylesheet" href="medias/design.css" media="all" type="text/css">
<!--[if IE]><link type="text/css" href="medias/design-ie.css" rel="stylesheet" /><![endif]-->
<script type="text/javascript" src="js/jquery-min.js"></script>
<script type="text/javascript" src="js/won-public.js"></script>
<?php if (wanEngine::existConnect()) : ?>
<script type="text/javascript" src="js/won-game.js"></script>
<?php endif; ?>
<link rel="shortcut icon" href="medias/favicon.ico">

<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-21038571-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>

</head>

<body>
	<div id="page">
		<div id="header">
			<div id="header-bloc">
				<?php include 'engine/includes/bandeau.inc.php'; ?>
			</div>
		</div>
		<?php include 'engine/includes/menu-global.php'; ?>
		<div id="bloc">
			<div id="content">
				<?php
				if (wanEngine::existConnect())
				{	
					$arrayPages = array('accueil' => 'site/news',
										'ninja' => 'jeu/ninja',
										'hopital' => 'jeu/hopital',
										'arene' => 'jeu/arene', 
										'academie' => 'jeu/academie',
										'caserne' => 'jeu/caserne', 
										'appartement' => 'jeu/appartement', 
										'centre' => 'jeu/centre',  
										'aide' => 'site/aide', 
										'marche' => 'jeu/market', 
										'agora' => 'jeu/agora', 
										'temple' => 'jeu/temple', 
										'debuter' => 'jeu/begin', 
										'profil' => 'jeu/profil', 
										'kage' => 'jeu/kage',
										'akatsuki' => 'jeu/akatsuki', 
										'clan' => 'jeu/clan', 
										'admin' => 'jeu/admin',
										'moderation' => 'jeu/moderation', 
										'notifications' => 'jeu/notifications');
				}			
				else
				{
					$arrayPages = array('accueil' => 'site/accueil',
										'connexion' => 'site/connexion',
										'inscription' => 'site/inscription', 
										'aide' => 'site/aide', 
										'mot-de-passe' => 'site/password', 
										'partenaires' => 'site/partenaires', 
										'news' => 'site/news', 
										'admin' => 'jeu/admin');
				}
				
				if (!empty($_GET['page']))
				{
					if (array_key_exists(strtolower($_GET['page']),$arrayPages))
							include 'pages/'.$arrayPages[strtolower($_GET['page'])].'.php';
					else
							include 'pages/'.$arrayPages['accueil'].'.php';
				}
				else
					include 'pages/'.$arrayPages['accueil'].'.php';
				?>
			</div>
			<div id="menu">
				<?php include 'engine/includes/menu.inc.php'; ?>
			</div>
		</div>
		<div id="footer">
			<div id="adsense">
				<script type="text/javascript"><!--
					google_ad_client = "ca-pub-5744459312956564";
					/* Way of Ninja Content */
					google_ad_slot = "1992950721";
					google_ad_width = 468;
					google_ad_height = 60;
					//-->
				</script>
				<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
			</div>
			<p>Way of Ninja est un jeu gratuit inspiré de Naruto, version <?php echo $wan_version; ?>.<br />Tous droits réservés.<br />
			Une production <a href="http://www.akibatech.fr" target="_blank" title="Réalisé par AkibaTech">AkibaTech</a>.<br />
			<a href="index.php?page=aide&mode=cgu" title="Conditions Générales d'Utilisation">CGU</a> - 
			<a href="index.php?page=aide&mode=confidentialite" title="Déclaration de confidentialité">Confidentialité</a> - 
			<a href="index.php?page=aide&mode=legales" title="Mentions légales">Mentions légales</a></p>
		</div>
	</div>
</body>
</html>
<?php
ob_end_flush();
exit;
?>
