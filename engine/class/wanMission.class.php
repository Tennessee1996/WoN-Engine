<?php

class wanMission
{
	public $_ninja;
	public $advert;
	public $redirect;
	public $mission;

	public function __construct($_ninja)
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
	 * Retourne les missions pour une catégorie donnée
	 */
	public function missionGetList($type, $akatsuki = false)
	{
		$request = "SELECT 
						* 
					FROM 
						wan_type_missions 
							LEFT JOIN 
							wan_missions 
								ON 
								wan_type_missions.type_mission_id = wan_missions.mission_type 
					WHERE 
						wan_type_missions.type_mission_id = ".ktPDO::get()->quote($type)."";

		if ($akatsuki)
		{
			$request .= " AND wan_missions.mission_akatsuki = 1";
		}
		else
		{
			$request .= " AND wan_missions.mission_akatsuki = 0";
		}

		$result = ktPDO::get()->query($request);

		if ($result != FALSE AND $result->rowCount() > 0)
		{
			$missions = array();

			while ($return = $result->fetch(PDO::FETCH_ASSOC))
			{
				$return['mission_duree'] = $return['mission_temps'] / 60;
				$return['mission_duree_amulette'] = ceil($return['mission_duree'] * 0.75);
				$return['mission_difficulte'] = $this->_missionDifficulty($return['mission_chance']);
				$missions[] = $return;
			}

			$result->closeCursor();
			return $missions;
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 * Sélectionne une mission
	 */
	public function missionGetOnce($mission_id = 0)
	{
		if (empty($mission_id))
		{
			return FALSE;
		}
		else
		{
			$request = "SELECT 
							wan_missions.*, 
							type_mission_id, 
							type_mission_rang, 
							rang_id, 
							rang_nom 
						FROM 
							wan_missions 
								LEFT JOIN 
								wan_type_missions
									ON 
									wan_type_missions.type_mission_id=wan_missions.mission_type 
								LEFT JOIN 
								wan_rangs 
									ON 
									wan_type_missions.type_mission_rang=wan_rangs.rang_id 
						WHERE 
							wan_missions.mission_id = ".ktPDO::get()->quote($mission_id)." 
						LIMIT 
							1";

			$result = ktPDO::get()->query($request);

			if ($result != FALSE AND $result->rowCount() == 1)
			{
				$this->mission = $result->fetch(PDO::FETCH_ASSOC);

				$result->closeCursor();

				return $this->mission;
			}
			else
			{
				return FALSE;
			}
		}
	}

	/*
	 * Met à jour le ninja pour le lancement de la mission
	 */
	private function _missionNinjaAssign($mission_id, $mission_end_at)
	{
		$request = "UPDATE 
						wan_stats 
					SET 
						stats_mission_id = ".ktPDO::get()->quote($mission_id).", 
						stats_mission_date = ".ktPDO::get()->quote($mission_end_at)." 
					WHERE 
						stats_id = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." 
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		return $result == 1;
	}

	/*
	 * Met à jour le ninja pour la clôture de la mission
	 */
	private function _missionNinjaClose()
	{
		$mission = $this->mission;

		$lost = $this->_proceedToChance($mission['mission_chance']);
		$victuals = $this->_missionEndVictuals($lost);

		if ($mission['mission_type'] == 5)
		{
			$grade = new wanGrade($this->_ninja);
			$grade->gradeProcessSennin();
		}

		$request = "UPDATE 
						wan_stats 
					SET 
						stats_faim = ".ktPDO::get()->quote($victuals['hunger']).", 
						stats_soif = ".ktPDO::get()->quote($victuals['thirst']).", 
						stats_vie = ".ktPDO::get()->quote($victuals['life']).", ";

		if ($lost == FALSE)
		{
			$request .= "stats_ryos = stats_ryos + ".ktPDO::get()->quote($mission['mission_gain_ryos']).",
						stats_xp = stats_xp + ".ktPDO::get()->quote($mission['mission_gain_exp']).", 
						stats_mission_".$mission['mission_type']." = stats_mission_".$mission['mission_type']."+1, ";
		}

		$request .= "	stats_mission_id = 0 
					WHERE 
						stats_id = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." 
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		if ($result == 1)
		{
			$mission_status = $lost ? 'lost' : 'won';

			$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
							  'ninja_login' => $this->_ninja->ninja['ninja_login'],
							  'mission_id' => $mission['mission_id'],
							  'mission_name' => $mission['mission_titre'],
							  'mission_status' => $mission_status);

			$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => 'missionEnd', 'data' => $log_data);

			wanLog::log($log);

			$notification_data = array('mission_name' => $mission['mission_titre'], 'mission_status' => $mission_status);

			wanNotify::notify($this->_ninja->ninja['ninja_id'], __CLASS__, self::missionEndNotificationParse($notification_data));

			return TRUE;
		}
		else
		{
			$this->advert = "Impossible de terminer la mission...";
			return FALSE;
		}
	}

	/*
	 * Retournes les catégories de mission
	 */
	public function missionGetCategories()
	{
		$request = "SELECT 
						* 
					FROM 
						wan_type_missions 
						LEFT JOIN 
							wan_rangs 
							ON 
								wan_rangs.rang_id=wan_type_missions.type_mission_rang";

		$result = ktPDO::get()->query($request);

		if ($result != FALSE AND $result->rowCount() > 0)
		{
			$types = array();

			while ($return = $result->fetch(PDO::FETCH_ASSOC))
			{
				$types[] = $return;
			}

			$result->closeCursor();

			return $types;
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 * Lance une mission
	 */
	public function missionStart($mission_id = 0)
	{
		$this->redirect = "index.php?page=caserne&mode=mission";

		if (empty($mission_id))
		{
			$this->advert = "Tu n'as pas sélectionné une mission valide !";
			return FALSE;
		}

		if ($this->_ninja->ninjaCheckHealth())
		{
			$this->advert = "Ton ninja n'est pas en assez bonne santé pour partir en mission";
			return FALSE;
		}
		else
		{
			$mission = $this->missionGetOnce($mission_id);

			if ($mission !== FALSE)
			{
				if ($this->_ninja->ninja['stats_rang'] < $mission['type_mission_rang'])
				{
					$this->advert = "Cette mission recquiert d'être au moins ".$mission['rang_nom']."";
					return FALSE;
				}
				if ($mission['mission_akatsuki'] == 1 AND $this->_ninja->ninja['stats_rang'] != 6)
				{
					$this->advert = "???";
					return FALSE;
				}

				$mission_time = $mission['mission_temps'];

				if ($this->_ninja->ninja['stats_amulette_rapidite'] == 1)
				{
					$mission_time = ceil($mission_time * 0.75);
				}

				$mission_start_at = time();
				$mission_end_at = $mission_start_at + $mission_time;

				$this->_missionNinjaAssign($mission['mission_id'], $mission_end_at);

				$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
								  'ninja_login' => $this->_ninja->ninja['ninja_login'],
								  'mission_id' => $mission_id,
								  'mission_name' => $mission['mission_titre'],
								  'mission_start_at' => $mission_start_at,
								  'mission_end_at' => $mission_end_at);

				$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

				wanLog::log($log);

				$this->advert = "Cette mission est pour toi ! Bonne chance !";
				$this->redirect = "index.php?page=ninja";
				return TRUE;
			}
			else
			{
				$this->advert = "La mission demandée n'existe pas !";
				return FALSE;
			}
		}
	}

	/*
	 *	Vérifie si la mission en cours est terminée
	 */
	public function missionIsFinished()
	{
		if ($this->_ninja->ninja['stats_mission_date'] <= time())
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 *	Vérifie qu'une mission est stoppable
	 */
	public function missionIsStoppable()
	{
		if ($this->_ninja->ninja['stats_amulette_rapidite'] == 1)
		{
			$mission_time = ceil($this->mission['mission_temps'] * 0.75);
		}
		else
		{
			$mission_time = $this->mission['mission_temps'];
		}

		$mission_start = $this->_ninja->ninja['stats_mission_date'] - $mission_time;
		$mission_elapsed = time() - $mission_start;
		$mission_done = (int) round(($mission_elapsed / $mission_time) * 100);

		return $mission_done <= 10;
	}

	/*
	 * Stoppe une mission
	 */
	public function missionStop()
	{
		$this->missionGetOnce($this->_ninja->ninja['stats_mission_id']);

		if ($this->missionIsStoppable())
		{
			$request = "UPDATE 
							wan_stats 
						SET 
							stats_mission_date = 0, 
							stats_mission_id = 0 
						WHERE 
							stats_id = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." 
						LIMIT 
							1";
			
			$result = ktPDO::get()->exec($request);

			$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
							  'ninja_login' => $this->_ninja->ninja['ninja_login'],
							  'mission_id' => $this->mission['mission_id'],
							  'mission_name' => $this->mission['mission_titre']);

			$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

			wanLog::log($log);

			$this->advert = "Tu as stoppé ta mission \"".$this->mission['mission_titre']."\"";
			return $result == 1;
		}
		else
		{
			$this->advert = "Tu ne peux plus stopper ta mission !";
			return FALSE;
		}
	}

	/*
	 * Termine une mission
	 */
	public function missionEnd()
	{
		$mission = $this->missionGetOnce($this->_ninja->ninja['stats_mission_id']);

		if ($mission !== FALSE)
		{
			$this->_missionNinjaClose();
		}
		else
		{
			return FALSE;
		}
	}

	/*
	 * Retourne les stats après une mission échouée
	 */
	private function _missionEndVictuals($lost = false)
	{
		$life = $this->_ninja->ninja['stats_vie'];
		$hunger = $this->_ninja->ninja['stats_faim'];
		$thirst = $this->_ninja->ninja['stats_soif'];

		if ($lost)
		{
			$life = round($life / 2);
			$hunger = $hunger - 10;
			$thirst = $thirst - 10;

			$this->advert = "Coriace cette mission ! Tu l'as échouée !";
		}
		else
		{
			$hunger = $hunger - 5;
			$thirst = $thirst - 5;

			if ($this->_ninja->ninja['stats_amulette_resistance'] == 1)
			{
				$life = round($life - ($life * 0.1));
			}
			else
			{
				$life = round($life - ($life * 0.2));
			}

			$this->advert = "Mission terminée ! Tout s'est bien passé !";
		}

		$return = array();
		$return['life'] = $life > $this->_ninja->ninja['stats_vie'] ? 0 : $life;
		$return['hunger'] = $hunger > $this->_ninja->ninja['stats_faim'] ? 0 : $hunger;
		$return['thirst'] = $thirst > $this->_ninja->ninja['stats_soif'] ? 0 : $thirst;
		return $return;
	}

	/*
	 * Vérifie si la mission est perdue
	 */
	private function _proceedToChance($chance = 0)
	{
		$random = mt_rand(0, $chance);
		$master_random = mt_rand(0, 2);
		
		return $random <= 100 - $chance AND $master_random == 1;
	}

	/*
	 * Retourne la difficulté de la mission en fonction des chances de réussites
	 */
	private function _missionDifficulty($chance = 0)
	{
		if ($chance < 60) {
			return 'Suicide';
		} else if ($chance >= 60 AND $chance < 70) {
			return 'Difficile';
		} else if ($chance >= 70 AND $chance < 80) {
			return 'Intermédiaire';
		} else if ($chance >= 80 AND $chance < 90) {
			return 'Facile';
		} else if ($chance >= 90 AND $chance < 100) {
			return 'Pédagogique';
		} else {
			return 'Aucune';
		}
	}

	/*
	 * LOGS PARSE
	 */
	public static function missionStartLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> commence la mission "'.$data['mission_name'].'"';
	}

	public static function missionStopLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> arrête sa mission "'.$data['mission_name'].'"';
	}

	public static function missionEndLogParse($data)
	{
		switch ($data['mission_status'])
		{
			case 'won' :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> termine la mission "'.$data['mission_name'].'"';
			break;

			case 'lost' :
				return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> échoue la mission "'.$data['mission_name'].'"';
			break;
		}
	}

	/*
	 * NOTIFY PARSE
	 */
	public static function missionEndNotificationParse($data)
	{
		switch ($data['mission_status'])
		{
			case 'won' :
				return 'Tu as terminé la mission "'.$data['mission_name'].'"';
			break;

			case 'lost' :
				return 'Tu as échoué la mission "'.$data['mission_name'].'"';
			break;
		}
	}
}

?>