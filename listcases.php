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

	$Id: listcases.php,v 1.58 2005/04/15 09:29:34 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

global $author_session;
global $prefs;

lcm_page_start(_T('title_my_cases'));
lcm_bubble('case_list');

//
// For "find case"
//
$find_case_string = '';

if (isset($_REQUEST['find_case_string'])) {
	$find_case_string = $_REQUEST['find_case_string'];
	show_find_box('case', $find_case_string);
}

//
// For "Filter case owner"
//
$prefs_change = false;

$case_owner = $prefs['case_owner'];
if (isset($_REQUEST['case_owner'])) {
	if ($case_owner != $_REQUEST['case_owner']) {
		$case_owner = $_REQUEST['case_owner'];
		$prefs['case_owner'] = $_REQUEST['case_owner'];
		$prefs_change = true;
	}
}

// always include 'my' cases [ML] $q_owner is re-used below
$q_owner .= " (a.id_author = " . $author_session['id_author'];

if ($case_owner == 'public')
	$q_owner .= " OR c.public = 1";

$q_owner .= " ) ";

//
// For "Filter case date_creation"
//
$case_period = $prefs['case_period'];
if (isset($_REQUEST['case_period'])) {
	if ($case_period != $_REQUEST['case_owner']) {
		$case_period = $_REQUEST['case_period'];
		$prefs['case_period'] = $_REQUEST['case_period'];
		$prefs_change = true;
	}
}

if ($prefs_change) {
	lcm_query("UPDATE lcm_author
				SET   prefs = '" . addslashes(serialize($prefs)) . "'
				WHERE id_author = " . $author_session['id_author']);
}

//
// Show filters form
//
$types_owner = array('my', 'public');
$types_period = array('m1' => 30, 'm3' => 91, 'm6' => 182, 'y1' => 365); // 30 days, 3 months, 6 months, 1 year

echo '<form action="listcases.php" method="get">' . "\n";
echo "<p class=\"normal_text\">\n";
echo "Filter: "; // TRAD
echo '<select name="case_owner">';

foreach ($types_owner as $t) {
	$sel = ($case_owner == $t ? ' selected="selected" ' : '');
	echo '<option value="' . $t . '"' . $sel . '>' . _T('case_filter_owner_option_' . $t) . "</option>\n";
}

if ($author_session['status'] == 'admin') {
	$sel = ($case_owner == 'all' ? ' selected="selected" ' : '');
	echo '<option value="all"' . $sel . '>' . _T('case_filter_owner_option_all') . "</option>\n";
}

echo "</select>\n";

echo '<select name="case_period">';

foreach ($types_period as $key => $val) {
	$sel = ($case_period == $val ? ' selected="selected" ' : '');
	echo '<option value="' . $val . '"' . $sel . '>' . _T('case_filter_period_option_' . $key) . "</option>\n";
}

$q_dates = "SELECT DISTINCT YEAR(date_creation) as year
			FROM lcm_case as c, lcm_case_author as a
			WHERE c.id_case = a.id_case AND " . $q_owner;

$result = lcm_query($q_dates);

while($row = lcm_fetch_array($result))
	echo '<option value="' . $row['year'] . '">' . _T('case_filter_period_option_year', array('year' => $row['year'])) . "</option>\n";

echo "</select>\n";

echo ' <button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
echo "</p>\n";
echo "</form>\n";

// Select cases of which the current user is author
$q = "SELECT c.id_case, title, id_court_archive, status, public, pub_write, date_creation
		FROM lcm_case as c, lcm_case_author as a
		WHERE (c.id_case = a.id_case ";

if (strlen($find_case_string) > 0) {
	$q .= " AND ( (c.id_case LIKE '%$find_case_string%')
				OR (c.title LIKE '%$find_case_string%') 
				OR (id_court_archive LIKE '%$find_case_string%') )";
}

$q .= ")";

//
// Apply filters to SQL
//

// Case owner
$q .= " AND " . $q_owner;

// Period (date_creation) to show
if ($case_period < 1900) // since X days
	$q .= " AND TO_DAYS(NOW()) - TO_DAYS(date_creation) < " . $case_period;
else // for year X
	$q .= " AND YEAR(date_creation) = " . $case_period;

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
echo '<p class="normal_text">' . "\n";
show_listcase_start();

for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))); $i++)
	show_listcase_item($row, $i);

show_listcase_end($list_pos, $number_of_rows);
echo "</p>\n";

echo '<p><a href="edit_case.php?case=0" class="create_new_lnk">' . _T('case_button_new') . "</a></p>\n";
echo '<p><a href="edit_client.php" class="create_new_lnk">' . _T('client_button_new') . "</a></p>\n";

//
// List of recent follow-ups
//

echo '<a name="fu"></a>' . "\n";
echo "<div class='prefs_column_menu_head'>" . 'Recent follow-ups' . "</div>\n";

$headers[0]['title'] = "#";
$headers[0]['order'] = 'no_order';
$headers[1]['title'] = _Th('time_input_date_start');
$headers[1]['order'] = 'fu_order';
$headers[1]['default'] = 'ASC';
$headers[2]['title'] = (($prefs['time_intervals'] == 'absolute') ? _Th('time_input_date_end') : _Th('time_input_length'));
$headers[2]['order'] = 'no_order';
$headers[3]['title'] = _Th('case_input_author');
$headers[3]['order'] = 'no_order';
$headers[4]['title'] = _Th('fu_input_type');
$headers[4]['order'] = 'no_order';
$headers[5]['title'] = _Th('fu_input_description');
$headers[5]['order'] = 'no_order';

echo '<p class="normal_text">' . "\n";
			
show_list_start($headers);

$q = "SELECT fu.id_case, fu.id_followup, fu.date_start, fu.date_end, fu.type, fu.description,
			a.name_first, a.name_middle, a.name_last, c.title 
		FROM lcm_followup as fu, lcm_author as a, lcm_case as c 
		WHERE fu.id_author = a.id_author 
		  AND  c.id_case = fu.id_case ";
			
// Author of the follow-up

	// START - Get list of cases on which author is assigned
	$q_temp = "SELECT c.id_case
				FROM lcm_case_author as ca, lcm_case as c
				WHERE ca.id_case = c.id_case
				  AND ca.id_author = " . $author_session['id_author'];

	if ($case_period < 1900) // since X days
		$q_temp .= " AND TO_DAYS(NOW()) - TO_DAYS(c.date_creation) < " . $case_period;
	else // for year X
		$q_temp .= " AND YEAR(date_creation) = " . $case_period;
			 
	$r_temp = lcm_query($q_temp);
	$list_cases = array();

	while ($row = lcm_fetch_array($r_temp))
		$list_cases[] = $row['id_case'];
	// END - Get list of cases on which author is assigned

if (! ($case_owner == 'all' && $author_session['status'] == 'admin')) {
	$q .= " AND ( ";

	if ($case_owner == 'public')
		$q .= " c.public = 1 OR ";

	// [ML] XXX FIXME TEMPORARY PATCH
	// if user and no cases + no follow-ups...
	if (count($list_cases))
		$q .= " fu.id_case IN (" . implode(",", $list_cases) . "))";
	else
		$q .= " fu.id_case IN ( 0 ))";
	
}

// Period (date_creation) to show
if ($case_period < 1900) // since X days
	$q .= " AND TO_DAYS(NOW()) - TO_DAYS(date_start) < " . $case_period;
else // for year X
	$q .= " AND YEAR(date_start) = " . $case_period;


// Add ordering
$fu_order = "DESC";
if (isset($_REQUEST['fu_order']))
	if ($_REQUEST['fu_order'] == 'ASC' || $_REQUEST['fu_order'] == 'DESC')
		$fu_order = $_REQUEST['fu_order'];

$q .= " ORDER BY date_start $fu_order, id_followup $fu_order";
			
$result = lcm_query($q);

// Check for correct start position of the list
$number_of_rows = lcm_num_rows($result);
$fu_list_pos = 0;
				
if (isset($_REQUEST['fu_list_pos']))
	$fu_list_pos = $_REQUEST['fu_list_pos'];
				
if ($fu_list_pos >= $number_of_rows)
	$fu_list_pos = 0;
				
// Position to the page info start
if ($fu_list_pos > 0)
	if (!lcm_data_seek($result,$fu_list_pos))
		lcm_panic("Error seeking position $fu_list_pos in the result");
			
// Process the output of the query
for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))); $i++) {
	echo "<tr>\n";

	// Id case
	echo '<td><abbrev title="' . $row['title'] . '">' . $row['id_case'] . '</abbrev></td>';
					
	// Start date
	echo '<td>' . format_date($row['date_start'], 'short') . '</td>';
					
	// Time
	echo '<td>';
	$fu_date_end = vider_date($row['date_end']);
	if ($prefs['time_intervals'] == 'absolute') {
		if ($fu_date_end) echo format_date($row['date_end'],'short');
	} else {
		$fu_time = ($fu_date_end ? strtotime($row['date_end']) - strtotime($row['date_start']) : 0);
		echo format_time_interval($fu_time,($prefs['time_intervals_notation'] == 'hours_only'));
	}
	echo '</td>';

	// Author initials
	echo '<td>' . get_person_initials($row) . '</td>';

	// Type
	echo '<td>' . _T('kw_followups_' . $row['type'] . '_title') . '</td>';

	// Description
	$short_description = get_fu_description($row);

	echo '<td>';
	echo '<a href="fu_det.php?followup=' . $row['id_followup'] . '" class="content_link">' . clean_output($short_description) . '</a>';
	echo '</td>';

	echo "</tr>\n";
}

show_list_end($fu_list_pos, $number_of_rows, false, 'fu');

echo "</p>\n";

lcm_page_end();

?>
