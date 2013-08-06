<?php

class wanMarket
{
	// instance du ninja
	public $_ninja;
	
	// id de l'objet
	public $objet_id;
	
	// id de la vente
	private $vente_id;
	// quantite de la vente
	private $vente_quantite = 1;
	// prix total de l'achat
	private $achat_cout;
	// data
	public $data = array();
	// instance de l'objet
	public $_objet;
	
	// variable d'une vente d'un objet
	public $prix_min, $prix_max;
	
	// message après éxecution
	public $advert;
	// redirection après éxecution
	public $redirect;
	
	/*
	 * Initialisation de la classe
	 */
	public function __construct(wanNinja $_ninja)
	{
		if ($_ninja instanceof $_ninja)
		{
			$this->_ninja = $_ninja;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Récupere les objets d'une vente
	 */
	public function marketGetSellOnce($objet_id = 0)
	{
		if (!empty($objet_id))
		{
			$this->objet_id = $objet_id;
			
			$req = "SELECT 
						* 
					FROM 
						wan_market 
						LEFT JOIN 
							wan_commerces 
							ON 
								wan_commerces.commerce_id = wan_market.market_objet_id 
						LEFT JOIN 
							wan_ninja 
							ON 
								wan_ninja.ninja_id = wan_market.market_objet_vendeur 
						LEFT JOIN 
							wan_stats 
							ON 
								wan_stats.stats_id = wan_market.market_objet_vendeur 
						LEFT JOIN 
							wan_villages
							ON 
								wan_villages.village_id = wan_stats.stats_village 
						LEFT JOIN 
							wan_rangs 
							ON 
								wan_rangs.rang_id = wan_commerces.commerce_rang 
					WHERE 
						wan_market.market_objet_id = ".ktPDO::get()->quote($this->objet_id)." 
					ORDER BY 
						wan_market.market_objet_prix 
						ASC";
						
			$res = ktPDO::get()->query($req);
			
			if (!$res OR $res->rowCount() == 0)
			{
				$this->advert = "Aucun ninja ne propose cet objet";
				return FALSE;
			}
			else
			{
				while ($ret = $res->fetch(PDO::FETCH_ASSOC))
					$this->data[] = $ret;
					
				$res->closeCursor();
				
				return $this->data;
			}
		}
		else
		{
			$this->advert = "Tu n'as pas choisi de vente";
			return FALSE;
		}
	}
	
	/*
	 * Récupere les ventes d'un joueur
	 */
	public function marketGetShopAll()
	{
		$req = "SELECT 
					wan_market.*, 
					commerce_nom, 
					commerce_id, 
					commerce_type, 
					commerce_prix, 
					objet_id, 
					objet_categorie 
				FROM 
					wan_market 
					LEFT JOIN 
						wan_commerces 
						ON 
							wan_market.market_objet_id = wan_commerces.commerce_id 
					LEFT JOIN 
						wan_categorie_objet 
						ON 
							wan_commerces.commerce_type = wan_categorie_objet.objet_id 
				WHERE 
					wan_market.market_objet_vendeur = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])."
				ORDER BY 
					wan_commerces.commerce_type ASC, 
					wan_commerces.commerce_rang ASC";
		
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
	 * Récupere les ventes de la place du marché
	 */
	public function marketGetSellAll()
	{
		$req = "SELECT 
						market_objet_vendeur, 
						market_objet_id, 
						MIN(market_objet_prix) AS market_prix_min, 
						market_objet_prix, 
						commerce_nom, 
						commerce_id, 
						commerce_type, 
						commerce_prix, 
						objet_id, 
						objet_categorie 
					FROM 
						wan_market 
						LEFT JOIN 
							wan_commerces 
							ON 
								wan_market.market_objet_id = wan_commerces.commerce_id 
						LEFT JOIN 
							wan_categorie_objet 
							ON 
								wan_commerces.commerce_type = wan_categorie_objet.objet_id 
					GROUP BY 
						wan_market.market_objet_id 
					ORDER BY 
						wan_commerces.commerce_type ASC, 
						wan_commerces.commerce_rang ASC";
		
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			$this->advert = "Il n'y a aucune vente sur le marché";
			return FALSE;
		}
		else
		{
			while ($ret = $res->fetch(PDO::FETCH_ASSOC))
				$this->data[] = $ret;
				
			$res->closeCursor();
			return $this->data;
		}
	}
	
	/*
	 * Recupere une vente
	 */
	private function marketGetVente()
	{
		$req = "SELECT 
					wan_market.*, 
					commerce_id, commerce_nom, 
					ninja_id, ninja_login 
				FROM 
					wan_market 
					LEFT JOIN 
						wan_ninja ON ninja_id = market_objet_vendeur 
					LEFT JOIN 
						wan_commerces ON commerce_id = market_objet_id 
				WHERE 
					market_vente_id = ".ktPDO::get()->quote($this->vente_id)." 
				LIMIT 
					1";
					
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			$this->advert = "Cette vente n'existe pas";
			return FALSE;
		}
		else
		{
			$this->data = $res->fetch(PDO::FETCH_ASSOC);
			$res->closeCursor();
			
			$this->objet_id = $this->data['market_objet_id'];
			
			return TRUE;
		}
	}
	
	/*
	 * Recupere une vente par id, prix
	 */
	private function marketSearchVente()
	{
		$req = "SELECT 
					wan_market.*
				FROM 
					wan_market 
				WHERE 
					market_objet_id = ".ktPDO::get()->quote($this->objet_id)." 
					AND 
					market_objet_prix = ".ktPDO::get()->quote($this->achat_cout)." 
					AND 
					market_objet_vendeur = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])."
				LIMIT 
					1";
					
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			return FALSE;
		}
		else
		{
			$ret = $res->fetch(PDO::FETCH_ASSOC);
			
			$this->vente_id = $ret['market_vente_id'];
			$res->closeCursor();
			
			return TRUE;
		}
	}
	
	/*
	 * Vérifie que la vente appartient au ninja
	 */
	private function marketCheckOwn()
	{
		if ($this->data['market_objet_vendeur'] == $this->_ninja->ninja['ninja_id'])
		{
			return TRUE;
		}
		else
		{
			$this->advert = "Cette vente ne t'appartient pas !";
			return FALSE;
		}
	}
	
	/*
	 * Met à jour l'inventaire du ninja lorsqu'il retire une vente
	 */
	private function marketDeleteVente_Inventaire()
	{
		$inventaire = new wanInventaire($this->_ninja);
		$inventaire->initObjet($this->objet_id);
		$inventaire->ninjaMajInventaire($this->data['market_objet_quantite'], 'add');
		
		return TRUE;
	}
	
	/*
	 * Met à jour une vente lors de sa suppression du marché
	 */
	private function marketDeleteVente_Vente()
	{
		if ($this->marketMajVente_Delete())
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Processus de suppression d'une vente
	 */
	private function marketDeleteVente_Process()
	{
		if ($this->marketDeleteVente_Inventaire() AND 
			$this->marketDeleteVente_Vente())
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Retire une vente d'un ninja
	 */
	public function marketDeleteVente($vente_id = '')
	{
		$this->vente_id = $vente_id;
		
		if (!empty($this->vente_id))
		{
			$this->marketGetVente();
			
			if ($this->marketCheckOwn())
			{	
				if ($this->marketDeleteVente_Process())
				{
					$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
									  'ninja_login' => $this->_ninja->ninja['ninja_login'],
									  'item_id' => $this->data['commerce_id'],
									  'item_name' => $this->data['commerce_nom']);

					$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

					wanLog::log($log);

					$this->advert = "Vente retirée !";
					return TRUE;
				}
				else
				{
					$this->advert = "Impossible de retirer la vente !";
					return FALSE;
				}
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			$this->advert = "Tu n'as pas sélectionné de vente !";
			return FALSE;
		}
	}
	
	/*
	 * Fait les vérifications de l'achat
	 */
	private function marketAchatObjet_Check()
	{
		if ($this->_ninja->ninja['ninja_id'] != $this->data['market_objet_vendeur'])
		{
			if ($this->vente_quantite <= $this->data['market_objet_quantite'])
			{
				$this->achat_cout = $this->data['market_objet_prix'] * $this->vente_quantite;
				
				if ($this->_ninja->ninjaCheckRyos($this->achat_cout))
				{
					return TRUE;
				}
				else
				{
					$this->redirect = "index.php?page=marche&mode=halle&halle=objet&id=".$this->objet_id;
					$this->advert = "Tu n'as pas assez de ryôs pour acheter cet objet";
					return FALSE;
				}
			}
			else
			{
				$this->redirect = "index.php?page=marche&mode=halle&halle=objet&id=".$this->objet_id;
				$this->advert = "Ce vendeur n'a pas assez de stock sur cette offre";
				return FALSE;
			}
		}
		else
		{
			$this->redirect = "index.php?page=marche&mode=halle&halle=objet&id=".$this->objet_id;
			$this->advert = "Tu ne peux pas acheter tes propres objets sur le marché";
			return FALSE;
		}
	}
	
	/*
	 * Effectue l'achat
	 */
	private function marketAchatObjet_Process()
	{
		$this->marketAchatObjet_Payment();
		$this->marketAchatObjet_Inventaire();
		$this->marketAchatObjet_Vente();
		
		return TRUE;
	}
	
	/*
	 * Effectue les transferts d'argent liés à la vente
	 */
	private function marketAchatObjet_Payment()
	{
		$this->_ninja->ninjaChangeRyos('pick', $this->achat_cout);
		
		$vendeur = new wanNinja($this->data['market_objet_vendeur'], TRUE);
		$vendeur->ninjaChangeRyos('add', $this->achat_cout);
	}
	
	/*
	 * Donne l'objet au ninja acheteur
	 */
	private function marketAchatObjet_Inventaire()
	{
		$inventaire = new wanInventaire($this->_ninja);
		$inventaire->initObjet($this->objet_id);
		$inventaire->ninjaMajInventaire($this->vente_quantite, 'add');
	}
	
	/*
	 * Met à jour la vente
	 */
	private function marketAchatObjet_Vente()
	{
		$this->marketMajVente('pick');
	}
	
	/*
	 * Initialise une vente
	 */
	public function marketAchatObjet($vente_id, $quantite = 1)
	{
		$this->vente_id = $vente_id;
		$this->vente_quantite = abs($quantite);
		
		if ($this->marketGetVente())
		{
			$this->objet_id = $this->data['market_objet_id'];
			
			if ($this->marketAchatObjet_Check())
			{
				if ($this->marketAchatObjet_Process())
				{
					$log_data = array('seller_id' => $this->data['ninja_id'],
									  'seller_login' => $this->data['ninja_login'],
									  'buyer_id' => $this->_ninja->ninja['ninja_id'],
									  'buyer_login' => $this->_ninja->ninja['ninja_login'],
									  'item_id' => $this->data['commerce_id'],
									  'item_name' => $this->data['commerce_nom'],
									  'sale_quantity' => $this->vente_quantite,
									  'sale_cost' => $this->achat_cout, 
									  'mode' => 'seller');

					$log = array('ninja' => $this->data['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

					wanLog::log($log);

					$log_data['mode'] = 'buyer';

					$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

					wanLog::log($log);

					$notification_data = array('buyer_id' => $this->_ninja->ninja['ninja_id'],
											  'buyer_login' => $this->_ninja->ninja['ninja_login'],
											  'item_id' => $this->data['commerce_id'],
											  'item_name' => $this->data['commerce_nom'],
											  'sale_quantity' => $this->vente_quantite,
											  'sale_cost' => $this->achat_cout);

					wanNotify::notify($this->data['ninja_id'], __CLASS__, self::marketAchatObjetNotificationParse($notification_data));

					$this->redirect = "index.php?page=marche&mode=halle";
					$this->advert = "Marché conclus !";
					return TRUE;
				}
				else
				{
					$this->redirect = "index.php?page=marche&mode=halle";
					$this->advert = "Impossible de conclure cette vente";
					return FALSE;
				}
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			$this->redirect = "index.php?page=marche&mode=halle";
			return FALSE;
		}
	}
	
	/*
	 * Met à jour la vente
	 */
	private function marketMajVente($mode = 'add')
	{
		switch ($mode)
		{
			case 'add' :
				$this->marketMajVente_UpdateAdd();
			break;
			
			case 'pick' : 
				if ($this->vente_quantite < $this->data['market_objet_quantite'])
					$this->marketMajVente_UpdatePick();
				else
					$this->marketMajVente_Delete();
			break;
		}
	}
	
	/*
	 * Mis à jour quantite d'une vente : suppression
	 */
	private function marketMajVente_Delete()
	{
		$req = "DELETE 
				FROM 
					wan_market 
				WHERE 
					market_vente_id = ".ktPDO::get()->quote($this->vente_id)." 
				LIMIT 
					1";
		
		$res = ktPDO::get()->exec($req);
		
		if ($res == 0)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Mis à jour quantite d'une vente : ajout
	 */
	private function marketMajVente_UpdateAdd()
	{
		$req = "UPDATE
					wan_market 
				SET 
					market_objet_quantite = market_objet_quantite + ".$this->vente_quantite." 
				WHERE 
					market_vente_id = ".ktPDO::get()->quote($this->vente_id)." 
				LIMIT 
					1";
		
		$res = ktPDO::get()->exec($req);
		
		if ($res == 0)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Mis à jour quantite d'une vente : prelevement
	 */
	private function marketMajVente_UpdatePick()
	{
		$req = "UPDATE
					wan_market 
				SET 
					market_objet_quantite = market_objet_quantite - ".$this->vente_quantite." 
				WHERE 
					market_vente_id = ".ktPDO::get()->quote($this->vente_id)." 
				LIMIT 
					1";
		
		$res = ktPDO::get()->exec($req);
		
		if ($res == 0)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Recupere un objet pour sa vente
	 */
	private function marketAjoutVente_Objet()
	{
		$this->_objet = new wanObjet($this->_ninja);
		
		if ($this->_objet->getObjet($this->objet_id))
		{
			$this->data = $this->_objet->objet;
			
			$this->prix_min = $this->data['commerce_prix'] * 0.50;
			$this->prix_max = $this->data['commerce_prix'] * 1.50;
			
			return TRUE;
		}
		else
		{
			$this->advert = "Cet objet n'existe pas !";
			return FALSE;
		}
	}

	/*
	 * Initialisation de la vente d'un objet du joueur
	 */
	public function marketAjoutVente_Init($objet_id = '')
	{
		if (!empty($objet_id))
		{
			$this->objet_id = (int) $objet_id;
			
			if ($this->marketAjoutVente_Objet())
			{
				if ($this->_objet->_inventaire->ninjaCheckInventaire() > 0)
				{
					return TRUE;
				}
				else
				{
					$this->advert = "Tu n'as pas cet objet dans ton inventaire";
					return FALSE;
				}
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			$this->advert = "Tu n'as pas sélectionné un objet à vendre";
			return FALSE;
		}
	}
	
	/*
	 * Crée ou met à jour une vente lorsque le vendeur vend un objet
	 */
	private function marketAjoutVente_Vente()
	{
		if ($this->marketSearchVente())
		{
			if ($this->marketMajVente_UpdateAdd())
			{
				return TRUE;
			}
			else
			{
				$this->advert = "Impossible de mettre à jour la vente existante !";
				return FALSE;
			}
		}
		else
		{
			$req = "INSERT INTO 
						wan_market (market_vente_id, market_objet_id, market_objet_vendeur, market_objet_prix, market_objet_quantite) 
					VALUES 
						(".ktPDO::get()->quote(wanEngine::generateId(32)).", 
						 ".ktPDO::get()->quote($this->objet_id).", 
						 ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id']).", 
						 ".ktPDO::get()->quote($this->achat_cout).",
						 ".ktPDO::get()->quote($this->vente_quantite).")";
						 
			$res = ktPDO::get()->exec($req);
			
			if ($res == 1)
			{
				return TRUE;
			}
			else
			{
				$this->advert = "Impossible de créer la vente !";
				return FALSE;
			}
		}
	}
	
	/*
	 * Vérifie que le ninja ait suffisament de stock pour vendre un objet
	 */
	public function marketAjoutVente_Check()
	{
		if ($this->_objet->_inventaire->inv_objet_qt < $this->vente_quantite)
		{
			$this->advert = "Tu n'as pas suffisament de stock sur cet objet pour en vendre autant !";
			return FALSE;
		}
		else if ($this->achat_cout > $this->prix_max)
		{
			$this->advert = "Tu ne peux pas vendre cet objet à plus de ".$this->prix_max." ryôs de l'unité !";
			return FALSE;
		}
		else if ($this->achat_cout < $this->prix_min)
		{
			$this->advert = "Tu ne peux pas vendre cet objet à moins de ".$this->prix_min." ryôs de l'unité !";
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/*
	 * Met à jour l'inventaire du ninja après mise en vente d'un objet
	 */
	public function marketAjoutVente_Inventaire()
	{
		if ($this->_objet->_inventaire->ninjaMajInventaire($this->vente_quantite, 'pick'))
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Ajout de la vente du joueur
	 */
	public function marketAjoutVente_Process($vente_cout = '', $vente_quantite = 1)
	{
		$this->vente_quantite = abs($vente_quantite);
		$this->achat_cout = abs($vente_cout);
		
		if (!empty($vente_cout))
		{
			if ($this->marketAjoutVente_Check())
			{
				if ($this->marketAjoutVente_Vente())
				{
					if ($this->marketAjoutVente_Inventaire())
					{
						$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
										  'ninja_login' => $this->_ninja->ninja['ninja_login'],
										  'item_name' => $this->data['commerce_nom'],
										  'item_id' => $this->data['commerce_id'],
										  'sale_quantity' => $this->vente_quantite,
										  'sale_cost' => $this->achat_cout);

						$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => 'marketAjoutVente', 'data' => $log_data);

						wanLog::log($log);

						$this->advert = "Objet mis en vente sur le marché";
						return TRUE;
					}
					else
					{
						$this->advert = "Impossible de mettre l'objet en vente sur le marché";
						return FALSE;
					}
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
		else
		{
			$this->advert = "Tu n'as pas spécifié un montant valide !";
			return FALSE;
		}
	}

	/*
	 * LOGS PARSE
	 */
	public static function marketAchatObjetLogParse($data)
	{
		switch ($data['mode'])
		{
			case 'buyer' :
				return '<a href="index.php?page=profil&id='.$data['buyer_id'].'">'.$data['buyer_login'].'</a> a acheté sur le 
						marché "'.$data['item_name'].'" x'.$data['sale_quantity'].' à 
						<a href="index.php?page=profil&id='.$data['seller_id'].'">'.$data['seller_login'].'</a> 
						pour '.$data['sale_cost'].' ryôs';
			break;

			case 'seller' :
				return '<a href="index.php?page=profil&id='.$data['seller_id'].'">'.$data['seller_login'].'</a> a vendu sur le 
						marché "'.$data['item_name'].'" x'.$data['sale_quantity'].' à 
						<a href="index.php?page=profil&id='.$data['buyer_id'].'">'.$data['buyer_login'].'</a> 
						pour '.$data['sale_cost'].' ryôs';
			break;
		}
	}

	public static function marketAjoutVenteLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> met en vente sur la marché 
				"'.$data['item_name'].'" x'.$data['sale_quantity'].' à '.$data['sale_cost'].' ryôs l\'unité';
	}

	public static function marketDeleteVenteLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> retire "'.$data['item_name'].'" de la vente sur le marché';
	}

	/*
	 * NOTIFICATION PARSE
	 */
	public static function marketAchatObjetNotificationParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['buyer_id'].'">'.$data['buyer_login'].'</a> t\'as acheté sur 
				le marché "'.$data['item_name'].'" x'.$data['sale_quantity'].' pour '.$data['sale_cost'].' ryôs';
	}
}

?>