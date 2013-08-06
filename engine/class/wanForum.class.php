<?php

/*
 * Classe de gestion du forum
 */

class wanForum
{
	// instance de la classe wanNinja
	public $_ninja;
	// liste des positions dans le forum
	public $array_modes = array('home', 'fil', 'repondre', 'creer', 'supprimer', 'editer');
	// position dans le forum
	public $current_mode = 'home';
	// message de sortie
	private $message;
	// redirection de sortie
	public $redirection;
	// catégories [nom - public - village - akatsuki - myoboku]
	private $array_categories = array(1 => array(1, 'Hagakure', FALSE, 1, FALSE, FALSE), 
									  2 => array(2, 'Iwagakure', FALSE, 2, FALSE, FALSE), 
									  3 => array(3, 'Akatsuki', FALSE, 0, TRUE, FALSE),
									  4 => array(4, 'Mont Myoboku', FALSE, 0, FALSE, TRUE), 
									  5 => array(5, 'Bar', TRUE, 0, FALSE, FALSE), 
									  6 => array(6, 'Way of Ninja', TRUE, 0, FALSE, FALSE));
	
	// variables du fil
	private $fil;
	// nom du fil
	public $fil_name = '';
	// variable de la catégorie
	private $cat;
	// requete SQL a executer
	private $sql_query;
	// données transmises par mysql
	private $sql_datas;
	// nombre de messages retournés
	private $count_messages = 0;
	// page en cours
	private $current_page = 0;
	// nombre de page total
	public $count_page = NULL;
	// private messages par page
	private $messages_page = 20;
	// autorisation d'afficher le topic
	public $allowed = FALSE;
	// message d'avertissement
	public $advert;
	// ninjas online
	private $online_ninja;
	
	/*
	 * Constructeur de la classe
	 */
	public function __construct(wanNinja $_ninja)
	{
		if ($_ninja instanceof wanNinja)
		{
			$this->_ninja = $_ninja;
			$this->redirection = wanEngine::previousPage();
		}
		else
			wanEngine::setError('Ninja non initialisé');
	}
	
	/*
	 * Méthode magique de récupération des variables
	 */
	public function __get($var)
	{
		return $this->$var;
	}
	
	/*
	 * Switch le mode de la classe
	 */
	public function initPosition($mode)
	{
		$this->online_ninja = wanEngine::ninjaOnline();
		
		if (in_array($mode, $this->array_modes))
			$this->current_mode = $mode;
		else
			return FALSE;
	}
	
	/*
	 * Indique une variable de fil
	 */
	public function setFil($id)
	{
		$this->fil = htmlentities($id);
	}
	
	/*
	 * Définie une catégorie pour l'affichage de l'accueil du forum
	 */
	public function setCat($cat)
	{
		if ($this->checkAccess($cat))
		{
			$this->cat = $cat;
			return TRUE;
		}
		else
		{
			$this->advert = "Cette catégorie n'existe pas";
			return FALSE;
		}
	}
	
	/*
	 * Comptes les pages du fil
	 */
	public function countPages()
	{
		$this->sql_query = "SELECT 
								wan_fils.* 
							FROM 
								wan_fils  
							WHERE 
								(fil_parent = ".$this->fil." 
								 OR 
								 fil_id = ".$this->fil.") 
							ORDER BY 
								fil_parent ASC, fil_date ASC";
								
		$res = ktPDO::get()->query($this->sql_query);
		
		if (!$res OR $res->rowCount() == 0)
		{
			$this->advert = "Ce sujet n'existe pas !";
			return FALSE;
		}
		else
		{
			$this->count_messages = $res->rowCount();
			$this->count_page = ceil($res->rowCount() / $this->messages_page);
			return TRUE;
		}
	}
	
	/*
	 * Retourne les données d'affichage
	 */
	private function prepareModeData()
	{
		switch ($this->current_mode)
		{
			case 'fil' :
				
				$this->sql_query = "SELECT 
										ninja_id, ninja_login, ninja_admin, ninja_modo, ninja_avatar, 
										stats_id, stats_clan, stats_rang, stats_niveau, 
										village_id, village_nom, 
										rang_id, rang_nom, 
										clan_id, clan_nom, 
										wan_fils.* 
									FROM 
										wan_fils 
											LEFT JOIN 
												wan_ninja ON wan_ninja.ninja_id = wan_fils.fil_ninja 
											LEFT JOIN 
												wan_stats ON wan_stats.stats_id = wan_fils.fil_ninja 
											LEFT JOIN 
												wan_villages ON wan_villages.village_id = wan_stats.stats_village 
											LEFT JOIN 
												wan_clan ON wan_clan.clan_id = wan_stats.stats_clan 
											LEFT JOIN 
												wan_rangs ON wan_rangs.rang_id = wan_stats.stats_rang 
									WHERE 
										(fil_parent = ".$this->fil." 
										 OR 
										 fil_id = ".$this->fil.") 
									ORDER BY 
										fil_parent ASC, fil_date ASC 
									LIMIT 
										".$this->startPage().", ".$this->messages_page." ";
				
			break;
			
			case 'supprimer' :
			case 'editer' :
			case 'repondre' :
				
				$this->sql_query = "SELECT 
										* 
									FROM 
										wan_fils 
									WHERE 
										(fil_parent = ".$this->fil." 
										 OR 
										 fil_id = ".$this->fil.")
									LIMIT 
										1";
				
			break;
			
			default :
			
				if ($this->_ninja->ninjaIsModo())
				{
					$this->sql_query = "SELECT 
											* 
										FROM 
											wan_fils 
										WHERE 
											fil_parent = 000000000 ";
					
					if (!empty($this->cat))
						$this->sql_query .= " AND fil_cat = ".ktPDO::get()->quote($this->cat)." ";
					
					$this->sql_query .= "ORDER BY 
											fil_date DESC 
										LIMIT 
											25";
				}
				else
				{
					if (!empty($this->cat))
					{
						$this->sql_query = "SELECT 
												* 
											FROM 
												wan_fils 
											WHERE 
												fil_cat = ".ktPDO::get()->quote($this->cat)."
												AND 
												fil_parent = 000000000 
											ORDER BY 
												fil_date DESC 
											LIMIT 
												25";
					}
					else
					{
						$this->sql_query = "SELECT 
												* 
											FROM 
												wan_fils 
											WHERE 
												fil_cat IN (".$this->getCategoriesSQL().") 
												AND 
												fil_parent = 000000000 
											ORDER BY 
												fil_date DESC 
											LIMIT 
												25";
					}
				}
				
			break;
		}
	}
	
	/*
	 * Transforme l'affiache du pseudo en fonction des données de chaque post
	 */
	public static function forumTransformPseudo($ninja)
	{
		if (is_array($ninja))
		{
			$pseudo = '';
			
			if ($ninja['ninja_admin'] == '1' && $ninja['ninja_modo'] == '1' OR $ninja['ninja_admin'] == '1' && $ninja['ninja_modo'] == '0')
				$pseudo .= '<img src="medias/icones/ico_admin.png" style="width:12px;" alt="Administrateur" title="Administrateur" /> ';
			
			if ($ninja['ninja_admin'] == '0' AND $ninja['ninja_modo'] == '1')
				$pseudo .= '<img src="medias/icones/ico_modo.png" style="width:12px;" alt="Modérateur" title="Modérateur" /> ';
			
			$pseudo .= $ninja['ninja_login'];
			
			if ($ninja['stats_clan'] != '0')
				$pseudo .= ' '.$ninja['clan_nom'];
			
			return $pseudo;
		}
		else
			return 'Ninja';
	}
	
	/*
	 * Initialise la pagination
	 */
	public function initPage($page = NULL)
	{
		if ($page == NULL)
		{
			$this->current_page = 0;
		}
		else
		{
			$this->current_page = (int) $page;
		}
	}
	
	/*
	 * Donne le message de départ en fonction de la page
	 */
	private function startPage()
	{
		if ($this->current_page == 0)
		{
			return 0;
		}
		else
		{
			$tmp = $this->current_page * $this->messages_page;
			return $tmp;
		}
	}
	
	/*
	 * Récupere la pagination
	 */
	public function getPagination()
	{
		$tmp = "";
		
		//$display_pages = array(1, 2, 3, $this->count_page - 1, $this->count_page - 2, $this->count_pages);
		$display_pages = array(1, 2, 
								$this->current_page - 1, 
								$this->current_page - 2, 
								$this->current_page + 1, 
								$this->current_page + 2, 
								$this->count_page - 2, 
								$this->count_page - 1, 
								$this->count_page);
	
		$marked_resume = FALSE;
		
		for ($i = 0; $i < $this->count_page; $i++)
		{
			$j = $i + 1;
		
			if ($i == $this->current_page)
			{
				$tmp .= ' '.$j.' - ';
			}
			else
			{
				if ($this->count_page > 12)
				{
					if (in_array($j, $display_pages))
					{
						$tmp .= ' <a href="index.php?page=agora&mode=forum&forum=fil&fil='.$this->fil.'&offset='.$i.'">'.$j.'</a> - ';
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
					$tmp .= ' <a href="index.php?page=agora&mode=forum&forum=fil&fil='.$this->fil.'&offset='.$i.'">'.$j.'</a> - ';
				}
			}
		}
		
		$tmp = substr($tmp, 0, -3);
		
		return $tmp;
	}
	
	/*
	 * Execute la requete SQL
	 */
	private function executeModeData()
	{
		$this->prepareModeData();
		
		if (!empty($this->sql_query))
		{
			$result = ktPDO::get()->query($this->sql_query);
			
			if (!$result OR $result->rowCount() < 1)
				return FALSE;
			else
			{
				$out = array();
				
				while ($datas = $result->fetch(PDO::FETCH_ASSOC))
					$out[] = $datas;
				
				$result->closeCursor();
				
				$this->sql_datas = $out;
				return TRUE;
			}
		}
		else
			return FALSE;
	}
	
	/*
	 * Retourne les données préparées pour l'affichage de l'acueil
	 */
	public function getModeData_Home()
	{
		$this->executeModeData();
		
		if (!empty($this->sql_datas))
		{
			foreach ($this->sql_datas as $key => $value)
				$this->sql_datas[$key]['fil_cat'] = $this->array_categories[$value['fil_cat']][1];
				
			return $this->sql_datas;
		}
		else
			return FALSE;
	}
	
	/*
	 * Retourne les données préparées pour l'affichage d'un fil
	 */
	public function getModeData_Fil()
	{
		$this->executeModeData();
		
		$this->online_ninja = wanEngine::ninjaOnline();
		
		if (!empty($this->sql_datas))
		{
			foreach ($this->sql_datas as $key => $value)
			{
				$this->sql_datas[$key]['fil_ninja_name'] = wanForum::forumTransformPseudo($this->sql_datas[$key]);
				
				if (empty($this->cat))
					$this->cat = $value['fil_cat'];
					
				if (empty($this->fil_name))
					$this->fil_name = $value['fil_name'];
					
				if ($this->checkOnlineNinja($value['ninja_id']))
					$this->sql_datas[$key]['statut'] = '<span style="color:green;">En ligne</span>';
				else
					$this->sql_datas[$key]['statut'] = '<span style="color:red;">Hors-ligne</span>';
			}

			$this->checkAccess($this->cat);
			
			return $this->sql_datas;
		}
		else
			return FALSE;
	}
	
	/*
	 * Retourne les données préparées pour l'ajout d'une réponse
	 */
	public function getModeData_Default()
	{
		$this->executeModeData();
		$this->checkAccess($this->sql_datas[0]['fil_cat']);
		
		$this->count_messages = count($this->sql_datas);
		
		return $this->sql_datas;
	}
	
	/*
	 * Dit si un ninja est en ligne ou hors ligne
	 */
	private function checkOnlineNinja($id = '')
	{
		if (!empty($id) AND is_array($this->online_ninja))
		{
			if (array_key_exists($id, $this->online_ninja))
				return TRUE;
			else
				return FALSE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Retourne les catégories dans lesquelles l'utilisateur peut lancer une discussion
	 */
	public function getCategories()
	{
		$out = array();
		
		foreach ($this->array_categories as $key => $value)
		{
			if ($this->checkAccess($key))
				$out[] = $this->array_categories[$key];
		}
		
		return $out;
	}
	
	/*
	 * Retourne les catégories disponibles pour l'utilisateur au format SQL
	 */
	public function getCategoriesSQL()
	{
		$out = "";
		
		foreach ($this->array_categories as $key => $value)
		{
			if ($this->checkAccess($key))
				$out .= $key.', ';
		}
		
		$out = substr($out, 0, -2);
		
		return $out;
	}
	
	/*
	 * Retourne la liste des catégories sous forme html
	 */
	public function getCategoriesHTML()
	{
		$out = '';
		
		$list = $this->getCategoriesSQL();
		$list = explode(', ', $list);
		
		if (empty($this->cat))
			$out .= 'Tout - ';
		else
			$out .= '<a href="index.php?page=agora&mode=forum">Tout</a> - ';
		
		foreach ($list as $key => $value)
		{
			if ($this->cat == $value)
				$out .= $this->array_categories[$value][1].' - ';
			else
				$out .= '<a href="index.php?page=agora&mode=forum&categorie='.$value.'">'.$this->array_categories[$value][1].'</a> - ';
		}
		
		$out = substr($out, 0, -3);
		
		return $out;
	}
	
	/*
	 * Vérifie les données postées
	 */
	public function checkPostedData()
	{
		switch ($this->current_mode)
		{
			case 'creer' :
				
				if (empty($_POST['sujet']))
					$this->advert = "Tu n'as pas mis de sujet";
				else if (strlen($_POST['sujet']) > 64)
					$this->advert = "Le sujet est trop long";
				else if (strlen($_POST['sujet']) < 4)
					$this->advert = "Le sujet est trop court";
				else if (!$this->checkAccess($_POST['cat']))
					$this->advert = "Tu ne peux pas poster dans cette catégorie";
				else if (empty($_POST['message']))
					$this->advert = "Ton message est vide";
				else
					return TRUE;
					
				return FALSE;
			
			break;
			
			case 'editer' :
			case 'repondre' :
			
				if (empty($_POST['reponse']))
					$this->advert = "Tu n'as pas mis de réponse";
				else if (strlen($_POST['reponse']) < 7)
					$this->advert = "Ta réponse est trop courte";
				else
					return TRUE;
				
				return FALSE;
				
			break;
		}
	}
	
	/*
	 * Vérifie la possibilité d'afficher des données
	 */
	private function checkAccess($categorie = NULL)
	{
		$tmp_data = $this->array_categories[$categorie];

		if ($tmp_data[2] == TRUE OR $this->_ninja->ninjaIsModo())
			$this->allowed = TRUE;
		else {
			if ($tmp_data[3] != 0 AND $tmp_data[3] != $this->_ninja->ninja['stats_village'])
				$this->allowed = FALSE;
			else if ($tmp_data[4] == TRUE AND $this->_ninja->ninja['stats_rang'] != 6)
				$this->allowed = FALSE;
			else if ($tmp_data[5] == TRUE AND $this->_ninja->ninja['stats_rang'] != 7)
				$this->allowed = FALSE;
			else
				$this->allowed = TRUE;
		}
		
		return $this->allowed;
	}
	
	/*
	 * Vérifie si l'utilisateur est le propriétaire du post
	 */
	public function checkOwner($reponse_datas = NULL)
	{
		if ($this->_ninja->ninjaisModo())
			return TRUE;
		else {
			empty($reponse_datas) ? $tmp_datas = $this->sql_datas[0] : $tmp_datas = $reponse_datas;
			
			if ($tmp_datas['fil_ninja'] == $this->_ninja->ninja['stats_id'])
				return TRUE;
			else
				return FALSE;
		}
	}
	
	/*
	 *	Vérifie si le message récupéré correspond à un fil entier
	 */
	public function checkReponseIsFil()
	{
		if ($this->current_page == 0)
		{
			if ($this->sql_datas[0]['fil_parent'] == '000000000')
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
	 * Retourne le nombre de message total de façon jolie
	 */
	public function returnCountMessages()
	{
		$tmp_count_messages = $this->count_messages - 1;
		
		if ($tmp_count_messages == 0)
			return 'Aucune réponse';
		else if ($tmp_count_messages == 1)
			return '1 réponse';
		else
			return $tmp_count_messages.' réponses';
	}
	
	/*
	 * Ajoute un fil
	 */
	public function addFil()
	{
		$data_sujet = addslashes(substr($_POST['sujet'], 0, 64));
		$data_message = addslashes($_POST['message']);
		$data_categorie =  (int) $_POST['categorie'];
		
		if (!$this->checkAccess($data_categorie))
			return FALSE;
		
		$req = "INSERT INTO 
					wan_fils (fil_cat, fil_name, fil_date, fil_message, fil_ninja)
				VALUES
					(".$data_categorie.", 
					 '".$data_sujet."', 
					 ".time().", 
					 '".$data_message."', 
					 '".$this->_ninja->ninja['stats_id']."')";

		$res = ktPDO::get()->exec($req);
		
		if ($res == 1) {
			$this->fil = ktPDO::get()->lastInsertId('fil_id');
			return TRUE;
		}
		else
			return FALSE;
	}
	
	/*
	 * Ajoute une réponse à un fil
	 */
	public function addReponse()
	{
		$data = $_POST['reponse'];
		
		if (!empty($data))
		{
			$req_insert = "INSERT INTO 
								wan_fils (fil_parent, fil_cat, fil_name, fil_date, fil_message, fil_ninja)
							VALUES
								(".$this->fil.", 
								 ".$this->sql_datas[0]['fil_cat'].", 
								 '".$this->sql_datas[0]['fil_name']."', 
								 ".time().", 
								 ".ktPDO::get()->quote($_POST['reponse']).", 
								 '".$this->_ninja->ninja['stats_id']."')";
			
			$req_update = "UPDATE 
								wan_fils 
							SET 
								fil_date = ".time()." 
							WHERE 
								fil_id = ".$this->fil." 
							LIMIT 
								1";
			
			$res = ktPDO::get()->exec($req_insert);
			$res = ktPDO::get()->exec($req_update);
			
			if ($res == 0)
				return FALSE;
			else
				return TRUE;
		}
		else
			return FALSE;
	}
	
	/*
	 * Edite une réponse
	 */
	public function editReponse()
	{
		if ($this->checkReponseIsFil())
			$this->redirection = "index.php?page=agora&mode=forum&forum=fil&fil=".$this->fil;
		else
			$this->redirection = "index.php?page=agora&mode=forum&forum=fil&fil=".$this->sql_datas[0]['fil_parent'];
			
		if ($this->current_page > 0)
			$this->redirection .= '&offset='.$this->current_page;
		
		$data = addslashes($_POST['reponse']);
		
		if (!empty($data))
		{
			$req = "UPDATE 
						wan_fils 
					SET 
						fil_message = '".$data."' 
					WHERE 
						fil_id = ".$this->fil." 
					LIMIT 
						1";
			
			$res = ktPDO::get()->exec($req);
			
			if ($res == 1)
			{
				$this->advert = "Réponse mise à jour";
				return TRUE;
			}
			else
			{
				$this->advert = "Impossible de mettre la réponse à jour";
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Supprime une réponse à un fil
	 */
	public function supprimerReponse()
	{
		$this->getModeData_Default();
		$this->redirection = 'index.php?page=agora&mode=forum&forum=fil&fil='.$this->sql_datas[0]['fil_parent'];
		
		if ($this->current_page > 0)
			$this->redirection .= '&offset='.$this->current_page;
		
		if ($this->checkOwner()) {
			if ($this->checkReponseIsFil()) {
				if ($this->supprimerFil()) {
					return TRUE;
				}
				else {
					return FALSE;
				}
			}
			else {
				$req = "DELETE FROM wan_fils WHERE fil_id = ".$this->fil." LIMIT 1";
				
				$res = ktPDO::get()->exec($req);
				
				if ($res == 1) {
					$this->advert = "Message supprimé";
					return TRUE;
				}
				else {
					$this->advert = "Impossible de supprimer ce message";
					return FALSE;
				}
			}
		}
		else {
			$this->advert = "Tu ne peux pas supprimer les messages des autres";
			return FALSE;
		}
	}
	
	/*
	 * Supprime un fil
	 */
	private function supprimerFil()
	{	
		$req = "DELETE FROM wan_fils WHERE fil_id = ".$this->fil." OR fil_parent = ".$this->fil."";
		
		$res = ktPDO::get()->exec($req);
		$this->redirection = 'index.php?page=agora&mode=forum';
		
		if ($res = $this->count_messages) {
			$this->advert = "Fil supprimé";
			return TRUE;
		}
		else {
			$this->advert = "Impossible de supprimer le fil dans son intégralité";
			return FALSE;
		}
	}
	
	/*
	 * Formate la date
	 */
	public function candyDate($date)
	{
		$message = date('d', $date);
		
		$hier = date('d') - 1;
		$avanthier = date('d') - 2;
		
		if ($message == date('d'))
			return 'Aujourd\'hui à '.date('H\:i', $date);
		else if ($message == $hier)
			return 'Hier à '.date('H\:i', $date);
		else if ($message == $avanthier)
			return 'Avant-hier à '.date('H\:i', $date);
		else
			return 'Le '.date('d\.m\.Y', $date);
	}
}

?>