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

	$Id: import_db.php,v 1.7 2005/02/17 09:45:40 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_conditions');

$tabs = array(	array('name' => 'All cases','url' => 'archive.php'),
		array('name' => 'Export DB','url' => 'export_db.php'),
		array('name' => 'Import DB','url' => 'import_db.php')
	);

function get_parameters() {
	lcm_page_start('Archives');

	// Show tabs
	global $tabs;
	show_tabs_links($tabs,2);

	// Show tab header
	echo "Import database\n";
	
	// Create form
	echo "\n<form action='import_db.php' method='POST'>\n";
	
	// Select backup to restore
	echo "<fieldset class='info_box'>\n";
	echo "<strong>Select backup to restore</strong><br />";
	echo "\t<select name='name' class='sel_frm'>\n";
	// Read existing backups
	$storage = opendir('inc/data');
	while (false !== ($file = readdir($storage))) {
//		var_dump($file);
		if (is_dir("inc/data/$file") && (strpos($file,'db-')===0)) {
			echo "\t\t<option value='" . substr($file,3) . "'>" . substr($file,3) . "</option>\n";
		}
	}
//	echo "\t\t<option selected>-- Create new file --</option>\n";
	echo "\t</select>\n";
	
	// Select restore type
	echo "<br />\n";
	echo "<p class='normal_text'>\n";
	echo "\t<input type='radio' name='restore_type' value='clean' id='r1' checked /><label for='r1'>&nbsp;<strong>Clean</strong></label><br />Import backup data into empty database. This will return database in the exact state at the time of backup.<br /><br />\n";
	echo "\t<input type='radio' name='restore_type' value='replace' id='r2' /><label for='r2'>&nbsp;<strong>Replace</strong></label><br /> Imported backup data will replace the existing. This is usefull to undo the changes made in the database since last backup, without losing the new information. Warning: This operation could break database integrity, especially if importing data between different LCM installations.<br /><br />\n";
	echo "\t<input type='radio' name='restore_type' value='ignore' id='r3' /><label for='r3'>&nbsp;<strong>Append</strong></label><br /> Only backup data NOT existing in the database will be imported. This is usefull to import data lost since last backup, without changing the existing information. Warning: This operation could break database integrity, especially if importing data between different LCM installations.<br /><br />\n";
	echo "\t<button type='submit' class='simple_form_btn'>Import</button>\n";
	echo "</p>\n";
	echo "</fieldset\n>";
	echo "</form>\n";

	lcm_page_end();
}

function import_database($input_filename) {
	global $tabs;

	// Clean input data
	$input_filename = clean_input($input_filename);
	// Check if file exists
	$root = addslashes(getcwd());
	$dir = "$root/inc/data/db-$input_filename";
	if (file_exists($dir)) {
		if ($_POST['conf']!=='yes') {
			// Print confirmation form
			lcm_page_start('Archives');

			// Show tabs
			show_tabs_links($tabs,2,true);

			// Show tab header
			echo "Warning!\n";

			echo "<fieldset class='info_box'>\n";
			echo "<form action='import_db.php' method='POST'>\n";
			echo "\tRestore operation will overwrite your database. Are you sure?<br />\n";
			echo "\t<button type='submit' class='simple_form_btn' name='conf' value='yes'>Yes</button>\n";
			echo "\t<button type='submit' class='simple_form_btn' name='conf' value='no'>No</button>\n";
			echo "\t<input type='hidden' name='name' value='$input_filename' />\n";
			echo "\t<input type='hidden' name='restore_type' value='" . $_POST['restore_type'] . "' />\n";
			echo "</form>";
			echo "</fieldset\n>";
			lcm_page_end();
			return;
		}
	}

	// Get saved database version
	if (false === ($fh = fopen("$dir/db-version",'r')))
		die("System error: Could not open file '$dir/db-version");
	$backup_db_version = intval(fread($fh,10));
	fclose($fh);

	// For debugging - use another database
	//lcm_query("use lcm_new");
	
	// Recreate tables
	if ( ($_POST['restore_type'] == 'clean') || ($backup_db_version < read_meta('lcm_db_version')) ) {
		// Open backup dir
		if (false === ($dh = opendir("$dir/")))
			die("System error: Could not open directory '$dir'!");

		while (false !== ($file = readdir($dh))) {
			// Get table name
			$table = substr($file,0,-10);
			// Add path to filename
			$file = "$dir/$file";
			if (strlen($file) > 10) {
				if (is_file($file) && (substr($file,-10) === ".structure")) {
					// Clear the table
					$q = "DROP TABLE IF EXISTS $table";
					$result = lcm_query($q);

					// Create table
					$fh = fopen($file,'r');
					$q = fread($fh,filesize($file));
					fclose($fh);
					$result = lcm_query($q);
				}
			}
		}
		closedir($dh);

		// Update lcm_db_version
		write_meta('lcm_db_version',$backup_db_version);
	}	// Old backup version
	else if ($backup_db_version > read_meta('lcm_db_version')) {
		// Backup version newer than installed db version
		lcm_page_start('Archives');
		
		// Show tabs
		show_tabs_links($tabs,2,true);

		// Show tab header
		echo "Version mismatch!\n";

		echo "<fieldset class='info_box'>\n";
		echo "Backup database version is newer than the installed database.";
		echo "</fieldset\n>";
		lcm_page_end();
		return;
	}	// Backup version newer than installed db version
	else {
		// Backup and current db versions are equal
	}
	
	//
	// Import data into database tables\
	//
	
	// Change backup dir permissions, so MySQL could read from it.
	chmod($dir,0755);
	// Open backup dir
	if (false === ($dh = opendir("$dir/")))
		die("System error: Could not open directory '$dir'!");

	while (false !== ($file = readdir($dh))) {
		// Get table name
		$table = substr($file,0,-5);
		// Add path to filename
		$file = "$dir/$file";
		if (strlen($file) > 5) {
			if (is_file($file) && (substr($file,-5) === ".data")) {
				// If restore_type='clean', clear the table
				if ($_POST['restore_type'] == 'clean') lcm_query("TRUNCATE TABLE $table");
				
				$q = "LOAD DATA INFILE '$file' ";
				$q .= (($_POST['restore_type'] == 'replace') ? 'REPLACE' : 'IGNORE');
				$q .= "	INTO TABLE $table
					FIELDS TERMINATED BY ','
						OPTIONALLY ENCLOSED BY '\"'
						ESCAPED BY '\\\\'
					LINES TERMINATED BY '\r\n'";
				$result = lcm_query($q);
			}
		}
	}
	closedir($dh);

	// Change backup dir permissions back
	chmod($dir,0700);
	
	// Update lcm_db_version since we have overwritten lcm_meta
	write_meta('lcm_db_version',$backup_db_version);
	
	// Debugging
	//lcm_query("use lcm");

	lcm_page_start('Archives');

	// Show tabs
	show_tabs_links($tabs,2,true);

	// Show tab header
	echo "Import finished\n";

	echo "<fieldset class='info_box'>\n";
	echo "Backup '$input_filename' was successfully imported into database.";
	echo "</fieldset\n>";
	lcm_page_end();

}	// import_database()

//
// Main
//
if ($_POST['name']) {
	// Proceed with import
	$log = import_database($_POST['name']);
} else {
	// Get import parameters
	get_parameters();
}

?>