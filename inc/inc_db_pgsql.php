<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

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

	$Id: inc_db_pgsql.php,v 1.9 2006/10/26 13:17:45 mlutfy Exp $
*/

if (defined('_INC_DB_PGSQL')) return;
define('_INC_DB_PGSQL', '1');

if (! function_exists("pg_query"))
	die("ERROR: PostgreSQL is not correctly installed. Verify that the php-mysql
	module is installed and that the php.ini has something similar to
	'extension=pgsql.so'. Refer to the user's manual FAQ for more information.");

$GLOBALS['db'] = 'pgsql';

//
// SQL query functions
//

function lcm_sql_server_info() {
	$info = pg_version();
	return "PostgreSQL " . $info['client'];
}

function lcm_query_db($query, $accept_fail = false) {
	global $lcm_pgsql_link;
	global $lcm_pgsql_error;
	static $tt = 0;

	$my_debug   = $GLOBALS['sql_debug'];
	$my_profile = $GLOBALS['sql_profile'];

	$lcm_pgsql_error = "";
	$query = process_query($query);

	if ($my_profile)
		$m1 = microtime();

	if ($GLOBALS['mysql_recall_link'] AND $lcm_pgsql_link)
		$result = pg_query($query, $lcm_pgsql_link);
	else 
		$result = pg_query($query);

	if ($my_debug AND $my_profile) {
		$m2 = microtime();
		list($usec, $sec) = explode(" ", $m1);
		list($usec2, $sec2) = explode(" ", $m2);
		$dt = $sec2 + $usec2 - $sec - $usec;
		$tt += $dt;
		echo "<small>".htmlentities($query);
		echo " -> <font color='blue'>".sprintf("%3f", $dt)."</font> ($tt)</small><p>\n";
	}

	if ($my_debug)
		lcm_debug("QUERY: $query\n", 1, 'sql');

	if (! $result) {
		$err = lcm_sql_error();

		if (! $accept_fail) {
			$error = _T('warning_sql_query_failed') . "<br />\n" . htmlentities($query) . "<br />\n";
			$error .= "&laquo; " . htmlentities($err) . " &raquo;<br />";
			lcm_panic($error);
		}

		$lcm_pgsql_error = $err;
		lcm_log("sql failed: $err");
	}

	return $result;
}

// Adapts queries to make them pgsql compat
function lcm_query_create_table($table, $fields, $keys = array()) {
	$new_fields = array();

	foreach ($fields as $f) {
		$tmp = $f;

		$tmp = preg_replace('/bigint\(21\) NOT NULL auto_increment/', "serial ", $tmp);
		// [fixed in db_create.php] $tmp = preg_replace("/DEFAULT '0'/", "DEFAULT 0", $tmp);

		$tmp = preg_replace('/tinyint\(\d+\)/', "smallint", $tmp);
		$tmp = preg_replace('/bigint\(\d+\)/', "bigint", $tmp);
		$tmp = preg_replace('/longblob/', "bytea", $tmp);
		$tmp = preg_replace('/blob/', "bytea", $tmp);
		$tmp = preg_replace('/tinytext/', "text", $tmp);

		if (preg_match('/^(\w+) ENUM\((.+)\) NOT NULL/', $tmp, $regs)) {
			$old_tmp = $tmp;
			$field_name = $regs[1];
			$choices = explode(',', $regs[2]);
			$max_len = 0;

			foreach ($choices as $c) {
				$len = strlen(trim($c)) - 2; // ex: 'Foo', so -2 for quotes
				if ($len > $max_len)
					$max_len = $len;
			}

			$tmp = "$field_name varchar($max_len) CHECK ($field_name IN ("
				. implode(', ', $choices) . ')) NOT NULL ';

			if (preg_match('/DEFAULT (.+)$/', $old_tmp, $regs))
				$tmp .= "DEFAULT " . $regs[1];
		}

		$tmp = preg_replace("/datetime( DEFAULT '0000-00-00 00:00:00')? NOT NULL/", "timestamp NOT NULL", $tmp);
		$tmp = preg_replace("/datetime DEFAULT NULL/", "timestamp DEFAULT NULL", $tmp);

		$new_fields[] = $tmp;
	}

	$query = "CREATE TABLE $table ("
		. implode(", ", $new_fields)
		. ")";

	lcm_query($query);
			
	if (count($keys)) {
		foreach ($keys as $name => $field) {
			lcm_query("CREATE INDEX idx_" . $table . '_' . $name . " ON $table ($field)");
		}
	}
}

function lcm_query_create_unique_index($table, $idx_name, $field) {
	lcm_query("ALTER TABLE $table ADD CONSTRAINT " . $table . '_' . $idx_name . " UNIQUE ($field)");
}


//
// Process a standard query
// This includes the "prefix" name for the database tables
//
function process_query($query) {
	$db = '';
	$suite = '';

	if ($GLOBALS['mysql_recall_link'] AND $db = $GLOBALS['lcm_mysql_db'])
		$db = '`'.$db.'`.';

	//
	// Fix syntax "INSERT INTO table SET f0 = v1, f1 = v2, etc."
	//

	// Put query on only one-line to facilitate other regexes
	$query = preg_replace('/\n/m', ' ', $query);

	if (preg_match("/^INSERT INTO ([_A-Za-z0-9]+)\s*SET (.*)/m", $query, $regs)) {
		$table = $regs[1];
		$fields = $regs[2];
		$conditions = "";

		// [ML] Apologies for this sloppy programming.. :-)
		if (preg_match("/(.*) WHERE (.*)/", $query, $regs)) {
			$fields = $regs[1];
			$conditions = $regs[2];
		}

		$all_fields = explode(',', $fields);
		$str_new_fields = "";
		$str_new_values = "";

		foreach ($all_fields as $f) { 
			$f = trim($f);
			if ($f) {

				if (preg_match('/^([_a-zA-Z0-9\.]+)\s*=\s*(.*)$/', $f, $regs)) {
					$str_new_fields .= $regs[1] . ',';
					$str_new_values .= $regs[2] . ',';
				} else {
					lcm_panic("Could not parse $f");
				}
			}
		}

		if (preg_match('/,$/', $str_new_fields))
			$str_new_fields = preg_replace('/,$/', '', $str_new_fields);

		if (preg_match('/,$/', $str_new_values))
			$str_new_values = preg_replace('/,$/', '', $str_new_values);

		$query = "INSERT INTO $table ($str_new_fields) VALUES ($str_new_values)";
	}

	// Make search queries case-insensitive
	if (preg_match("/^SELECT (.*) LIKE '(.*)$/", $query, $regs))
		$query = "SELECT " . $regs[1] . " ILIKE '" . $regs[2];

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

	// Change RAND() to RANDOM() for MySQL -> PosgreSQL unification
	$query = str_replace('RAND(','RANDOM(',$query);

	return $query;
}


//
// Connection to the database
//

function lcm_connect_db($host, $port = 0, $login, $pass, $db = 0, $link = 0) {
	global $lcm_pgsql_link, $lcm_mysql_db;	// for multiple connections

	if (! $login)
		lcm_panic("missing login?");

	// TODO: add port?
	$str  = ($host ? "host=$host " : '');
	$str .= ($login ? "user=$login " : '');
	$str .= ($pass  ? "password=$pass " : '');
	$str .= ($db ? "dbname=$db" : '');

	$lcm_pgsql_link = @pg_connect($str);

/* [ML] not necessary
	if ($lcm_pgsql_link && $db) {
		$lcm_mysql_db = $db;
		return @mysql_select_db($db);
	} else {
		return $lcm_pgsql_link;
	}
*/
	return $lcm_pgsql_link;
}

function lcm_connect_db_test($host, $login, $pass, $db, $port = 0) {
	global $link;
	global $lcm_pgsql_error;

	unset($link);

	// Non-silent connect, should be shown in <!-- --> anyway
	if ($port > 0) $host = "$host:$port";

	$str  = ($host ? "host=$host " : '');
	$str .= ($login ? "user=$login " : '');
	$str .= ($pass  ? "password=$pass " : '');
	$str .= ($db ? "dbname=$db" : '');

	// Capture output and store it into $lcm_pgsql_error
	ob_start();
	$link = pg_connect($str);
	// $lcm_pgsql_error = "XX" . ob_get_contents();
	$lcm_pgsql_error = pg_last_notice();
	ob_end_clean();

	if ($link) {
		pg_close($link);
		return true;
	} else {
		return false;
	}
}

function lcm_list_databases($host, $login, $pass, $port = 0) {
	global $link;
	$databases = array();

	$str  = ($host ? "host=$host " : '');
	$str .= ($login ? "user=$login " : '');
	$str .= ($pass  ? "password=$pass " : '');
	$str .= "dbname=lcm_matt";

	lcm_panic("Not implemented.");

	// if ($port > 0) $host = "$host:$port";
	$link = @pg_connect($str);

	if ($link) {
		$query = "SELECT datname FROM pg_database";
		$result = lcm_query($query);

		while ($row = lcm_fetch_array($result)) {
			array_push($databases, $row['datname']);
		}

		return $databases;
	} else {
		echo "<!-- NO LINK -->\n";
		return NULL;
	}
}


//
// Fetch the results
//

function lcm_fetch_array($r) {
	if ($r)
		return pg_fetch_array($r);
}

function lcm_fetch_assoc($r) {
	if ($r)
		return mysql_fetch_assoc($r);
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
	global $lcm_pgsql_link;
	global $lcm_pgsql_error;

	if (isset($lcm_pgsql_link))
		return pg_last_error($lcm_pgsql_link);
	elseif (isset($lcm_pgsql_error))
		return $lcm_pgsql_error;
	else
		return '';
}

function lcm_sql_errno() {
	global $lcm_pgsql_error;

	// [ML] XXX There is no pg_errno() ?
	if (isset($lcm_pgsql_error) && $lcm_pgsql_error) {
		lcm_log("1 error = $lcm_pgsql_error");
		return 1;
	} else {
		lcm_log("0 error = $lcm_pgsql_error");
		return 0;
	}
}

function lcm_num_rows($r) {
	if ($r)
		return pg_num_rows($r);
}

function lcm_data_seek($r,$n) {
	if ($r)
		return pg_result_seek($r,$n);
}

function lcm_free_result($r) {
	lcm_panic("Not implemented");

	if ($r)
		return mysql_free_result($r);
}

function lcm_insert_id($table, $field) {
	// return mysql_insert_id();

	$result = lcm_query("SELECT last_value FROM ${table}_${field}_seq");
	$seq_array = pg_fetch_row($result, 0);
	return $seq_array[0]; 
}

function lcm_query_date_add_interval($date, $op, $type, $units) {
	$ret = "";

	$type = strtolower($type);

	switch ($op) {
		case '+':
		case '-':
			// ex: TIMESTAMP '2000-01-01' + INTERVAL 1 month
			$ret = "TIMESTAMP '$date' $op INTERVAL '$units $type'";
			break;
		default:
			lcm_panic("Operand unknown");
	}

	return $ret;
}

// Make sure to put $date in quotes, ex: '2000-01-01 00:00:00'
// we don't put by default, because it is made to also accept fields
// ex: date_trunc('day', t.date_start)
function lcm_query_trunc_field($date, $type) {
	$ret = "";

	switch ($type) {
		case 'day':
		case 'year':
			$ret = "date_trunc('$type', $date)";
			break;
		default:
			lcm_panic("Not supported");
	}

	return $ret;
}

function lcm_query_sum_time($field_start, $field_end) {
	return "SUM($field_end - $field_start)";
	/*
	return "SUM(CASE 
				WHEN $field_end - $field_start > 0 THEN $field_end - $field_start
				ELSE 0
			END) as time";
	*/
}

function lcm_query_subst_time($field_start, $field_end) {
	return " $field_end - $field_start ";
}

// Put a local lock on a given LCM installation
// [ML] we can probably ignore this
function spip_get_lock($nom, $timeout = 0) {
	global $lcm_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($lcm_mysql_db) $nom = "$lcm_mysql_db:$nom";

	$nom = addslashes($nom);
	list($lock_ok) = spip_fetch_array(spip_query("SELECT GET_LOCK('$nom', $timeout)"));
	return $lock_ok;
}

function spip_release_lock($nom) {
	global $lcm_mysql_db, $table_prefix;
	if ($table_prefix) $nom = "$table_prefix:$nom";
	if ($lcm_mysql_db) $nom = "$lcm_mysql_db:$nom";

	$nom = addslashes($nom);
	spip_query("SELECT RELEASE_LOCK('$nom')");
}

?>
