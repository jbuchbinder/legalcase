<?php

if (defined('_INC_DB_CREATE')) return;
define('_INC_DB_CREATE', '1');

// [ML] Is this needed?  XXX
// [AG] I don't see any reason for it.
include_lcm('inc_access');

function log_if_not_duplicate_table($errno) {
	if ($errno) {
		$error = lcm_sql_error();
		// XXX 1- If MySQL set by default in non-English, may not catch the error
		//        (needs testing, and we can simply add the regexp in _T())
		// XXX 2- PostgreSQL may have different error format.
		if (! preg_match("/.*Table.*already exists.*/", $error)) {
			return lcm_sql_error() . "\n";
		}
	}

	return "";
}

// For details on the various fields, see:
// http://www.lcm.ngo-bg.org/article2.html
//
// [AG] Upon database format change DO NOT FORGET
// to increase $lcm_db_version found in inc_version.
// Also, add the queries to apply the changes into
// upgrade_database() in inc_db_upgrade

function create_database() {
	global $lcm_db_version;
	$log = "";

	//
	// Main objects
	//

	// - DONE lcm_case
	// - DONE lcm_followup
	// - DONE lcm_author
	// - DONE lcm_client
	// - DONE lcm_org
	// - DONE lcm_client_org
	// + DONE lcm_contact
	// + TODO lcm_courtfinal
	// + TODO lcm_appelation
	// + DONE lcm_keyword
	// + TODO lcm_keyword_group
	// + TODO lcm_client_keyword
	// + TODO lcm_case_keyword
	// - DONE lcm_case_client_org
	// - DONE lcm_case_author

	lcm_log("creating the SQL tables", 'install');

	$query = "CREATE TABLE lcm_case (
		id_case bigint(21) NOT NULL auto_increment,
		title text NOT NULL,
		id_court_archive text NOT NULL,
		date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		date_assignment datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		legal_reason text NOT NULL,
		alledged_crime text NOT NULL,
		status text NOT NULL,
		public tinyint(1) DEFAULT '0' NOT NULL,
		pub_write tinyint(1) DEFAULT '0' NOT NULL,
		PRIMARY KEY (id_case))";
	$result = lcm_query($query);
	
	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_followup (
		id_followup bigint(21) NOT NULL auto_increment,
		id_case bigint(21) DEFAULT '0' NOT NULL,
		date_start datetime NOT NULL,
		date_end datetime NOT NULL,
		type ENUM('assignment', 'suspension', 'resumption', 'delay', 'conclusion', 'reopening', 'consultation', 'correspondance', 'travel', 'other') NOT NULL,
		description text NOT NULL,
		sumbilled decimal(19,4) NOT NULL,
		PRIMARY KEY (id_followup),
		KEY id_case (id_case))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	// [ML] XXX too many extra fields
	$query = "CREATE TABLE lcm_author (
		id_author bigint(21) NOT NULL auto_increment,
		id_office bigint(21) DEFAULT 0 NOT NULL,
		name_first text NOT NULL,
		name_middle text NOT NULL,
		name_last text NOT NULL,
		date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		username VARCHAR(255) BINARY NOT NULL,
		password tinytext NOT NULL,
		lang VARCHAR(10) DEFAULT '' NOT NULL,
		prefs tinytext NOT NULL,
		status ENUM('admin', 'normal', 'external', 'trash', 'waiting', 'suspended') DEFAULT 'normal' NOT NULL,
		cookie_recall tinytext NOT NULL,

		maj TIMESTAMP,
		pgp BLOB NOT NULL,
		htpass tinyblob NOT NULL,
		imessage VARCHAR(3) NOT NULL,
		messagerie VARCHAR(3) NOT NULL,
		alea_actuel tinytext NOT NULL,
		alea_futur tinytext NOT NULL,
		extra longblob NULL,

		PRIMARY KEY (id_author),
		KEY username (username),
		KEY status (status),
		KEY lang (lang))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_client (
		id_client bigint(21) NOT NULL auto_increment,
		name_first text NOT NULL,
		name_middle text NOT NULL,
		name_last text NOT NULL,
		date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		citizen_number text NOT NULL,
		address text NOT NULL,
		gender ENUM('female','male') DEFAULT 'male' NOT NULL,
		civil_status decimal(2) DEFAULT 0 NOT NULL,
		income decimal(2) DEFAULT 0 NOT NULL,
		PRIMARY KEY id_client (id_client))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_org (
		id_org bigint(21) NOT NULL auto_increment,
		name text NOT NULL,
		date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		address text NOT NULL,
		PRIMARY KEY id_org (id_org))";

	$result = lcm_query($query);
	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_contact (
		id_contact bigint(21) NOT NULL auto_increment,
		type_person ENUM('author', 'client', 'org') DEFAULT 'author' NOT NULL,
		id_of_person bigint(21) DEFAULT 0 NOT NULL,
		value text NOT NULL,
		type_contact tinyint(2) DEFAULT 0 NOT NULL,
		PRIMARY KEY id_contact (id_contact))";

	$result = lcm_query($query);
	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_keyword (
		id_keyword bigint(21) NOT NULL auto_increment,
		id_group bigint(21) NOT NULL DEFAULT 0,
		name VARCHAR(255) NOT NULL,
		title text NOT NULL DEFAULT '',
		description text NOT NULL DEFAULT '',
		ac_author ENUM('Y', 'N') NOT NULL DEFAULT 'Y',
		PRIMARY KEY (id_keyword))";
	
	$result = lcm_query($query);
	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE UNIQUE INDEX idx_kw_name ON lcm_keyword (name)";
	$result = lcm_query($query);

	$query = "CREATE TABLE lcm_keyword_group (
		id_group bigint(21) NOT NULL auto_increment,
		name VARCHAR(255) NOT NULL,
		title text NOT NULL DEFAULT '',
		description text NOT NULL DEFAULT '',
		type ENUM('system', 'case', 'followup', 'client', 'org', 'author'),
		policy ENUM('optional', 'recommended', 'mandatory') DEFAULT 'optional',
		quantity ENUM('one', 'many') DEFAULT 'one',
		suggest text NOT NULL DEFAULT '',
		ac_admin ENUM('Y', 'N') DEFAULT 'Y',
		ac_author ENUM('Y', 'N') DEFAULT 'Y',
		PRIMARY KEY (id_group))";

	$result = lcm_query($query);
	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE UNIQUE INDEX idx_kwg_name ON lcm_keyword_group (name)";
	$result = lcm_query($query);

	$query = "CREATE TABLE lcm_report (
		id_report bigint(21) NOT NULL auto_increment,
		title varchar(255) NOT NULL default '',
		id_author bigint(21) NOT NULL default '0',
		date_creation datetime NOT NULL default '0000-00-00 00:00:00',
		date_update datetime NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (id_report),
		KEY id_author (id_author))";

	$result = lcm_query($query);
	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_fields (
		id_field bigint(21) NOT NULL auto_increment,
		table_name varchar(255) NOT NULL default '',
		field_name varchar(255) NOT NULL default '',
		description varchar(255) NOT NULL default '',
		PRIMARY KEY  (id_field))";

	$result = lcm_query($query);
	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "REPLACE INTO lcm_fields VALUES (1, 'lcm_case', 'title', 'Case: Title'),
											(2, 'lcm_case', 'id_court_archive', 'Case: Court archive ID'),
											(3, 'lcm_case', 'date_creation', 'Case: Creation date'),
											(4, 'lcm_case', 'date_assignment', 'Case: Assignment date'),
											(5, 'lcm_case', 'legal_reason', 'Case: Legal reason'),
											(6, 'lcm_case', 'alledged_crime', 'Case: Alleged crime'),
											(7, 'lcm_author', 'name_first', 'Author: First name'),
											(8, 'lcm_author', 'name_middle', 'Author: Middle name'),
											(9, 'lcm_author', 'name_last', 'Author: Last name'),
											(10, 'lcm_author', 'date_creation', 'Author: Date created'),
											(11, 'lcm_author', 'date_update', 'Author: Date updated')";

	$result = lcm_query($query);
	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_filter (
		id_filter bigint(21) NOT NULL auto_increment,
		title varchar(255) NOT NULL default '',
		type enum('AND','OR') NOT NULL default 'AND',
		id_author bigint(21) NOT NULL default '0',
		date_creation datetime NOT NULL default '0000-00-00 00:00:00',
		date_update datetime NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (id_filter),
		KEY id_author (id_author))";

	$result = lcm_query($query);
	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	//
	// Relations
	//

	lcm_log("creating the tables used for relations between objects", 'install');

	$query = "CREATE TABLE lcm_case_client_org (
		id_case bigint(21) DEFAULT '0' NOT NULL,
		id_client bigint(21) DEFAULT '0' NOT NULL,
		id_org bigint(21) DEFAULT '0' NOT NULL,
		KEY id_case (id_case),
		KEY id_client (id_client),
		KEY id_org (id_org))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_case_author (
		id_case bigint(21) DEFAULT '0' NOT NULL,
		id_author bigint(21) DEFAULT '0' NOT NULL,
		ac_read tinyint(1) DEFAULT '1' NOT NULL,
		ac_write tinyint(1) DEFAULT '0' NOT NULL,
		ac_edit tinyint(1) DEFAULT '0' NOT NULL,
		ac_admin tinyint(1) DEFAULT '0' NOT NULL,
		KEY id_case (id_case),
		KEY id_author (id_author))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_client_org (
		id_client bigint(21) DEFAULT '0' NOT NULL,
		id_org bigint(21) DEFAULT '0' NOT NULL,
		KEY id_client (id_client),
		KEY id_org (id_org))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_rep_cols (
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
		KEY col_order (col_order))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_rep_filters (
		id_report bigint(21) NOT NULL default '0',
		id_filter bigint(21) NOT NULL default '0',
		type enum('AND','OR') NOT NULL default 'AND',
		KEY id_report (id_report),
		KEY id_filter (id_filter))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	$query = "CREATE TABLE lcm_filter_conds (
		id_filter bigint(21) NOT NULL default '0',
		id_field bigint(21) NOT NULL default '0',
		cond_order bigint(21) NOT NULL default '0',
		type tinyint(2) NOT NULL default '0',
		value varchar(255) default NULL,
		KEY id_filter (id_filter),
		KEY id_field (id_field),
		KEY cond_order (cond_order))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	//
	// Management of the application
	//

	$query = "CREATE TABLE lcm_meta (
		name VARCHAR(255) NOT NULL,
		value VARCHAR(255) DEFAULT '',
		upd TIMESTAMP,
		PRIMARY KEY (name))";
	$result = lcm_query($query);

	$log .= log_if_not_duplicate_table(lcm_sql_errno());

	// Set the version of the installed database
	$query = "INSERT INTO lcm_meta
				SET name='lcm_db_version',value=$lcm_db_version";
	$result = lcm_query($query);

	if (!$result) $log .= lcm_sql_error . "\n";

	lcm_log("LCM database initialisation complete", 'install');

	return $log;
}

?>
