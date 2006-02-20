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

	$Id: listorgs.php,v 1.20 2006/02/20 02:55:17 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_impex');

$find_org_string = '';
if (isset($_REQUEST['find_org_string']))
	$find_org_string = $_REQUEST['find_org_string'];

if (!empty($_REQUEST['export']) && ($GLOBALS['author_session']['status'] == 'admin')) {
	export('org', $_REQUEST['exp_format'], $find_org_string);
	exit;
}

lcm_page_start(_T('title_org_list'), '', '', 'clients_intro');
lcm_bubble('org_list');
show_find_box('org', $find_org_string, '', (string)($GLOBALS['author_session']['status'] == 'admin') );

// List all organisations in the system + search criterion if any
$q = "SELECT id_org,name
		FROM lcm_org";

if (strlen($find_org_string) > 1)
	$q .= " WHERE (name LIKE '%$find_org_string%')";

// Sort orgs by ID
$order_set = false;
$order_id = '';
if (isset($_REQUEST['order_id']))
	if ($_REQUEST['order_id'] == 'ASC' || $_REQUEST['order_id'] == 'DESC') {
		$order_id = $_REQUEST['order_id'];
		$q .= " ORDER BY id_org " . $order_id;
		$order_set = true;
	}

// Sort organisations by name
$order_name = 'ASC';
if (isset($_REQUEST['order_name']))
	if ($_REQUEST['order_name'] == 'ASC' || $_REQUEST['order_name'] == 'DESC')
		$order_name = $_REQUEST['order_name'];

$q .= ($order_set ? " , " : " ORDER BY ");
$q .= " name " . $order_name;

$result = lcm_query($q);
$number_of_rows = lcm_num_rows($result);

// Check for correct start position of the list
if (isset($_REQUEST['list_pos']))
	$list_pos = $_REQUEST['list_pos'];
else
	$list_pos = 0;

if ($list_pos >= $number_of_rows)
	$list_pos = 0;

// Position to the page info start
if ($list_pos > 0)
	if (!lcm_data_seek($result,$list_pos))
		lcm_panic("Error seeking position $list_pos in the result");

// Output table tags
// Not worth creating show_listorgs_*() for now
$cpt = 0;
$headers = array();

$headers[0]['title'] = "#";
$headers[0]['order'] = 'order_id';
$headers[0]['default'] = '';

$headers[1]['title'] = _Th('org_input_name');
$headers[1]['order'] = 'order_name';
$headers[1]['default'] = 'ASC';
$headers[1]['width'] = '99%';

show_list_start($headers);

for ($i = 0 ; (($i < $prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	echo "<tr>\n";
	echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">'
		. $row['id_org']
		. "</td>\n";
	echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	echo '<a href="org_det.php?org=' . $row['id_org'] . '" class="content_link">';
	echo highlight_matches(clean_output($row['name']), $find_org_string);
	echo "</a>\n";
	echo "</td>\n";
	echo "</tr>\n";
}

show_list_end($list_pos, $number_of_rows);

echo '<p><a href="edit_org.php" class="create_new_lnk">' .  _T('org_button_new') . "</a></p>\n";
echo "<br />\n";

lcm_page_end();

?>
