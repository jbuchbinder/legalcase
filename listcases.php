<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2005 Free Software Foundation, Inc.

	This program is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published by the 
	Free Software Foundation; either version 2 of the License, or (at your 
	option) any later version.

	This program is distributed in the hope that it will be useful, but 
	WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
	or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
	for more details.

	You should have received a copy of the GNU General Public License along 
	with this program; if not, write to the Free Software Foundation, Inc.,
    59 Temple Place, Suite 330, Boston, MA  02111-1307, USA
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

$q = "SELECT lcm_case.id_case,title,status,public,pub_write
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

// Debuging code
echo "<!-- Page rows:" . $prefs['page_rows'] . "-->\n";
?>

<!-- [ML:FIXME] I'm not sure about the CSS classes -->
<table border='0' align='center' class='tbl_usr_dtl' width='99%'>
	<tr><th class='heading'>Description</th>
		<th class='heading'>Status</th>
		<th colspan="2" class='heading'>Actions</th>
	</tr>
<?php
// Process the output of the query
for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	// Show case title
	echo "<tr><td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";

	if (allowed($row['id_case'],'r')) echo '<a href="case_det.php?case=' . $row['id_case'] . '" class="content_link">';
	echo highlight_matches(clean_output($row['title']),$find_case_string);
	if (allowed($row['id_case'],'r')) echo '</a>';
	echo "</td>\n<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . $row['status'];
	echo "</td>\n<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	if (allowed($row['id_case'],'e'))
		echo '<a href="edit_case.php?case=' . $row['id_case'] . '" class="content_link">Edit case</a>';
	echo "</td>\n<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	if (allowed($row['id_case'],'w'))
		echo '<a href="edit_fu.php?case=' . $row['id_case'] . '" class="content_link">Add followup</a>';
	echo "</td></tr>\n";
}

?>
</table>
<p align='right'><a href="edit_case.php?case=0" class="content_link">Open new case</a></p>

<table border='0' align='center' width='99%'>
	<tr><td align="left"><?php

// Show link to previous page
if ($list_pos>0) {
	echo '<a href="listcases.php';
	if ($list_pos>$prefs['page_rows']) echo '?list_pos=' . ($list_pos - $prefs['page_rows']);
	if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
	echo '">< Prev</a> ';
}

echo "</td>\n\t\t<td align='center'>";

// Show page numbers with direct links
$list_pages = ceil($number_of_rows / $prefs['page_rows']);
if ($list_pages>1) {
	echo 'Go to page: ';
	for ($i=0 ; $i<$list_pages ; $i++) {
		if ($i==floor($list_pos / $prefs['page_rows'])) echo '[' . ($i+1) . '] ';
		else {
			echo '<a href="listcases.php?list_pos=' . ($i*$prefs['page_rows']);
			if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
			echo '">' . ($i+1) . '</a> ';
		}
	}
}

echo "</td>\n\t\t<td align='right'>";

// Show link to next page
$next_pos = $list_pos + $prefs['page_rows'];
if ($next_pos<$number_of_rows) {
	echo "<a href=\"listcases.php?list_pos=$next_pos";
	if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
	echo '">Next ></a>';
}

echo "</td>\n\t</tr>\n</table>\n";

lcm_page_end();
?>
