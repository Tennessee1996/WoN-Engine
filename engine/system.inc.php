<?php

/*
 * Gestion des erreurs
 */
error_reporting(E_ALL ^ E_NOTICE);
#error_reporting(0);

/*
 * On inclut la librairie Security
 */
require 'class/wanSecurity.class.php';
$security = new wanSecurity;


/*
 * Création de la ressource MySQL
 */
require 'apps/ktPDO/ktPDO.class.php';

	ktPDO::setHost('localhost');
	ktPDO::setBase('wayofninja_current');
	ktPDO::setUser('root');
	ktPDO::setPassword('root');

$pdo = ktPDO::get();
$pdo->query("SET NAMES 'utf8'");

/*
 * Main Require de l'application
 */
require 'class/wanEngine.class.php';
require 'apps/rmail/Rmail.php';

/*
 * Require des classes par AutoLoad;
 */
function __autoload($_class)
{
	require 'class/'.$_class.'.class.php';
}

if (wanEngine::existConnect())
{
	/*
	 * Rafraichissement CSRF
	 */
	wanSecurity::csrfRefresh();

	/*
	 * Require des librairies de la version alpha
	 */
	require 'libraries/element.lib.php';
	require 'libraries/competence.lib.php';
	
	/*
	 * Ecoute de la déconnexion
	 */
	if ($_GET['page'] == 'deconnexion')
	{
		wanSecurity::csrf();
		wanEngine::unsetConnect();
	}
	
	/*
	 * Capture AJAX
	 */
	if ($_GET['page'] == 'ajax')
		wanEngine::ajax();
	
	if (wanEngine::isAjax())
		exit;
	
	/*
	 * Instanciation du ninja
	 */
	$ninja = new wanNinja($_SESSION['ninja']['id']);
	
	/*
	 * Gestion du déséquipement
	 */
	if (!empty($_GET['desequiper']))
	{
		wanSecurity::csrf();
		wanStuff::unsetStuff($ninja, $_GET['desequiper']);
	}
	
	/*
	 * Actualisation diverses du ninja
	 */
	$ninja->majTime();
	$notifications = $ninja->getNotifications();

	if ($_GET['page'] == 'notifications' AND $notifications['count'] > 0)
	{
		$ninja->checkNotifications($notifications['last']);
	}
	if ($_GET['page'] == 'appartement' AND $_GET['mode'] == 'boite' AND $notifications['boite'] > 0)
	{
		$ninja->checkNotifications($notifications['last'], $type = 'wanBoite');
	}
	if ($_GET['page'] == 'arene' AND $notifications['arene'] > 0)
	{
		$ninja->checkNotifications($notifications['last'], $type = 'wanCombat');
	}
}

?>