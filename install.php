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
*/


include('inc/inc_version.php');
include_lcm('inc_presentation');
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

//
// Main program
//

use_language_of_visitor();

// Test if the software is already installed
if (@file_exists('inc/config/inc_connect.php')) {
	install_html_start();

	// forbidden area
	echo "<div class='box_error'>\n";
	echo "\t<h3>" . _T('warning_forbidden_area') . "</h3>\n";
	echo "\t<p>" . _T('title_software_article') . " " . _T('warning_already_installed') . "</p>\n";
	echo "</div>\n";

	install_html_end();
	exit;
}

//
// Main installation steps
//

if ($step == 6) {
	install_html_start();

	echo "<h3><small>" . _T('install_step_last') . _T('typo_column') .  "</small></h3>\n";

	// last step
	echo "<div class='box_success'>\n";
	echo "<p><b>"._T('install_info_do_not_forget') . "</b></p>\n";
	echo "<p>" . _T('install_info_application_ready') . "</p>\n";
	echo "</div>\n";

	include_config('inc_connect_install');
	include_lcm('inc_meta');
	include_lcm('inc_access');

	if ($username) {
		// If the login name already exists, this provides a way to reset
		// an administrator's account.
		$name_first  = addslashes($name_first);
		$name_middle = addslashes($name_middle);
		$name_last   = addslashes($name_last);
		$username    = addslashes($username);

		$query = "SELECT id_author FROM lcm_author WHERE username=\"$username\"";
		$result = lcm_query($query);

		unset($id_author);
		while ($row = lcm_fetch_array($result))
			$id_author = $row['id_author'];

		$mdpass = md5($pass);

		// Update main author information
		if ($id_author) {
			$query = "UPDATE lcm_author 
						SET name_first = \"$name_first\", 
							name_middle = \"$name_middle\", 
							name_last = \"$name_last\", 
							username = \"$username\", 
							password = \"$mdpass\", 
							alea_actuel = '', 
							alea_futur = FLOOR(32000*RAND()), 
							status = \"admin\" 
					  WHERE id_author = $id_author";
		} else {
			$query = "INSERT INTO lcm_author (name_first, name_middle, name_last, username, password, alea_futur, status)
							VALUES(\"$name_first\", \"$name_middle\", \"$name_last\", \"$username\", \"$mdpass\", FLOOR(32000*RAND()), \"admin\")";
		}

		lcm_query_db($query);

		// Set e-mail for author (if none)
		if ($email) {
			// Check if it is not already there
			$email_exists = false;
			$query = "SELECT value
						FROM lcm_contact
						WHERE id_of_person = $id_author 
							AND type_person = 'author' 
							AND type_contact = 1"; // EMAIL XXX
			$result = lcm_query($query);

			while ($row = lcm_fetch_array($result))
				if ($row['value'] == $email)
					$email_exists = true;

			// Insert email if not exist
			if (! $email_exists) {
				$query = "INSERT INTO lcm_contact (type_person, id_of_person, type_contact, value)
							VALUES(\"author\", $id_author, 1, \"" .  addslashes($email) . "\")";
				lcm_query($query);
			}
		}

		// Insert email as main system administrator
		write_meta('email_sysadmin', $email);
	} else {

		// TODO: We should test if any users exist at all, because it would
		// leave the system in a unusable state...

	}

	include_lcm('inc_meta_defaults');
	init_default_config();
	init_languages();

	// Block public access to the 'data' subdirectory
	// [ML] Moved data + config under inc, and blocked inc instead
	// [ML] But for now, we can ignore it. Why not just simply ship with a .htaccess included?
	/* ecrire_acces();
	$protec = "deny from all\n";
	$myFile = fopen('inc/.htaccess', 'w');
	fputs($myFile, $protec);
	fclose($myFile);
	*/

	@unlink('inc/data/inc_meta_cache.php');
	if (!@rename('inc/config/inc_connect_install.php', 'inc/config/inc_connect.php')) {
		copy('inc/config/inc_connect_install.php', 'inc/config/inc_connect.php');
		@unlink('inc/config/inc_connect_install.php');
	}

	echo "<form action='index.php' method='post'>";
	echo "<div align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'>";
	echo "</form>";

	write_metas();

	install_html_end();
}

else if ($step == 5) {
	install_html_start();

	echo "<h3><small>" . _T('install_step_five') . _T('typo_column') .  "</small> "
		. _T('install_title_admin_account') . "</h3>\n";

	include_config('inc_connect_install');

	// Test if an administrator already exists
	$query = "SELECT name_first, name_middle, name_last, username
			  FROM lcm_author
			  WHERE status = 'admin'";

	$result = lcm_query($query);
	$number_admins = lcm_num_rows($result);

	echo "<!-- Number of administrators: " . $number_admins . " -->\n";

	echo "<p>" . _T('install_info_new_account_1') . "</p>\n";
	// echo help ("install5");

	if ($numrows)
		echo "<p>" . _T('install_info_new_account_2') . "</p>\n";

	echo "\n<form action='install.php' method='post'>\n";
	echo "<input type='hidden' name='step' value='6'>\n";

	echo "<fieldset><label><b>". _T('info_your_contact_information') . "</b><br></label>\n";
	echo "<b>". _T('info_name_of_person') . "</b><br>\n";
	echo "<table border='0'>\n";
	echo "<tr>\n";
	echo "<td><small><label for='name_first'>" . _T('enter_name_first') .  "</label></small></td>\n";
	echo "<td><small><label for='name_middle'>" . _T('enter_name_middle') . "</label></small></td>\n";
	echo "<td><small><label for='name_last'>" . _T('enter_name_last') . "</label></small></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td><input type='text' id='name_first' name='name_first' class='formo' value='$name_first' size='20'></td>\n";
	echo "<td><input type='text' id='name_middle' name='name_middle' class='formo' value='$name_middle' size='20'></td>\n";
	echo "<td><input type='text' id='name_last' name='name_last' class='formo' value='$name_last' size='20'></td>\n";
	echo "<tr>\n";
	echo "</table>\n";

	echo "<b><label for='email'>" . _T('input_email') . "</label></b><br>";
	echo "<input type='text' id='email' name='email' class='formo' value=\"$email\" size='40'></fieldset><p>\n";

	echo "<fieldset><b>" . _T('input_connection_identifiers') . "</b><br/>";
	echo "<b><label for='username'>" . _T('login_login') . "</label></b><br>";
	echo "<small>" . _T('info_more_than_three') . "</small><br>";
	echo "<input type='text' id='username' name='username' class='formo' value=\"$username\" size='40'><p>\n";

	// TODO XXX
	// - Confirm the password?
	// - Error check if no authors and none created?

	echo "<b><label for='pass'>" . _T('login_password') . "</label></b><br>";
	echo "<small>" . _T('info_more_than_five')."</small><br>";
	echo "<input type='password' id='pass' name='pass' class='formo' value=\"$pass\" size='40'></fieldset><p>\n";

	echo "<div align='$spip_lang_right'><input type='submit' class='fondl' name='validate' value='"._T('button_next')." >>'>";
	echo "</form>";
	echo "<p>";

	/* [ML] Not used for now
	if ($flag_ldap AND !$ldap_present) {
		echo "<div style='border: 1px solid #404040; padding: 10px; text-align: left;'>";
		echo "<b>"._T('info_authentification_externe')."</b>";
		echo "<p>"._T('texte_annuaire_ldap_1');
		echo "<form action='install.php' method='post'>";
		echo "<input type='hidden' name='step' value='ldap1'>";
		echo "<div align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value=\""._T('bouton_acces_ldap')."\">";
		echo "</form>";
	} */

	install_html_end();
}

else if ($step == 4) {
	install_html_start();

	$install_log = "";
	$upgrade_log = "";

	echo "<h3><small>" . _T('install_step_four') . _T('typo_column') . "</small> "
		. _T('install_title_creating_database') . "</h3>\n";

	// Comment out possible errors because the creation of new tables
	// over an already installed system is not a problem. Besides, we do
	// additional error reporting.
	echo "<!-- \n\n";

	// TODO: Error checking
	if ($db_choice == "new_lcm") {
		$sel_db = $table_new;
		$link = lcm_connect_db($db_address, 0, $db_login, $db_password);
		@lcm_query_db("CREATE DATABASE $sel_db");

		if (lcm_sql_errno()) {
			$install_log = lcm_sql_error();
		} else {
			$link = lcm_connect_db($db_address, 0, $db_login, $db_password, $sel_db, $link);
		}
	} else {
		$sel_db = $db_choice;
		$link = lcm_connect_db($db_address, 0, $db_login, $db_password, $sel_db);
	}

	if (empty($install_log)) {
		// Test if the software was already installed
		@lcm_query_db("SELECT COUNT(*) FROM lcm_meta");
		$already_installed = !lcm_sql_errno();
		$old_lcm_version = 'NONE';

		if ($already_installed) {
			// Find the current database version
			$old_lcm_db_version = 0;
			$query = "SELECT value FROM lcm_meta WHERE name = 'lcm_db_version'";
			$result = lcm_query_db($query);
			while ($row = lcm_fetch_array($result))
				$old_lcm_db_version = $row['value'];

			// Check if upgrade is needed
			if ($old_lcm_db_version<$lcm_db_version) {
				// Upgrade the existing database
				include_lcm('inc_db_upgrade');
				$upgrade_log  = upgrade_database($old_lcm_db_version);
			}
		} else {
			// Create database from scratch
			include_lcm('inc_db_create');
			$install_log .= create_database();
		}

		include_lcm('inc_db_test');
		// Previous structure test. Not appropriate.
		// $query = "SELECT COUNT(*) FROM lcm_case";
		// $result = lcm_query_db($query);
		// $structure_ok = (lcm_num_rows($result) > 0);
		$structure_ok = lcm_structure_test();

		if (!$already_installed) {
			// [ML] This is not used at the moment (not checked once installed)
			$query = "INSERT lcm_meta (name, value) VALUES ('new_installation', 'yes')";
			lcm_query_db($query);
			$structure_ok = !lcm_sql_errno();
		}

		// To simplify error listings
		echo "\n\n";
		echo "* ------\n";
		echo "* Existing: " . ($already_installed ? 'Yes (' . $old_lcm_version .  ')' : 'No') . "\n";
		echo "* Install: " . ($install_log ? 'ERROR(S), see listing' : 'INSTALL OK') . "\n";
		echo "* Upgrade: " . ($upgrade_log ? 'UPGRADED OK' : 'UPGRADE FAILED') . "\n";
		echo "* Integrety: " . ($structure_ok ? 'STRUCTURE OK' : 'VALIDATION FAILED') . "\n";
		echo "* ------\n\n";
	}

	echo "--> \n\n";

	if (! empty($install_log)) {
		echo "<div class='box_error'>\n";
		echo "\t<p><b>" . _T('warning_operation_failed') . "</b> " .  _T('install_database_install_failed') . "</p>\n";
		echo "</div>\n";

		// Dump error listing
		echo put_text_in_textbox($install_log);
	} else if (! empty($upgrade_log)) {
		echo "<div class='box_error'>\n";
		echo "\t<p>" . _T('install_warning_update_impossible', array('old_version' => $old_lcm_version, 'version' => $lcm_version)) . "</p>\n";
		echo "</div>\n";

		// Dump error listing
		echo put_text_in_textbox($upgrade_log);
	} else if (! $structure_ok) {
		echo "<div class='box_error'>\n";
		// TODO TRANSLATE
		echo "\t<p> STRUCTURE PROBLEM </p>\n";
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

		$myFile = fopen('inc/config/inc_connect_install.php', 'wb');
		fputs($myFile, $conn);
		fclose($myFile);

		echo "<div class='box_success'>\n";
		echo "\t<p><b>" . _T('install_database_installed_ok') . "</b></p>\n";
		echo "</div>\n\n";

		echo "<p>" . _T('install_next_step') . "</p>\n\n";

		echo "<form action='install.php' method='post'>\n";
		echo "\t<input type='hidden' name='step' value='5'>\n";
		echo "\t<div align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'></div>\n";
		echo "</form>\n\n";
	}

	install_html_end();
}

else if ($step == 3) {
	install_html_start();

	echo "<h3><small>" . _T('install_step_three') . _T('typo_column') .  "</small> "
		. _T('install_title_select_database') . "</h3>\n";

	// [ML] TODO echo help ("install2");

	echo "<form action='install.php' method='post'>\n";
	echo "\t<input type='hidden' name='step' value='4'>\n";
	echo "\t<input type='hidden' name='db_address'  value=\"$db_address\" size='40'>\n";
	echo "\t<input type='hidden' name='db_login' value=\"$db_login\">\n";
	echo "\t<input type='hidden' name='db_password' value=\"$db_password\">\n";

	$result = lcm_list_databases($db_address, $db_login, $db_password);

	echo "<fieldset><label><b>" . _T('install_select_database') .  _T('typo_column') . "</b><br></label>";

	echo "<!-- " . count($result) . " -->\n";

	if (is_array($result) && ($num = count($result)) > 0) {
		echo "<ul>";
		$listdbtxt = "";

		for ($i = 0; $i < $num; $i++) {
			// $table_nom = mysql_dbname($result, $i);
			$table_name = array_pop($result);
			$base = "<input name='db_choice' value='" . $table_name . "' type='radio' id='tab$i'";
			$base_end = "><label for='tab$i'>" . $table_name . "</label><br>\n";

			if ($table_name == $db_login) {
				$listdbtxt = "$base CHECKED$base_end" . $listdbtxt;
				$checked = true;
			} else {
				$listdbtxt .= "$base$base_end\n";
			}
		}

		echo $listdbtxt;
		echo "</ul>\n";
		echo "<p>" . _T('info_or') . " ... </p>\n";
	} else {
		echo "<div class='box_warning'>\n";
		echo "<p><b>" . _T('install_warning_no_databases_1') . "</b></p>\n";
		echo "<p><small>" . _T('install_warning_no_databases_2') . "</small></p>\n";
		echo "</div>\n";

		if ($db_login) {
			echo _T('install_warning_no_databases_3');
			echo "<ul>";
			echo "<input name=\"db_choice\" value=\"" . $db_login . "\" type=Radio id='stand' CHECKED>";
			echo "<label for='stand'>" . $db_login . "</label><br>\n";
			echo "</ul>";
			echo "<p>" . _T('info_or') . " ... </p>\n";
			$checked = true;
		}
	}

	echo "<p><label for='new_db'>" . _T('install_create_new_database') .  _T('typo_column') . "</label></p>\n";
	echo "<ul>\n";
	echo "<input name='db_choice' value='new_lcm' type=Radio id='new_db'";
	if (!$checked) echo " CHECKED";
	echo "> ";
	echo "<input type='text' name='table_new' class='fondo' value=\"lcm\" size='20'></fieldset><p>\n";
	echo "<div align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'></div>\n";
	echo "</form>\n";

	install_html_end();
}

else if ($step == 2) {
	install_html_start();

	echo "<h3><small>" . _T('install_step_two') . _T('typo_column') . "</small> "
		. _T('install_title_connection_attempt') . "</h3>\n";

	echo "\n<!--\n";
		$link = lcm_connect_db_test($db_address, $db_login, $db_password);
		$db_connect = lcm_sql_errno();
		echo "SQL ERRNO: $db_connect\n";
	echo "\n-->\n";

	if (($db_connect == "0") && $link) {
		echo "<div class='box_success'>\n";
		echo "\t<b>" . _T('install_connection_succeeded') . "</b>\n";
		echo "</div>\n";
		echo "<p>" . _T('install_next_step') . "</p>";

		echo "<form action='install.php' method='post'>";
		echo "\t<input type='hidden' name='step' value='3'>";
		echo "\t<input type='hidden' name='db_address'  value=\"$db_address\" size='40'>";
		echo "\t<input type='hidden' name='db_login' value=\"$db_login\">";
		echo "\t<input type='hidden' name='db_password' value=\"$db_password\"><P>";
		echo "\t<div align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'></div>";
		echo "</form>";
	} else {
		echo "<div class='box_error'>\n";
		echo "\t<b>"._T('warning_sql_connection_failed') . "</b>\n";
		echo "\t<p>"._T('install_info_go_back_verify_data') . "\n";
		echo "\t<p><small>" .  _T('install_info_sql_connection_failed') .  "</small></p>\n";
		echo "</div>\n\n";
	}

	install_html_end();
}

else if ($step == 1) {
	install_html_start();

	echo "<h3><small>" . _T('install_step_one') . _T('typo_column') .  "</small> "
		.  _T('install_title_sql_connection') . "</h3>\n";

	echo "<p>" . _T('install_info_sql_connection') . "</p>\n";

	// [ML] TODO echo help ("install1");

	$db_address = 'localhost';
	$db_login = '';
	$db_password = '';

	// Fetch the previous configuration data to make things easier (if possible)
	if (@file_exists('inc/config/inc_connect_install.php')) {
		$s = @join('', @file('inc/config/inc_connect_install.php'));
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
	echo "\t<input type='hidden' name='step' value='2'>\n";
	echo "\t<fieldset>\n";
	echo "\t\t<div><label for='db_address'><b>" .  _T('install_database_address') . "</b></label></div>\n";
	echo "\t\t<div style='font-size: 85%;'>" . _T('install_info_database_address') . "</div>\n";
	echo "\t\t<input type='text' id='db_address' name='db_address' class='formo' value=\"$db_address\" size='40'>\n";
	echo "\t</fieldset><p>\n";

	echo "\t<fieldset>\n";
	echo "\t\t<div><label for='db_login'><b>" .  _T('install_connection_login') . "</b></div></label>\n";
	echo "\t\t<div style='font-size: 85%;'>(" . _T('install_info_connection_login') . ")</div>\n";
	echo "\t\t<input type='text' id='db_login' name='db_login' class='formo' value=\"$db_login\" size='40'>\n";
	echo "\t</fieldset><p>";

	echo "\t<fieldset>\n";
	echo "\t\t<div><label for='db_password'><b>" .  _T('install_connection_password') . "</b></div></label>\n";
	echo "\t\t<div style='font-size: 85%;'>(" . _T('install_info_connection_password') . ")</div>\n";
	echo "\t\t<input type='password' id='db_password' name='db_password' class='formo' value=\"$db_password\" size='40'>\n";
	echo "\t</fieldset><p>";

	echo "\t<div align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'></div>";
	echo "</form>";

	install_html_end();
}

else if ($step == 'dirs') {
	header("Location: lcm_test_dirs.php");
}

else if (!$step) {
	$menu_lang = menu_languages('var_lang_lcm_all');

	install_html_start();

	echo "<div align='center'>\n";
	echo "<table border='0' cellspacing='0' width='490' height='242' style=\"background-image: url('images/lcm/lcm_logo_install.png'); border: 0\">\n";
	echo "<tr><td align='center' valign='middle'>
			<div style='background-color: #ffffff; -moz-opacity: 0.7; filter: alpha(opacity=70);'>
				<p><span style='font-size: 130%;'>" .  _T('title_software') . "</span><br/>
				<span style='font-size: 90%;'>" .  _T('title_software_description') . "</span></p>
			</div>
		</td></tr>\n";
	echo "</table>\n";
	echo "</div>\n";

	echo "<p>" . _T('install_select_language') . "</p>\n";

	echo "<div align='center'><p>" . $menu_lang . "</p></div>\n";

	echo "<form action='install.php' method='get'>";
	echo "\t<input type='hidden' name='step' value='dirs'>";
	echo "\t<div align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'></div>";
	echo "</form>";

	install_html_end();
}


//
// Steps for LDAP installation
// [ML] For now, lets ignore this
//

else if ($step == 'ldap5') {
	install_html_start();

	include_ecrire('inc_connect_install.php3');
	include_ecrire('inc_meta.php3');
	write_meta("ldap_statut_import", $statut_ldap);
	write_metas();

	echo "<B>"._T('info_ldap_ok')."</B>";
	echo "<P>"._T('info_terminer_installation');

	echo "<form action='install.php' method='post'>";
	echo "<input type='hidden' name='step' value='5'>";

	echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'>";

	echo "</FORM>";
}

else if ($step == 'ldap4') {
	install_html_start();

	if (!$base_ldap) $base_ldap = $base_ldap_text;

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	// Try to validate the path provided
	$r = @ldap_compare($ldap_link, $base_ldap, "objectClass", "");
	$fail = (ldap_errno($ldap_link) == 32);

	if ($fail) {
		echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_chemin_acces_annuaire')."</B></FONT>";
		echo "<P>";

		echo "<B>"._T('avis_operation_echec')."</B> "._T('avis_chemin_invalide_1')." (<tt>".htmlspecialchars($base_ldap);
		echo "</tt>) "._T('avis_chemin_invalide_2');
	}
	else {
		echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_reglage_ldap')."</FONT>";
		echo "<P>";

		$conn = join('', file("inc_connect_install.php3"));
		if ($p = strpos($conn, '?'.'>')) 
			$conn = substr($conn, 0, $p);
		if (!strpos($conn, 'spip_connect_ldap')) {
			$conn .= "function spip_connect_ldap() {\n";
			$conn .= "\t\$GLOBALS['ldap_link'] = @ldap_connect(\"$adresse_ldap\",\"$port_ldap\");\n";
			$conn .= "\t@ldap_bind(\$GLOBALS['ldap_link'],\"$login_ldap\",\"$pass_ldap\");\n";
			$conn .= "\treturn \$GLOBALS['ldap_link'];\n";
			$conn .= "}\n";
			$conn .= "\$GLOBALS['ldap_base'] = \"$base_ldap\";\n";
			$conn .= "\$GLOBALS['ldap_present'] = true;\n";
		}
		$conn .= "?".">";
		$myFile = fopen("inc_connect_install.php3", "wb");
		fputs($myFile, $conn);
		fclose($myFile);

		echo "<p><form action='install.php' method='post'>";
		echo "<input type='hidden' name='step' value='ldap5'>";
		echo "<fieldset><label><B>"._T('info_statut_utilisateurs_1')."</B></label><BR>";
		echo _T('info_statut_utilisateurs_2')." ";
		echo "<p>";
		echo "<input type='Radio' name='statut_ldap' value=\"external\" id='external'>";
		echo "<label for='visit'><b>"._T('info_visiteur_1')."</b></label> "._T('info_visiteur_2')."<br>";
		echo "<input type='Radio' name='statut_ldap' value=\"normal\" id='normal' CHECKED>";
		echo "<label for='redac'><b>"._T('info_redacteur_1')."</b></label> "._T('info_redacteur_2')."<br>";
		echo "<input type='Radio' name='statut_ldap' value=\"admin\" id='admin'>";
		echo "<label for='admin'><b>"._T('info_administrateur_1')."</b></label> "._T('info_administrateur_2')."<br>";
	
		echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'>";

		echo "</FORM>";
	}

	install_html_end();
}

else if ($step == 'ldap3') {
	install_html_start();

	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' size=3>"._T('info_chemin_acces_1')."</FONT>";

	echo "<P>"._T('info_chemin_acces_2');

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	@ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	$result = @ldap_read($ldap_link, "", "objectclass=*", array("namingContexts"));
	$info = @ldap_get_entries($ldap_link, $result);

	echo "<form action='install.php' method='post'>";
	echo "<input type='hidden' name='step' value='ldap4'>";
	echo "<input type='hidden' name='adresse_ldap' value=\"$adresse_ldap\">";
	echo "<input type='hidden' name='port_ldap' value=\"$port_ldap\">";
	echo "<input type='hidden' name='login_ldap' value=\"$login_ldap\">";
	echo "<input type='hidden' name='pass_ldap' value=\"$pass_ldap\">";

	echo "<fieldset>";

	$checked = false;

	if (is_array($info) AND $info["count"] > 0) {
		echo "<P>"._T('info_selection_chemin_acces');
		echo "<UL>";
		$n = 0;
		for ($i = 0; $i < $info["count"]; $i++) {
			$names = $info[$i]["namingcontexts"];
			if (is_array($names)) {
				for ($j = 0; $j < $names["count"]; $j++) {
					$n++;
					echo "<input name=\"base_ldap\" value=\"".htmlspecialchars($names[$j])."\" type='Radio' id='tab$n'";
					if (!$checked) {
						echo " CHECKED";
						$checked = true;
					}
					echo ">";
					echo "<label for='tab$n'>".htmlspecialchars($names[$j])."</label><BR>\n";
				}
			}
		}
		echo "</UL>";
		echo _T('info_ou')." ";
	}
	echo "<input name=\"base_ldap\" value=\"\" type='Radio' id='manuel'";
	if (!$checked) {
		echo " CHECKED";
		$checked = true;
	}
	echo ">";
	echo "<label for='manuel'>"._T('entree_chemin_acces')."</label> ";
	echo "<input type='text' name='base_ldap_text' class='formo' value=\"ou=users, dc=mon-domaine, dc=com\" size='40'></fieldset><P>";

	echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'>";
	echo "</FORM>";

	install_html_end();
}

else if ($step == 'ldap2') {
	install_html_start();

	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size='3'>"._T('titre_connexion_ldap')."</font>";
	echo "<p>";

	$ldap_link = @ldap_connect("$adresse_ldap", "$port_ldap");
	$r = @ldap_bind($ldap_link, "$login_ldap", "$pass_ldap");

	if ($ldap_link && ($r || !$login_ldap)) {
		echo "<B>"._T('info_connexion_ldap_ok');

		echo "<form action='install.php' method='post'>";
		echo "<input type='hidden' name='step' value='ldap3'>";
		echo "<input type='hidden' name='adresse_ldap' value=\"$adresse_ldap\">";
		echo "<input type='hidden' name='port_ldap' value=\"$port_ldap\">";
		echo "<input type='hidden' name='login_ldap' value=\"$login_ldap\">";
		echo "<input type='hidden' name='pass_ldap' value=\"$pass_ldap\">";

		echo "<DIV align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>"._T('avis_connexion_ldap_echec_1')."</B>";
		echo "<P>"._T('avis_connexion_ldap_echec_2');
		echo "<br>"._T('avis_connexion_ldap_echec_3');
	}

	install_html_end();
}

else if ($step == 'ldap1') {
	install_html_start();

	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size=3>"._T('titre_connexion_ldap')."</font>";

	echo "<P>"._T('entree_informations_connexion_ldap');

	$adresse_ldap = 'localhost';
	$port_ldap = 389;

	// Recuperer les anciennes donnees (si presentes)
	if (@file_exists("inc_connect_install.php3")) {
		$s = @join('', @file("inc_connect_install.php3"));
		if (ereg('ldap_connect\("(.*)","(.*)"\)', $s, $regs)) {
			$adresse_ldap = $regs[1];
			$port_ldap = $regs[2];
		}
	}

	echo "<p><form action='install.php' method='post'>";
	echo "<input type='hidden' name='step' value='ldap2'>";
	echo "<fieldset><label><b>"._T('entree_adresse_annuaire')."</b><br></label>";
	echo _T('texte_adresse_annuaire_1')."<br>";
	echo "<input type='text' name='adresse_ldap' class='formo' value=\"$adresse_ldap\" size='20'><p>";

	echo "<label><b>" . _T('entree_port_annuaire') . "</b><br></label>";
	echo _T('texte_port_annuaire') . "<br>";
	echo "<input type='text' name='port_ldap' class='formo' value=\"$port_ldap\" size='20'><P></fieldset>";

	echo "<p><fieldset>";
	echo _T('texte_acces_ldap_anonyme_1')." ";
	echo "<label><b>"._T('entree_login_ldap')."</b><br></label>";
	echo _T('texte_login_ldap_1')."<br>";
	echo "<input type='text' name='login_ldap' class='formo' value=\"\" size='40'><p>";

	echo "<label><b>"._T('entree_passe_ldap')."</b><br></label>";
	echo "<input type='password' name='pass_ldap' class='formo' value=\"\" size='40'></fieldset>";

	echo "<p><div align='$spip_lang_right'><input type='submit' class='fondl' name='Next' value='"._T('button_next')." >>'></div>";

	echo "</form>";

	install_html_end();
}


?>
