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

	$Id: lcm_cookie.php,v 1.29 2006/10/26 13:35:36 mlutfy Exp $
*/

include("inc/inc_version.php");
include_lcm("inc_session");
include_lcm('inc_filters');

global $author_session;

// Determine where we want to fallback after the operation
if (_request('url')) {
	$cible = new Link(_request('url'));
	if (_request('referer')) // see config_author.php
		$cible->addVar('referer', _request('referer'));
} else {
	if (_request('logout'))
		$cible = new Link("index.php");
	else
		$cible = new Link(); // [ML] XXX uses current page, but this can create strange bugs..
}

// Replay the cookie to renew lcm_session
if (_request('change_session') == 'yes' || _request('change_session') == 'oui') {
	if (verifier_session($_COOKIE['lcm_session'])) {
		// Warning: only the user with the correct IP has the right to replay
		// the cookie, therefore a cookie theft cannot disconnect the vitim
		// but be disconnected by her.
		if ($author_session['hash_env'] == hash_env()) {
			$author_session['ip_change'] = false;
			$cookie = creer_cookie_session($author_session);
			delete_session($_COOKIE['lcm_session']);
			lcm_setcookie('lcm_session', $cookie);
		}
		@header('Content-Type: image/gif');
		@header('Expires: 0');
		@header("Cache-Control: no-store, no-cache, must-revalidate");
		@header('Pragma: no-cache');
		@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		@readfile('ecrire/img_pack/rien.gif'); // XXX change this
		exit;
	}
}

// If cookie_admin == no, delete the lcm_admin cookie
// This is the "connect with another identifier" on the login page
$cookie_admin = _request('cookie_admin');

if ($cookie_admin == 'no') {
	lcm_setcookie('lcm_admin', $lcm_admin, time() - 3600 * 24);
	$cible->delVar('var_login');
	$cible->addVar('var_login', '-1');
} else if ($cookie_admin AND $lcm_admin != $cookie_admin) {
	// Remember the username for the next login
	// This way, the user can login in only one form, not two
	lcm_setcookie('lcm_admin', $cookie_admin, time() + 3600 * 24 * 14);
}

// Attempt to logout
if (_request('logout')) {
	include_lcm('inc_session');
	verifier_visiteur();

	global $author_session;

	if ($author_session['username'] == $_REQUEST['logout']) {
		if ($_COOKIE['lcm_session']) {
			zap_sessions($author_session['id_author'], true);
			lcm_setcookie('lcm_session', $_COOKIE['lcm_session'], time() - 3600 * 24);
		}
		unset ($author_session);
	}

	if (!$url)
		@Header("Location: ./lcm_login.php");
	else
		@Header("Location: $url");
	exit;
}


// If the user logins with privet=yes (privet: greetings), we try to
// put a cookie and then go to lcm_login.php which will try to make 
// a diagnostic if necessary.
if (_request('cookie_test_failed') == 'yes') {
	lcm_setcookie('lcm_session', 'cookie_test_failed');
	$link = new Link("lcm_login.php?var_cookie_failed=yes");
	// [ML] This caused strange endless redirections. Since it does not happen often,
	// better to get rid of it, the user will be redirected to the home page anyway.
	// $link->addVar("var_url", $cible->getUrl());
	@header("Location: ".$link->getUrlForHeader());
	exit;
}

// Attempt to login
unset ($cookie_session);
if (_request('essai_login') == 'oui') {
	// Get the username stored in a hidden field
	$session_login_hidden = $_REQUEST['session_login_hidden'];
	$session_login = $_REQUEST['session_login'];
	$session_password = $_REQUEST['session_password'];

	if ($session_login_hidden AND !$session_login)
		$session_login = $session_login_hidden;

	$login = $session_login; // [ML] where from? -- html form
	$pass = $session_password; // [ML] not used?? -- html form

	// Try different authentication methods, starting with "db" (database)
	$auths = array('db');

	// Test if LDAP is available
	include_config('inc_connect'); 
	if ($ldap_present) $auths[] = 'ldap';

	// Add other methods here (with associated inc/inc_auth_NAME.php)
	// ...

	$ok = false;
	reset($auths);
	foreach($auths as $nom_auth) {
		include_lcm('inc_auth_'.$nom_auth);
		$classe_auth = 'Auth_'.$nom_auth;
		$auth = new $classe_auth;

		if ($auth->init()) {
			$session_password_md5 = $_REQUEST['session_password_md5'];

			// Try with the md5 password (made by Javascript in the form)
			// [ML] TODO: session_password_md5 + next_session_password_md5 
			// should probably be refered to via _REQUEST... (test after!)
			$ok = $auth->validate_md5_challenge($login, $session_password_md5, $next_session_password_md5);

			// If failed, try as cleartext
			if (!$ok && $session_password) {
				lcm_debug("md5 login failed, trying with plaintext");
				$ok = $auth->validate_pass_cleartext($login, $session_password);
			}
		}

		if ($ok) break;
	}

	if ($ok) $ok = $auth->lire();

	if ($ok) {
		$auth->activate();

		// Force cookies for admins
		if ($auth->username AND $auth->status == 'admin')
			$cookie_admin = "@" . $auth->username;

		$query = "SELECT * 
					FROM lcm_author
					WHERE username='".addslashes($auth->username)."'";
		$result = lcm_query($query);

		if ($row_author = lcm_fetch_array($result))
			$cookie_session = creer_cookie_session($row_author);

		$cible->addVar('privet','yes');
	} else {
		$cible = new Link("lcm_login.php");

		$cible->addVar('var_login', $login);
		$cible->addVar('var_url', urldecode($url));

		if ($session_password || $session_password_md5)
			$cible->addVar('var_erreur', 'pass');
	}
}

// Set a session cookie?
if ($cookie_session) {
	if ($session_remember == 'yes')
		lcm_setcookie('lcm_session', $cookie_session, time() + 3600 * 24 * 14);
	else
		lcm_setcookie('lcm_session', $cookie_session);

	$prefs = ($row_author['prefs']) ? unserialize($row_author['prefs']) : array();
	$prefs['cnx'] = ($session_remember == 'yes') ? 'perma' : '';

	lcm_query("UPDATE lcm_author 
				SET prefs = '" . addslashes(serialize($prefs)) . "' 
				WHERE id_author = " . $row_author['id_author']);
}

// Change the language of the private area (or login)
// [ML] I once wanted to put this in a function, and it did a hell
// of a mess because of the session handling stuff.. 
if (isset($_REQUEST['var_lang_lcm'])) {
	// ex: bg, fr, en, en_uk, etc. nothing else is accepted
	if (preg_match("/^[_A-Za-z]+[0-9]*$/", $_REQUEST['var_lang_lcm'])) {
		include_lcm('inc_lang');
		include_lcm('inc_session');

		$new_lang = clean_input($_REQUEST['var_lang_lcm']);
		$valid_author = verifier_visiteur();

		if (lcm_set_language($new_lang)) {
			lcm_setcookie('lcm_lang', $new_lang, time() + 365 * 24 * 3600);

			// Save language preference only if we are installed and if author connected
			if ($valid_author && include_config_exists('inc_connect')) {
				include_lcm('inc_admin');

				lcm_query("UPDATE lcm_author 
						SET lang = '" . $new_lang . "' 
						WHERE id_author = " . $GLOBALS['author_session']['id_author']);
				$author_session['lang'] = $new_lang;
				lcm_add_session($author_session, $_COOKIE['lcm_session']);
			} else {
				lcm_log("Not valid_author ($valid_author) or not yet installed");
			}

			$cible->delvar('lang');
			$cible->addvar('lang', $new_lang);
		} else {
			lcm_log("lcm_set_language() is not happy, wrong lang code?");
		}
	}
}

// Redirection
// Under Apache, cookies with a redirection work
// Else, we do a HTTP refresh
if (ereg("^Apache", $_SERVER['SERVER_SOFTWARE'])) {
	@header("Location: " . $cible->getUrlForHeader());
	exit;
} else {
	@header("Refresh: 0; url=" . $cible->getUrl());
	echo "<html><head>";
	echo "<meta http-equiv='Refresh' content='0; url=".$cible->getUrl()."'>";
	echo "</head>\n";
	echo "<body><a href='".$cible->getUrl()."'>"
		. "Redirecting.." // TRAD
		. "</a></body></html>";
	exit;
}

?>
