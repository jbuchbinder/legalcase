<?php

include('inc/inc.php');

if (($org>0) && ($clients)) {
	foreach($clients as $client) {
		// Prepare query
		$q="INSERT INTO lcm_client_org
			SET id_org=$org,id_client=$client";

		// Do the query
		if (!($result = lcm_query($q))) die("$q<br>\n" . _T('title_error') . " " . lcm_errno() . ": " . lcm_error());
	}
}

header("Location: $ref_sel_cli_org");

?>
