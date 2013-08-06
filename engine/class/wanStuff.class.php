<?php

/*
 * Manipule l'équipement du ninja
 * Note : entièrement statique
 */
 
class wanStuff
{
	private function __construct() { }
	private function __clone() { }
	
	/*
	 * Lecture de la base et construction du fichier de cache d'équipement
	 */
	public static function stuffBuildCache($id_ninja)
	{
		$file = 'cache/equipements/equipement_'.$id_ninja.'.html';
		
		$requete_equipement = "SELECT 
									wan_equipement.*, 
									commerce_id, commerce_nom, commerce_valeur, commerce_effet, commerce_clan, 
									commerce_image, commerce_type, commerce_rang 
								FROM 
									wan_equipement 
									LEFT JOIN 
										wan_commerces 
										ON 
										wan_commerces.commerce_id=wan_equipement.equipement_objet_id 
								WHERE 
									equipement_ninja_id='".$id_ninja."'";
		
		$resultat_equipement = ktPDO::get()->query($requete_equipement);
		
		if (!$resultat_equipement)
			return FALSE;
		else
		{
			$equipement = array();
			
			while ($retour_equipement = $resultat_equipement->fetch(PDO::FETCH_ASSOC))
				$equipement[] = $retour_equipement;
			
			$resultat_equipement->closeCursor();
			
			$count_equipement = count($equipement);
			
			if ($count_equipement == 2)
			{
				$new_array_equipement[$equipement[0]['equipement_type']] = $equipement[0];
				$new_array_equipement[$equipement[1]['equipement_type']] = $equipement[1];
			}
			elseif ($count_equipement == 1)
				$new_array_equipement[$equipement[0]['equipement_type']] = $equipement[0];
			else
				$new_array_equipement = array();
			
			$equipement = array();
			$equipement = $new_array_equipement;
			
			$equipement = serialize($equipement);
			$state = file_put_contents($file, $equipement);
			
			if (!$state)
				return TRUE;
			else
				return FALSE;
		}
	}
	
	/*
	 * Suppression du fichier de cache utilisateur
	 */
	public static function stuffDeleteCache($id_ninja)
	{	
		$file = 'cache/equipements/equipement_'.$id_ninja.'.html';
		
		if (file_exists($file))
		{
			@unlink($file);
			self::stuffBuildCache($id_ninja);
		}
		else
			return FALSE;
	}
	
	/*
	 * Récupere, lit et retourne le fichier de cache utilisateur
	 */
	public static function getStuff($id_ninja)
	{	
		$file = 'cache/equipements/equipement_'.$id_ninja.'.html';
		
		if (file_exists($file))
		{
			$equipement = file_get_contents($file);
			$equipement = unserialize($equipement);
			return $equipement;
		}
		else
		{
			self::stuffBuildCache($id_ninja);
			self::getStuff($id_ninja);
		}
	}
	
	/*
	 * Déséquipe un équipement selon son type
	 */
	public static function unsetStuff($_ninja, $mode)
	{
		if ($_ninja instanceof wanNinja)
		{
			$modes = array('arme', 'armure');
			
			if (in_array($mode, $modes))
			{
				if (array_key_exists($mode, $_ninja->ninja['equipement']))
					$objet_id = $_ninja->ninja['equipement'][$mode]['commerce_id'];
				else
				{
					$_SESSION['flash'] = "Tu n'as pas d'".$mode." équipée";
					wanEngine::redirect('index.php?page=appartement');
				}
				
				$objet_name = $_ninja->ninja['equipement'][$mode]['commerce_nom'];
				$state = $_ninja->ninjaRemoveEquipement($_ninja->ninja['equipement'][$mode]);
				
				if ($state)
				{
					$_SESSION['flash'] = "Tu ne portes plus ".$objet_name;
					wanEngine::redirect('index.php?page=appartement');
				}
				else
				{
					$_SESSION['flash'] = "Impossible d'enlever ".$objet_name;
					wanEngine::redirect('index.php?page=appartement');
				}
			}
			else
			{
				$_SESSION['flash'] = "Type d'objet non-reconnu";
				wanEngine::redirect('index.php?page=appartement');
			}
			
		}
		else
			wanEngine::setError('ninja not reconized');
	}
}