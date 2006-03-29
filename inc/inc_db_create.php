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

	$Id: inc_db_create.php,v 1.50 2006/03/29 17:25:50 mlutfy Exp $
*/

if (defined('_INC_DB_CREATE')) return;
define('_INC_DB_CREATE', '1');

// [ML] Is this needed?  XXX
// [AG] I don't see any reason for it.
// include_lcm('inc_access');

// XXX DEPRECATED
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

// [AG] Upon database format change DO NOT FORGET
// to increase $lcm_db_version found in inc_version.
// Also, add the queries to apply the changes into
// upgrade_database() in inc_db_upgrade

function create_database() {
	$log = "";

	//
	// Main objects
	//

	lcm_log("creating the SQL tables", 'install');

	$fields = array (
		"id_case bigint(21) NOT NULL auto_increment",
		"title text NOT NULL",
		"date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_assignment datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"legal_reason text NOT NULL",
		"alledged_crime text NOT NULL",
		"notes text NOT NULL DEFAULT ''",
		"status text NOT NULL",
		"stage varchar(255) NOT NULL",
		"public tinyint(1) DEFAULT 0 NOT NULL",
		"pub_write tinyint(1) DEFAULT '0' NOT NULL",
		"PRIMARY KEY (id_case)"
	);

	lcm_query_create_table('lcm_case', $fields);


	$fields = array (
		"id_attachment bigint(21) NOT NULL auto_increment", 
		"id_case bigint(21) NOT NULL default 0", 
		"id_author bigint(21) NOT NULL default 0", 
		"filename varchar(255) NOT NULL default ''", 
		"type varchar(255) default NULL", 
		"size bigint(21) NOT NULL default 0", 
		"description text", 
		"content longblob",
		"date_attached datetime DEFAULT '0000-00-00 00:00:00' NOT NULL", 
		"date_removed datetime DEFAULT '0000-00-00 00:00:00' NOT NULL", 
		"PRIMARY KEY  (id_attachment)"
	);

	$keys = array (
		"id_case" => "id_case",
		"id_case" => "id_case",
		"id_author" => "id_author",
		"filename" => "filename"
	);

	lcm_query_create_table('lcm_case_attachment', $fields, $keys);


	$fields = array (
		"id_entry bigint(21) NOT NULL auto_increment",
		"id_case bigint(21) DEFAULT 0 NOT NULL",
		"kw_case_stage varchar(255) NOT NULL DEFAULT ''",
		"date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"id_fu_creation bigint(21) NOT NULL DEFAULT 0",
		"date_conclusion datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"id_fu_conclusion bigint(21) NOT NULL DEFAULT 0",
		"kw_result varchar(255) NOT NULL DEFAULT ''",
		"kw_conclusion varchar(255) NOT NULL DEFAULT ''",
		"kw_sentence varchar(255) NOT NULL DEFAULT ''",
		"sentence_val text NOT NULL DEFAULT ''",
		"date_agreement datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"latest tinyint(1) DEFAULT '0' NOT NULL",
		"PRIMARY KEY (id_entry)"
	);

	$keys = array (
		"id_case" => "id_case"
	);

	lcm_query_create_table('lcm_stage', $fields, $keys);
	lcm_query_create_unique_index('lcm_stage', 'idx_case_stage', 'id_case, kw_case_stage');


	$fields = array (
		"id_followup bigint(21) NOT NULL auto_increment",
		"id_case bigint(21) DEFAULT '0' NOT NULL",
		"id_author bigint(21) DEFAULT '0' NOT NULL",
		"date_start datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_end datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"type varchar(255) NOT NULL DEFAULT 'other'",
		"description text NOT NULL",
		"case_stage varchar(255) NOT NULL",
		"sumbilled decimal(19,4) NOT NULL DEFAULT 0",
		"hidden ENUM('N', 'Y') NOT NULL DEFAULT 'N'",
		"PRIMARY KEY (id_followup)"
	);

	$keys = array (
		'id_case' => 'id_case',
		'id_author' => 'id_author'
	);

	lcm_query_create_table('lcm_followup', $fields, $keys);


	// [ML] XXX too many extra fields
	$fields = array (
		"id_author bigint(21) NOT NULL auto_increment",
		"id_office bigint(21) DEFAULT 0 NOT NULL",
		"name_first text NOT NULL",
		"name_middle text NOT NULL",
		"name_last text NOT NULL",
		"date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"username VARCHAR(255) NOT NULL", /* [ML] 0.7.0 removed 'BINARY', I see no use for it */
		"password tinytext NOT NULL",
		"lang VARCHAR(10) DEFAULT '' NOT NULL",
		"prefs text NOT NULL DEFAULT ''",
		"status ENUM('admin', 'normal', 'external', 'trash', 'waiting', 'suspended') NOT NULL DEFAULT 'normal'",
		"cookie_recall tinytext NOT NULL DEFAULT ''",

		"maj TIMESTAMP",
		"pgp blob NOT NULL DEFAULT ''",
		"imessage VARCHAR(3) NOT NULL DEFAULT ''",
		"messagerie VARCHAR(3) NOT NULL DEFAULT ''",
		"alea_actuel tinytext NOT NULL DEFAULT ''",
		"alea_futur tinytext NOT NULL DEFAULT ''",

		"PRIMARY KEY (id_author)"
	);

	$keys = array(
		'username' => 'username',
		'status' => 'status',
		'lang' => 'lang'
	);

	lcm_query_create_table('lcm_author', $fields, $keys);


	$fields = array (
		"id_client bigint(21) NOT NULL auto_increment",
		"name_first text NOT NULL",
		"name_middle text NOT NULL DEFAULT ''",
		"name_last text NOT NULL",
		"date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"citizen_number text NOT NULL DEFAULT ''",
		"address text NOT NULL DEFAULT ''",
		"gender ENUM('female','male', 'unknown') NOT NULL DEFAULT 'unknown'",
		"civil_status varchar(255) DEFAULT 'unknown' NOT NULL",
		"income varchar(255) DEFAULT 'unknown' NOT NULL",
		"notes text DEFAULT '' NOT NULL",
		"PRIMARY KEY (id_client)"
	);

	lcm_query_create_table('lcm_client', $fields);


	$fields = array (
		"id_attachment bigint(21) NOT NULL auto_increment",
		"id_client bigint(21) NOT NULL default 0",
		"id_author bigint(21) NOT NULL default 0",
		"filename varchar(255) NOT NULL default ''",
		"type varchar(255) default NULL", // XXX hum!
		"size bigint(21) NOT NULL default 0",
		"description text",
		"content longblob",
		"date_attached datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_removed datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"PRIMARY KEY  (id_attachment)"
	);

	$keys = array (
		'id_client' => 'id_client',
		'id_author' => 'id_author',
		'filename' => 'filename'
	); 

	lcm_query_create_table('lcm_client_attachment', $fields, $keys);


	$fields = array (
		"id_org bigint(21) NOT NULL auto_increment",
		"name text NOT NULL",
		"date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"address text NOT NULL",
		"notes text NOT NULL DEFAULT ''",
		"court_reg text NOT NULL default ''",
		"tax_number text NOT NULL default ''",
		"stat_number text NOT NULL default ''",
		"PRIMARY KEY (id_org)"
	);

	lcm_query_create_table('lcm_org', $fields);


	$fields = array (
		"id_attachment bigint(21) NOT NULL auto_increment",
		"id_org bigint(21) NOT NULL default '0'",
		"id_author bigint(21) NOT NULL default '0'",
		"filename varchar(255) NOT NULL default ''",
		"type varchar(255) default NULL",
		"size bigint(21) NOT NULL default '0'",
		"description text",
		"content longblob",
		"date_attached datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_removed datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"PRIMARY KEY  (id_attachment)"
	);

	$keys = array (
		'id_org' => 'id_org',
		'id_author' => 'id_author',
		'filename' => 'filename'
	);

	lcm_query_create_table('lcm_org_attachment', $fields, $keys);


	$fields = array (
		"id_contact bigint(21) NOT NULL auto_increment",
		"type_person ENUM('author', 'client', 'org') NOT NULL DEFAULT 'author'",
		"id_of_person bigint(21) DEFAULT 0 NOT NULL",
		"value text NOT NULL",
		"type_contact tinyint(2) DEFAULT 0 NOT NULL",
		"PRIMARY KEY (id_contact)"
	);

	lcm_query_create_table('lcm_contact', $fields);


	$fields = array (
		"id_keyword bigint(21) NOT NULL auto_increment",
		"id_group bigint(21) NOT NULL DEFAULT 0",
		"name VARCHAR(255) NOT NULL",
		"title text NOT NULL DEFAULT ''",
		"description text NOT NULL DEFAULT ''",
		"hasvalue ENUM('Y', 'N') NOT NULL DEFAULT 'N'",
		"ac_author ENUM('Y', 'N') NOT NULL DEFAULT 'Y'",
		"PRIMARY KEY (id_keyword)"
	);

	lcm_query_create_table('lcm_keyword', $fields);


	$fields = array (
		"id_entry bigint(21) NOT NULL auto_increment",
		"id_keyword bigint(21) NOT NULL default 0",
		"id_case bigint(21) NOT NULL default 0",
		"id_stage bigint(21) NOT NULL default 0",
		"value text NOT NULL default ''",
		"PRIMARY KEY (id_entry)"
	);

	$keys = array (
			'id_keyword' => 'id_keyword',
			'id_case' => 'id_case'
	);
	
	lcm_query_create_table('lcm_keyword_case', $fields, $keys);

	$fields = array (
		"id_entry bigint(21) NOT NULL auto_increment",
		"id_keyword bigint(21) NOT NULL DEFAULT 0",
		"id_client bigint(21) NOT NULL DEFAULT 0",
		"value text NOT NULL DEFAULT ''",
		"PRIMARY KEY (id_entry)"
	);

	$keys = array (
		'id_keyword' => 'id_keyword',
		'id_client' => 'id_client'
	);
	
	lcm_query_create_table('lcm_keyword_client', $fields, $keys);


	$fields = array (
		"id_entry bigint(21) NOT NULL auto_increment",
		"id_keyword bigint(21) NOT NULL DEFAULT 0",
		"id_org bigint(21) NOT NULL DEFAULT 0",
		"value text NOT NULL DEFAULT ''",
		"PRIMARY KEY (id_entry)"
	);

	$keys = array (
		'id_keyword' => 'id_keyword',
		'id_org' => 'id_org'
	);

	lcm_query_create_table('lcm_keyword_org', $fields, $keys);
	lcm_query_create_unique_index('lcm_keyword', 'idx_kw_name', 'id_group, name');


	$fields = array (
		"id_group bigint(21) NOT NULL auto_increment",
		"name VARCHAR(255) NOT NULL",
		"title text NOT NULL DEFAULT ''",
		"description text NOT NULL DEFAULT ''",
		"type ENUM('system', 'case', 'stage', 'followup', 'client', 'org', 'client_org', 'author') NOT NULL",
		"policy ENUM('optional', 'recommended', 'mandatory') NOT NULL DEFAULT 'optional'",
		"quantity ENUM('one', 'many') NOT NULL DEFAULT 'one'",
		"suggest text NOT NULL DEFAULT ''",
		"ac_admin ENUM('Y', 'N') NOT NULL DEFAULT 'Y'",
		"ac_author ENUM('Y', 'N') NOT NULL DEFAULT 'Y'",
		"PRIMARY KEY (id_group)"
	);

	lcm_query_create_table('lcm_keyword_group', $fields);
	lcm_query_create_unique_index('lcm_keyword_group', 'idx_kwg_name', 'name');


	$fields = array (
		"id_report bigint(21) NOT NULL auto_increment",
		"title varchar(255) NOT NULL default ''",
		"description text NOT NULL default ''",
		"notes text NOT NULL default ''",
		"id_author bigint(21) NOT NULL default '0'",
		"date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"line_src_type text NOT NULL DEFAULT ''",
		"line_src_name text NOT NULL DEFAULT ''",
		"col_src_type text NOT NULL DEFAULT ''",
		"col_src_name text NOT NULL DEFAULT ''",
		"PRIMARY KEY  (id_report)"
	);

	lcm_query_create_table('lcm_report', $fields);


	$fields = array (
		"id_field bigint(21) NOT NULL auto_increment",
		"table_name varchar(255) NOT NULL default ''",
		"field_name varchar(255) NOT NULL default ''",
		"description varchar(255) NOT NULL default ''",
		"filter ENUM('none','date','number','text','currency') NOT NULL DEFAULT 'none'",
		"enum_type text NOT NULL DEFAULT ''",
		"PRIMARY KEY  (id_field)"
	);

	lcm_query_create_table('lcm_fields', $fields);


	$fields = array (
		"id_filter bigint(21) NOT NULL auto_increment",
		"title varchar(255) NOT NULL default ''",
		"type ENUM('AND','OR') NOT NULL DEFAULT 'AND'",
		"id_author bigint(21) NOT NULL default '0'",
		"date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"PRIMARY KEY  (id_filter)"
	);

	lcm_query_create_table('lcm_filter', $fields);


	$fields = array (
		"id_app bigint(21) NOT NULL auto_increment",
		"id_case bigint(21) NOT NULL default '0'",
		"id_author bigint(21) NOT NULL default '0'",
		"type varchar(255) NOT NULL default ''",
		"title varchar(255) NOT NULL default ''",
		"description text NOT NULL",
		"start_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"end_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"reminder datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"PRIMARY KEY  (id_app)"
	);

	$keys = array (
		'id_case' => 'id_case',
		'id_author' => 'id_author',
		'type' => 'type'
	);

	lcm_query_create_table('lcm_app', $fields, $keys);


	$fields = array (
		"id_expense bigint(21) NOT NULL auto_increment",
		"id_case bigint(21) NOT NULL DEFAULT 0",
		"id_followup bigint(21) NOT NULL DEFAULT 0", 
		"id_author bigint(21) NOT NULL",
		"id_admin bigint(21) NOT NULL DEFAULT 0", // 0 = not approved
		"status ENUM('pending', 'granted', 'refused', 'deleted') NOT NULL",
		"type varchar(255) NOT NULL", // will use system-keyword
		"cost decimal(19,4) NOT NULL DEFAULT 0",
		"description text NOT NULL DEFAULT ''",
		"date_creation datetime NOT NULL",
		"date_update datetime NOT NULL",
		"pub_read tinyint(1) NOT NULL",
		"pub_write tinyint(1) NOT NULL"
	);

	$keys = array (
		"id_case" => "id_case",
		"id_author" => "id_author"
	);

	lcm_query_create_table("lcm_expense", $fields, $keys);


	$fields = array (
		"id_comment bigint(21) NOT NULL auto_increment",
		"id_expense bigint(21) NOT NULL",
		"id_author bigint(21) NOT NULL",
		"date_creation datetime NOT NULL",
		"date_update datetime NOT NULL",
		"comment text NOT NULL DEFAULT ''"
	);

	lcm_query_create_table("lcm_expense_comment", $fields);

	//
	// Relations
	//

	lcm_log("creating the tables used for relations between objects", 'install');

	$fields = array (
		"id_app bigint(21) NOT NULL default '0'",
		"id_client bigint(21) NOT NULL default '0'",
		"id_org bigint(21) NOT NULL default '0'"
	);

	lcm_query_create_table('lcm_app_client_org', $fields);
	lcm_query_create_unique_index('lcm_app_client_org', 'idx_uniq', 'id_app,id_client,id_org');

	
	$fields = array (
		"id_app bigint(21) NOT NULL default '0'",
		"id_followup bigint(21) NOT NULL default '0'",
		"relation ENUM('parent','child') NOT NULL DEFAULT 'parent'"
	);

	lcm_query_create_table('lcm_app_fu', $fields);
	lcm_query_create_unique_index('lcm_app_fu', 'idx_uniq', 'id_app,id_followup');


	$fields = array (
		"id_author bigint(21) NOT NULL default '0'",
		"id_app bigint(21) NOT NULL default '0'"
	);

	lcm_query_create_table('lcm_author_app', $fields);
	lcm_query_create_unique_index('lcm_author_app', 'idx_uniq', 'id_author,id_app');


	$fields = array (
		"id_case bigint(21) DEFAULT '0' NOT NULL",
		"id_client bigint(21) DEFAULT '0' NOT NULL",
		"id_org bigint(21) DEFAULT '0' NOT NULL"
	);

	$keys = array (
		'id_case' => 'id_case',
		'id_client' => 'id_client',
		'id_org' => 'id_org'
	);

	lcm_query_create_table('lcm_case_client_org', $fields, $keys);
	lcm_query_create_unique_index('lcm_case_client_org', 'idx_uniq', 'id_case,id_client,id_org');


	$fields = array (
		"id_case bigint(21) DEFAULT 0 NOT NULL",
		"id_author bigint(21) DEFAULT 0 NOT NULL",
		"ac_read tinyint(1) DEFAULT 1 NOT NULL",
		"ac_write tinyint(1) DEFAULT 0 NOT NULL",
		"ac_edit tinyint(1) DEFAULT 0 NOT NULL",
		"ac_admin tinyint(1) DEFAULT 0 NOT NULL"
	);

	$keys = array (
		'id_case' => 'id_case',
		'id_author' => 'id_author'
	);

	lcm_query_create_table('lcm_case_author', $fields, $keys);
	lcm_query_create_unique_index('lcm_case_author', 'idx_uniq', 'id_case,id_author');


	$fields = array (
		"id_client bigint(21) DEFAULT '0' NOT NULL",
		"id_org bigint(21) DEFAULT '0' NOT NULL"
	);

	$keys = array (
		'id_client' => 'id_client',
		'id_org' => 'id_org'
	);

	lcm_query_create_table('lcm_client_org', $fields, $keys);
	lcm_query_create_unique_index('lcm_client_org', 'idx_uniq', 'id_client,id_org');


	$fields = array (
		"id_column bigint(21) NOT NULL auto_increment",
		"id_report bigint(21) NOT NULL default 0",
		"id_field bigint(21) NOT NULL default 0",
		"col_order bigint(21) NOT NULL default 0",
		"header varchar(255) NOT NULL default ''",
		"sort ENUM('asc','desc') NOT NULL DEFAULT 'asc'",
		"total tinyint(1) NOT NULL default 0",
		"col_group ENUM('COUNT','SUM') NOT NULL",
		"PRIMARY KEY  (id_column)"
	);

	$keys = array (
		'id_report' => 'id_report',
		'id_field' => 'id_field',
		'col_order' => 'col_order'
	);

	lcm_query_create_table('lcm_rep_col', $fields, $keys);


	$fields = array (
		"id_line bigint(21) NOT NULL auto_increment",
		"id_report bigint(21) NOT NULL DEFAULT 0",
		"id_field bigint(21) NOT NULL DEFAULT 0",
		"sort_type ENUM('asc', 'desc') NOT NULL DEFAULT 'asc'",
		"col_order bigint(21) NOT NULL DEFAULT 0",
		"total tinyint(1) NOT NULL DEFAULT 0",
		"PRIMARY KEY (id_line)"
	);

	$keys = array (
		'id_report' => 'id_report',
		'id_field' => 'id_field',
		'col_order' => 'col_order'
	);
		
	lcm_query_create_table('lcm_rep_line', $fields, $keys);


	$fields = array (
		"id_report bigint(21) NOT NULL default 0",
		"id_filter bigint(21) NOT NULL default 0",
		"type ENUM('AND','OR') NOT NULL DEFAULT 'AND'",
	);

	lcm_query_create_table('lcm_rep_filters', $fields);
	lcm_query_create_unique_index('lcm_rep_filters', 'idx_uniq', 'id_report,id_filter');


	$fields = array (
		"id_filter bigint(21) NOT NULL default 0",
		"id_field bigint(21) NOT NULL default 0",
		"cond_order bigint(21) NOT NULL default 0",
		"type tinyint(2) NOT NULL default 0",
		"value varchar(255) default NULL"
	);

	lcm_query_create_table('lcm_filter_conds', $fields);
	lcm_query_create_unique_index('lcm_filter_conds', 'idx_uniq', 'id_filter,id_field,cond_order');


	$fields = array (
		"id_filter bigint(21) NOT NULL auto_increment",
		"id_report bigint(21) NOT NULL default 0",
		"id_field bigint(21) NOT NULL default 0",
		"type varchar(255) NOT NULL default ''",
		"value varchar(255) NOT NULL default ''",
		"PRIMARY KEY  (id_filter)"
	);

	lcm_query_create_table('lcm_rep_filter', $fields);


	//
	// Management of the application
	//

	$fields = array (
		"name VARCHAR(255) NOT NULL",
		"value VARCHAR(255) DEFAULT ''",
		"upd TIMESTAMP",
		"PRIMARY KEY (name)"
	);

	lcm_query_create_table('lcm_meta', $fields);


	// Set the version of the installed database
	global $lcm_db_version;

	lcm_assert_value($lcm_db_version);

	$query = "INSERT INTO lcm_meta (name, value, upd)
				VALUES ('lcm_db_version', '$lcm_db_version', NOW())";
	lcm_query($query);

	lcm_log("Setting-up default LCM configuration", 'install');
	include_lcm('inc_db_upgrade');
	upgrade_database_conf();

	lcm_log("LCM database initialisation complete", 'install');
	return $log;
}

?>
