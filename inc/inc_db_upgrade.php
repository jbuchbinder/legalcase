<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2005 Free Software Foundation, Inc.

	This program is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published by the
	Free Software Foundation; either version 2 of the License, or (at your
	option) any later version.

	This program is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
	or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
	for more details.

	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
	59 Temple Place, Suite 330, Boston, MA  02111-1307, USA

	$Id: inc_db_upgrade.php,v 1.33 2005/02/07 12:27:47 mlutfy Exp $
*/

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
	// Create new keywords (if necessary)
	//

	// Do not remove, or variables won't be declared
	global $system_keyword_groups;
	$system_keyword_groups = array();

	include_lcm('inc_meta');
	include_lcm('inc_keywords_default');
	create_groups($system_keyword_groups);

	//
	// Create new meta (if necessary)
	//

	include_lcm('inc_meta_defaults');
	init_default_config();
	
	// Rewrite metas in inc/data/inc_meta_cache.php, just to be sure
	write_metas();

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

	// [ML] 15 + 16 had bugs, corrected below

	if ($lcm_db_version_current < 17) {
		lcm_query("ALTER TABLE lcm_followup CHANGE type type ENUM('assignment','suspension','resumption','delay','conclusion','reopening','merge','consultation','correspondance','travel','other') DEFAULT 'assignment' NOT NULL");
		lcm_query("ALTER TABLE lcm_followup ADD id_author bigint(21) DEFAULT '0' NOT NULL AFTER id_case");
		lcm_query("ALTER TABLE lcm_followup ADD INDEX id_author (id_author)");
		upgrade_db_version (17);
	}

	if ($lcm_db_version_current < 18) {
		lcm_query("ALTER TABLE lcm_report
				ADD description text NOT NULL DEFAULT '',
				ADD line_src_type text NOT NULL DEFAULT '',
				ADD line_src_name text NOT NULL DEFAULT '',
				ADD col_src_type text NOT NULL DEFAULT '',
				ADD col_src_name text NOT NULL DEFAULT '' ");

		lcm_query("CREATE TABLE lcm_rep_line (
				id_line bigint(21) NOT NULL auto_increment,
				id_report bigint(21) NOT NULL DEFAULT 0,
				id_field bigint(21) NOT NULL DEFAULT 0,
				sort_type ENUM('asc', 'desc') DEFAULT NULL,
				col_order bigint(21) NOT NULL DEFAULT 0,
				total tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (id_line),
				KEY id_report (id_report),
				KEY id_field (id_field),
				KEY col_order (col_order))");

		// [ML] I'm stubborn, and renaming this table to singular
		lcm_query("CREATE TABLE lcm_rep_col (
				id_column bigint(21) NOT NULL auto_increment,
				id_report bigint(21) NOT NULL default 0,
				id_field bigint(21) NOT NULL default 0,
				col_order bigint(21) NOT NULL default 0,
				header varchar(255) NOT NULL default '',
				sort enum('asc','desc') default NULL,
				total tinyint(1) NOT NULL default 0,
				col_group enum('COUNT','SUM') default NULL,
				PRIMARY KEY (id_column),
				KEY id_report (id_report),
				KEY id_field (id_field),
				KEY col_order (col_order))");

		lcm_query("INSERT INTO lcm_rep_col
					SELECT * FROM lcm_rep_cols");

		lcm_query("drop table lcm_rep_cols");

		lcm_query("ALTER TABLE lcm_fields
				ADD enum_type text NOT NULL DEFAULT ''");

		lcm_query("INSERT INTO lcm_fields (table_name, field_name, description, enum_type)
				VALUES
					('lcm_case', 'count(*)', 'COUNT(*)', ''),
					('lcm_author', 'count(*)', 'COUNT(*)', ''),
					('lcm_author', 'id_author', 'Author: ID', ''),
					('lcm_case', 'id_case', 'Case: ID', ''),
					('lcm_followup', 'type', 'Activities: Type', 'keyword:system_kwg:followups'),
					('lcm_followup', 'date_start', 'Activities: Date start', ''),
					('lcm_followup', 'date_end', 'Activities: Date end', ''),
					('lcm_followup', 'date_end - date_start', 'Activities: Time spent', ''),
					('lcm_followup', 'id_followup', 'Activities: ID', '')");

		upgrade_db_version (18);
	}

	return $log;
}

?>
