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

	$Id: listcases.php,v 1.74 2006/04/21 19:12:44 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_obj_case');

global $author_session;
global $prefs;

lcm_page_start(_T('title_my_cases'), '', '', 'cases_intro');
lcm_bubble('case_list');

//
// For "find case"
//
$find_case_string = '';

if (_request('find_case_string')) {
	$find_case_string = _request('find_case_string');

	// remove useless spaces
	$find_case_string = trim($find_case_string);
	$find_case_string = preg_replace('/ +/', ' ', $find_case_string);

	show_find_box('case', $find_case_string);
}

//
// For "Filter case owner"
//
$prefs_change = false;

$types_owner = array('my' => 1, 'public' => 1);
$types_period = array('m1' => 30, 'm3' => 91, 'm6' => 182, 'y1' => 365); // 30 days, 3 months, 6 months, 1 year

if ($author_session['status'] == 'admin')
	$types_owner['all'] = 1;

if (($v = _request('case_owner'))) {
	if ($prefs['case_owner'] != $v) {
		if (! array_key_exists($v, $types_owner))
			lcm_panic("Value for case owner not permitted: " . htmlspecialchars($v));
		
		$prefs['case_owner'] = _request('case_owner');
		$prefs_change = true;
	}
}

// always include 'my' cases 
$q_owner = " (a.id_author = " . $author_session['id_author'];

if ($prefs['case_owner'] == 'public')
	$q_owner .= " OR c.public = 1";

if ($author_session['status'] == 'admin' && $prefs['case_owner'] == 'all')
	$q_owner .= " OR 1=1 ";

$q_owner .= " ) ";

//
// For "Filter case date_creation"
//
if (($v = intval(_request('case_period')))) {
	if ($prefs['case_period'] != $v) {
		// [ML] Ignoring filter, since case period may be 1,5,50 days, but also v = 2005, 2006, etc.
		// if (! array_search($v, $types_period))
		//	lcm_panic("Value for case period not permitted: " . htmlspecialchars($v));

		$prefs['case_period'] = $v;
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
echo '<form action="listcases.php" method="get">' . "\n";
echo "<p class=\"normal_text\">\n";
echo _T('input_filter_case_owner');
echo '<select name="case_owner">';

foreach ($types_owner as $t => $foo) {
	$sel = ($prefs['case_owner'] == $t ? ' selected="selected" ' : '');
	echo '<option value="' . $t . '"' . $sel . '>' . _T('case_filter_owner_option_' . $t) . "</option>\n";
}

echo "</select>\n";

echo '<select name="case_period">';

foreach ($types_period as $key => $val) {
	$sel = isSelected($prefs['case_period'] == $val);
	echo '<option value="' . $val . '"' . $sel . '>' . _T('case_filter_period_option_' . $key) . "</option>\n";
}

$q_dates = "SELECT DISTINCT " . lcm_query_trunc_field('date_creation', 'year') . " as year
			FROM lcm_case as c, lcm_case_author as a
			WHERE c.id_case = a.id_case AND " . $q_owner;

$result = lcm_query($q_dates);

while($row = lcm_fetch_array($result)) {
	$sel = isSelected($prefs['case_period'] == $row['year']);
	echo '<option value="' . $row['year'] . '"' . $sel . '>' . _T('case_filter_period_option_year', array('year' => $row['year'])) . "</option>\n";
}

echo "</select>\n";

echo ' <button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
echo "</p>\n";
echo "</form>\n";

//
// Show the list of cases
//
echo '<p class="normal_text">' . "\n";

$case_list = new LcmCaseListUI();

$case_list->setSearchTerm($find_case_string);
$case_list->start();
$case_list->printList();
$case_list->finish();

echo "</p>\n";

echo '<p><a href="edit_case.php?case=0" class="create_new_lnk">' . _T('case_button_new') . "</a></p>\n";
echo '<p><a href="edit_client.php" class="create_new_lnk">' . _T('client_button_new') . "</a></p>\n";

//
// List of recent follow-ups
//

echo '<a name="fu"></a>' . "\n";
show_page_subtitle(_T('case_subtitle_recent_followups'));

echo '<p class="normal_text">' . "\n";
show_listfu_start('general');

$q = "SELECT fu.id_case, fu.id_followup, fu.date_start, fu.date_end, fu.type, fu.description, fu.case_stage,
			fu.hidden, a.name_first, a.name_middle, a.name_last, c.title
		FROM lcm_followup as fu, lcm_author as a, lcm_case as c 
		WHERE fu.id_author = a.id_author 
		  AND  c.id_case = fu.id_case ";
			
// Author of the follow-up

	// START - Get list of cases on which author is assigned
	$q_temp = "SELECT c.id_case
				FROM lcm_case_author as ca, lcm_case as c
				WHERE ca.id_case = c.id_case
				  AND ca.id_author = " . $author_session['id_author'];

	if ($prefs['case_period'] < 1900) // since X days
		// $q_temp .= " AND TO_DAYS(NOW()) - TO_DAYS(c.date_creation) < " . $prefs['case_period'];
		$q_temp .= " AND " . lcm_query_subst_time('c.date_creation', 'NOW()') . ' < ' . $prefs['case_period'] * 3600 * 24;
	else // for year X
		// $q_temp .= " AND YEAR(date_creation) = " . $prefs['case_period'];
		$q_temp .= " AND " . lcm_query_trunc_field('c.date_creation', 'year') . ' = ' . $prefs['case_period'];
			 
	$r_temp = lcm_query($q_temp);
	$list_cases = array();

	while ($row = lcm_fetch_array($r_temp))
		$list_cases[] = $row['id_case'];
	// END - Get list of cases on which author is assigned

if (! ($prefs['case_owner'] == 'all' && $author_session['status'] == 'admin')) {
	$q .= " AND ( ";

	if ($prefs['case_owner'] == 'public')
		$q .= " c.public = 1 OR ";

	// [ML] XXX FIXME TEMPORARY PATCH
	// if user and no cases + no follow-ups...
	if (count($list_cases))
		$q .= " fu.id_case IN (" . implode(",", $list_cases) . "))";
	else
		$q .= " fu.id_case IN ( 0 ))";
	
}

// Period (date_creation) to show
if ($prefs['case_period'] < 1900) // since X days
	// $q .= " AND TO_DAYS(NOW()) - TO_DAYS(date_start) < " . $prefs['case_period'];
	$q .= " AND " . lcm_query_subst_time('date_start', 'NOW()') . ' < ' . $prefs['case_period'] * 3600 * 24;
else // for year X
	// $q .= " AND YEAR(date_start) = " . $prefs['case_period'];
	$q .= " AND " . lcm_query_trunc_field('date_start', 'year') . ' = ' . $prefs['case_period'];


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
for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))); $i++)
	show_listfu_item($row, $i, 'general');

show_list_end($fu_list_pos, $number_of_rows, false, 'fu');
echo "</p>\n";

lcm_page_end();

?>
