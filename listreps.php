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

	$Id: listreps.php,v 1.11 2005/05/13 10:06:59 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

global $author_session;

// Restrict page to administrators
if ($author_session['status'] != 'admin') {
	lcm_page_start(_T('title_report_list'), '', '', 'reports_intro');
	echo '<p class="normal_text">' . _T('warning_forbidden_not_admin') . "</p>\n";
	lcm_page_end();
	exit;
}

//
// For "find report"
//
$find_rep_string = '';
if (isset($_REQUEST['find_rep_string']))
	$find_rep_string = $_GET['find_rep_string'];

lcm_page_start(_T('title_report_list'), '', '', 'reports_intro');
// lcm_bubble('report_list');
show_find_box('rep', $find_rep_string);

$q = "SELECT id_report,title
		FROM lcm_report";

// Add search criteria if any
if (strlen($find_rep_string)>1) {
	$q .= " WHERE (title LIKE '%$find_rep_string%')";
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

$headers = array();
$headers[0]['title'] = _Th('person_input_name');
$headers[0]['order'] = 'order_name_first';
$headers[0]['default'] = 'ASC';

show_list_start($headers);

// Process the output of the query
for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	// Show report title
	echo "<tr><td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";

	if (true) echo '<a href="rep_det.php?rep=' . $row['id_report'] . '" class="content_link">';
	echo highlight_matches(clean_output($row['title']),$find_rep_string);
	if (true) echo '</a>';
	echo "</td>\n";
	
	echo "</tr>\n";
}

show_list_end($list_pos, $number_of_rows);

echo '<p><a href="edit_rep.php?rep=0" class="create_new_lnk">' . _T('rep_button_new') . "</a></p>\n";
lcm_page_end();

?>
