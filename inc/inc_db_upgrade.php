<?php

// Execute this file only once
if (defined("_INC_DB_UPGRADE")) return;
define("_INC_DB_UPGRADE", "1");

include('inc/inc_version.php');
include_lcm('inc_meta');
include_lcm('inc_db');

function upgrade_db_version ($version, $test = true) {
	if ($test) {
		write_meta('lcm_db_version', $version);
		lcm_log("Upgraded database to version: $version");
	} else {
		include_lcm('inc_lang');
		echo _T('install_warning_update_impossible', array('db_version' => $version));
		exit;
	}
}

function upgrade_database($old_db_version) {
	global $lcm_db_version;
	$log = "";

	// [ML] I think we still need this
	// $lcm_db_version_current = read_meta('lcm_db_version');
	$lcm_db_version_current = $old_db_version;

	//
	// Verify the rights to modify the database
	//

	include_lcm('inc_db_test');
	$alter_test_log = lcm_test_alter_table();

	if ($alter_test_log)
		return $alter_test_log;

	//
	// Upgrade the database accordingly to the current version
	//

	if ($lcm_db_version_current < 2) {
		lcm_query("ALTER TABLE lcm_case ADD public tinyint(1) DEFAULT '0' NOT NULL");
		lcm_query("ALTER TABLE lcm_case_author ADD ac_read tinyint(1) DEFAULT '1' NOT NULL,
												ADD ac_write tinyint(1) DEFAULT '0' NOT NULL,
												ADD ac_admin tinyint(1) DEFAULT '0' NOT NULL");

		upgrade_db_version (2);
	}

	if ($lcm_db_version_current < 3) {
		lcm_query("ALTER TABLE lcm_case_author ADD ac_edit tinyint(1) DEFAULT '0' NOT NULL AFTER ac_write");
		upgrade_db_version (3);
	}

	if ($lcm_db_version_current < 4) {
		lcm_query("ALTER TABLE lcm_author ALTER id_office SET DEFAULT 0");
		upgrade_db_version (4);
	}

	if ($lcm_db_version_current < 5) {
		lcm_query("ALTER TABLE lcm_case ADD pub_write tinyint(1) DEFAULT '0' NOT NULL");
		upgrade_db_version (5);
	}

	// Renames a previously unused column
	// (stores a cookie for when user forgets pass and needs reset)
	if ($lcm_db_version_current < 6) {
		lcm_query("ALTER TABLE lcm_author DROP cookie_oubli");
		lcm_query("ALTER TABLE lcm_author ADD cookie_recall char(3) default 'no' NOT NULL");
		upgrade_db_version (6);
	}

	if ($lcm_db_version_current < 7) {
		// Ahem.. the previous version was a mistake
		lcm_query("ALTER TABLE lcm_author DROP cookie_recall");
		lcm_query("ALTER TABLE lcm_author ADD cookie_recall tinytext NOT NULL");

		// For author/client/org contact book
		lcm_query("CREATE TABLE lcm_contact (
			id_contact bigint(21) NOT NULL auto_increment,
			type_person ENUM('author', 'client', 'org') DEFAULT 'author' NOT NULL,
			id_of_person bigint(21) DEFAULT '0' NOT NULL,
			value text NOT NULL,
			type_contact tinyint(2) DEFAULT 0 NOT NULL,
			PRIMARY KEY id_contact (id_contact))");

		upgrade_db_version (7);
	}

	if ($lcm_db_version_current < 8) {
		$site_address = read_meta('site_address');

		if (! $site_address) {
			global $HTTP_SERVER_VARS, $HTTP_HOST;

			// Replace www.site.net/foo/name.php -> www.site.net/foo/
			$site_address = $HTTP_SERVER_VARS['REQUEST_URI'];
			if (!$site_address) $site_address = $_ENV['PHP_SELF']; // [ML] unsure
			$site_address = preg_replace("/\/[^\/]+\.php$/", "/", $site_address);
			$site_address = 'http://' . $HTTP_HOST /* $GLOBALS['SERVER_NAME'] */ . $site_address;

			write_meta('site_address', $site_address);
		}

		// Added 'trash' and 'suspended'
		lcm_query("ALTER TABLE lcm_author
			CHANGE status status ENUM('admin', 'normal', 'external', 'trash', 'waiting', 'suspended')
			DEFAULT 'normal' NOT NULL");

		upgrade_db_version (8);
	}


	if ($lcm_db_version_current < 10) {
		$query = "CREATE TABLE lcm_keyword (
			id_keyword bigint(21) NOT NULL auto_increment,
			id_group bigint(21) NOT NULL DEFAULT 0,
			name VARCHAR(255) NOT NULL,
			title text NOT NULL DEFAULT '',
			description text NOT NULL DEFAULT '',
			ac_author ENUM('Y', 'N') NOT NULL DEFAULT 'Y',
			PRIMARY KEY (id_keyword))";
		$result = lcm_query($query);

		$query = "CREATE UNIQUE INDEX idx_kw_name ON lcm_keyword (name)";
		$result = lcm_query($query);

		$query = "CREATE TABLE lcm_keyword_group (
			id_group bigint(21) NOT NULL auto_increment,
			name VARCHAR(255) NOT NULL,
			title text NOT NULL DEFAULT '',
			description text NOT NULL DEFAULT '',
			type ENUM('system', 'case', 'followup', 'client', 'org', 'author'),
			policy ENUM('optional', 'recommended', 'mandatory') DEFAULT 'optional',
			suggest text NOT NULL DEFAULT '',
			quantity ENUM('one', 'many') DEFAULT 'one',
			ac_admin ENUM('Y', 'N') DEFAULT 'Y',
			ac_author ENUM('Y', 'N') DEFAULT 'Y',
			PRIMARY KEY (id_group))";
		$result = lcm_query($query);

		$query = "CREATE UNIQUE INDEX idx_kwg_name ON lcm_keyword_group (name)";
		$result = lcm_query($query);

		global $system_keyword_groups;
		$system_keyword_groups = array();

		include_lcm('inc_keywords_default');
		create_groups($system_keyword_groups);

		upgrade_db_version (10);
	}

	if ($lcm_db_version_current < 11) {
		write_metas(); // forgotten at last upgrade
		read_metas(); // make sure they are loaded

		global $system_kwg;
		$type_email = $system_kwg['contacts']['keywords']['email_main']['id_keyword'];

		$query = "UPDATE lcm_contact
					SET type_contact = $type_email
					WHERE type_contact = 1";
		$result = lcm_query($query);

		upgrade_db_version (11);
	}

	// [ML] Was for db version 9, but it had a bug in the query
	// + added 'unknown' to the ENUM + set as default
	if ($lcm_db_version_current < 12) {
		lcm_query("ALTER TABLE lcm_client ADD gender ENUM('female', 'male', 'unknown') DEFAULT 'unknown' NOT NULL");
		upgrade_db_version (12);
	}

	if ($lcm_db_version_current < 13) {
		lcm_query("CREATE TABLE lcm_report (
			id_report bigint(21) NOT NULL auto_increment,
			title varchar(255) NOT NULL default '',
			id_author bigint(21) NOT NULL default '0',
			date_creation datetime NOT NULL default '0000-00-00 00:00:00',
			date_update datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (id_report),
			KEY id_author (id_author))");
		lcm_query("CREATE TABLE lcm_fields (
			id_field bigint(21) NOT NULL auto_increment,
			table_name varchar(255) NOT NULL default '',
			field_name varchar(255) NOT NULL default '',
			description varchar(255) NOT NULL default '',
			PRIMARY KEY  (id_field))");
		lcm_query("REPLACE INTO lcm_fields VALUES (1, 'lcm_case', 'title', 'Case: Title'),
											(2, 'lcm_case', 'id_court_archive', 'Case: Court archive ID'),
											(3, 'lcm_case', 'date_creation', 'Case: Creation date'),
											(4, 'lcm_case', 'date_assignment', 'Case: Assignment date'),
											(5, 'lcm_case', 'legal_reason', 'Case: Legal reason'),
											(6, 'lcm_case', 'alledged_crime', 'Case: Alleged crime'),
											(7, 'lcm_author', 'name_first', 'Author: First name'),
											(8, 'lcm_author', 'name_middle', 'Author: Middle name'),
											(9, 'lcm_author', 'name_last', 'Author: Last name'),
											(10, 'lcm_author', 'date_creation', 'Author: Date created'),
											(11, 'lcm_author', 'date_update', 'Author: Date updated')");
		lcm_query("CREATE TABLE lcm_filter (
			id_filter bigint(21) NOT NULL auto_increment,
			title varchar(255) NOT NULL default '',
			type enum('AND','OR') NOT NULL default 'AND',
			id_author bigint(21) NOT NULL default '0',
			date_creation datetime NOT NULL default '0000-00-00 00:00:00',
			date_update datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (id_filter),
			KEY id_author (id_author))");
// [AG] Removed due to error in field name ('order')
/*		lcm_query("CREATE TABLE lcm_rep_cols (
			id_column bigint(21) NOT NULL auto_increment,
			id_report bigint(21) NOT NULL default '0',
			id_field bigint(21) NOT NULL default '0',
			order bigint(21) NOT NULL default '0',
			header varchar(255) NOT NULL default '',
			sort enum('asc','desc') default NULL,
			total tinyint(1) NOT NULL default '0',
			group enum('COUNT','SUM') default NULL,
			PRIMARY KEY  (id_column),
			KEY id_report (id_report),
			KEY id_field (id_field),
			KEY order (order))");	*/
		lcm_query("CREATE TABLE lcm_rep_filters (
			id_report bigint(21) NOT NULL default '0',
			id_filter bigint(21) NOT NULL default '0',
			type enum('AND','OR') NOT NULL default 'AND',
			KEY id_report (id_report),
			KEY id_filter (id_filter))");
// [AG] Removed due to error in field name ('order')
/*		lcm_query("CREATE TABLE lcm_filter_conds (
			id_filter bigint(21) NOT NULL default '0',
			id_field bigint(21) NOT NULL default '0',
			order bigint(21) NOT NULL default '0',
			type tinyint(2) NOT NULL default '0',
			value varchar(255) default NULL,
			KEY id_filter (id_filter),
			KEY id_field (id_field),
			KEY order (order))");	*/
		upgrade_db_version (13);
	}

	if ($lcm_db_version_current < 14) {
		lcm_query("CREATE TABLE lcm_rep_cols (
			id_column bigint(21) NOT NULL auto_increment,
			id_report bigint(21) NOT NULL default '0',
			id_field bigint(21) NOT NULL default '0',
			col_order bigint(21) NOT NULL default '0',
			header varchar(255) NOT NULL default '',
			sort enum('asc','desc') default NULL,
			total tinyint(1) NOT NULL default '0',
			col_group enum('COUNT','SUM') default NULL,
			PRIMARY KEY  (id_column),
			KEY id_report (id_report),
			KEY id_field (id_field),
			KEY col_order (col_order))");
		lcm_query("CREATE TABLE lcm_filter_conds (
			id_filter bigint(21) NOT NULL default '0',
			id_field bigint(21) NOT NULL default '0',
			cond_order bigint(21) NOT NULL default '0',
			type tinyint(2) NOT NULL default '0',
			value varchar(255) default NULL,
			KEY id_filter (id_filter),
			KEY id_field (id_field),
			KEY cond_order (cond_order))");
		upgrade_db_version (14);
	}

/* [ML] I'm leaving this because it can provide us with interesting ideas
	if ($lcm_version_current < 0.999) {
		global $htsalt;
		lcm_query("ALTER TABLE spip_auteurs CHANGE pass pass tinyblob NOT NULL");
		lcm_query("ALTER TABLE spip_auteurs ADD htpass tinyblob NOT NULL");
		$query = "SELECT id_auteur, pass FROM spip_auteurs WHERE pass!=''";
		$result = lcm_query($query);
		while (list($id_auteur, $pass) = spip_fetch_array($result)) {
			$htpass = generer_htpass($pass);
			$pass = md5($pass);
			lcm_query("UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=$id_auteur");
		}
		upgrade_version (0.999);
	}

	if ($lcm_version_current < 1.414) {
		// Forum par defaut "en dur" dans les spip_articles
		// -> non, prio (priori), pos (posteriori), abo (abonnement)
		include_ecrire ("inc_meta.php3");
		$accepter_forum = substr(read_meta("forums_publics"),0,3) ;
		$query = "ALTER TABLE spip_articles CHANGE accepter_forum accepter_forum CHAR(3) NOT NULL";
		$result = lcm_query($query);
		$query = "UPDATE spip_articles SET accepter_forum='$accepter_forum' WHERE accepter_forum != 'non'";
		$result = lcm_query($query);
		upgrade_version (1.414);
	}

	if ($lcm_version_current < 1.418) {
		$query = "SELECT * FROM spip_auteurs WHERE statut = 'admin' AND email != '' ORDER BY id_auteur LIMIT 0,1";
		$result = lcm_query($query);
		if ($webmaster = spip_fetch_array($result)) {
			include_ecrire("inc_meta.php3");
			ecrire_meta('email_webmaster', $webmaster['email']);
			ecrire_metas();
		}
		upgrade_version (1.418);
	}

	if ($lcm_version_current < 1.459) {
		$result = lcm_query("SELECT type FROM spip_mots GROUP BY type");
		while ($row = spip_fetch_array($result)) {
			$type = addslashes($row['type']);
			$res = lcm_query("SELECT * FROM spip_groupes_mots
				WHERE titre='$type'");
			if (spip_num_rows($res) == 0) {
				lcm_query("INSERT IGNORE INTO spip_groupes_mots
					(titre, unseul, obligatoire, articles, breves, rubriques, syndic, admin, 1comite, 6forum)
					VALUES ('$type', 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
				if ($id_groupe = spip_insert_id())
					lcm_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type='$type'");
			}
		}
		lcm_query("UPDATE spip_articles SET popularite=0");
		upgrade_version (1.459);
	}

	if ($lcm_version_current < 1.460) {
		// remettre les mots dans les groupes dupliques par erreur
		// dans la precedente version du paragraphe de maj 1.459
		// et supprimer ceux-ci
		$result = lcm_query("SELECT * FROM spip_groupes_mots ORDER BY id_groupe");
		while ($row = spip_fetch_array($result)) {
			$titre = addslashes($row['titre']);
			if (! $vu[$titre] ) {
				$vu[$titre] = true;
				$id_groupe = $row['id_groupe'];
				lcm_query ("UPDATE spip_mots SET id_groupe=$id_groupe WHERE type='$titre'");
				lcm_query ("DELETE FROM spip_groupes_mots WHERE titre='$titre' AND id_groupe<>$id_groupe");
			}
		}
		upgrade_version (1.460);
	}

	if ($lcm_version_current < 1.462) {
		lcm_query("UPDATE spip_types_documents SET inclus='embed' WHERE inclus!='non' AND extension IN ".
			"('aiff', 'asf', 'avi', 'mid', 'mov', 'mp3', 'mpg', 'ogg', 'qt', 'ra', 'ram', 'rm', 'swf', 'wav', 'wmv')");
		upgrade_version (1.462);
	}

	if ($lcm_version_current < 1.463) {
		lcm_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE");
		lcm_query("ALTER TABLE spip_visites_temp ADD maj TIMESTAMP");
		lcm_query("ALTER TABLE spip_referers_temp ADD maj TIMESTAMP");
		upgrade_version (1.463);
	}

	// l'upgrade < 1.462 ci-dessus etait fausse, d'ou correctif
	if (($lcm_version_current < 1.464) AND ($lcm_version_current >= 1.462)) {
		$res = lcm_query("SELECT id_type, extension FROM spip_types_documents WHERE id_type NOT IN (1,2,3)");
		while ($row = spip_fetch_array($res)) {
			$extension = $row['extension'];
			$id_type = $row['id_type'];
			lcm_query("UPDATE spip_documents SET id_type=$id_type
				WHERE fichier like '%.$extension'");
		}
		upgrade_version (1.464);
	}

	if ($lcm_version_current < 1.604) {
		lcm_query("ALTER TABLE spip_auteurs ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		$u = lcm_query("SELECT * FROM spip_auteurs WHERE prefs LIKE '%spip_lang%'");
		while ($row = spip_fetch_array($u)) {
			$prefs = unserialize($row['prefs']);
			$l = $prefs['spip_lang'];
			unset ($prefs['spip_lang']);
			lcm_query ("UPDATE spip_auteurs SET lang='".addslashes($l)."',
				prefs='".addslashes(serialize($prefs))."'
				WHERE id_auteur=".$row['id_auteur']);
		}
		upgrade_version (1.604, lcm_query("SELECT lang FROM spip_auteurs"));
	}

	if ($lcm_version_current < 1.702) {
		lcm_query("ALTER TABLE spip_articles ADD extra longblob NULL");
		lcm_query("ALTER TABLE spip_auteurs ADD extra longblob NULL");
		lcm_query("ALTER TABLE spip_breves ADD extra longblob NULL");
		lcm_query("ALTER TABLE spip_rubriques ADD extra longblob NULL");
		lcm_query("ALTER TABLE spip_mots ADD extra longblob NULL");

		// recuperer les eventuels 'supplement' installes en 1.701
		if ($lcm_version_current == 1.701) {
			lcm_query ("UPDATE spip_articles SET extra = supplement");
			lcm_query ("ALTER TABLE spip_articles DROP supplement");
			lcm_query ("UPDATE spip_auteurs SET extra = supplement");
			lcm_query ("ALTER TABLE spip_auteurs DROP supplement");
			lcm_query ("UPDATE spip_breves SET extra = supplement");
			lcm_query ("ALTER TABLE spip_breves DROP supplement");
			lcm_query ("UPDATE spip_rubriques SET extra = supplement");
			lcm_query ("ALTER TABLE spip_rubriques DROP supplement");
			lcm_query ("UPDATE spip_mots SET extra = supplement");
			lcm_query ("ALTER TABLE spip_mots DROP supplement");
		}
		upgrade_version (1.702,
			lcm_query("SELECT extra FROM spip_articles")
			&& lcm_query("SELECT extra FROM spip_auteurs")
			&& lcm_query("SELECT extra FROM spip_breves")
			&& lcm_query("SELECT extra FROM spip_rubriques")
			&& lcm_query("SELECT extra FROM spip_mots")
			);
	}

	if ($lcm_version_current < 1.734) {
		// integrer nouvelles tables auxiliaires du compilateur ESJ
		creer_base();
		upgrade_version(1.734);
	}

	if ($lcm_version_current < 1.801) {
		lcm_query("ALTER TABLE spip_rubriques
			ADD statut_tmp VARCHAR(10) NOT NULL,
			ADD date_tmp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		include_ecrire('inc_rubriques.php3');
		calculer_rubriques();
		upgrade_version(1.801);
	}
*/

	return $log;
}

?>
