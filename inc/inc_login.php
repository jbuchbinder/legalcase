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

	$Id: inc_login.php,v 1.27 2005/03/21 11:22:37 mlutfy Exp $
*/

if (defined('_INC_LOGIN')) return;
define('_INC_LOGIN', '1');

include('inc/inc_version.php');
include_lcm('inc_meta');
include_lcm('inc_session');
include_lcm('inc_filters');
include_lcm('inc_text');

function open_login($title='') {
	$text = "<div>\n";

	if ($title)
		$text .= "<h3>$title</h3>";

	$text .= '<div id="login_main">' . "\n";
	return $text;
}

function close_login() {
	$text =  "</div>";
	$text .= "</div>";
	return $text;
}

function login($cible, $prive = 'prive', $message_login='') {
	$pass_popup = 'href="lcm_pass.php" target="lcm_pass" '
		. ' onclick="' . "javascript:window.open('lcm_pass.php', 'lcm_pass', 'scrollbars=yes, resizable=yes, width=480, height=450'); return false;\"";

	$error = '';
	$login = (isset($GLOBALS['var_login']) ? $GLOBALS['var_login'] : '');
	$essai_auth_http = (isset($GLOBALS['var_essai_auth_http']) ? $GLOBALS['var_essai_auth_http'] : '');
	$logout = (isset($GLOBALS['var_logout']) ? $GLOBALS['var_logout'] : '');

	// If the cookie fails, inc_auth tried to redirect to lcm_cookie who
	// then tried to put a cookie. If it is not there, it is "cookie failed"
	// who is there, and it's probably a bookmark on privet=yes and not
	// a cookie failure.
	if (isset($GLOBALS['var_cookie_failed']))
		$cookie_failed = ($GLOBALS['lcm_session'] != 'cookie_test_failed');
	else
		$cookie_failed = "";

	global $author_session;
	global $lcm_session, $PHP_AUTH_USER, $ignore_auth_http;
	global $lcm_admin;
	global $php_module;
	global $clean_link;

	if (!$cible) {
		if (isset($GLOBALS['var_url']) && $GLOBALS['var_url'])
			$cible = new Link($GLOBALS['var_url']);
		else 
			$cible = new Link('index.php');
	}

	$cible->delVar('var_erreur');
	$cible->delVar('var_url');
	$cible->delVar('var_cookie_failed');
	$clean_link->delVar('var_erreur');
	$clean_link->delVar('var_login');
	$clean_link->delVar('var_cookie_failed');
	$url = $cible->getUrl();

	include_lcm('inc_session');
	verifier_visiteur();

	if ($author_session AND !$logout 
		AND ($author_session['status']=='admin' OR $author_session['status']=='normal'))
	{
		if ($url != $GLOBALS['clean_link']->getUrl())
			@Header("Location: " . $cible->getUrlForHeader());

		echo "<a href='$url'>"._T('login_this_way')."</a>\n";
		return;
	}

	if (isset($GLOBALS['var_erreur']) && $GLOBALS['var_erreur'] == 'pass')
		$error = _T('login_password_incorrect');

	// The login is memorized in the cookie for a possible future admin login
	if (!$login) {
		if (ereg("^@(.*)$", $lcm_admin, $regs))
			$login = $regs[1];
	} else if ($login == '-1')
		$login = '';

	// other sources for authentication
	$flag_autres_sources = (isset($GLOBALS['ldap_present']) ? $GLOBALS['ldap_present'] : '');

	// What informations to pass?
	if ($login) {
		$status_login = 0; // unknown status
		$login = addslashes($login);
		$query = "SELECT id_author, status, password, prefs, alea_actuel, alea_futur 
					FROM lcm_author 
					WHERE username='$login'";
		$result = lcm_query($query);
		if ($row = lcm_fetch_array($result)) {
			if ($row['status'] == 'trash' OR $row['password'] == '') {
				$status_login = -1; // deny
			} else {
				$status_login = 1; // known login

				// Which infos to pass for the javascript ?
				$id_author = $row['id_author'];
				$alea_actuel = $row['alea_actuel']; // for MD5
				$alea_futur = $row['alea_futur'];

				// Button for lenght of connection
				if ($row['prefs']) {
					$prefs = unserialize($row['prefs']);
					$rester_checked = ($prefs['cnx'] == 'perma' ? ' checked=\'checked\'':'');
				}
			}
		}

		// Unknown login (except LDAP) or refused
		if ($status_login == -1 OR ($status_login == 0 AND !$flag_autres_sources)) {
			$error = _T('login_identifier_unknown', array('login' => htmlspecialchars($login)));
			$login = '';
			@lcm_setcookie('lcm_admin', '', time() - 3600);
		}
	}
	
	// Javascript for the focus
	if ($login)
		$js_focus = 'document.form_login.session_password.focus();';
	else
		$js_focus = 'document.form_login.var_login.focus();';

	// [ML] we should probably add a help link here, since tech, but let's see 
	// how many users complain first, since this should affect only tech users
	if ($cookie_failed == "yes")
		$error = _T('login_warning_cookie');

	echo open_login();
	$action = $clean_link->getUrl();

	if ($login) {
		// Shows the login form, including the MD5 javascript
		$flag_challenge_md5 = true;

		if ($flag_challenge_md5)
			echo '<script type="text/javascript" src="inc/md5.js"></script>';

		echo "\n";
		echo '<form name="form_login" action="lcm_cookie.php" method="post"';

		if ($flag_challenge_md5)
			echo " onsubmit='if (this.session_password.value) {
				this.session_password_md5.value = calcMD5(\"$alea_actuel\" + this.session_password.value);
				this.next_session_password_md5.value = calcMD5(\"$alea_futur\" + this.session_password.value);
				this.session_password.value = \"\"; }'";

		echo ">\n";
		echo "<div class='main_login_box' style='text-align:".$GLOBALS["lcm_lang_left"].";'>\n";

		if ($error) 
			echo "<div style='color:red;'><b>" .  _T('login_access_denied') . " $error</b></div><br />\n";

		if ($flag_challenge_md5) {
			// This is printed with javascript so that it is hidden from navigators not
			// using JS, since they will see the username field anyway.
			echo "<script type=\"text/javascript\"><!--\n" 
				.  "document.write('" . addslashes(_T('login_login')) .  _T('typo_column') . " <b>$login</b><br/>"
				. "<font size=\\'2\\'><a href=\\'lcm_cookie.php?cookie_admin=no&amp;url=".rawurlencode($action)."\\' class=\\'link_btn\\'>" . _T('login_other_identifier') . "</a></font>');\n"
				.  "//--></script>\n";

			// If javascript is active, we pass the login in the hidden field
		 	echo "<input type='hidden' name='session_login_hidden' value='$login' />";

			// If javascript is not active, the login is still modifiable
			// (since the challenge is not used)
			echo "<noscript>";
		}

		echo "\t<label for='session_login'><b>" . _T('login_login') . _T('typo_column') . "</b> (" . _T('login_info_login').")<br /></label>";
		echo "\t<input type='text' name='session_login' id='session_login' class='forml' value=\"$login\" size='40' />\n";
		if ($flag_challenge_md5) echo "</noscript>\n";

		echo "\t<p />\n";
		echo "\t<label for='session_password'><b>" . _T('login_password') . _T('typo_column') . "</b><br /></label>";
		echo "\t<input type='password' name='session_password' id='session_password' class='forml' value=\"\" size='40' />\n";
		echo "\t<input type='hidden' name='essai_login' value='oui' />\n";

		echo "\t<br />&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='session_remember' value='yes' id='session_remember'$rester_checked /> ";
		echo "\t<label for='session_remember'>" . _T('login_remain_logged_on') . "</label>";

		echo "\t<input type='hidden' name='url' value='$url' />\n";
		echo "\t<input type='hidden' name='session_password_md5' value='' />\n";
		echo "\t<input type='hidden' name='next_session_password_md5' value='' />\n";
		echo "<div align='right'><input class='button_login' type='submit' value='"._T('button_validate')."' /></div>\n";
		echo "</div>";
		echo "</form>";
	} else {
		// Ask only for the login/username
		$action = quote_amp($action);
		echo "<form name='form_login' action='$action' method='post'>\n";
		echo "<div class='main_login_box' style='text-align:" . $GLOBALS["lcm_lang_left"] . ";'>";

		if ($error)
			echo "<span style='color:red;'><b>" . _T('login_access_denied') . " $error</b></span><p />";
			
		echo "<label><b>" . _T('login_login') . '</b> (' . _T('login_info_login') . ')' . _T('typo_column') . "<br /></label>";
		echo "<input type='text' name='var_login' class='forml' value=\"\" size='40' />\n";

		echo "<input type='hidden' name='var_url' value='$url' />\n";
		echo "<div align='right'><input class='button_login' type='submit' value='"._T('button_validate')."' /></div>\n";
		echo "</div>";
		echo "</form>";
	}

	// Focus management
	echo "<script type=\"text/javascript\"><!--\n" . $js_focus . "\n//--></script>\n";

	// Start the login footer
	echo "<div align='left' style='font-size: 12px;' >";

	echo "<div class='lang_combo_box'>". menu_languages() ."</div>\n";

	// button for "forgotten password"
	include_lcm('inc_mail');
	if (tester_mail()) {
		echo '<a href="lcm_pass.php?pass_forgotten=yes" target="lcm_pass" onclick="' ."javascript:window.open(this.href, 'lcm_pass', 'scrollbars=yes, resizable=yes, width=480, height=280'); return false;\" class=\"link_btn\">" ._T('login_password_forgotten').'</a>';
	}
	
	$open_subscription = read_meta("site_open_subscription");
	if ($open_subscription == 'yes' || $open_subscription == 'moderated')
		echo "&nbsp;&nbsp;&nbsp;<a $pass_popup class=\"link_btn\">" . _T('login_register').'</a>';

	echo "</div>\n";

	echo close_login();
}

?>
