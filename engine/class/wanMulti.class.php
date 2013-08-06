<?php

class wanMulti
{
	// instance de la classe wanNinja
	private $_ninja;
	
	// variables de la classe
	private $filename;
	private $filedata;
	
	// variables de sortie d'execution
	public $advert;
	public $redirection;
	
	/*
	 * Constructeur
	 */
	public function __construct(wanNinja $_ninja)
	{
		if ($_ninja instanceof wanNinja)
		{
			$this->_ninja = $_ninja;
			
			$this->multiFileName();
			$this->multiFileRead();
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 *	Retourne le nom du fichier du ninja
	 */
	private function multiFileName()
	{
		$this->filename = 'cache/multi/multi_'.$this->_ninja->ninja['ninja_account_id'].'.html';
		return;
	}
	
	/*
	 * Crée le fichier multi du joueur
	 */
	public function multiFileCreate()
	{
		$req = "SELECT 
					ninja_account_id, ninja_id, ninja_login, ninja_avatar, 
					stats_id, stats_niveau, stats_rang, 
					rang_id, rang_nom 
				FROM 
					wan_ninja 
						LEFT JOIN 
							wan_stats 
								ON 
									wan_stats.stats_id = wan_ninja.ninja_id 
						LEFT JOIN 
							wan_rangs 
								ON 
									wan_rangs.rang_id = wan_stats.stats_rang 
				WHERE 
					ninja_account_id = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_account_id'])." 
				LIMIT 
					3";
		
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			$this->advert = "Impossible de créer le fichier de configuration multi-comptes";
			return FALSE;
		}
		else
		{
			$data = array();
			
			while ($ret = $res->fetch(PDO::FETCH_ASSOC))
				$data[$ret['ninja_id']] = $ret;
				
			$res->closeCursor();
			
			if (count($data) == 0)
			{
				$this->advert = "Données multi-comptes invalides";
				return FALSE;
			}
			else
			{
				if ($this->multiFileWrite($data))
				{
					$this->advert = "Fichier multi-comptes mis à jour !";
					return TRUE;
				}
				else
				{
					$this->advert = "Impossible d'écrire le fichier multi-comptes";
					return FALSE;
				}
			}
		}
	}
	
	/*
	 * Ecrit dans le fichier multi du joueur
	 */
	private function multiFileWrite($data)
	{
		$data = serialize($data);
		
		$state = file_put_contents($this->filename, $data);
		
		if ($state != FALSE)
			return TRUE;
		else
			return TRUE;
	}
	
	/*
	 * Lit le fichier multi du joueur
	 */
	private function multiFileRead()
	{
		if (file_exists($this->filename))
		{
			$data = file_get_contents($this->filename);
			$this->filedata = unserialize($data);
			
			if (!empty($this->filedata))
				return TRUE;
			else
				return FALSE;
		}
		else
		{
			if ($this->multiFileCreate())
				return $this->multiFileRead();
			else
				return FALSE;
		}
	}
	
	/*
	 * Compte le nombre de comptes du joueurs
	 */
	public function multiCountAccounts()
	{
		return sizeof($this->filedata);
	}
	
	/*
	 * Vérifie si le compte est le compte principal
	 */
	public function multiCheckPrincipal()
	{
		if ($this->_ninja->ninja['ninja_id'] == $this->_ninja->ninja['ninja_account_id'])
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 *	Vérifie qu'un joueur appartient à un même autre joueur
	 */
	public function multiCheckNinja($ninja_id)
	{
		if (array_key_exists($ninja_id, $this->filedata))
		{
			$tmpdata = $this->filedata[$ninja_id];
			
			if ($tmpdata['ninja_account_id'] == $this->_ninja->ninja['ninja_account_id'])
			{
				return TRUE;
			}
			else
			{
				$this->advert = "Tu ne peux pas sélectionner ce ninja";
				return FALSE;
			}
			
		}
		else
		{
			$this->advert = "Ce ninja n'existe pas";
			return FALSE;
		}
	}
	
	/*
	 * Vérifie la connexion existante du ninja
	 */
	public function multiCheckConnexion($ninja_id)
	{
		if ($ninja_id != $this->_ninja->ninja['stats_id'])
		{
			return TRUE;
		}
		else
		{
			$this->advert = "Tu es déjà connecté avec ce ninja";
			return FALSE;
		}
	}
	
	/*
	 * Switch un ninja
	 */
	public function multiSwitchNinja($ninja_id)
	{	
		$_SESSION['control'] = array();
		$_SESSION['control']['agent'] = md5($_SERVER['HTTP_USER_AGENT']);
		$_SESSION['control']['ip'] = md5($_SERVER['REMOTE_ADDR']);
		$_SESSION['control']['time'] = time()+900;
		$_SESSION['ninja'] = array();
		$_SESSION['ninja']['id'] = $this->filedata[$ninja_id]['ninja_id'];
		$_SESSION['ninja']['login'] = $this->filedata[$ninja_id]['ninja_login'];
		$_SESSION['ninja']['time'] = time();
		wanSecurity::csrfInit();
		
		$requete = "UPDATE 
						wan_ninja 
					SET 
						ninja_log_errors = 0, 
						ninja_last_connect = ".time().", 
						ninja_ip = '".$_SERVER['REMOTE_ADDR']."'
					WHERE 
						ninja_id = ".ktPDO::get()->quote($this->filedata[$ninja_id]['ninja_id'])." 
					LIMIT 1";

		$result = ktPDO::get()->exec($requete);

		$_SESSION['flash'] = "Connecté en tant que ".$this->filedata[$ninja_id]['ninja_login']." !";
		wanEngine::redirect('index.php?page=ninja');
	}
	
	/*
	 * Retourne les comptes dispo du ninja
	 */
	public function multiGetAccounts()
	{
		return $this->filedata;
	}
	
	/*
	 * Vérifie le lien entre deux ninjas
	 */
	public function multiCheckPartner($ninja_id)
	{
		if (array_key_exists($ninja_id, $this->filedata))
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Vérifie si un joueur a déjà son compte associé
	 */
	public function multiCheckAssociate()
	{
		if ($this->multiCountAccounts() > 1)
		{
			foreach ($this->filedata as $key => $value)
			{
				if ($value['ninja_id'] != $value['ninja_account_id'])
					return TRUE;
			}
			
			return FALSE;
		}
		else
		{
			return FALSE;
		}
	}

	public function multiTransferAssociate()
	{
		$res_sql = 0;
		
		$req_compte = "UPDATE 
							wan_ninja 
						SET 
							ninja_account_id = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_account_id'])." 
						WHERE 
							ninja_id = ".ktPDO::get()->quote($_SESSION['associer']['id'])." 
						LIMIT 
							1";
		
		$res_sql += ktPDO::get()->exec($req_compte);
		
		$req_ninja = "UPDATE 
							wan_stats 
						SET	
							stats_koban = stats_koban - 1 
						WHERE 
							stats_id = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." 
						LIMIT 
							1";
					
		$res_sql += ktPDO::get()->exec($req_ninja);
		
		if ($_SESSION['associer']['village'] != $this->_ninja->ninja['stats_village'])
		{
			$req_village = "UPDATE 
								wan_stats 
							SET 
								stats_village = ".$this->_ninja->ninja['stats_village']." 
							WHERE 
								stats_id = ".ktPDO::get()->quote($_SESSION['associer']['id'])." 
							LIMIT 
								1";
			
			$res_sql += ktPDO::get()->exec($req_village);
		}

		if ($res_sql > 0)
		{
			$log_data['ninja_id'] = $this->_ninja->ninja['ninja_id'];
			$log_data['ninja_login'] = $this->_ninja->ninja['ninja_login'];
			$log_data['associate_id'] = $_SESSION['associer']['id'];
			$log_data['associate_login'] = $_SESSION['associer']['login'];
			$log_data['for'] = 'parent';

			wanLog::log(array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data));

			$log_data['for'] = 'children';

			wanLog::log(array('ninja' => $_SESSION['associer']['id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data));
			
			wanNotify::notify($_SESSION['associer']['id'], __CLASS__, self::multiTransferAssociateNotificationParse());

			$this->multiFileCreate();
			@unlink('cache/multi/multi_'.$_SESSION['associer']['id'].'.html');
			unset($_SESSION['associer']);
			
			return TRUE;
		}
		else
		{
			unset($_SESSION['associer']);
			return FALSE;
		}
	}

	public function multiCreateAssociate()
	{
		$id_ninja = wanEngine::generateId(16);
		
		$req_ninja = "INSERT INTO 
							wan_ninja 
						SET 
							ninja_account_id = '".$this->_ninja->ninja['ninja_account_id']."', 
							ninja_id = '".$id_ninja."', 
							ninja_login = ".ktPDO::get()->quote($_SESSION['creer']['login']).", 
							ninja_mail = ".ktPDO::get()->quote($_SESSION['creer']['mail']).",  
							ninja_password = '".wanEngine::myHash($_SESSION['creer']['pass'])."', 
							ninja_date_inscription = NOW()";
		
		$req_stats = "INSERT INTO 
							wan_stats 
						SET 
							stats_id = '".$id_ninja."', 
							stats_taille = ".$_SESSION['creer']['taille'].", 
							stats_masse = ".$_SESSION['creer']['masse'].", 
							stats_village = ".$this->_ninja->ninja['stats_village'].", 
							stats_sexe = ".ktPDO::get()->quote($_SESSION['creer']['sexe'])."";
		
		$res_sql = 0;
		$res_sql += ktPDO::get()->exec($req_ninja);
		$res_sql += ktPDO::get()->exec($req_stats);
		
		if ($res_sql == 2)
		{
			$log_data['ninja_id'] = $this->_ninja->ninja['ninja_id'];
			$log_data['ninja_login'] = $this->_ninja->ninja['ninja_login'];
			$log_data['created_id'] = $id_ninja;
			$log_data['created_login'] = $_SESSION['creer']['login'];
			$log_data['for'] = 'parent';

			wanLog::log(array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data));

			$log_data['for'] = 'children';

			wanLog::log(array('ninja' => $id_ninja, 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data));

			$this->multiFileCreate();
			unset($_SESSION['creer']);

			return TRUE;
		}
		else
		{
			unset($_SESSION['creer']);

			return FALSE;
		}
	}

	public static function multiCreateAssociateLogParse($data)
	{
		switch ($data['for'])
		{
			case 'parent' :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> crée le multi-compte 
						<a href="index.php?page=profil&id='.$data['created_id'].'">'.$data['created_login'].'</a>';
			break;

			case 'children' :
				return '<a href="index.php?page=profil&id='.$data['created_id'].'">'.$data['created_login'].'</a> est inscrit depuis le compte  
						<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a>';
			break;
		}
	}

	public static function multiTransferAssociateLogParse($data)
	{
		switch ($data['for'])
		{
			case 'parent' :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> transfert le compte 
						<a href="index.php?page=profil&id='.$data['associate_id'].'">'.$data['associate_login'].'</a>';
			break;

			case 'children' :
				return '<a href="index.php?page=profil&id='.$data['associate_id'].'">'.$data['associate_login'].'</a> est associé au compte 
						<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a>';
			break;
		}
	}

	public static function multiTransferAssociateNotificationParse($data)
	{
		return 'Ton compte a été associé au compte <a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a>';
	}
}

?>