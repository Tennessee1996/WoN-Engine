<?php

/*
 * Gestion des grades ninja
 */

class wanGrade
{
	// instance de la classe wanNinja
	public $_ninja;
	// grade initialisé
	public $grade;
	// message de retour de la classe
	public $message;
	// redirection après execution
	private $redirection;
	// débloque les rangs spéciaux
	private $unlock_special = FALSE;
	// enregistre la dernière date d'examen
	public $last_exam;
	
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
	 * Initialise un grade
	 */
	public function initGrade($grade)
	{
		$this->grade = (int) $grade;
		$this->getGrade();
	}
	
	/*
	 * Débloque les rangs spéciaux
	 */
	public function unlockSpecial($state = TRUE)
	{
		$this->unlock_special = $state;
	}
	
	/*
	 * Spécifie une redirection après redirection
	 */
	public function setRedirection($string)
	{
		$this->redirection = $string;
	}
	
	/*
	 * Récupére les données d'un grade dans la base
	 */
	private function getGrade()
	{
		$req_grade = "SELECT 
							* 
						FROM 
							wan_rangs 
						WHERE 
							rang_id=".$this->grade." 
						LIMIT 
							1";
		
		$res_grade = ktPDO::get()->query($req_grade);
		
		if (!$res_grade)
		{
			$this->message = "Ce grade n'existe pas";
			return FALSE;
		}
		else
		{
			$this->grade = $res_grade->fetch(PDO::FETCH_ASSOC);
			$res_grade->closeCursor();
			
			return TRUE;
		}
	}
	
	/*
	 * Récupere la liste de tous les grades
	 */
	public function getGrades()
	{
		$req = "SELECT 
					* 
				FROM 
					wan_rangs 
				WHERE 
					rang_id!=0 
				ORDER BY 
					rang_id ASC";
		
		$res = ktPDO::get()->query($req);
		
		$data = array();
		
		while ($rang = $res->fetch(PDO::FETCH_ASSOC))
			$data[] = $rang;
		
		$res->closeCursor();
		return $data;
	}
	
	/*
	 * Controle les rangs spéciaux
	 */
	public function gradeControleSpecial()
	{
		if ($this->grade['rang_id'] < 6)
			return FALSE;
		else
		{
			if ($this->_ninja->ninja['stats_rang'] == 6 AND $this->grade['rang_id'] == 7)
				return TRUE;
			else if ($this->_ninja->ninja['stats_rang'] == 7 AND $this->grade['rang_id'] == 6)
				return TRUE;
			else
				return FALSE;
		}
	}
	
	/*
	 * Vérification du ninja
	 */
	public function gradeCheckPassage()
	{
		$state = FALSE;
		$difference_rang = $this->grade['rang_id'] - $this->_ninja->ninja['stats_rang'];
		$mission_manquante = abs($this->grade['rang_nombre_mission'] - $this->_ninja->ninja['stats_mission_'.$this->grade['rang_type_mission']]);
		$niveau_manquant = $this->grade['rang_niveau'] - $this->_ninja->ninja['stats_niveau'];
		
		
		if ($this->grade['rang_liste'] == 1 AND !$this->unlock_special)
			$this->message = "Quoi ? Tu veux devenir ".$this->grade['rang_nom']." !?";
		
		else if ($this->_ninja->ninja['stats_rang'] == $this->grade['rang_id'])
			$this->message = "Tu es déjà ".$this->grade['rang_nom'];
		
		else if ($this->_ninja->ninja['stats_rang'] > $this->grade['rang_id'])
			$this->message = "Tu ne veux quand même pas redevenir ".$this->grade['rang_nom']." ?";
			
		else if ($difference_rang != 1)
			$this->message = "Tu dois passer d'autres examens avant de passer celui-ci";
		
		else if ($this->_ninja->ninja['stats_mission_'.$this->grade['rang_type_mission']] < $this->grade['rang_nombre_mission'])
			$this->message = "Il te manque ".$mission_manquante." mission(s) pour passer cet examen";
		
		else if ($this->_ninja->ninja['stats_niveau'] < $this->grade['rang_niveau'])
			$this->message = "Il te manque ".$niveau_manquant." niveau(x) pour passer cet examen";
		
		else 
			$state = TRUE;
			
		return $state;
	}
	
	/*
	 * Envoi une invitation à devenir Sennin
	 */
	public function gradeSendSennin()
	{
		$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_login'],
						  'ninja_login' => $this->_ninja->ninja['ninja_login']);

		$log = array('ninja' => $this->_ninja->ninja['ninja_login'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

		wanLog::log($log);

		wanNotify::notify($this->_ninja->ninja['ninja_id'], __CLASS__, self::gradeSendSenninNotificationParse());

		wanCombat::ajouterCombat($this->_ninja, '0', $this->_ninja->ninja['stats_id'], 1, 'sennin');
		
		return TRUE;
	}
	
	/*
	 * Calcule si le ninja va recevoir une invitation à devenir Sennin
	 */
	public function gradeProcessSennin()
	{
		if ($this->_ninja->ninjaCanSennin())
		{
			$lucky = mt_rand(1, 20);
			
			if ($lucky == 13)
			{
				$this->gradeSendSennin();
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
	
	/*
	 * Muter le ninja
	 */
	public function gradeNinjaUpdate()
	{
		if (!empty($this->grade))
		{
			if (!$this->_ninja->ninjaChangeGrade($this->grade['rang_id']))
			{
				$this->message = "Impossible de passer le grade de ".$this->grade['rang_nom'];
				return FALSE;
			}
			else
			{
				$multi = new wanMulti($this->_ninja);
				$multi->multiFileCreate();
				
				$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
								  'ninja_login' => $this->_ninja->ninja['ninja_login'],
								  'rank_id' => $this->grade['rang_id'],
								  'rank_name' => $this->grade['rang_nom']);

				$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

				wanLog::log($log);

				$notification_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'],
										   'ninja_login' => $this->_ninja->ninja['ninja_login'],
										   'rank_name' => $this->grade['rang_nom']);

				wanNotify::notify($this->_ninja->ninja['ninja_id'], __CLASS__, self::gradeNinjaUpdateNotificationParse($notification_data));

				$this->message = "Félicitations ! Te voilà ".$this->grade['rang_nom'];
				return TRUE;
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	/*
	 * Envoi le flux de redirection et termine l'execution de la classe
	 */
	public function gradeProcess()
	{
		$_SESSION['flash'] = $this->message;
		wanEngine::redirect($this->redirection);
	}

	/*
	 * Echec à l'examen
	 */
	public function testLoose()
	{
		$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 
						  'ninja_login' => $this->_ninja->ninja['ninja_login'], 
						  'rank_id' => $this->grade['rang_id'],
						  'rank_name' => $this->grade['rang_nom']);

		$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

		wanLog::log($log);

		wanNotify::notify($this->_ninja->ninja['ninja_id'], __CLASS__, self::testLooseNotificationParse($log_data));
	}

	/*
	 * LOGS PARSE
	 */
	public static function gradeSendSenninLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> reçoit une invitation Sennin';
	}

	public static function gradeNinjaUpdateLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> 
				passe au rang <strong>'.$data['rank_name'].'</strong>';
	}

	public static function testLooseLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> 
				échoue à l\'examen <strong>'.$data['rank_name'].'</strong>';
	}

	/*
	 * NOTIFY PARSE
	 */
	public static function gradeSendSenninNotificationParse()
	{
		return 'Tu as reçu une invitation à être Sennin...';
	}

	public static function gradeNinjaUpdateNotificationParse($data)
	{
		return 'Félicitations ! Te voilà <strong>'.$data['rank_name'].'</strong> !';
	}

	public static function testLooseNotificationParse($data)
	{
		return 'Dommage ! Tu as loupé l\'examen <strong>'.$data['rank_name'].'</strong> !';
	}
}