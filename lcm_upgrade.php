<?php
	include('inc/inc_version.php');
	include_lcm('inc_presentation');

	// if upgraded needed

	include_lcm('inc_db_upgrade');

	lcm_page_start("Database upgrade", "install");

	upgrade_database($installed_db_version);

	echo "<div class='box_sucess'>\n";
	echo "<p>Should be ok, but we should maybe test it with inc_db_test.php.
		<a href='index.php'>Click here to go back to the main page</a></p>\n";
	echo "</div>\n";

	lcm_page_end();

?>
