<?php

include('inc/inc.php');
lcm_page_start("Case details");

// Prepare query
$q='SELECT * FROM lcm_case WHERE id_case=' . $case;

// Do the query
$result = lcm_query($q);

// Process the output of the query
if ($row = mysql_fetch_array($result)) {
	// Show case details
	echo '<h1>Details for case: </h1>' . $row['title'];
	echo ' [<a href="edit_case.php?case=' . $row['id_case'] . "\">Edit case information</a>]<br>\n";
	echo 'Case ID: ' . $row['id_case'] . "<br>\n";
	echo 'Court archive ID: ' . $row['id_court_archive'] . "<br>\n";
	echo 'Creation date: ' . $row['date_creation'] . "<br>\n";
	echo 'Assignment date: ' . $row['date_assignment'] . "<br>\n";
	echo 'Legal reason: ' . $row['legal_reason'] . "<br>\n";
	echo 'Alledged crime: ' . $row['alledged_crime'] . "<br>\n";
	echo 'Status: ' . $row['status'] . "<br>\n";

	?><h2>Clients in this case:</h2><br>

	<table border>
	<caption>Organizations:</caption>
	<?php

	// Show case organization(s)
	$q="SELECT * FROM lcm_case_client_org,lcm_org WHERE id_case=$case AND lcm_case_client_org.id_org=lcm_org.id_org";

	// Do the query
	$result = lcm_query($q);

	while ($row = mysql_fetch_array($result)) {
		echo '<tr><td>' . $row['name'] . "</td>\n";
		echo '<td><a href="edit_org.php?org=' . $row['id_org'] . "\">Edit</a></td></tr>\n";
	}

	?><tr><td>Add organization</td><td></td></tr>
	</table><br>

	<table border>
	<caption>Clients:</caption>
	<?php

	// Show case client(s)
	$q="SELECT * FROM lcm_case_client_org,lcm_client WHERE id_case=$case";
	$q.=" AND lcm_case_client_org.id_client=lcm_client.id_client";

	// Do the query
	$result = lcm_query($q);

	while ($row = mysql_fetch_array($result)) {
		echo '<tr><td>' . $row['name_first'] . ' ' . $row['name_middle'] . ' ' .$row['name_last'] . "</td>\n";
		echo '<td><a href="edit_client.php?client=' . $row['id_client'] . "\">Edit</a></td></tr>\n";
	}
	?><tr><td>Add client</td><td></td></tr>
	</table><br>
	<?php

} else die("There's no such case!")


?>
<br><table border>
<caption>Follow-ups to this case:</caption>
<tr><th>Date</th><th>Type</th><th>Description</th><th></th></tr>
<?php

// Prepare query
$q = 'SELECT id_followup,date_start,type,description FROM lcm_followup WHERE id_case=' . $case;

// Do the query
$result = lcm_query($q);

// Process the output of the query
while ($row = mysql_fetch_assoc($result)) {
	// Show followup
	echo '<tr><td>' . $row['date_start'] . '</td><td>' . $row['type'] . '</td><td>' . $row['description'] . '</td>';
	echo '<td><a href="edit_fu.php?followup=' . $row['id_followup'] . "\">Edit</a></td></tr>\n";
}
echo '<tr><td colspan="3"><a href="edit_fu.php?case=' . $case . "\">New followup</a></td><td></td></tr>\n";

?>
</table>

<?php
	lcm_page_end();
?>
