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

	$Id: inc_db_upgrade.php,v 1.46 2005/03/25 13:14:04 mlutfy Exp $
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

	if ($lcm_db_version_current < 19) {
		lcm_query("CREATE TABLE lcm_rep_filter (
			id_filter bigint(21) NOT NULL auto_increment,
			id_report bigint(21) NOT NULL default 0,
			id_field bigint(21) NOT NULL default 0,
			type varchar(255) NOT NULL default '',
			value varchar(255) NOT NULL default '',
			KEY id_report (id_report),
			KEY id_field (id_field),
			PRIMARY KEY  (id_filter))");

		lcm_query("ALTER TABLE lcm_fields
			ADD filter ENUM('none', 'date', 'number', 'text') NOT NULL DEFAULT 'none'");

		upgrade_db_version (19);
	}

	if ($lcm_db_version_current < 20) {
		// [ML] Sorry for the brutality
		lcm_query("DELETE FROM lcm_fields");

		lcm_query("INSERT INTO lcm_fields (table_name, field_name, description, enum_type, filter) VALUES
				('lcm_case',     'id_case',          'id_case',          '', 'number'),
				('lcm_case',     'title',            'title',            '', 'text'),
				('lcm_case',     'id_court_archive', 'id_court_archive', '', 'text'),
				('lcm_case',     'date_creation',    'date_creation',    '', 'date'),
				('lcm_case',     'date_assignment',  'date_assignment',  '', 'date'),
				('lcm_case',     'legal_reason',     'legal_reason',     '', 'none'),
				('lcm_case',     'alledged_crime',   'alleged_crime',    '', 'none'),
				('lcm_case',     'count(*)',         'count',            '', 'number'),
				('lcm_author',   'id_author',        'id_author',        '', 'number'),
				('lcm_author',   'id_office',        'id_office',        '', 'number'),
				('lcm_author',   'name_first',       'name_first',       '', 'text'),
				('lcm_author',   'name_middle',      'name_middle',      '', 'text'),
				('lcm_author',   'name_last',        'name_last',        '', 'text'),
				('lcm_author',   'date_creation',    'date_creation',    '', 'date'),
				('lcm_author',   'status',           'status',           '', 'text'),
				('lcm_author',   'count(*)',         'count',            '', 'number'),
				('lcm_client',   'id_client',        'id_client',        '', 'number'),
				('lcm_client',   'name_first',       'name_first',       '', 'text'),
				('lcm_client',   'name_middle',      'name_middle',      '', 'text'),
				('lcm_client',   'name_last',        'name_last',        '', 'text'),
				('lcm_client',   'date_creation',    'date_creation',    '', 'date'),
				('lcm_client',   'citizen_number',   'citizen_number',   '', 'text'),
				('lcm_client',   'civil_status',     'civil_status',     'keyword:system_kwg:civilstatus', 'number'),
				('lcm_client',   'income',           'income',           'keyword:system_kwg:income', 'number'),
				('lcm_client',   'gender',           'gender',           'list:female,male,unknown', 'text'),
				('lcm_followup', 'id_followup',      'id_followup',      '', 'number'),
				('lcm_followup', 'id_case',          'id_case',          '', 'number'),
				('lcm_followup', 'id_author',        'id_author',        '', 'number'),
				('lcm_followup', 'type',             'type',             'keyword:system_kwg:followups', 'number'),
				('lcm_followup', 'description',      'description',      '', 'none'),
				('lcm_followup', 'sumbilled',        'sumbilled',        '', 'number'),
				('lcm_followup', 'date_start',       'date_start',       '', 'date'),
				('lcm_followup', 'date_end',         'date_end',         '', 'date'),
				('lcm_followup', 'date_end - date_start', 'time_spent',  '', 'number'),
				('lcm_followup', 'count(*)',         'count',            '', 'none')");

		upgrade_db_version (20);
	}
	
	if ($lcm_db_version_current < 21) {
		lcm_query("CREATE TABLE lcm_app (
			id_app bigint(21) NOT NULL auto_increment,
			id_case bigint(21) NOT NULL default '0',
			id_author bigint(21) NOT NULL default '0',
			type varchar(255) NOT NULL default '',
			title varchar(255) NOT NULL default '',
			description text NOT NULL,
			start_time datetime NOT NULL default '0000-00-00 00:00:00',
			end_time datetime NOT NULL default '0000-00-00 00:00:00',
			reminder datetime NOT NULL default '0000-00-00 00:00:00',
			date_creation datetime NOT NULL default '0000-00-00 00:00:00',
			date_update datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (id_app),
			KEY id_case (id_case),
			KEY id_author (id_author),
			KEY type (type),
			FULLTEXT KEY title (title),
			FULLTEXT KEY description (description))");

		lcm_query("CREATE TABLE lcm_app_client_org (
			id_app bigint(21) NOT NULL default '0',
			id_client bigint(21) NOT NULL default '0',
			id_org bigint(21) NOT NULL default '0',
			KEY id_app (id_app,id_client,id_org))");
		
		lcm_query("CREATE TABLE lcm_app_fu (
			id_app bigint(21) NOT NULL default '0',
			id_followup bigint(21) NOT NULL default '0',
			relation enum('parent','child') NOT NULL default 'parent',
			KEY id_app (id_app,id_followup))");
		
		lcm_query("CREATE TABLE lcm_author_app (
			id_author bigint(21) NOT NULL default '0',
			id_app bigint(21) NOT NULL default '0',
			KEY id_author (id_author,id_app))");
		
		upgrade_db_version (21);
	}
	
	if ($lcm_db_version_current < 22) {
		lcm_query("CREATE TABLE lcm_case_attachment (
			  id_attachment bigint(21) NOT NULL auto_increment,
			  id_case bigint(21) NOT NULL default '0',
			  filename varchar(255) NOT NULL default '',
			  type varchar(255) default NULL,
			  size bigint(21) NOT NULL default '0',
			  description text,
			  content longblob NOT NULL,
			  date_attached datetime NOT NULL default '0000-00-00 00:00:00',
			  PRIMARY KEY  (id_attachment),
			  KEY id_case (id_case),
			  KEY filename (filename),
			  FULLTEXT KEY description (description))");

		upgrade_db_version (22);
	}

	if ($lcm_db_version_current < 23) {
		// Clear duplicated table lines
		$tables = array('lcm_app_client_org' => 'id_app,id_client,id_org',
				'lcm_app_fu' => 'id_app,id_followup',
				'lcm_author_app' => 'id_author,id_app',
				'lcm_case_client_org' => 'id_case,id_client,id_org',
				'lcm_case_author' => 'id_case,id_author',
				'lcm_client_org' => 'id_client,id_org',
				'lcm_rep_filters' => 'id_report,id_filter',
				'lcm_filter_conds' => 'id_filter,id_field,cond_order'
				);
		foreach ($tables as $k => $v) {
			$result = lcm_query("SELECT DISTINCT $v,count(*) as copies FROM $k GROUP BY $v");
			while ($row = lcm_fetch_array($result)) {
				if ($row['copies'] > 1) {
					$w = '';
					foreach ($row as $rk => $rv) {
						if ((!is_int($rk)) && ($rk != 'copies')) $w .= ($w ? ' AND ' : '') . "$rk=$rv";
					}
					$q = "DELETE FROM $k WHERE $w LIMIT " . ($row['copies']-1);
					lcm_query($q);
				}
			}
			lcm_query("OPTIMIZE TABLE $k");
		}

		// Create unique indexes
		lcm_query("ALTER TABLE lcm_app_client_org DROP INDEX id_app");
		lcm_query("CREATE UNIQUE INDEX uniq ON lcm_app_client_org (id_app,id_client,id_org)");
		
		lcm_query("ALTER TABLE lcm_app_fu DROP INDEX id_app");
		lcm_query("CREATE UNIQUE INDEX uniq ON lcm_app_fu (id_app,id_followup)");

		lcm_query("ALTER TABLE lcm_author_app DROP INDEX id_author");
		lcm_query("CREATE UNIQUE INDEX uniq ON lcm_author_app (id_author,id_app)");

		lcm_query("CREATE UNIQUE INDEX uniq ON lcm_case_client_org (id_case,id_client,id_org)");

		lcm_query("CREATE UNIQUE INDEX uniq ON lcm_case_author (id_case,id_author)");

		lcm_query("CREATE UNIQUE INDEX uniq ON lcm_client_org (id_client,id_org)");

		lcm_query("CREATE UNIQUE INDEX uniq ON lcm_rep_filters (id_report,id_filter)");

		lcm_query("CREATE UNIQUE INDEX uniq ON lcm_filter_conds (id_filter,id_field,cond_order)");

		upgrade_db_version (23); 
	}

	if ($lcm_db_version_current < 24) {
		// Name doesn't need to be 100% unique, but unique for a given group
		lcm_query("ALTER TABLE lcm_keyword DROP INDEX idx_kw_name");
		lcm_query("CREATE UNIQUE INDEX idx_kw_name ON lcm_keyword (id_group, name)");

		// Convert civil_status + income to varchar(255) for keywords
		// Users should not have been using this field, and if they have, the usage was wrong, sorry
		lcm_query("ALTER TABLE lcm_client CHANGE civil_status civil_status varchar(255) NOT NULL DEFAULT 'unknown'");
		lcm_query("ALTER TABLE lcm_client CHANGE income income varchar(255) NOT NULL DEFAULT 'unknown'");
		lcm_query("UPDATE lcm_client SET civil_status = 'unknown', income = 'unknown'");

		// Convert follow-up type to varchar(255) so that we can use keywords
		// This should convert without problems. Knock on wood.
		lcm_query("ALTER TABLE lcm_followup CHANGE type type varchar(255) NOT NULL DEFAULT 'other'");

		upgrade_db_version (24); 
	}

	if ($lcm_db_version_current < 25) {
		// Add case stage
		lcm_query("ALTER TABLE lcm_case ADD stage VARCHAR(255) NOT NULL AFTER status");

		upgrade_db_version (25); 
	}

	if ($lcm_db_version_current < 26) {
		// Add case stage
		lcm_query("ALTER TABLE lcm_followup ADD case_stage VARCHAR(255) NOT NULL AFTER description");

		upgrade_db_version (26); 
	}

	if ($lcm_db_version_current < 27) {
		// Add client attachments table
		lcm_query("CREATE TABLE lcm_client_attachment (
		  id_attachment bigint(21) NOT NULL auto_increment,
		  id_client bigint(21) NOT NULL default '0',
		  filename varchar(255) NOT NULL default '',
		  type varchar(255) default NULL,
		  size bigint(21) NOT NULL default '0',
		  description text,
		  content longblob NOT NULL,
		  date_attached datetime NOT NULL default '0000-00-00 00:00:00',
		  PRIMARY KEY  (id_attachment),
		  KEY id_client (id_client),
		  KEY filename (filename),
		  FULLTEXT KEY description (description))");

		upgrade_db_version (27); 
	}

	if ($lcm_db_version_current < 27) {
		// Add client attachments table
		lcm_query("CREATE TABLE lcm_client_attachment (
		  id_attachment bigint(21) NOT NULL auto_increment,
		  id_client bigint(21) NOT NULL default '0',
		  filename varchar(255) NOT NULL default '',
		  type varchar(255) default NULL,
		  size bigint(21) NOT NULL default '0',
		  description text,
		  content longblob NOT NULL,
		  date_attached datetime NOT NULL default '0000-00-00 00:00:00',
		  PRIMARY KEY  (id_attachment),
		  KEY id_client (id_client),
		  KEY filename (filename),
		  FULLTEXT KEY description (description))");

		upgrade_db_version (27); 
	}

	if ($lcm_db_version_current < 28) {
		// Add client attachments table
		lcm_query("CREATE TABLE lcm_org_attachment (
		  id_attachment bigint(21) NOT NULL auto_increment,
		  id_org bigint(21) NOT NULL default '0',
		  filename varchar(255) NOT NULL default '',
		  type varchar(255) default NULL,
		  size bigint(21) NOT NULL default '0',
		  description text,
		  content longblob NOT NULL,
		  date_attached datetime NOT NULL default '0000-00-00 00:00:00',
		  PRIMARY KEY  (id_attachment),
		  KEY id_org (id_org),
		  KEY filename (filename),
		  FULLTEXT KEY description (description))");

		upgrade_db_version (28); 
	}

	if ($lcm_db_version_current < 29) {
		lcm_query("CREATE TABLE lcm_keyword_case (
			id_entry bigint(21) NOT NULL auto_increment,
			id_case bigint(21) NOT NULL default '0',
			PRIMARY KEY (id_entry),
			KEY id_case (id_case))");

		lcm_query("CREATE TABLE lcm_keyword_client (
			id_entry bigint(21) NOT NULL auto_increment,
			id_keyword bigint(21) NOT NULL default '0',
			id_client bigint(21) NOT NULL default '0',
			PRIMARY KEY (id_entry),
			KEY id_client (id_client))");

		lcm_query("CREATE TABLE lcm_keyword_org (
			id_entry bigint(21) NOT NULL auto_increment,
			id_keyword bigint(21) NOT NULL default '0',
			id_org bigint(21) NOT NULL default '0',
			PRIMARY KEY (id_entry),
			KEY id_org (id_org))");

		lcm_query("ALTER TABLE lcm_case ADD notes text NOT NULL AFTER alledged_crime");
		lcm_query("ALTER TABLE lcm_client ADD notes text NOT NULL");

		lcm_query("ALTER TABLE lcm_org 
						ADD notes text NOT NULL default '',
						ADD court_reg text NOT NULL default '',
						ADD tax_number text NOT NULL default '',
						ADD stat_number text NOT NULL default ''");

		// Remove lcm_client.address = lcm_org.address and move to lcm_contacts
		// If no one complains, we can remove the fields at the next upgrade
		include_lcm('inc_contacts');
		$id_address = get_contact_type_id('address_main');

		lcm_query("INSERT INTO lcm_contact (type_person, id_of_person, value, type_contact)
				SELECT 'client', id_client, address, " . $id_address . " 
					FROM lcm_client
					WHERE (address IS NOT NULL AND address != '')");

		lcm_query("INSERT INTO lcm_contact (type_person, id_of_person, value, type_contact)
				SELECT 'org', id_org, address, " . $id_address . " 
					FROM lcm_org
					WHERE (address IS NOT NULL AND address != '')");

		upgrade_db_version (29); 
	}

	if ($lcm_db_version_current < 30) {
		// [ML] If no one complained, uncomment the following:
		// lcm_query("ALTER TABLE lcm_client DROP address");
		// lcm_query("ALTER TABLE lcm_org DROP address");

		// upgrade_db_version (30); 
	}

	return $log;
}

?>
