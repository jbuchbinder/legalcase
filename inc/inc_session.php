<?php
//
// Execute this file only once
if (defined('_INC_SESSION')) return;
define('_INC_SESSION', '1');


/*
 * Management for authentication by sessions.
 */

$GLOBALS['auteur_session'] = '';


//
// Hash is created with the IP and the web navigator name
//
function hash_env() {
	global $HTTP_SERVER_VARS;
	return md5($HTTP_SERVER_VARS['REMOTE_ADDR'] . $HTTP_SERVER_VARS['HTTP_USER_AGENT']);
}


//
// Calculate the name of the session file
//
function fichier_session($id_session, $alea) {
	if (ereg("^([0-9]+_)", $id_session, $regs))
		$id_author = $regs[1];

	$fichier_session = 'session_'.$id_author.md5($id_session.' '.$alea).'.php';
	$fichier_session = 'inc/data/'.$fichier_session;

	return $fichier_session;
}

//
// Ajouter une session pour l'auteur specifie
//
function ajouter_session($auteur, $id_session) {
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere'));
	$vars = array('id_author', 'name_first', 'name_middle', 'name_last', 'username', 'email', 'status', 'lang', 'ip_change', 'hash_env');

	$texte = "<"."?php\n";
	reset($vars);
	while (list(, $var) = each($vars)) {
		$texte .= "\$GLOBALS['auteur_session']['$var'] = '".addslashes($auteur[$var])."';\n";
	}
	$texte .= "?".">\n";

	if ($f = @fopen($fichier_session, "wb")) {
		fputs($f, $texte);
 		fclose($f);
	} else {
		lcm_log("CRITICAL: cannot write in $fichier_session, am I installed?");
		@header("Location: lcm_test_dirs.php");
		exit;
	}
}

//
// Verifier et inclure une session
//
function verifier_session($id_session) {
	// Tester avec alea courant
	$ok = false;
	if ($id_session) {
		$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere'));
		if (@file_exists($fichier_session)) {
			include($fichier_session);
			$ok = true;
		}
		else {
			// Sinon, tester avec alea precedent
			$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere_ancien'));
			if (@file_exists($fichier_session)) {
				// Renouveler la session (avec l'alea courant)
				include($fichier_session);
				supprimer_session($id_session);
				ajouter_session($GLOBALS['auteur_session'], $id_session);
				$ok = true;
			}
		}
	}

	// marquer la session comme "ip-change" si le cas se presente
	if ($ok AND (hash_env() != $GLOBALS['auteur_session']['hash_env']) AND !$GLOBALS['auteur_session']['ip_change']) {
		$GLOBALS['auteur_session']['ip_change'] = true;
		ajouter_session($GLOBALS['auteur_session'], $id_session);
	}

	return $ok;
}

//
// Supprimer une session
//
function supprimer_session($id_session) {
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere'));
	if (@file_exists($fichier_session)) {
		@unlink($fichier_session);
	}
	$fichier_session = fichier_session($id_session, lire_meta('alea_ephemere_ancien'));
	if (@file_exists($fichier_session)) {
		@unlink($fichier_session);
	}
}

//
// Creer une session et retourne le cookie correspondant (a poser)
//
function creer_cookie_session($auteur) {
	if ($id_author = $auteur['id_author']) {
		$id_session = $id_author.'_'.md5(creer_uniqid());
		$auteur['hash_env'] = hash_env();
		ajouter_session($auteur, $id_session);
		return $id_session;
	}
}

//
// Creer un identifiant aleatoire
//
function creer_uniqid() {
	static $seeded;

	if (!$seeded) {
		$seed = (double) (microtime() + 1) * time();
		mt_srand($seed);
		srand($seed);
		$seeded = true;
	}

	$s = mt_rand();
	if (!$s) $s = rand();
	if ($GLOBALS['flag_uniqid2'])
		return uniqid($s, 1);
	else
		return uniqid($s);
}


//
// This function deletes all the sessions belonging to the author.
// We also take the opportunity to delete sessions older than 48 hours.
//
function zap_sessions ($id_author, $zap) {
	$dirname = 'inc/data/';

	// Do not delete yourself by accident
	// [ML] This does not seem necessary.
	if ($s = $GLOBALS['lcm_session'])
		$fichier_session = fichier_session($s, lire_meta('alea_ephemere'));

	$dir = opendir($dirname);
	$t = time();
	while(($item = readdir($dir)) != '') {
		$chemin = "$dirname$item";
		if (ereg("^session_([0-9]+_)?([a-z0-9]+)\.php$", $item, $regs)) {

			// If it is an old session, we throw away
			if (($t - filemtime($chemin)) > 48 * 3600)
				@unlink($chemin);

			// If not, we test whether it is from the same author
			else if ($regs[1] == $id_author.'_') {
				$zap_num ++;
				if ($zap)
					@unlink($chemin);
			}
		}
	}

	return $zap_num;
}

//
// reconnaitre un utilisateur authentifie en php_auth
//
function verifier_php_auth() {
	global $PHP_AUTH_USER, $PHP_AUTH_PW, $ignore_auth_http;
	if ($PHP_AUTH_USER && $PHP_AUTH_PW && !$ignore_auth_http) {
		$login = addslashes($PHP_AUTH_USER);
		$result = spip_query("SELECT * FROM lcm_author WHERE username='$login'");
		$row = spip_fetch_array($result);
		$auth_mdpass = md5($row['alea_actuel'] . $PHP_AUTH_PW);
		if ($auth_mdpass != $row['pass']) {
			$PHP_AUTH_USER='';
			return false;
		} else {
			// [ML] FIXME update fields
			$GLOBALS['auteur_session']['id_author'] = $row['id_author'];
			$GLOBALS['auteur_session']['nom'] = $row['nom'];
			$GLOBALS['auteur_session']['login'] = $row['login'];
			$GLOBALS['auteur_session']['email'] = $row['email'];
			$GLOBALS['auteur_session']['statut'] = $row['statut'];
			$GLOBALS['auteur_session']['lang'] = $row['lang'];
			$GLOBALS['auteur_session']['hash_env'] = hash_env();
			return true;
		}
	}
}

//
// entete php_auth
//
function ask_php_auth($text_failure) {
	@Header("WWW-Authenticate: Basic realm=\"espace prive\"");
	@Header("HTTP/1.0 401 Unauthorized");
	echo $text_failure;
	exit;
}

//
// verifie si on a un cookie de session ou un auth_php correct
// et charge ses valeurs dans $GLOBALS['auteur_session']
//
function verifier_visiteur() {
	if (verifier_session($GLOBALS['HTTP_COOKIE_VARS']['lcm_session']))
		return true;
	if (verifier_php_auth())
		return true;
	return false;
}

?>
