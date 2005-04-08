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

	$Id: archive.php,v 1.11 2005/04/08 07:02:53 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Check access rights
if ($GLOBALS['author_session']['status'] != 'admin') 
	die("You don't have the right to list all cases!");

$find_case_string = '';
if (isset($_REQUEST['find_case_string']))
	$find_case_string = $_REQUEST['find_case_string'];

// Show page start
lcm_page_start(_T('title_archives'));

// Show tabs
$tabs = array(	array('name' => _T('archives_tab_all_cases'), 'url' => 'archive.php'),
		array('name' => _T('archives_tab_export'), 'url' => 'export_db.php'),
		array('name' => _T('archives_tab_import'), 'url' => 'import_db.php')
	);
show_tabs_links($tabs,0);

show_find_box('case', $find_case_string, '__self__');

$q = "SELECT DISTINCT lcm_case.id_case,title,status,public,pub_write
		FROM lcm_case,lcm_case_author
		WHERE (lcm_case.id_case=lcm_case_author.id_case";

// Add search criteria if any
if (strlen($find_case_string) > 1) {
	$q .= " AND ((lcm_case.title LIKE '%$find_case_string%')
				OR (lcm_case.status LIKE '%$find_case_string%'))";
}

$q .= ")";

// Sort cases by creation date
$case_order = 'DESC';
if (isset($_REQUEST['case_order']))
	if ($_REQUEST['case_order'] == 'ASC' || $_REQUEST['case_order'] == 'DESC')
		$case_order = $_REQUEST['case_order'];

$q .= " ORDER BY date_creation " . $case_order;

$result = lcm_query($q);
$number_of_rows = lcm_num_rows($result);

// Check for correct start position of the list
$list_pos = (isset($_REQUEST['list_pos']) ? $_REQUEST['list_pos'] : 0);

if ($list_pos >= $number_of_rows)
	$list_pos = 0;

// Position to the page info start
if ($list_pos > 0)
	if (!lcm_data_seek($result,$list_pos))
		die("Error seeking position $list_pos in the result");

// Process the output of the query
show_listcase_start();

for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	show_listcase_item($row, $i);
}

show_listcase_end($list_pos, $number_of_rows);

lcm_page_end();
?>
