<?php

include('inc/inc.php');
include('inc/inc_acc.php');
lcm_page_start("Organisation details");

if ($org>0) {
	// Prepare query
	$q="SELECT *
		FROM lcm_org
		WHERE lcm_org.id_org=$org";

	// Do the query
	$result = lcm_query($q);

	// Process the output of the query
	if ($row = lcm_fetch_array($result)) {

	/* Saved for future use
		// Check for access rights
		if (!($row['public'] || allowed($case,'r'))) {
			die("You don't have permission to view this case!");
		}
		$edit = allowed($case,'w');
	*/
		$edit = true;

		// Show organisation details
		echo '<h1>Details for organisation:</h1>' . $row['name'];
		echo "<br>\nOrganisation ID: " . $row['id_org'] . "<br>\n";
	//	echo 'Organisation name: ' . $row['name'] . "<br>\n";
		echo 'Organisation address: ' . $row['address'] . "<br>\n";
		echo 'Creation date: ' . $row['date_creation'] . "<br>\n";
		echo 'Last update date: ' . $row['date_update'] . "<br>\n";
		if ($edit)
			echo ' [<a href="edit_org.php?org=' . $row['id_org'] . '">Edit organisation information</a>]';

		?><h2>Representative(s) of this organisation:</h2>

		<table border>
		<caption>Representative(s):</caption>
		<?php

		// Show organisation representative(s)
		$q="SELECT lcm_client.id_client,name_first,name_middle,name_last
			FROM lcm_client_org,lcm_client
			WHERE id_org=$org AND lcm_client_org.id_client=lcm_client.id_client";

		// Do the query
		$result = lcm_query($q);

		while ($row = lcm_fetch_array($result)) {
			echo '<tr><td><a href="client_det.php?client=' . $row['id_client'] . '">';
			echo $row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last'] . "</a></td>\n<td>";
			if ($edit)
				echo '<a href="edit_client.php?client=' . $row['id_client'] . '">Edit</a>';
			echo "</td></tr>\n";
		}

		if ($edit)
			echo "<tr><td><a href=\"sel_cli_org.php?org=$org\">Add representative(s)</a></td><td></td></tr>";

		?>
		</table><br>
		<?php

	} else die("There's no such organisation!");
} else die("Which organisation?");

lcm_page_end();
?>
