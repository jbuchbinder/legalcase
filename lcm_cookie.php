<?php

include ("inc/inc_version.php");
include_lcm ("inc_session");

// Determine where we want to fallback after the operation
if ($_REQUEST['url']) {
	$cible = new Link($_REQUEST['url']);
	if ($_REQUEST['referer']) // see config_author.php
		$cible->addVar('referer', $_REQUEST['referer']);
} else
	$cible = new Link('/');

// Replay the cookie to renew lcm_session
if ($change_session == 'yes' || $change_session == 'oui') {
	if (verifier_session($lcm_session)) {
		// Attention : seul celui qui a le bon IP a le droit de rejouer,
		// ainsi un eventuel voleur de cookie ne pourrait pas deconnecter
		// sa victime, mais se ferait deconnecter par elle.
		if ($author_session['hash_env'] == hash_env()) {
			$author_session['ip_change'] = false;
			$cookie = creer_cookie_session($author_session);
			supprimer_session($lcm_session);
			lcm_setcookie('lcm_session', $cookie);
		}
		@header('Content-Type: image/gif');
		@header('Expires: 0');
		@header("Cache-Control: no-store, no-cache, must-revalidate");
		@header('Pragma: no-cache');
		@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		@readfile('ecrire/img_pack/rien.gif');
		exit;
	}
}

// Attempt to logout
if ($logout) {
	include_lcm('inc_session');
	verifier_visiteur();

	if ($author_session['username'] == $logout) {
		if ($lcm_session) {
			zap_sessions($author_session['id_author'], true);
			lcm_setcookie('lcm_session', $lcm_session, time() - 3600 * 24);
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
// [ML] echec == failure
if ($cookie_test_failed == 'yes') {
	lcm_setcookie('lcm_session', 'cookie_test_failed');
	$link = new Link("lcm_login.php?var_cookie_failed=yes");
	$link->addVar("var_url", $cible->getUrl());
	@header("Location: ".$link->getUrl());
	exit;
}

// Attempt to login
unset ($cookie_session);
if ($essai_login == 'oui') {
	// Get the username stored in a hidden field
	if ($session_login_hidden AND !$session_login)
		$session_login = $session_login_hidden;

	$login = $session_login; // [ML] where from?
	$pass = $session_password; // [ML] not used??

	// Try different authentication methods, starting with "db" (database)
	$auths = array('db');

	// Test if LDAP is available
	include_config('inc_connect'); 
	if ($ldap_present) $auths[] = 'ldap';

	// Add other methods here (with associated inc/inc_auth_NAME.php)
	// ...

	$ok = false;
	reset($auths);
	while (list(, $nom_auth) = each($auths)) {
		include_lcm('inc_auth_'.$nom_auth);
		$classe_auth = 'Auth_'.$nom_auth;
		$auth = new $classe_auth;

		if ($auth->init()) {
			// Try with the md5 password (made by Javascript in the form)
			// [ML] TODO: session_password_md5 + next_session_password_md5 
			// should probably be refered to via _REQUEST... (test after!)
			$ok = $auth->validate_md5_challenge($login, $session_password_md5, $next_session_password_md5);

			// If failed, try as cleartext
			if (!$ok && $session_password)
				$ok = $auth->validate_pass_cleartext($login, $session_password);
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


// Set an administrative cookie?
// [ML] Not very useful I think
if ($cookie_admin == 'no') {
	lcm_setcookie('lcm_admin', $lcm_admin, time() - 3600 * 24);
	$cible->delVar('var_login');
	$cible->addVar('var_login', '-1');
} else if ($cookie_admin AND $lcm_admin != $cookie_admin) {
	lcm_setcookie('lcm_admin', $cookie_admin, time() + 3600 * 24 * 14);
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
if ($var_lang_lcm) {
	include_lcm('inc_lang');
	include_lcm('inc_session');

	$valid_author = verifier_visiteur();

	if (lcm_set_language($var_lang_lcm)) {
		lcm_setcookie('lcm_lang', $var_lang_lcm, time() + 365 * 24 * 3600);

		// Save language preference only if we are installed and if author connected
		if ($valid_author && @file_exists('inc/config/inc_connect.php')) {
			include_lcm('inc_admin');

			lcm_query("UPDATE lcm_author 
					SET lang = '" . addslashes($var_lang_lcm) . "' 
					WHERE id_author = " . $GLOBALS['author_session']['id_author']);
			$author_session['lang'] = $var_lang_lcm;
			lcm_add_session($author_session, $lcm_session);
		}

		$cible->delvar('lang');
		$cible->addvar('lang', $var_lang_lcm);
	}
}

// Redirection
// Under Apache, cookies with a redirection work
// Else, we do a HTTP refresh
if (ereg("^Apache", $SERVER_SOFTWARE)) {
	@header("Location: " . $cible->getUrl());
} else {
	@header("Refresh: 0; url=" . $cible->getUrl());
	echo "<html><head>";
	echo "<meta http-equiv='Refresh' content='0; url=".$cible->getUrl()."'>";
	echo "</head>\n";
	echo "<body><a href='".$cible->getUrl()."'>"._T('navigateur_pas_redirige')."</a></body></html>";
}

?>
