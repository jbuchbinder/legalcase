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

	$Id: listclients.php,v 1.32 2005/03/31 14:44:18 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

$find_client_string = '';
if (isset($_REQUEST['find_client_string']))
	$find_client_string = $_REQUEST['find_client_string'];

lcm_page_start(_T('title_client_list'));
lcm_bubble('client_list');
show_find_box('client', $find_client_string);

// List all clients in the system + search criterion if any
$q = "SELECT id_client,name_first,name_middle,name_last
		FROM lcm_client";

if (strlen($find_client_string)>1) {
	// Add search criteria
	$q .= " WHERE ((name_first LIKE '%$find_client_string%')
			OR (name_middle LIKE '%$find_client_string%')
			OR (name_last LIKE '%$find_client_string%'))";
}

// Sort clients by ID
$order_set = false;
$order_id = '';
if (isset($_REQUEST['order_id']))
	if ($_REQUEST['order_id'] == 'ASC' || $_REQUEST['order_id'] == 'DESC') {
		$order_id = $_REQUEST['order_id'];
		$q .= " ORDER BY id_client " . $order_id;
		$order_set = true;
	}

// Sort clients by first name
// [ML] I know, problably more logical by last name, but we do not split the columns
// later we can sort by any column if we need to
$order_name_first = 'ASC';
if (isset($_REQUEST['order_name_first']))
	if ($_REQUEST['order_name_first'] == 'ASC' || $_REQUEST['order_name_first'] == 'DESC')
		$order_name_first = $_REQUEST['order_name_first'];

$q .= ($order_set ? " , " : " ORDER BY ");
$q .= " name_first " . $order_name_first;

$result = lcm_query($q);
$number_of_rows = lcm_num_rows($result);

// Check for correct start position of the list
$list_pos = 0;

if (isset($_REQUEST['list_pos']))
	$list_pos = $_REQUEST['list_pos'];

if ($list_pos >= $number_of_rows)
	$list_pos = 0;

// Position to the page info start
if ($list_pos > 0)
	if (!lcm_data_seek($result,$list_pos))
		lcm_panic("Error seeking position $list_pos in the result");

// Output table tags
show_listclient_start();

for ($i = 0 ; (($i < $prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	echo "<tr>\n";
	echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">'
		. $row['id_client']
		. "</td>\n";
	echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">';
	echo '<a href="client_det.php?client=' . $row['id_client'] . '" class="content_link">';
	$fullname = clean_output($row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last']);
	echo highlight_matches($fullname, $find_client_string);
	echo "</a>\n";
	echo "</td>\n";
	echo "</tr>\n";
}

show_listclient_end($list_pos, $number_of_rows);

?>
<p><a href="edit_client.php" class="create_new_lnk"><?php echo _T('client_button_new'); ?></a></p>
<br /><br />
<?php
lcm_page_end();
?>
