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
include_lcm('inc_filters');

$q = "SELECT id_report,title
		FROM lcm_report";

// Add search criteria if any
$find_rep_string = '';

if (isset($_GET['find_rep_string']))
	$find_rep_string = $_GET['find_rep_string'];

if (strlen($find_rep_string)>1) {
	$q .= " WHERE (title LIKE '%$find_rep_string%')";
	lcm_page_start("Reports, containing '$find_rep_string':");
} else {
	lcm_page_start("List of reports");
}

$result = lcm_query($q);

// Get the number of rows in the result
$number_of_rows = lcm_num_rows($result);

// Check for correct start position of the list
$list_pos = 0;

if (isset($_REQUEST['list_pos']))
	$list_pos = $_REQUEST['list_pos'];

if ($list_pos>=$number_of_rows) $list_pos = 0;

// Position to the page info start
if ($list_pos>0)
	if (!lcm_data_seek($result,$list_pos))
		die("Error seeking position $list_pos in the result");

?>

<table border='0' align='center' class='tbl_usr_dtl' width='99%'>
	<tr><th class='heading'>Description</th>
		<th colspan="2" class='heading'>Actions</th>
	</tr>
<?php
// Process the output of the query
for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	// Show report title
	echo "<tr><td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";

	if (true) echo '<a href="rep_det.php?rep=' . $row['id_report'] . '" class="content_link">';
	echo highlight_matches(clean_output($row['title']),$find_rep_string);
	if (true) echo '</a>';
	echo "</td>\n<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	if (true)
		echo '<a href="edit_rep.php?rep=' . $row['id_report'] . '" class="content_link">Edit</a>';
	echo "</td>\n<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	if (true)
		echo '<a href="run_rep.php?rep=' . $row['id_report'] . '" class="content_link">Run</a>';
	echo "</td></tr>\n";
}

?>
</table>
<p align='right'><a href="edit_rep.php?rep=0" class="content_link">Create new report</a></p>

<table border='0' align='center' width='99%'>
	<tr><td align="left"><?php

// Show link to previous page
if ($list_pos>0) {
	echo '<a href="listreps.php';
	if ($list_pos>$prefs['page_rows']) echo '?list_pos=' . ($list_pos - $prefs['page_rows']);
	if (strlen($find_rep_string)>1) echo "&amp;find_rep_string=" . rawurlencode($find_rep_string);
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
			echo '<a href="listreps.php?list_pos=' . ($i*$prefs['page_rows']);
			if (strlen($find_rep_string)>1) echo "&amp;find_rep_string=" . rawurlencode($find_rep_string);
			echo '">' . ($i+1) . '</a> ';
		}
	}
}

echo "</td>\n\t\t<td align='right'>";

// Show link to next page
$next_pos = $list_pos + $prefs['page_rows'];
if ($next_pos<$number_of_rows) {
	echo "<a href=\"listreps.php?list_pos=$next_pos";
	if (strlen($find_rep_string)>1) echo "&amp;find_rep_string=" . rawurlencode($find_rep_string);
	echo '">Next ></a>';
}

echo "</td>\n\t</tr>\n</table>\n";

lcm_page_end();
?>
