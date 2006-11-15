<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

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

	$Id: inc_db_upgrade.php,v 1.74 2006/11/15 19:43:46 mlutfy Exp $
*/

// Execute this file only once
if (defined("_INC_DB_UPGRADE")) return;
define("_INC_DB_UPGRADE", "1");

include_lcm('inc_meta');
include_lcm('inc_db');

function upgrade_db_version ($version, $test = true) {
	if ($test) {
		write_meta('lcm_db_version', $version);
		lcm_log("Upgraded database to version: $version", 'upgrade');
	} else {
		// DEPRECATED ?
		include_lcm('inc_lang');
		lcm_panic(_T('install_warning_update_impossible', array('db_version' => $version)));
		exit;
	}
}

function upgrade_database_conf() {
	//
	// Create new keywords (if necessary)
	// This must be done at the end, in case keyword DB structure changed
	//

	include_lcm('inc_meta');
	include_lcm('inc_keywords_default');

	$all_default_kwgs = get_default_keywords();
	create_groups($all_default_kwgs);

	//
	// Create new meta (if necessary)
	// This must be done at the end, in case meta DB structure changed
	//

	include_lcm('inc_meta_defaults');
	init_default_config();
	
	// Rewrite metas in inc/data/inc_meta_cache.php, just to be sure
	write_metas();

	//
	// Update lcm_fields
	//
	include_lcm('inc_repfields_defaults');
	$fields = get_default_repfields();
	create_repfields($fields);
}

function upgrade_database($old_db_version) {
	$log = "";

	$lcm_db_version_current = intval($old_db_version);

	//
	// Verify the rights to modify the database
	//

	lcm_log("upgrade_database: starting, old_db_version = $lcm_db_version_current", 'upgrade');

	include_lcm('inc_db_test');
	$alter_test_log = lcm_test_alter_table();

	if ($alter_test_log)
		return $alter_test_log;

	lcm_log("upgrade_database: alter table test was OK.", 'upgrade');

	//
	// Upgrade the database accordingly to the current version
	//

	lcm_log("Starting LCM database upgrade; version = :$lcm_db_version_current:", 'upgrade');

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
			title text NOT NULL,
			description text NOT NULL,
			ac_author ENUM('Y', 'N') NOT NULL DEFAULT 'Y',
			PRIMARY KEY (id_keyword))";
		$result = lcm_query($query);

		$query = "CREATE UNIQUE INDEX idx_kw_name ON lcm_keyword (name)";
		$result = lcm_query($query);

		$query = "CREATE TABLE lcm_keyword_group (
			id_group bigint(21) NOT NULL auto_increment,
			name VARCHAR(255) NOT NULL,
			title text NOT NULL,
			description text NOT NULL,
			type ENUM('system', 'case', 'followup', 'client', 'org', 'author'),
			policy ENUM('optional', 'recommended', 'mandatory') DEFAULT 'optional',
			suggest text NOT NULL,
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
				ADD description text NOT NULL,
				ADD line_src_type text NOT NULL,
				ADD line_src_name text NOT NULL,
				ADD col_src_type text NOT NULL,
				ADD col_src_name text NOT NULL ");

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
				ADD enum_type text NOT NULL");

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
						ADD notes text NOT NULL,
						ADD court_reg text NOT NULL,
						ADD tax_number text NOT NULL,
						ADD stat_number text NOT NULL");

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
		lcm_query("ALTER TABLE lcm_keyword_group
			CHANGE type type ENUM('system','case','followup','client','org','client_org','author')");

		// in version 29, the id_entry + key was missing
		lcm_query("ALTER TABLE lcm_keyword_case
			ADD id_keyword bigint(21) NOT NULL default '0' AFTER id_entry,
			ADD KEY id_keyword (id_keyword)");

		lcm_query("ALTER TABLE lcm_keyword_client ADD KEY id_keyword (id_keyword)");
		lcm_query("ALTER TABLE lcm_keyword_org ADD KEY id_keyword (id_keyword)");

		upgrade_db_version (30); 
	}
	
	if ($lcm_db_version_current < 31) {
		lcm_query("ALTER TABLE lcm_client DROP address");
		lcm_query("ALTER TABLE lcm_org DROP address");

		// [AG] Adding id_author, date_removed and index to attached documents
		lcm_query("ALTER TABLE lcm_case_attachment	ADD id_author BIGINT(21) NOT NULL AFTER id_case,
								CHANGE content content LONGBLOB DEFAULT NULL,
								ADD date_removed DATETIME NOT NULL,
								ADD INDEX (id_author)");
		lcm_query("ALTER TABLE lcm_client_attachment	ADD id_author BIGINT(21) NOT NULL AFTER id_client,
								CHANGE content content LONGBLOB DEFAULT NULL,
								ADD date_removed DATETIME NOT NULL,
								ADD INDEX (id_author)");
		lcm_query("ALTER TABLE lcm_org_attachment	ADD id_author BIGINT(21) NOT NULL AFTER id_org,
								CHANGE content content LONGBLOB DEFAULT NULL,
								ADD date_removed DATETIME NOT NULL,
								ADD INDEX (id_author)");

		upgrade_db_version (31);
	}

	if ($lcm_db_version_current < 32) {
		// [AG] Expanding author preferences field to fit all data
		lcm_query("ALTER TABLE lcm_author CHANGE prefs prefs text NOT NULL");

		upgrade_db_version (32);
	}

	if ($lcm_db_version_current < 33) {
		lcm_query("ALTER TABLE lcm_keyword_case
					ADD id_stage bigint(21) not null default 0 AFTER id_case,
					ADD value text not null");

		upgrade_db_version (33);
	}

	if ($lcm_db_version_current < 34) {
		// Add 'stage' type
		lcm_query("ALTER TABLE lcm_keyword_group
					CHANGE type type ENUM('system','case','stage','followup','client','org','client_org','author')");

		// Used for stage court archives numbers
		lcm_query("ALTER TABLE lcm_keyword
					ADD hasvalue ENUM('Y', 'N') NOT NULL DEFAULT 'N' AFTER description");

		upgrade_db_version (34);
	}

	if ($lcm_db_version_current < 35) {
		lcm_query("ALTER TABLE lcm_fields CHANGE filter filter text NOT NULL");
		include_lcm('inc_repfields_defaults');

		$fields = get_default_repfields();
		create_repfields($fields);

		upgrade_db_version (35);
	}
	
	if ($lcm_db_version_current < 36) {
		lcm_query("ALTER TABLE lcm_report ADD notes text NOT NULL AFTER description");
		upgrade_db_version (36);
	}

	if ($lcm_db_version_current < 37) {
		// Converts the lcm_case.id_court_archive into 'court archive' keywords 
		// for the latest 'stage' of the case (if there is a court archive).
		lcm_query("INSERT INTO lcm_keyword_case (id_keyword, id_case, id_stage, value)
			SELECT kk.id_keyword as kw_court_archive,
				c.id_case, k.id_keyword as id_stage, 
				c.id_court_archive 
			FROM lcm_case as c, lcm_keyword as k 
			LEFT JOIN lcm_keyword as kk ON (kk.name = 'courtarchive')
			WHERE id_court_archive != '' AND k.name = c.stage AND c.stage != '' ");

		upgrade_db_version (37);
	}

	if ($lcm_db_version_current < 38) {
		lcm_query("CREATE TABLE lcm_stage (
			id_entry bigint(21) NOT NULL auto_increment,
			id_case bigint(21) DEFAULT 0 NOT NULL,
			kw_case_stage varchar(255) NOT NULL DEFAULT '',
			date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			id_fu_creation bigint(21) NOT NULL DEFAULT 0,
			date_conclusion datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			id_fu_conclusion bigint(21) NOT NULL DEFAULT 0,
			kw_result varchar(255) NOT NULL DEFAULT '',
			kw_conclusion varchar(255) NOT NULL DEFAULT '',
			kw_sentence varchar(255) NOT NULL DEFAULT '',
			sentence_val text NOT NULL,
			date_agreement datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			latest tinyint(1) DEFAULT '0' NOT NULL,
			PRIMARY KEY (id_entry),
			KEY id_case (id_case))");

		lcm_query("CREATE UNIQUE INDEX idx_case_stage ON lcm_stage (id_case, kw_case_stage)");

		// Populate table based on lcm_followup
		// case stage creation (use one followup per stage)
		lcm_query("INSERT INTO lcm_stage (id_case, kw_case_stage, date_creation, id_fu_creation, latest)
				SELECT c.id_case, fu.case_stage, fu.date_start, fu.id_followup, 0
				FROM lcm_case as c, lcm_followup as fu 
				WHERE c.id_case = fu.id_case
				  AND fu.case_stage != '' 
				GROUP BY c.id_case, fu.case_stage
				ORDER BY fu.date_start ASC");

		$q = "SELECT *
				FROM lcm_followup
				WHERE type = 'conclusion'
				   OR type = 'case_change'";

		$result = lcm_query($q);

		while ($row = lcm_fetch_array($result)) {
			$tmp = lcm_unserialize($row['description']);

			$q = "UPDATE lcm_stage SET
					date_conclusion = '" . $row['date_start'] . "',
					id_fu_conclusion = " . $row['id_followup'] . ",
					kw_result = '" . $tmp['result'] . "',
					kw_conclusion = '" . $tmp['conclusion'] . "',
					kw_sentence = '" . $tmp['sentence'] . "',
					sentence_val = '" . $tmp['sentence_val'] . "',
					date_agreement = '" . $row['date_start'] . "'
				  WHERE id_case = " . $row['id_case'] . "
				    AND kw_case_stage = '" . $row['case_stage'] . "'";

			lcm_query($q);
		}

		upgrade_db_version (38);
	}

	if ($lcm_db_version_current < 39) {
		lcm_query("ALTER TABLE lcm_followup
					ADD hidden ENUM('N', 'Y') not null default 'N' AFTER sumbilled");

		upgrade_db_version (39);
	}

	// [ML] Yes, quite awful, I know, but LCM 0.6.4 had problems..
	function lcm_db_40_refresh_case_update () {
		$server_info = lcm_sql_server_info();

		// [ML] This won't work on MySQL 3.23 .. nor 4.0 (?!)
		if (preg_match('/^MySQL/', $server_info)
			&& (! preg_match('/^MySQL 3\./', $server_info))
			&& (! preg_match('/^MySQL 4\.0/', $server_info))) 
		{
			lcm_query("UPDATE lcm_case 
						SET date_update = (SELECT max(fu.date_start) 
										FROM lcm_followup as fu 
										WHERE lcm_case.id_case = fu.id_case
										GROUP BY fu.id_case)", true);
		} else {
			// [ML] Probably not the best idea.. but brain-dead mysql
			// incompatibilities are driving me crazy..
			//
			// Note: using the join to exclude non-empty dates allows to 
			// continue/re-run the upgrade if it makes a time-out.
			$result = lcm_query("SELECT c.id_case, MAX(fu.date_start) as date
								FROM lcm_followup as fu, lcm_case as c
								WHERE fu.id_case = c.id_case
								  AND c.date_update != '0000-00-00 00:00:00'
								GROUP BY fu.id_case
								ORDER BY fu.id_case ASC");

			while (($row = lcm_fetch_array($result))) {
				lcm_query("UPDATE lcm_case
							SET date_update = '" . $row['date'] . "'
							WHERE id_case = " . $row['id_case']);
			}
		}
	}

	if ($lcm_db_version_current < 40) {
		lcm_query("ALTER TABLE lcm_case
					ADD date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL AFTER date_assignment", true);

		lcm_query("UPDATE lcm_case
					SET date_update = date_assignment", true);

		lcm_db_40_refresh_case_update();

		upgrade_db_version (40);
	}

	if ($lcm_db_version_current < 41) {
		// Clients would get a "" (empty) field if revenue and civil_status field 
		// were left blank. LCM would then lcm_panic() when the fields are activated.
		lcm_query("UPDATE lcm_client
					SET civil_status = 'unknown'
					WHERE civil_status = ''");

		lcm_query("UPDATE lcm_client
					SET income = 'unknown'
					WHERE income = ''");

		// Altough "gender enum('female', 'male', 'unknown') DEFAULT 'unknown'" 
		// was added in lcm_db_version = 12, it was left "DEFAULT 'male'" in the
		// inc_db_create.php until now. The result is that installations that
		// do not activate their "gender" field get clients that are all male.
		lcm_query("ALTER TABLE lcm_client 
					CHANGE gender gender enum('female', 'male', 'unknown') 
					DEFAULT 'unknown' NOT NULL");

		// Therefore, the following "UPDATE" will not fix much, users will have
		// to manually fix their client data, but just in case..
		lcm_query("UPDATE lcm_client
					SET gender = 'unknown'
					WHERE gender = ''");

		upgrade_db_version (41);
	}

	if ($lcm_db_version_current < 42) {
		// This has been deprecated for some time
		lcm_query("DELETE FROM lcm_fields WHERE table_name = 'lcm_case' AND field_name = 'id_court_archive'");
		lcm_db_40_refresh_case_update(); // for 0.6.4a release

		upgrade_db_version (42);
	}

	// LCM 0.7.0
	if ($lcm_db_version_current < 43) {
		lcm_query("ALTER TABLE lcm_keyword_client
					ADD value text NOT NULL");

		lcm_query("ALTER TABLE lcm_keyword_org
					ADD value text NOT NULL");

		upgrade_db_version (43);
	}

	if ($lcm_db_version_current < 44) {
		// Values which were previously 'yes' become 'yes_optional'
		// because that's how they were processed in previous versions
		$upd_metas = array('client_name_middle', 'client_citizen_number', 
						'client_civil_status', 'client_income', 
						'case_alledged_crime', 'case_legal_reason');

		foreach ($upd_metas as $m) {
			lcm_query("UPDATE lcm_meta
						SET value = 'yes_optional'
						WHERE value = 'yes' AND name = '$m'");
		}


		$fields = array (
			"id_expense bigint(21) NOT NULL auto_increment",
			"id_case bigint(21) NOT NULL DEFAULT 0",
			"id_followup bigint(21) NOT NULL DEFAULT 0", 
			"id_author bigint(21) NOT NULL",
			"id_admin bigint(21) NOT NULL DEFAULT 0", // 0 = not approved
			"status ENUM('pending', 'granted', 'refused', 'deleted') NOT NULL",
			"type varchar(255) NOT NULL", // will use system-keyword
			"cost decimal(19,4) NOT NULL DEFAULT 0",
			"description text NOT NULL",
			"date_creation datetime NOT NULL",
			"date_update datetime NOT NULL",
			"pub_read tinyint(1) NOT NULL",
			"pub_write tinyint(1) NOT NULL",
			"PRIMARY KEY  (id_expense)"
		);

		$keys = array (
			"id_case" => "id_case",
			"id_author" => "id_author"
		);

		// If user installs LCM 0.7.0, then imports a 0.6.4 database, the 
		// lcm_expense table will already exist. It is better to drop and 
		// re-create because we may "ALTER" the table later. On the other 
		// hand, there is a risk that we accidently drop a table with data.
		lcm_query("DROP TABLE lcm_expense", true);
		lcm_query_create_table("lcm_expense", $fields, $keys);


		$fields = array (
			"id_comment bigint(21) NOT NULL auto_increment",
			"id_expense bigint(21) NOT NULL",
			"id_author bigint(21) NOT NULL",
			"date_creation datetime NOT NULL",
			"date_update datetime NOT NULL",
			"comment text NOT NULL",
			"PRIMARY KEY  (id_comment)"
		);

		lcm_query("DROP TABLE lcm_expense_comment", true);
		lcm_query_create_table("lcm_expense_comment", $fields);

		upgrade_db_version (44);
	}

	if ($lcm_db_version_current < 45) {
		lcm_query("ALTER TABLE lcm_report
					ADD filecustom text NOT NULL");

		upgrade_db_version (45);
	}

	// This should have been done a long time ago!
	if ($lcm_db_version_current < 46) {
		lcm_query("ALTER TABLE lcm_fields
			CHANGE filter filter ENUM('none','date','number','text','currency') NOT NULL DEFAULT 'none'");

		upgrade_db_version (46);
	}

	if ($lcm_db_version_current < 47) { 
		lcm_query("ALTER TABLE lcm_keyword_group
			ADD id_parent bigint(21) NOT NULL DEFAULT 0 AFTER id_group");

		upgrade_db_version (47);
	}

	if ($lcm_db_version_current < 48) {
		lcm_query("ALTER TABLE lcm_client
			ADD date_birth datetime NULL AFTER date_update");

		upgrade_db_version(48);
	}

	if ($lcm_db_version_current < 49) {
		$fields = array (
			"id_entry bigint(21) NOT NULL auto_increment",
			"id_keyword bigint(21) NOT NULL default 0",
			"id_followup bigint(21) NOT NULL default 0",
			"value text NOT NULL",
			"PRIMARY KEY (id_entry)"
		);
	
		$keys = array (
				'id_keyword' => 'id_keyword',
				'id_followup' => 'id_followup'
		);
		
		lcm_query_create_table('lcm_keyword_followup', $fields, $keys);

		upgrade_db_version(49);
	}

	if ($lcm_db_version_current < 50) {
		lcm_query("ALTER TABLE lcm_stage
			CHANGE date_creation date_creation datetime NOT NULL,
			CHANGE date_conclusion date_conclusion datetime DEFAULT NULL,
			CHANGE date_agreement date_agreement datetime DEFAULT NULL");

		lcm_query("ALTER TABLE lcm_case_attachment
			CHANGE date_removed date_removed datetime default NULL");

		lcm_query("ALTER TABLE lcm_client_attachment
			CHANGE date_removed date_removed datetime default NULL");

		lcm_query("ALTER TABLE lcm_org_attachment
			CHANGE date_removed date_removed datetime default NULL");

		lcm_query("ALTER TABLE lcm_followup
			CHANGE date_end date_end datetime default NULL");

		lcm_query("ALTER TABLE lcm_app
			CHANGE reminder reminder datetime default NULL");

		upgrade_db_version(50);
	}

	if ($lcm_db_version_current < 51) {
		// 
		// Contacts keywords now become lcm_keyword_group instead of lcm_keyword
		// so that we can decide whether they are mandatory/sugg/opt, etc.
		//
		lcm_query("ALTER TABLE lcm_keyword_group
			CHANGE type type ENUM('system', 'contact', 'case', 'stage', 'followup', 'client', 'org', 'client_org', 'author') NOT NULL");

		lcm_query("ALTER TABLE lcm_contact
			ADD date_update datetime default NULL", true);

		// [ML] Intentionally not using inc_keywords.php functions, so that it
		// is more error resistant (i.e. to avoid lcm_panic if there is an error).
		$result = lcm_query("SELECT id_group FROM lcm_keyword_group WHERE name = 'contacts'");
		if (($row0 = lcm_fetch_array($result))) {
			$id_group = $row0['id_group'];

			lcm_log("UPGRADE: Found kwg = $id_group");

			lcm_query("INSERT INTO lcm_keyword_group 
				(name, title, description, type, policy, quantity, suggest, ac_admin, ac_author)
				SELECT CONCAT('+', name), title, description, 'contact', 'optional', 'many', 'none', 'Y', ac_author
				  FROM lcm_keyword
				 WHERE id_group = $id_group");

			lcm_log("UPGRADE: added a few new groups.");

			// Create table to convert IDs in lcm_contact
			// Note: we use the date_update to know whether an entry has been
			// update already (in case the upgrade has a runtime timeout)
			// but we then later set it to null afterwards, because on existing
			// entries, we don't really know when it was last updated 
			// (will show 'unknown' in interface)
			$table_kw1 = array();
			$result1 = lcm_query("SELECT id_keyword, name FROM lcm_keyword WHERE id_group = $id_group");
			while (($row1 = lcm_fetch_array($result1))) {
				$table_kw1[$row1['id_keyword']] = '+' . $row1['name'];
				lcm_log("UPGRADE: load old kw .. " . $row1['id_keyword'] . " = " . $row1['name']);
			}

			$table_kw2 = array();
			$result2 = lcm_query("SELECT id_group, name FROM lcm_keyword_group WHERE type = 'contact'");
			while (($row2 = lcm_fetch_array($result2))) {
				$table_kw2[$row2['name']] = $row2['id_group'];
				lcm_log("UPGRADE: load new kwg .. " . $row2['id_group'] . " = " . $row2['name']);
			}

			$result3 = lcm_query("SELECT id_contact, type_contact, value
							FROM lcm_contact " /* [ML] made no sense, since field is new anyway, but can mess-up imports
							WHERE date_update is not null */ . "
							ORDER BY id_contact ASC");

			while (($row3 = lcm_fetch_array($result3))) {
				lcm_log("UPGRADE: contact id " . $row3['id_contact']
					. " type: "
					. $row3['type_contact']
					. " value: " . $row3['value'] . " ->> " . $table_kw2[$table_kw1[$row3['type_contact']]]);

				if ($table_kw2[$table_kw1[$row3['type_contact']]]) {
					lcm_query("UPDATE lcm_contact
								SET type_contact = " . $table_kw2[$table_kw1[$row3['type_contact']]] . ",
								    date_update = NOW()
								WHERE id_contact = " . $row3['id_contact']);
				} else {
					lcm_log("UPGRADE: contact ignored.");
				}
			}
	
			lcm_query("DELETE FROM lcm_keyword
						WHERE id_group = $id_group");
		} else {
			lcm_log("WARNING: Could not find contact group in lcm_keyword_group. "
				. "You may have to re-create your contacts manually. "
				. "Please e-mail legalcase-devel@lists.sf.net to warn us that this happened.");
		}

		lcm_query("UPDATE lcm_keyword_group
					SET policy = 'recommended'
					WHERE name IN ('+address_main', '+phone_home')");

		upgrade_db_version(51);

		// Reset date_update on contacts, since we are finished with update,
		// and better to show 'unknown'. In the future, it will show the latest
		// date at which a given contact was updated.
		lcm_query("UPDATE lcm_contact SET date_update = NULL");
	} 

	// Update the meta, lcm_fields, keywords, etc.
	lcm_log("Updating LCM default configuration (meta/keywords/repfields/..)", 'upgrade');
	upgrade_database_conf();

	lcm_log("LCM database upgrade complete", 'upgrade');
	return $log;
}

?>
