<?php

include('inc/inc.php');
include_lcm('inc_acc');

lcm_page_start("List of cases");

// Prepare query
$q = "SELECT lcm_case.id_case,title,public
		FROM lcm_case,lcm_case_author
		WHERE (lcm_case.id_case=lcm_case_author.id_case
			AND lcm_case_author.id_author=" . $GLOBALS['connect_id_auteur'] . ")";

// TODO - add case filter based on user/case status to query

// Do the query
$result = lcm_query($q);

?>

<table border='1' align='center'>
<tr><th colspan="2">Case description</th></tr>
<?php
// Process the output of the query
while ($row = lcm_fetch_array($result)) {
	// Show case title
	echo '<tr><td>';
	if (allowed($row['id_case'],'r')) {
		echo '<a href="case_det.php?case=' . $row['id_case'] . '">' . $row['title'] . "</a></td>\n";
	} else echo $row['title'] . "</td>\n";
	echo '<td>';
	if (allowed($row['id_case'],'e'))
		echo '<a href="edit_case.php?case=' . $row['id_case'] . '">Edit case</a>';
	echo "</td></tr>\n";
}

?>
<tr><td colspan="2"><a href="edit_case.php?case=0">Open new case</a></td></tr>
</table>

<?php
	lcm_page_end();
?>
