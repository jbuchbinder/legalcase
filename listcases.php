<?php

// Test settings
$GLOBALS['list_len'] = 3;

include('inc/inc.php');
include_lcm('inc_acc');

// Prepare query
$q = "SELECT lcm_case.id_case,title,public,pub_write
		FROM lcm_case,lcm_case_author
		WHERE (lcm_case.id_case=lcm_case_author.id_case
			AND lcm_case_author.id_author=" . $GLOBALS['author_session']['id_author'];

// Add search criteria if any
if (strlen($find_case_string)>1) {
	$q .= " AND (lcm_case.title LIKE '%$find_case_string%')";
	lcm_page_start("Cases, containing '$find_case_string':");
} else {
	lcm_page_start("List of cases");
}

$q .= ")";

// Do the query
$result = lcm_query($q);

// Get the number of rows in the result
$number_of_rows = lcm_num_rows($result);

// Check for correct start position of the list
if ($list_pos>=$number_of_rows) $list_pos = 0;

// Position to the page info start
if ($list_pos>0)
	if (!lcm_data_seek($result,$list_pos))
		die("Error seeking position $list_pos in the result");
?>

<table border='1' align='center'>
<tr><th colspan="3">Case description</th></tr>
<?php
// Process the output of the query
for ($i = 0 ; (($i<$GLOBALS['list_len']) && ($row = lcm_fetch_array($result))) ; $i++) {
	// Show case title
	echo '<tr><td>';
	if (allowed($row['id_case'],'r')) echo '<a href="case_det.php?case=' . $row['id_case'] . '">';
	echo highlight_matches($row['title'],$find_case_string);
	if (allowed($row['id_case'],'r')) echo '</a>';
	echo "</td>\n<td>";
	if (allowed($row['id_case'],'e'))
		echo '<a href="edit_case.php?case=' . $row['id_case'] . '">Edit case</a>';
	echo "</td>\n<td>";
	if (allowed($row['id_case'],'w'))
		echo '<a href="edit_fu.php?case=' . $row['id_case'] . '">Add followup</a>';
	echo "</td></tr>\n";
}

?>
<tr><td colspan="3"><a href="edit_case.php?case=0">Open new case</a></td></tr>
</table>

<?php

// Show link to previous page
if ($list_pos>0) {
	echo '<a href="listcases.php';
	if ($list_pos>$GLOBALS['list_len']) echo '?list_pos=' . ($list_pos - $GLOBALS['list_len']);
	if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
	echo '">< Prev</a> ';
}

// Show page numbers with direct links
$list_pages = ceil($number_of_rows / $GLOBALS['list_len']);
if ($list_pages>1) {
	for ($i=0 ; $i<$list_pages ; $i++) {
		if ($i==floor($list_pos / $GLOBALS['list_len'])) echo ($i+1) . ' ';
		else {
			echo '<a href="listcases.php?list_pos=' . ($i*$GLOBALS['list_len']);
			if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
			echo '">' . ($i+1) . '</a> ';
		}
	}
}

// Show link to next page
$next_pos = $list_pos + $GLOBALS['list_len'];
if ($next_pos<$number_of_rows) {
	echo "<a href=\"listcases.php?list_pos=$next_pos";
	if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
	echo '">Next ></a>';
}

lcm_page_end();
?>
