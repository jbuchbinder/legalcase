<?php

if (defined('_INC_DB_MYSQL')) return;
define('_INC_DB_MYSQL', '1');

//
// SQL query functions
//

function lcm_query_db($query) {
	global $spip_mysql_link;
	static $tt = 0;

	$my_debug = $GLOBALS['sql_debug'];
	$my_profile = $GLOBALS['sql_profile'];

	$query = process_query($query);

	if ($my_profile)
		$m1 = microtime();

	if ($GLOBALS['mysql_rappel_connexion'] AND $spip_mysql_link)
		$result = mysql_query($query, $spip_mysql_link);
	else 
		$result = mysql_query($query);

	if ($my_debug AND $my_profile) {
		$m2 = microtime();
		list($usec, $sec) = explode(" ", $m1);
		list($usec2, $sec2) = explode(" ", $m2);
		$dt = $sec2 + $usec2 - $sec - $usec;
		$tt += $dt;
		echo "<small>".htmlentities($query);
		echo " -> <font color='blue'>".sprintf("%3f", $dt)."</font> ($tt)</small><p>\n";
	}

	if ($my_debug AND $s = mysql_error()) {
		echo _T('info_erreur_requete') . " " . htmlentities($query) . "<br>";
		echo "&laquo; ".htmlentities($s)." &raquo;<p>";
	}

	if ($my_debug)
		lcm_log("QUERY: $query\n", "mysql");

	return $result;
}

function spip_query_db($query) {
	lcm_log("use of deprecated function: spip_query_db, use lcm_query_db instead");
	return lcm_query_db($query);
}

function lcm_create_table($table, $query) {
	lcm_log("use of deprecated function: lcm_create_table, use lcm_query instead");
	return lcm_query_db('CREATE TABLE '.$GLOBALS['table_prefix'].'_'.$table.'('.$query.')');
}


//
// Process a standard query
// This includes the "prefix" name for the database tables
//
function process_query($query) {
	// [ML] 'rappel_connection' == 'recall connection' (keep alive)
	if ($GLOBALS['mysql_rappel_connexion'] AND $db = $GLOBALS['spip_mysql_db'])
		$db = '`'.$db.'`.';

	// change the names of the tables ($table_prefix)
	// for example, lcm_case may become foo_case
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
// Connection to the database
//

function lcm_connect_db($host, $port, $login, $pass, $db) {
	global $spip_mysql_link, $spip_mysql_db;	// for multiple connections

	if ($port > 0) $host = "$host:$port";
	$spip_mysql_link = @mysql_connect($host, $login, $pass);
	$spip_mysql_db = $db;
	return @mysql_select_db($db);
}

function spip_connect_db($host, $port, $login, $pass, $db) {
	lcm_log("use of deprecated function: spip_connect_db, use lcm_connect_db instead");
	return lcm_connect_db($host, $port, $login, $pass, $db);
}


//
// Fetch the results
//

function lcm_fetch_array($r) {
	if ($r)
		return mysql_fetch_array($r);
}

function spip_fetch_array($r) {
	lcm_log("use of deprecated function: spip_fetch_array, use lcm_fetch_array instead");
	return lcm_fetch_array($r);
}

function lcm_fetch_object($r) {
	if ($r)
		return mysql_fetch_object($r);
}

function spip_fetch_object($r) {
	lcm_log("use of deprecated function: spip_fetch_object, use lcm_fetch_object instead");
	return lcm_fetch_object($r);
}

function lcm_fetch_row($r) {
	if ($r)
		return mysql_fetch_row($r);
}

function spip_fetch_row($r) {
	lcm_log("use of deprecated function: spip_fetch_row, use lcm_fetch_row instead");
	return lcm_fetch_row($r);
}

function lcm_sql_error() {
	return mysql_error();
}

function spip_sql_error() {
	lcm_log("use of deprecated function: spip_sql_error, use lcm_sql_error instead");
	return lcm_sql_error();
}

function lcm_sql_errno() {
	return mysql_errno();
}

function spip_sql_errno() {
	lcm_log("use of deprecated function: spip_sql_errno, use lcm_sql_errno instead");
	return lcm_sql_errno();
}

function lcm_num_rows($r) {
	if ($r)
		return mysql_num_rows($r);
}

function spip_num_rows($r) {
	lcm_log("use of deprecated function: spip_num_rows, use lcm_num_rows instead");
	return lcm_num_rows($r);
}

function lcm_free_result($r) {
	if ($r)
		return mysql_free_result($r);
}

function spip_free_result($r) {
	lcm_log("use of deprecated function: spip_free_result, use lcm_free_result instead");
	return lcm_free_result($r);
}

function lcm_insert_id() {
	return mysql_insert_id();
}

function spip_insert_id() {
	lcm_log("use of deprecated function: spip_insert_id, use lcm_insert_id instead");
	return lcm_insert_id();
}

// Put a local lock on a given LCM installation
// [ML] we can probably ignore this
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
