<?php

class wanAkatsuki
{
	// instance du ninja
	private $_ninja;
	
	// redirection après redirection
	private $redirection;
	
	// message affiché avec la redirection
	private $advert;
	
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
	 * Initialise un ninja et vérifie qu'il peut devenir membre de l'Akatsuki
	 */
	public function recrutementCheckNinja($ninja_id)
	{
		$ninja = new wanNinja($ninja_id, true);
		
		if ($ninja->ninjaCanNukenin())
			return TRUE;
		else
			return FALSE;
	}
	
	/*
	 * Retourne la liste des ninjas susceptibles d'être recrutés dans l'Akatsuki
	 */
	public function recrutementGetNinja()
	{
		$req = "SELECT 
					ninja_id, ninja_login, 
					stats_id, stats_niveau, stats_rang, stats_village, stats_clan, 
					village_id, village_nom, 
					clan_id, clan_nom
				FROM 
					wan_ninja 
						LEFT JOIN 
							wan_stats ON wan_ninja.ninja_id = wan_stats.stats_id 
						LEFT JOIN 
							wan_villages ON wan_villages.village_id = wan_stats.stats_village 
						LEFT JOIN 
							wan_clan ON wan_clan.clan_id = wan_stats.stats_clan 
				WHERE 
					wan_stats.stats_niveau >= 125 
					AND 
					wan_stats.stats_rang = 5";
					
		$res = ktPDO::get()->query($req);
		
		if (!$res)
		{
			return FALSE;
		}
		else
		{
			$out = array();
			
			while ($ninja = $res->fetch(PDO::FETCH_ASSOC))
			{
				if ($ninja['stats_clan'] != '0')
					$ninja['ninja_login'] .= ' '.$ninja['clan_nom'];
	
				$out[$ninja['ninja_id']] = $ninja;
			}
			
			$res->closeCursor();
			
			if (count($out) > 0)
				return $out;
			else
				return FALSE;
		}
	}
	
	/*
	 * Retourne la liste des ninjas susceptibles d'être recrutés dans l'Akatsuki
	 */
	public function akatsukiGetNinja()
	{
		$req = "SELECT 
					ninja_id, ninja_login, 
					stats_id, stats_niveau, stats_rang, stats_village, stats_clan, 
					village_id, village_nom, 
					clan_id, clan_nom
				FROM 
					wan_ninja 
						LEFT JOIN 
							wan_stats ON wan_ninja.ninja_id = wan_stats.stats_id 
						LEFT JOIN 
							wan_villages ON wan_villages.village_id = wan_stats.stats_village 
						LEFT JOIN 
							wan_clan ON wan_clan.clan_id = wan_stats.stats_clan
				WHERE 
					wan_stats.stats_rang = 6 
				ORDER BY 
					wan_stats.stats_niveau DESC";
					
		$res = ktPDO::get()->query($req);
		
		if (!$res)
		{
			return FALSE;
		}
		else
		{
			$out = array();
			
			while ($ninja = $res->fetch(PDO::FETCH_ASSOC))
			{
				if ($ninja['stats_clan'] != '0')
					$ninja['ninja_login'] .= ' '.$ninja['clan_nom'];
	
				$out[$ninja['ninja_id']] = $ninja;
			}
			
			$res->closeCursor();
			
			if (count($out) > 0)
				return $out;
			else
				return FALSE;
		}
	}
	
	/*
	 * Envoi un recrutement à un ninja via son ID
	 */
	public function recrutementSendNinja($ninja_id)
	{
		$this->redirection = "index.php?page=akatsuki&mode=recrutement";
		
		wanCombat::ajouterCombat($this->_ninja, '0', $ninja_id, 1, 'nukenin');
		$recrue = new wanNinja($ninja_id, TRUE);

		$log_data = array('receiver_id' => $ninja_id, 
						'receiver_login' => $recrue->ninja['ninja_login'], 
						'sender_id' => $this->_ninja->ninja['stats_id'],
						'sender_login' => $this->_ninja->ninja['ninja_login']);

		$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 
					'ninja' => $this->_ninja->ninja['stats_id'], 'data' => $log_data);
		
		wanLog::log($log);

		wanNotify::notify($ninja_id, __CLASS__, self::recrutementSendNinjaNotificationParse());
		
		return TRUE;
	}

	/*
	 * LOGS PARSE
	 */
	public static function recrutementSendNinjaLogParse($data)
	{
		return '<a href="'.$data['sender_id'].'">'.$data['sender_login'].'</a> envoi un recrutement Akatsuki à 
				<a href="'.$data['receiver_id'].'">'.$data['receiver_login'].'</a>';
	}

	/*
	 * NOTIFICATION PARSE
	 */
	public static function recrutementSendNinjaNotificationParse()
	{
		return 'Tu as reçu une invitation de l\'Akatsuki, rend toi dans l\'<a href="index.php?page=arene">arène</a>...';
	}
}

?>