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

	$Id: listcases.php,v 1.51 2005/03/31 14:44:18 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

lcm_page_start(_T('title_my_cases'));
lcm_bubble('case_list');

// For "find case"
$find_case_string = '';

if (isset($_REQUEST['find_case_string'])) {
	$find_case_string = $_REQUEST['find_case_string'];
	show_find_box('case', $find_case_string);
}

// Select cases of which the current user is author
$q = "SELECT c.id_case, title, id_court_archive, status, public, pub_write, date_creation
		FROM lcm_case as c, lcm_case_author as a
		WHERE (c.id_case = a.id_case
			AND a.id_author = " . $GLOBALS['author_session']['id_author'];

if (strlen($find_case_string) > 0) {
	$q .= " AND ( (c.id_case LIKE '%$find_case_string%')
				OR (c.title LIKE '%$find_case_string%') 
				OR (id_court_archive LIKE '%$find_case_string%') )";
}

$q .= ")";

// Sort cases by creation date
$case_order = 'DESC';
if (isset($_REQUEST['case_order']))
	if ($_REQUEST['case_order'] == 'ASC' || $_REQUEST['case_order'] == 'DESC')
		$case_order = $_REQUEST['case_order'];

$q .= " ORDER BY date_creation " . $case_order;

$result = lcm_query($q);

// Check for correct start position of the list
$number_of_rows = lcm_num_rows($result);
$list_pos = 0;

if (isset($_REQUEST['list_pos']))
	$list_pos = $_REQUEST['list_pos'];

if ($list_pos >= $number_of_rows)
	$list_pos = 0;

// Position to the page info start
if ($list_pos > 0)
	if (!lcm_data_seek($result,$list_pos))
		lcm_panic("Error seeking position $list_pos in the result");


// Process the output of the query
show_listcase_start();

for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))); $i++)
	show_listcase_item($row, $i);

show_listcase_end($list_pos, $number_of_rows);

echo '<p><a href="edit_case.php?case=0" class="create_new_lnk">' . _T('case_button_new') . "</a></p>\n";
echo '<p><a href="edit_client.php" class="create_new_lnk">' . _T('client_button_new') . "</a></p>\n";
echo "<br /><br />\n";

lcm_page_end();

?>
