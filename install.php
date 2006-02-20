<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2005 Free Software Foundation, Inc.

	Note: This file was initially based on SPIP's install.php3 
	(http://www.spip.net).

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

	$Id: install.php,v 1.50 2006/02/20 03:16:35 mlutfy Exp $
*/

session_start();

include('inc/inc_version.php');
include_lcm('inc_presentation');
include_lcm('inc_filters');
include_lcm('inc_db');

//
// Helper functions
//

function put_text_in_textbox($text) {
	$textbox = "";
	$lines = count(explode("\n", $text));

	if ($lines <= 1)
		$lines = 2;

	$textbox  = "<form action='get'>\n";
	$textbox .= "\t<textarea readonly='readonly' cols='60' wrap='off' rows='$lines' dir='ltr'>";
	$textbox .= $text;
	$textbox .= "</textarea>\n";
	$textbox .= "</form>\n";

	return $textbox;
}

function get_number_admins() {
	$query = "SELECT name_first, name_middle, name_last, username
			  FROM lcm_author
			  WHERE status = 'admin'";

	$result = lcm_query($query);
	$number = lcm_num_rows($result);
	return $number;
}


//
// Main program
//

use_language_of_visitor();

// Test if the software is already installed
if (include_config_exists('inc_connect')) {
	install_html_start();

	// forbidden area
	echo "<div class='box_error'>\n";
	echo "\t<h3>" . _T('warning_forbidden_area') . "</h3>\n";
	echo "\t<p>" . _T('warning_already_installed') . "</p>\n";
	echo "</div>\n";

	install_html_end();
	exit;
}

//
// Main installation steps
//

$step = $_REQUEST['step'];

function install_step_5() {
	include_config('inc_connect_install');
	include_lcm('inc_meta');
	include_lcm('inc_access');

	// Either leave the form completely empty, or fill in everything
	if ($_REQUEST['username'] || $_REQUEST['name_first'] || $_REQUEST['name_last'] || $_REQUEST['email']) {
		$_SESSION['usr']['name_first']  = addslashes($_REQUEST['name_first']);
		$_SESSION['usr']['name_middle'] = addslashes($_REQUEST['name_middle']);
		$_SESSION['usr']['name_last']   = addslashes($_REQUEST['name_last']);
		$_SESSION['usr']['username']    = addslashes($_REQUEST['username']);
		$_SESSION['usr']['email']       = addslashes($_REQUEST['email']);

		// Test mandatory fields, sorry for the ugly code
		$mandatory = array(
			'name_first' => 'person_input',
			'name_last'  => 'person_input',
			'username'   => 'authoredit_input',
			// 'email'      => 'input', // [ML] too annoying
			'password'   => 'authorconf_input',
			'password_confirm' => 'authorconf_input');

		foreach ($mandatory as $mn => $str) {
			if (! $_REQUEST[$mn])
				$_SESSION['errors'][$mn] = _T($str . '_' . $mn) . ' ' . _T('warning_field_mandatory');
		}

		if ($_REQUEST['password'] != $_REQUEST['password_confirm'])
			$_SESSION['errors']['password'] = _T('login_warning_password_dont_match');

		if (count($_SESSION['errors']))
			return install_step_4();

		$query = "SELECT id_author FROM lcm_author WHERE username='" . $_SESSION['usr']['username'] . "'";
		$result = lcm_query($query);

		unset($id_author);
		while ($row = lcm_fetch_array($result))
			$id_author = $row['id_author'];

		// If user exists, allow to reset a forgotten password, which is possible
		// by deleting inc_connect.php and re-installing (it does not affect the DB).
		$query = "SET name_first = '" . $_SESSION['usr']['name_first'] . "', 
					name_middle = '" . $_SESSION['usr']['name_middle'] . "', 
					name_last = '" . $_SESSION['usr']['name_last'] . "', 
					username = '" . $_SESSION['usr']['username'] . "', 
					date_update = NOW(),
					alea_actuel = '', 
					alea_futur = FLOOR(32000*RAND()), 
					status = 'admin'";

		if ($id_author) {
			$query = "UPDATE lcm_author " . $query . " WHERE id_author = " . $id_author;
			lcm_query_db($query);
		} else {
			$query = "INSERT INTO lcm_author " . $query;
			$query .= ", date_creation = NOW()";
			lcm_query_db($query);
			$id_author = lcm_insert_id();
		}

		//
		// Set password
		//
		$class_auth = 'Auth_db';
		include_lcm('inc_auth_db');
		$auth = new $class_auth;

		if (! $auth->init()) {
			lcm_log("pass change: failed auth init: " . $auth->error);
			$_SESSION['errors']['password'] = $auth->error;
			return;
		}

		if (! $auth->newpass($id_author, $_SESSION['usr']['username'], $_REQUEST['password']))
			$_SESSION['errors']['password'] = $auth->error;

		if (count($_SESSION['errors'])) {
			header("Location: install.php?step=4");
			exit;
		}

		//
		// Set e-mail for author
		//
		if ($_SESSION['usr']['email']) {
			include_lcm('inc_contacts');

			if (! is_existing_contact('author', $id_author, 'email_main', $_SESSION['usr']['email']))
				add_contact('author', $id_author, 'email_main', $_SESSION['usr']['email']);

			// Insert email as main system administrator
			write_meta('email_sysadmin', $_SESSION['usr']['email']);
		}
	} else {
		// Test if an administrator already exists
		$number_admins = get_number_admins();

		if (! $number_admins) {
			$_SESSION['errors']['generic'] = _T('install_warning_no_admins_exist');
			header("Location: install.php?step=4");
			exit;
		}
	}

	$site_address = read_meta('site_address');
	if (! $site_address) {
		global $HTTP_SERVER_VARS, $HTTP_HOST;

		// Replace www.site.net/foo/name.php -> www.site.net/foo/
		$site_address = $_SERVER['REQUEST_URI'];
		if (!$site_address) $site_address = $_ENV['PHP_SELF']; // [ML] unsure
		$site_address = preg_replace("/\/[^\/]+\.php$/", "/", $site_address);
		$site_address = 'http://' . $_SERVER['HTTP_HOST'] /* $GLOBALS['SERVER_NAME'] */ . $site_address;

		write_meta('site_address', $site_address);
	}

	// Force regeneration of metas, just in case..
	$lcm_meta_cache = 'inc_meta_cache.php';
	if (isset($_SERVER['LcmDataDir']))
		$lcm_meta_cache = $_SERVER['LcmDataDir'] . '/' . $lcm_meta_cache;
	else
		$lcm_meta_cache = 'inc/data/' . $lcm_meta_cache;

	@unlink($lcm_meta_cache);
	write_metas();

	// Finalise installation
	$lcm_config_prefix = (isset($_SERVER['LcmConfigDir']) ?  $_SERVER['LcmConfigDir'] : 'inc/config');

	if (!@rename($lcm_config_prefix . '/inc_connect_install.php', $lcm_config_prefix . '/inc_connect.php')) {
		copy($lcm_config_prefix . '/inc_connect_install.php', $lcm_config_prefix . '/inc_connect.php');
		@unlink($lcm_config_prefix . '/inc_connect_install.php');
	}

	echo "<h3><small>" . _T('install_step_last') . "</small></h3>\n";

	echo "<div class='box_success'>\n";
	echo "<p><b>"._T('install_info_do_not_forget') . "</b></p>\n";
	echo "<p>" . _T('install_info_application_ready') . "</p>\n";
	echo "</div>\n\n";

	echo "<form action='index.php' method='post'>\n";
	echo "<div align='$lcm_lang_right'>"
		. "<button type='submit' name='Next'>" . _T('button_next')." >></button>&nbsp;"
		. "</div>\n";
	echo "</form>\n";
}

function install_step_4() {
	echo "<h3><small>" . _T('install_step_four') . "</small> "
		. _T('install_title_admin_account') . "</h3>\n";

	include_config('inc_connect_install');

	echo '<p class="simple_text">' 
		. _T('install_info_new_account_1') . '<br />'
		. _T('warning_field_mandatory_all') 
		. ' ' . lcm_help('install_personal') . "</p>\n";

	if (isset($_SESSION['errors']))
		echo show_all_errors($_SESSION['errors']);
	
	echo "<form action='install.php' method='post'>\n";
	echo "<input type='hidden' name='step' value='5' />\n";

	// Your contact information
	echo "<fieldset class=\"fs_box\">\n";
	echo "<p><b>" . _T('info_your_contact_information') . "</b></p>\n";

	// [ML] Altough not most problematic, could be better. But if someone
	// fixes here, please fix lcm_pass.php also (function print_registration_form())
	$name_first = (isset($_SESSION['usr']['name_first']) ?  $_SESSION['usr']['name_first'] : '');
	echo "<table border='0' cellpadding='0' cellspacing='5' width='80%'><tr>\n";
	echo "<td>
			<strong><label for='name_first'>" . f_err_star('name_first') . _T('person_input_name_first') . "</label></strong><br />
			<input type='text' style='width: 100%;' id='name_first' name='name_first' value='$name_first' size='15' class='txt_lmnt' />
		</td>\n";

	$name_last = (isset($_SESSION['usr']['name_last']) ?  $_SESSION['usr']['name_last'] : '');
	echo "<td>
			<strong><label for='name_last'>" . f_err_star('name_last') . _T('person_input_name_last') . "</label></strong><br />
			<input style='width: 100%;' type='text' id='name_last' name='name_last' value='$name_last' size='15' class='txt_lmnt' />
		</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>";

	$email = (isset($_SESSION['usr']['email']) ?  $_SESSION['usr']['email'] : '');
	echo "<p><b><label for='email'>" . f_err_star('email') . _T('input_email') . "</label></b><br />\n";
	echo "<input style='width: 100%;' type='text' id='email' name='email' value=\"$email\" size='40' class='txt_lmnt' /></p>\n";

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n\n";

	// Identifiers
	echo "<p><b>" . _T('input_connection_identifiers') . "</b></p>\n";

	$username = (isset($_SESSION['usr']['username']) ?  $_SESSION['usr']['username'] : '');
	echo "<table border='0' cellpadding='0' cellspacing='5' width='80%'>\n";
	echo "<tr>\n";
	echo "<td>";
	echo "<b><label for='username'>" . f_err_star('username') . _T('authoredit_input_username') . "</label></b> \n";
	echo "<small>" . _T('info_more_than_three') . "</small><br />\n";
	echo "<input style='width: 100%;' type='text' id='username' name='username' value='$username' size='40' class='txt_lmnt' />\n";
	echo "</td>\n";
	echo "</tr><tr>\n";
	echo "<td>";
	echo "<p><b><label for='password'>" . f_err_star('password') . _T('authorconf_input_password') . "</label></b> \n";
	echo "<small>" . _T('info_more_than_five')."</small><br />\n";
	echo "<input style='width: 100%;' type='password' id='password' name='password' value='' size='40' class='txt_lmnt' /></p>\n";
	echo "</td>\n";
	echo "</tr><tr>\n";
	echo "<td>";
	echo "<p><b><label for='password_confirm'>" . f_err_star('password') . _T('authorconf_input_password_confirm') . "</label></b> \n";
	echo "<input style='width: 100%;' type='password' id='password_confirm' name='password_confirm' value='' size='40' class='txt_lmnt' /></p>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "</fieldset>\n\n";

	echo "<br /><div align='$lcm_lang_right'>"
		. "<button type='submit' name='validate'>" . _T('button_next') . " >></button>&nbsp;"
		. "</div>\n";

	echo "</form>";

	$_SESSION['errors'] = array();
	$_SESSION['usr'] = array();
}

function install_step_3() {
	$db_address  = $_REQUEST['db_address'];
	$db_login    = $_REQUEST['db_login'];
	$db_password = $_REQUEST['db_password'];

	global $lcm_db_version;

	$install_log = "";
	$upgrade_log = "";

	// Comment out possible errors because the creation of new tables
	// over an already installed system is not a problem. Besides, we do
	// additional error reporting.
	echo "<!-- \n";

	if ($_REQUEST['db_choice'] == "__manual__") {
		$sel_db = $_REQUEST['manual_db'];
	} else {
		$sel_db = $_REQUEST['db_choice'];
	}

	$link = lcm_connect_db($db_address, 0, $db_login, $db_password, $sel_db);

	// FIXME
	if (! $link)
		lcm_panic("connection denied: " . lcm_sql_error());

	// Test if the software was already installed
	lcm_query("SELECT COUNT(*) FROM lcm_meta", true);
	$already_installed = !lcm_sql_errno();
	$old_lcm_version = 'NONE';

	if ($already_installed) {
		lcm_log("LCM already installed", 'install');

		// Find the current database version
		$old_lcm_db_version = 0;
		$query = "SELECT value FROM lcm_meta WHERE name = 'lcm_db_version'";
		$result = lcm_query_db($query);
		while ($row = lcm_fetch_array($result))
			$old_lcm_db_version = $row['value'];

		lcm_log("LCM version installed is $old_lcm_db_version", 'install');

		// Check if upgrade is needed
		if ($old_lcm_db_version < $lcm_db_version) {
			lcm_log("Calling the upgrade procedure (since < $lcm_db_version)", 'install');
			include_lcm('inc_db_upgrade');
			$upgrade_log  = upgrade_database($old_lcm_db_version);
		} else {
			lcm_log("Upgrade _not_ called, looks OK (= $lcm_db_version)", 'install');
		}
	} else {
		lcm_log("Creating the database from scratch", 'install');

		include_lcm('inc_db_create');
		$install_log .= create_database();

		lcm_log("DB creation complete", 'install');
	}

	// Create default meta + keywords
	include_lcm('inc_meta');
	include_lcm('inc_keywords_default');
	include_lcm('inc_meta_defaults');

	init_default_config();
	init_languages();

	$skwg = get_default_keywords();
	create_groups($skwg);
	write_metas(); // regenerate inc/data/inc_meta_cache.php

	// Test DB: not used for now..
	include_lcm('inc_db_test');
	$structure_ok = lcm_structure_test();

	// To simplify error listings
	echo "\n\n";
	echo "* . . . . . .\n";
	echo "* Existing: " . ($already_installed ? 'Yes (' . $old_lcm_version .  ')' : 'No') . "\n";
	echo "* Install: " . ($install_log ? 'ERROR(S), see listing' : 'INSTALL OK') . "\n";
	echo "* Upgrade: " . ($upgrade_log ? 'UPGRADED OK' : 'UPGRADE FAILED') . "\n";
	echo "* Integrity: " . ($structure_ok ? 'STRUCTURE OK' : 'VALIDATION FAILED') . "\n";
	echo "* . . . . . .\n\n";

	// echo "--> \n\n";
	echo " -->\n"; // end of 'hidden errors'

	if (! empty($install_log)) {
		echo "<h3><small>" . _T('install_step_three') . "</small> "
			. _T('install_title_creating_database') . "</h3>\n";

		echo "<div class='box_error'>\n";
		echo "\t<p>";
		echo "<b>" . _T('warning_operation_failed') . "</b> " . _T('install_database_install_failed');
		echo " " . lcm_help("install_connection") . "</p>\n";
		echo "</div>\n";

		// Dump error listing
		echo put_text_in_textbox($install_log);
	} else if (! empty($upgrade_log)) {
		echo "<h3><small>" . _T('install_step_three') . "</small> "
			. _T('install_title_creating_database') . "</h3>\n";

		echo "<div class='box_error'>\n";
		echo "\t<p>" . _T('install_warning_update_impossible', array('old_version' => $old_lcm_version, 'version' => $lcm_version)) . "</p>\n";
		echo "</div>\n";

		// Dump error listing
		echo put_text_in_textbox($upgrade_log);
	} else if (! $structure_ok) {
		echo "<h3><small>" . _T('install_step_three') . "</small> "
			. _T('install_title_creating_database') . "</h3>\n";

		echo "<div class='box_error'>\n";
		echo "\t<p> STRUCTURE PROBLEM </p>\n"; // TRAD
		echo "</div>\n";
	} else {
		// Everything OK

		$conn = '<' . '?php' . "\n";
		$conn .= "if (defined('_CONFIG_INC_CONNECT')) return;\n";
		$conn .= "define('_CONFIG_INC_CONNECT', '1');\n";
		$conn .= "\$GLOBALS['lcm_connect_version'] = 0.1;\n";
		$conn .= "include_lcm('inc_db');\n";
		$conn .= "@lcm_connect_db('$db_address','','$db_login','$db_password','$sel_db');\n";
		$conn .= "\$GLOBALS['db_ok'] = !!@lcm_num_rows(@lcm_query_db('SELECT COUNT(*) FROM lcm_meta'));\n";
		$conn .= '?'.'>';

		$lcm_config_prefix = (isset($_SERVER['LcmConfigDir']) ?  $_SERVER['LcmConfigDir'] : 'inc/config');
		$myFile = fopen($lcm_config_prefix . '/inc_connect_install.php', 'wb');
		fputs($myFile, $conn);
		fclose($myFile);

		install_step_4();
	}
}

function install_step_2() {
	$db_address  = $_SESSION['usr']['db_address']  = $_REQUEST['db_address'];
	$db_login    = $_SESSION['usr']['db_login']    = $_REQUEST['db_login'];
	$db_password = $_SESSION['usr']['db_password'] = $_REQUEST['db_password'];

	if (! ($db_login || $db_password)) {
		if (! $db_login)
			$_SESSION['errors']['login'] = _Ti('install_connection_login') . _T('warning_field_mandatory');

		if (! $db_password)
			$_SESSION['errors']['password'] = _Ti('install_connection_password') . _T('warning_field_mandatory');

		return install_step_1();
	}

	echo "\n<!--\n";
		$link = lcm_connect_db_test($db_address, $db_login, $db_password);
		$error = (lcm_sql_errno() ? lcm_sql_error() : '');
	echo "\n-->\n";

	if ($error || ! $link) {

		$_SESSION['errors']['generic'] = _T('warning_sql_connection_failed')
			// . ' ' . _T('install_info_go_back_verify_data')
			. ' ' . _T('install_info_sql_connection_failed')
			. ' (' . lcm_sql_errno() . ': ' . $error . ')';

		return install_step_1();

		/*
		echo "<h3><small>" . _T('install_step_two') . "</small> "
			. _T('install_title_connection_attempt') . "</h3>\n";

		echo "<div class='box_error'>\n";
		echo "<strong>" . _T('warning_sql_connection_failed') . "</strong>\n";
		echo "<p><code>" . $error . "</code></p>\n";
		echo "<p>"._T('install_info_go_back_verify_data') . ' ' . lcm_help('install_connection') . "</p>\n";
		echo "<p><small>" . _T('install_info_sql_connection_failed') . "</small></p>\n";
		echo "</div>\n\n";
		*/

	}

	echo "<h3><small>" . _T('install_step_two') .  "</small> "
		. _T('install_title_select_database') . "</h3>\n";

	echo "<form action='install.php' method='post'>\n";
	echo "\t<input type='hidden' name='step' value='3' />\n";
	echo "\t<input type='hidden' name='db_address' value=\"$db_address\" size='40' />\n";
	echo "\t<input type='hidden' name='db_login' value=\"$db_login\" />\n";
	echo "\t<input type='hidden' name='db_password' value=\"$db_password\" />\n\n";

	$result = lcm_list_databases($db_address, $db_login, $db_password);

	echo "<fieldset class='fs_box'>\n";
	echo "<p><b><label>" . _T('install_select_database') . "</label></b> "
		. lcm_help('install_database', 'database') . "</p>";

	echo "<!-- " . count($result) . " -->\n";

	if (is_array($result) && ($num = count($result)) > 0) {
		echo "<ul class=\"simple_list\">";
		$listdbtxt = "";

		for ($i = 0; $i < $num; $i++) {
			// $table_nom = mysql_dbname($result, $i);
			$table_name = array_pop($result);
			$base = "<li><input name='db_choice' value='" . $table_name . "' type='radio' id='tab$i'";
			$base_end = " /><label for='tab$i'>" . $table_name . "</label></li>\n";

			if ($table_name == $db_login) {
				$listdbtxt = "$base checked='checked'$base_end" . $listdbtxt;
				$checked = true;
			} else {
				$listdbtxt .= "$base$base_end\n";
			}
		}

		echo $listdbtxt;
		echo "</ul>\n";
	} else {
		echo "<div class='box_warning'>\n";
		echo "<p><b>" . _T('install_warning_no_databases_1') . "</b></p>\n";
		echo "<p><small>" . _T('install_warning_no_databases_2') . "</small></p>\n";
		echo "</div>\n";

		if ($db_login) {
			echo _T('install_warning_no_databases_3');
			echo "<ul class=\"simple_list\">";
			echo "<li><input name=\"db_choice\" value=\"" . $db_login . "\" type='radio' id='stand' checked='checked' />";
			echo "<label for='stand'>" . $db_login . "</label><br />\n";
			echo "</li></ul>";
			echo "<p align='left'>" . _T('info_or') . " ... </p>\n";
			$checked = true;
		}

		echo '<ul class="simple_list">';
		echo '<li><input name="db_choice" value="__manual__" type="radio" id="manual_db_checkbox"';
		if (!$checked) echo ' checked="checked"';
		echo " />\n";
		
		echo "<label for='manual_db_checkbox'>" . _T('install_enter_name_manually') . "</label><br />\n";

		echo "<label for='manual_db'>" . _T('install_input_database_name') . "</label>\n";
		echo "<input type='text' name='manual_db' id='manual_db' value='' size='20' class='txt_lmnt' /></li>\n";
		echo "</ul>\n";
	}

	echo "</fieldset>\n";
	echo "<br /><div align='$lcm_lang_right'>"
		. "<button type='submit' name='Next'>" . _T('button_next') . " >></button>&nbsp;"
		. "</div>\n";
	echo "</form>\n";
}

function install_step_1() {
	echo "<h3><small>" . _T('install_step_one') . "</small> "
		. _T('install_title_sql_connection') . "</h3>\n";

	echo show_all_errors($_SESSION['errors']);

	echo "<p class='simple_text'>" . _T('install_info_sql_connection') . " " . lcm_help("install_database") . "</p>\n";

	$db_address = (isset($_SESSION['usr']['db_address']) ? $_SESSION['usr']['db_address'] : 'localhost');
	$db_login = (isset($_SESSION['usr']['db_login']) ?  $_SESSION['usr']['db_login'] : '');
	$db_password = (isset($_SESSION['usr']['db_password']) ?  $_SESSION['usr']['db_password'] : '');

	// Fetch the previous configuration data to make things easier (if possible)
	$lcm_config_prefix = (isset($_SERVER['LcmConfigDir']) ?  $_SERVER['LcmConfigDir'] : 'inc/config');
	if (@file_exists($lcm_config_prefix . '/inc_connect_install.php')) {
		$s = @join('', @file($lcm_config_prefix . '/inc_connect_install.php'));
		if (ereg("mysql_connect\([\"'](.*)[\"'],[\"'](.*)[\"'],[\"'](.*)[\"']\)", $s, $regs)) {
			$db_address = $regs[1];
			$db_login = $regs[2];
		} else if (ereg("lcm_connect_db\('(.*)','(.*)','(.*)','(.*)','(.*)'\)", $s, $regs)) {
			$db_address = $regs[1];
			if ($port_db = $regs[2]) $db_address .= ':'.$port_db;
			$db_login = $regs[3];
		}
	}

	echo "<form action='install.php' method='post'>\n";
	echo "<input type='hidden' name='step' value='2' />\n";

	echo "<fieldset class='fs_box'>\n";

	echo "<div><label for='db_address'><strong>" . f_err_star('address') . _T('install_database_address') . "</strong></label></div>\n";
	echo "<input type='text' id='db_address' name='db_address' value=\"$db_address\" size='40' class='txt_lmnt' />\n";

	echo "<br />\n";
	echo "<br />\n";

	echo "<div><label for='db_login'><strong>" . f_err_star('login') . _T('install_connection_login') . "</strong></label></div>\n";
	echo "<input type='text' id='db_login' name='db_login' value=\"$db_login\" size='40' class='txt_lmnt' />\n";

	echo "<br />\n";
	echo "<br />\n";

	echo "<div><label for='db_password'><strong>" . f_err_star('password') . _T('install_connection_password') . "</strong></label></div>\n";
	echo "<input type='password' id='db_password' name='db_password' value=\"$db_password\" size='40' class='txt_lmnt' />\n";

	echo "</fieldset>\n";

	echo "<div align='$lcm_lang_right'>"
		. "<button type='submit' name='Next'>" . _T('button_next') . " >></button>&nbsp;"
		. "</div>\n";
	echo "</form>\n";
}

function call_step($step) {
	// Clear error handling
	$_SESSION['errors'] = array();
	$_SESSION['usr'] = array();

	install_html_start('AUTO', '', $step);

	$func = "install_step_" . $step;
	$func();

	install_html_end($step);

	// Clear error handling
	$_SESSION['errors'] = array();
	$_SESSION['usr'] = array();
}

if (1 <= $step && $step <= 5)
	call_step($step);
else if ($step == 'dirs')
	header("Location: lcm_test_dirs.php");
else if (!$step) {
	install_html_start('AUTO', '', "intro");

	$menu_lang = menu_languages('var_lang_lcm_all');

	echo "<div align='center'>\n";
	echo "<table border='0' cellspacing='0' width='490' height='242' style=\"background-image: url('images/lcm/lcm_logo_install.png'); border: 0\">\n";
	echo "<tr><td align='center' valign='top'>
			<div id='title'>
				<p><span style='font-size: 130%;'>" . _T('title_software') . "</span><br />
				<span style='font-size: 90%;'>" . _T('title_software_description') . "</span></p>
			</div>
		</td></tr>
		<tr><td align='center' valign='top'>
			<p id='license'>";

	echo _T('info_free_software', 
			array(
				'distributed' => '<a href="http://www.lcm.ngo-bg.org/" class="prefs_normal_lnk">' . _T('info_free_software1') . '</a>',
				'license' => lcm_help_string('about_license', _T('info_free_software2'))))
			. "</p>
		</td></tr>\n";
	echo "</table>\n";
	echo "</div>\n";

	echo "<p class=\"simple_text\">" . _T('install_select_language') . "</p>\n";

	echo "<div align='center'><p>" . $menu_lang . "</p></div>\n";

	echo "<form action='install.php' method='get'>\n";
	echo "\t<input type='hidden' name='step' value='dirs' />\n";
	echo "\t<div align='$lcm_lang_right'>"
		. "<button type='submit' name='Next'>" . _T('button_next')." >></button>&nbsp;"
		. "</div>";
	echo "</form>";

	install_html_end("intro");
}

?>
