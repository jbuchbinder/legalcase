<?php

include('inc/inc.php');
include('inc/inc_acc.php');
lcm_page_start("Case details");

if ($case>0) {
	// Prepare query
	$q="SELECT *
		FROM lcm_case
		WHERE id_case=$case";

	// Do the query
	$result = lcm_query($q);

	// Process the output of the query
	if ($row = lcm_fetch_array($result)) {

		// Check for access rights
		if (!($row['public'] || allowed($case,'r'))) {
			die("You don't have permission to view this case!");
		}
		$edit = allowed($case,'w');

		// Show case details
		echo '<h1>Details for case: </h1>' . $row['title'];
		if ($edit)
			echo ' [<a href="edit_case.php?case=' . $row['id_case'] . '">Edit case information</a>]';
		echo "<br>\nCase ID: " . $row['id_case'] . "<br>\n";

		// Show users, assigned to the case
		echo 'Case user(s): ';
		$q = "SELECT id_case,lcm_author.id_author,name_first,name_middle,name_last
			FROM lcm_case_author,lcm_author
			WHERE (id_case=$case
				AND lcm_case_author.id_author=lcm_author.id_author)";
		// Do the query
		$authors = lcm_query($q);
		// Show the results
		while ($user = lcm_fetch_array($authors)) {
			echo '<a href="edit_auth.php?case=' . $case . '&amp;author=' . $user['id_author'] . '">';
			echo $user['name_first'] . ' ' . $user['name_middle'] . ' ' . $user['name_last'] . '</a>; ';
		}
		echo '[<a href="sel_auth.php?case=' . $case . '">Add user to the case</a>]';
		echo "<br>\n";
		echo 'Court archive ID: ' . $row['id_court_archive'] . "<br>\n";
		echo 'Creation date: ' . $row['date_creation'] . "<br>\n";
		echo 'Assignment date: ' . $row['date_assignment'] . "<br>\n";
		echo 'Legal reason: ' . $row['legal_reason'] . "<br>\n";
		echo 'Alledged crime: ' . $row['alledged_crime'] . "<br>\n";
		echo 'Status: ' . $row['status'] . "<br>\n";
		echo 'Public: ';
		if ($row['public'])
			echo 'Yes';
		else
			echo 'No';
		echo "<br>\n";

		?><h2>Clients in this case:</h2>

		<table border>
		<caption>Organisations:</caption>
		<?php

		// Show case organization(s)
		$q="SELECT lcm_org.id_org,name
			FROM lcm_case_client_org,lcm_org
			WHERE id_case=$case AND lcm_case_client_org.id_org=lcm_org.id_org";

		// Do the query
		$result = lcm_query($q);

		while ($row = mysql_fetch_array($result)) {
			echo '<tr><td><a href="org_det.php?org=' . $row['id_org'] . '">' . $row['name'] . "</a></td>\n<td>";
			if ($edit)
				echo '<a href="edit_org.php?org=' . $row['id_org'] . '">Edit</a>';
			echo "</td></tr>\n";
		}

		if ($edit)
			echo "<tr><td><a href=\"sel_org.php?case=$case\">Add organisation(s)</a></td><td></td></tr>";

		?></table><br>

		<table border>
		<caption>Clients:</caption>
		<?php

		// Show case client(s)
		$q="SELECT lcm_client.id_client,name_first,name_middle,name_last
			FROM lcm_case_client_org,lcm_client
			WHERE id_case=$case AND lcm_case_client_org.id_client=lcm_client.id_client";

		// Do the query
		$result = lcm_query($q);

		while ($row = mysql_fetch_array($result)) {
			echo '<tr><td>' . $row['name_first'] . ' ' . $row['name_middle'] . ' ' .$row['name_last'] . "</td>\n<td>";
			if ($edit)
				echo '<a href="edit_client.php?client=' . $row['id_client'] . '">Edit</a>';
			echo "</td></tr>\n";
		}
		if ($edit)
			echo "<tr><td><a href=\"sel_client.php?case=$case\">Add client(s)</a></td><td></td></tr>";
		?></table><br>
		<?php

	} else die("There's no such case!");

	?>
	<br><table border>
	<caption>Follow-ups to this case:</caption>
	<tr><th>Date</th><th>Type</th><th>Description</th><th></th></tr>
	<?php

	// Prepare query
	$q = "SELECT id_followup,date_start,type,description
		FROM lcm_followup
		WHERE id_case=$case";

	// Do the query
	$result = lcm_query($q);

	// Process the output of the query
	while ($row = mysql_fetch_assoc($result)) {
		// Show followup
		echo '<tr><td>' . $row['date_start'] . '</td><td>' . $row['type'] . '</td><td>' . $row['description'] . '</td><td>';
		if ($edit)
			echo '<a href="edit_fu.php?followup=' . $row['id_followup'] . '">Edit</a>';
		echo "</td></tr>\n";
	}
	if ($edit)
		echo "<tr><td colspan=\"3\"><a href=\"edit_fu.php?case=$case\">New followup</a></td><td></td></tr>\n";

	?>
	</table>
	<?php
} else die("Which case?");

	lcm_page_end();
?>
