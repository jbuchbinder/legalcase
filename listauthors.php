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

	$Id: listauthors.php,v 1.25 2005/03/21 16:34:18 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

lcm_page_start(_T('title_author_list'));

$q = "SELECT id_author,name_first,name_middle,name_last,status
		FROM lcm_author
		WHERE (1";

// Add search criteria if any
if (strlen($find_author_string)>1) {
	$q .= " AND ((name_first LIKE '%$find_author_string%')"
		. " OR (name_middle LIKE '%$find_author_string%')"
		. " OR (name_last LIKE '%$find_author_string%'))";
}

$q .= ")";

// Sort clients by first name
// [ML] I know, problably more logical by last name, but we do not split the columns
// later we can sort by any column if we need to
$order_name_first = 'ASC';
if (isset($_REQUEST['order_name_first']))
	if ($_REQUEST['order_name_first'] == 'ASC' || $_REQUEST['order_name_first'] == 'DESC')
		$order_name_first = $_REQUEST['order_name_first'];

$q .= " ORDER BY name_first " . $order_name_first;

$result = lcm_query($q);
$number_of_rows = lcm_num_rows($result);

// Check for correct start position of the list
if ($list_pos>=$number_of_rows) $list_pos = 0;

// Position to the page info start
if ($list_pos>0)
	if (!lcm_data_seek($result,$list_pos))
		die("Error seeking position $list_pos in the result");

// Search form
show_find_box('author', $find_author_string);

$headers = array();
$headers[0]['title'] = _Th('person_input_name');
$headers[0]['order'] = 'order_name_first';
$headers[0]['default'] = 'asc';
$headers[1]['title'] = _Th('authoredit_input_status');
$headers[1]['order'] = 'no_order';

show_list_start($headers);

// Process the output of the query
for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	// Show author name
	echo "<tr>\n";
	echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	echo '<a href="author_det.php?author=' . $row['id_author'] . '" class="content_link">';
	echo highlight_matches(get_person_name($row), $find_author_string);
	echo "</a></td>\n";

	// Show author status
	echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	echo clean_output($row['status']);
	echo "</td>\n";

	// Show author action(s)
	/* [ML]
	echo "\t\t<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	if (($GLOBALS['author_session']['status'] == 'admin') ||
		($row['id_author'] == $GLOBALS['author_session']['id_author']))
			echo '<a href="edit_author.php?author=' . $row['id_author'] . '" class="content_link">Edit</a>';
	echo "</td>\n";
	*/

	echo "</tr>\n";
}

show_list_end($list_pos, $number_of_rows);

// Show add auhor button
if ($GLOBALS['author_session']['status'] == 'admin')
	echo '<p><a href="edit_author.php?author=0" class="create_new_lnk">Add author</a></p>'; // TRAD

lcm_page_end();
?>
