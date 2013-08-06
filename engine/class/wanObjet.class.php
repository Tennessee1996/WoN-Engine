<?php

/*
 * Intéragie avec les objets du jeu et le ninja
 */
 
class wanObjet
{
	// instance de wanNinja
	private $_ninja;
	// instance de l'inventaire du ninja
	public $_inventaire;

	// données de l'objet
	public $objet;
	
	// redirection après execution
	private $redirection;
	// message affiché après execution
	private $message;
	
	/*
	 * Constructeur
	 */
	public function __construct(wanNinja $_ninja)
	{
		if ($_ninja instanceof wanNinja)
		{
			$this->_ninja = $_ninja;
			$this->_inventaire = new wanInventaire($this->_ninja);
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Termine l'execution en spécifiant le message et la redirection
	 */
	private function proccessOutput()
	{
		$_SESSION['flash'] = $this->message;
		wanEngine::redirect($this->redirection);
	}
	
	/*
	 * Spécifie une redirection
	 */
	public function setRedirection($redirection)
	{
		$this->redirection = $redirection;
	}
	
	/*
	 *	Récupère un objet
	 */
	public function getObjet($objet_id = NULL)
	{
		$objet_id = (int) $objet_id;
		
		if (!empty($objet_id))
		{
			$req_objet = "SELECT 
								wan_commerces.*, 
								rang_id, rang_nom, 
								clan_id, clan_nom 
							FROM 
								wan_commerces 
								LEFT JOIN 
									wan_rangs 
									ON 
									wan_rangs.rang_id=wan_commerces.commerce_rang 
								LEFT JOIN 
									wan_clan 
									ON 
									wan_clan.clan_id=wan_commerces.commerce_clan 
							WHERE 
								commerce_id = ".$objet_id." 
							LIMIT 
								1";
			
			$res_objet = ktPDO::get()->query($req_objet);
			
			if ($res_objet != FALSE AND $res_objet->rowCount() > 0)
			{
				$this->objet = $res_objet->fetch(PDO::FETCH_ASSOC);
				$res_objet->closeCursor();
				$this->_inventaire->initObjet($objet_id);
				
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 *	Initialise un objet
	 */
	public function initObjet($objet_id = NULL)
	{
		$objet_id = (int) $objet_id;
		
		if (!empty($objet_id))
		{
			$this->_inventaire->initObjet($objet_id);
			
			if ($this->_inventaire->ninjaCheckInventaire())
			{
				$req_objet = "SELECT 
									wan_commerces.*, 
									rang_id, rang_nom, 
									clan_id, clan_nom 
								FROM 
									wan_commerces 
									LEFT JOIN 
										wan_rangs 
										ON 
										wan_rangs.rang_id=wan_commerces.commerce_rang 
									LEFT JOIN 
										wan_clan 
										ON 
										wan_clan.clan_id=wan_commerces.commerce_clan 
								WHERE 
									commerce_id=".$objet_id." 
								LIMIT 
									1";
				
				$res_objet = ktPDO::get()->query($req_objet);
				
				if ($res_objet != FALSE AND $res_objet->rowCount() > 0)
				{
					$this->objet = $res_objet->fetch(PDO::FETCH_ASSOC);
					$res_objet->closeCursor();
					
					if ($this->_ninja->ninjaCheckGrade($this->objet['commerce_rang']))
					{
						if ($this->_ninja->ninjaCheckSpecialGrade($this->objet['commerce_rang']))
						{
							if ($this->objet['commerce_clan'] == '0' OR $this->_ninja->ninjaCheckClan($this->objet['commerce_clan']))
								return TRUE;
							else
							{
								$this->message = "Tu dois faire parti du clan des ".$this->objet['clan_nom']." pour utiliser cet objet";
								$this->proccessOutput();
							}
						}
						else
						{
							if ($this->objet['commerce_rang'] == 6)
								$this->message = "Un nukenin ne peut pas porter les équipements d'un sennin";
							else if ($this->objet['commerce_rang'] == 7)
								$this->message = "Un sennin ne peut pas porter les équipements d'un nukenin";
							else
								$this->message = "Ton grade ninja ne te permet pas d'utiliser cet objet";
							$this->processOutput();
						}
					}
					else
					{
						$this->message = "Tu dois être au moins ".$this->objet['rang_nom']." pour utiliser cet objet";
						$this->proccessOutput();
					}
				}
				else
				{
					$this->message = "L'objet demandé n'existe pas";
					$this->proccessOutput();
				}
			}
			else
			{
				$this->message = "Ton ninja ne possède pas cet objet";
				$this->proccessOutput();
			}
		}
		else
			wanEngine::setError('Objet non initialisé');
	}

	/*
	 * Jette un objet
	 */
	public function trashObjet($quantite = 1)
	{
		$quantite = abs($quantite);
		
		if ($quantite == 0)
		{
			$_SESSION['flash'] = "Tu ne peux pas en jeter 0 !";
			return FALSE;
		}

		$state = $this->_inventaire->ninjaTrashObjet($quantite);

		if ($state)
		{
			$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 
							  'ninja_login' => $this->_ninja->ninja['ninja_login'], 
							  'item_id' => $this->objet['commerce_id'],
							  'item_name' => $this->objet['commerce_nom'], 
							  'item_quantity' => $quantite);

			$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

			wanLog::log($log);

			wanEngine::redirect('index.php?page=appartement');
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Utilise l'objet initialisé
	 */
	public function useObjet()
	{
		if (!empty($this->objet))
		{
			$obj_fx = $this->objet['commerce_effet'];
			
			switch ($obj_fx)
			{
				case 'faim' :
				case 'soif' :
					$state = $this->objetFx_01();
				break;
				
				case 'vie' :
				case 'chakra' :
					$state = $this->objetFx_02();
				break;
				
				case 'arme' :
				case 'armure' :
					$state = $this->objetFx_03();
				break;
				
				case 'amulette' :
					$state = $this->objetFx_04();
				break;
				
				default :
					$this->message = "Rien ne se passe...";
					$state = FALSE;
				break;
			}
				
			if ($state)
			{
				$this->_inventaire->ninjaMajInventaire(1, 'pick');

				$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 
								  'ninja_login' => $this->_ninja->ninja['ninja_login'], 
								  'item_id' => $this->objet['commerce_id'],
								  'item_name' => $this->objet['commerce_nom'], 
								  'item_quantity' => 1);

				$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

				wanLog::log($log);

				$this->proccessOutput();
			}
			else
			{
				$this->proccessOutput();
			}
		}
		else
			wanEngine::setError('Aucun objet prêt à être utilisé');
	}
	
	/*
	 *	Applique l'effet Fx01 (faim & soif)
	 */
	private function objetFx_01()
	{
		switch ($this->objet['commerce_effet'])
		{
			case 'faim' :
				$p_value_max = 100;
				$p_value_current = $this->_ninja->ninja['stats_faim'];
				
				if ($p_value_max != $p_value_current)
				{
					$o_value = $this->objet['commerce_valeur'];
					$p_value_next = $p_value_current + $o_value;
					
					$p_value_next < $p_value_max ? 
						$state = $this->_ninja->ninjaChangeFaim($p_value_next) : 
							$state = $this->_ninja->ninjaChangeFaim($p_value_max) ;
							
					$this->message = "Te voilà rassasié !";
				}
				else
				{
					$this->message = "Tu n'as pas faim !";
					$state = FALSE;
				}
			break;
			
			case 'soif' :
				$p_value_max = 100;
				$p_value_current = $this->_ninja->ninja['stats_soif'];
				
				if ($p_value_max != $p_value_current)
				{
					$o_value = $this->objet['commerce_valeur'];
					$p_value_next = $p_value_current + $o_value;
					
					$p_value_next < $p_value_max ? 
						$state = $this->_ninja->ninjaChangeSoif($p_value_next) : 
							$state = $this->_ninja->ninjaChangeSoif($p_value_max) ;
							
					$this->message = "Hydratation no jutsu !";
				}
				else
				{
					$this->message = "Tu n'as pas soif";
					$state = FALSE;
				}
			break;
			
			default :
				$state = FALSE;
			break;
		}
		
		return $state;
	}
	
	/*
	 * Applique l'effet Fx02 (vie & chakra)
	 */
	private function objetFx_02()
	{
		switch ($this->objet['commerce_effet'])
		{
			case 'vie' :
				$p_value_max = $this->_ninja->ninja['stats_vie_max'];
				$p_value_current = $this->_ninja->ninja['stats_vie'];
				
				if ($p_value_max != $p_value_current)
				{
					$o_value = $this->objet['commerce_valeur'];
					$p_value_next = $p_value_current + $o_value;
					
					$p_value_next < $p_value_max ? 
						$state = $this->_ninja->ninjaChangeVie($p_value_next) : 
							$state = $this->_ninja->ninjaChangeVie($p_value_max) ;
							
					$this->message = "Points de vie restaurés";
				}
				else
				{
					$this->message = "Tu n'as pas besoin d'être soigné !";
					$state = FALSE;
				}
			break;
			
			case 'chakra' :
				$p_value_max = $this->_ninja->ninja['stats_chakra_max'];
				$p_value_current = $this->_ninja->ninja['stats_chakra'];
				
				if ($p_value_max != $p_value_current)
				{
					$o_value = $this->objet['commerce_valeur'];
					$p_value_next = $p_value_current + $o_value;
					
					$p_value_next < $p_value_max ? 
						$state = $this->_ninja->ninjaChangeChakra($p_value_next) : 
							$state = $this->_ninja->ninjaChangeChakra($p_value_max) ;
							
					$this->message = "Chakra restauré";
				}
				else
				{
					$this->message = "Ton chakra est au maximum";
					$state = FALSE;
				}
			break;
			
			default :
				$state = FALSE;
			break;
		}
		
		return $state;
	}
	
	/*
	 * Applique l'effet Fx03 (arme & armure)
	 */
	private function objetFx_03()
	{
		switch ($this->objet['commerce_effet'])
		{
			case 'arme' :
				$w_current = array_key_exists('equipement', $this->_ninja->ninja) ? 
					$w_current = array_key_exists('arme', $this->_ninja->ninja['equipement']) : 
						$w_current = false;
						
				$w_next = $this->objet['commerce_id'];
				
				if (!$w_current)
				{
					$state = $this->_ninja->ninjaAddEquipement($this->objet, $this->objetStuffPrepare());

					if ($state)
						$this->message = "Tu es maintenant équipé de ".$this->objet['commerce_nom'];
					else
						$this->message = "Tu ne peux pas t'équiper de ".$this->objet['commerce_nom'];
				}
				else
				{
					$this->message = "Tu portes déjà une arme";
					$state = FALSE;
				}
			break;
			
			case 'armure' :
				$w_current = array_key_exists('equipement', $this->_ninja->ninja) ? 
					$w_current = array_key_exists('armure', $this->_ninja->ninja['equipement']) : 
						$w_current = false;
						
				$w_next = $this->objet['commerce_id'];
				
				if (!$w_current)
				{
					$state = $this->_ninja->ninjaAddEquipement($this->objet, $this->objetStuffPrepare());

					if ($state)
						$this->message = "Tu es maintenant équipé de ".$this->objet['commerce_nom'];
					else
						$this->message = "Tu ne peux pas t'équiper de ".$this->objet['commerce_nom'];
				}
				else
				{
					$this->message = "Tu portes déjà une armure";
					$state = FALSE;
				}
			break;
			
			default :
				$state = FALSE;
			break;
		}
		
		return $state;
	}
	
	/*
	 * Applique l'effet Fx04 (amulette)
	 */
	private function objetFx_04()
	{

		if (!empty($this->objet['commerce_valeur']))
		{
			$state = $this->_ninja->ninjaAmuletteAdd($this->objet['commerce_valeur']);
			
			if ($state)
				$this->message = "Amulette équipée";
			else
				$this->message = "Tu portes déjà cette amulette";
		}
		else
		{
			$this->message = "Impossible de mettre cette amulette";
			$state = FALSE;
		}
		
		return $state;
	}
	
	/*
	 * Parse les valeurs d'effet  
	 */
	public function objetStuffPrepare($datas = NULL)
	{
		if (empty($this->objet) AND $datas != NULL)
			$this->objet = $datas;
		
		$v_obj = explode('#', $this->objet['commerce_valeur']);
		
		if (sizeof($v_obj))
		{
			$r_values = array();
			
			foreach ($v_obj as $key => $value)
			{
				$sub_v = explode(',', $value);
				
				if (sizeof($sub_v))
					$r_values[$sub_v[0]] = array($sub_v[0], $sub_v[1]);
				else
					continue;
			}
			
			if (count($r_values) > 0)
				return $r_values;
			else
				return FALSE;
		}
		else
			return FALSE;
	}

	/*
	 * LOGS PARSE
	 */
	public static function useObjetLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> utilise l\'objet "'.$data['item_name'].'"';
	}

	public static function trashObjetLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> jette l\'objet 
				"'.$data['item_name'].'" x'.$data['item_quantity'];
	}
}