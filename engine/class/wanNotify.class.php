<?php

class wanNotify 
{
	static private $locked_types = array('wanCombat', 'wanBoite');

	static public function notify($ninja, $type, $content)
	{
		$request = "INSERT DELAYED  
						wan_notifications (notification_ninja, notification_type, notification_content, notification_created_on) 
					VALUES 
					(
						".ktPDO::get()->quote($ninja).",
						".ktPDO::get()->quote($type).",
						".ktPDO::get()->quote($content).",
						".ktPDO::get()->quote(time())."
					)";
	
		$result = ktPDO::get()->exec($request);

		if ($result === 1)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	static public function notifyBatch($data)
	{
		$time = time();

		$request = "INSERT DELAYED 
						wan_notifications (notification_ninja, notification_type, notification_content, notification_created_on) 
					VALUES ";

		foreach ($data as $key => $value)
		{
			$request .= "(
							".ktPDO::get()->quote($value['ninja']).",
							".ktPDO::get()->quote($value['type']).",
							".ktPDO::get()->quote($value['content']).",
							".ktPDO::get()->quote($time)."
						),";
		}

		$request = substr($request, 0, -1);

		$result = ktPDO::get()->exec($request);

		return $result >= 1;
	}

	static public function count_news($ninja_id)
	{
		$request = "SELECT 
						notification_created_on, notification_received_on, notification_ninja, notification_type   
					FROM 
						wan_notifications 
					WHERE 
						notification_ninja = ".ktPDO::get()->quote($ninja_id)." 
						AND 
						notification_received_on = 0
					ORDER BY 
						notification_created_on DESC";

		$result = ktPDO::get()->query($request);

		if (!$result OR $result->rowCount() < 1)
		{
			$n['total'] = 0;
			$n['count'] = 0;
			$n['boite'] = 0;
			$n['arene'] = 0;
			$n['last'] = time();
		}
		else
		{
			$n = array('total' => 0, 'count' => 0, 'boite' => 0, 'arene' => 0, 'last' => 0);

			while ($return = $result->fetch(PDO::FETCH_ASSOC))
			{
				if ($return['notification_type'] == 'wanBoite')
				{
					$n['boite']++;
				}
				else if ($return['notification_type'] == 'wanCombat')
				{
					$n['arene']++;
				}
				else
				{
					$n['count']++;
				}
				$n['total']++;

				if ($n['last'] < $return['notification_created_on'])
				{
					$n['last'] = $return['notification_created_on'];
				}
			}

			$result->closeCursor();
		}

		return $n;
	}

	static public function check_news($ninja_id, $last_timestamp, $constraint_type = FALSE)
	{
		$request = "UPDATE 
						wan_notifications 
					SET 
						notification_received_on = ".ktPDO::get()->quote(time())." 
					WHERE 
						notification_created_on <= ".ktPDO::get()->quote($last_timestamp)." 
						AND 
						notification_received_on = 0 
						AND 
						notification_ninja = ".ktPDO::get()->quote($ninja_id)."";

		$request .= self::_sql_constraint_type($constraint_type);

		$result = ktPDO::get()->exec($request);

		return $result > 0 ? true : false;
	}

	static private function _sql_constraint_type($constraint_type = false)
	{
		$output = ' AND notification_type '; 

		if (in_array($constraint_type, self::$locked_types) AND $constraint_type !== FALSE)
		{
			$output .= ' IN (\''.$constraint_type.'\'';
		}
		else
		{
			$output .= 'NOT IN (';

			foreach (self::$locked_types as $key => $value)
			{
				$output .= '\''.$value.'\',';
			}

			$output = substr($output, 0, -1);
		}

		$output .= ') ';

		return $output;
	}

	static public function get($ninja_id, $limit = 10)
	{
		$request = "SELECT 
						*  
					FROM 
						wan_notifications 
					WHERE 
						notification_ninja = ".ktPDO::get()->quote($ninja_id)." ";

		$request .= self::_sql_constraint_type(FALSE);

		$request .=	"ORDER BY 
						notification_created_on DESC 
					LIMIT 
						".$limit."";

		$result = ktPDO::get()->query($request);

		if ($result !== FALSE AND $result->rowCount() > 0)
		{
			$notifications = array();

			while ($return = $result->fetch(PDO::FETCH_ASSOC))
			{
				$return['icone'] = strtolower(substr($return['notification_type'], 3));
				$notifications[] = $return;
			}

			$result->closeCursor();

			return $notifications;
		}
		else
		{
			return FALSE;
		}
	}
}