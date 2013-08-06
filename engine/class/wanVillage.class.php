<?php

class wanVillage
{
	public $advert = NULL;

	private $village_id = NULL;
	private $village_taxe = NULL;
	private $village_banque = NULL;
		
	private $global_taxe = 0.8;
	public $commerce_data = array();
	public $item_data = array();
	private $item_id = NULL;
	private $item_qt = NULL;
	private $item_cout = NULL;
	
	public function __construct($village_id = NULL, 
								$village_taxe = NULL, 
								$village_banque = NULL)
	{
		if (!empty($village_id) AND 
			!empty($village_taxe) AND 
			!empty($village_banque))
		{
			$this->village_id = $village_id;
			$this->village_taxe = $village_taxe;
			$this->village_banque = $village_banque;
		}
		else
			return FALSE;
	}
	
	public function villageChangeTaxe($new_taxe = NULL)
	{
		if (!empty($new_taxe))
		{
			if ($new_taxe >= 0 AND $new_taxe <= 99)
			{
				$req_taxe = "UPDATE 
								wan_villages 
							SET 
								village_taxe=".$new_taxe." 
							WHERE 
								village_id=".$this->village_id." 
							LIMIT 
								1";
								
				$res_taxe = ktPDO::get()->exec($req_taxe);
				
				if ($res_taxe != 1)
					return FALSE;
				else
					return TRUE;
			}
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	public function villageBankAdd($montant = NULL)
	{
		if (!empty($montant))
		{
			$montant = abs($montant);
			
			$req_add = "UPDATE 
							wan_villages 
						SET 
							village_banque=village_banque+".$montant." 
						WHERE 
							village_id=".$this->village_id." 
						LIMIT 
							1";
			
			$res_add = ktPDO::get()->exec($req_add);
			$this->village_banque += $montant;
			
			if ($res_add != 1)
				return 0;
			else
				return 1;
		}
		else
			wanEngine::setError('Aucun montant spécifié');
	}
	
	public function villagePayImpot($montant = NULL)
	{
		if (!empty($montant))
		{
			$montant = abs($montant);
			
			$pay_state = $this->villageBankAdd($montant);
			
			return $pay_state;
		}
		else
			wanEngine::setError('Montant non spécifié');
	}
	
	public function villageBankPick($pick_montant = NULL)
	{
		if (!empty($pick_montant))
		{
			if ($this->village_banque - $pick_montant >= 0)
			{
				$req_pick = "UPDATE 
								wan_villages 
							SET 
								village_banque = village_banque-".$pick_montant." 
							WHERE 
								village_id = ".$this->village_id." 
							LIMIT 
								1";
								
				$res_pick = ktPDO::get()->exec($req_pick);
				$this->village_banque -= $pick_montant;
				
				if ($res_pick != 1)
					return 0;
				else
					return 1;
			}
			else
				return FALSE;
		}
		else
			wanEngine::setError('Paramètres du prélevement invalides');
	}
	
	public function villageKageChange($ninja_id)
	{
		if (!empty($ninja_id))
		{
			$req_kage = "UPDATE 
							wan_villages 
						SET 
							village_kage='".$ninja_id."' 
						WHERE 
							village_id=".$this->village_id." 
						LIMIT 
							1";
			
			$res_kage = ktPDO::get()->exec($req_kage);
			
			if ($res_kage != 1)
				return FALSE;
			else
				return TRUE;
		}
		else
			wanEngine::setError('Id ninja non spécifié');
	}
	
	// Récupere la liste des objets du commerce
	public function villageGetCategories()
	{
		$req = "SELECT * 
				FROM wan_categorie_objet 
				WHERE objet_id != 6 
				ORDER BY objet_id ASC";
		
		$res = ktPDO::get()->query($req);
		
		if ($req != FALSE AND $res->rowCount() > 0)
		{
			$tmp = array();
			
			while ($ret = $res->fetch(PDO::FETCH_ASSOC))
				$tmp[] = $ret;
			
			$res->closeCursor();
			
			return $tmp;
		}
		else
			return FALSE;
	}
	
	// Récupere la liste des objets du commerce
	public function villageGetCommerce($type)
	{
		$type = (int) $type;
		
		if (!empty($type))
		{
			$req = "SELECT 
						* 
					FROM 
						wan_commerces 
							LEFT JOIN 
								wan_boutiques 
									ON 
										wan_boutiques.boutique_objet = wan_commerces.commerce_id 
											AND 
										wan_boutiques.boutique_village = ".$this->village_id." 
						
					WHERE 
						commerce_type = ".$type." 
							AND 
						commerce_clan = '0' 
							AND 
						commerce_secret = 0 
							AND 
						commerce_rang != 6 
					ORDER BY 
						commerce_rang ASC, 
						commerce_prix ASC";
			
			$res = ktPDO::get()->query($req);
			
			if ($req != FALSE AND $res->rowCount() > 0)
			{
				while ($ret = $res->fetch(PDO::FETCH_ASSOC))
				{
					$ret['commerce_prix'] = round($ret['commerce_prix'] * $this->global_taxe);
					
					$this->commerce_data[$ret['commerce_id']] = $ret;
				}
				
				$res->closeCursor();
				
				return TRUE;
			}
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	// Récupere la liste des objets du commerce
	private function villageInitItem($item_id)
	{
		$req = "SELECT
					commerce_id, commerce_koban, commerce_prix, commerce_clan, 
					commerce_secret, commerce_type, commerce_nom, 
					wan_boutiques.* 
				FROM
					wan_commerces 
						LEFT JOIN 
							wan_boutiques 
								ON 
									commerce_id = boutique_objet 
										AND 
									boutique_village = ".$this->village_id." 
				WHERE 
					commerce_id = ".$this->item_id." 
				LIMIT 
					1";
		
		$res = ktPDO::get()->query($req);
		
		if ($req != FALSE AND $res->rowCount() > 0)
		{
			$this->item_data = $res->fetch(PDO::FETCH_ASSOC);
			$this->item_data['commerce_prix'] = round($this->item_data['commerce_prix'] * $this->global_taxe);
			
			$res->closeCursor();
			
			return TRUE;
		}
		else
			return FALSE;
	}
	
	// vérifie que le village puisse acheter l'item
	private function villageCheckBanque($ammount)
	{
		if ($this->village_banque >= $ammount)
			return TRUE;
		else
			return FALSE;
	}
	
	// calcule le montant d'un achat
	private function villagePrepareCout()
	{
		$this->item_cout = $this->item_qt * $this->item_data['commerce_prix'];
	}
	
	// mise à jour de la boutique du village
	private function villageMajItem()
	{
		if (array_key_exists('boutique_quantite', $this->item_data) AND isset($this->item_data['boutique_quantite']))
			return $this->villageMajItem_Update();
		else
			return $this->villageMajItem_Insert();
	}
	
	// mise à jour de la boutique du village : insert
	private function villageMajItem_Insert()
	{
		$req = "INSERT INTO wan_boutiques (boutique_objet, boutique_village, boutique_quantite) 
				VALUES (".$this->item_id.", ".$this->village_id.", ".$this->item_qt.")";
				
		$res = ktPDO::get()->exec($req);
		
		if ($res == 1)
			return TRUE;
		else
			return FALSE;
	}
	
	// mise à jour de la boutique du village : update
	private function villageMajItem_Update()
	{
		$req = "UPDATE wan_boutiques 
				SET boutique_quantite = boutique_quantite + ".$this->item_qt." 
				WHERE boutique_objet = ".$this->item_id." 
					AND boutique_village = ".$this->village_id." 
				LIMIT 
					1";
					
		$res = ktPDO::get()->exec($req);
		
		if ($res == 1)
			return TRUE;
		else
			return FALSE;
	}
	
	// vérifie que le village puisse acheter l'objet
	private function villageCheckAchat()
	{
		if (!empty($this->item_data))
		{
			if ($this->item_data['commerce_clan'] != '0')
				return FALSE;
			if ($this->item_data['commerce_type'] == 6)
				return FALSE;
			if ($this->item_data['commerce_rang'] == 6)
				return FALSE;
			else if ($this->item_data['commerce_secret'] == 1)
				return FALSE;
			else
				return TRUE;
		}
		else
			return FALSE;
	}
	
	// lance la procédure d'achat d'un objet : requetes sql
	private function villageProcessAchat()
	{
		$state_maj_item = $this->villageMajItem();
		$state_payment = $this->villageBankPick($this->item_cout);
		
		if ($state_maj_item AND $state_payment)
			return TRUE;
		else
			return FALSE;
	}
	
	// procédure d'ajout d'un item à la boutique du village
	public function villageAddItem($item_id, $item_qt)
	{
		$this->item_id = $item_id;
		$this->item_qt = abs($item_qt);
		
		if ($this->villageInitItem($item_id))
		{
			$this->villagePrepareCout();
			
			if ($this->villageCheckBanque($this->item_cout))
			{
				if ($this->villageCheckAchat())
				{
					if ($this->villageProcessAchat())
						$this->advert = "Stock mis à jour !";
					else
						$this->advert = "Impossible de faire cet achat !";
				}
				else
					$this->advert = "Cet objet ne peut pas être acheté !";
			}
			else
				$this->advert = "La banque du village n'a pas assez d'argent !";
		}
		else
			$this->advert = "Cet objet n'est pas dans le commerce";
			
		return;
	}
}

?>