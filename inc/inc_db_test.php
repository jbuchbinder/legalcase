<?php

if (defined('_INC_DB_TEST')) return;
define('_INC_DB_TEST', '1');


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
