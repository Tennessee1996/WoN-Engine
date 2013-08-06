<?php

class wanRealtime
{
	public $_ninja;
	public $time;
	public $last;
	public $diff;

	public function __construct($_ninja)
	{
		if ($_ninja instanceof wanNinja)
		{
			$this->_ninja = $_ninja;
			$this->time = time();
			$this->last = $this->_ninja->ninja['ninja_last_updated'];
		}
	}

	public function controlVictuals()
	{
		$this->time_elapsed = $this->time - $this->_ninja->ninja['ninja_last_updated'];

		if ($this->last == 0)
		{
			$this->_updateLastUpdated();
		}
		else
		{
			if ($this->time > $this->last)
			{
				if ($this->_hourDiff() >= 1)
				{
					$this->_updateVictuals();
					$this->_updateLastUpdated();

					$this->_ninja->ninja['ninja_last_updated'] = $this->time;

					return TRUE;
				}
			}
		}

		return FALSE;
	}

	private function _updateVictuals()
	{
		$ninja_faim = $this->_ninja->ninja['stats_faim'];
		$ninja_soif = $this->_ninja->ninja['stats_soif'];

		$punctured_faim = $this->diff;
		$punctured_soif = $this->diff;

		$remaining_faim = $ninja_faim - $punctured_faim;
		$remaining_soif = $ninja_soif - $punctured_soif;

		$request = "UPDATE 
						wan_stats 
					SET ";

		if ($remaining_faim > 1)
		{
			$request .= 'stats_faim = '.$remaining_faim;
			$this->_ninja->ninja['stats_faim'] = $remaining_faim;
		}
		else
		{
			$request .= 'stats_faim = 1';
			$this->_ninja->ninja['stats_faim'] = 1;
		}

		$request .= ', ';

		if ($remaining_soif >= 1)
		{
			$request .= 'stats_soif = '.$remaining_soif;
			$this->_ninja->ninja['stats_soif'] = $remaining_soif;
		}
		else
		{
			$request .= 'stats_soif = 1';
			$this->_ninja->ninja['stats_soif'] = 1;
		}

		$request .= " WHERE 
						stats_id = ".ktPDO::get()->quote($this->_ninja->ninja['stats_id'])." 
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		return $result == 1 ? TRUE : FALSE;
	}

	private function _hourDiff()
	{
		$hours = 0;

		while ($this->time_elapsed >= 3600)
		{
			$hours++;
			$this->time_elapsed = $this->time_elapsed - 3600;
		}

		$this->diff = $hours;

		return $this->diff;
	}

	private function _updateLastUpdated()
	{
		$request = "UPDATE 
						wan_ninja 
					SET 
						ninja_last_updated = ".ktPDO::get()->quote($this->time)." 
					WHERE 
						ninja_id = ".ktPDO::get()->quote($this->_ninja->ninja['ninja_id'])." 
					LIMIT 
						1";

		$result = ktPDO::get()->exec($request);

		return $result == 1 ? TRUE : FALSE;
	}

	public function actualiseLastUpdated()
	{
		return $this->_updateLastUpdated();
	}
}

?>