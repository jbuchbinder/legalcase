<?php

if (defined("_ECRIRE_INC_BASE")) return;
define("_ECRIRE_INC_BASE", "1");

include_lcm('inc_access');


function create_database() {
	//
	// Main objects
	//

	lcm_log("creating the tables for objects", 'install');

	// * case *
	$query = "id_case bigint(21) NOT NULL auto_increment,
		title text NOT NULL,
		id_court_archive text NOT NULL,
		date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		date_assignment datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		legal_reason text NOT NULL,
		alledged_crime text NOT NULL,
		status text NOT NULL,
		PRIMARY KEY (id_case)";
	$result = lcm_create_table('case', $query);

	// * followup *
	$query = "id_followup bigint(21) NOT NULL auto_increment,
		id_case bigint(21) DEFAULT '0' NOT NULL,
		date_start datetime NOT NULL,
		date_end datetime NOT NULL,
		type ENUM('assignment', 'suspension', 'delay', 'conclusion', 'consultation', 'correspondance', 'travel', 'other') NOT NULL,
		description text NOT NULL,
		sumbilled decimal(19,4) NOT NULL,
		PRIMARY KEY (id_followup),
		KEY id_case (id_case)";
	$result = lcm_create_table('followup', $query);

	// * author *
	// XXX too many extra fields
	$query = "id_author bigint(21) NOT NULL auto_increment,
		name_first text NOT NULL,
		name_middle text NOT NULL,
		name_last text NOT NULL,
		email tinytext NOT NULL,
		date_creation datetime NOT NULL,
		date_update datetime NOT NULL,
		adress_home text NOT NULL,
		phone_office text NOT NULL,
		phone_mobile text NOT NULL,
		phone_home text NOT NULL,
		login VARCHAR(255) BINARY NOT NULL,
		pass tinytext NOT NULL,
		low_sec tinytext NOT NULL,
		status VARCHAR(255) NOT NULL,
		maj TIMESTAMP,
		pgp BLOB NOT NULL,
		htpass tinyblob NOT NULL,
		imessage VARCHAR(3) NOT NULL,
		messagerie VARCHAR(3) NOT NULL,
		alea_actuel tinytext NOT NULL,
		alea_futur tinytext NOT NULL,
		prefs tinytext NOT NULL,
		cookie_oubli tinytext NOT NULL,
		lang VARCHAR(10) DEFAULT '' NOT NULL,
		extra longblob NULL,
		PRIMARY KEY (id_author),
		KEY login (login),
		KEY status (status),
		KEY lang (lang)";
	$result = lcm_create_table('author', $query);

	// XXX TODO
	// * client *
	// * courtfinal *
	// * appelation *
	// * keyword *
	// * keyword_group *
	// * client_keywords *
	// * case_client *
	// * case_lawyer *

	//
	// Relations
	//

	lcm_log("creating the tables used for relations between objects", 'install');
	/*
	$query = "CREATE TABLE spip_auteurs_articles (
		id_auteur bigint(21) DEFAULT '0' NOT NULL,
		id_article bigint(21) DEFAULT '0' NOT NULL,
		KEY id_auteur (id_auteur),
		KEY id_article (id_article))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_auteurs_rubriques (
		id_auteur bigint(21) DEFAULT '0' NOT NULL,
		id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		KEY id_auteur (id_auteur),
		KEY id_rubrique (id_rubrique))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_auteurs_messages (
		id_auteur bigint(21) DEFAULT '0' NOT NULL,
		id_message bigint(21) DEFAULT '0' NOT NULL,
		vu CHAR(3) NOT NULL,
		KEY id_auteur (id_auteur),
		KEY id_message (id_message))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_articles (
		id_mot bigint(21) DEFAULT '0' NOT NULL,
		id_article bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot),
		KEY id_article (id_article))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_breves (
		id_mot bigint(21) DEFAULT '0' NOT NULL,
		id_breve bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot),
		KEY id_breve (id_breve))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_rubriques (
		id_mot bigint(21) DEFAULT '0' NOT NULL,
		id_rubrique bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot),
		KEY id_rubrique (id_rubrique))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_syndic (
		id_mot bigint(21) DEFAULT '0' NOT NULL,
		id_syndic bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot),
		KEY id_syndic (id_syndic))";
	$result = spip_query($query);

	$query = "CREATE TABLE spip_mots_forum (
		id_mot bigint(21) DEFAULT '0' NOT NULL,
		id_forum bigint(21) DEFAULT '0' NOT NULL,
		KEY id_mot (id_mot),
		KEY id_forum (id_forum))";
	$result = spip_query($query);
	*/


	//
	// Management of the application
	//

	$query = "name VARCHAR(255) NOT NULL,
		value VARCHAR(255) DEFAULT '',
		upd TIMESTAMP,
		PRIMARY KEY (name)";
	$result = lcm_create_table('meta', $query);

	lcm_log("LCM database initialisation complete", 'install');
}

?>
