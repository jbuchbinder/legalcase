<?php

// Test settings
$GLOBALS['list_len'] = 3;

include('inc/inc.php');
include_lcm('inc_filters');

// Prepare query
$q = "SELECT id_client,name_first,name_middle,name_last
		FROM lcm_client";

if (strlen($find_client_string)>1) {
	// Add search criteria
	$q .= " WHERE ((name_first LIKE '%$find_client_string%')
			OR (name_middle LIKE '%$find_client_string%')
			OR (name_last LIKE '%$find_client_string%'))";
	lcm_page_start("Client(s), containing '$find_client_string':");
} else {
	lcm_page_start("List of client(s)");
}

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

// Output table tags
?>
<table border>
	<tr>
		<th>Client name</th>
		<th></th>
	</tr>
<?php
for ($i = 0 ; (($i<$GLOBALS['list_len']) && ($row = lcm_fetch_array($result))) ; $i++) {
	echo "\t<tr><td><a href=\"client_det.php?client=" . $row['id_client'] . '">';
	$fullname = clean_output($row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last']);
	echo highlight_matches($fullname,$find_client_string);
?></td>
		<td><a href="edit_client.php?client=<?php echo $row['id_client']; ?>">Edit</a></td>
	</tr>
<?php
}
?>
	<tr>
		<td><a href="edit_client.php">Add new client</a></td>
		<td></td>
	</tr>
</table>
<?php

// Show link to previous page
if ($list_pos>0) {
	echo '<a href="listclients.php';
	if ($list_pos>$GLOBALS['list_len']) echo '?list_pos=' . ($list_pos - $GLOBALS['list_len']);
	if (strlen($find_client_string)>1) echo "&amp;find_client_string=" . rawurlencode($find_client_string);
	echo '">< Prev</a> ';
}

// Show page numbers with direct links
$list_pages = ceil($number_of_rows / $GLOBALS['list_len']);
if ($list_pages>1) {
	for ($i=0 ; $i<$list_pages ; $i++) {
		if ($i==floor($list_pos / $GLOBALS['list_len'])) echo ($i+1) . ' ';
		else {
			echo '<a href="listclients.php?list_pos=' . ($i*$GLOBALS['list_len']);
			if (strlen($find_client_string)>1) echo "&amp;find_client_string=" . rawurlencode($find_client_string);
			echo '">' . ($i+1) . '</a> ';
		}
	}
}

// Show link to next page
$next_pos = $list_pos + $GLOBALS['list_len'];
if ($next_pos<$number_of_rows) {
	echo "<a href=\"listclients.php?list_pos=$next_pos";
	if (strlen($find_client_string)>1) echo "&amp;find_client_string=" . rawurlencode($find_client_string);
	echo '">Next ></a>';
}

lcm_page_end();
?>
