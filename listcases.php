<?php

include('inc/inc.php');
lcm_page_start("List of cases");

// Prepare query
$q = 'SELECT id_case,title FROM lcm_case';

// TODO - add case filter based on user/case status to query

// Do the query
$result = lcm_query($q);

?>
<h1>List of cases</h1>
<table border>
<tr><th colspan="2">Case description</th></tr>
<?php
// Process the output of the query
while ($row = lcm_fetch_assoc($result)) {
	// Show case title
	echo '<tr><td><a href="case_det.php?case=' . $row['id_case'] . '">'. $row['title'] . "</a></td>\n";
	echo '<td><a href="edit_case.php?case=' . $row['id_case'] . '">Edit case</a></td></tr>' . "\n";
}

?>
<tr><td colspan="2"><a href="edit_case.php?case=0">Open new case</a></td></tr>
</table>

<?php
	lcm_page_end();
?>
