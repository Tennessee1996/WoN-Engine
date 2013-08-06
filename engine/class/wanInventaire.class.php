<?php

/*
 *	Classe permettant de manipuler l'inventaire d'un ninja
 */

class wanInventaire
{
	// instance de l'objet ninja (référence)
	private $_ninja = NULL;
	// id de l'objet à manipuler
	private $inv_objet_id = NULL;
	// quantité de l'objet à manipuler
	public $inv_objet_qt;
	
	/*
	 * Constructeur
	 */
	public function __construct(wanNinja $_ninja)
	{
		if ($_ninja instanceof wanNinja)
		{
			$this->_ninja = $_ninja;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Initialisation de l'objet à manipuler
	 */
	public function initObjet($inv_objet_id = NULL)
	{
		$inv_objet_id = (int) $inv_objet_id;
		
		if (isset($inv_objet_id))
		{
			$this->inv_objet_id = $inv_objet_id;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Vérifie l'inventaire du ninja par rapport à l'objet initialisé
	 */
	public function ninjaCheckInventaire()
	{
		if (!empty($this->inv_objet_id))
		{
			$req_ninja_objet = "SELECT 
									* 
								FROM 
									wan_inventaire 
								WHERE 
									inventaire_objet_id=".$this->inv_objet_id." 
									AND 
									inventaire_ninja_id='".$this->_ninja->ninja['stats_id']."' 
								LIMIT 
									1";
			
			$res_ninja_objet = ktPDO::get()->query($req_ninja_objet);
			
			if ($res_ninja_objet != FALSE AND $res_ninja_objet->rowCount() > 0)
			{
				$ret_ninja_objet = $res_ninja_objet->fetch(PDO::FETCH_ASSOC);
				$res_ninja_objet->closeCursor();
				$this->inv_objet_qt = $ret_ninja_objet['inventaire_objet_quantite'];
				return $ret_ninja_objet['inventaire_objet_quantite'];
			}
			else
				return 0;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 *	Jette un objet
	 */
	public function ninjaTrashObjet($quantite = 1)
	{
		$quantite = (int) $quantite;
		
		if (!empty($this->inv_objet_id))
		{
			if ($this->ninjaCheckInventaire() >= $quantite)
			{
				$state = $this->ninjaMajInventaire($quantite, 'pick');
				
				if ($state)
				{
					$_SESSION['flash'] = "Objet(s) supprimé(s) [x".$quantite."]";
					return TRUE;
				}
				else
				{
					$_SESSION['flash'] = "Rien ne se passe...";
					return FALSE;
				}
			}
			else
			{
				$_SESSION['flash'] = "Tu n'en a pas assez pour en jeter autant";
				return FALSE;
			}
		}
		else
		{
			$_SESSION['flash'] = "Rien ne se passe...";
			return FALSE;
		}
	}
	
	/*
	 * Met à jour l'inventaire d'un ninja par rapport à l'objet initialisé
	 */
	public function ninjaMajInventaire($objet_quantite = 1, $mode = 'add')
	{
		if (empty($this->inv_objet_qt))
			$this->ninjaCheckInventaire();

		switch ($mode)
		{
			case 'add' :
				if ($this->inv_objet_qt > 0)
					return $this->ninjaUpdateInventaire($objet_quantite);
				else
					return $this->ninjaInsertInventaire($objet_quantite);
			break;
			
			case 'pick' :
				if ($this->inv_objet_qt > $objet_quantite)
					return $this->ninjaUpdateInventaire($objet_quantite, 'pick');
				else
					return $this->ninjaDelInventaire($objet_quantite);
			break;
		}
		
	}
	
	/*
	 * Insert un objet dans l'inventaire du ninja
	 */
	private function ninjaInsertInventaire($objet_quantite = 1)
	{
		$objet_quantite = (int) abs($objet_quantite);
		
		$req_add = "INSERT INTO 
						wan_inventaire (inventaire_ninja_id, inventaire_objet_id, inventaire_objet_quantite)
					VALUES 
						('".$this->_ninja->ninja['stats_id']."', ".$this->inv_objet_id.", ".$objet_quantite.")";
						
		$res_add = ktPDO::get()->exec($req_add);
		
		if ($res_add != 1)
			return 0;
		else
			return 1;
	}
	
	/*
	 * Met à jour l'inventaire du ninja
	 */
	private function ninjaUpdateInventaire($objet_quantite = 1, $mode = 'add')
	{
		$objet_quantite = (int) abs($objet_quantite);
		
		switch ($mode)
		{
			case 'add' :
				$req_maj = "UPDATE 
								wan_inventaire 
							SET 
								inventaire_objet_quantite=inventaire_objet_quantite+".$objet_quantite." 
							WHERE 
								inventaire_ninja_id='".$this->_ninja->ninja['stats_id']."' 
								AND 
								inventaire_objet_id=".$this->inv_objet_id." 
							LIMIT 
								1";
			break;
			
			case 'pick':
				$req_maj = "UPDATE 
								wan_inventaire 
							SET 
								inventaire_objet_quantite=inventaire_objet_quantite-".$objet_quantite." 
							WHERE 
								inventaire_ninja_id='".$this->_ninja->ninja['stats_id']."' 
								AND 
								inventaire_objet_id=".$this->inv_objet_id." 
							LIMIT 
								1";
			break;
		}
		
		$res_maj = ktPDO::get()->exec($req_maj);
		
		if ($res_maj != 1)
			return 0;
		else
			return 1;
	}
	
	/*
	 * Supprime un objet de l'inventaire du ninja
	 */
	public function ninjaDelInventaire()
	{
		$req_del = "DELETE 
					FROM 
						wan_inventaire 
					WHERE 
						inventaire_ninja_id='".$this->_ninja->ninja['stats_id']."' 
						AND 
						inventaire_objet_id=".$this->inv_objet_id." 
					LIMIT 
						1";
						
		$res_del = ktPDO::get()->exec($req_del);
		
		if ($res_del != 1)
			return 0;
		else
			return 1;
	}

	/*
	 * Récupère l'inventaire d'un ninja
	 */
	public function ninjaGetInventory()
	{
		$request = "SELECT 
						wan_inventaire.*, 
						wan_categorie_objet.*,
						wan_commerces.* 
					FROM 
						wan_inventaire 
						LEFT JOIN 
							wan_commerces 
							ON 
								wan_commerces.commerce_id = wan_inventaire.inventaire_objet_id 
						LEFT JOIN 
							wan_categorie_objet 
							ON 
								wan_categorie_objet.objet_id = wan_commerces.commerce_type 
					WHERE 
						wan_inventaire.inventaire_ninja_id = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])." 
					ORDER BY 
						wan_commerces.commerce_type ASC, wan_commerces.commerce_nom ASC";

		$result = ktPDO::get()->query($request);

		if ($result != FALSE AND $result->rowCount() > 0)
		{
			$items[] = array();

			while ($return = $result->fetch(PDO::FETCH_ASSOC))
			{
				$items[$return['commerce_type']]['category_name'] = $return['objet_categorie'];
				$items[$return['commerce_type']]['items'][$return['commerce_id']] = $return;
			}

			$result->closeCursor();

			return $items;
		}
		else
		{
			return FALSE;
		}
	}
}