<?php

include('inc/inc.php');

if (($case>0) && ($clients)) {
	foreach($clients as $client) {
		// Prepare query
		$q="INSERT INTO lcm_case_client_org
			SET id_case=$case,id_client=$client";

		// Do the query
		if (!($result = lcm_query($q))) die("$q<br>\nError ".lcm_errno().": ".lcm_error());
	}
}

// Close connection
// mysql_close($db);

header("Location: $ref_sel_client");

?>
