<?php

/*
 * Manipule les achats du jeu, intéragie avec la base des objets et intervient sur le ninja
 */
 
class wanCommerce
{
	// instance du ninja
	private $_ninja;
	// instance de l'inventaire du ninja
	private $_inventaire;
	// classe du village du ninja
	private $_village;
	
	// tableau de l'objet retourné
	public $objet;
	
	// cout total de l'objet
	private $cout_total;
	
	// ignorer le village
	private $ignore_village = FALSE;
	
	// paramères liés au requetes sur la table commerces
	public $where_param = FALSE;
	public $order_param = FALSE;
	
	// paramètres liés à la classe et au retour vers l'utilisateur
	private $unlock_secret = FALSE;
	private $message;
	private $message_plus;
	private $quantite = 1;
	private $redirection;
	
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
			return FALSE;
	}
	
	/*
	 * Initialisation de l'objet
	 */
	public function initObjet($objet_id = 0)
	{
		if (!empty($objet_id))
		{
			if ($this->ignore_village)
			{
				$req_objet = "SELECT 
								commerce_id, commerce_nom, commerce_prix, commerce_koban, commerce_valeur, commerce_effet, commerce_rang, 
								commerce_clan, commerce_type, commerce_secret 
							FROM 
								wan_commerces 
							WHERE 
								commerce_id = ".$objet_id." 
							LIMIT 
								1";
			}
			else
			{
				$req_objet = "SELECT 
								commerce_id, commerce_nom, commerce_prix, commerce_koban, commerce_valeur, commerce_effet, commerce_rang, 
								commerce_clan, commerce_type, commerce_secret, 
								wan_boutiques.*, 
								village_id, village_nom, village_taxe, village_banque, village_banque 
							FROM 
								wan_boutiques 
								LEFT JOIN 
									wan_commerces 
									ON 
									wan_commerces.commerce_id=wan_boutiques.boutique_objet 
								LEFT JOIN 
									wan_villages 
									ON 
									wan_boutiques.boutique_village=wan_villages.village_id 
							WHERE 
								boutique_objet = ".$objet_id." 
								AND 
								boutique_village = ".ktPDO::get()->quote($this->_ninja->ninja['stats_village'])."
							LIMIT 
								1";
			}
							
			$res_objet = ktPDO::get()->query($req_objet);
			
			if (!$res_objet)
			{
				$this->message = "Cet objet n'existe pas";
				
				return FALSE;
			}
			else
			{
				$ret_objet = $res_objet->fetch(PDO::FETCH_ASSOC);
				$this->objet = $ret_objet;
				$res_objet->closeCursor();
				
				$this->_village = new wanVillage($this->objet['village_id'], 
												 $this->objet['village_taxe'], 
												 $this->objet['village_banque']);
				
				return TRUE;
			}
		}
		else
			return FALSE;
	}
	
	/*
	 * Ajoute une clause where à la requete
	 */
	public function setWhere($string = '')
	{
		!empty($string) ? $this->where_param = $string : false;
	}
	
	/*
	 * Ajoute une clause order à la requete
	 */
	public function setOrder($string = '')
	{
		!empty($string) ? $this->order_param = $string : false;
	}
	
	/*
	 * Spécifie une redirection après interaction
	 */
	public function setRedirection($redirection = '')
	{
		!empty($redirection) ? $this->redirection = $redirection : false;
	}
	
	/*
	 * Complète le message affiché après interaction
	 */
	public function setMessage($message = '')
	{
		!empty($message) ? $this->message_plus = $message : false;
	}
	
	/*
	 * Déverouille les objets secrets
	 */
	public function setUnlock($state = FALSE)
	{
		$state == FALSE ? $this->unlock_secret = FALSE : $this->unlock_secret = TRUE;
	}
	
	/*
	 * Ignore le village
	 */
	public function commerceIgnoreVillage($state)
	{
		$this->ignore_village = $state;
	}
	
	/*
	 * Vérifie si un objet est secret ou non
	 */
	private function objetCheckSecret()
	{
		if ($this->objet['commerce_secret'] == 1 AND !$this->unlock_secret)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Vérifie les paramètres du clan de l'objet
	 */
	private function objetCheckClan()
	{
		if ($this->objet['commerce_clan'] == '0')
			return TRUE;
		else if ($this->objet['commerce_clan'] != '0' AND $this->_ninja->ninjaCheckClan($this->objet['commerce_clan']))
			return TRUE;
		else
			return FALSE;
	}
	
	/* 
	 * Vérifie le stock de l'objet dans la boutique du village
	 */
	private function objetCheckStock()
	{
		if ($this->ignore_village)
		{
			return TRUE;
		}
		else
		{
			if ($this->objet['boutique_quantite'] >= $this->quantite)
				return TRUE;
			else	
				return FALSE;
		}
	}
	
	/*
	 * Vérifie si un objet doit être payer en koban
	 */
	private function objetCheckKoban()
	{
		if ($this->objet['commerce_koban'] > 0)
			return TRUE;
		else 
			return FALSE;
	}
	
	/*
	 * Applique la taxe du village
	 */
	private function applyTaxe($prix)
	{
		if ($this->ignore_village)
		{
			return $prix;
		}
		else
		{
			$multiplicateur = 1.00 + ($this->_ninja->ninja['village_taxe'] / 100);
			return round($prix * $multiplicateur);
		}
	}
	
	/*
	 * Met à jour le stock d'une boutique
	 */
	private function boutiqueMajStock()
	{
		$req_maj = "UPDATE 
						wan_boutiques 
					SET 
						boutique_quantite=boutique_quantite-".$this->quantite." 
					WHERE 
						boutique_village=".$this->_ninja->ninja['stats_village']." 
						AND 
						boutique_objet=".$this->objet['commerce_id']." 
					LIMIT 
						1";
						
		$res_maj = ktPDO::get()->exec($req_maj);
		
		if ($res_maj != 1)
			return 0;
		else
			return 1;
	}
	
	/*
	 * Vérifie qu'un ninja peut acheter un objet
	 */
	private function objetCheckCan($quantite = 0)
	{
		$this->_inventaire->initObjet($this->objet['commerce_id']);
		
		if ($this->objetCheckPayment())
		{
			if ($this->objetCheckStock())
			{
				if ($this->objetCheckSecret())
				{
					if ($this->objetCheckClan())
					{
						return TRUE;
					}
					else
					{
						$this->message = "Tu ne peux pas acheter les objets d'un clan";
						return FALSE;
					}
				}
				else
				{
					$this->message = "Cet objet est secret";
					return FALSE;
				}
			}
			else
			{
				$this->message = "Il n'y a pas assez de stock pour acheter cet objet";
				return FALSE;
			}
		}
		else
			return FALSE;
	}
	
	/*
	 *	Vérifie le paiement d'un objet
	 */
	private function objetCheckPayment()
	{
		if ($this->objetCheckKoban())
		{
				if ($this->_ninja->ninjaCheckKoban($this->objet['commerce_koban']))
				{
					return TRUE;
				}
				else
				{
					$this->message = "Tu n'as pas assez de Koban";
					return FALSE;
				}
		}
		else
		{
			if ($this->_ninja->ninjaCheckRyos($this->cout_total))
			{
				return TRUE;
			}
			else
			{
				$this->message = "Tu n'as pas assez de ryôs";
				return FALSE;
			}
		}
	}
	
	/*
	 * Achète un objet
	 */
	public function achatObjet($quantite = 1)
	{
		$quantite = abs($quantite);
		$this->quantite = $quantite;
		$this->cout_total = $this->objet['commerce_prix'] * $this->quantite;
		$this->cout_total = $this->applyTaxe($this->cout_total);
		
		if (!empty($this->objet))
		{
			if ($this->objetCheckCan($quantite))
			{
				ktPDO::get()->beginTransaction();
				$sql_state = 0;
				
				if ($this->objetCheckKoban())
				{
					$sql_state += $this->_inventaire->ninjaMajInventaire($this->quantite);
					$sql_state += $this->_ninja->ninjaChangeKoban('pick', $this->objet['commerce_koban']);
				}
				else
				{
					$sql_state += $this->boutiqueMajStock();
					$sql_state += $this->_inventaire->ninjaMajInventaire($this->quantite);
					$sql_state += $this->_ninja->ninjaChangeRyos('pick', $this->cout_total);
					
					if (!$this->ignore_village)
						$sql_state += $this->_village->villagePayImpot($this->cout_total);
				}	
				
				if ($sql_state != 0)
				{
					$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 
									  'ninja_login' => $this->_ninja->ninja['ninja_login'], 
									  'item_id' => $this->objet['commerce_id'],
									  'item_name' => $this->objet['commerce_nom'],
									  'item_cost_ryos' => $this->objet['commerce_ryos'],
									  'item_cost_koban' => $this->objet['commerce_koban'],
									  'item_quantity' => $this->quantite);

					$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

					wanLog::log($log);

					ktPDO::get()->commit();
					$this->message = "Merci de ton achat" . $this->message_plus;
				}
				else
				{
					ktPDO::get()->rollBack();
					$this->message = "Impossible d'acheter cet objet";
				}
			}	
		}
		else
			$this->message = "Cet objet n'est pas en vente dans ton village";
		
		$_SESSION['flash'] = $this->message;
		wanEngine::redirect($this->redirection);
	}
	
	/*
	 * Récupere les objets d'une boutique en fonction du village et du type
	 */
	public function commerceGetBoutiques($type = 0)
	{
		$req_boutique = "SELECT 
							wan_commerces.*, 
							wan_boutiques.*, 
							rang_id, rang_nom 
						FROM 
							wan_boutiques 
							LEFT JOIN 
								wan_commerces 
								ON 
								wan_boutiques.boutique_objet=wan_commerces.commerce_id 
							LEFT JOIN 
								wan_rangs 
								ON
								wan_rangs.rang_id=wan_commerces.commerce_rang 
						WHERE 
							wan_boutiques.boutique_village = ".$this->_ninja->ninja['village_id']." 
							AND 
							wan_commerces.commerce_type = ".$type." 
							AND 
							wan_commerces.commerce_rang NOT IN (6, 8) 
							AND 
							wan_boutiques.boutique_quantite > 0";
							
		$this->where_param != FALSE ? $req_boutique .= $this->where_param : false;
		$this->order_param != FALSE ? $req_boutique .= $this->order_param : false;
							
		$res_boutique = ktPDO::get()->query($req_boutique);
		
		if (!$res_boutique OR $res_boutique->rowCount() == 0)
			return FALSE;
		else
		{
			$data = array();
			
			while ($boutique = $res_boutique->fetch(PDO::FETCH_ASSOC))
			{
				$boutique['commerce_prix'] = $this->applyTaxe($boutique['commerce_prix']);
				$data[] = $boutique;
			}
			
			return $data;
		}
	}
	
	/*
	 * Récupere les objets de l'akatsuki
	 */
	public function commerceGetBoutiques_Akatsuki()
	{
		$req_boutique = "SELECT 
								* 
							FROM 
								wan_commerces 
							WHERE 
								commerce_rang = 6 
								AND 
								commerce_secret = 0 
							ORDER BY 
								commerce_type ASC, 
								commerce_prix ASC";
							
		$res_boutique = ktPDO::get()->query($req_boutique);
		
		if (!$res_boutique OR $res_boutique->rowCount() == 0)
			return FALSE;
		else
		{
			$data = array();
			
			while ($boutique = $res_boutique->fetch(PDO::FETCH_ASSOC))
				$data[] = $boutique;
			
			return $data;
		}
	}
	
	/*
	 * Récupere les objets d'une boutique en fonction du village et du type
	 */
	public function commerceGetBoutiques_Autel()
	{
		$req_boutique = "SELECT 
							 *
						FROM 
							wan_commerces 
						WHERE 
							wan_commerces.commerce_koban > 0";
							
		$res_boutique = ktPDO::get()->query($req_boutique);
		
		if (!$res_boutique OR $res_boutique->rowCount() == 0)
			return FALSE;
		else
		{
			$data = array();
			
			while ($boutique = $res_boutique->fetch(PDO::FETCH_ASSOC))
				$data[] = $boutique;
			
			return $data;
		}
	}

	/*
	 *	LOGS PARSE
	 */
	public static function achatObjetLogParse($data)
	{
		return '<a href="'.$data['ninja_id'].'">'.$data['ninja_login'].'</a> achète l\'objet "'.$data['item_name'].'" x'.$data['item_quantity'];
	}
}

?>