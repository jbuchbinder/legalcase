<?php
//
// Execute this file only once
if (defined('_INC_SESSION')) return;
define('_INC_SESSION', '1');


/*
 * Management for authentication by sessions.
 */

$GLOBALS['author_session'] = '';


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
function get_session_file($id_session, $alea) {
	if (ereg("^([0-9]+_)", $id_session, $regs))
		$id_author = $regs[1];

	$session_file = 'session_'.$id_author.md5($id_session.' '.$alea).'.php';
	$session_file = 'inc/data/'.$session_file;

	return $session_file;
}

function fichier_session($id_session, $alea) {
	lcm_log("fichier_session: deprecated, call get_session_file() instead");
	return get_session_file($id_session, $alea);
}

//
// Add a session for the specified author
//
function lcm_add_session($author, $id_session) {
	$session_file = get_session_file($id_session, read_meta('alea_ephemere'));
	$vars = array('id_author', 'name_first', 'name_middle', 'name_last', 'username', 'email', 'status', 'lang', 'ip_change', 'hash_env');

	$texte = "<"."?php\n";
	reset($vars);
	while (list(, $var) = each($vars)) {
		$texte .= "\$GLOBALS['author_session']['$var'] = '".addslashes($author[$var])."';\n";
	}
	$texte .= "?".">\n";

	if ($f = @fopen($session_file, "wb")) {
		fputs($f, $texte);
 		fclose($f);
	} else {
		lcm_log("CRITICAL: cannot write in $session_file, am I installed?");
		@header("Location: lcm_test_dirs.php");
		exit;
	}
}

function ajouter_session($author, $id_session) {
	lcm_log("Use of deprecated function ajouter_session(), use lcm_add_session() instead");
	return lcm_add_session($author, $id_session);
}

//
// Verify/check and include a session file
//
function verifier_session($id_session) {
	// Test with the current alea
	$ok = false;
	if ($id_session) {
		$session_file = get_session_file($id_session, read_meta('alea_ephemere'));
		if (@file_exists($session_file)) {
			include($session_file);
			$ok = true;
		}
		else {
			// Else, check with the previous alea
			$session_file = get_session_file($id_session, read_meta('alea_ephemere_ancien'));
			if (@file_exists($session_file)) {
				// Renouveler la session (avec l'alea courant)
				include($session_file);
				supprimer_session($id_session);
				ajouter_session($GLOBALS['author_session'], $id_session);
				$ok = true;
			}
		}
	}

	// if necessary, mark the session as 'ip-change'
	if ($ok AND (hash_env() != $GLOBALS['author_session']['hash_env']) AND !$GLOBALS['author_session']['ip_change']) {
		$GLOBALS['author_session']['ip_change'] = true;
		ajouter_session($GLOBALS['author_session'], $id_session);
	}

	return $ok;
}

//
// Delete a session
//
function delete_session($id_session) {
	$session_file = get_session_file($id_session, read_meta('alea_ephemere'));
	if (@file_exists($session_file)) {
		@unlink($session_file);
	}
	$session_file = get_session_file($id_session, read_meta('alea_ephemere_ancien'));
	if (@file_exists($session_file)) {
		@unlink($session_file);
	}
}

function supprimer_session($id_session) {
	lcm_log("supprimer_session: deprecated, call delete_session() instead");
	return delete_session($id_session);
}

//
// Create a session and return the associated cookie
//
function creer_cookie_session($auteur) {
	if ($id_author = $auteur['id_author']) {
		$id_session = $id_author.'_'.md5(create_uniq_id());
		$auteur['hash_env'] = hash_env();
		ajouter_session($auteur, $id_session);
		return $id_session;
	}
}

//
// Create a random identifier
//
function create_uniq_id() {
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

function creer_uniqid() {
	lcm_log("Call to deprecated function creer_uniqid(), use create_uniq_id() instead");
	return create_uniq_id();
}


//
// This function deletes all the sessions belonging to the author.
// We also take the opportunity to delete sessions older than 48 hours.
//
function zap_sessions($id_author, $zap) {
	$dirname = 'inc/data/';

	// Do not delete yourself by accident
	// [ML] This does not seem necessary.
	if ($s = $GLOBALS['lcm_session'])
		$session_file = get_session_file($s, read_meta('alea_ephemere'));

	$dir = opendir($dirname);
	$t = time();
	while(($item = readdir($dir)) != '') {
		$fullname = "$dirname$item";
		if (ereg("^session_([0-9]+_)?([a-z0-9]+)\.php$", $item, $regs)) {

			// If it is an old session, we throw away
			if (($t - filemtime($fullname)) > 48 * 3600)
				@unlink($fullname);

			// If not, we test whether it is from the same author
			else if ($regs[1] == $id_author.'_') {
				$zap_num ++;
				if ($zap)
					@unlink($fullname);
			}
		}
	}

	return $zap_num;
}

// Recognize a user authentified with php_auth
// [ML] I think we can scrap this
function verifier_php_auth() {
	lcm_log("inc_session.php/verifier_php_auth: should be deprecated");
	global $PHP_AUTH_USER, $PHP_AUTH_PW, $ignore_auth_http;
	if ($PHP_AUTH_USER && $PHP_AUTH_PW && !$ignore_auth_http) {
		$login = addslashes($PHP_AUTH_USER);
		$result = lcm_query("SELECT * FROM lcm_author WHERE username='$login'");
		$row = lcm_fetch_array($result);
		$auth_mdpass = md5($row['alea_actuel'] . $PHP_AUTH_PW);
		if ($auth_mdpass != $row['pass']) {
			$PHP_AUTH_USER='';
			return false;
		} else {
			// [ML] FIXME update fields
			$GLOBALS['author_session']['id_author'] = $row['id_author'];
			$GLOBALS['author_session']['nom'] = $row['nom'];
			$GLOBALS['author_session']['login'] = $row['login'];
			$GLOBALS['author_session']['email'] = $row['email'];
			$GLOBALS['author_session']['statut'] = $row['statut'];
			$GLOBALS['author_session']['lang'] = $row['lang'];
			$GLOBALS['author_session']['hash_env'] = hash_env();
			return true;
		}
	}
}

// php_auth header
// [ML] I think we can scrap this
function ask_php_auth($text_failure) {
	@Header("WWW-Authenticate: Basic realm=\"espace prive\"");
	@Header("HTTP/1.0 401 Unauthorized");
	echo $text_failure;
	exit;
}

// Verify if we have a correct session cookie and load
// the values in $GLOBALS['author_session'] (author)
function verifier_visiteur() {
	if (verifier_session($GLOBALS['HTTP_COOKIE_VARS']['lcm_session']))
		return true;

	return false;
}

?>
