<?php

class wanHospital
{
	public $_ninja;
	public $recovery_on;
	public $malus_minutes;
	public $malus_ryos;
	public $death_reason;


	public function __construct($_ninja)
	{
		if ($_ninja instanceof wanNinja)
		{
			$this->_ninja = $_ninja;
		}
	}

	public function hospitalPageCan()
	{
		$pages = array('ninja', 'agora', 'news', 'accueil', 'kage', 'aide', 'moderation', 'profil', 'notifications', 'debuter');

		if (in_array($_GET['page'], $pages) OR 
			($_GET['page'] == 'appartement' AND $_GET['mode'] == 'boite') OR 
			($_GET['page'] == 'hopital' AND $_GET['mode'] == 'reanimation'))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function reanimationTransfer()
	{
		if (!$this->hospitalPageCan())
		{
			$_SESSION['flash'] = "Ton ninja est K.O, la partie s'arrête pour l'instant !";
			wanEngine::redirect('index.php?page=hopital&mode=reanimation');
		}
	}

	public function checkDeath()
	{
		if ($this->isDead())
		{
			$this->reanimationTransfer();
		}
		else
		{
			return FALSE;
		}
	}

	public function isDead()
	{
		if ($this->_ninja->ninja['stats_vie'] <= 0 OR 
			$this->_ninja->ninja['stats_faim'] <= 0 OR 
			$this->_ninja->ninja['stats_soif'] <= 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function malusCalculation()
	{
		$minutes = 0;
		$cost = 0;
		$reasons = array();

		if ($this->_ninja->ninja['stats_faim'] <= 0)
		{
			$minutes += 50;
			$cost += 100;
			$reasons[] = 'hunger';

		}
		if ($this->_ninja->ninja['stats_soif'] <= 0)
		{
			$minutes += 50;
			$cost += 100;
			$reasons[] = 'thirst';
		}
		if ($this->_ninja->ninja['stats_vie'] <= 0)
		{
			$minutes += round((50 + $this->_ninja->ninja['stats_vie_max']) / 60);
			$cost += round($this->_ninja->ninja['stats_vie_max'] * 1.1);
			$reasons[] = 'life';
		}

		$this->malus_minutes = $minutes > 1440 ? 1440 : $minutes;
		$this->malus_ryos = $cost > 30000 ? 30000 : $cost;
		$this->death_reason = $reasons;
		$this->recovery_on = time() + ($this->malus_minutes * 60);
	}

	public function deathReason()
	{
		$msg = 'Ton ninja s\'est évanouit par : ';

		if (in_array('thirst', $this->death_reason))
		{
			$msg .= 'déshydratation, ';
		}
		if (in_array('hunger', $this->death_reason))
		{
			$msg .= 'faim, ';
		}
		if (in_array('life', $this->death_reason))
		{
			$msg .= 'vie à zéro, ';
		}

		$msg = substr($msg, 0, -2) . '.';

		return $msg;
	}

	public function saveDeath()
	{
		$this->malusCalculation();

		$sql_death_reason = implode(',', $this->death_reason);
		$sql_recovery_on = $this->recovery_on;

		$request = "INSERT INTO 
						wan_hopital (hopital_ninja_id, hopital_death_reason, hopital_recovery_on, hopital_malus_time, hopital_malus_ryos) 
					VALUES 
						(".ktPDO::get()->quote($this->_ninja->ninja['ninja_id']).",
						 ".ktPDO::get()->quote($sql_death_reason).", 
						 ".ktPDO::get()->quote($sql_recovery_on).", 
						 ".ktPDO::get()->quote($this->malus_minutes).", 
						 ".ktPDO::get()->quote($this->malus_ryos).")";

		$result = ktPDO::get()->exec($request);

		$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 'ninja_login' => $this->_ninja->ninja['ninja_login'], 
							'malus_minutes' => $this->malus_minutes, 'malus_ryos' => $this->malus_ryos, 
							'death_reason' => $this->death_reason);

		$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);

		wanLog::log($log);

		return $result == 1;
	}

	public function deathProgress($coefficient = 100)
	{
		$start_date = $this->recovery_on - ($this->malus_minutes * 60);
		$elapsed_minutes = round((time() - $start_date) / 60);
		return round(($elapsed_minutes / $this->malus_minutes) * $coefficient);
	}

	public function deleteDeath()
	{
		$request = "DELETE FROM wan_hopital WHERE hopital_ninja_id = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])." LIMIT 1";

		$result = ktPDO::get()->exec($request);

		return $result == 1;
	}

	public function getDeath()
	{
		$request = "SELECT * FROM wan_hopital WHERE hopital_ninja_id = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])." LIMIT 1";

		$result = ktPDO::get()->query($request);

		if ($result !== FALSE AND $result->rowCount() == 1)
		{
			$death = $result->fetch(PDO::FETCH_ASSOC);

			$this->malus_minutes = $death['hopital_malus_time'];
			$this->malus_ryos = $death['hopital_malus_ryos'];
			$this->death_reason = explode(',', $death['hopital_death_reason']);
			$this->recovery_on = $death['hopital_recovery_on'];

			$result->closeCursor();

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function closeRecovery($paid = FALSE)
	{
		if ($this->recovery_on <= time() OR $paid)
		{
			$this->deleteDeath();
			$this->ninjaRecovery($paid);

			$log_data = array('ninja_id' => $this->_ninja->ninja['ninja_id'], 'ninja_login' => $this->_ninja->ninja['ninja_login'], 
								'paid' => $paid);

			$log = array('ninja' => $this->_ninja->ninja['ninja_id'], 'class' => __CLASS__, 'method' => __FUNCTION__, 'data' => $log_data);
			wanLog::log($log);

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	private function ninjaRecovery($paid = FALSE)
	{
		$request = "UPDATE 
						wan_stats 
					SET 
						stats_faim = 100, 
						stats_soif = 100, 
						stats_vie = stats_vie_max";

		if ($paid)
		{
			$request .= ", stats_ryos = stats_ryos - ".$this->malus_ryos."";
		}

		$request .= " WHERE 
						stats_id = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." 
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		$realtime = new wanRealtime($this->_ninja);
		$realtime->actualiseLastUpdated();

		return $result == 1;
	}

	/*
	 * LOGS PARSE
	 */
	public static function closeRecoveryLogParse($data)
	{
		if ($data['paid'])
		{
			return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> fini sa période de réanimation en payant les frais hôspitaliers';
		}
		else
		{
			return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> fini sa période de réanimation en laissant le temps passer';
		}
	}

	public static function saveDeathLogParse($data)
	{
		$return = '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> part en réanimation pour les raisons suivantes : ';

		if (in_array('hunger', $data['death_reason']))
		{
			$return .= 'déshydratation, ';
		}
		if (in_array('thirst', $data['death_reason']))
		{
			$return .= 'faim, ';
		}
		if (in_array('life', $data['death_reason']))
		{
			$return .= 'vie à zéro, ';
		}

		$return = substr($return, 0, -2);

		$return .= '<br /><em style="font-size:6px">Pénalité en minutes : '.$data['malus_minutes'].' - Pénalité en ryôs : '.$data['malus_ryos'].'</em>';

		return $return;
	}
}

?>