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
