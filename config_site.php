<?php

include ("inc/inc.php");

lcm_page_start("Site configuration");

global $author_session;

if ($author_session['status'] != 'admin') {
	echo "<p>Warning: Access denied, not admin\n";
	lcm_page_end();
	exit;
} 

echo "ok\n";

lcm_page_end();

?>
