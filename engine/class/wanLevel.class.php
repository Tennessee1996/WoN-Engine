<?php
class wanLevel
{
	public static $niveaux = array('1' => '0', 
									'2' => '850', 
									'3' => '1650', 
									'4' => '2837', 
									'5' => '3525', 
									'6' => '4219', 
									'7' => '4927', 
									'8' => '5654', 
									'9' => '6410', 
									'10' => '7200', 
									'11' => '8032', 
									'12' => '8914', 
									'13' => '9851', 
									'14' => '10853', 
									'15' => '11925', 
									'16' => '13075', 
									'17' => '14311', 
									'18' => '15638', 
									'19' => '17066', 
									'20' => '18600', 
									'21' => '20248', 
									'22' => '22018', 
									'23' => '23915', 
									'24' => '25949', 
									'25' => '28125', 
									'26' => '30451', 
									'27' => '32935', 
									'28' => '35582', 
									'29' => '38402', 
									'30' => '41400', 
									'31' => '44584', 
									'32' => '47962', 
									'33' => '51539', 
									'34' => '55325', 
									'35' => '59325', 
									'36' => '63547', 
									'37' => '67999', 
									'38' => '72686', 
									'39' => '77618', 
									'40' => '82800', 
									'41' => '88240', 
									'42' => '93946', 
									'43' => '99923', 
									'44' => '106181', 
									'45' => '112725', 
									'46' => '119563', 
									'47' => '126703', 
									'48' => '134150', 
									'49' => '141914', 
									'50' => '150000', 
									'51' => '158416', 
									'52' => '167170', 
									'53' => '176267', 
									'54' => '185717', 
									'55' => '195525', 
									'56' => '205699', 
									'57' => '216247', 
									'58' => '227174', 
									'59' => '238490', 
									'60' => '250200', 
									'61' => '262312', 
									'62' => '274834', 
									'63' => '287771', 
									'64' => '301133', 
									'65' => '314925', 
									'66' => '329155', 
									'67' => '343831', 
									'68' => '358958', 
									'69' => '374546', 
									'70' => '390600', 
									'71' => '407128', 
									'72' => '424138', 
									'73' => '441635', 
									'74' => '459629', 
									'75' => '478125', 
									'76' => '497131', 
									'77' => '516655', 
									'78' => '536702', 
									'79' => '557282', 
									'80' => '578400', 
									'81' => '600064', 
									'82' => '622282', 
									'83' => '645059', 
									'84' => '668405', 
									'85' => '692325', 
									'86' => '716827', 
									'87' => '741919', 
									'88' => '767606', 
									'89' => '793898', 
									'90' => '820800', 
									'91' => '848320', 
									'92' => '876466', 
									'93' => '905243', 
									'94' => '934661', 
									'95' => '964725', 
									'96' => '995443', 
									'97' => '1026823', 
									'98' => '1058870', 
									'99' => '1091594', 
									'100' => '1125000', 
									'101' => '1159096', 
									'102' => '1193890', 
									'103' => '1229387', 
									'104' => '1265597', 
									'105' => '1302525', 
									'106' => '1340179', 
									'107' => '1378567', 
									'108' => '1417694', 
									'109' => '1457570', 
									'110' => '1498200', 
									'111' => '1539592', 
									'112' => '1581754', 
									'113' => '1624691', 
									'114' => '1668413', 
									'115' => '1712925', 
									'116' => '1758235', 
									'117' => '1804351', 
									'118' => '1851278', 
									'119' => '1899026', 
									'120' => '1947600', 
									'121' => '1997008', 
									'122' => '2047258', 
									'123' => '2098355', 
									'124' => '2150309', 
									'125' => '2203125', 
									'126' => '2256811', 
									'127' => '2311375', 
									'128' => '2366822', 
									'129' => '2423162', 
									'130' => '2480400', 
									'131' => '2538544', 
									'132' => '2597602', 
									'133' => '2657579', 
									'134' => '2718485', 
									'135' => '2780325', 
									'136' => '2843107', 
									'137' => '2906839', 
									'138' => '2971526', 
									'139' => '3037178', 
									'140' => '3103800', 
									'141' => '3171400', 
									'142' => '3239986', 
									'143' => '3309563', 
									'144' => '3380141', 
									'145' => '3451725', 
									'146' => '3524323', 
									'147' => '3597943', 
									'148' => '3672590', 
									'149' => '3748274', 
									'150' => '3825000', 
									'151' => '3902776', 
									'152' => '3981610', 
									'153' => '4061507', 
									'154' => '4142477', 
									'155' => '4224525', 
									'156' => '4307659', 
									'157' => '4391887', 
									'158' => '4477214', 
									'159' => '4563650', 
									'160' => '4651200', 
									'161' => '4739872', 
									'162' => '4829674', 
									'163' => '4920611', 
									'164' => '5012693', 
									'165' => '5105925', 
									'166' => '5200315', 
									'167' => '5295871', 
									'168' => '5392598', 
									'169' => '5490506', 
									'170' => '5589600', 
									'171' => '5689888', 
									'172' => '5791378', 
									'173' => '5894075', 
									'174' => '5997989', 
									'175' => '6103125', 
									'176' => '6209491', 
									'177' => '6317095', 
									'178' => '6425942', 
									'179' => '6536042', 
									'180' => '6647400', 
									'181' => '6760024', 
									'182' => '6873922', 
									'183' => '6989099', 
									'184' => '7105565', 
									'185' => '7223325', 
									'186' => '7342387', 
									'187' => '7462759', 
									'188' => '7584446', 
									'189' => '7707458', 
									'190' => '7831800', 
									'191' => '7957480', 
									'192' => '8084506', 
									'193' => '8212883', 
									'194' => '8342621', 
									'195' => '8473725', 
									'196' => '8606203', 
									'197' => '8740063', 
									'198' => '8875310', 
									'199' => '9011954', 
									'200' => '9150000',
									'201' => '9463635',
									'202' => '9627544',
									'203' => '9796370',
									'204' => '9970261',
									'205' => '10149369',
									'206' => '10333850',
									'207' => '10523865',
									'208' => '10719581',
									'209' => '10921169',
									'210' => '11128804',
									'211' => '11342668',
									'212' => '11562948',
									'213' => '11789837',
									'214' => '12023532',
									'215' => '12264238',
									'216' => '12512165',
									'217' => '12767530',
									'218' => '13030556',
									'219' => '13301472',
									'220' => '13580517',
									'221' => '13867932',
									'222' => '14163970',
									'223' => '14468889',
									'224' => '14782956',
									'225' => '15106445',
									'226' => '15439638',
									'227' => '15782827',
									'228' => '16136312',
									'229' => '16500401',
									'230' => '16875413',
									'231' => '17261676',
									'232' => '17659526',
									'233' => '18069312',
									'234' => '18491391',
									'235' => '18926133',
									'236' => '19373917',
									'237' => '19835134',
									'238' => '20310188',
									'239' => '20799494',
									'240' => '21303479',
									'241' => '21822583',
									'242' => '22357261',
									'243' => '22907979',
									'244' => '23475218',
									'245' => '24059475',
									'246' => '24661259',
									'247' => '25281097',
									'248' => '25919530',
									'249' => '26577115',
									'250' => '27254429');

	private static function levelListe($level_id = NULL)
	{
		if (!empty($level_id) AND array_key_exists($level_id, self::$niveaux))
			return self::$niveaux[$level_id];
		else
			return self::$niveaux;
	}
	
	public static function levelUp($current_level, $current_xp)
	{
		$ninja_id = $_SESSION['ninja']['id'];

		$levelup_max = self::levelMax()-1;
		
		$current_level = $current_level <= $levelup_max ? $current_level + 1 : FALSE;
		
		if ($current_level != FALSE)
		{
			$next_level = self::levelListe($current_level);

			if ($current_xp >= $next_level && $current_level <= self::levelMax())
			{

				$requete_passage = "UPDATE 
										wan_stats 
									SET 
										stats_niveau = stats_niveau + 1, ";

				if ($current_level > 199)
				{
					$pa = 100;
					$requete_passage .= " stats_pa = stats_pa + 100 ";
				}
				else
				{
					$pa = 10;
					$requete_passage .= " stats_pa = stats_pa + 10 ";
				}

				$requete_passage .= "WHERE 
										stats_id = '".$ninja_id."' 
									LIMIT 
										1";

				$resultat_passage = ktPDO::get()->exec($requete_passage);

				if ($resultat_passage != 1)
				{
					return FALSE;
				}
				else
				{
					$multi = new wanMulti(new wanNinja($_SESSION['ninja']['id'], TRUE));
					$multi->multiFileCreate();

					$log_data = array('ninja_id' => $_SESSION['ninja']['id'], 
									  'ninja_login' => $_SESSION['ninja']['login'], 
									  'level_reached' => $current_level, 
									  'pa_awarded' => $pa);

					$log = array('class' => __CLASS__, 'method' => __FUNCTION__, 
								'ninja' => $_SESSION['ninja']['id'], 'data' => $log_data);

					wanLog::log($log);

					$notification_data = array('level_reached' => $current_level);

					wanNotify::notify($_SESSION['ninja']['id'], __CLASS__, self::levelUpNotificationParse($notification_data));

					wanEngine::redirect('index.php?page=ninja');
				}
			}
		}
	}

	public static function levelUpLogParse($data)
	{
		return '<a href="index.php?page=profil&id='.$data['ninja_id'].'">'.$data['ninja_login'].'</a> atteint le niveau '.$data['level_reached'].' 
				et gagne '.$data['pa_awarded'].' points d\'apprentissage';
	}
	
	public static function nextLevel($ninja)
	{
		if ($ninja['stats_niveau'] < self::levelMax())
		{
			$next_level = $ninja['stats_niveau']+1; // niveau suivant
			$next_level_xp = self::$niveaux[$next_level]; // xp requise pour le niveau suivant
			$base_level = $ninja['stats_niveau']; // niveau actuel
			$base_level_xp = self::$niveaux[$base_level]; // xp requise pour le niveau actuel
			$ninja_xp = $ninja['stats_xp']; // xp du ninja

			$xp_gap_next_base = $next_level_xp - $base_level_xp; // calcule la différence d'expérience entre deux niveaux
			$xp_gap_next_current = $next_level_xp - $ninja_xp; // calcule la différence d'expérience entre l'xp du ninja et le niveau suivant
			$xp_remaining = $xp_gap_next_base - $xp_gap_next_current; // calcule l'experience manquante pour le prochain niveau

			$percentage_accomplished = ($xp_remaining * 100) / $xp_gap_next_base;
			$percentage_accomplished = $percentage_accomplished * 2.85;
			$percentage_accomplished = round($percentage_accomplished); // calcule le pourcentage de progression
		
			return array('percentage' => $percentage_accomplished, 
						'gap' => $xp_gap_next_current, 
						'remaining' => $xp_remaining, 
						'level' => $next_level);
		}
		else
		{
			return array('percentage' => 285,
						'remaining' => 0);
		}
	}
	
	public static function levelMax()
	{
		return array_search(self::$niveaux[count(self::$niveaux)], self::$niveaux);
	}

	/*
	 * NOTIFY PARSE
	 */
	public static function levelUpNotificationParse($data)
	{
		return 'Tu as atteint le niveau '.$data['level_reached'];
	}
}

/*
	# Calcul de la moyenne des niveaux
	$niveaux = wanLevel::$niveaux;
	$max = wanLevel::levelMax();

	$moyenne = 0;
	$i = 0;

	foreach ($niveaux as $key => $value)
	{
		if ($key < 150)
		{
			continue;
		}

		$key_prev = $key - 1;
		$exp = $value - $niveaux[$key_prev];
		$moyenne += $exp;
		$i++;
		echo '* Niveau '.$key.'('.$value.') - '.$key_prev.'('.$niveaux[$key_prev].') : '.$exp.'<br />';
	}

	echo '<strong>Moyenne :</strong> '.$moyenne / $i.'<br /><hr />';
	
	# Calculs des niveaux à partir de 200
	$i = 200;
	$exp = 9150000;
	$moyenne = 150000;

	for ($i; $i <= 250; $i++)
	{
		$moyenne += $moyenne * 0.03;
		$exp = $exp + $moyenne;
		echo '\''.$i .'\' => \''. floor($exp) .'\',<br />';
	}

	exit;
*/

?>