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

	$Id: listclients.php,v 1.18 2004/11/24 12:31:54 makaveev Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

// Prepare query
$q = "SELECT id_client,name_first,name_middle,name_last
		FROM lcm_client";

$find_client_string = '';
if (isset($_REQUEST['find_client_string']))
	$find_client_string = $_REQUEST['find_client_string'];

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
$list_pos = 0;

if (isset($_REQUEST['list_pos']))
	$list_pos = $_REQUEST['list_pos'];

if ($list_pos>=$number_of_rows) $list_pos = 0;

// Position to the page info start
if ($list_pos>0)
	if (!lcm_data_seek($result,$list_pos))
		die("Error seeking position $list_pos in the result");

// Output table tags
?>
<table border='0' width='99%' class='tbl_usr_dtl'>
	<tr>
		<th class='heading'>Client name</th>
		<th class='heading'>&nbsp;</th>
	</tr>
<?php
for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	echo "\t<tr><td><a href=\"client_det.php?client=" . $row['id_client'] . '" class="content_link">';
	$fullname = clean_output($row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last']);
	echo highlight_matches($fullname,$find_client_string);
?></td>
		<td><a href="edit_client.php?client=<?php echo $row['id_client']; ?>" class="content_link">Edit</a></td>
	</tr>
<?php
}
?>
</table>
<br /><a href="edit_client.php" class="add_create_new_lnk">Add new client</a>
<?php

// Show link to previous page
if ($list_pos>0) {
	echo '<a href="listclients.php';
	if ($list_pos>$prefs['page_rows']) echo '?list_pos=' . ($list_pos - $prefs['page_rows']);
	if (strlen($find_client_string)>1) echo "&amp;find_client_string=" . rawurlencode($find_client_string);
	echo '">< Prev</a> ';
}

// Show page numbers with direct links
$list_pages = ceil($number_of_rows / $prefs['page_rows']);
if ($list_pages>1) {
	for ($i=0 ; $i<$list_pages ; $i++) {
		if ($i==floor($list_pos / $prefs['page_rows'])) echo ($i+1) . ' ';
		else {
			echo '<a href="listclients.php?list_pos=' . ($i*$prefs['page_rows']);
			if (strlen($find_client_string)>1) echo "&amp;find_client_string=" . rawurlencode($find_client_string);
			echo '">' . ($i+1) . '</a> ';
		}
	}
}

// Show link to next page
$next_pos = $list_pos + $prefs['page_rows'];
if ($next_pos<$number_of_rows) {
	echo "<a href=\"listclients.php?list_pos=$next_pos";
	if (strlen($find_client_string)>1) echo "&amp;find_client_string=" . rawurlencode($find_client_string);
	echo '">Next ></a>';
}

lcm_page_end();
?>
