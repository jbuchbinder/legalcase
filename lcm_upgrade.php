<?php

include('inc/inc_version.php');
include_lcm('inc_presentation');
include_lcm('inc_meta');
include_lcm('inc_auth');

$current_version = read_meta('lcm_db_version');
if (!$current_version) $current_version = 0;

// test if upgraded necessary
if ($lcm_db_version <> $current_version) {
	include_lcm('inc_db_upgrade');
	
	lcm_page_start("Database upgrade", "install");
	
	echo "\n<!-- Hide possibly confusing error messages: \n";
	echo "\t** Upgrading from $current_version to $lcm_db_version **\n";
	$log = upgrade_database($current_version);
	echo "-->\n";

	if ($log) {
		echo "<div class='box_error'>\n";
		echo "<p>An error occured while upgrading the database: <br/>$log<br/>
			<a href='index.php'>Click here to go back to the main page.</a></p>\n";
		echo "</div>\n";
	} else {
		echo "<div class='box_success'>\n";
		echo "<p>The database upgrade was a success.
			<a href='index.php'>Click here to go back to the main page.</a></p>\n";
		echo "</div>\n";
	}
	
	lcm_page_end();
} else {
	lcm_page_start("No database upgrade needed", "install");

	echo "<p><a href='index.php'>Click here to go back to the main page.</a></p>\n";

	lcm_page_end();
}

?>
