<?php

include ("inc/inc_version.php");
include_lcm ("inc_session");

// Determine where we want to fallback after the operation
if ($url)
	$cible = new Link($url);
else
	$cible = new Link('/');

// Replay the cookie to renew lcm_session
if ($change_session == 'oui') {
	if (verifier_session($lcm_session)) {
		// Attention : seul celui qui a le bon IP a le droit de rejouer,
		// ainsi un eventuel voleur de cookie ne pourrait pas deconnecter
		// sa victime, mais se ferait deconnecter par elle.
		if ($auteur_session['hash_env'] == hash_env()) {
			$auteur_session['ip_change'] = false;
			$cookie = creer_cookie_session($auteur_session);
			supprimer_session($lcm_session);
			spip_setcookie('lcm_session', $cookie);
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


// Attemp to connect via auth_http
// [ML] TODO
if ($essai_auth_http AND !$ignore_auth_http) {
	include_local ("inc-login.php3");
	auth_http($cible, $essai_auth_http);
	exit;
}

// Attempt to logout
if ($logout) {
	include_lcm('inc_session');
	verifier_visiteur();

	if ($auteur_session['username'] == $logout) {
		if ($lcm_session) {
			zap_sessions($auteur_session['id_author'], true);
			spip_setcookie('lcm_session', $lcm_session, time() - 3600 * 24);
		}
		if ($PHP_AUTH_USER AND !$ignore_auth_http) {
			include_local ("inc-login.php"); // [ML] XXX
			auth_http($cible, 'logout');
		}
		unset ($auteur_session);
	}

	$test = 'yes=' . $auteur_session['login'];

	if (!$url)
		@Header("Location: ./lcm_login.php" . '?' . $test);
	else
		@Header("Location: $url");
	exit;
}


// en cas de login sur bonjour=oui, on tente de poser un cookie
// puis de passer a spip_login qui diagnostiquera l'echec de cookie
// le cas echeant.
if ($test_echec_cookie == 'oui') {
	spip_setcookie('lcm_session', 'test_echec_cookie');
	$link = new Link("lcm_login.php?var_echec_cookie=oui");
	$link->addVar("var_url", $cible->getUrl());
	@header("Location: ".$link->getUrl());
	exit;
}

// Attempt to login
unset ($cookie_session);
if ($essai_login == 'oui') {
	// Recuperer le login en champ hidden
	if ($session_login_hidden AND !$session_login)
		$session_login = $session_login_hidden;

	$login = $session_login;
	$pass = $session_password;

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
			// Essayer les mots de passe md5
			$ok = $auth->verifier_challenge_md5($login, $session_password_md5, $next_session_password_md5);
			// Sinon essayer avec le mot de passe en clair
			if (!$ok && $session_password) $ok = $auth->verifier($login, $session_password);
		}
		if ($ok) break;
	}

	if ($ok) $ok = $auth->lire();

	if ($ok) {
		$auth->activer();

		if ($auth->login AND $auth->statut == 'admin') // force cookies for admins
			$cookie_admin = "@".$auth->login;

		$query = "SELECT * FROM lcm_author WHERE username='".addslashes($auth->login)."'";
		$result = spip_query($query);
		if ($row_auteur = spip_fetch_array($result))
			$cookie_session = creer_cookie_session($row_auteur);

		// [ML] if (ereg("ecrire/", $cible->getUrl())) {
			$cible->addVar('bonjour','oui');
		// }
	} else {
		if (ereg("ecrire/", $cible->getUrl())) {
			$cible = new Link("./lcm_login.php");
		}

		$cible->addVar('var_login', $login);
		if ($session_password || $session_password_md5)
			$cible->addVar('var_erreur', 'pass');
		$cible->addVar('var_url', urldecode($url));
	}
}


// cookie d'admin ?
if ($cookie_admin == 'non') {
	spip_setcookie('lcm_admin', $spip_admin, time() - 3600 * 24);
	$cible->delVar('var_login');
	$cible->addVar('var_login', '-1');
}
else if ($cookie_admin AND $spip_admin != $cookie_admin) {
	spip_setcookie('lcm_admin', $cookie_admin, time() + 3600 * 24 * 14);
}

// cookie de session ?
if ($cookie_session) {
	if ($session_remember == 'oui')
		spip_setcookie('lcm_session', $cookie_session, time() + 3600 * 24 * 14);
	else
		spip_setcookie('lcm_session', $cookie_session);

	$prefs = ($row_auteur['prefs']) ? unserialize($row_auteur['prefs']) : array();
	$prefs['cnx'] = ($session_remember == 'oui') ? 'perma' : '';
	spip_query ("UPDATE lcm_author SET prefs = '".addslashes(serialize($prefs))."' WHERE id_author = ".$row_auteur['id_author']);
}

// Change the language of the private area (or login)
if ($var_lang_lcm) {
	include_lcm('inc_lang');
	include_lcm('inc_session');
	$verif = verifier_visiteur();

	if (changer_langue($var_lang_lcm)) {
		spip_setcookie('lcm_lang', $var_lang_lcm, time() + 365 * 24 * 3600);

		// [ML] Strange, if I don't do this, id_auteur stays null,
		// and I have no idea where the variable should have been initialized
		$id_auteur = $GLOBALS['auteur_session']['id_author'];

		if (@file_exists('config/inc_connect.php')) {
			include_lcm('inc_admin');
			$cible->addvar('test', '123_' .  $GLOBALS['auteur_session']['id_author'] . '_' . $verif);

			if (verifier_action_auteur('var_lang_lcm', $valeur, $id_auteur)) {
				spip_query ("UPDATE lcm_author SET lang = '".addslashes($var_lang_lcm)."' WHERE id_author = ".$id_auteur);
				$auteur_session['lang'] = $var_lang_lcm;
				ajouter_session($auteur_session, $lcm_session);	// enregistrer dans le fichier de session
			}
		}

		$cible->delvar('lang');
		$cible->addvar('lang', $var_lang_lcm);
	}
}

// Redirection
// Sous Apache, les cookies avec une redirection fonctionnent
// Sinon, on fait un refresh HTTP
if (ereg("^Apache", $SERVER_SOFTWARE)) {
	@header("Location: " . $cible->getUrl());
}
else {
	@header("Refresh: 0; url=" . $cible->getUrl());
	echo "<html><head>";
	echo "<meta http-equiv='Refresh' content='0; url=".$cible->getUrl()."'>";
	echo "</head>\n";
	echo "<body><a href='".$cible->getUrl()."'>"._T('navigateur_pas_redirige')."</a></body></html>";
}

?>
