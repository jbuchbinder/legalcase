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

	$Id: inc_auth.php,v 1.31 2006/08/16 13:48:12 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_AUTH')) return;
define('_INC_AUTH', '1');

include_lcm('inc_meta'); // initiates the database connection
include_lcm('inc_session');
include_lcm('inc_db');


// [ML] Alot of things to adapt... XXX/TODO
function auth() {
	global $INSECURE, $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_COOKIE_VARS, $REMOTE_USER, $PHP_AUTH_USER, $PHP_AUTH_PW;
	global $auth_can_disconnect;

	global $connect_id_auteur, $connect_nom, $connect_bio, $connect_email;
	global $connect_nom_site, $connect_url_site, $connect_login, $connect_pass;
	global $connect_activer_imessage, $connect_activer_messagerie;
	global $connect_status;

	global $author_session, $prefs;
	global $clean_link;

	// This reloads $GLOBALS['db_ok'], just in case
	include_config('inc_connect');

	// If there is not SQL connection, quit.
	if (! $GLOBALS['db_ok']) {
		include_lcm('inc_presentation');
		lcm_html_start("Technical problem", "install");

		// annoy sql_errno()
		echo "\n<!-- \n";
		echo "\t* Flag connect: ". $GLOBALS['flag_connect'] ."\n\t";
		lcm_query("SELECT count(*) from lcm_meta");
		echo "\n-->\n\n";

		echo "<div align='left' style='width: 600px;' class='box_error'>\n";
		echo "\t<h3>". _T('title_technical_problem') ."</h3>\n";
		echo "\t<p>" . _T('info_technical_problem_database') . "</p>\n";

		if (lcm_sql_errno())
			echo "\t<p><tt>". lcm_sql_errno() ." ". lcm_sql_error() ."</tt></p>\n";
		else
			echo "\t<p><tt>No error diagnostic was provided.</tt></p>\n";

		echo "</div>\n";
		lcm_html_end();

		return false;
	}


	// Initialise variables (avoid URL hacks)
	$auth_login = "";
	$auth_pass = "";
	$auth_pass_ok = false;
	$auth_can_disconnect = false;

	// Fetch identification data from authentication session
	if (isset($_COOKIE['lcm_session'])) {
		if (verifier_session($_COOKIE['lcm_session'])) {
			if ($author_session['status'] == 'admin' OR $author_session['status'] == 'normal') {
				$auth_login = $author_session['username'];
				$auth_pass_ok = true;
				$auth_can_disconnect = true;
			}
		}
	} else if ($_REQUEST['privet'] == 'yes') {
		// Failed login attempt: cookie failed
		$link = new Link("lcm_cookie.php?cookie_test_failed=yes");
		$clean_link->delVar('privet');
		$url = str_replace('/./', '/', $clean_link->getUrl());
		$link->addVar('var_url', $url);
		@header("Location: ".$link->getUrl());
		exit;
	}

	// If not authenticated, ask for login / password
	if (!$auth_login) {
		$url = $clean_link->getUrl();
		@header("Location: lcm_login.php?var_url=".urlencode($url));
		exit;
	}

	//
	// Search for the login in the authors' table
	//

	$auth_login = addslashes($auth_login);
	$query = "SELECT * FROM lcm_author WHERE username='$auth_login' AND status !='external' AND status !='6forum'";
	$result = @lcm_query($query);

	if ($row = lcm_fetch_array($result)) {
		$connect_id_auteur = $row['id_author'];
		$connect_nom = $row['name_first'];
		$connect_login = $row['username'];
		$connect_pass = $row['password'];
		$connect_status = $row['status'];
		$connect_activer_messagerie = "non"; //$row["messagerie"];
		$connect_activer_imessage = "non "; //$row["imessage"];

		// Set the users' preferences
		$prefs = unserialize( ( get_magic_quotes_runtime() ? stripslashes($row['prefs']) : $row['prefs'] ) );

		//
		// Default values for some possibly unset preferences
		//

		if (! isset($prefs['page_rows']) || intval($prefs['page_rows']) < 1) 
			$prefs['page_rows'] = 15;

		if (! isset($prefs['theme']) || ! $prefs['theme'])
			$prefs['theme'] = 'green';

		if (! isset($prefs['screen']) || ! $prefs['screen'])
			$prefs['screen'] = 'wide';

		if (! isset($prefs['font_size']) || ! $prefs['font_size'])
			$prefs['font_size'] = 'medium_font';

		if (! isset($prefs['case_owner']) || ! $prefs['case_owner'])
			$prefs['case_owner'] = 'my';

		if (! isset($prefs['case_period']) || ! $prefs['case_period'])
			$prefs['case_period'] = '91';

		if (! isset($prefs['mode']) || ! $prefs['mode'])
			$prefs['mode'] = 'simple';

		if (! isset($prefs['time_intervals']) || ! $prefs['time_intervals']) {
			$prefs['time_intervals'] = 'relative';
			$prefs['time_intervals_notation'] = 'hours_only';
		}
	}
	else {
		// This case is a strange possibility: the author is authentified
		// OK, but he does not exist in the authors table. Possible cause:
		// the database was restaured and the author does not exist (and
		// the user was authentified by another source, such as LDAP).
		// Note: we use to show a strange error message which would advice
		// to logout, but since it occurs only after db upgrade, just logout
		// brutally (with cookie_admin=no to forget the username).

		lcm_header('Location: lcm_cookie.php?cookie_admin=no&logout=' . $auth_login);
		exit;
	}

	if (!$auth_pass_ok) {
		@header("Location: lcm_login.php?var_erreur=pass");
		exit;
	}

	// [ML] Again, not sure how this is used, but we can ignore it for now
	// TODO (note: nouveau == new)
	if ($connect_status == 'nouveau') {
		$query = "UPDATE lcm_author SET status = 'normal' WHERE id_author = $connect_id_auteur";
		$result = lcm_query($query);
		$connect_status = 'normal';
	}

	// PHP sessions are started here, and stopped at logout
	session_start();

	return true;
}


if (!auth()) exit;

?>
