<?php

class wanClan
{
	public $_ninja;
	
	public $advert, $redirect;
	
	public $clan_id;
	public $clan_infos;
	
	/*
	 * Constructeur de la classe
	 */
	public function __construct($_ninja)
	{
		if ($_ninja instanceof wanNinja)
		{
			$this->_ninja = $_ninja;
		}
	}
	
	/*
	 * Définit un clan
	 */
	public function setClan($id = "")
	{
		if (!empty($id))
		{
			$this->clan_id = substr($id, 0, 18);
		}
		else
		{
			$this->clan_id = $this->_ninja->ninja['stats_clan'];
		}
	}

	/*
	 * Redimmenssionner image clan
	 */
	private function clanImageResize($fichier)
	{
		$longueur = 600;
		$largeur = 150;
		$taille = getimagesize($fichier);

		if ($taille)
		{
			if ($taille['mime'] == 'image/jpeg')
			{
				$img_big = imagecreatefromjpeg($fichier); 
				$img_new = imagecreate($longueur, $largeur);
				$img_petite = imagecreatetruecolor($longueur, $largeur) or $img_petite = imagecreate($longueur, $largeur);
				imagecopyresized($img_petite, $img_big, 0, 0, 0, 0, $longueur, $largeur, $taille[0], $taille[1]);
				imagejpeg($img_petite, $fichier);
			}


			else if ($taille['mime'] == 'image/png')
			{
				$img_big = imagecreatefrompng($fichier);
				$img_new = imagecreate($longueur, $largeur);
				$img_petite = imagecreatetruecolor($longueur, $largeur) OR $img_petite = imagecreate($longueur, $largeur);
				imagecopyresized($img_petite, $img_big, 0, 0, 0, 0, $longueur, $largeur, $taille[0], $taille[1]);
				imagepng($img_petite, $fichier);
			}

			else if ($taille['mime'] == 'image/gif')
			{
				$img_big = imagecreatefromgif($fichier); 
				$img_new = imagecreate($longueur, $largeur);
				$img_petite = imagecreatetruecolor($longueur, $largeur) or $img_petite = imagecreate($longueur, $largeur);
				imagecopyresized($img_petite, $img_big, 0, 0, 0, 0,$longueur, $largeur, $taille[0], $taille[1]);
				imagegif($img_petite, $fichier);
			}
		}
	}

	/*
	 *	Informations sur l'extension de l'image du clan
	 */
	private function clanImageExtension($nom)
	{
		$nom = explode(".", $nom);
		$nb = count($nom);
		return strtolower($nom[$nb-1]);
	}

	/*
	 *	Contrôle l'image du clan
	 */
	private function clanImageCheck()
	{
		if (!empty($_FILES['clan_image']) AND !empty($_POST['MAX_FILE_SIZE']))
		{
			$extensionsOk = array('jpg', 'gif', 'png');
			$typeImagesOk = array(1, 2, 3);
			$destinationFold = 'uploads/clan/';

			$tailleKo = 200;
			$tailleMax = $tailleKo*1024;

			if ($_FILES['clan_image']['error'] !== "0")
			{			
				switch ($_FILES['clan_image']['error'])
				{
					case 1:
						$erreurs[] = "Ton image doit faire moins de $tailleKo Ko";
					break;
					
					case 2:
						$erreurs[] = "Ton image doit faire moins de $tailleKo Ko";
					break;
					
					case 3:
						$erreurs[] = "L'image n'a pas pu être chargée";
					break;
					
					case 4:
						$erreurs[] = "Erreur d'upload de l'avatar";
					break;
					
					case 6:
						$erreur[] = "Impossible de stocker l'image";
					break;
					
					case 7:
						$erreurs[] = "Impossible de sauvegarder l'image";
					break;
				}
			}

			if (!@$getimagesize = getimagesize($_FILES['clan_image']['tmp_name']))
			{
				$erreurs[] = "Le fichier n'est pas une image valide";
			}

			if( (!in_array($this->clanImageExtension($_FILES['clan_image']['name']), $extensionsOk )) OR (!in_array($getimagesize[2], $typeImagesOk )))
			{
					$extensions_string = '';
					
					foreach ($extensionsOk as $text)
					{
						$extensions_string .= $text.', ';
					}
					
					$erreurs[] = 'Choissis un fichier de type '.substr($extensions_string, 0, -2).' !';
			}

			if (file_exists($_FILES['clan_image']['tmp_name']) AND filesize($_FILES['clan_image']['tmp_name']) > $tailleMax)
			{
				$erreurs[] = "Ton fichier doit faire moins de $tailleKo Ko";
			}

			if (!isset($erreurs) OR empty($erreurs))
			{
				$destinationFile = basename($_FILES['clan_image']['name']);
				$destinationFile = $this->clan_id.'.'.$this->clanImageExtension($_FILES['clan_image']['name']);
				
				if (move_uploaded_file($_FILES['clan_image']['tmp_name'], $destinationFold . $destinationFile))
				{
					if ($this->clan_infos['clan_image'] != 'default.jpg')
					{
						@unlink($destinationFold.$this->clan_infos['clan_image']);
					}
					$this->clanImageResize($destinationFold.$destinationFile);
					return $destinationFile;
				}
				else
				{
					$erreurs[] = "Impossible d'uploader le fichier";
				}
			}

			return $erreurs;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Récupére la liste des clans d'un village
	 */
	public function clanGetListe($village = FALSE)
	{
		$req = "SELECT 
					wan_clan.*, 
					rang_id, rang_nom, 
					stats_id, stats_village 
				FROM 
					wan_clan 
					LEFT JOIN 
						wan_rangs ON wan_rangs.rang_id = wan_clan.clan_rang 
					LEFT JOIN 
						wan_stats ON wan_stats.stats_id = wan_clan.clan_chef ";
						
		if (!$village)
		{
			$req .= "WHERE 
						(stats_village = ".$this->_ninja->ninja['stats_village'].") OR 
						(stats_village != ".$this->_ninja->ninja['stats_village']." AND clan_multi = '1')";
		}
		
		$req .= "ORDER BY 
					clan_nom ASC";
					
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			return FALSE;
		}
		else
		{
			$return = array();
			
			while ($ret = $res->fetch(PDO::FETCH_ASSOC))
				$return[] = $ret;
				
			$res->closeCursor();
				
			return $return;
		}
	}

	/*
	 * Retourne les logs d'un clan
	 */
	public function clanGetLogs($limit = 25)
	{
		$request = "SELECT 
						* 
					FROM 
						wan_clan_logs 
						LEFT JOIN 
							wan_logs 
							ON wan_logs.log_id = wan_clan_logs.clan_log_id 
					WHERE 
						wan_clan_logs.clan_id = ".ktPDO::get()->quote($this->clan_id)." 
					ORDER BY 
						wan_logs.log_time DESC 
					LIMIT 
						".$limit."";

		$result = ktPDO::get()->query($request);

		if ($result !== FALSE AND $result->rowCount() > 0)
		{
			$logs = array();

			while ($return = $result->fetch(PDO::FETCH_ASSOC))
			{
				$logs[] = $return;
			}

			$result->closeCursor();

			return $logs;
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 *	Enregistre un log pour le clan
	 */
	public function clanSaveLog($id)
	{
		$request = "INSERT DELAYED  
						wan_clan_logs (clan_log_id, clan_id)
					VALUES 
						(
							".ktPDO::get()->quote($id).",
							".ktPDO::get()->quote($this->clan_id)."
						)";
		
		$result = ktPDO::get()->exec($request);

		return;
	}
	
	/*
	 * Récupere les infos d'un clan
	 */
	public function clanGetOnce($id = "")
	{
		if (empty($id))
		{
			$this->advert = "Tu n'as pas sélectionné de clan";
			$this->redirect = "index.php?page=centre&mode=clan";
			return FALSE;
		}
		else
		{
			$this->clan_id = $id;
			
			$req = "SELECT 
						wan_clan.*, 
						rang_id, rang_nom, 
						stats_id, stats_niveau, stats_village, 
						ninja_id, ninja_login 
					FROM 
						wan_clan 
						LEFT JOIN 
							wan_ninja ON ninja_id = clan_chef 
						LEFT JOIN 
							wan_stats ON stats_id = clan_chef 
						LEFT JOIN 
							wan_rangs ON rang_id = clan_rang 
					WHERE 
						clan_id = ".ktPDO::get()->quote($this->clan_id)."
					LIMIT 
						1";
			
			$res = ktPDO::get()->query($req);
			
			if (!$res OR $res->rowCount() == 0)
			{
				$this->advert = "Ce clan n'existe pas";
				$this->redirect = 'index.php?page=centre&mode=clan';
				return FALSE;
			}
			else
			{
				$return = $res->fetch(PDO::FETCH_ASSOC);
				
				if (($return['stats_village'] != $this->_ninja->ninja['stats_village']) 
					AND $return['clan_multi'] == '0')
				{
					$this->advert = "Le clan des ".$return['clan_nom']." ne fait pas parti de ton village";
					$this->redirect = 'index.php?page=centre&mode=clan';
					return FALSE;
				}
				else
				{
					$this->clan_infos = $return;
					return $return;
				}
			}
		}
	}
	
	/*
	 * Récupere les membres d'un clan
	 */
	public function clanGetMembres($id = "", $lite = FALSE)
	{
		if (empty($id) AND empty($this->clan_id))
		{
			$this->advert = "Aucun clan sélectionné";
			$this->redirect = 'index.php?page=centre&mode=clan';
			return FALSE;
		}
		
		!empty($id) ? $this->clan_id = $id : false;
		
		if (!$lite)
		{
			$req = "SELECT 
						wan_ninja.ninja_id, wan_ninja.ninja_login, 
						wan_stats.stats_id, wan_stats.stats_niveau, wan_stats.stats_rang, wan_stats.stats_clan, 
						wan_rangs.rang_id, wan_rangs.rang_nom, 
						wan_clan_roles.role_name, wan_clan_roles.role_id, wan_clan_roles.role_permissions, 
						wan_clan_members.member_ninja_id, wan_clan_members.member_role_id 
					FROM 
						wan_stats 
						LEFT JOIN 
							wan_ninja 
							ON 
								wan_ninja.ninja_id = wan_stats.stats_id 
						LEFT JOIN 
							wan_rangs 
							ON 
								wan_rangs.rang_id = wan_stats.stats_rang 
						LEFT JOIN 
							wan_clan_members 
							ON 
								wan_clan_members.member_ninja_id = wan_stats.stats_id 
						LEFT JOIN 
							wan_clan_roles 
							ON 
								wan_clan_roles.role_id = wan_clan_members.member_role_id
					WHERE 
						wan_stats.stats_clan = ".ktPDO::get()->quote($this->clan_id)." 
					ORDER BY 
						wan_stats.stats_niveau DESC, 
						wan_stats.stats_rang DESC";
		}
		else
		{
			$req = "SELECT 
						stats_id, stats_clan 
					FROM 
						wan_stats 
					WHERE 
						stats_clan = ".ktPDO::get()->quote($this->clan_id)."";
		}
		
					
		$res = ktPDO::get()->query($req);
		
		if (!$res OR $res->rowCount() == 0)
		{
			return FALSE;
		}
		else
		{
			$return = array();
			
			while ($ret = $res->fetch(PDO::FETCH_ASSOC))
			{
				if (!$lite)
				{
					if ($ret['member_role_id'] == 0)
					{
						if ($ret['stats_id'] == $this->clan_infos['clan_chef'])
						{
							$ret['role_name'] = 'Chef des '.$this->clan_infos['clan_nom'];
						}
						else
						{
							$ret['role_name'] = '';
						}
					}
				}

				$return[$ret['stats_id']] = $ret;
			}
				
			$res->closeCursor();
			
			return $return;
		}
	}

	/*
	 * Contruit la liste des membres d'un clan
	 */
	public function clanConstructMemberlist()
	{
		$request = "SELECT 
						wan_stats.stats_id, wan_stats.stats_clan, 
						wan_ninja.ninja_login 
					FROM 
						wan_stats 
						LEFT JOIN 
							wan_ninja 
							ON 
								wan_ninja.ninja_id = wan_stats.stats_id 
					WHERE 
						wan_stats.stats_clan = ".ktPDO::get()->quote($this->clan_id)."";

		$result = ktPDO::get()->query($request);

		if ($result != FALSE AND $result->rowCount() > 0)
		{
			$membres = array();

			while ($return = $result->fetch(PDO::FETCH_ASSOC))
			{
				if (!empty($return['ninja_login']))
				{
					$membres[] = $return;
				}
			}

			foreach ($membres as $key => $value)
			{
				$this->_clanAddMember($value['stats_id']);
			}

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 *	Récupère un membre du clan
	 */
	public function clanGetMember($ninja_id)
	{
		$request = "SELECT 
						wan_clan_members.*, 
						wan_ninja.ninja_login, 
						wan_ninja.ninja_id, 
						wan_clan_roles.role_id, 
						wan_clan_roles.role_permissions 
					FROM 
						wan_clan_members 
						LEFT JOIN 
							wan_ninja 
							ON 
								wan_ninja.ninja_id = wan_clan_members.member_ninja_id 
						LEFT JOIN 
							wan_clan_roles 
							ON 
								wan_clan_roles.role_id = wan_clan_members.member_role_id 

					WHERE 
						wan_clan_members.member_ninja_id = ".ktPDO::get()->quote($ninja_id)." 
					LIMIT 
						1";

		$result = ktPDO::get()->query($request);

		if ($result != FALSE AND $result->rowCount() == 1)
		{
			$ninja = $result->fetch(PDO::FETCH_ASSOC);
			
			if ($ninja['member_role_id'] == 0)
			{
				$possible_permissions = $this->clanPossibleRolePermissions();

				if ($ninja['ninja_id'] == $this->clan_infos['clan_chef'])
				{
					foreach ($possible_permissions as $key => $value)
					{
						$ninja['role_name'] = 'Chef des '.$this->clan_infos['clan_nom'];
						$ninja['role_permissions'][$key] = TRUE;
					}
				}
				else
				{
					foreach ($possible_permissions as $key => $value)
					{
						$ninja['role_name'] = '';
						$ninja['role_permissions'][$key] = FALSE;
					}
				}
			}

			return $ninja;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Vérifie si un ninja peut rejoindre le clan
	 */
	public function clanCheckJoining()
	{
		if (!empty($this->clan_id))
		{
			$this->clan_infos = $this->clanGetOnce($this->clan_id);			

			if (!$this->clan_infos)
			{
				$this->advert = "Ce clan n'existe pas";
				$this->redirect = 'index.php?page=centre&mode=clan';
			}
			else
			{
				$this->redirect = 'index.php?page=centre&mode=clan';
				
				if ($this->_ninja->ninja['stats_clan'] != '0')
				{
					$this->advert = "Tu fais déjà parti d'un clan";
				}	
				else if ($this->clan_infos['clan_statut'] == '1')
				{
					$this->advert = "Les inscriptions à ce clan sont closes";
				}
				else if ($this->_ninja->ninja['stats_niveau'] < $this->clan_infos['clan_niveau'])
				{
					$this->advert = "Tu dois être au moins au niveau ".$this->clan_infos['clan_niveau']." pour rejoindre ce clan";
				}	
				else if ($this->_ninja->ninja['stats_rang'] < $this->clan_infos['clan_rang'])
				{
					$this->advert = "Tu dois être au moins ".$this->clan_infos['rang_nom']." pour rejoindre ce clan";
				}	
				else if (!$this->_ninja->ninjaCheckRyos($this->clan_infos['clan_frais']))
				{
					$this->advert = "Tu n'as pas assez de ryôs pour rejoindre ce clan";
				}
				else
				{
					$this->clanProcessJoining();
				}
			}
		}
		else
		{
			$this->advert = "Tu n'as pas sélectionné de clan";
			$this->redirect = 'index.php?page=centre&mode=clan';
		}
	}
	
	/*
	 * Effectue l'adhésion au clan
	 */
	private function clanProcessJoining()
	{
		$req1 = "UPDATE 
					wan_stats 
				SET 
					stats_ryos = stats_ryos - ".$this->clan_infos['clan_frais'].", 
					stats_clan = ".ktPDO::get()->quote($this->clan_id)." 
				WHERE 
					stats_id = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])." 
				LIMIT 
					1";
					
		$req2 = "UPDATE 
					wan_clan 
				SET 
					clan_caisse = clan_caisse + ".$this->clan_infos['clan_frais']." 
				WHERE 
					clan_id = ".ktPDO::get()->quote($this->clan_infos['clan_id'])." 
				LIMIT 
					1";

		$res = ktPDO::get()->exec($req1);
		$res += ktPDO::get()->exec($req2);
		
		if ($res == 0)
		{
			$this->advert = "Impossible de rejoindre les ".$this->clan_infos['clan_nom']." pour le moment";
			$this->redirect = 'index.php?page=centre&mode=clan';
		}
		else
		{
			$membres = $this->clanGetMembres($this->clan_id, TRUE);

			$data_notification = array();
			$notification = self::clanProcessJoiningNotificationParse($this->_ninja->ninja);

			foreach ($membres as $key => $value)
			{
				$data_notification[] = array('ninja' => $value['stats_id'], 'type' => __CLASS__, 'content' => $notification);
			}

			wanNotify::notifyBatch($data_notification);

			$log_data = array('clan_id' => $this->clan_id, 
							'clan_name' => $this->clan_infos['clan_nom'], 
							'ninja_id' => $this->_ninja->ninja['ninja_id'],
							'ninja_login' => $this->_ninja->ninja['ninja_login']);

			$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 
						'ninja' => $this->_ninja->ninja['stats_id'], 'data' => $log_data);

			wanLog::log($log, $this);

			$this->_clanAddMember($this->_ninja->ninja['ninja_id'], $this->clan_infos['clan_role']);
			
			$this->advert = "Bienvenue chez les ".$this->clan_infos['clan_nom'];
			$this->redirect = 'index.php?page=clan';
		}
	}
	
	/*
	 * Retourne les grades disponibles pour un clan
	 */
	public function clanPossibleGrades()
	{
		$array_grades = array(0 => 'Aucun', 
							  1 => 'Genin', 
							  2 => 'Chuunin', 
							  3 => 'Jounin', 
							  4 => 'Tokubetsu Jounin',
							  5 => 'Anbu', 
							  7 => 'Sennin');
		
		return $array_grades;
	}
	
	/*
	 * Vérifie si le ninja est le chef du clan
	 */
	public function clanCheckChief()
	{
		return $this->_ninja->ninja['stats_id'] == $this->_ninja->ninja['clan_chef'];
	}
	
	/*
	 * Récupere les infos du clan
	 */
	public function clanGetInfos()
	{
		if (!empty($this->clan_id))
		{
			return $this->clanGetOnce($this->clan_id);
		}
		else
		{
			$this->advert = "Ce clan n'existe pas";
			$this->redirect = 'index.php?page=centre&mode=clan';
			return FALSE;
		}
	}
	
	/*
	 * Vérifie les informations postées pour l'édition des infos du clan
	 */
	private function clanCheckEdit($data)
	{
		$clan_frais = (int) htmlentities($data['clan_frais']);
		$clan_rang = (int) htmlentities($data['clan_rang']);
		$clan_niveau = (int) htmlentities($data['clan_niveau']);
		$clan_statut = (int) htmlentities($data['clan_statut']);
		$clan_role = (int) htmlentities($data['clan_role']);
		$clan_multi = (int) htmlentities($data['clan_multi']);
		$clan_description = $data['clan_description'];
		
		$this->advert = 'Oups !<br />';
		$err = 0;
		
		if ($clan_frais == 0)
		{
			$err++;
			$this->advert .= "- les frais de clan sont obligatoires<br />";
		}
			
		if ($clan_niveau < 1 OR $clan_niveau > 200)
		{
			$err++;
			$this->advert .= "- le champs niveau est incorrect<br />";
		}
		
		if ($clan_niveau > $this->_ninja->ninja['stats_niveau'])
		{
			$err++;
			$this->advert .= "- tu ne peux pas indiquer un niveau supérieur au tiens<br />";
		}
			
		if ($clan_rang > $this->_ninja->ninja['stats_rang'])
		{
			$err++;
			$this->advert .= "- le rang demandé est supérieur au tiens<br />";
		}
			
		if (!array_key_exists($clan_rang, $this->clanPossibleGrades()))
		{
			$err++;
			$this->advert .= "- le rang demandé n'existe pas<br />";
		}
		
		if ($clan_statut > 1)
		{
			$err++;
			$this->advert .= "- statut des adhésions erroné<br />";
		}

		if ($clan_multi > 1)
		{
			$err++;
			$this->advert .= "- information clan multi-village erronée<br />";
		}

		$roles = $this->clanGetRoles();
		
		if (!array_key_exists($clan_role, $roles) AND $clan_role != 0)
		{
			$err++;
			$this->advert .= "- le rôle par défaut n'existe pas<br />";
		}

		if (!empty($_FILES['clan_image']['name']))
		{
			$image = $this->clanImageCheck();

			if (is_array($image) AND $image != FALSE)
			{
				$err++;

				foreach ($image as $key => $value)
				{
					$this->advert .= "- ".$value."<br />";
				}
			}
		}
		else
		{
			$image = $this->clan_infos['clan_image'];
		}

		if ($err == 0)
		{
			$req_editer = "UPDATE 
								wan_clan 
							SET 
								clan_frais = ".ktPDO::get()->quote($clan_frais).", 
								clan_niveau = ".ktPDO::get()->quote($clan_niveau).", 
								clan_rang = ".ktPDO::get()->quote($clan_rang).", 
								clan_statut = ".ktPDO::get()->quote($clan_statut).", 
								clan_multi = ".ktPDO::get()->quote($clan_multi).", 
								clan_role = ".ktPDO::get()->quote($clan_role).",
								clan_description = ".ktPDO::get()->quote($clan_description).", 
								clan_image = ".ktPDO::get()->quote($image)." 
							WHERE 
								clan_id = ".ktPDO::get()->quote($this->clan_id)." 
							LIMIT 
								1";

			$res_editer = ktPDO::get()->exec($req_editer);
			
			if ($res_editer != 1)
			{	
				$this->advert = "Aucun changement n'a été apporté";
				$this->redirect = 'index.php?page=clan&mode=options';
				return FALSE;
			}
			else
			{
				$this->advert = "Clan édité";
				$this->redirect = 'index.php?page=clan&mode=options';
				return TRUE;
			}
		}
		else
		{
			$this->redirect = 'index.php?page=clan&mode=options';
			return FALSE;
		}
	}
	
	/*
	 * Edite les infos du clan
	 */
	public function clanEdit($params = '')
	{
		if (!empty($params) AND is_array($params))
		{
			if ($this->clanCheckEdit($params))
			{
				$data_edit = array('frais' => $params['clan_frais'],
									'niveau' => $params['clan_niveau'],
									'multi' => $params['clan_multi'], 
									'statut' => $params['clan_statut'], 
									'rang' => $params['clan_rang']);

				$log_data = array('clan_id' => $this->clan_id, 
								'clan_name' => $this->clan_infos['clan_nom'], 
								'ninja_id' => $this->_ninja->ninja['ninja_id'],
								'ninja_login' => $this->_ninja->ninja['ninja_login'],
								'clan_edit_infos' => $data_edit);

				$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 
							'ninja' => $this->_ninja->ninja['stats_id'], 'data' => $log_data);

				wanLog::log($log, $this);
				
				return TRUE;
			}
			else
			{	
				return FALSE;
			}
		}
		else
		{
			$this->advert = "Formulaire incomplet";
			$this->redirect = 'index.php?page=clan&mode=options';
			return FALSE;
		}
	}

	/*
	 * Récupère les rôles d'un clan
	 */
	public function clanGetRoles()
	{
		$request = "SELECT 
						* 
					FROM 
						wan_clan_roles 
					WHERE 
						role_clan_id = ".ktPDO::get()->quote($this->clan_id)." 
					ORDER BY 
						role_id DESC";

		$result = ktPDO::get()->query($request);

		if ($result != FALSE AND $result->rowCount() > 0)
		{
			$roles = array();

			while ($return = $result->fetch(PDO::FETCH_ASSOC))
			{
				$return['role_permissions'] = unserialize($return['role_permissions']);
				$roles[$return['role_id']] = $return;
			}

			$result->closeCursor();
			return $roles;
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 * Vérifie la possibilité de changement de rôle d'un ninja
	 */
	public function clanCheckAssignRole($ninja_id)
	{
		$this->redirect = "index.php?page=clan&mode=membres";

		if ($ninja_id == $this->_ninja->ninja['ninja_id'])
		{
			$this->advert = "Tu ne peux pas changer ton propre rôle";
			return FALSE;
		}

		$ninja = $this->clanGetMember($ninja_id);

		if ($ninja != FALSE)
		{
			if ($ninja['role_permission']['exclude_protected'] == TRUE 
				AND !$this->clanCheckChief())
			{
				$this->advert = "Tu ne peux pas changer le rôle de ce ninja";
				return FALSE;
			}
			if ($ninja['ninja_id'] == $this->_ninja->ninja['clan_chef'])
			{
				$this->advert = "Tu ne peux pas changer le rôle du chef";
				return FALSE;
			}

			return $ninja;
		}
		else
		{
			$this->advert = "Tu ne peux pas changer le rôle de ce Ninja";
			return FALSE;
		}
	}

	/*
	 *	Change le rôle d'un ninja du clan
	 */
	public function clanAssignRole($ninja_id, $role)
	{
		$this->redirect = "index.php?page=clan&mode=membres";
		$ninja = $this->clanCheckAssignRole($ninja_id);

		if ($ninja === FALSE)
		{
			return FALSE;
		}

		$roles = $this->clanGetRoles();

		if (!array_key_exists($role, $roles) AND $role != 0)
		{
			$this->advert = "Le rôle demandé n'existe pas";
			return FALSE;
		}

		if ($ninja['member_role_id'] == $role)
		{
			$this->advert = "Tu ne peux pas assigner le ninja au même rôle qu'il ne l'était déjà !";
			return FALSE;
		}

		$request = "UPDATE 
						wan_clan_members 
					SET 
						member_role_id = ".ktPDO::get()->quote($role)." 
					WHERE 
						member_ninja_id = ".ktPDO::get()->quote($ninja['ninja_id'])." 
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		if ($result == 1)
		{
			$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 
							  'ninja_login' => $this->_ninja->ninja['ninja_login'],
							  'receiver_id' => $ninja['ninja_id'], 
							  'receiver_login' => $ninja['ninja_login'], 
							  'role_id' => $role, 
							  'role_name' => $roles[$role]['role_name'], 
							  'clan_id' => $this->clan_id, 
							  'clan_name' => $this->clan_infos['clan_nom'],
							  'mode' => 'sender');

			$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

			wanLog::log($log, $this);

			$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 
							  'ninja_login' => $this->_ninja->ninja['ninja_login'],
							  'receiver_id' => $ninja['ninja_id'], 
							  'receiver_login' => $ninja['ninja_login'], 
							  'role_id' => $role, 
							  'role_name' => $roles[$role]['role_name'], 
							  'clan_id' => $this->clan_id, 
							  'clan_name' => $this->clan_infos['clan_nom'],
							  'mode' => 'receiver');

			$log = array('ninja' => $ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

			wanLog::log($log);

			$notification_data = array('role_name' => $roles[$role]['role_name'], 
									   'role_id' => $role,
									   'clan_id' => $this->clan_id, 
									   'clan_name' => $this->clan_infos['clan_nom']);

			wanNotify::notify($ninja['ninja_id'], __CLASS__, self::clanAssignRoleNotificationParse($notification_data));

			$this->advert = "Le rôle de ".$ninja['ninja_login']." est maintenant \"".$roles[$role]['role_name']."\"";
			return TRUE;
		}
		else
		{
			$this->advert = "Impossible d'assigner le rôle de ce ninja pour le moment";
			return FALSE;
		}
	}

	/*
	 *	Ajoute un ninja à la liste des ninjas du clan
	 */
	private function _clanAddMember($ninja_id = '', $role_id = 0)
	{
		$ninja_id = empty($ninja_id) ? $this->_ninja->ninja['ninja_id'] : $ninja_id;

		$request = "INSERT DELAYED 
						wan_clan_members (member_ninja_id, member_clan_id, member_role_id, member_date) 
					VALUES 
						(".ktPDO::get()->quote($ninja_id).",
						 ".ktPDO::get()->quote($this->clan_id).",
						 ".ktPDO::get()->quote($role_id).",
						 ".ktPDO::get()->quote(time()).")";

		$result = ktPDO::get()->exec($request);

		return $result == 1 ? TRUE : FALSE;
	}

	/*
	 *	Vérifie qu'un ninja fait parti du clan
	 */
	private function _clanNinjaExists($ninja_id = '')
	{
		if (empty($ninja_id))
		{
			$this->advert = "Tu n'as pas sélectionné de ninja !";
			return FALSE;
		}
		else
		{
			$membres = $this->clanGetMembres($this->clan_id, TRUE);

			if (array_key_exists($ninja_id, $membres))
			{
				return TRUE;
			}
			else
			{
				$this->advert = "Le ninja sélectionné ne fait pas parti du clan";
				return FALSE;
			}
		}
	}

	/*
	 * Supprime un ninja de la liste des ninjas du clan
	 */
	private function _clanRemoveMember($ninja_id = '')
	{
		$ninja_id = empty($ninja_id) ? $this->_ninja->ninja['ninja_id'] : $ninja_id;

		$request_clan = "DELETE LOW_PRIORITY FROM wan_clan_members WHERE member_ninja_id = ".ktPDO::get()->quote($ninja_id)." LIMIT 1";

		$result_clan = ktPDO::get()->exec($request_clan);

		$request_stats = "UPDATE wan_stats SET stats_clan = '0' WHERE stats_id = ".ktPDO::get()->quote($ninja_id)." LIMIT 1";

		$result_stats = ktPDO::get()->exec($request_stats);

		return $result_clan == 1 ? TRUE : FALSE;
	}

	/*
	 * Nettoie la table des membres d'un clan
	 */
	private function _clanCleanMembers()
	{
		$request = "DELETE LOW_PRIORITY FROM wan_clan_members WHERE member_clan_id = ".ktPDO::get()->quote($this->clan_id)."";

		$result = ktPDO::get()->exec($request);

		return $result == 1 ? TRUE : FALSE;
	}

	/*
	 * Exclu un ninja du clan
	 */
	public function clanExcludeMember($ninja_id = '')
	{
		$this->redirect = "index.php?page=clan&mode=membres";

		if (!empty($ninja_id))
		{
			$ninja = $this->clanGetMember($ninja_id);

			if (!$this->ninjaHasPermission('member_exclude'))
			{
				return FALSE;
			}
			
			$this->redirect = "index.php?page=clan&mode=membres";

			if ($ninja['member_clan_id'] != $this->clan_id)
			{
				$this->advert = "Tu ne peux pas exclure un ninja qui ne fait pas parti du clan !";
				return FALSE;
			}
			if ($ninja['ninja_id'] == $this->_ninja->ninja['ninja_id'])
			{
				$this->advert = "Tu ne peux pas t'exclure toi-même du clan";
				return FALSE;
			}
			if ($ninja['ninja_id'] == $this->clan_infos['clan_chef'])
			{
				$this->advert = "Tu ne peux pas exclure le chef du clan";
				return FALSE;
			}
			if ($ninja['role_permissions']['exclude_protected'] == TRUE AND !$this->clanCheckChief())
			{
				$this->advert = "Ce ninja est protégé de l'exclusion";
				return FALSE;
			}

			$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
							  'ninja_login' => $this->_ninja->ninja['ninja_login'],
							  'clan_id' => $this->clan_id,
							  'clan_name' => $this->clan_infos['clan_nom'],
							  'receiver_id' => $ninja['ninja_id'],
							  'receiver_login' => $ninja['ninja_login'],
							  'mode' => 'sender');

			$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

			wanLog::log($log, $this);

			$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
							  'ninja_login' => $this->_ninja->ninja['ninja_login'],
							  'clan_id' => $this->clan_id,
							  'clan_name' => $this->clan_infos['clan_nom'],
							  'receiver_id' => $ninja['ninja_id'],
							  'receiver_login' => $ninja['ninja_login'],
							  'mode' => 'receiver');

			$log = array('ninja' => $ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

			wanLog::log($log);

			$notification_data = array('clan_id' => $this->clan_id, 
									   'clan_name' => $this->clan_infos['clan_nom']);

			wanNotify::notify($ninja['ninja_id'], __FUNCTION__, self::clanExcludeMemberNotificationParse($notification_data));

			$this->_clanRemoveMember($ninja['ninja_id']);

			$this->advert = $ninja['ninja_login']." a été exclu du clan !";
			return TRUE;
		}
		else
		{
			$this->advert = "Tu n'as pas sélectionné de ninja à exclure !";
			return FALSE;
		}
	}

	/*
	 * Retourne le role d'un ninja
	 */
	public function clanNinjaGetRole($ninja_id = '')
	{
		$request = "SELECT 
						wan_clan_members.member_role_id, 
						wan_clan_roles.role_id, 
						wan_clan_roles.role_name, 
						wan_clan_roles.role_permissions 
					FROM 
						wan_clan_members 
						LEFT JOIN 
							wan_clan_roles 
							ON 
								wan_clan_roles.role_id = wan_clan_members.member_role_id 
					WHERE 
						wan_clan_members.member_ninja_id = ".ktPDO::get()->quote($ninja_id)." 
					LIMIT 
						1";

		$result = ktPDO::get()->query($request);

		if ($result != FALSE AND $result->rowCount() == 1)
		{
			$role = $result->fetch(PDO::FETCH_ASSOC);
			$role_name = $role['role_name'];
			$role_permissions = unserialize($role['role_permissions']);
		}
		else
		{
			$role = 0;
		}

		if ($role == 0)
		{
			$possible_permissions = $this->clanPossibleRolePermissions();
			$permissions = array();

			if ($ninja_id == $this->clan_infos['clan_chef'])
			{
				foreach ($possible_permissions as $key => $value)
				{
					$permissions[$key] = TRUE;
				}

				$role_name = 'Chef des '.$this->clan_infos['clan_nom'];
			}
			else
			{
				foreach ($possible_permissions as $key => $value)
				{
					$permissions[$key] = FALSE;
				}

				$role_name = 'Aucun';
			}

			$role_permissions = $permissions;
		}

		return array('role_name' => $role_name, 'role_permissions' => $role_permissions);
	}

	/*
	 * Retourne les permissions d'un ninja
	 */
	public function ninjaGetRole()
	{
		$role = $this->clanNinjaGetRole($this->_ninja->ninja['ninja_id']);

		$this->_ninja->ninja['role_name'] = $role['role_name'];
		$this->_ninja->ninja['role_permissions'] = $role['role_permissions'];
	}

	/*
	 *	Vérifie la permission
	 */
	public function ninjaHasPermission($permission)
	{
		if ($this->clanCheckChief())
		{
			return TRUE;
		}

		$this->advert = "Tu n'as pas la permission suffisante pour effectuer cette action";
		$this->redirect = 'index.php?page=clan';

		return array_key_exists($permission, $this->_ninja->ninja['role_permissions']) ? $this->_ninja->ninja['role_permissions'][$permission] : FALSE;
	}

	/*
	 * Liste les permissions possible du rôle
	 */
	public function clanPossibleRolePermissions()
	{
		$permissions = array('bank_withdrawal' => "Retirer des ryôs sur le compte",
							 'bank_deposit' => "Déposer des ryôs sur le compte",
							 'chest_take' => "Prendre des objets du coffre",
							 'chest_deposit' => "Déposer des objets dans le coffre",
							 'options_update' => "Gèrer les informations du clan", 
							 'role_manage' => "Gérer des rôles",
							 'member_exclude' => "Exclure un membre",
							 'exclude_protected' => "Protegé de l'exclusion", 
							 'bonus_activate' => "Activer les bonus", 
							 'clan_dissolve' => "Dissoudre le clan");

		return $permissions;
	}

	/*
	 * Vérifie qu'un type de permission existe
	 */
	public function clanCheckPossibleRolePermission($role)
	{
		return array_key_exists($role, $this->clanPossibleRolePermissions());
	}

	/*
	 * Vérifie les informations de la création d'un rôle
	 */
	public function clanCheckCreateRole($params)
	{
		$role_name = stripslashes($params['role_name']);

		$this->advert = 'Oups !<br />';
		$err = 0;

		$controled_role_name = wanEngine::controlPseudo($role_name, 30);
		if ($controled_role_name != $role_name)
		{
			$err++;
			echo $controled_role_name . ' - '.$role_name;exit;
			$this->advert .= "- Le nom du rôle contient des caractères non-autorisés ou est trop long<br />";
		}
		else
		{
			$role_name = $controled_role_name;
		}

		if (strlen($role_name) < 3 OR empty($role_name))
		{
			$err++;
			$this->advert .= "- Tu dois renseigner le nom du rôle<br />";
		}

		$possible_permissions = $this->clanPossibleRolePermissions();
		$permissions = array();

		foreach ($possible_permissions as $key => $value)
		{
			$permission_key = 'role_'.$key;

			if (array_key_exists($permission_key, $params))
			{
				$permissions[$key] = $params[$permission_key] == '1' ? TRUE : FALSE;
			}
		}

		if (count($permissions) < 1)
		{
			$err++;
			$this->advert .= "- Tu dois indiquer des permissions pour ce rôle";
		}

		if ($err == 0)
		{
			$request = "INSERT INTO 
							wan_clan_roles (role_clan_id, role_name, role_permissions) 
						VALUES 
							(".ktPDO::get()->quote($this->clan_id).", 
							 ".ktPDO::get()->quote($role_name).", 
							 ".ktPDO::get()->quote(serialize($permissions)).")";

			$result = ktPDO::get()->exec($request);

			$this->_clanBankPay(10000);

			if ($result == 1)
			{
				$this->advert = "Le rôle \"".$role_name."\" a été crée !";
				$this->redirect = "index.php?page=clan&mode=hierarchie";
				return array('role_name' => $role_name, 'role_permissions' => serialize($permissions));
			}
			else
			{
				$this->advert = "Le rôle n'a pas pu être sauvegardé";
				$this->redirect = "index.php?page=clan&mode=hierarchie";
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 * Vérifie une somme de ryôs sur le compte en banque
	 */
	public function clanCheckBank($amount = 0)
	{
		$amount = abs($amount);

		if ($this->clan_infos['clan_caisse'] >= $amount)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 * Préleve de l'argent sur le compte du clan
	 */
	private function _clanBankPay($amount = 1)
	{
		$amount = abs($amount);

		$request = "UPDATE 
						wan_clan 
					SET 
						clan_caisse = clan_caisse - ".ktPDO::get()->quote($amount)." 
					WHERE 
						clan_id = ".ktPDO::get()->quote($this->clan_id)." 
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		return $result == 1;
	}

	/*
	 * Retire de l'argent de la banque du clan
	 */
	public function clanBankWithdrawal($amount = 0)
	{
		$amount = abs($amount);
		$this->redirect = "index.php?page=clan&mode=banque";

		if ($amount == 0)
		{
			$this->advert = "Tu ne peux pas retirer 0 ryôs";
			return FALSE;
		}

		if ($this->clanCheckBank($amount))
		{
			$request = "UPDATE 
							wan_clan 
						SET 
							clan_caisse = clan_caisse - ".ktPDO::get()->quote($amount)." 
						WHERE 
							clan_id = ".ktPDO::get()->quote($this->clan_id)." 
						LIMIT 
							1";

			$result = ktPDO::get()->exec($request);

			if ($result == 1)
			{
				$this->_ninja->ninjaChangeRyos('add', $amount);

				$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 
								  'ninja_login' => $this->_ninja->ninja['ninja_login'], 
								  'clan_id' => $this->clan_id,
								  'clan_name' => $this->clan_infos['clan_nom'], 
								  'amount' => $amount);

				$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

				wanLog::log($log, $this);

				$this->advert = "Le retrait a été fait avec succès !";
				return TRUE;
			}
			else
			{
				$this->advert = "Impossible de retirer la somme demandée";
				return FALSE;
			}
		}
		else
		{
			$this->advert = "Le clan n'a pas les fonds pour effectuer ce retrait";
			return FALSE;
		}
	}

	/*
	 * Dépose des ryôs sur le compte du clan
	 */
	public function clanBankDeposit($amount)
	{
		$this->redirect = "index.php?page=clan&mode=banque";
		$amount = abs($amount);

		if ($amount == 0)
		{
			$this->advert = "Tu ne peux pas déposer 0 ryôs";
			return FALSE;
		}

		if ($this->_ninja->ninjaCheckRyos($amount))
		{
			$request = "UPDATE 
							wan_clan 
						SET 
							clan_caisse = clan_caisse + ".ktPDO::get()->quote($amount)." 
						WHERE 
							clan_id = ".ktPDO::get()->quote($this->clan_id)." 
						LIMIT 
							1";

			$result = ktPDO::get()->exec($request);

			if ($result == 1)
			{
				$this->_ninja->ninjaChangeRyos('pick', $amount);

				$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 
								  'ninja_login' => $this->_ninja->ninja['ninja_login'], 
								  'clan_id' => $this->clan_id, 
								  'clan_name' => $this->clan_infos['clan_nom'], 
								  'amount' => $amount);

				$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

				wanLog::log($log, $this);

				$this->advert = $amount." ryôs ont été déposés sur le compte du clan";
				return TRUE;
			}
			else
			{
				$this->advert = "Impossible de déposer la somme demandée";
				return FALSE;
			}
		}
		else
		{
			$this->advert = "Tu n'as pas assez de ryôs pour faire un tel dépôt !";
			return FALSE;
		}
	}

	/*
	 * Créée un nouveau rôle
	 */
	public function clanCreateRole($params)
	{
		$this->redirect = 'index.php?page=clan&mode=hierarchie&action=ajouter';

		if (!empty($params) AND is_array($params))
		{
			$checked = $this->clanCheckCreateRole($params);

			if ($checked !== FALSE)
			{
				$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 
								  'ninja_login' => $this->_ninja->ninja['ninja_login'], 
								  'clan_id' => $this->clan_id,
								  'clan_name' => $this->clan_infos['clan_nom'],
								  'role_data' => $checked);

				$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

				wanLog::log($log, $this);

				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		
		$this->advert = "Formulaire incomplet";
		return FALSE;
	}
	
	/*
	 * Dissout un clan
	 */
	public function clanDissoudre()
	{
		$this->redirect = 'index.php?page=clan&mode=dissoudre';
		
		$membres = $this->clanGetMembres($this->clan_id, TRUE);
		
		if (count($membres) == 0 OR empty($membres))
		{
			$this->advert = "Le clan ne peut être dissout pour le moment";
			return FALSE;
		}
		else
		{		
			$res_membres->closeCursor();
			
			$req_clan = "DELETE 
						 FROM 
							wan_clan 
						 WHERE 
							clan_id = ".ktPDO::get()->quote($this->clan_id)." 
						 LIMIT 
							1";
			
			$req_users = "UPDATE 
							wan_stats 
						  SET 
							stats_clan = '0' 
						  WHERE 
							stats_clan = ".ktPDO::get()->quote($this->clan_id)."";
							
			$res = 0;
			$res += ktPDO::get()->exec($req_clan);
			$res += ktPDO::get()->exec($req_users);
			
			if ($res >= 2)
			{				
				$data_notification[] = array();

				$notification = self::clanDissoudreNotificationParse($this->clan_infos['clan_nom']);

				foreach ($membres as $key => $value)
				{
					$data_notification[] = array('ninja' => $value['stats_id'], 'type' => __CLASS__, 'content' => $notification);
				}

				wanNotify::notifyBatch($data_notification);
				
				$log_data = array('clan_id' => $this->clan_id, 
								'clan_name' => $this->clan_infos['clan_nom'], 
								'ninja_id' => $this->_ninja->ninja['ninja_id'],
								'ninja_login' => $this->_ninja->ninja['ninja_login']);

				$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 
							'ninja' => $this->_ninja->ninja['stats_id'], 'data' => $log_data);

				wanLog::log($log, $this);

				$this->_clanCleanMembers();
				
				$this->advert = "Le clan a été dissout";
				$this->redirect = 'index.php?page=centre&mode=clan';
				return TRUE;
			}
			else
			{	
				$this->advert = "Le clan ne peut être dissout pour le moment";
				return FALSE;
			}
		}
	}
	
	/*
	 * Supprime le ninja en cours du clan en cours
	 */
	public function clanQuitter()
	{
		$this->redirect = 'index.php?page=centre&mode=clan';
		
		$req_user = "UPDATE 
						wan_stats 
					  SET 
						stats_clan = '0' 
					  WHERE 
						stats_id = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." 
					  LIMIT 
						1";
					
		$res = ktPDO::get()->exec($req_user);
		
		if ($res == 1)
		{			
			$membres = $this->clanGetMembres($this->clan_id, TRUE);

			$notification = self::clanQuitterNotificationParse($this->_ninja->ninja);
			$data_notification = array();

			foreach ($membres as $key => $value)
			{
				$data_notification[] = array('ninja' => $value['stats_id'], 'type' => __CLASS__, 'content' => $notification);
			}

			wanNotify::notifyBatch($data_notification);
			
			$log_data = array('clan_id' => $this->clan_id, 
							'clan_name' => $this->clan_infos['clan_nom'], 
							'ninja_id' => $this->_ninja->ninja['ninja_id'],
							'ninja_login' => $this->_ninja->ninja['ninja_login']);

			$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 
						'ninja' => $this->_ninja->ninja['stats_id'], 'data' => $log_data);

			wanLog::log($log, $this);

			$this->_clanRemoveMember($this->_ninja->ninja['ninja_id']);
			
			$this->advert = "Tu ne fais plus parti des ".$this->clan_infos['clan_nom'];
			$this->redirect = 'index.php?page=centre&mode=clan';
			return TRUE;
		}
		else
		{	
			$this->advert = "Tu ne peux pas quitter le clan pour le moment";
			return FALSE;
		}
	}
	
	/*
	 * Vérifications de la création d'un clan
	 */
	private function clanCheckCreer($data)
	{
		$_POST['clan_nom'] = (string) htmlentities(trim($_POST['clan_nom']));
		$_POST['clan_nom'] = explode(' ', $_POST['clan_nom']);
		$_POST['clan_nom'] = ucfirst($_POST['clan_nom'][0]);
		$_POST['clan_frais'] = (int) htmlentities($_POST['clan_frais']);
		$_POST['clan_rang'] = (int) htmlentities($_POST['clan_rang']);
		$_POST['clan_niveau'] = (int) htmlentities($_POST['clan_niveau']);
		$_POST['clan_multi'] = (int) htmlentities($_POST['clan_multi']);
		
		$_POST['clan_nom_cleaned'] =  wanEngine::controlPseudo($_POST['clan_nom']);

		$req_clan = "SELECT 
						clan_nom 
					FROM 
						wan_clan 
					WHERE 
						clan_nom = ".ktPDO::get()->quote($_POST['clan_nom_cleaned'])." 
					LIMIT 
						1";
		
		$result_clan = ktPDO::get()->query($req_clan);
		$clan = $result_clan->fetch(PDO::FETCH_ASSOC);
		$result_clan->closeCursor();
		
		$this->advert = '';
		$this->redirect = 'index.php?page=centre&mode=clan&clan=creer';
		$err = 0;
		
		if ($this->_ninja->ninja['stats_niveau'] < $_POST['clan_niveau'] OR $_POST['clan_niveau'] > 200 OR $_POST['clan_niveau'] == 0)
		{
			$this->advert .= "- le champs 'niveau minimum' est invalide<br />";
			$err++;
		}

		if ($_POST['clan_nom_cleaned'] != $_POST['clan_nom'])
		{
			$this->advert .= "- le nom du clan contient des caractères invalides<br />";
			$err++;
		}
		
		if ($clan['clan_nom'] == $_POST['clan_nom_cleaned'])
		{
			$this->advert .= "- le nom de clan choisi existe déjà<br />";
			$err++;
		}
		
		if (!$this->_ninja->ninjaCheckRyos(100000))
		{
			$this->advert .= "- tu n'as pas assez d'argent pour payer les frais<br />";
			$err++;
		}
		
		if ($_POST['clan_frais'] == 0)
		{
			$this->advert .= "- les frais de clan sont obligatoire<br />";
			$err++;
		}
		
		if ($this->_ninja->ninja['stats_clan'] != '0')
		{
			$this->advert .= "- tu fais déjà parti d'un clan ninja<br />";
			$err++;
		}
		
		if (!array_key_exists($_POST['clan_rang'], $this->clanPossibleGrades()))
		{
			$this->advert .= "- le rang minimum choisi ne correspond pas<br />";
			$err++;
		}
		
		if ($_POST['clan_rang'] > $this->_ninja->ninja['stats_rang'])
		{
			$this->advert .= "- tu ne peux pas choisir un rang minimum supérieur au tiens<br />";
			$err++;
		}
		
		if (empty($_POST['clan_nom']))
		{
			$this->advert .= "- tu dois choisir un nom de clan<br />";
			$err++;
		}

		if ($_POST['clan_multi'] > 1)
		{
			$this->advert .= "- le champs 'multi-village' est invalide<br />";
			$err++;
		}
		
		if ($err == 0)
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Creer un clan
	 */
	public function clanCreer($params = '')
	{
		if (!empty($params) AND is_array($params))
		{
			if ($this->clanCheckCreer($params))
			{
				$this->clan_id = wanEngine::generateId(18);

				$requete_clan1 = "INSERT INTO 
									wan_clan (clan_id, clan_nom, clan_chef, clan_frais, clan_rang, clan_niveau, clan_multi)
								VALUES 
									(".ktPDO::get()->quote($this->clan_id).", 
									".ktPDO::get()->quote($_POST['clan_nom']).", 
									".ktPDO::get()->quote($this->_ninja->ninja['stats_id']).", 
									".ktPDO::get()->quote($_POST['clan_frais']).", 
									".ktPDO::get()->quote($_POST['clan_rang']).", 
									".ktPDO::get()->quote($_POST['clan_niveau']).", 
									".ktPDO::get()->quote($_POST['clan_multi']).")";

				$requete_clan2 = "UPDATE 
									wan_stats 
								SET 
									stats_ryos = stats_ryos - 100000, 
									stats_clan = ".ktPDO::get()->quote($this->clan_id)." 
								WHERE 
									stats_id = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])."";
									
				$res = 0;
				$res += ktPDO::get()->exec($requete_clan1);
				$res += ktPDO::get()->exec($requete_clan2);
				
				if ($res == 2)
				{
					$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
									'ninja_login' => $this->_ninja->ninja['ninja_login'],
									'clan_id' => $this->clan_id,
									'clan_name' => $_POST['clan_nom']);

					$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 
								'ninja' => $this->_ninja->ninja['stats_id'], 'data' => $log_data);

					wanLog::log($log, $this);

					$this->_clanAddMember($this->_ninja->ninja['ninja_id']);
					
					$this->advert = "Bienvenue chez les ".$_POST['clan_nom'];
					$this->redirect = 'index.php?page=clan';
					return TRUE;
				}
				else
				{
					$this->advert = "Impossible de créer le clan maintenant";
					$this->redirect = 'index.php?page=centre&mode=clan&clan=creer';
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
			$this->redirect = 'index.php?page=centre&mode=clan&clan=creer';
			$this->advert = "Formulaire invalide";
			return FALSE;
		}
	}

	/*
	 * COFFRE
	 */

	/*
	 *	Récupère les objets du coffre de clan
	 */
	public function clanChestList()
	{
		$request = "SELECT 
						wan_clan_chests.*, 
						wan_categorie_objet.*,
						wan_commerces.commerce_id,
						wan_commerces.commerce_nom,
						wan_commerces.commerce_description, 
						wan_commerces.commerce_image, 
						wan_commerces.commerce_type  
					FROM 
						wan_clan_chests 
						LEFT JOIN 
							wan_commerces 
							ON 
								wan_commerces.commerce_id = wan_clan_chests.chest_item_id 
						LEFT JOIN 
							wan_categorie_objet 
							ON 
								wan_categorie_objet.objet_id = wan_commerces.commerce_type 
					WHERE 
						wan_clan_chests.chest_clan_id = ".ktPDO::get()->quote($this->clan_id)." 
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

	/*
	 * Dépose un objet dans le coffre du clan
	 */
	public function clanChestDeposit($item_id, $item_quantity)
	{
		$this->redirect = "index.php?page=clan&mode=coffre";
		$item_quantity = abs($item_quantity);

		if ($item_quantity == 0)
		{
			$this->advert = "Tu ne peux pas déposer 0 quantité de l'objet dans le coffre !";
			return FALSE;
		}

		$_item = new wanObjet($this->_ninja);
		$item = $_item->getObjet($item_id);

		if ($item != FALSE)
		{
			$_item->_inventaire->ninjaCheckInventaire();

			if ($_item->_inventaire->inv_objet_qt >= $item_quantity)
			{
				if ($this->_clanUpdateChestItem('add', $item_id, $item_quantity))
				{
					$_item->_inventaire->ninjaMajInventaire($item_quantity, 'pick');

					$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
									  'ninja_login' => $this->_ninja->ninja['ninja_login'],
									  'item_id' => $item_id,
									  'item_quantity' => $item_quantity,
									  'item_name' => $_item->objet['commerce_nom'],
									  'clan_id' => $this->clan_id,
									  'clan_name' => $this->clan_infos['clan_nom']);

					$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

					wanLog::log($log, $this);

					$this->advert = "Tu as déposé l'objet \"".$_item->objet['commerce_nom']."\" x".$item_quantity." dans le coffre du clan !";
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
			else
			{
				$this->advert = "Tu n'as pas cet objet en quantité suffisante pour en déposer";
				return FALSE;
			}
		}
		else
		{
			$this->advert = "Cet objet n'existe pas !";
			return FALSE;
		}
	}

	/*
	 * Prend un objet dans le coffre du clan
	 */
	public function clanChestTake($item_id, $item_quantity)
	{
		$this->redirect = "index.php?page=clan&mode=coffre";
		$item_quantity = abs($item_quantity);

		if ($item_quantity == 0)
		{
			$this->advert = "Tu ne peux pas prendre 0 quantité de l'objet dans le coffre !";
			return FALSE;
		}

		$_item = new wanObjet($this->_ninja);
		$item = $_item->getObjet($item_id);

		if ($item != FALSE)
		{
			if ($this->_clanUpdateChestItem('pick', $item_id, $item_quantity))
			{
				$_item->_inventaire->ninjaMajInventaire($item_quantity, 'add');

				$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
								  'ninja_login' => $this->_ninja->ninja['ninja_login'],
								  'item_id' => $item_id,
								  'item_quantity' => $item_quantity,
								  'item_name' => $_item->objet['commerce_nom'],
								  'clan_id' => $this->clan_id,
								  'clan_name' => $this->clan_infos['clan_nom']);

				$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

				wanLog::log($log, $this);

				$this->advert = "Tu as pris l'objet \"".$_item->objet['commerce_nom']."\" x".$item_quantity." dans le coffre du clan !";
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			$this->advert = "Cet objet n'existe pas !";
			return FALSE;
		}
	}

	/*
	 *	Lit un objet dans le coffre
	 */
	private function _clanGetChestItem($item_id)
	{
		$request = "SELECT 
						* 
					FROM 
						wan_clan_chests 
					WHERE 
						chest_clan_id = ".ktPDO::get()->quote($this->clan_id)." 
						AND 
						chest_item_id = ".ktPDO::get()->quote($item_id)." 
					LIMIT 
						1";

		$result = ktPDO::get()->query($request);

		if ($result != FALSE AND $result->rowCount() == 1)
		{
			$item = $result->fetch(PDO::FETCH_ASSOC);

			$result->closeCursor();

			return $item;
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 * Met à jour un objet dans le coffre
	 */
	private function _clanUpdateChestItem($mode = 'add', $item_id, $quantity)
	{
		$chest_item_quantity = $this->_clanGetChestItem($item_id);

		switch ($mode)
		{
			case 'add' :

				if ($chest_item_quantity !== FALSE)
				{
					return $this->_clanAddChestItem($item_id, $quantity);
				}
				else
				{
					return $this->_clanInsertChestItem($item_id, $quantity);
				}

			break;

			case 'pick' :

				if ($chest_item_quantity !== FALSE OR 
					$chest_item_quantity['chest_item_quantity'] - $quantity >= 0)
				{
					if ($chest_item_quantity['chest_item_quantity'] > $quantity)
					{
						return $this->_clanPickChestItem($item_id, $quantity);
					}
					else
					{
						return $this->_clanDeleteChestItem($item_id);
					}
				}
				else
				{
					$this->advert = "Il n'y a pas assez de cet objet dans le coffre pour en prendre cette quantité !";
					return FALSE;
				}

			break;
		}
	}

	/*
	 * Augmente la quantité d'un objet dans le coffre du clan
	 */
	private function _clanAddChestItem($item_id, $item_quantity = 1)
	{
		$request = "UPDATE 
						wan_clan_chests 
					SET 
						chest_item_quantity = chest_item_quantity + ".ktPDO::get()->quote($item_quantity)." 
					WHERE 
						chest_clan_id = ".ktPDO::get()->quote($this->clan_id)." 
						AND 
						chest_item_id = ".ktPDO::get()->quote($item_id)." 
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		return $result == 1;
	}

	/*
	 * Insère un nouvel objet dans le coffre du clan
	 */
	private function _clanInsertChestItem($item_id, $item_quantity = 1)
	{
		$request = "INSERT INTO 
						wan_clan_chests (chest_clan_id, chest_item_id, chest_item_quantity) 
					VALUES 
						(
							".ktPDO::get()->quote($this->clan_id).",
							".ktPDO::get()->quote($item_id).",
							".ktPDO::get()->quote($item_quantity)."
						)";

		$result = ktPDO::get()->exec($request);

		return $result == 1;
	}

	/*
	 * Enlève une quantité d'un certain objet dans le coffre du clan
	 */
	private function _clanPickChestItem($item_id, $item_quantity = 1)
	{
		$request = "UPDATE 
						wan_clan_chests 
					SET 
						chest_item_quantity = chest_item_quantity - ".ktPDO::get()->quote($item_quantity)."
					WHERE 
						chest_clan_id = ".ktPDO::get()->quote($this->clan_id)." 
						AND 
						chest_item_id = ".ktPDO::get()->quote($item_id)."
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		return $result == 1;
	}

	/*
	 * Supprime un objet du coffre du clan
	 */
	private function _clanDeleteChestItem($item_id)
	{
		$request = "DELETE FROM 
						wan_clan_chests 
					WHERE 
						chest_clan_id = ".ktPDO::get()->quote($this->clan_id)." 
						AND 
						chest_item_id = ".ktPDO::get()->quote($item_id)." 
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		return $result == 1;
	}

	/*
	 * LOGS PARSE
	 */
	public static function clanProcessJoiningLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> rejoint le clan 
				<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	public static function clanBankWithdrawalLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> fait un retrait de '.$data['amount'].' ryôs 
				sur le compte du clan <a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	public static function clanBankDepositLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> fait un dépôt de '.$data['amount'].' ryôs 
				sur le compte du clan <a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	public static function clanEditLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> modifie les informations du clan 
				<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	public static function clanDissoudreLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> dissout le clan 
				<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	public static function clanCreateRoleLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> crée le rôle "'.$data['role_data']['role_name'].'" 
				 pour le clan <a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	public static function clanQuitterLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> quitte le clan 
				<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	public static function clanCreerLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> crée le clan 
				<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	public static function clanAssignRoleLogParse($data)
	{
		switch ($data['mode'])
		{
			case 'sender' :
				if ($data['role_id'] == 0) :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> supprime le rôle de  
						<a href="index.php?page=profil&id='.$data['receiver_id'].'">'.$data['receiver_login'].'</a> du clan 
						<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
				else:
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> désigne 
						<a href="index.php?page=profil&id='.$data['receiver_id'].'">'.$data['receiver_login'].'</a> en tant que 
						"'.$data['role_name'].'" du clan 
						<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
				endif;
			break;

			case 'receiver' :
				if ($data['role_id'] == 0) :
				return '<a href="index.php?page=profil&id='.$data['receiver_id'].'">'.$data['receiver_login'].'</a> s\'est fait retiré son rôle 
						du clan <a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a> par 
						<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a>';
				else :
				return '<a href="index.php?page=profil&id='.$data['receiver_id'].'">'.$data['receiver_login'].'</a> est désigné 
						"'.$data['role_name'].'" du clan 
						<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a> par 
						<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a>';
				endif;
			break;
		}
	}

	public static function clanExcludeMemberLogParse($data)
	{
		switch ($data['mode'])
		{
			case 'sender' :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> exclu 
						<a href="index.php?page=profil&id='.$data['receiver_id'].'">'.$data['receiver_login'].'</a> 
						du clan <a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
			break;

			case 'receiver' :
				return '<a href="index.php?page=profil&id='.$data['receiver_id'].'">'.$data['receiver_login'].'</a> a été exclu 
						du clan <a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a> 
						par <a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a>';
			break;
		}
	}

	public static function clanChestDepositLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> dépose "'.$data['item_name'].'" 
				x'.$data['item_quantity'].' dans le coffre du clan 
				<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	public static function clanChestTakeLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> prend "'.$data['item_name'].'" 
				x'.$data['item_quantity'].' dans le coffre du clan 
				<a href="index.php?page=centre&mode=clan&clan=voir&id='.$data['clan_id'].'">'.$data['clan_name'].'</a>';
	}

	/*
	 * NOTIFY PARSE
	 */
	public static function clanProcessJoiningNotificationParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a>, 
				'.$data['rang_nom'].' niv.'.$data['stats_niveau'].' rejoint le clan';
	}

	public static function clanAssignRoleNotificationParse($data)
	{
		if ($data['role_id'] == 0) :
		return 'Tu as été désigné "'.$data['role_name'].'" du clan <a href="index.php?page=clan">'.$data['clan_name'].'</a>';
		else :
		return 'Ton rôle au sein du clan <a href="index.php?page=clan">'.$data['clan_name'].'</a> a été supprimé';
		endif;
	}

	public static function clanExcludeMemberNotificationParse($data)
	{
		return 'Tu as été exclu du clan <a href="index.php?page=clan">'.$data['clan_name'].'</a>';
	}

	public static function clanDissoudreNotificationParse($data)
	{
		return 'Le clan <strong>'.$data.'</strong> a été dissout';
	}

	public static function clanQuitterNotificationParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a>, 
				'.$data['rang_nom'].' niv.'.$data['stats_niveau'].' a quitté le clan';
	}
}

?>