<?php

include('inc/inc.php');

if (($client>0) && ($orgs)) {
	foreach($orgs as $org) {
		// Prepare query
		$q="INSERT INTO lcm_client_org
			SET id_client=$client,id_org=$org";

		// Do the query
		if (!($result = lcm_query($q))) die("$q<br>\n" . _T('title_error') . " " . lcm_errno() . ": " . lcm_error());
	}
}

header("Location: $ref_sel_org_cli");

?>
