<?php

// retourne la valeur d'une competence
function ninja_return_comp($competence)
{
	global $ninja;
	
	if ($competence == 'ninjutsu')
		return $ninja->ninja['stats_ninjutsu'];
	else if ($competence == 'taijutsu')
		return $ninja->ninja['stats_taijutsu'];
	else if ($competence == 'genjutsu')
		return $ninja->ninja['stats_genjutsu'];
	else 
		return 0;
}

// retourne les infos d'une competence
function ninja_display_comp($competence, $next = FALSE)
{
	global $ninja;

	$array_belt = array(0 => array('nom' => 'Ceinture blanche', 'image' => 'belt_0.png', 'cout' => '0'), 
						1 => array('nom' => 'Ceinture jaune', 'image' => 'belt_1.png', 'cout' => '2500'),
						2 => array('nom' => 'Ceinture orange', 'image' => 'belt_2.png', 'cout' => '6000'),
						3 => array('nom' => 'Ceinture verte', 'image' => 'belt_3.png', 'cout' => '12000'),
						4 => array('nom' => 'Ceinture bleue', 'image' => 'belt_4.png', 'cout' => '20000'),
						5 => array('nom' => 'Ceinture marron', 'image' => 'belt_5.png', 'cout' => '35000'),
						6 => array('nom' => 'Ceinture noire 1ère dan', 'image' => 'belt_6.png', 'cout' => '50000'),
						7 => array('nom' => 'Ceinture noire 2ème dan', 'image' => 'belt_7.png', 'cout' => '80000'),
						8 => array('nom' => 'Ceinture noire 3ème dan', 'image' => 'belt_8.png', 'cout' => '160000'),
						9 => array('nom' => 'Ceinture noire 4ème dan', 'image' => 'belt_9.png', 'cout' => '200000'),
						10 => array('nom' => 'Ceinture noire 5ème dan', 'image' => 'belt_10.png', 'cout' => '250000'),
						11 => array('nom' => 'Ceinture rouge et blanche 6ème dan', 'image' => 'belt_11.png', 'cout' => '320000'),
						12 => array('nom' => 'Ceinture rouge et blanche 7ème dan', 'image' => 'belt_12.png', 'cout' => '400000'),
						13 => array('nom' => 'Ceinture rouge et blanche 8ème dan', 'image' => 'belt_13.png', 'cout' => '500000'),
						14 => array('nom' => 'Ceinture rouge 9ème dan', 'image' => 'belt_14.png', 'cout' => '750000'),
						15 => array('nom' => 'Ceinture rouge 10ème dan', 'image' => 'belt_15.png', 'cout' => '1000000'));
	
	$competence = 'stats_'.$competence;
	
	if ($next)
	{
		$key = $ninja->ninja[$competence] + 1;
		
		if (array_key_exists($key, $array_belt))
			return $array_belt[$key];
		else
			return FALSE;
	}
	else
		return $array_belt[$ninja->ninja[$competence]];
}

// vérifie qu'un ninja puisse progresser dans un domaine
function ninja_can_progress($competence)
{
	global $ninja;
	
	if (!empty($competence))
	{
		if (($ninja->ninja['stats_'.$competence] + 1) <= 15)
		{
			if ($competence == 'ninjutsu')
			{
				if (($ninja->ninja['stats_ninjutsu'] + 1) <= 6)
					return TRUE;
				else if ($ninja->ninja['stats_taijutsu'] <= 6 AND $ninja->ninja['stats_genjutsu'] <= 6)
					return TRUE;
				else
					return FALSE;
			}
			else if ($competence == 'taijutsu')
			{
				if (($ninja->ninja['stats_taijutsu'] + 1) <= 6)
					return TRUE;
				else if ($ninja->ninja['stats_ninjutsu'] <= 6 AND $ninja->ninja['stats_genjutsu'] <= 6)
					return TRUE;
				else
					return FALSE;
			}
			else if ($competence == 'genjutsu')
			{
				if (($ninja->ninja['stats_genjutsu'] + 1) <= 6)
					return TRUE;
				else if ($ninja->ninja['stats_ninjutsu'] <= 6 AND $ninja->ninja['stats_taijutsu'] <= 6)
					return TRUE;
				else
					return FALSE;
			}
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	else
		return FALSE;
}

?>