<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2005 Free Software Foundation, Inc.

	Note: This file was initially based on SPIP's ecrire/inc_meta.php3
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

	$Id: inc_session.php,v 1.19 2005/12/16 11:24:04 mlutfy Exp $
*/

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

	if (isset($_SERVER['LcmDataDir']))
		$session_file = $_SERVER['LcmDataDir'] . '/' . $session_file;
	else
		$session_file = 'inc/data/' . $session_file;

	return $session_file;
}

function fichier_session($id_session, $alea) {
	lcm_log("fichier_session: deprecated, call get_session_file() instead");
	return get_session_file($id_session, $alea);
}

//
// Add a session for the specified author
//
function lcm_add_session($content, $id_session) {
	$session_file = get_session_file($id_session, read_meta('alea_ephemere'));
	$vars = array('id_author', 'name_first', 'name_middle', 'name_last', 'username', 'email', 'status', 'lang', 'ip_change', 'hash_env');

	$text = "<"."?php\n";
	reset($vars);

	foreach ($vars as $v)
		$text .= "\$GLOBALS['author_session']['$v'] = '". addslashes($content[$v]) . "';\n";

	$text .= "?".">\n";

	if ($f = @fopen($session_file, "wb")) {
		fputs($f, $text);
 		fclose($f);
	} else {
		lcm_log("CRITICAL: cannot write in $session_file, am I installed correctly?");
		@header("Location: lcm_test_dirs.php");
		exit;
	}
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
				lcm_add_session($GLOBALS['author_session'], $id_session);
				$ok = true;
			}
		}
	}

	// if necessary, mark the session as 'ip-change'
	if ($ok AND (hash_env() != $GLOBALS['author_session']['hash_env']) AND !$GLOBALS['author_session']['ip_change']) {
		$GLOBALS['author_session']['ip_change'] = true;
		lcm_add_session($GLOBALS['author_session'], $id_session);
	}

	// Clean included data from session file
	// It used to be done in inc_version, but makes more sense only here
	// Example where it applies: lcm_author.name_first = Math'ieu, etc.
	// Note: Variable not always set, e.g. auth failed
	if (isset($GLOBALS['author_session']) && count($GLOBALS['author_session']))
		foreach ($GLOBALS['author_session'] as $key => $val)
			$GLOBALS['author_session'][$key] = stripslashes($val);

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
function creer_cookie_session($author) {
	if ($id_author = $author['id_author']) {
		$id_session = $id_author.'_'.md5(create_uniq_id());
		$author['hash_env'] = hash_env();
		lcm_add_session($author, $id_session);
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
	$dirname = (isset($_SERVER['LcmDataDir']) ? $_SERVER['LcmDataDir'] . '/' : 'inc/data/');

	// Do not delete yourself by accident
	// [ML] This does not seem necessary.
	if ($s = $_COOKIE['lcm_session'])
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

// Verify if we have a correct session cookie and load
// the values in $GLOBALS['author_session'] (author)
function verifier_visiteur() {
	if (isset($_COOKIE['lcm_session']))
		if (verifier_session($_COOKIE['lcm_session']))
			return true;

	return false;
}

?>
