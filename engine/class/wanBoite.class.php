<?php

class wanBoite
{
	public $_ninja;
	public $advert;
	public $recepteur = array();
	public $id_source;
	
	private $message = array();
	
	private $message_per_page = 8;
	private $message_start = 0;
	private $total_page = 0;
	private $total_message = 0;
	private $current_page = 0;
	
	/*
	 * Constructeur de la classe
	 */
	public function __construct($_ninja = NULL)
	{
		if ($_ninja instanceof wanNinja)
		{
			$this->_ninja = $_ninja;
		}
	}
	
	/*
	 * Récupère toutes les discussions d'un ninja
	 */
	public function boiteGetReceipt()
	{
		$boite = array();
		
		$req = "SELECT 
					wan_boite.*, 
					ninja_id, ninja_login, 
					stats_id, stats_clan, 
					clan_id, clan_nom 
				FROM 
					wan_boite 
					LEFT JOIN 
						wan_ninja ON
							IF (".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." = boite_ninja_1, ninja_id = boite_ninja_2, ninja_id = boite_ninja_1)
						LEFT JOIN 
							wan_stats ON stats_id = ninja_id 
						LEFT JOIN 
							wan_clan ON clan_id = stats_clan 
				WHERE 
					boite_ninja_1 = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." 
						OR 
					 boite_ninja_2 = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])."
				ORDER BY 
					boite_state ASC, boite_date DESC";
		
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			return $boite;
		}
		else
		{
			while ($ret = $res->fetch(PDO::FETCH_ASSOC))
			{
				if ($ret['stats_clan'] != '0')
				{
					$ret['ninja_login'] .= ' '.$ret['clan_nom'];
				}

				$boite[] = $ret;
			}
				
			$res->closeCursor();
				
			return $boite;
		}
	}
	
	/*
	 * Extrapole le recepteur
	 */
	public function boiteExtraRecepteur()
	{	
		$tmp_ninja = array('1' => $this->message[0]['boite_ninja_1'], '2' => $this->message[0]['boite_ninja_2']);
		
		foreach ($tmp_ninja as $key => $value)
		{
			if ($value != $this->_ninja->ninja['ninja_id'])
				return $value;
		}
	}
	
	/*
	 * Définit le recepteur du message qui va être envoyé
	 */
	public function boiteSetRecepteur($id_recepteur)
	{
		return $this->boiteGetNinja($id_recepteur);
	}
	
	/*
	 * Définit l'ID de la conversation en cours grâce à l'ID source
	 */
	public function boiteSetSource($id_source)
	{
		$this->id_source = $id_source;
	}
	
	/*
	 * Récupere la pagination
	 */
	public function getPagination()
	{
		$tmp = "";
		
		$display_pages = array(1, 2, 
								$this->current_page - 1, 
								$this->current_page - 2, 
								$this->current_page + 1, 
								$this->current_page + 2, 
								$this->total_page - 2, 
								$this->total_page - 1, 
								$this->total_page);
	
		$marked_resume = FALSE;
		
		for ($i = 0, $j = 1; $i < $this->total_page; $i++, $j++)
		{	
			if ($i == $this->current_page)
			{
				$tmp .= ' '.$j.' - ';
			}
			else
			{
				if ($this->total_page > 12)
				{
					if (in_array($i, $display_pages))
					{
						$tmp .= ' <a href="index.php?page=appartement&mode=boite&boite=lire&id='.$this->id_source.'&offset='.$i.'">'.$j.'</a> - ';
					}
					else
					{
						if (!$marked_resume)
						{
							$tmp .= ' [...] - ';
							$marked_resume = TRUE;
						}
					}
				}
				else
				{
					$tmp .= ' <a href="index.php?page=appartement&mode=boite&boite=lire&id='.$this->id_source.'&offset='.$i.'">'.$j.'</a> - ';
				}
			}
		}
		
		$tmp = substr($tmp, 0, -3);
		
		return $tmp;
	}
	
	/*
	 * Compte les messages d'une conversation
	 */
	private function boiteCountOnce()
	{
		$req = "SELECT 
					wan_boite.*, 
					wan_lettres.* 
				FROM 
					wan_lettres 
					LEFT JOIN 
						wan_boite ON boite_id = lettre_boite
				WHERE 
					lettre_boite = ".ktPDO::get()->quote($this->id_source)." 
				ORDER BY 
					lettre_date DESC";
					
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			return FALSE;
		}
		else
		{
			$this->current_page = (int) abs($_GET['offset']);
			$this->total_message = $res->rowCount();
			$this->total_page = ceil($this->total_message / $this->message_per_page);
			$this->message_start = ceil($this->message_per_page * $this->current_page);
		}
	}
	
	/*
	 * Récupère un message en particulier grâce à l'ID source
	 */
	public function boiteGetOnce($pagination = TRUE)
	{
		$this->boiteCountOnce();
		
		$req = "SELECT 
					wan_boite.*, 
					wan_lettres.*, 
					ninja_id, ninja_login, ninja_avatar, 
					stats_id, stats_clan, stats_rang, stats_niveau, stats_village, 
					clan_nom, clan_id, 
					village_id, village_nom, 
					rang_id, rang_nom 
				FROM 
					wan_lettres 
					LEFT JOIN 
						wan_boite ON boite_id = lettre_boite 
					LEFT JOIN 
						wan_ninja ON ninja_id = lettre_ninja  
					LEFT JOIN 
						wan_stats ON stats_id = ninja_id 
					LEFT JOIN 
						wan_clan ON clan_id = stats_clan 
					LEFT JOIN 
						wan_rangs ON rang_id = stats_rang 
					LEFT JOIN 
						wan_villages ON village_id = stats_village 
				WHERE 
					lettre_boite = ".ktPDO::get()->quote($this->id_source)." 
				ORDER BY 
					lettre_date DESC";
		
		if ($pagination)
			$req .= " LIMIT ".$this->message_start.", ".$this->message_per_page;
		
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			$this->advert = "Ce message n'existe pas !";
			return $this->message;
		}
		else
		{
			while ($ret = $res->fetch(PDO::FETCH_ASSOC))
			{
				if ($ret['stats_clan'] != '0')
					$ret['ninja_login'] .= ' '.$ret['clan_nom'];
					
				if ($ret['stats_rang'] == 6)
					$ret['village_nom'] = '<span style="text-decoration:line-through;">'.$ret['village_nom'].'</span>';
					
				$this->message[] = $ret;
			}
			
			$res->closeCursor();
			
			$this->boiteMajState();
			
			if ($this->boiteCheckOwn())
				return $this->message;
			else
				return FALSE;
		}
	}
	
	/*
	 * Vérifie le propriétaire d'une conversation
	 */
	private function boiteCheckOwn()
	{
		if ($this->message[0]['boite_ninja_1'] == $this->_ninja->ninja['ninja_id'] OR
			$this->message[0]['boite_ninja_2'] == $this->_ninja->ninja['ninja_id'])
		{
			return TRUE;
		}
		else
		{
			$this->advert = "Cette conversation ne te concerne pas !";
			return FALSE;
		}
	}
	
	/*
	 *	Vérifie si une conversation existe déjà entre 2 ninjas
	 */
	private function boiteDiscussExists()
	{
		$req = "SELECT 
					* 
				FROM 
					wan_boite 
				WHERE 
					(boite_ninja_1 = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." AND boite_ninja_2 = ".ktPDO::get()->quote($this->recepteur['ninja_id']).") 
						OR 
					(boite_ninja_2 = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." AND boite_ninja_1 = ".ktPDO::get()->quote($this->recepteur['ninja_id']).")
				ORDER BY 
					boite_date DESC 
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
			$res->closeCursor();
			return $ret['boite_id'];
		}
	}
	
	/*
	 * Récupere un ninja avec son Id
	 */
	private function boiteGetNinja($id_ninja)
	{
		$req = "SELECT 
					ninja_id, ninja_login 
				FROM 
					wan_ninja 
				WHERE 
					ninja_id = ".ktPDO::get()->quote($id_ninja)." 
					OR 
					ninja_login = ".ktPDO::get()->quote($id_ninja)."
				LIMIT 
					1";
		
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			$this->advert = "Ce ninja n'existe pas !";
			return FALSE;
		}
		else
		{
			$this->recepteur = $res->fetch(PDO::FETCH_ASSOC);
			$res->closeCursor();
			
			if ($this->boiteCheckRecepteur())
				return TRUE;
			else
				return FALSE;
		}
	}
	
	/*
	 * Met à jour le statut du message
	 */
	private function boiteMajState()
	{
		if ($this->message[0]['boite_state'] == 0 
			AND 
			$this->message[0]['boite_ninja'] == $this->_ninja->ninja['stats_id'])
		{
			$req = "UPDATE 
						wan_boite 
					SET 
						boite_state = 1 
					WHERE 
						boite_id = ".ktPDO::get()->quote($this->id_source)." 
					LIMIT 
						1";
			
			$res = ktPDO::get()->exec($req);
			
			if ($res == 1)
				return TRUE;
			else
				return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/*
	 * Met à jour la conversation source
	 */
	private function boiteMajSource()
	{
		$req = "UPDATE 
					wan_boite 
				SET 
					boite_ninja = ".ktPDO::get()->quote($this->recepteur['ninja_id']).", 
					boite_date = ".time().", 
					boite_state = 0 
				WHERE 
					boite_id = ".ktPDO::get()->quote($this->id_source)."";
		
		$res = ktPDO::get()->exec($req);
		
		if ($res == 1)
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Vérifie si la conversation peut être répondue
	 */
	public function boiteCanRepondre()
	{
		return TRUE;
	}
	
	/*
	 * Vérifie si la conversation est une conversation système
	 */
	public function boiteCheckNotSystem()
	{
		return TRUE;
	}
	
	/*
	 * Controle le recepteur et le ninja envoyeur
	 */
	private function boiteCheckRecepteur()
	{
		if ($this->_ninja->ninja['ninja_id'] == $this->recepteur['ninja_id'])
		{
			$this->advert = "Tu ne peux pas t'écrire à toi-même !";
			return FALSE;
		}
		else if (empty($this->recepteur))
		{
			$this->advert = "Tu n'as pas choisi de destinataire";
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/*
	 * Vide entièrement la boite d'un ninja
	 */
	public function boitePurger()
	{		
		$req_lettres = "DELETE FROM 
									wan_lettres 
								WHERE 
									lettre_ninja = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])."";
		
		$res_lettres = ktPDO::get()->exec($req_lettres);
		
		$req_boite = "DELETE FROM 
								wan_boite 
							WHERE 
								boite_ninja_1 = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])." 
									OR 
								boite_ninja_2 = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])."";
		
		$res_boite = ktPDO::get()->exec($req_boite);
		
		return "La boite aux lettres a été vidée !";
	}
	
	/*
	 * Supprimer une conversation
	 */
	public function boiteSupprimer()
	{
		$this->boiteGetOnce();
		
		if ($this->boiteCheckOwn() AND $this->boiteCheckNotSystem())
		{
			$req_boite = "DELETE 
						  FROM 
							wan_boite 
						  WHERE 
							boite_id = ".ktPDO::get()->quote($this->id_source)." 
						  LIMIT 
							1";
			
			$req_lettres = "DELETE 
							FROM 
								wan_lettres 
							WHERE 
								lettre_boite = ".ktPDO::get()->quote($this->id_source)."";
								
			$res = 0;
			$res += ktPDO::get()->exec($req_boite);
			$res += ktPDO::get()->exec($req_lettres);
			
			if ($res >= 1)
			{
				$this->advert = "Conversation supprimée !";
				return TRUE;
			}
			else
			{
				$this->advert = "Impossible de supprimer la conversation !";
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Ecrit un message entre deux ninjas
	 */
	public function boiteEcrire()
	{
		if (empty($this->id_source))
			$this->id_source = $this->boiteDiscussExists($this->recepteur['ninja_id']);
		
		if (!$this->id_source)
			return $this->boiteCreer();
		else
			return $this->boiteRepondre();
	}
	
	/*
	 * Crée une conversation entre deux ninjas
	 */
	public function boiteCreer()
	{
		$this->id_source = wanEngine::generateId(32);
		
		$req_boite = "INSERT INTO 
							wan_boite (boite_id, boite_ninja_1, boite_ninja_2, boite_date, boite_ninja)
						VALUES 
							(".ktPDO::get()->quote($this->id_source).", 
							 ".ktPDO::get()->quote($this->recepteur['ninja_id']).", 
							 ".ktPDO::get()->quote($this->_ninja->ninja['stats_id']).", 
							 ".time().", 
							 ".ktPDO::get()->quote($this->recepteur['ninja_id']).")";
		
		$req_lettre = "INSERT INTO 
							wan_lettres (lettre_id, lettre_boite, lettre_ninja, lettre_message, lettre_date)
						VALUES 
							(".ktPDO::get()->quote(wanEngine::generateId(32)).", 
							 ".ktPDO::get()->quote($this->id_source).", 
							 ".ktPDO::get()->quote($this->_ninja->ninja['stats_id']).", 
							 ".ktPDO::get()->quote($_POST['message']).", 
							 ".time().")";
		
		$res = 0;
		$res += ktPDO::get()->exec($req_boite);
		$res += ktPDO::get()->exec($req_lettre);
		
		if ($res == 2)
		{
			wanNotify::notify($this->recepteur['ninja_id'], __CLASS__, '');

			$this->advert = "Message envoyé !";
			return TRUE;
		}
		else
		{
			$this->advert = "Le message n'a pu être envoyé";
			return FALSE;
		}
	}
	 
	/*
	 * Répondre à une conversation
	 */
	public function boiteRepondre()
	{
		if ($this->boiteCanRepondre())
		{			
			$req = "INSERT INTO 
						wan_lettres (lettre_id, lettre_boite, lettre_ninja, lettre_message, lettre_date)
					VALUES 
						(".ktPDO::get()->quote(wanEngine::generateId(32)).", 
						 ".ktPDO::get()->quote($this->id_source).", 
						 ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id']).", 
						 ".ktPDO::get()->quote($_POST['message']).", 
						 ".time().")";
						 
			$res = ktPDO::get()->exec($req);
			$this->boiteMajSource();
			
			if ($res == 1)
			{
				wanNotify::notify($this->recepteur['ninja_id'], __CLASS__, '');
				
				$this->advert = "Message envoyé !";
				return TRUE;
			}
			else
			{
				$this->advert = "Le message n'a pu être envoyé";
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}
}

?>