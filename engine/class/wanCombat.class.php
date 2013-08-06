<?php

class wanCombat
{
	// instance des ninjas
	private $_combattant;
	private $_adversaire;
	
	// Joueur actuel
	private $ninja_current;
	
	// données temporaires
	private $combattant_vie;
	private $adversaire_vie;
	private $combattant_attaque_min;
	private $combattant_attaque_max;
	private $adversaire_attaque_min;
	private $adversaire_attaque_max;
	private $combattant_coup_min;
	private $combattant_coup_max;
	private $adversaire_coup_min;
	private $adversaire_coup_max;
	
	// données du combat
	public $combat;
	private $gain_ryos;
	private $gain_exp;
	private $count_tours = 0;
	private $couleur_combattant = '#5a8e22';
	private $couleur_adversaire = '#8d363b';
	private $coup_critique = NULL;
	private $coup_miss = NULL;
	
	// texte du combat
	public $html_out = "";
	
	// message de retour
	public static $advert = "";
	
	// redirection après execution
	public static $redirection = "";
	
	/*
	 * Constructeur : recquiert un identifiant de combat
	 */
	public function __construct($combat_id)
	{
		$req = "SELECT 
					* 
				FROM 
					wan_arene 
				WHERE 
					arene_id = ".ktPDO::get()->quote($_GET['id'])." 
				LIMIT 
					1";
					
		$res = ktPDO::get()->query($req);
		
		if ($res == 1)
		{
			$this->combat = $res->fetch(PDO::FETCH_ASSOC);
			$res->closeCursor();
			
			return TRUE;
		}
		else
		{
			self::$advert .= "Ce combat n'existe pas !";
			self::$redirection .= "index.php?page=arene";
			return FALSE;
		}
	}
	
	/*
	 * Initialise un joueur combattant ou adversaire
	 */
	public function initJoueur($type, $joueur_id)
	{
		switch ($type)
		{
			case 'combattant' :
			
				$this->_combattant = new wanNinja($joueur_id, TRUE);
				
				if ($this->_combattant instanceof wanNinja)
					return TRUE;
				else
					return FALSE;
			
			break;
			
			case 'adversaire' :
			
				$this->_adversaire = new wanNinja($joueur_id, TRUE);
				
				if ($this->_adversaire instanceof wanNinja)
					return TRUE;
				else
					return FALSE;
			
			break;
		}
	}
	
	/*
	 * Transmet l'instance d'un ninja à une variable joueur
	 */
	public function giveJoueur($type, $joueur_instance)
	{
		switch ($type)
		{
			case 'combattant' :
			
				$this->_combattant = $joueur_instance;
				
				if ($this->_combattant instanceof wanNinja)
					return TRUE;
				else
					return FALSE;
			
			break;
			
			case 'adversaire' :
			
				$this->_adversaire = $joueur_instance;
				
				if ($this->_adversaire instanceof wanNinja)
					return TRUE;
				else
					return FALSE;
			
			break;
		}
	}
	
	/*
	 * Initialise un PNJ
	 */
	public function initPNJ()
	{
		$this->_combattant = clone $this->_adversaire;
		
		switch ($this->combat['arene_type'])
		{
			case 'nukenin' :
				
				$this->_combattant->ninja['ninja_avatar'] = "nukenin.jpg";
				$this->_combattant->ninja['stats_vie'] = 12000;
				$this->_combattant->ninja['stats_chakra'] = 20000;
				$this->_combattant->ninja['ninja_login'] = '???';
				$this->_combattant->ninja['stats_carac_force'] = round($this->_combattant->ninja['stats_carac_force'] * 0.8);
				$this->_combattant->ninja['stats_carac_rapidite'] = round($this->_combattant->ninja['stats_carac_rapidite'] * 0.8);
				$this->_combattant->ninja['stats_carac_endurance'] = round($this->_combattant->ninja['stats_carac_endurance'] * 0.8);
				$this->_combattant->ninja['stats_bonus_force'] = 800;
				$this->_combattant->ninja['stats_bonus_rapidite'] = 400;
				$this->_combattant->ninja['stats_bonus_endurance'] = 1000;
				
			break;
			
			case 'sennin' :

				$this->_combattant->ninja['ninja_login'] = 'Dark '.$this->_combattant->ninja['ninja_login'];
				$this->_combattant->ninja['stats_carac_force'] = round($this->_combattant->ninja['stats_carac_force'] * 0.8);
				$this->_combattant->ninja['stats_carac_rapidite'] = round($this->_combattant->ninja['stats_carac_rapidite'] * 0.8);
				$this->_combattant->ninja['stats_carac_endurance'] = round($this->_combattant->ninja['stats_carac_endurance'] * 0.8);
				$this->_combattant->ninja['stats_bonus_force'] = 0;
				$this->_combattant->ninja['stats_bonus_rapidite'] = 0;
				$this->_combattant->ninja['stats_bonus_endurance'] = 0;
			
			break;
		}
	}
	
	/*
	 * Vérifie qu'un combat normal peut avoir lieu
	 */
	public function validCombat_normal()
	{
		self::$redirection = "index.php?page=arene";
		
		if ($this->combat['arene_adversaire'] != $this->_adversaire->ninja['stats_id'])
			self::$advert = "Tu ne peux pas accepter les combats des autres";
		
		if ($this->combat['arene_gagnant'] != '0')
			self::$advert = "Ce combat est déjà fait";
			
		if ($this->combat['arene_adversaire'] == $this->combat['arene_combattant'])
			self::$advert = "Tu ne peux pas te défier toi-même";
			
		if ($this->_combattant->ninjaCheckHealth())
			self::$advert = "Tu ne peux pas te battre le ventre vide";
			
		if ($this->_combattant->ninja['stats_combat_max'] == 0)
		{
			self::supprimerCombat($this->_combattant, $this->combat['arene_id']);
			self::$advert = "Tu as déjà fait tous tes combats aujourd'hui [défi annulé]";
		}
			
		if ($this->_adversaire->ninja['stats_vie'] == 0)
			self::$advert = "Ton adversaire n'a pas assez de vie pour combattre";
			
		if ($this->_adversaire->ninja['stats_combat_max'] == 0)
		{
			self::supprimerCombat($this->_combattant, $this->combat['arene_id']);
			self::$advert = "Ton adversaire a déjà fait tous ses combats aujourd'hui [défi annulé]";
		}
		
		if ($this->_adversaire->ninja['ninja_ban_statut'] == 1)
		{
			self::supprimerCombat($this->_combattant, $this->combat['arene_id']);
			self::$advert = "Ton adversaire a été banni du site [défi annulé]";
		}
		
		if (empty(self::$advert))
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Vérifie qu'un combat special peut avoir lieu
	 */
	public function validCombat_special()
	{
		self::$redirection = "index.php?page=arene";
		
		if ($this->combat['arene_adversaire'] != $this->_adversaire->ninja['stats_id'])
			self::$advert = "Tu ne peux pas accepter les combats des autres";
			
		if ($this->combat['arene_close'] == 1)
			self::$advert = "Ce combat est déjà fait";
			
		if ($this->_adversaire->ninjaCheckHealth())
			self::$advert = "Tu ne peux pas te battre le ventre vide";
			
		if ($this->combat['arene_type'] == 'nukenin' AND $this->_adversaire->ninja['stats_rang'] == 6)
			self::$advert = "Tu es déjà Nukenin";
			
		if ($this->combat['arene_type'] == 'sennin' AND $this->_adversaire->ninja['stats_rang'] == 7)
			self::$advert = "Tu es déjà Sennin";
		
		if (empty(self::$advert))
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Définie un gagnant
	 */
	private function setWinner()
	{
		$this->gagnant = $this->ninja_current;
		
		return;
	}
	
	/*
	 * Prépare les ninjas pour un combat normal
	 */
	private function prepareJoueurs_normal()
	{			
		$this->combattant_vie = $this->_combattant->ninja['stats_vie'];
		$this->adversaire_vie = $this->_adversaire->ninja['stats_vie'];
		$this->calculAttaque('combattant');
		$this->calculAttaque('adversaire');
		$this->calculVitesse('combattant');
		$this->calculVitesse('adversaire');
	}
	
	/*
	 * Prépare les ninjas pour un combat special
	 */
	private function prepareJoueurs_special()
	{		
		$this->combattant_vie = $this->_combattant->ninja['stats_vie'];
		$this->adversaire_vie = $this->_adversaire->ninja['stats_vie'];
		$this->calculAttaque('combattant');
		$this->calculAttaque('adversaire');
		$this->calculVitesse('combattant');
		$this->calculVitesse('adversaire');
	}
	
	/*
	 * Choisit le ninja qui commencera
	 */
	private function selectTour()
	{
		$this->ninja_current = rand(0, 1);
	}
	
	/*
	 * Change le tour du ninja
	 */
	private function changeTour()
	{
		if ($this->ninja_current == 0)
			$this->ninja_current = 1;
		else
			$this->ninja_current = 0;
	}
	
	/*
	 * Couche javascript des combats
	 */
	public function javascriptCombat()
	{
		$out = "";
		
		$out .= '<script language="javascript">';

		for ($x = 0; $x < $this->count_tours+1; $x++)
			$out .= 'setTimeout(\'document.getElementById("'.$x.'").style.visibility = "visible"\', \''.($x * 50).'\');';
		
		$out .= '</script>';
		
		return $out;
	}
	
	/*
	 * Calcul des ryôs gagnés
	 */
	private function calculGainRyos()
	{
		if ($this->gagnant == 0)
		{
			$niveau_gagnant = $this->_combattant->ninja['stats_niveau'];
			$niveau_perdant = $this->_adversaire->ninja['stats_niveau'];
		}
		else
		{
			$niveau_gagnant = $this->_adversaire->ninja['stats_niveau'];
			$niveau_perdant = $this->_combattant->ninja['stats_niveau'];
		}
		
		$difference = (float) $niveau_gagnant / $niveau_perdant;
		$difference = number_format($difference, 1, '.', ' ');
		$difference = (string) $difference;
		
		$tab_multiplicateur = array('0.1' => 10, 
									'0.2' => 9,
									'0.3' => 8, 
									'0.4' => 7, 
									'0.5' => 6,
									'0.6' => 5,
									'0.7' => 4, 
									'0.8' => 3, 
									'0.9' => 2, 
									'1.0' => 1, 
									'1.1' => 0.9, 
									'1.2' => 0.8, 
									'1.3' => 0.7, 
									'1.4' => 0.6,
									'1.5' => 0.5,
									'1.6' => 0.4,
									'1.7' => 0.3,
									'1.8' => 0.2,
									'1.9' => 0.1, 
									'2.0' => 0.05);
									
		if ($difference > '2.0')
			$this->gain_ryos = 5;
		else
			$this->gain_ryos = $tab_multiplicateur[$difference] * 100;
	}
	
	/*
	 * Calcul de l'experience gagnée
	 */
	private function calculGainExp()
	{
		if ($this->gagnant == 0)
		{
			$niveau_gagnant = $this->_combattant->ninja['stats_niveau'];
			$niveau_perdant = $this->_adversaire->ninja['stats_niveau'];
		}
		else
		{
			$niveau_gagnant = $this->_adversaire->ninja['stats_niveau'];
			$niveau_perdant = $this->_combattant->ninja['stats_niveau'];
		}
		
		$difference = (float) $niveau_gagnant / $niveau_perdant;
		$difference = number_format($difference, 1, '.', ' ');
		$difference = (string) $difference;
		
		$tab_multiplicateur = array('0.1' => 9, 
									'0.2' => 8,
									'0.3' => 7, 
									'0.4' => 6, 
									'0.5' => 5,
									'0.6' => 4,
									'0.7' => 3, 
									'0.8' => 2, 
									'0.9' => 1, 
									'1.0' => 0.9, 
									'1.1' => 0.8, 
									'1.2' => 0.7, 
									'1.3' => 0.6, 
									'1.4' => 0.5,
									'1.5' => 0.4,
									'1.6' => 0.3,
									'1.7' => 0.2,
									'1.8' => 0.1,
									'1.9' => 0.07, 
									'2.0' => 0.04);
									
		if ($difference > 2.0)
			$this->gain_exp = 5;
		else
			$this->gain_exp = $tab_multiplicateur[$difference] * 100;
	}
	
	/*
	 * Calcul des chances de ne pas réussir une attaque
	 */
	private function attaqueMiss()
	{
		if ($this->ninja_current == 0)
			$rapidite = ($this->_combattant->ninja['stats_carac_rapidite'] * 1.2) + $this->_combattant->ninja['stats_bonus_rapidite'];
		else
			$rapidite = ($this->_adversaire->ninja['stats_carac_rapidite'] * 1.2) + $this->_adversaire->ninja['stats_bonus_rapidite'];
	
		if ($rapidite < 100)
			$max = 20;
		if ($rapidite >= 100 AND $rapidite < 1000)
			$max = 30;
		if ($rapidite >= 1000)
			$max = 40;
		
		$chance_miss = mt_rand(1, $max);
		
		if ($chance_miss < 5)
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Calcul des changes de faire un coup critique
	 */
	private function attaqueCritique()
	{
		if ($this->ninja_current == 0)
			$rapidite = $this->_combattant->ninja['stats_carac_rapidite'];
		else
			$rapidite = $this->_adversaire->ninja['stats_carac_rapidite'];

		if ($rapidite < 100)
			$max = 10;
		if ($rapidite >= 100 AND $rapidite < 1000)
			$max = 20;
		if ($rapidite >= 1000)
			$max = 30;
		
		$chance_critique = rand(1, $max);
		
		if ($chance_critique < 5)
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Calcul de l'attaque
	 */
	private function calculAttaque($type)
	{
		if ($type == 'combattant')
		{
			$attaquant = $this->_combattant;
			$defenseur = $this->_adversaire;
		}
		else
		{
			$attaquant = $this->_adversaire;
			$defenseur = $this->_combattant;
		}

		$attaque = round($attaquant->ninja['stats_carac_force'] * 1.2) + $attaquant->ninja['stats_bonus_force'];
		$defense = round($defenseur->ninja['stats_carac_endurance'] * 1.2) + $defenseur->ninja['stats_bonus_endurance'];
		
		$coup = $attaque - ( $defense * 0.5 );
		
		if ($coup <= 0)
		{
			$coup = abs($coup);
			$coup = $coup / $defenseur->ninja['stats_niveau'];
		}
			
		$coup = round($coup);
		
		if ($type == 'combattant')
		{
			$this->combattant_attaque_min = round($coup * 0.9);
			$this->combattant_attaque_max = round($coup * 1.1);
		}
		else
		{
			$this->adversaire_attaque_min = round($coup * 0.9);
			$this->adversaire_attaque_max = round($coup * 1.1);
		}
	}
	
	/*
	 * Calcul de la vitesse
	 */
	function calculVitesse($mode = 'combattant')
	{
		if ($mode == 'combattant')
		{
			$rapidite = round($this->_combattant->ninja['stats_carac_rapidite'] * 1.2) + $this->_combattant->ninja['stats_bonus_rapidite'];
			$contre = round($this->_adversaire->ninja['stats_carac_rapidite'] * 1.2) + $this->_adversaire->ninja['stats_bonus_rapidite'];
		}
		else
		{
			$rapidite = round($this->_adversaire->ninja['stats_carac_rapidite'] * 1.2) + $this->_adversaire->ninja['stats_bonus_rapidite'];
			$contre = round($this->_combattant->ninja['stats_carac_rapidite'] * 1.2) + $this->_combattant->ninja['stats_bonus_rapidite'];
		}
		
		$facteur = round($rapidite / $contre);
		
		if ($facteur >= 6)
		{
			$coup_min = 5;
			$coup_max = 7;
		}
		else if ($facteur < 6 AND $facteur >= 4)
		{
			$coup_min = 4;
			$coup_max = 6;
		}
		else if ($facteur < 4 AND $facteur >= 1)
		{
			$coup_min = 3;
			$coup_max = 5;
		}
		else
		{
			$coup_min = 2;
			$coup_max = 4;
		}
		
		if ($mode == 'combattant')
		{
			$this->combattant_coup_min = $coup_min;
			$this->combattant_coup_max = $coup_max;
		}
		else
		{
			$this->adversaire_coup_min = $coup_min;
			$this->adversaire_coup_max = $coup_max;
		}
	}
	
	/*
	 * Clos un combat normal
	 */
	private function endCombat_normal()
	{
		$this->calculGainRyos();
		$this->calculGainExp();
		
		if ($this->gagnant == 0)
		{
			$ninja_gagnant = $this->_combattant;
			$ninja_perdant = $this->_adversaire;
		}
		else
		{
			$ninja_gagnant = $this->_adversaire;
			$ninja_perdant = $this->_combattant;
		}
		

		if ($ninja_perdant->ninja['stats_amulette_guerrier'] == 1) :
			$perte_vie_perdant = ceil(($ninja_perdant->ninja['stats_vie_max'] * 1) / 100);
		else :
			$perte_vie_perdant = ceil(($ninja_perdant->ninja['stats_vie_max'] * 5) / 100);
		endif;

		if ($ninja_gagnant->ninja['stats_amulette_guerrier'] == 1) :
			$perte_vie_gagnant = ceil(($ninja_gagnant->ninja['stats_vie_max'] * 1) / 500);
		else :
			$perte_vie_gagnant = ceil(($ninja_gagnant->ninja['stats_vie_max'] * 5) / 500);
		endif;

		$ninja_perdant->ninja['stats_vie'] - $perte_vie_perdant >= 0 ? 
			$param_vie_perdant = 'stats_vie = stats_vie - '.$perte_vie_perdant : 
			$param_vie_perdant = 'stats_vie = 0';

		$ninja_gagnant->ninja['stats_vie'] - $perte_vie_gagnant >= 0 ? 
			$param_vie_gagnant = 'stats_vie = stats_vie - '.$perte_vie_gagnant : 
			$param_vie_gagnant = 'stats_vie = 0';

		$requete_maj_gagnant = "UPDATE 
									wan_stats 
								SET 
									stats_victoire = stats_victoire + 1, 
									stats_ryos = stats_ryos + ".$this->gain_ryos.", 
									stats_xp = stats_xp +".$this->gain_exp.", 
									stats_combat_max = stats_combat_max - 1,
									".$param_vie_gagnant." 
								WHERE 
									stats_id = ".ktPDO::get()->quote($ninja_gagnant->ninja['stats_id'])." 
								LIMIT 
									1";

		$requete_maj_perdant = "UPDATE 
									wan_stats 
								SET 
									stats_defaite = stats_defaite + 1, 
									stats_xp = stats_xp + 5, 
									stats_combat_max = stats_combat_max - 1, 
									".$param_vie_perdant."
								WHERE 
									stats_id = ".ktPDO::get()->quote($ninja_perdant->ninja['stats_id'])." 
								LIMIT 
									1";

		$requete_maj_combat = "UPDATE 
									wan_arene 
								SET 
									arene_gagnant = ".ktPDO::get()->quote($ninja_gagnant->ninja['stats_id']).", 
									arene_gagnant_ryos = ".$this->gain_ryos.", 
									arene_gagnant_xp = ".$this->gain_exp." 
								WHERE 
									arene_id = ".ktPDO::get()->quote($this->combat['arene_id'])." 
								LIMIT 
									1";

		$resultat_maj = 0;
		$resultat_maj += ktPDO::get()->exec($requete_maj_gagnant);
		$resultat_maj += ktPDO::get()->exec($requete_maj_perdant);
		$resultat_maj += ktPDO::get()->exec($requete_maj_combat);
		
		if ($resultat_maj == 3)
		{
			$log_data = array('winner_id' => $ninja_gagnant->ninja['ninja_id'],
							  'winner_login' => $ninja_gagnant->ninja['ninja_login'],
							  'looser_id' => $ninja_perdant->ninja['ninja_id'],
							  'looser_login' => $ninja_perdant->ninja['ninja_login'],
							  'fight_id' => $this->combat['arene_id'],
							  'exp_awarded' => $this->gain_exp,
							  'ryos_awarded' => $this->gain_ryos, 
							  'mode' => 'normal',
							  'log_to' => 'winner');

			$log = array('ninja' => $ninja_gagnant->ninja['ninja_id'], 'class' => __CLASS__, 'method' => 'endCombat', 'data' => $log_data);

			wanLog::log($log);

			$log_data['log_to'] = 'looser';

			$log = array('ninja' => $ninja_perdant->ninja['ninja_id'], 'class' => __CLASS__, 'method' => 'endCombat', 'data' => $log_data);

			wanLog::log($log);

			$this->html_out .= $ninja_gagnant->ninja['ninja_login']. ' remporte '.$this->gain_ryos.' ryôs et '.$this->gain_exp.' exp.';
			$this->html_out .= '<br /><a href="index.php?page=arene" alt="Retour à l\'arène">Retour</a>';
			return;
		}
		else
		{
			$this->html_out .= '<br />Un problème est survenu, tes stats ne seront pas mis à jour et tu peux recommencer le combat.';
			return;
		}
		return;
	}
	
	/*
	 * Clos un combat normal
	 */
	private function endCombat_partner()
	{
		if ($this->gagnant == 0)
		{
			$ninja_gagnant = $this->_combattant;
			$ninja_perdant = $this->_adversaire;
		}
		else
		{
			$ninja_gagnant = $this->_adversaire;
			$ninja_perdant = $this->_combattant;
		}
		
		if ($ninja_perdant->ninja['stats_amulette_guerrier'] == 1) :
			$perte_vie_perdant = ceil(($ninja_perdant->ninja['stats_vie_max'] * 1) / 100);
		else :
			$perte_vie_perdant = ceil(($ninja_perdant->ninja['stats_vie_max'] * 5) / 100);
		endif;

		if ($ninja_gagnant->ninja['stats_amulette_guerrier'] == 1) :
			$perte_vie_gagnant = ceil(($ninja_gagnant->ninja['stats_vie_max'] * 1) / 500);
		else :
			$perte_vie_gagnant = ceil(($ninja_gagnant->ninja['stats_vie_max'] * 5) / 500);
		endif;

		$ninja_perdant->ninja['stats_vie'] - $perte_vie_perdant >= 0 ? 
			$param_vie_perdant = 'stats_vie = stats_vie - '.$perte_vie_perdant : 
			$param_vie_perdant = 'stats_vie = 0';

		$ninja_gagnant->ninja['stats_vie'] - $perte_vie_gagnant >= 0 ? 
			$param_vie_gagnant = 'stats_vie = stats_vie - '.$perte_vie_gagnant : 
			$param_vie_gagnant = 'stats_vie = 0';
		
		$requete_maj_gagnant = "UPDATE 
									wan_stats 
								SET 
									stats_combat_max = stats_combat_max - 1, 
									".$param_vie_gagnant." 
								WHERE 
									stats_id = ".ktPDO::get()->quote($ninja_gagnant->ninja['stats_id'])." 
								LIMIT 
									1";

		$requete_maj_perdant = "UPDATE 
									wan_stats 
								SET 
									stats_combat_max = stats_combat_max - 1, 
									".$param_vie_perdant."
								WHERE 
									stats_id = ".ktPDO::get()->quote($ninja_perdant->ninja['stats_id'])." 
								LIMIT 
									1";

		$requete_maj_combat = "UPDATE 
									wan_arene 
								SET 
									arene_gagnant = ".ktPDO::get()->quote($ninja_gagnant->ninja['stats_id'])." 
								WHERE 
									arene_id = ".ktPDO::get()->quote($this->combat['arene_id'])." 
								LIMIT 
									1";

		$resultat_maj = 0;
		$resultat_maj += ktPDO::get()->exec($requete_maj_gagnant);
		$resultat_maj += ktPDO::get()->exec($requete_maj_perdant);
		$resultat_maj += ktPDO::get()->exec($requete_maj_combat);
		
		if ($resultat_maj == 3)
		{			
			$log_data = array('winner_id' => $ninja_gagnant->ninja['ninja_id'],
							  'winner_login' => $ninja_gagnant->ninja['ninja_login'],
							  'looser_id' => $ninja_perdant->ninja['ninja_id'],
							  'looser_login' => $ninja_perdant->ninja['ninja_login'],
							  'fight_id' => $this->combat['arene_id'],
							  'exp_awarded' => 0,
							  'ryos_awarded' => 0, 
							  'mode' => 'partner',
							  'log_to' => 'winner');

			$log = array('ninja' => $ninja_gagnant->ninja['ninja_id'], 'class' => __CLASS__, 'method' => 'endCombat', 'data' => $log_data);

			wanLog::log($log);

			$log_data['log_to'] = 'looser';

			$log = array('ninja' => $ninja_perdant->ninja['ninja_id'], 'class' => __CLASS__, 'method' => 'endCombat', 'data' => $log_data);

			wanLog::log($log);

			$this->html_out .= 'Combat amical, pas de gain.';
			$this->html_out .= '<br /><a href="index.php?page=arene" alt="Retour à l\'arène">Retour</a>';
			return;
		}
		else
		{
			$this->html_out .= '<br />Un problème est survenu, tu peux recommencer le combat.';
			return;
		}
	}
	
	/*
	 * Clos un combat special
	 */
	private function endCombat_special()
	{
		switch ($this->gagnant)
		{
			case 1 :
				
				if ($this->combat['arene_type'] == 'sennin')
				{
					$req_combat = "UPDATE wan_arene 
								   SET arene_close = 1, arene_gagnant = ".ktPDO::get()->quote($this->_adversaire->ninja['stats_id'])." 
								   WHERE arene_id = ".ktPDO::get()->quote($this->combat['arene_id'])." 
								   LIMIT 1";
					
					$req_ninja = "UPDATE wan_stats 
								  SET stats_rang = 7
								  WHERE stats_id = ".ktPDO::get()->quote($this->_adversaire->ninja['stats_id'])." 
								  LIMIT 1";
				}
				
				if ($this->combat['arene_type'] == 'nukenin')
				{
					$req_combat = "UPDATE wan_arene 
								   SET arene_close = 1, arene_gagnant = ".ktPDO::get()->quote($this->_adversaire->ninja['stats_id'])." 
								   WHERE arene_id = ".ktPDO::get()->quote($this->combat['arene_id'])." 
								   LIMIT 1";
					
					$req_ninja = "UPDATE wan_stats 
								  SET stats_rang = 6 
								  WHERE stats_id = ".ktPDO::get()->quote($this->_adversaire->ninja['stats_id'])." 
								  LIMIT 1";
					
					$akatsuki = new wanAkatsuki($this->_adversaire);
				}
				
				$res = ktPDO::get()->exec($req_combat);
				$res += ktPDO::get()->exec($req_ninja);
				
			break;
			
			case 0 :
				
				if ($this->combat['arene_type'] == 'sennin')
				{
					$req_combat = "UPDATE wan_arene 
								   SET arene_close = 1 
								   WHERE arene_id = ".ktPDO::get()->quote($this->combat['arene_id'])." 
								   LIMIT 1";
				}
				
				if ($this->combat['arene_type'] == 'nukenin')
				{
					$req_combat = "UPDATE wan_arene 
								   SET arene_close = 1 
								   WHERE arene_id = ".ktPDO::get()->quote($this->combat['arene_id'])." 
								   LIMIT 1";
				}
				
				$res = ktPDO::get()->exec($req_combat);
				
			break;
		}	
		
		if ($res != 0)
		{
			if ($this->gagnant == 1)
			{
				$log_data = array('winner_id' => $this->_adversaire->ninja['ninja_id'],
								  'winner_login' => $this->_adversaire->ninja['ninja_login'],
								  'looser_id' => '',
								  'looser_login' => '',
								  'fight_id' => $this->combat['arene_id'],
								  'exp_awarded' => 0,
								  'ryos_awarded' => 0, 
								  'mode' => $this->combat['arene_type'],
								  'log_to' => 'winner');

				$log = array('ninja' => $this->_adversaire->ninja['ninja_id'], 'class' => __CLASS__, 'method' => 'endCombat', 'data' => $log_data);

				wanLog::log($log);

				$this->html_out .= 'Tu as réussi le test '.$this->combat['arene_type'].'. Te voilà '.ucfirst($this->combat['arene_type']).' !';
				$this->html_out .= '<br /><a href="index.php?page=arene" alt="Retour à l\'arène">Retour</a>';
			}
			else
			{
				$log_data = array('winner_id' => '',
								  'winner_login' => '',
								  'looser_id' => $this->_adversaire->ninja['ninja_id'],
								  'looser_login' => $this->_adversaire->ninja['ninja_login'],
								  'fight_id' => $this->combat['arene_id'],
								  'exp_awarded' => 0,
								  'ryos_awarded' => 0, 
								  'mode' => $this->combat['arene_type'],
								  'log_to' => 'looser');

				$log = array('ninja' => $this->_adversaire->ninja['ninja_id'], 'class' => __CLASS__, 'method' => 'endCombat', 'data' => $log_data);

				wanLog::log($log);

				$this->html_out .= 'Tu n\'as pas réussi le test '.$this->combat['arene_type'];
				$this->html_out .= '<br /><a href="index.php?page=arene" alt="Retour à l\'arène">Retour</a>';
			}
			return;
		}
		else
		{
			$this->html_out .= 'Impossible de sauvegarder le combat, tu peux librement réessayer.';
			return;
		}
	}
	
	/*
	 * Boucle du combat
	 */
	private function boucleCombat()
	{
		while ($this->adversaire_vie >= 0 OR $this->combattant_vie >= 0 OR empty($this->gagnant))
		{
			if ($this->ninja_current == 0)
			{
				$vitesse = mt_rand($this->combattant_coup_min, $this->combattant_coup_max);
				$compte_attaque = 0;
				
				while ($compte_attaque <= $vitesse AND $compte_attaque < 8)
				{
					$this->count_tours++;
					
					$attaque = rand($this->combattant_attaque_min, $this->combattant_attaque_max);
					
					$this->coup_critique = $this->attaqueCritique();
					$this->coup_miss = $this->attaqueMiss();
					
					$this->coup_critique ? $attaque = ceil($attaque * 1.5) : false;
					$this->coup_miss ? $attaque = 0 : false;
					
					$this->adversaire_vie -= $attaque;
					
					if ($this->adversaire_vie <= 0)
					{
						$this->html_out .= '<span style="visibility:hidden;color:'.$this->couleur_combattant.';" id="'.$this->count_tours.'">'
											.$this->_combattant->ninja['ninja_login'].' attaque'
											.'&nbsp;et lui inflige '.$attaque.'. <strong>'.$this->_combattant->ninja['ninja_login'].' remporte la victoire !</strong></span><br />';
						
						$this->setWinner();
						break 2;
					}
					else
					{
						if ($this->coup_miss OR $attaque == 0)
						{
							$this->html_out .= '<span style="visibility:hidden;color:'.$this->couleur_combattant.';" id="'.$this->count_tours.'">'
												.$this->_combattant->ninja['ninja_login'].' loupe son attaque</span><br />';
						}
						else if ($this->coup_critique)
						{
							$this->html_out .= '<span style="visibility:hidden;color:'.$this->couleur_combattant.';font-weight:bold;" id="'.$this->count_tours.'">'
												.$this->_combattant->ninja['ninja_login'].' attaque'
												.'&nbsp;et lui inflige '.$attaque.' par coup critique ! Reste '.$this->adversaire_vie.'</span><br />';
						}
						else
						{
							$this->html_out .= '<span style="visibility:hidden;color:'.$this->couleur_combattant.';" id="'.$this->count_tours.'">'
											   .$this->_combattant->ninja['ninja_login'].' attaque &nbsp;et lui inflige '.$attaque.'. Reste '.$this->adversaire_vie.'</span><br />';
						}
					}
					
					$compte_attaque++;
				}
				
				$this->changeTour();
			}
			else
			{
				$vitesse = mt_rand($this->combattant_coup_min, $this->combattant_coup_max);
				$compte_attaque = 0;
				
				while ($compte_attaque <= $vitesse AND $compte_attaque < 8)
				{
					$this->count_tours++;
					
					$attaque = rand($this->adversaire_attaque_min, $this->adversaire_attaque_max);
					
					$this->coup_critique = $this->attaqueCritique();
					$this->coup_miss = $this->attaqueMiss();
					
					$this->coup_critique == TRUE ? $attaque = ceil($attaque * 1.5) : false;
					$this->coup_miss == TRUE ? $attaque = 0 : false;
					
					$this->combattant_vie -= $attaque;
					
					if ($this->combattant_vie <= 0)
					{
						$this->html_out .= '<span style="visibility:hidden;color:'.$this->couleur_adversaire.';" id="'.$this->count_tours.'">'
											.$this->_adversaire->ninja['ninja_login'].' attaque'
											.'&nbsp;et lui inflige '.$attaque.'. <strong>'.$this->_adversaire->ninja['ninja_login'].' remporte la victoire !</strong></span><br />';
						
						$this->setWinner();
						break 2;
					}
					else
					{
						if ($this->coup_miss OR $attaque == 0)
						{
							$this->html_out .= '<span style="visibility:hidden;color:'.$this->couleur_adversaire.';" id="'.$this->count_tours.'">'
												.$this->_adversaire->ninja['ninja_login'].' loupe son attaque</span><br />';
						}
						else if ($this->coup_critique)
						{
							$this->html_out .= '<span style="visibility:hidden;color:'.$this->couleur_adversaire.';font-weight:bold;" id="'.$this->count_tours.'">'
												.$this->_adversaire->ninja['ninja_login'].' attaque'
												. '&nbsp;et lui inflige '.$attaque.' par coup critique ! Reste '.$this->combattant_vie.'</span><br />';
						}
						else
						{
							$this->html_out .= '<span style="visibility:hidden;color:'.$this->couleur_adversaire.';" id="'.$this->count_tours.'">'
											   .$this->_adversaire->ninja['ninja_login'].' attaque &nbsp;et lui inflige '.$attaque.'. Reste '.$this->combattant_vie.'</span><br />';
						}
					}
					
					$compte_attaque++;
				}
				
				$this->changeTour();
			}
		}
		
		return;
	}
	
	/*
	 * Fait un combat normal
	 */
	public function processCombat_normal()
	{		
		// affichage de l'en-tête
		$this->addHeadToCombat();
		
		// préparation des joueurs
		$this->prepareJoueurs_normal();
		
		// choix du joueur qui combattra le premier
		$this->selectTour();
		
			$this->html_out .= '<p>';
		
		// lancement de la boucle du combat
		$this->boucleCombat();
		
			$this->html_out .= '<span style="visibility:hidden;" id="'.++$this->count_tours.'">';
		
		// procédure de fin de combat
		if ($this->_combattant->_multi->multiCheckPartner($this->_adversaire->ninja['ninja_id']))
			$this->endCombat_partner();
		else
			$this->endCombat_normal();
		
			$this->html_out .= '</span>';
			$this->html_out .= '</p>';
		
		// sauvegarde du combat
		$this->writeCombat();
		
		return;
	}
	
	/*
	 * Fait un combat spécial
	 */
	public function processCombat_special()
	{		
		// affichage de l'en-tête
		$this->addHeadToCombat();
		
		// préparation des joueurs
		$this->prepareJoueurs_special();
		
		// choix du joueur qui combattra le premier
		$this->selectTour();
		
			$this->html_out .= '<p>';
		
		// lancement de la boucle du combat
		$this->boucleCombat();
		
			$this->html_out .= '<span style="visibility:hidden;" id="'.++$this->count_tours.'">';
		
		// procédure de fin de combat
		$this->endCombat_special();
		
			$this->html_out .= '</span>';
			$this->html_out .= '</p>';
		
		// sauvegarde du combat
		$this->writeCombat();
		
		return;
	}
	
	/*
	 * En-tête du combat
	 */
	private function addHeadToCombat()
	{
		$this->html_out .= '<table style="width:100%;height:120px;text-align:center;">';
		$this->html_out .= '<tr>';
		
		if (!array_key_exists('arme', $this->_combattant->ninja['equipement']))
			$this->html_out .= '<td><img src="medias/objets/nashi.jpg" style="width:50px;" />';
		else
			$this->html_out .= '<td><img src="medias/objets/'.$this->_combattant->ninja['equipement']['arme']['commerce_image'].'" style="width:50px;" />';
		
		if (!array_key_exists('armure', $this->_combattant->ninja['equipement']))
			$this->html_out .= '<img src="medias/objets/nashi.jpg" style="width:50px;" /></td>';
		else
			$this->html_out .= '<img src="medias/objets/'.$this->_combattant->ninja['equipement']['armure']['commerce_image'].'" style="width:50px;" /></td>';
		
		$this->html_out .= '<td><img src="uploads/'.$this->_combattant->ninja['ninja_avatar'].'" style="width:100px;border:1px solid black;" /></td>';
		$this->html_out .= '<td style="valign:middle;font-size:18px;font-weight:bold;">&nbsp;Vs.&nbsp;</td>';
		$this->html_out .= '<td><img src="uploads/'.$this->_adversaire->ninja['ninja_avatar'].'" style="width:100px;border:1px solid black;" /></td>';
		
		if (!array_key_exists('arme', $this->_adversaire->ninja['equipement']))
			$this->html_out .= '<td><img src="medias/objets/nashi.jpg" style="width:50px;" />';
		else
			$this->html_out .= '<td><img src="medias/objets/'.$this->_adversaire->ninja['equipement']['arme']['commerce_image'].'" style="width:50px;" />';
		
		if (!array_key_exists('armure', $this->_adversaire->ninja['equipement']))
			$this->html_out .= '<img src="medias/objets/nashi.jpg" style="width:50px;" /></td>';
		else
			$this->html_out .= '<img src="medias/objets/'.$this->_adversaire->ninja['equipement']['armure']['commerce_image'].'" style="width:50px;" /></td>';
		
		$this->html_out .= '</tr>';
		$this->html_out .= '</table>';
		
		return;
	}
	
	/*
	 * Archive dans un fichier le combat
	 */
	public function writeCombat()
	{
		$filename = 'combat_'.date('d', wanEngine::myTimestamp($this->combat['arene_datetime'])).'_'.$this->combat['arene_id'].'.html';
		
		$this->html_out_save = str_replace('hidden', 'visible', $this->html_out);
		file_put_contents('cache/combats/'.$filename, $this->html_out_save);
		
		return;
	}
	
	/*
	 * Récupére les combats reçus
	 */
	public static function combatArene_Receipt($ninja_id)
	{
		$ninja_id = substr($ninja_id, 0, 16);
		
		$req = "SELECT 
					wan_arene.*, 
					ninja_id, ninja_login, 
					stats_id, stats_niveau, stats_rang, stats_clan, 
					rang_id, rang_nom, 
					clan_id, clan_nom 
				FROM 
					wan_arene 
						LEFT JOIN 
							wan_ninja 
							ON 
								wan_ninja.ninja_id = wan_arene.arene_combattant 
						LEFT JOIN 
							wan_stats 
							ON 
								wan_stats.stats_id = wan_arene.arene_combattant 
						LEFT JOIN 
							wan_rangs 
							ON 
								wan_rangs.rang_id = wan_stats.stats_rang 
						LEFT JOIN 
							wan_clan 
							ON 
								wan_clan.clan_id = wan_stats.stats_clan 
				WHERE 
					arene_adversaire = ".ktPDO::get()->quote($ninja_id)." 
				ORDER BY 
					arene_gagnant ASC";
		
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			return FALSE;
		}
		else
		{
			$out = array();
			
			while ($ret = $res->fetch(PDO::FETCH_ASSOC))
				$out[] = $ret;
			
			$res->closeCursor();
			
			return $out;
		}
	}
	
	/*
	 * Récupére les combats envoyés
	 */
	public static function combatArene_Sended($ninja_id)
	{
		$ninja_id = substr($ninja_id, 0, 16);
		
		$req = "SELECT 
					wan_arene.*, 
					ninja_id, ninja_login, 
					stats_id, stats_niveau, stats_rang, stats_clan, 
					rang_id, rang_nom, 
					clan_id, clan_nom 
				FROM 
					wan_arene 
						LEFT JOIN 
							wan_ninja 
							ON 
								wan_ninja.ninja_id = wan_arene.arene_adversaire 
						LEFT JOIN 
							wan_stats 
							ON 
								wan_stats.stats_id = wan_arene.arene_adversaire 
						LEFT JOIN 
							wan_rangs 
							ON 
								wan_rangs.rang_id = wan_stats.stats_rang 
						LEFT JOIN 
							wan_clan 
							ON 
								wan_clan.clan_id = wan_stats.stats_clan 
				WHERE 
					arene_combattant = ".ktPDO::get()->quote($ninja_id)." 
				ORDER BY 
					arene_gagnant ASC";
		
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			return FALSE;
		}
		else
		{
			$out = array();
			
			while ($ret = $res->fetch(PDO::FETCH_ASSOC))
				$out[] = $ret;
			
			$res->closeCursor();
			
			return $out;
		}
	}
	
	/*
	 * Ajoute un ou plusieurs combats
	 */
	public static function ajouterCombat($ninja_instance, $id_combattant, $id_adversaire, $nombre = 1, $type = 'normal')
	{
		self::$redirection = 'index.php?page=arene&mode=inscription';
		
		if ($ninja_instance instanceof wanNinja)
		{	
			if ($id_adversaire == $id_combattant)
				self::$advert = "Tu ne peux pas te défier toi-même";
			
			if ($ninja_instance->ninjaCheckHealth())
				self::$advert = "Tu ne peux pas te battre le ventre vide !";
			else
			{
				$req = "INSERT INTO 
							wan_arene (arene_id, arene_combattant, arene_adversaire, arene_datetime, arene_gagnant, arene_type) 
						VALUES ";
				
				$i = 0;
				
				while ($i != $nombre)
				{
					$id_combat = wanEngine::generateId(32);
					$req .= "('".$id_combat."', '".$id_combattant."', '".$id_adversaire."', NOW(), '0', '".$type."'), ";
					$i++;
				}
				
				$req = substr($req, 0, -2);
				
				$res = ktPDO::get()->exec($req);

				wanNotify::notify($id_adversaire, __CLASS__, '');
				
				if ($res != $nombre)
					self::$advert = "Impossible d'ajouter le ou les combats";
				else
					self::$advert = "Combat(s) ajouté(s)";
			}
		}
		else
			self::$advert = "Impossible d'accèder aux inscriptions à l'arène";
			
		return;
	}
	
	/*
	 * suppression d'un combat
	 */
	public static function supprimerCombat($ninja_instance, $id_combat)
	{
		self::$redirection = "index.php?page=arene";
		
		if ($ninja_instance instanceof wanNinja)
		{
			$id_combat = htmlentities(substr($id_combat, 0, 32));
			
			$requete_combat = "SELECT 
									* 
								FROM 
									wan_arene 
								WHERE 
									arene_id = ".ktPDO::get()->quote($id_combat)." 
								LIMIT 
									1";
			
			$resultat_combat = ktPDO::get()->query($requete_combat);
			
			if (!$resultat_combat)
				self::$advert = "Une erreur est survenue lors de la suppression du combat";
			else
			{
				$retour_combat = $resultat_combat->rowCount();
				
				if ($retour_combat == 0)
					self::$advert = "Ce combat n'existe pas !";
		
				$combat = $resultat_combat->fetch(PDO::FETCH_ASSOC);
				$resultat_combat->closeCursor();
				
				if ($combat['arene_combattant'] != $ninja->ninja['ninja_id'] && $combat['arene_adversaire'] != $ninja->ninja['ninja_id'])
					self::$advert = "Tu ne peux pas supprimer un combat qui ne te concerne pas";
				
				if ($combat['arene_gagnant'] != 0)
					self::$advert = "Tu ne peux pas supprimer un combat qui a déjà été fait";
				else
				{
					$requete_suppression = "DELETE 
											FROM 
												wan_arene 
											WHERE 
												arene_id = ".ktPDO::get()->quote($id_combat)."";

					$resultat_suppression = ktPDO::get()->exec($requete_suppression);
					
					if ($resultat_suppression == 1)
						self::$advert = "Combat suprimmé";
					else
						self::$advert = "Impossible de supprimer ce combat";
				}
			}
		}
		else
			self::$advert = "Impossible d'accèder à la suppression d'un combat";
			
		return;
	}

	/*
	 * LOGS PARSE
	 */
	public static function endCombatLogParse($data)
	{
		switch ($data['mode'])
		{
			case 'normal' :
				if ($data['log_to'] == 'winner') :
					return '<a href="index.php?page=profil&id='.$data['winner_id'].'">'.$data['winner_login'].'</a> gagne 
							<a href="index.php?page=profil&id='.$data['looser_id'].'">'.$data['looser_login'].'</a>';
				else :
					return '<a href="index.php?page=profil&id='.$data['looser_id'].'">'.$data['looser_login'].'</a> perd contre 
							<a href="index.php?page=profil&id='.$data['winner_id'].'">'.$data['winner_login'].'</a>';
				endif;
			break;

			case 'nukenin' :
				if ($data['log_to'] == 'winner') :
					return '<a href="index.php?page=profil&id='.$data['winner_id'].'">'.$data['winner_login'].'</a> réussi le test Nukenin';
				else :
					return '<a href="index.php?page=profil&id='.$data['looser_id'].'">'.$data['looser_login'].'</a> échoue le test Nukenin';
				endif;
			break;

			case 'sennin' :
				if ($data['log_to'] == 'winner') :
					return '<a href="index.php?page=profil&id='.$data['winner_id'].'">'.$data['winner_login'].'</a> réussi le test Sennin';
				else :
					return '<a href="index.php?page=profil&id='.$data['looser_id'].'">'.$data['looser_login'].'</a> échoue le test Sennin';
				endif;
			break;

			case 'partner' :
				if ($data['log_to'] == 'winner') :
					return '<a href="index.php?page=profil&id='.$data['winner_id'].'">'.$data['winner_login'].'</a> gagne en amical contre 
							<a href="index.php?page=profil&id='.$data['looser_id'].'">'.$data['looser_login'].'</a>';
				else :
					return '<a href="index.php?page=profil&id='.$data['looser_id'].'">'.$data['looser_login'].'</a> perd en amical contre 
							<a href="index.php?page=profil&id='.$data['winner_id'].'">'.$data['winner_login'].'</a>';
				endif;
			break;
		}
	}

	/*
	 * NOTIFY PARSE
	 */
	public static function ajouterCombatNotificationParse($data)
	{
		return;
	}
}

?>