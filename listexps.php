<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

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

	$Id: listexps.php,v 1.5 2006/04/21 16:34:36 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_obj_exp');

global $author_session;
global $prefs;

lcm_page_start(_T('title_expenses'), '', '', 'expenses_intro');
lcm_bubble('expenses_list');

//
// For "find expense"
//
$find_exp_string = '';

if (_request('find_exp_string')) {
	$find_exp_string = _request('find_exp_string');

	// remove useless spaces
	$find_exp_string = trim($find_exp_string);
	$find_exp_string = preg_replace('/ +/', ' ', $find_exp_string);
}

show_find_box('exp', $find_exp_string);

//
// For "Filter expense owner"
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

// always include 'my' cases [ML] $q_owner is re-used below
$q_owner = " (e.id_author = " . $author_session['id_author'];

if ($prefs['case_owner'] == 'public')
	$q_owner .= " OR e.pub_read = 1";

if ($author_session['status'] == 'admin' && $prefs['case_owner'] == 'all')
	$q_owner .= " OR 1=1 ";

$q_owner .= " ) ";

//
// For "Filter case date_creation"
//
if (($v = _request('case_period'))) {
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
echo '<form action="listexps.php" method="get">' . "\n";
echo "<p class=\"normal_text\">\n";
echo _T('input_filter_case_owner');
echo '<select name="case_owner">';

foreach ($types_owner as $t => $foo) {
	$sel = isSelected($prefs['case_owner'] == $t);
	echo '<option value="' . $t . '"' . $sel . '>' . _T('expense_filter_owner_option_' . $t) . "</option>\n";
}

echo "</select>\n";

echo '<select name="case_period">';

foreach ($types_period as $key => $val) {
	$sel = isSelected($prefs['case_period'] == $val);
	echo '<option value="' . $val . '"' . $sel . '>' . _T('case_filter_period_option_' . $key) . "</option>\n";
}

$q_dates = "SELECT DISTINCT " . lcm_query_trunc_field('date_creation', 'year') . " as year
			FROM lcm_expense as e
			WHERE " . $q_owner;

$result = lcm_query($q_dates);

while($row = lcm_fetch_array($result)) {
	$sel = isSelected($prefs['case_period'] == $row['year']);
	echo '<option value="' . $row['year'] . '"' . $sel . '>' . _T('case_filter_period_option_year', array('year' => $row['year'])) . "</option>\n";
}

echo "</select>\n";

echo ' <button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
echo "</p>\n";
echo "</form>\n";

// Process the output of the query
$exp_list = new LcmExpenseListUI();

$exp_list->setSearchTerm($find_exp_string);

$exp_list->start();
$exp_list->printList();
$exp_list->finish();

echo '<p><a href="edit_exp.php?case=0" class="create_new_lnk">' . _T('expense_button_new') . "</a></p>\n";

lcm_page_end();

?>
