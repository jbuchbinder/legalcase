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

	$Id: inc_db_create.php,v 1.65 2008/04/07 19:24:03 mlutfy Exp $
*/

if (defined('_INC_DB_CREATE')) return;
define('_INC_DB_CREATE', '1');

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
		"id_stage bigint(21) NOT NULL",
		"title text NOT NULL",
		"date_creation datetime NOT NULL",
		"date_assignment datetime NOT NULL",
		"date_update datetime NOT NULL",
		"legal_reason text",
		"alledged_crime text",
		"notes text",
		"status text",
		"stage varchar(255) NOT NULL",
		"public tinyint(1) DEFAULT 0 NOT NULL",
		"pub_write tinyint(1) DEFAULT 0 NOT NULL",
		"PRIMARY KEY (id_case)"
	);

	lcm_query_create_table('lcm_case', $fields);


	$fields = array (
		"id_attachment bigint(21) NOT NULL auto_increment", 
		"id_case bigint(21) NOT NULL DEFAULT 0", 
		"id_author bigint(21) NOT NULL DEFAULT 0", 
		"filename varchar(255) NOT NULL DEFAULT ''", 
		"type varchar(255) DEFAULT '' NOT NULL",
		"size bigint(21) NOT NULL DEFAULT 0", 
		"description text", 
		"content longblob",
		"date_attached datetime NOT NULL", 
		"date_removed datetime DEFAULT NULL",  // may be null
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
		"kw_case_stage varchar(255) DEFAULT '' NOT NULL",
		"date_creation datetime NOT NULL",
		"id_fu_creation bigint(21) NOT NULL DEFAULT 0",
		"date_conclusion datetime DEFAULT NULL", // may be null
		"id_fu_conclusion bigint(21) NOT NULL DEFAULT 0",
		"kw_result varchar(255) NOT NULL DEFAULT ''",
		"kw_conclusion varchar(255) NOT NULL DEFAULT ''",
		"kw_sentence varchar(255) NOT NULL DEFAULT ''",
		"sentence_val text",
		"date_agreement datetime DEFAULT NULL", // may be null
		"latest tinyint(1) DEFAULT 0 NOT NULL",
		"PRIMARY KEY (id_entry)"
	);

	$keys = array (
		"id_case" => "id_case"
	);

	lcm_query_create_table('lcm_stage', $fields, $keys);
	// [ML] 0.7.3 lcm_query_create_unique_index('lcm_stage', 'idx_case_stage', 'id_case, kw_case_stage');

	$fields = array (
		"id_followup bigint(21) NOT NULL auto_increment",
		"id_case bigint(21) NOT NULL",
		"id_stage bigint(21) NOT NULL",
		"id_author bigint(21) NOT NULL",
		"date_start datetime NOT NULL",
		"date_end datetime DEFAULT NULL", // may be null
		"type varchar(255) NOT NULL",
		"description text",
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
		"name_first text",
		"name_middle text",
		"name_last text",
		"date_creation datetime NOT NULL",
		"date_update datetime NOT NULL",
		"username VARCHAR(255) NOT NULL", /* [ML] 0.7.0 removed 'BINARY', I see no use for it */
		"password tinytext NOT NULL",
		"lang VARCHAR(10) DEFAULT 'en' NOT NULL",
		"prefs text",
		"status ENUM('admin', 'normal', 'external', 'trash', 'waiting', 'suspended') NOT NULL DEFAULT 'normal'",
		"cookie_recall tinytext",

		"maj TIMESTAMP",
		"pgp blob",
		"imessage VARCHAR(3) NOT NULL DEFAULT ''",
		"messagerie VARCHAR(3) NOT NULL DEFAULT ''",
		"alea_actuel tinytext",
		"alea_futur tinytext",

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
		"name_first text",
		"name_middle text",
		"name_last text",
		"date_creation datetime NOT NULL",
		"date_update datetime NOT NULL",
		"date_birth datetime DEFAULT NULL", // may be null
		"citizen_number text",
		"gender ENUM('female','male', 'unknown') NOT NULL",
		"civil_status varchar(255) DEFAULT 'unknown' NOT NULL",
		"income varchar(255) DEFAULT 'unknown' NOT NULL",
		"notes text",
		"PRIMARY KEY (id_client)"
	);

	lcm_query_create_table('lcm_client', $fields);


	$fields = array (
		"id_attachment bigint(21) NOT NULL auto_increment",
		"id_client bigint(21) NOT NULL DEFAULT 0",
		"id_author bigint(21) NOT NULL DEFAULT 0",
		"filename varchar(255) NOT NULL DEFAULT ''",
		"type varchar(255) DEFAULT '' NOT NULL",
		"size bigint(21) NOT NULL DEFAULT 0",
		"description text",
		"content longblob",
		"date_attached datetime NOT NULL",
		"date_removed datetime DEFAULT NULL", // may be null
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
		"name text",
		"date_creation datetime DEFAULT NULL",
		"date_update datetime DEFAULT NULL",
		"notes text",
		"court_reg text",
		"tax_number text",
		"stat_number text",
		"PRIMARY KEY (id_org)"
	);

	lcm_query_create_table('lcm_org', $fields);


	$fields = array (
		"id_attachment bigint(21) NOT NULL auto_increment",
		"id_org bigint(21) NOT NULL DEFAULT '0'",
		"id_author bigint(21) NOT NULL DEFAULT '0'",
		"filename varchar(255) NOT NULL DEFAULT ''",
		"type varchar(255) DEFAULT '' NOT NULL",
		"size bigint(21) NOT NULL DEFAULT '0'",
		"description text",
		"content longblob",
		"date_attached datetime NOT NULL",
		"date_removed datetime DEFAULT NULL", // may be null
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
		"type_person ENUM('author', 'client', 'org') NOT NULL",
		"id_of_person bigint(21) NOT NULL",
		"value text NOT NULL",
		"type_contact tinyint(2) DEFAULT 0 NOT NULL", // XXX do we really need a default value?
		"date_update datetime DEFAULT NULL", // may be null (if installation was upgraded)
		"PRIMARY KEY (id_contact)"
	);

	lcm_query_create_table('lcm_contact', $fields);


	$fields = array (
		"id_keyword bigint(21) NOT NULL auto_increment",
		"id_group bigint(21) NOT NULL",
		"name VARCHAR(255) NOT NULL",
		"title text NOT NULL",
		"description text",
		"hasvalue ENUM('Y', 'N') NOT NULL DEFAULT 'N'",
		"ac_author ENUM('Y', 'N') NOT NULL DEFAULT 'Y'",
		"PRIMARY KEY (id_keyword)"
	);

	lcm_query_create_table('lcm_keyword', $fields);


	$fields = array (
		"id_entry bigint(21) NOT NULL auto_increment",
		"id_keyword bigint(21) NOT NULL",
		"id_case bigint(21) NOT NULL",
		"id_stage bigint(21) NOT NULL DEFAULT 0",
		"value text",
		"PRIMARY KEY (id_entry)"
	);

	$keys = array (
			'id_keyword' => 'id_keyword',
			'id_case' => 'id_case'
	);
	
	lcm_query_create_table('lcm_keyword_case', $fields, $keys);

	$fields = array (
		"id_entry bigint(21) NOT NULL auto_increment",
		"id_keyword bigint(21) NOT NULL",
		"id_client bigint(21) NOT NULL",
		"value text",
		"PRIMARY KEY (id_entry)"
	);

	$keys = array (
		'id_keyword' => 'id_keyword',
		'id_client' => 'id_client'
	);
	
	lcm_query_create_table('lcm_keyword_client', $fields, $keys);


	$fields = array (
		"id_entry bigint(21) NOT NULL auto_increment",
		"id_keyword bigint(21) NOT NULL",
		"id_followup bigint(21) NOT NULL",
		"value text",
		"PRIMARY KEY (id_entry)"
	);

	$keys = array (
			'id_keyword' => 'id_keyword',
			'id_followup' => 'id_followup'
	);
	
	lcm_query_create_table('lcm_keyword_followup', $fields, $keys);


	$fields = array (
		"id_entry bigint(21) NOT NULL auto_increment",
		"id_keyword bigint(21) NOT NULL",
		"id_org bigint(21) NOT NULL",
		"value text",
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
		"id_parent bigint(21) NOT NULL DEFAULT 0",
		"name VARCHAR(255) NOT NULL",
		"title text NOT NULL",
		"description text",
		"type ENUM('system', 'contact', 'case', 'stage', 'followup', 'client', 'org', 'client_org', 'author') NOT NULL",
		"policy ENUM('optional', 'recommended', 'mandatory') NOT NULL DEFAULT 'optional'",
		"quantity ENUM('one', 'many') NOT NULL DEFAULT 'one'",
		"suggest text",
		"ac_admin ENUM('Y', 'N') NOT NULL DEFAULT 'Y'",
		"ac_author ENUM('Y', 'N') NOT NULL DEFAULT 'Y'",
		"PRIMARY KEY (id_group)"
	);

	lcm_query_create_table('lcm_keyword_group', $fields);
	lcm_query_create_unique_index('lcm_keyword_group', 'idx_kwg_name', 'name');


	$fields = array (
		"id_report bigint(21) NOT NULL auto_increment",
		"title varchar(255) NOT NULL",
		"description text",
		"notes text",
		"id_author bigint(21) NOT NULL",
		"date_creation datetime NOT NULL",
		"date_update datetime NOT NULL",
		"line_src_type text",
		"line_src_name text",
		"col_src_type text",
		"col_src_name text",
		"filecustom text",
		"PRIMARY KEY  (id_report)"
	);

	lcm_query_create_table('lcm_report', $fields);


	// XXX maybe double-check whether default values are necessary
	$fields = array (
		"id_field bigint(21) NOT NULL auto_increment",
		"table_name varchar(255) NOT NULL DEFAULT ''",
		"field_name varchar(255) NOT NULL DEFAULT ''",
		"description varchar(255) NOT NULL DEFAULT ''",
		"filter ENUM('none','date','number','text','currency') NOT NULL DEFAULT 'none'",
		"enum_type text",
		"PRIMARY KEY  (id_field)"
	);

	lcm_query_create_table('lcm_fields', $fields);


	// XXX we can drop this table
	$fields = array (
		"id_filter bigint(21) NOT NULL auto_increment",
		"title varchar(255) NOT NULL",
		"type ENUM('AND','OR') NOT NULL DEFAULT 'AND'",
		"id_author bigint(21) NOT NULL DEFAULT '0'",
		"date_creation datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"date_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"PRIMARY KEY  (id_filter)"
	);

	lcm_query_create_table('lcm_filter', $fields);


	$fields = array (
		"id_app bigint(21) NOT NULL auto_increment",
		"id_case bigint(21) NOT NULL DEFAULT 0",
		"id_author bigint(21) NOT NULL DEFAULT 0",
		"type varchar(255) NOT NULL DEFAULT ''",
		"title varchar(255) NOT NULL",
		"description text",
		"start_time datetime NOT NULL",
		"end_time datetime NOT NULL",
		"reminder datetime DEFAULT NULL", // may be null
		"date_creation datetime NOT NULL",
		"date_update datetime NOT NULL",
		"hidden ENUM('N', 'Y') NOT NULL DEFAULT 'N'",
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
		"description text",
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

	lcm_query_create_table("lcm_expense", $fields, $keys);


	$fields = array (
		"id_comment bigint(21) NOT NULL auto_increment",
		"id_expense bigint(21) NOT NULL",
		"id_author bigint(21) NOT NULL",
		"date_creation datetime NOT NULL",
		"date_update datetime NOT NULL",
		"comment text",
		"PRIMARY KEY  (id_comment)"
	);

	lcm_query_create_table("lcm_expense_comment", $fields);

	//
	// Relations
	//

	lcm_log("creating the tables used for relations between objects", 'install');

	$fields = array (
		"id_app bigint(21) NOT NULL",
		"id_client bigint(21) NOT NULL DEFAULT 0", // may be 0, if app with org
		"id_org bigint(21) NOT NULL DEFAULT 0" // may be 0, if app with client
	);

	lcm_query_create_table('lcm_app_client_org', $fields);
	lcm_query_create_unique_index('lcm_app_client_org', 'idx_uniq', 'id_app,id_client,id_org');

	
	$fields = array (
		"id_app bigint(21) NOT NULL",
		"id_followup bigint(21) NOT NULL",
		"relation ENUM('parent','child') NOT NULL DEFAULT 'parent'"
	);

	lcm_query_create_table('lcm_app_fu', $fields);
	lcm_query_create_unique_index('lcm_app_fu', 'idx_uniq', 'id_app,id_followup');


	$fields = array (
		"id_author bigint(21) NOT NULL",
		"id_app bigint(21) NOT NULL"
	);

	lcm_query_create_table('lcm_author_app', $fields);
	lcm_query_create_unique_index('lcm_author_app', 'idx_uniq', 'id_author,id_app');


	$fields = array (
		"id_case bigint(21) NOT NULL",
		"id_client bigint(21) DEFAULT 0 NOT NULL",
		"id_org bigint(21) DEFAULT 0 NOT NULL"
	);

	$keys = array (
		'id_case' => 'id_case',
		'id_client' => 'id_client',
		'id_org' => 'id_org'
	);

	lcm_query_create_table('lcm_case_client_org', $fields, $keys);
	lcm_query_create_unique_index('lcm_case_client_org', 'idx_uniq', 'id_case,id_client,id_org');


	$fields = array (
		"id_case bigint(21) NOT NULL",
		"id_author bigint(21) NOT NULL",
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
		"id_client bigint(21) NOT NULL",
		"id_org bigint(21) NOT NULL"
	);

	$keys = array (
		'id_client' => 'id_client',
		'id_org' => 'id_org'
	);

	lcm_query_create_table('lcm_client_org', $fields, $keys);
	lcm_query_create_unique_index('lcm_client_org', 'idx_uniq', 'id_client,id_org');


	// XXX is this used?
	$fields = array (
		"id_column bigint(21) NOT NULL auto_increment",
		"id_report bigint(21) NOT NULL",
		"id_field bigint(21) NOT NULL DEFAULT 0",
		"col_order bigint(21) NOT NULL DEFAULT 0",
		"header varchar(255) NOT NULL DEFAULT ''",
		"sort ENUM('asc','desc') NOT NULL DEFAULT 'asc'",
		"total tinyint(1) NOT NULL DEFAULT 0",
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
		"id_report bigint(21) NOT NULL",
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


	// XXX Deprecated ?
	$fields = array (
		"id_report bigint(21) NOT NULL",
		"id_filter bigint(21) NOT NULL",
		"type ENUM('AND','OR') NOT NULL DEFAULT 'AND'",
	);

	lcm_query_create_table('lcm_rep_filters', $fields);
	lcm_query_create_unique_index('lcm_rep_filters', 'idx_uniq', 'id_report,id_filter');


	// XXX Deprecated ?
	$fields = array (
		"id_filter bigint(21) NOT NULL DEFAULT 0",
		"id_field bigint(21) NOT NULL DEFAULT 0",
		"cond_order bigint(21) NOT NULL DEFAULT 0",
		"type tinyint(2) NOT NULL DEFAULT 0",
		"value varchar(255) DEFAULT NULL"
	);

	lcm_query_create_table('lcm_filter_conds', $fields);
	lcm_query_create_unique_index('lcm_filter_conds', 'idx_uniq', 'id_filter,id_field,cond_order');


	$fields = array (
		"id_filter bigint(21) NOT NULL auto_increment",
		"id_report bigint(21) NOT NULL",
		"id_field bigint(21) NOT NULL DEFAULT 0", // XXX is 0 possible ?
		"type varchar(255) NOT NULL DEFAULT ''",
		"value varchar(255) NOT NULL DEFAULT ''",
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
