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

	$Id: import_db.php,v 1.16 2006/09/14 19:36:21 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_conditions');

define('DIR_BACKUPS', (isset($_SERVER['LcmDataDir']) ? $_SERVER['LcmDataDir'] : addslashes(getcwd()) . '/inc/data'));
define('DIR_BACKUPS_PREFIX', DIR_BACKUPS . '/db-');

define('DATA_EXT_NAME', '.csv');
define('DATA_EXT_LEN', strlen(lcm_utf8_decode(DATA_EXT_NAME)));

if (! isset($_SESSION['errors']))
	$_SESSION['errors'] = array();

$tabs = array(
			array('name' => _T('archives_tab_export'), 'url' => 'export_db.php'),
			array('name' => _T('archives_tab_import'), 'url' => 'import_db.php')
	);

function show_import_form() {
	lcm_page_start(_T('title_archives'), '', '', 'archives_import');

	global $tabs;
	show_tabs_links($tabs, 1);
	lcm_bubble('archive_restore');

	// Show the errors (if any)
	echo show_all_errors($_SESSION['errors']);

	// Upload backup form
	echo '<form enctype="multipart/form-data" action="import_db.php" method="post">' . "\n";
	echo '<input type="hidden" name="action" value="upload_file" />' . "\n";
	echo '<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />' . "\n";

	echo "<fieldset class='info_box'>\n";
	show_page_subtitle(_T('archives_subtitle_upload'), 'archives_import', 'newrestore');

	echo '<p class="normal_text">' . _T('archives_info_how_to_upload') . "</p>\n";
	echo '<p class="normal_text">' . _Ti('file_input_name');
	echo '<input type="file" name="filename" size="40" value="" /> ';
	echo '<input type="submit" name="submit" id="btn_upload" value="' . _T('button_validate') . '" class="search_form_btn" />';
	echo "</p>\n";

	echo "</fieldset>\n";
	echo "</form>\n";

	// Restore backup form
	echo '<form action="import_db.php" method="post">' . "\n";
	echo '<input type="hidden" name="action" value="import" />' . "\n";

	echo "<fieldset class='info_box'>\n";
	show_page_subtitle(_T('archives_subtitle_restore'), 'archives_import', 'delrestore');

	echo "<strong>" . _Ti('archives_input_select_backup') . "</strong><br />";
	echo "<select name='file' class='sel_frm'>\n";

	lcm_debug("opendir: " . DIR_BACKUPS);

	$storage = opendir(DIR_BACKUPS);
	while (($file = readdir($storage))) {
		lcm_debug("file in opendir: $file");

		if (is_dir(DIR_BACKUPS . '/' . $file) && (strpos($file,'db-')===0))
			echo "\t\t<option value='" . substr($file,3) . "'>" . substr($file,3) . "</option>\n";
	}
	echo "</select>\n";
	
	// Select restore type
	echo "<p class='normal_text'>\n";
	echo "<input type='radio' name='restore_type' value='clean' id='r1' /><label for='r1'>&nbsp;<strong>" . _T('archives_input_option_restore') .  "</strong></label><br />" . _T('archives_info_option_restore') . "<br /><br />\n";
	
	// [ML] This is confusing as hell. I understand the aim from DB point of view
	// but I don't understand the aim from the admin's point of view.
	// echo "<input type='radio' name='restore_type' value='replace' id='r2' /><label for='r2'>&nbsp;<strong>Replace (experimental!)</strong></label><br /> Imported backup data will replace the existing. This is usefull to undo the changes made in the database since last backup, without losing the new information. Warning: This operation could break database integrity, especially if importing data between different LCM installations.<br /><br />\n";

	echo "<input type='radio' name='restore_type' value='ignore' id='r3' /><label for='r3'>&nbsp;<strong>" . _T('archives_input_option_sync') . " (experimental!)</strong></label><br /> Only backup data NOT existing in the database will be imported. This is usefull to import data lost since last backup, without changing the existing information. Warning: This operation could break database integrity, especially if importing data between different LCM installations.<br /><br />\n"; // TRAD

	echo "<button type='submit' class='simple_form_btn'>" . _T('button_validate') . "</button>\n";
	echo "</p>\n";
	echo "</fieldset\n>";
	echo "</form>\n";

	lcm_page_end();
	$_SESSION['errors'] = array();
}

function import_database($input_filename) {
	global $tabs;

	$input_filename = clean_input($input_filename);
	$root = addslashes(getcwd());
	$dir = DIR_BACKUPS_PREFIX . $input_filename;

	if (file_exists($dir)) {
		if ($_POST['conf']!=='yes') {
			// Print confirmation form
			lcm_page_start(_T('title_archives'), '', '', 'archives_import');
			show_tabs_links($tabs, 1, true);

			echo "<fieldset class='info_box'>\n";
			show_page_subtitle(_T('generic_subtitle_warning'), 'archives_import');

			echo "<p class='normal_text'><img src='images/jimmac/icon_warning.gif' alt='' "
				. "align='right' height='48' width='48' />" 
				. _T('archives_info_restore_will_delete')
				. "</p>\n";

			echo "<form action='import_db.php' method='post'>\n";
			echo '<input type="hidden" name="action" value="import" />' . "\n";

			echo "<button type='submit' class='simple_form_btn' name='conf' value='yes'>" . _T('info_yes') . "</button>\n";
			echo "<button type='submit' class='simple_form_btn' name='conf' value='no'>" . _T('info_no') . "</button>\n";
			echo "<input type='hidden' name='file' value='$input_filename' />\n";
			echo "<input type='hidden' name='restore_type' value='" . $_POST['restore_type'] . "' />\n";
			echo "</form>";
			echo "</fieldset\n>";
			lcm_page_end();
			return;
		}
	}

	// Get saved database version
	if (! ($fh = fopen("$dir/db-version",'r')))
		lcm_panic("System error: Could not open file '$dir/db-version");

	$backup_db_version = intval(fread($fh,10));
	fclose($fh);

	// For debugging - use another database
	//lcm_query("use lcm_new");
	
	// Recreate tables
	if ( ($_POST['restore_type'] == 'clean') || ($backup_db_version < read_meta('lcm_db_version')) ) {
		// Open backup dir
		if (! ($dh = opendir("$dir/")))
			lcm_panic("System error: Could not open directory '$dir'");

		while (($file = readdir($dh))) {
			// Get table name
			$table = substr($file,0,-10);

			// Add path to filename
			$file = "$dir/$file";
			if (strlen($file) > 10) {
				if (is_file($file) && (substr($file,-10) === ".structure")
					&& is_file("$dir/$table" . DATA_EXT_NAME)) 
				{
					// Clear the table
					$q = "DROP TABLE IF EXISTS $table";
					$result = lcm_query($q);

					// Create table
					$fh = fopen($file,'r');
					$q = fread($fh,filesize($file));
					fclose($fh);
					$result = lcm_query_restore_table($q);
				}
			}
		}

		closedir($dh);

		// Update lcm_db_version
		// [ML] This is rather useless because they will be overwritten when the
		// values are loaded (LOAD FILE), but I leave it just in case there are
		// obscur bugs (altough this will most likely generate strange bugs..)
		write_meta('lcm_db_version', $backup_db_version);

		if (! preg_match('/^MySQL (4\.0|3\.)/', lcm_sql_server_info()))
			write_meta('db_utf8', 'yes');

		write_metas();
	}	// Old backup version
	else if ($backup_db_version > read_meta('lcm_db_version')) {
		// Backup version newer than installed db version
		lcm_page_start(_T('title_archives'), '', '', 'archives_import');
		
		// Show tabs
		show_tabs_links($tabs, 1, true);

		// Show tab header
		echo "Version mismatch!\n"; // TRAD

		echo "<fieldset class='info_box'>\n";
		echo "Backup database version is newer than the installed database."; // TRAD
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
	if (! ($dh = opendir("$dir/")))
		lcm_panic("System error: Could not open directory '$dir'");

	while (($file = readdir($dh))) {
		// Get table name
		$table = substr($file,0,- DATA_EXT_LEN);
		// Add path to filename
		$file = "$dir/$file";
		if (strlen($file) > 5) { // [ML] why?
			if (is_file($file) && (substr($file, - DATA_EXT_LEN) === DATA_EXT_NAME)) {
				// If restore_type='clean', clear the table
				if ($_POST['restore_type'] == 'clean')
					lcm_query("TRUNCATE TABLE $table");
				
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
	chmod($dir, 0700);
	
	// Update lcm_db_version since we have overwritten lcm_meta
	write_meta('lcm_db_version', $backup_db_version);

	if ($_REQUEST['restore_type'] == 'clean')
		if (! preg_match('/^MySQL (4\.0|3\.)/', lcm_sql_server_info()))
			write_meta('db_utf8', 'yes');

	write_metas();

	lcm_page_start(_T('title_archives'), '', '', 'archives_import'); // FIXME?
	show_tabs_links($tabs, 1, true);

	echo '<div class="sys_msg_box">' . "\n";
	show_page_subtitle("Import finished", 'archives_import'); // FIXME TRAD? 

	echo "Backup '$input_filename' was successfully imported into database."; // TRAD
	echo "</div\n>";
	lcm_page_end();

}

function upload_backup_file() {
	// File name and extention
	$fname = "";
	$fext  = "";

	// Clear all previous errors
	$_SESSION['errors'] = array();

	if (! is_uploaded_file($_FILES['filename']['tmp_name'])) {
		// FIXME: error message
		$_SESSION['errors']['upload_file'] = '1 - not a valid file'; // TRAD
		return;
	}

	if (! ($_FILES['filename']['size'] > 0)) {
		// FIXME: error message
		$_SESSION['errors']['upload_file'] = 'size is zero'; // TRAD
		return;
	}

	// File should be: name.tar or name.tar.gz or name.tgz
	// name can be pretty much anything, since it will be rawurlencoded()
	// if it is prefixed with "db-", it will be removed and later added again
	if (preg_match("/^(db-)?(.+)\.(tar(\.gz)?|tgz)$/", $_FILES['filename']['name'], $regs)) {
		$fname = rawurlencode($regs[2]);
		$fext  = $regs[3];
	} else {
		// FIXME: error
		$_SESSION['errors']['upload_file'] = 'name not accepted'; // TRAD
		return;
	}

	$cpt = 0;
	while (file_exists(DIR_BACKUPS_PREFIX . $fname . ($cpt ? "-" . $cpt : '') . "." . $fext))
		$cpt++;

	$fname_full = DIR_BACKUPS_PREFIX . $fname . ($cpt ? "-" . $cpt : '') . "." . $fext;

	if (! move_uploaded_file($_FILES['filename']['tmp_name'], $fname_full)) {
		// FIXME: error message
		$_SESSION['errors']['upload_file'] = 'move_uploaded_file freaked out'; // TRAD
		return;
	}

	if (is_file($fname_full)) {
		// unpackage
		@include("Archive/Tar.php");
		$tar_worked = false;

		if (class_exists("Archive_Tar")) {
			$tar_worked = true;

			$old_dir = getcwd();
			chdir(DIR_BACKUPS);

			$tar_object = new Archive_Tar($fname_full);
			$tar_object->setErrorHandling(PEAR_ERROR_PRINT);

			// XXX is this safe to do this here? What if file exists?
			// FIXME: check extractList() to modify dest path
			$tar_object->extract();

			chdir($old_dir);

			lcm_debug("untar should be OK");
		} else {
			$_SESSION['errors']['upload_file'] = "Archive::Tar not installed"; // TRAD
			lcm_log("Archive::Tar not installed");
			return;
		}
	} else {
		lcm_panic("This should not happen...");
	}
}

//
// Main
//

global $author_session;

// Restrict page to administrators
if ($author_session['status'] != 'admin') {
	lcm_page_start(_T('title_archives'), '', '', 'archives_import');
	echo '<p class="normal_text">' . _T('warning_forbidden_not_admin') . "</p>\n";
	lcm_page_end();
	exit;
}

switch(_request('action')) {
	case 'upload_file':
		upload_backup_file(_request('file'));
		show_import_form();
		break;
	case 'import':
		if (($f = _request('file'))) {
			import_database($f);
		} else {
			// FIXME: show error message
			show_import_form();
		}
		break;
	default:
		show_import_form();
}


?>
