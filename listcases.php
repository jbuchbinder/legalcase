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

	$Id: listcases.php,v 1.52 2005/04/07 10:16:04 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

global $author_session;

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
$case_owner = 'my';
if (isset($_REQUEST['case_owner']))
	$case_owner = $_REQUEST['case_owner'];

// always include 'my' cases [ML] $q_owner is re-used below
$q_owner .= " (a.id_author = " . $author_session['id_author'];

if ($case_owner == 'public')
	$q_owner .= " OR c.public = 1";

$q_owner .= " ) ";

//
// For "Filter case date_creation"
//
$case_period = '365';
if (isset($_REQUEST['case_period']))
	$case_period = $_REQUEST['case_period'];

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
	echo '<option value="' . $t . '"' . $sel . '>' . _T('case_filter_owner_' . $t) . "</option>\n";
}

echo "</select>\n";

echo "Period: "; // TRAD
echo '<select name="case_period">';

foreach ($types_period as $key => $val) {
	$sel = ($case_period == $val ? ' selected="selected" ' : '');
	echo '<option value="' . $val . '"' . $sel . '>' . _T('case_filter_period_' . $key) . "</option>\n";
}

$q_dates = "SELECT DISTINCT YEAR(date_creation) as year
			FROM lcm_case as c, lcm_case_author as a
			WHERE c.id_case = a.id_case AND " . $q_owner;

$result = lcm_query($q_dates);

while($row = lcm_fetch_array($result))
	echo '<option value="' . $row['year'] . '">' . $row['year'] . "</option>\n";

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
show_listcase_start();

for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))); $i++)
	show_listcase_item($row, $i);

show_listcase_end($list_pos, $number_of_rows);

echo '<p><a href="edit_case.php?case=0" class="create_new_lnk">' . _T('case_button_new') . "</a></p>\n";
echo '<p><a href="edit_client.php" class="create_new_lnk">' . _T('client_button_new') . "</a></p>\n";
echo "<br /><br />\n";

lcm_page_end();

?>
