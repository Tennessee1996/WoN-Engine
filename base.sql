-- Version du serveur: 5.5.25

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de données: `wayofninja_current`
--

-- --------------------------------------------------------

--
-- Structure de la table `wan_archives_mission`
--

CREATE TABLE `wan_archives_mission` (
  `mission_ninja` varchar(16) NOT NULL,
  `mission_id` smallint(3) unsigned NOT NULL,
  `mission_date` datetime NOT NULL,
  `mission_statut` tinyint(1) unsigned NOT NULL,
  KEY `mission_ninja` (`mission_ninja`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_arene`
--

CREATE TABLE `wan_arene` (
  `arene_id` varchar(32) NOT NULL,
  `arene_combattant` varchar(16) NOT NULL DEFAULT '0',
  `arene_adversaire` varchar(16) NOT NULL DEFAULT '0',
  `arene_datetime` datetime NOT NULL,
  `arene_gagnant` varchar(16) NOT NULL DEFAULT '0',
  `arene_gagnant_ryos` int(9) unsigned NOT NULL DEFAULT '0',
  `arene_gagnant_xp` int(9) unsigned NOT NULL DEFAULT '0',
  `arene_type` enum('normal','sennin','nukenin') NOT NULL DEFAULT 'normal',
  `arene_close` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`arene_id`),
  KEY `arene_combattant` (`arene_combattant`,`arene_adversaire`),
  KEY `arene_gagnant` (`arene_gagnant`),
  KEY `arene_adversaire` (`arene_adversaire`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_boite`
--

CREATE TABLE `wan_boite` (
  `boite_id` varchar(32) NOT NULL,
  `boite_ninja_1` varchar(16) NOT NULL DEFAULT 'system',
  `boite_ninja_2` varchar(16) NOT NULL,
  `boite_state` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `boite_date` int(10) unsigned NOT NULL,
  `boite_ninja` varchar(16) NOT NULL,
  PRIMARY KEY (`boite_id`),
  KEY `boite_envoyeur` (`boite_ninja_1`),
  KEY `boite_recepteur` (`boite_ninja_2`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_boutiques`
--

CREATE TABLE `wan_boutiques` (
  `boutique_objet` int(9) unsigned NOT NULL,
  `boutique_village` tinyint(1) unsigned NOT NULL,
  `boutique_quantite` int(9) unsigned NOT NULL DEFAULT '1',
  KEY `boutique_village` (`boutique_village`),
  KEY `boutique_objet` (`boutique_objet`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_categorie_objet`
--

CREATE TABLE `wan_categorie_objet` (
  `objet_id` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `objet_categorie` varchar(32) NOT NULL DEFAULT 'Aucune',
  KEY `objet_id` (`objet_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_clan`
--

CREATE TABLE `wan_clan` (
  `clan_id` varchar(18) NOT NULL,
  `clan_nom` varchar(12) NOT NULL,
  `clan_chef` varchar(16) NOT NULL,
  `clan_frais` int(6) unsigned NOT NULL DEFAULT '0',
  `clan_niveau` smallint(3) unsigned NOT NULL DEFAULT '1',
  `clan_statut` enum('0','1') NOT NULL DEFAULT '0',
  `clan_role` int(10) unsigned NOT NULL DEFAULT '0',
  `clan_rang` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `clan_caisse` int(9) unsigned NOT NULL DEFAULT '0',
  `clan_description` text NOT NULL,
  `clan_multi` enum('0','1') NOT NULL DEFAULT '0',
  `clan_image` varchar(23) NOT NULL DEFAULT 'default.jpg',
  PRIMARY KEY (`clan_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_clan_chests`
--

CREATE TABLE `wan_clan_chests` (
  `chest_clan_id` varchar(18) NOT NULL,
  `chest_item_id` int(9) unsigned NOT NULL,
  `chest_item_quantity` int(9) unsigned NOT NULL,
  KEY `chest_clan_id` (`chest_clan_id`,`chest_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_clan_logs`
--

CREATE TABLE `wan_clan_logs` (
  `clan_log_id` bigint(20) unsigned NOT NULL,
  `clan_id` varchar(18) NOT NULL,
  KEY `clan_stats_id` (`clan_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_clan_members`
--

CREATE TABLE `wan_clan_members` (
  `member_ninja_id` varchar(16) NOT NULL,
  `member_clan_id` varchar(18) NOT NULL,
  `member_role_id` int(10) unsigned NOT NULL DEFAULT '0',
  `member_date` int(10) unsigned NOT NULL,
  KEY `member_ninja_id` (`member_ninja_id`,`member_clan_id`,`member_role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_clan_roles`
--

CREATE TABLE `wan_clan_roles` (
  `role_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(40) NOT NULL,
  `role_permissions` blob NOT NULL,
  `role_clan_id` varchar(18) NOT NULL,
  PRIMARY KEY (`role_id`),
  KEY `role_clan_id` (`role_clan_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=56 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_comments`
--

CREATE TABLE `wan_comments` (
  `comment_id` int(9) unsigned NOT NULL AUTO_INCREMENT,
  `comment_news` smallint(4) unsigned NOT NULL,
  `comment_ninja` varchar(16) NOT NULL,
  `comment_date` int(10) unsigned NOT NULL,
  `comment_message` text NOT NULL,
  `comment_moderate` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`),
  KEY `comment_news` (`comment_news`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1994 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_commerces`
--

CREATE TABLE `wan_commerces` (
  `commerce_id` int(9) unsigned NOT NULL AUTO_INCREMENT,
  `commerce_nom` varchar(42) NOT NULL DEFAULT 'No name',
  `commerce_description` text NOT NULL,
  `commerce_prix` int(9) NOT NULL DEFAULT '10',
  `commerce_koban` smallint(3) unsigned NOT NULL DEFAULT '0',
  `commerce_effet` varchar(8) NOT NULL,
  `commerce_valeur` varchar(255) NOT NULL DEFAULT 'stats_ryos=stats_ryos',
  `commerce_rang` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `commerce_clan` varchar(18) NOT NULL DEFAULT '0',
  `commerce_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `commerce_secret` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `commerce_image` varchar(32) NOT NULL DEFAULT '0',
  PRIMARY KEY (`commerce_id`),
  KEY `commerce_type` (`commerce_type`),
  KEY `commerce_clan` (`commerce_clan`),
  KEY `commerce_secret` (`commerce_secret`),
  KEY `commerce_koban` (`commerce_koban`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=109 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_elements`
--

CREATE TABLE `wan_elements` (
  `element_id` tinyint(1) unsigned NOT NULL AUTO_INCREMENT,
  `element_nom` varchar(18) NOT NULL,
  `element_parents` varchar(3) NOT NULL DEFAULT '0',
  `element_niveau` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `element_image` varchar(24) NOT NULL,
  `element_description` varchar(255) NOT NULL,
  PRIMARY KEY (`element_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_equipement`
--

CREATE TABLE `wan_equipement` (
  `equipement_ninja_id` varchar(16) NOT NULL,
  `equipement_objet_id` int(9) unsigned NOT NULL,
  `equipement_type` varchar(16) NOT NULL,
  KEY `equipement_ninja_id` (`equipement_ninja_id`,`equipement_objet_id`),
  KEY `equipement_type` (`equipement_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_fils`
--

CREATE TABLE `wan_fils` (
  `fil_id` int(9) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `fil_parent` int(9) unsigned zerofill NOT NULL DEFAULT '000000000',
  `fil_cat` smallint(1) unsigned NOT NULL DEFAULT '0',
  `fil_name` varchar(64) NOT NULL,
  `fil_date` int(10) NOT NULL DEFAULT '0',
  `fil_message` text NOT NULL,
  `fil_ninja` varchar(16) NOT NULL,
  PRIMARY KEY (`fil_id`),
  KEY `fil_ninja` (`fil_ninja`),
  KEY `fil_parent` (`fil_parent`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=262333 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_hopital`
--

CREATE TABLE `wan_hopital` (
  `hopital_ninja_id` varchar(16) NOT NULL,
  `hopital_death_reason` varchar(64) NOT NULL,
  `hopital_recovery_on` int(10) unsigned NOT NULL DEFAULT '0',
  `hopital_malus_time` int(6) unsigned NOT NULL DEFAULT '0',
  `hopital_malus_ryos` int(6) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`hopital_ninja_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_inventaire`
--

CREATE TABLE `wan_inventaire` (
  `inventaire_ninja_id` varchar(16) NOT NULL,
  `inventaire_objet_id` int(9) unsigned NOT NULL,
  `inventaire_objet_quantite` int(9) unsigned NOT NULL,
  KEY `inventaire_objet_id` (`inventaire_objet_id`),
  KEY `inventaire_ninja_id` (`inventaire_ninja_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_lettres`
--

CREATE TABLE `wan_lettres` (
  `lettre_id` varchar(32) NOT NULL,
  `lettre_boite` varchar(32) NOT NULL,
  `lettre_ninja` varchar(16) NOT NULL DEFAULT 'system',
  `lettre_message` text NOT NULL,
  `lettre_date` int(10) unsigned NOT NULL,
  PRIMARY KEY (`lettre_id`),
  KEY `lettre_boite` (`lettre_boite`),
  KEY `lettre_ninja` (`lettre_ninja`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_logs`
--

CREATE TABLE `wan_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_class` varchar(32) NOT NULL,
  `log_method` varchar(64) NOT NULL,
  `log_ninja` varchar(16) NOT NULL,
  `log_data` blob NOT NULL,
  `log_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `log_class` (`log_class`,`log_method`,`log_ninja`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13764 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_market`
--

CREATE TABLE `wan_market` (
  `market_vente_id` varchar(32) NOT NULL,
  `market_objet_vendeur` varchar(16) NOT NULL,
  `market_objet_id` int(9) unsigned NOT NULL,
  `market_objet_quantite` int(9) unsigned NOT NULL,
  `market_objet_prix` int(10) unsigned NOT NULL,
  KEY `market_objet_vendeur` (`market_objet_vendeur`),
  KEY `market_objet_prix` (`market_objet_prix`),
  KEY `market_objet_id` (`market_objet_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_missions`
--

CREATE TABLE `wan_missions` (
  `mission_id` smallint(3) unsigned NOT NULL AUTO_INCREMENT,
  `mission_titre` varchar(32) NOT NULL,
  `mission_description` varchar(255) NOT NULL,
  `mission_gain_ryos` smallint(5) NOT NULL,
  `mission_gain_exp` smallint(5) NOT NULL,
  `mission_temps` int(5) NOT NULL,
  `mission_type` tinyint(9) NOT NULL,
  `mission_chance` smallint(3) unsigned NOT NULL DEFAULT '100',
  `mission_akatsuki` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`mission_id`),
  KEY `mission_type` (`mission_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_news`
--

CREATE TABLE `wan_news` (
  `news_id` smallint(4) unsigned NOT NULL AUTO_INCREMENT,
  `news_sujet` varchar(64) NOT NULL,
  `news_contenu` text NOT NULL,
  `news_date` int(10) unsigned zerofill NOT NULL,
  PRIMARY KEY (`news_id`),
  KEY `news_date` (`news_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=59 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_ninja`
--

CREATE TABLE `wan_ninja` (
  `ninja_account_id` varchar(16) NOT NULL,
  `ninja_id` varchar(16) NOT NULL,
  `ninja_login` varchar(12) NOT NULL,
  `ninja_password` varchar(64) NOT NULL,
  `ninja_mail` varchar(64) NOT NULL,
  `ninja_date_inscription` datetime NOT NULL,
  `ninja_ip` varchar(15) NOT NULL DEFAULT '0',
  `ninja_log_errors` tinyint(1) NOT NULL DEFAULT '0',
  `ninja_ban_statut` enum('0','1') NOT NULL DEFAULT '0',
  `ninja_ban_raison` varchar(255) NOT NULL,
  `ninja_ban_time` datetime NOT NULL,
  `ninja_modo` enum('0','1') NOT NULL DEFAULT '0',
  `ninja_admin` enum('0','1') NOT NULL DEFAULT '0',
  `ninja_last_connect` int(10) unsigned NOT NULL DEFAULT '0',
  `ninja_last_updated` int(10) unsigned NOT NULL DEFAULT '0',
  `ninja_avatar` varchar(21) NOT NULL DEFAULT 'avatar.jpg',
  PRIMARY KEY (`ninja_id`),
  KEY `ninja_modo` (`ninja_modo`),
  KEY `ninja_account_id` (`ninja_account_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_notifications`
--

CREATE TABLE `wan_notifications` (
  `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `notification_ninja` varchar(16) NOT NULL,
  `notification_type` varchar(18) NOT NULL,
  `notification_content` blob NOT NULL,
  `notification_created_on` int(10) unsigned NOT NULL,
  `notification_received_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`notification_id`),
  KEY `notification_ninja` (`notification_ninja`,`notification_created_on`,`notification_received_on`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7184 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_questionnaire`
--

CREATE TABLE `wan_questionnaire` (
  `question_id` smallint(3) NOT NULL AUTO_INCREMENT,
  `question_rang` tinyint(1) NOT NULL DEFAULT '1',
  `question_interrogation` varchar(255) CHARACTER SET latin1 NOT NULL,
  `question_reponse1_id` tinyint(1) NOT NULL DEFAULT '1',
  `question_reponse1_texte` varchar(255) CHARACTER SET latin1 NOT NULL,
  `question_reponse2_id` tinyint(1) NOT NULL DEFAULT '2',
  `question_reponse2_texte` varchar(255) CHARACTER SET latin1 NOT NULL,
  `question_reponse3_id` tinyint(1) NOT NULL DEFAULT '3',
  `question_reponse3_texte` varchar(255) CHARACTER SET latin1 NOT NULL,
  `question_reponse4_id` tinyint(1) NOT NULL DEFAULT '4',
  `question_reponse4_texte` varchar(255) CHARACTER SET latin1 NOT NULL,
  `question_reponseok_id` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`question_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=45 ;

-- --------------------------------------------------------

--
-- Structure de la table `wan_rangs`
--

CREATE TABLE `wan_rangs` (
  `rang_id` tinyint(1) unsigned NOT NULL,
  `rang_nom` varchar(32) NOT NULL,
  `rang_description` text NOT NULL,
  `rang_niveau` smallint(3) unsigned NOT NULL,
  `rang_passage` varchar(255) NOT NULL,
  `rang_liste` enum('0','1') NOT NULL DEFAULT '0',
  `rang_type_mission` tinyint(1) unsigned NOT NULL,
  `rang_nombre_mission` int(6) unsigned NOT NULL,
  PRIMARY KEY (`rang_id`),
  KEY `rang_niveau` (`rang_niveau`),
  KEY `rang_liste` (`rang_liste`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_stats`
--

CREATE TABLE `wan_stats` (
  `stats_id` varchar(16) NOT NULL,
  `stats_sexe` enum('Homme','Femme') NOT NULL DEFAULT 'Homme',
  `stats_taille` int(3) NOT NULL DEFAULT '165',
  `stats_masse` int(3) NOT NULL DEFAULT '45',
  `stats_village` enum('1','2') NOT NULL DEFAULT '1',
  `stats_ryos` int(10) unsigned NOT NULL DEFAULT '250',
  `stats_koban` smallint(5) unsigned NOT NULL DEFAULT '0',
  `stats_niveau` smallint(3) unsigned NOT NULL DEFAULT '1',
  `stats_xp` int(10) unsigned NOT NULL DEFAULT '0',
  `stats_rang` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `stats_pa` int(9) NOT NULL DEFAULT '0',
  `stats_faim` smallint(3) unsigned NOT NULL DEFAULT '100',
  `stats_soif` smallint(3) unsigned NOT NULL DEFAULT '100',
  `stats_vie` int(10) unsigned NOT NULL DEFAULT '50',
  `stats_vie_max` int(10) unsigned NOT NULL DEFAULT '50',
  `stats_chakra` int(10) unsigned NOT NULL DEFAULT '30',
  `stats_chakra_max` int(10) unsigned NOT NULL DEFAULT '30',
  `stats_carac_force` int(9) unsigned NOT NULL DEFAULT '5',
  `stats_carac_rapidite` int(9) unsigned NOT NULL DEFAULT '5',
  `stats_carac_endurance` int(9) unsigned NOT NULL DEFAULT '5',
  `stats_bonus_force` int(9) unsigned NOT NULL DEFAULT '0',
  `stats_bonus_rapidite` int(9) unsigned NOT NULL DEFAULT '0',
  `stats_bonus_endurance` int(9) unsigned NOT NULL DEFAULT '0',
  `stats_ninjutsu` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `stats_taijutsu` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `stats_genjutsu` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `stats_amulette_rapidite` enum('0','1') NOT NULL DEFAULT '0',
  `stats_amulette_guerrier` enum('0','1') NOT NULL DEFAULT '0',
  `stats_amulette_resistance` enum('0','1') NOT NULL DEFAULT '0',
  `stats_element_chuunin` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `stats_element_jounin` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `stats_element_anbu` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `stats_victoire` int(9) unsigned NOT NULL DEFAULT '0',
  `stats_defaite` int(9) unsigned NOT NULL DEFAULT '0',
  `stats_mission_0` int(6) unsigned NOT NULL DEFAULT '0',
  `stats_mission_1` int(6) unsigned NOT NULL DEFAULT '0',
  `stats_mission_2` int(6) unsigned NOT NULL DEFAULT '0',
  `stats_mission_3` int(6) unsigned NOT NULL DEFAULT '0',
  `stats_mission_4` int(6) unsigned NOT NULL DEFAULT '0',
  `stats_mission_5` int(6) unsigned NOT NULL DEFAULT '0',
  `stats_mission_id` smallint(3) unsigned NOT NULL DEFAULT '0',
  `stats_mission_date` int(10) unsigned NOT NULL DEFAULT '0',
  `stats_pass_combat` smallint(3) unsigned NOT NULL DEFAULT '0',
  `stats_combat_max` tinyint(2) NOT NULL DEFAULT '40',
  `stats_clan` varchar(18) NOT NULL DEFAULT '0',
  `stats_desc` text NOT NULL,
  PRIMARY KEY (`stats_id`),
  KEY `stats_mission_id` (`stats_mission_id`),
  KEY `stats_bonus_force` (`stats_bonus_force`,`stats_bonus_rapidite`,`stats_bonus_endurance`),
  KEY `stats_clan` (`stats_clan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_type_missions`
--

CREATE TABLE `wan_type_missions` (
  `type_mission_id` tinyint(9) NOT NULL,
  `type_mission_nom` varchar(128) NOT NULL,
  `type_mission_rang` tinyint(9) NOT NULL,
  PRIMARY KEY (`type_mission_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `wan_villages`
--

CREATE TABLE `wan_villages` (
  `village_id` tinyint(1) unsigned NOT NULL,
  `village_nom` varchar(32) NOT NULL,
  `village_description` text NOT NULL,
  `village_kage` varchar(16) NOT NULL DEFAULT '0436dc7f3f75fffd',
  `village_icone` varchar(48) NOT NULL,
  `village_close` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `village_banque` int(9) unsigned NOT NULL DEFAULT '9000000',
  `village_taxe` tinyint(2) unsigned NOT NULL DEFAULT '5',
  PRIMARY KEY (`village_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
