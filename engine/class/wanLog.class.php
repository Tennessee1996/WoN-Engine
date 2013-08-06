<?php

class wanLog 
{
	static public function log($data, $callback = false)
	{
		$request = "INSERT INTO 
						wan_logs (log_ninja, log_class, log_method, log_data, log_time) 
					VALUES 
					(
						".ktPDO::get()->quote($data['ninja']).",
						".ktPDO::get()->quote($data['class']).",
						".ktPDO::get()->quote($data['method']).",
						".ktPDO::get()->quote(self::_write_prep($data['data'])).",
						".ktPDO::get()->quote(time())."
					)";
	
		$result = ktPDO::get()->exec($request);

		if ($result === 1)
		{
			$id = ktPDO::get()->lastInsertId('log_id');

			if (is_object($callback))
			{
				$class = get_class($callback);

				switch ($class)
				{
					case 'wanClan' :
						$callback->clanSaveLog($id);
					break;
				}
			}

			return $id;
		}
		else
		{
			return FALSE;
		}
	}

	static public function parse($data)
	{
		$class = $data['log_class'];
		$method = $data['log_method'];
		return call_user_func($class.'::'.$method.'LogParse', $data['log_data']);
	}

	static public function parseRaw($data)
	{
		$class = $data['log_class'];
		$method = $data['log_method'];
		$data['log_data'] = self::_read_prep($data['log_data']);

		return call_user_func($class.'::'.$method.'LogParse', $data['log_data']);
	}

	static public function get($ninja_id = '', $limit = 10)
	{
		$request = "SELECT 
						* 
					FROM 
						wan_logs ";

		if (!empty($ninja_id))
		{
			$request .= "WHERE 
							log_ninja = ".ktPDO::get()->quote($ninja_id)." ";
		}

		$request .= "ORDER BY 
						log_time DESC 
					LIMIT 
						".$limit."";

		$result = ktPDO::get()->query($request);

		if ($result !== FALSE AND $result->rowCount() > 0)
		{
			$logs = array();

			while ($return = $result->fetch(PDO::FETCH_ASSOC))
			{
				$return['log_data'] = self::_read_prep($return['log_data']);
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

	static private function _write_prep($data)
	{
		return gzdeflate(serialize($data));
	}

	static private function _read_prep($data)
	{
		return unserialize(gzinflate($data));
	}

	/*
	 * COMMON LOG
	 */
	public static function commonRegisterLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> s\'inscrit sur Way of Ninja';
	}

	public static function commonManekinekoLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> utilise le code <strong>'.$data['manekineko_code'].'</strong>';
	}

	public static function commonCompetenceLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> passe la '.$data['competence_name'].' 
				en '.ucfirst($data['competence_type']);
	}

	public static function commonElementLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> obtient l\'élément '.$data['element_name'].' 
				pour le niveau '.ucfirst($data['element_level']);
	}

	public static function commonChangeNameLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> est maintenant connu sous le 
				nom de <strong>'.$data['new_name'].'</strong>';
	}

	public static function commonKobanLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> achète un bonus avec des Kobans';
	}

	public static function commonBanLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> banni le compte numéro <strong>'.$data['account_id'].'</strong>';
	}

	public static function commonTrainingLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> s\'entraine en '.ucfirst($data['training_type']).' 
				pour '.$data['training_mode'].' PA';
	}
}