<?php

include('inc/inc.php');

if (($case>0) && ($authors)) {
	foreach($authors as $author) {
		// Prepare query
		$q="INSERT INTO lcm_case_author
			SET id_case=$case,id_author=$author";

		// Do the query
		if (!($result = lcm_query($q))) die("$q<br>\nError ".lcm_errno().": ".lcm_error());
	}
}

// Close connection
// mysql_close($db);

header("Location: $ref_sel_auth");

?>
