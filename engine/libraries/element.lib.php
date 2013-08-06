<?php

// vérifie si un ninja à déjà choisi l'élement associé à un grade
function ninja_element_busy($type)
{
	global $ninja;
	
	if (!empty($type))
	{
		if ($type == 'chuunin')
		{
			if ($ninja->ninja['stats_rang'] >= 2 AND $ninja->ninja['stats_element_chuunin'] != 0)
				return TRUE;
			else 
				return FALSE;
		}
		if ($type == 'jounin')
		{
			if ($ninja->ninja['stats_rang'] >= 3 AND $ninja->ninja['stats_element_jounin'] != 0)
				return TRUE;
			else 
				return FALSE;
		}
		if ($type == 'anbu')
		{
			if ($ninja->ninja['stats_rang'] >= 5 AND $ninja->ninja['stats_element_anbu'] != 0)
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

// vérifie si le ninja possède l'élément disponible pour son grade
function ninja_element_empty($type)
{
	global $ninja;
	
	if (!empty($type))
	{
		if ($type == 'chuunin')
		{
			if ($ninja->ninja['stats_rang'] >= 2 AND $ninja->ninja['stats_element_chuunin'] == 0)
				return TRUE;
			else 
				return FALSE;
		}
		if ($type == 'jounin')
		{
			if ($ninja->ninja['stats_rang'] >= 3 AND $ninja->ninja['stats_element_jounin'] == 0)
				return TRUE;
			else 
				return FALSE;
		}
		if ($type == 'anbu')
		{
			if ($ninja->ninja['stats_rang'] >= 5 AND $ninja->ninja['stats_element_anbu'] == 0)
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

// construit un cache contenant les infos des éléments
function build_elements_cache()
{
	global $pdo;
	$file_elements = 'cache/liste_elements.html';
	
	$req_elements = "SELECT 
						* 
					FROM 
						wan_elements 
					ORDER BY 
						element_id ASC";
	
	$res_elements = $pdo->query($req_elements);
	
	while ($elements = $res_elements->fetch(PDO::FETCH_ASSOC))
		$content_elements[$elements['element_id']] = $elements;
	
	$res_elements->closeCursor();
	$content_elements = serialize($content_elements);
	file_put_contents($file_elements, $content_elements);
}

// retourne les infos d'un élément
function get_element_infos($id_element)
{
	$id_element = (int) htmlentities($id_element);
	
	if (!empty($id_element))
	{
		$file_elements = 'cache/liste_elements.html';
		
		if (file_exists($file_elements))
		{
			$liste_elements = file_get_contents($file_elements);
			$liste_elements = unserialize($liste_elements);
			
			if (array_key_exists($id_element, $liste_elements))
			{
				return $liste_elements[$id_element];
			}
			else
				return FALSE;
		}
		else
		{
			build_elements_cache();
			get_element_infos($id_element);
		}
	}
	else
		return FALSE;
}

// retourne le cache des éléments
function get_elements_cache()
{
	$file = 'cache/liste_elements.html';
	
	if (file_exists($file))
	{
		$cache = file_get_contents($file);
		$cache = unserialize($cache);
		return $cache;
	}
	else
	{
		build_elements_cache();
		get_elements_cache();
	}
}

// retourne true ou false si le ninja possède un élément
function ninja_has_element($id_element)
{
	global $ninja;
	
	$id_element = (int) $id_element;
	
	if ($ninja->ninja['stats_element_chuunin'] == $id_element)
		return TRUE;
	else if ($ninja->ninja['stats_element_jounin'] == $id_element)
		return TRUE;
	else if ($ninja->ninja['stats_element_anbu'] == $id_element)
		return TRUE;
	else
		return FALSE;
}

// retourne la liste de tous les éléments qu'un ninja peut apprendre
function ninja_can_element()
{
	global $ninja;
	
	$liste_elements = get_elements_cache();
	
	foreach ($liste_elements as $key => $value)
	{
		if (!ninja_has_element($key))
		{
			if ($value['element_niveau'] == 1)
			{
				$parents = explode('|', $value['element_parents']);
				
				if (ninja_has_element($parents[0]) AND ninja_has_element($parents[1]))
					$return_elements[$key] = $value;
			}
			else
				$return_elements[$key] = $value;
		}
	}
	
	return $return_elements;
}

// retourne le premier element manquant ex : chuunin, jounin ou anbu
function element_grade_empty()
{
	global $ninja;
	
	if ($ninja->ninja['stats_element_chuunin'] == 0)
		return 'chuunin';
	else if ($ninja->ninja['stats_element_jounin'] == 0)
		return 'jounin';
	else if ($ninja->ninja['stats_element_anbu'] == 0)
		return 'anbu';
	else
		return FALSE;
}

// 

?>