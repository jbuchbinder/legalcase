<?php

include('inc/inc.php');

if (($case>0) && ($orgs)) {
	foreach($orgs as $org) {
		// Prepare query
		$q="INSERT INTO lcm_case_client_org
			SET id_case=$case,id_org=$org";

		// Do the query
		if (!($result = lcm_query($q))) die("$q<br>\nError ".lcm_errno().": ".lcm_error());
	}
}

// Close connection
// mysql_close($db);

header("Location: $ref_sel_org");

?>
