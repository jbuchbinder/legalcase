<?php

if (defined('_INC_DB_TEST')) return;
define('_INC_DB_TEST', '1');

// Verify the rights to modify the database
function lcm_test_alter_table() {
	$log = "";

	lcm_query("DROP TABLE lcm_test", true);
	lcm_query("CREATE TABLE lcm_test (a INT)");
	lcm_query("ALTER TABLE lcm_test ADD b INT");
	lcm_query("INSERT INTO lcm_test (b) VALUES (1)");
	$result = lcm_query("SELECT b FROM lcm_test");
	lcm_query("ALTER TABLE lcm_test DROP b");

	if (!$result) {
		$log .= "User does not have the right to modify the database:";
		if (lcm_sql_errno())
			$log .= "<p>" . lcm_sql_error() . "</p>";
		else
			$log .= "<p>" . "No error message available." . "</p>";
	}

	lcm_query("DROP TABLE lcm_test", true);

	return $log;
}

function lcm_structure_test() {
	// TODO

	// Examples of possible tests:
	// - Insert/Delet entry in lcm_meta

	// Non-portable test:
	// - Doing 'describe table' in MySQL (may not work under PostgreSQL)

	// This will be important for when we will be upgrading the database
	// and making, for example, "ALTER TABLE" queries, which could rise
	// some strange bugs in the application if not everything is compatible
	// because of bad testing or old version of MySQL.

	return true;
}
