<?php

include('inc/inc.php');
include('inc/inc_acc.php');

if ($case>0) {
	if ($authors) {
		if (allowed($case,'a')) {
			foreach($authors as $author) {
				// Prepare query
				$q="INSERT INTO lcm_case_author
					SET id_case=$case,id_author=$author";

				// Do the query
				if (!($result = lcm_query($q))) die("$q<br>\nError ".lcm_errno().": ".lcm_error());
			}
		} else die("You don't have permission to add access rights to this case!");
	}
} else die("Which case?");

// Close connection
// mysql_close($db);

header("Location: $ref_sel_auth");

?>
