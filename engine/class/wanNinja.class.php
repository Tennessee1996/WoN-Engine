<?php

/*
 * Récupére et manipule les données d'un ninja
 */
 
class wanNinja
{
	// données du ninja
	public $ninja = NULL;
	public $guest = FALSE;
	public $_multi = NULL;
	
	/*
	 * Constructeur
	 */
	public function __construct($ninja_id, $guest = FALSE)
	{
		$this->guest = $guest;
		
		if ($this->getNinja($ninja_id))
		{
			return TRUE;
		}
		else
		{
			wanEngine::redirect('index.php?page=deconnexion');
		}
	}
	
	/*
	 * Récupere le ninja dans la base
	 */
	private function getNinja($ninja_id = NULL)
	{
		$req_get_ninja = "SELECT 
							* 
						FROM 
							wan_ninja 
							LEFT JOIN 
								wan_stats 
								ON 
									wan_ninja.ninja_id=wan_stats.stats_id 
							LEFT JOIN 
								wan_villages
								ON 
									wan_stats.stats_village=wan_villages.village_id
							LEFT JOIN 
								wan_rangs
								ON 
									wan_rangs.rang_id=wan_stats.stats_rang 
							LEFT JOIN 
								wan_clan 
								ON 
									wan_clan.clan_id=wan_stats.stats_clan
					WHERE 
						wan_ninja.ninja_id='".$ninja_id."'";
		
		$res_get_ninja = ktPDO::get()->query($req_get_ninja);
		
		if (!$res_get_ninja)
		{
			return FALSE;
		}
		else if (($cnt_get_ninja = $res_get_ninja->rowCount()) == 0)
		{
			return FALSE;
		}
		else
		{
			$ret_get_ninja = $res_get_ninja->fetch(PDO::FETCH_ASSOC);
			$res_get_ninja->closeCursor();
			
			$this->ninja = $ret_get_ninja;
			
			if (!$this->guest)
			{
				$this->controlNinja();
				wanLevel::levelUp($this->ninja['stats_niveau'], $this->ninja['stats_xp']);
				
				$hopital = new wanHospital($this);
				
				if ($hopital->checkDeath() == FALSE)
				{
					$victuals = new wanRealtime($this);
					$victuals->controlVictuals();
				}
			}
			
			$this->_multi = new wanMulti($this);
			$this->completeNinjaName();
			$this->ninja['equipement'] = wanStuff::getStuff($this->ninja['stats_id']);
			
			return TRUE;
		}
	}
	
	/*
	 * Complète le nom d'un ninja s'il est dans un clan
	 */
	private function completeNinjaName()
	{
		if (!empty($this->ninja['stats_clan']))
			$this->ninja['ninja_login'] .= ' '.$this->ninja['clan_nom'];
	}
	
	/*
	 * Complète le village d'un ninja s'il est Nukenin
	 */
	private function completeNinjaVillage()
	{
		if ($this->ninja['stats_rang'] == 6)
			$this->ninja['village_nom'] = '<span style="text-decoration:line-through;">'.$this->ninja['village_nom'].'</span>';
	}
	
	/*
	 * Contrôle les interactions spéciales d'un ninja
	 */
	private function controlNinja()
	{
		if ($this->ninja['ninja_ban_statut'] == '1')
		{
			$log_data['ninja_id'] = $this->ninja['ninja_id'];
			$log_data['ninja_login'] = $this->ninja['ninja_login'];

			$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 
						'ninja' => $this->ninja['stats_id'], 'data' => $log_data);

			wanLog::log($log);

			wanEngine::unsetConnect();
		}
	}

	/*
	 *	Contrôle si le ninja est en réanimation
	 */
	public function isInReanimation()
	{
		$hopital = new wanHospital($this);

		return $hopital->isDead();
	}

	/*
	 * Récupère les nouvelles notifications
	 */
	public function getNotifications()
	{
		return wanNotify::count_news($this->ninja['ninja_id']);
	}
	
	/*
	 * Valide les nouvelles notifications
	 */
	public function checkNotifications($last_timestamp, $type = false)
	{
		return wanNotify::check_news($this->ninja['ninja_id'], $last_timestamp, $type);
	}
	
	/*
	 * Controle si le ninja est en mission
	 */
	public function ninjacheckMission()
	{
		if ($this->ninja['stats_mission_id'] != 0)
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Controle l'état de santé d'un ninja
	 */
	public function ninjacheckHealth()
	{
		if ($this->ninja['stats_faim'] < 20)
			return TRUE;
		if ($this->ninja['stats_soif'] < 20)
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Met à jour la session temps d'un ninja
	 */
	public function majTime($void = FALSE)
	{
		if ($_SESSION['control']['time'] < time() OR $void == TRUE)
		{
			$req_time = "UPDATE 
							wan_ninja 
						SET 
							ninja_last_connect=".time()." 
						WHERE 
							ninja_id= ".ktPDO::get()->quote($this->ninja['ninja_id'])." 
						LIMIT 
							1";
			
			$result_time = ktPDO::get()->exec($req_time);
			
			if ($result_time != 1)
			{
				return FALSE;
			}
			else
			{
				$_SESSION['ninja']['time'] = time();
				$_SESSION['control']['time'] = time()+900;
				return TRUE;
			}
		}
		else
			return FALSE;
	}
	
	/*
	 * Vérifie la bourse d'un ninja pour une somme donnée
	 */
	public function ninjaCheckRyos($ryos = 0)
	{
		if (!empty($ryos))
		{
			if ($this->ninja['stats_ryos'] - $ryos >= 0)
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	/*
	 * Vérifie l'appartenance à un village pour un village donné
	 */
	public function ninjaCheckVillage($village = 0)
	{
		if (isset($village))
		{
			if ($this->ninja['stats_village'] == $village)
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	/*
	 * Vérifie l'appartenance à un clan pour un clan donné
	 */
	public function ninjaCheckClan($clan = NULL)
	{
		if (isset($clan))
		{
			if ($this->ninja['stats_clan'] == $clan OR empty($clan))
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	/*
	 * Vérifie le grade du ninja pour un grade donné
	 */
	public function ninjaCheckGrade($grade = NULL)
	{
		if (isset($grade))
		{
			if ($this->ninja['stats_rang'] >= $grade)
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	/*
	 * Vérifie les kobans d'un ninja pour une somme donnée
	 */
	public function ninjaCheckKoban($koban = 0)
	{
		if (!empty($koban))
		{
			if ($this->ninja['stats_koban'] - $koban >= 0)
				return TRUE;
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	/*
	 * Modifie les kobans du ninja pour un mode et une somme donnée
	 */
	public function ninjaChangeKoban($mode, $value)
	{
		$value = abs($value);
		
		switch ($mode) :
		
			case 'add' :
				
				$req_ryos = "UPDATE 
									wan_stats 
								SET 
									stats_koban=stats_koban+".$value." 
								WHERE 
									stats_id='".$this->ninja['stats_id']."' 
								LIMIT 
									1";
				
			break;
			
			case 'pick' :
				
				if ($this->ninjaCheckKoban($value))
				{
					$req_ryos = "UPDATE 
									wan_stats 
								SET 
									stats_koban=stats_koban-".$value." 
								WHERE 
									stats_id='".$this->ninja['stats_id']."' 
								LIMIT 
									1";
				}
				else
					return FALSE;
				
			break;
		
		endswitch;
		
		$res_ryos = ktPDO::get()->exec($req_ryos);
		
		if ($res_ryos != 1)
			return 0;
		else
			return 1;
	}
	
	/*
	 * Modifie la bourse du ninja pour un mode et une somme donnée
	 */
	public function ninjaChangeRyos($mode, $value)
	{
		$value = abs($value);
		
		switch ($mode) :
		
			case 'add' :
				
				$req_ryos = "UPDATE 
									wan_stats 
								SET 
									stats_ryos=stats_ryos+".$value." 
								WHERE 
									stats_id='".$this->ninja['stats_id']."' 
								LIMIT 
									1";
				
			break;
			
			case 'pick' :
				
				if ($this->ninjaCheckRyos($value))
				{
					$req_ryos = "UPDATE 
									wan_stats 
								SET 
									stats_ryos=stats_ryos-".$value." 
								WHERE 
									stats_id='".$this->ninja['stats_id']."' 
								LIMIT 
									1";
				}
				else
					return FALSE;
				
			break;
		
		endswitch;
		
		$res_ryos = ktPDO::get()->exec($req_ryos);
		
		if ($res_ryos != 1)
			return 0;
		else
			return 1;
	}
	
	/*
	 *	Modifie la faim du ninja
	 */
	public function ninjaChangeFaim($value)
	{
		$req = "UPDATE 
					wan_stats 
				SET 
					stats_faim=".$value." 
				WHERE 
					stats_id='".$this->ninja['stats_id']."' 
				LIMIT 
					1";
		
		$res = ktPDO::get()->exec($req);
		
		if ($res != 1)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Modifie la soif du ninja
	 */
	public function ninjaChangeSoif($value)
	{
		$req = "UPDATE 
					wan_stats 
				SET 
					stats_soif=".$value." 
				WHERE 
					stats_id='".$this->ninja['stats_id']."' 
				LIMIT 
					1";
		
		$res = ktPDO::get()->exec($req);
		
		if ($res != 1)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Modifie la vie du ninja
	 */
	public function ninjaChangeVie($value)
	{
		$req = "UPDATE 
					wan_stats 
				SET 
					stats_vie=".$value." 
				WHERE 
					stats_id='".$this->ninja['stats_id']."' 
				LIMIT 
					1";
		
		$res = ktPDO::get()->exec($req);
		
		if ($res != 1)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Modifie le chakra du ninja
	 */
	public function ninjaChangeChakra($value)
	{
		$req = "UPDATE 
					wan_stats 
				SET 
					stats_chakra=".$value." 
				WHERE 
					stats_id='".$this->ninja['stats_id']."' 
				LIMIT 
					1";
		
		$res = ktPDO::get()->exec($req);
		
		if ($res != 1)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Equipe une amulette
	 */
	public function ninjaAmuletteAdd($mode)
	{
		if ($this->ninja['stats_amulette_'.$mode] == '1')
			return FALSE;
		else
		{
			$req_add = "UPDATE 
							wan_stats
						SET 
							stats_amulette_".$mode."='1' 
						WHERE 
							stats_id='".$this->ninja['stats_id']."' 
						LIMIT 
							1";
							
			$res_add = ktPDO::get()->exec($req_add);

			$log_data['ninja_id'] = $this->ninja['ninja_id'];
			$log_data['ninja_login'] = $this->ninja['ninja_login'];
			$log_data['amulet_name'] = $mode;

			$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 'ninja' => $this->ninja['ninja_id'], 
						'data' => $log_data);

			wanLog::log($log);
			
			if ($res_add == 1)
				return TRUE;
			else
				return FALSE;
		}
	}
	
	/*
	 * Vérifie les grades spéciaux (sennin & nukenin)
	 */
	public function ninjaCheckSpecialGrade($grade)
	{
		if ($grade > 5)
		{
			if ($grade == 6 AND $this->_ninja->ninja['stats_rang'] == 7)
				return FALSE;
			else if ($grade == 7 AND $this->_ninja->ninja['stats_rang'] == 6)
				return FALSE;
			else
				return TRUE;
		}
		else
			return TRUE;
	}
	
	/*
	 * Vérifie si le ninja a des capacités de modération
	 */
	public function ninjaIsModo()
	{
		if ($this->ninja['ninja_modo'] == '1' OR $this->ninja['ninja_admin'] == '1')
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Vérifie si le ninja a des capacités d'administration
	 */
	public function ninjaIsAdmin()
	{
		if ($this->ninja['ninja_admin'] == '1')
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Vérifie si le ninja est le Kage de son village
	 */
	public function ninjaIsKage()
	{
		if ($this->ninja['stats_rang'] == 8 AND $this->ninja['stats_id'] == $this->ninja['village_kage'])
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Ajoute un équipement au ninja et lui affecte les attributs
	 */
	public function ninjaAddEquipement($objet, $objet_values)
	{
		if (!empty($objet) AND !empty($objet_values))
		{
			$req_add = "INSERT INTO 
							wan_equipement (equipement_ninja_id, equipement_objet_id, equipement_type) 
						VALUES 
							('".$this->ninja['stats_id']."', ".$objet['commerce_id'].", '".$objet['commerce_effet']."')";
			
			$req_up = "UPDATE 
							wan_stats 
						SET ";

			foreach ($objet_values as $key => $value)
			{
				if ($key == 'force')
					$req_up .= "stats_bonus_force=stats_bonus_force+".$value[1].", ";
				else if ($key == 'rapidite')
					$req_up .= "stats_bonus_rapidite=stats_bonus_rapidite+".$value[1].", ";
				else if ($key == 'endurance')
					$req_up .= "stats_bonus_endurance=stats_bonus_endurance+".$value[1].", ";
				else
					continue;
			}
			
			$req_up = substr($req_up, 0, -2);
			
			$req_up .= " WHERE 
							stats_id='".$this->ninja['stats_id']."' 
						LIMIT 
							1";
			
			$state = 0;					
			$state += ktPDO::get()->exec($req_add);
			$state += ktPDO::get()->exec($req_up);

			$log_data['ninja_id'] = $this->ninja['ninja_id'];
			$log_data['ninja_login'] = $this->ninja['ninja_login'];
			$log_data['objet_name'] = $objet['commerce_nom'];
			$log_data['objet_id'] = $objet['commerce_id'];
			$log_data['objet_type'] = $objet['commerce_effet'];

			$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 'ninja' => $this->ninja['ninja_id'], 
						'data' => $log_data);

			wanLog::log($log);
			
			if ($state == 2)
			{
				wanStuff::stuffDeleteCache($this->ninja['stats_id']);
				return TRUE;
			}
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	/*
	 * Enlève un équipement au ninja et lui affecte les attributs
	 */
	public function ninjaRemoveEquipement($objet_datas = array())
	{
		if (!empty($objet_datas))
		{
			$_objet = new wanObjet($this);
			$_objet->_inventaire->initObjet($objet_datas['commerce_id']);
			$objet_values = $_objet->objetStuffPrepare($objet_datas);
			
			$req_del = "DELETE
						FROM 
							wan_equipement 
						WHERE 
							equipement_ninja_id='".$this->ninja['stats_id']."' 
							AND 
							equipement_objet_id=".$objet_datas['commerce_id']." 
							AND
							equipement_type='".$objet_datas['commerce_effet']."'";
			
			$req_up = "UPDATE 
							wan_stats 
						SET ";

			foreach ($objet_values as $key => $value)
			{
				if ($key == 'force')
					$req_up .= "stats_bonus_force=stats_bonus_force-".$value[1].", ";
				else if ($key == 'rapidite')
					$req_up .= "stats_bonus_rapidite=stats_bonus_rapidite-".$value[1].", ";
				else if ($key == 'endurance')
					$req_up .= "stats_bonus_endurance=stats_bonus_endurance-".$value[1].", ";
				else
					continue;
			}
			
			$req_up = substr($req_up, 0, -2);
			
			$req_up .= " WHERE 
							stats_id='".$this->ninja['stats_id']."' 
						LIMIT 
							1";
							
			$state = 0;					
			
			$state += ktPDO::get()->exec($req_del);
			$state += ktPDO::get()->exec($req_up);
			
			$state += $_objet->_inventaire->ninjaMajInventaire(1, 'add');
			
			$log_data['ninja_id'] = $this->ninja['ninja_id'];
			$log_data['ninja_login'] = $this->ninja['ninja_login'];
			$log_data['objet_name'] = $objet_datas['commerce_nom'];
			$log_data['objet_id'] = $objet_datas['commerce_id'];
			$log_data['objet_type'] = $objet_datas['commerce_effet'];

			$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 'ninja' => $this->ninja['ninja_id'], 
						'data' => $log_data);

			wanLog::log($log);

			if ($state == 3)
			{
				wanStuff::stuffDeleteCache($this->ninja['stats_id']);
				return TRUE;
			}
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	
	/*
	 * Vérifie que le ninja puisse devenir membre de l'Akatsuki
	 */
	public function ninjaCanNukenin()
	{
		if ($this->ninja['stats_rang'] != 5)
			return FALSE;
		if ($this->ninja['stats_niveau'] < 125)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Vérifie que le ninja puisse devenir Sennin
	 */
	public function ninjaCanSennin()
	{
		if ($this->ninja['stats_rang'] != 5)
			return FALSE;
		if ($this->ninja['stats_niveau'] < 125)
			return FALSE;
		if ($this->ninja['stats_mission_5'] < 100)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Change le rang du ninja
	 */
	public function ninjaChangeGrade($grade)
	{
		$grade = (int) $grade;
		
		$req = "UPDATE 
					wan_stats 
				SET 
					stats_rang=".$grade." 
				WHERE 
					stats_id='".$this->ninja['stats_id']."' 
				LIMIT 
					1";
		
		$res = ktPDO::get()->exec($req);
		
		if ($res != 1)
		{
			return FALSE;
		}
		else
		{
			$this->_multi->multiFileCreate();
			return TRUE;
		}
	}
	
	/*
	 * Efface les éléments
	 */
	public function ninjaResetElements()
	{
		$req = "UPDATE 
					wan_stats 
				SET 
					stats_element_chuunin = 0, 
					stats_element_jounin = 0, 
					stats_element_anbu = 0 
				WHERE 
					stats_id = ".ktPDO::get()->quote($this->ninja['stats_id'])." 
				LIMIT 
					1";
					
		$res = ktPDO::get()->exec($req);

		$log_data['ninja_id'] = $this->ninja['ninja_id'];
		$log_data['ninja_login'] = $this->ninja['ninja_login'];

		$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 'ninja' => $this->ninja['ninja_id'], 
					'data' => $log_data);

		wanLog::log($log);
		
		if (!$res)
			return FALSE;
		else
			return TRUE;
	}
	
	/*
	 * Change le nom d'un ninja
	 */
	public function ninjaChangeName($name = "")
	{
		if (!empty($name))
		{
			$req = "UPDATE 
						wan_ninja 
					SET 
						ninja_login = ".ktPDO::get()->quote($name)."
					WHERE 
						ninja_id = ".ktPDO::get()->quote($this->ninja['ninja_id'])." 
					LIMIT 
						1";
			
			$res = ktPDO::get()->exec($req);
			
			if ($res == 1)
			{
				$this->ninjaChangeKoban('pick', 2);

				$log_data['ninja_id'] = $this->ninja['ninja_id'];
				$log_data['ninja_login'] = $this->ninja['ninja_login'];
				$log_data['new_login'] = $name;

				$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 'ninja' => $this->ninja['ninja_id'], 
							'data' => $log_data);

				wanLog::log($log);

				$multi = new wanMulti($this);
				$multi->multiFileCreate();
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

	public function sendNewPassword()
	{
		$new_password = wanEngine::generateId(6);
		$new_password_free = $new_password;
		$new_password_hash = wanEngine::myHash($new_password);

		$req_up = "UPDATE 
						wan_ninja 
					SET 
						ninja_password = ".ktPDO::get()->quote($new_password_hash)." 
					WHERE 
						ninja_id = ".ktPDO::get()->quote($this->ninja['ninja_id'])." 
					LIMIT 
						1";

		$res_up = ktPDO::get()->exec($req_up);

		$log_data['ninja_id'] = $this->ninja['ninja_id'];
		$log_data['ninja_login'] = $this->ninja['ninja_login'];
		$log_data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		$log_data['user_ip'] = $_SERVER['REMOTE_ADDR'];

		wanLog::log(array('ninja' => $this->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data));

		$mail_message = "Ce mail vous est envoyé car vous avez demandé un changement de mot de passe.<br /><br />"
		.	"Pour rappel, votre login est : \"".$this->ninja['ninja_mail']."\".<br /><br />"
		.	"Votre nouveau mot de passe est : <strong>".$new_password_free."</strong>, vous pourrez le changer ensuite sur la page de votre profil.<br /><br />"
		.	"Bon jeu sur Way of Ninja !";
		
		$mail_object = new Rmail();
		$mail_object->setFrom('Way of Ninja <contact@wayofninja.fr>');
		$mail_object->setSubject('Way of Ninja : changement de mot de passe');
		$mail_object->setPriority('high');
		$mail_object->setHTML($mail_message);
		$address = $this->ninja['ninja_mail'];
		$result  = $mail_object->send(array($address));
	}

	/*
	 * LOGS PARSE
	 */
	public static function controlNinjaLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> est déconnecté suite à un bannissement';
	}

	public static function ninjaAddEquipementLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> équipe <strong>'.$data['objet_name'].'</strong>';
	}

	public static function ninjaRemoveEquipementLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> déséquipe <strong>'.$data['objet_name'].'</strong>';
	}

	public static function ninjaChangeNameLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> est désormais connu sous le nom de <strong>'.$data['new_login'].'</strong>';
	}

	public static function ninjaResetElementsLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> remet à zéro ses éléments';
	}

	public static function ninjaAmuletteAddLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> porte désormais l\'amulette "'.ucfirst($data['amulet_name']).'"';
	}

	public static function sendNewPasswordLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> lance la procédure "Mot de passe oublié"<br />
				<em style="font-size:6px;">'.$data['user_agent'].' - '.$data['user_ip'].'</em>';
	}	
}

?>