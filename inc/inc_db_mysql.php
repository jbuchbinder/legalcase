<?php

if (defined('_INC_DB_MYSQL')) return;
define('_INC_DB_MYSQL', '1');

//
// SQL query functions
//

function lcm_query_db($query) {
	global $spip_mysql_link;
	static $tt = 0;

	// [ML] added "1 OR" temporarely, because this case ignores the installation process
	$my_admin = 1 OR (($GLOBALS['connect_statut'] == 'admin') OR ($GLOBALS['auteur_session']['statut'] == 'admin'));
	$my_profile = ($GLOBALS['mysql_profile'] AND $my_admin);
	$my_debug = ($GLOBALS['mysql_debug'] AND $my_admin);

	$query = process_query($query);

	if ($my_profile)
		$m1 = microtime();

	if ($GLOBALS['mysql_rappel_connexion'] AND $spip_mysql_link)
		$result = mysql_query($query, $spip_mysql_link);
	else 
		$result = mysql_query($query);

	if ($my_profile) {
		$m2 = microtime();
		list($usec, $sec) = explode(" ", $m1);
		list($usec2, $sec2) = explode(" ", $m2);
		$dt = $sec2 + $usec2 - $sec - $usec;
		$tt += $dt;
		echo "<small>".htmlentities($query);
		echo " -> <font color='blue'>".sprintf("%3f", $dt)."</font> ($tt)</small><p>\n";
	}

	if ($my_debug AND $s = mysql_error()) {
		echo _T('info_erreur_requete')." ".htmlentities($query)."<br>";
		echo "&laquo; ".htmlentities($s)." &raquo;<p>";
	}

	return $result;
}

function spip_query_db($query) {
	return lcm_query_db($query);
}

function lcm_create_table($table, $query) {
	return lcm_query_db('CREATE TABLE '.$GLOBALS['table_prefix'].'_'.$table.'('.$query.')');
}


//
// Process a standard query
// This includes the "prefix" name for the database tables
//
function process_query($query) {
	if ($GLOBALS['mysql_rappel_connexion'] AND $db = $GLOBALS['spip_mysql_db'])
		$db = '`'.$db.'`.';

	// changer les noms des tables ($table_prefix)
	if ($GLOBALS['flag_pcre']) {
		if (preg_match('/\s(VALUES|WHERE)\s/i', $query, $regs)) {
			$suite = strstr($query, $regs[0]);
			$query = substr($query, 0, -strlen($suite));
		}
		$query = preg_replace('/([,\s])lcm_/', '\1'.$db.$GLOBALS['table_prefix'].'_', $query) . $suite;
	}
	else {
		if (eregi('[[:space:]](VALUES|WHERE)[[:space:]]', $query, $regs)) {
			$suite = strstr($query, $regs[0]);
			$query = substr($query, 0, -strlen($suite));
		}
		$query = ereg_replace('([[:space:],])lcm_', '\1'.$db.$GLOBALS['table_prefix'].'_', $query) . $suite;
	}

	return $query;
}


//
// Connexion a la base
//

function spip_connect_db($host, $port, $login, $pass, $db) {
	global $spip_mysql_link, $spip_mysql_db;	// pour connexions multiples

	if ($port > 0) $host = "$host:$port";
	$spip_mysql_link = @mysql_connect($host, $login, $pass);
	$spip_mysql_db = $db;
	return @mysql_select_db($db);
}


//
// Recuperation des resultats
//

function spip_fetch_array($r) {
	if ($r)
		return mysql_fetch_array($r);
}

function spip_fetch_object($r) {
	if ($r)
		return mysql_fetch_object($r);
}

function spip_fetch_row($r) {
	if ($r)
		return mysql_fetch_row($r);
}

function spip_sql_error() {
	return mysql_error();
}

function spip_sql_errno() {
	return mysql_errno();
}

function lcm_num_rows($r) {
	if ($r)
		return mysql_num_rows($r);
}

function spip_num_rows($r) {
	return lcm_num_rows($r);
}

function spip_free_result($r) {
	if ($r)
		return mysql_free_result($r);
}

function spip_insert_id() {
	return mysql_insert_id();
}

// Poser un verrou local a un SPIP donne
function spip_get_lock($nom, $timeout = 0) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	$nom = addslashes($nom);
	list($lock_ok) = spip_fetch_array(spip_query("SELECT GET_LOCK('$nom', $timeout)"));
	return $lock_ok;
}

function spip_release_lock($nom) {
	global $spip_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($spip_mysql_db) $nom = "$spip_mysql_db:$nom";

	$nom = addslashes($nom);
	spip_query("SELECT RELEASE_LOCK('$nom')");
}

?>
