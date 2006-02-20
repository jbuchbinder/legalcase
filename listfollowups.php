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

	$Id: listfollowups.php,v 1.6 2006/02/20 02:55:17 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_keywords');

global $author_session;

lcm_page_start(_T('case_subtitle_recent_followups'));
//lcm_bubble('case_list');

//-----------------------------------------------
// Analyse parameters
//

// For "find followup"
$find_fu_string = '';

if (isset($_REQUEST['find_fu_string'])) {
	$find_fu_string = $_REQUEST['find_fu_string'];
	show_find_box('fu', $find_fu_string);
}

// For "Filter case owner"
$case_owner = 'my';
if (isset($_REQUEST['case_owner']))
	$case_owner = $_REQUEST['case_owner'];

// always include 'my' cases [ML] $q_owner is re-used below
$q_owner .= " (a.id_author = " . $author_session['id_author'];

if ($case_owner == 'public')
	$q_owner .= " OR c.public = 1";

$q_owner .= " ) ";

// For "Filter case date_creation"
$case_period = '7';
if (isset($_REQUEST['case_period']))
	$case_period = $_REQUEST['case_period'];

//-----------------------------------------------
// Show filters form
//
$types_owner = array('my', 'public');
$types_period = array('w1' => 7, 'm1' => 30, 'm3' => 91, 'm6' => 182, 'y1' => 365); // week, 30 days, 3 months, 6 months, 1 year

echo '<form action="listfollowups.php" method="get">' . "\n";
echo "<p class=\"normal_text\">\n";
echo _T('input_filter_case_owner');
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
			WHERE c.id_case = a.id_case AND " . $q_owner ."
			ORDER BY year DESC";

$result = lcm_query($q_dates);

while( ($row = lcm_fetch_array($result)) && $row['year'] )
	echo '<option value="' . $row['year'] . '"'
		. ($case_period == $row['year'] ? ' selected="selected"' : '') . '>'
		. _T('case_filter_period_option_year', array('year' => $row['year'])) . "</option>\n";

echo "</select>\n";

echo ' <button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
echo "</p>\n";
echo "</form>\n";

//-----------------------------------------------
// Get cases data from SQL
//
/*
// Select cases of which the current user is author
$q = "SELECT c.id_case, title, status, public, pub_write, date_creation
		FROM lcm_case as c, lcm_case_author as a
		WHERE (c.id_case = a.id_case ";

if (strlen($find_case_string) > 0) {
	$q .= " AND ( (c.id_case LIKE '%$find_case_string%')
				OR (c.title LIKE '%$find_case_string%') )";
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
*/
//-----------------------------------------------
// List of recent follow-ups
//

echo '<a name="fu"></a>' . "\n";
//echo "<div class='prefs_column_menu_head'>" . 'Recent follow-ups' . "</div>\n";

// $headers[0]['title'] = "#";
//$headers[0]['order'] = 'no_order';
$headers[0]['title'] = _Th('time_input_date_start');
$headers[0]['order'] = 'fu_order';
$headers[0]['default'] = 'DESC';
$headers[1]['title'] = (($prefs['time_intervals'] == 'absolute') ? _Th('time_input_date_end') : _Th('time_input_length'));
//$headers[1]['order'] = 'no_order';
$headers[2]['title'] = _Th('case_input_author');
//$headers[2]['order'] = 'no_order';
$headers[3]['title'] = _Th('fu_input_type');
//$headers[3]['order'] = 'no_order';
$headers[4]['title'] = _Th('fu_input_description');
//$headers[4]['order'] = 'no_order';

echo '<p class="normal_text">' . "\n";

show_list_start($headers);

// Get recent followups from SQL
// Prepare query
$q = "SELECT	c.id_case, c.title, c.status, c.date_creation,
		fu.*,
		a.name_first, a.name_middle, a.name_last
	FROM lcm_case as c, lcm_followup as fu, lcm_author as a, lcm_case_author as ca
	WHERE c.id_case=fu.id_case
	  AND fu.id_author=a.id_author
	  AND ca.id_case=fu.id_case
	  AND ca.id_author=fu.id_author";

// Add filtering by user access rights
if ($author_session['status'] != 'admin' || $case_owner != 'all') {
	// Select cases on which the user is assigned
	$q .= "	AND ( (ca.id_author=" . $author_session['id_author'] . ")";

	// Or cases which are public
	if ($case_owner == 'public')
		$q .= "	OR (c.public=1)";

	$q .= ")";
}

/*
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

	$q .= " fu.id_case IN (" . implode(",", $list_cases) . "))";
}
*/
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

$q .= " ORDER BY date_creation $fu_order, date_start $fu_order, id_followup $fu_order";
			
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
			
// Set the length of short followup title
$title_length = (($prefs['screen'] == "wide") ? 48 : 115);
			
// Process the output of the query
$c = 0;
for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))); $i++) {
	$css = ($i %2 ? "dark" : "light");
	
	// Show case subdivision, if necessary
	if ($row['id_case'] != $c) {
		echo '<tr><td colspan="5">&nbsp;</td></tr>';
		echo '<tr><th colspan="5">';
		
		echo '<a href="case_det.php?case=' . $row['id_case'] . '" class="content_link">';
		
		echo '<img src="images/jimmac/stock_edit-16.png" width="16" height="16" alt="" border="0" />&nbsp;';

		echo '#' . $row['id_case'] . " - '" . $row['title'] . "'";
		echo '&nbsp;(' . _T('case_status_option_' . $row['status']) . ')';
		echo '</a></th>';
		echo "</tr>\n";
		
		$c = $row['id_case'];
	}
	
	echo "<tr>\n";

	// Id followup
	// echo '<td class="tbl_cont_' . $css . '"><img src="images/lcm/dotted_angle.gif" width="15" height="15" align="left" />&nbsp;' . $row['id_followup'] . '</td>';
					
	// Start date
	echo '<td class="tbl_cont_' . $css . '">' . format_date($row['date_start'], 'short') . '</td>';
					
	// Time
	echo '<td class="tbl_cont_' . $css . '">';
	$fu_date_end = vider_date($row['date_end']);
	if ($prefs['time_intervals'] == 'absolute') {
		if ($fu_date_end) echo format_date($row['date_end'],'short');
	} else {
		$fu_time = ($fu_date_end ? strtotime($row['date_end']) - strtotime($row['date_start']) : 0);
		echo format_time_interval_prefs($fu_time);
	}
	echo '</td>';

	// Author initials
	echo '<td class="tbl_cont_' . $css . '">' . get_person_initials($row) . '</td>';

	// Type
	echo '<td class="tbl_cont_' . $css . '">' . _Tkw('followups', $row['type']) . '</td>';

	// Description
	$short_description = get_fu_description($row);

	echo '<td class="tbl_cont_' . $css . '">';
	echo '<a href="fu_det.php?followup=' . $row['id_followup'] . '" class="content_link">' . $short_description . '</a>';
	echo '</td>';

	echo "</tr>\n";

}

show_list_end($fu_list_pos, $number_of_rows, false, 'fu');

echo "</p>\n";

echo '<p><a href="edit_case.php?case=0" class="create_new_lnk">' . _T('case_button_new') . "</a></p>\n";

lcm_page_end();

?>
