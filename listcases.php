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

	$Id: listcases.php,v 1.39 2005/02/07 17:57:06 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Read site preferences
$case_court_archive = read_meta('case_court_archive');

// Select cases of which the current user is author
$q = "SELECT c.id_case, title, id_court_archive, status, public, pub_write
		FROM lcm_case as c, lcm_case_author as a
		WHERE (c.id_case = a.id_case
			AND a.id_author = " . $GLOBALS['author_session']['id_author'];

// Add search criteria if any
$find_case_string = '';

if (isset($_REQUEST['find_case_string']))
	$find_case_string = $_REQUEST['find_case_string'];
	
if (strlen($find_case_string)>0) {
	$q .= " AND ( (c.id_case LIKE '%$find_case_string%') OR (c.title LIKE '%$find_case_string%') OR (id_court_archive LIKE '%$find_case_string%') )";
	lcm_page_start("Cases, containing '$find_case_string':");
} else {
	lcm_page_start(_T('title_my_cases'));
}

$q .= ")";

// Do the query
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

// Debuging code
echo "<!-- Page rows:" . $prefs['page_rows'] . "-->\n";
?>

<!-- [ML:FIXME] I'm not sure about the CSS classes -->
<table border='0' align='center' class='tbl_usr_dtl' width='99%'>
	<tr>
		<th class="heading">#</th>
		<th class="heading">Title</th>
<?php	if ($case_court_archive == 'yes') {
?>		<th class="heading">Court archive</th>
<?php	}
?>		<th colspan="3" class="heading">Status</th>
	</tr>
<?php
// Process the output of the query
for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	$ac_read = allowed($row['id_case'],'r');
	$ac_edit = allowed($row['id_case'], 'e');

	echo "<tr>";

	// Case ID
	echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	if ($ac_read) echo '<a href="case_det.php?case=' . $row['id_case'] . '" class="content_link">';
	echo highlight_matches($row['id_case'],$find_case_string);
	if ($ac_read) echo '</a>';
	echo "</td>\n";

	// Title
	echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	if ($ac_read) echo '<a href="case_det.php?case=' . $row['id_case'] . '" class="content_link">';
	echo highlight_matches(clean_output($row['title']),$find_case_string);
	if (allowed($row['id_case'],'r')) echo '</a>';
	echo "</td>\n";
	
	// Court archive ID
	if ($case_court_archive == 'yes') {
		echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
		echo highlight_matches(clean_output($row['id_court_archive']),$find_case_string);
		echo "</td>\n";
	}
	
	// Status
	echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . $row['status'] . "</td>\n";
	
	/*
	echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	if (allowed($row['id_case'],'e'))
		echo '<a href="edit_case.php?case=' . $row['id_case'] . '" class="content_link">Edit case</a>';
	echo "</td>\n";
	*/

	echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	if (allowed($row['id_case'],'w'))
		echo '<a href="edit_fu.php?case=' . $row['id_case'] . '" class="content_link">Add followup</a>';
	echo "</td></tr>\n";
}

?>
</table>

<table border='0' align='center' width='99%' class='page_numbers'>
	<tr><td align="left" width="15%"><?php
// Show link to previous page
if ($list_pos>0) {
	echo '<a href="listcases.php?list_pos=';
	echo ( ($list_pos>$prefs['page_rows']) ? ($list_pos - $prefs['page_rows']) : 0);
	if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
	echo '" class="content_link">< Prev</a> ';
}

echo "</td>\n\t\t<td align='center' width='70%'>";

// Show page numbers with direct links
$list_pages = ceil($number_of_rows / $prefs['page_rows']);
if ($list_pages>1) {
	echo 'Go to page: ';
	for ($i=0 ; $i<$list_pages ; $i++) {
		if ($i==floor($list_pos / $prefs['page_rows'])) echo '[' . ($i+1) . '] ';
		else {
			echo '<a href="listcases.php?list_pos=' . ($i*$prefs['page_rows']);
			if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
			echo '" class="content_link">' . ($i+1) . '</a> ';
		}
	}
}

echo "</td>\n\t\t<td align='right' width='15%'>";

// Show link to next page
$next_pos = $list_pos + $prefs['page_rows'];
if ($next_pos<$number_of_rows) {
	echo "<a href=\"listcases.php?list_pos=$next_pos";
	if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
	echo '" class="content_link">Next ></a>';
}

echo "</td>\n\t</tr>\n</table>\n";
?>
<br /><a href="edit_case.php?case=0" class="create_new_lnk">Open new case</a>
<?php
lcm_page_end();
?>
