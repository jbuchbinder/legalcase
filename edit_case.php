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

	$Id: edit_case.php,v 1.85 2006/03/07 14:12:11 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

include_lcm('inc_obj_client');
include_lcm('inc_obj_case');
include_lcm('inc_obj_fu');

$case = 0;

if (empty($_SESSION['errors'])) {

	// Clear form data
	$_SESSION['form_data'] = array();

	// Set the returning page, usually, there should not be, therefore
	// it will send back to "case_det.php?case=NNN" after update.
	$_SESSION['form_data']['ref_edit_case'] = _request('ref');

	// Register case ID as session variable
	if (!session_is_registered("case"))
		session_register("case");

	$case = intval($_GET['case']);

	// Register case type variable for the session
	if (!session_is_registered("existing"))
		session_register("existing");

	// Find out if this is existing or new case
	$existing = ($case > 0);

	if ($existing) {
		// Check access rights
		if (!allowed($case,'e')) die(_T('error_no_edit_permission'));

		$q = "SELECT *
			FROM lcm_case
			WHERE id_case = $case";

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach ($row as $key => $value) {
				$_SESSION['form_data'][$key] = $value;
			}
		}

		$_SESSION['form_data']['admin'] = allowed($case,'a');

	} else {
		// Set default values for the new case
		$_SESSION['form_data']['date_assignment'] = date('Y-m-d H:i:s');
		$_SESSION['form_data']['public'] = (int) (read_meta('case_default_read') == 'yes');
		$_SESSION['form_data']['pub_write'] = (int) (read_meta('case_default_write') == 'yes');
		$_SESSION['form_data']['status'] = 'draft';

		$_SESSION['form_data']['admin'] = true;

	}
}

$attach_client = 0;
$attach_org = 0;

if (! $case) {
	$attach_client = intval(_request('attach_client', 0));
	$attach_org    = intval(_request('attach_org', 0));

	$attach_client = intval(_session('attach_client', $attach_client));
	$attach_org    = intval(_session('attach_org', $attach_org));
}

if ($attach_client) {
	$client = new LcmClient($attach_client);

	// Leave empty if user did the error of leaving it blank
	if (! isset($_SESSION['form_data']['title']))
		$_SESSION['form_data']['title'] = $client->getName();
}

if ($attach_org) {
	$query = "SELECT name
				FROM lcm_org
				WHERE id_org = " . $attach_org;

	$result = lcm_query($query);
	if ($info = lcm_fetch_array($result)) {
		// Leave empty if user did the error of leaving it blank
		if (! isset($_SESSION['form_data']['title']))
			$_SESSION['form_data']['title'] = $info['name'];
	} else {
		lcm_panic("No such organisation #" . $attach_org);
	}
}


// Start page and title
if ($existing)
	lcm_page_start(_T('title_case_edit'), '', '', 'cases_intro#edit');
else
	lcm_page_start(_T('title_case_new'), '', '', 'cases_intro#new');

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

if ($attach_client || $attach_org)
	show_context_start();

if ($attach_client) {
	$query = "SELECT id_client, name_first, name_middle, name_last
				FROM lcm_client
				WHERE id_client = " . $attach_client;
	$result = lcm_query($query);
	while ($row = lcm_fetch_array($result))  // should be only once
		echo '<li style="list-style-type: none;">' . _Ti('fu_input_involving_clients') . get_person_name($row) . "</li>\n";
	
}

if ($attach_org) {
	$query = "SELECT id_org, name
				FROM lcm_org
				WHERE id_org = " . $attach_org;
	$result = lcm_query($query);
	while ($row = lcm_fetch_array($result))  // should be only once
		echo '<li style="list-style-type: none;">' . _Ti('fu_input_involving_clients') . $row['name'] . "</li>\n";
}

if ($attach_client || $attach_org)
	show_context_end();

// Start edit case form
echo '<form action="upd_case.php" method="post">' . "\n";

if (! $case) {
	if ($attach_client) {
		show_page_subtitle("Client information", 'clients_intro'); // TRAD

		$client = new LcmClientInfoUI($attach_client);
		$client->printGeneral(false);
		$client->printCases();
		$client->printAttach();
	} elseif ($attach_org) {
		// TODO: $org = new OrgInfoUI($attach_org);
		// $org->printGeneral(false);
		// $org->printCases();
		echo '<input type="hidden" name="attach_org" value="' . $attach_org . '" />' . "\n";
	} else {
		//
		// For to find or create new client for case
		//
		show_page_subtitle("Client information", 'clients_intro'); // TRAD

		echo '<p class="normal_text">';
		echo '<input type="checkbox"' . isChecked(_session('add_client')) . 'name="add_client" id="box_new_client" onclick="display_block(\'new_client\', \'flip\')"; />';
		echo '<label for="box_new_client">' . "Add a client to this case" . '</label>'; // TRAD
		echo "</p>\n";

		echo '<div id="new_client" ' . (_session('add_client') ? '' : ' style="display: none;"') . '>';

		echo "<div style='overflow: hidden; width: 100%;'>";
		echo '<div style="float: left; text-align: right; width: 29%;">';
		echo '<p class="normal_text" style="margin: 0; padding: 4px;">' .  _Ti('input_search_client') . '</p>';
		echo "</div>\n";

		echo '<div style="float: right; width: 69%;">';
		echo '<p class="normal_text" style="margin: 0; padding: 4px;"><input type="text" autocomplete="off" name="clientsearchkey" id="clientsearchkey" size="25" />' . "</p>\n";
		echo '<span id="autocomplete-client-popup" class="autocomplete" style="position: absolute; visibility: hidden;"><span></span></span>';
		echo '</div>';

		echo '<div style="clear: right;"></div>';

		echo '<div id="autocomplete-client-data"></div>' . "\n";
		echo "</div>\n";

		echo '<div id="autocomplete-client-alt">';
		$client = new LcmClientInfoUI();
		$client->printEdit();
		echo '</div>';

		echo "<script type=\"text/javascript\">
			autocomplete('clientsearchkey', 'autocomplete-client-popup', 'ajax.php', 'autocomplete-client-data', 'autocomplete-client-alt')
			</script>\n";

		echo "</div>\n"; // box that hides this function by default
	}
}

if (! $case) {
	//
	// Find case (show only if new case)
	//
	show_page_subtitle("Case information", 'cases_intro'); // TRAD

	echo "<div style='overflow: hidden; width: 100%;'>";
	echo '<div style="float: left; text-align: right; width: 29%;">';
	echo '<p class="normal_text" style="margin: 0; padding: 4px;">' . _Ti('input_search_case') . '</p>';
	echo "</div>\n";
	
	echo '<div style="float: right; width: 69%;">';
	echo '<p class="normal_text" style="margin: 0; padding: 4px;"><input type="text" autocomplete="off" name="casesearchkey" id="casesearchkey" size="25" />' . "</p>\n";
	echo '<span id="autocomplete-case-popup" class="autocomplete" style="position: absolute; visibility: hidden;"><span></span></span>';
	echo '</div>';
	
	echo '<div style="clear: right;"></div>';
	
	echo '<div id="autocomplete-case-data"></div>' . "\n";
	echo "</div>\n";
}

echo '<div id="case_data">';
	
$obj_case = new LcmCaseInfoUI($case);
$obj_case->printEdit();

echo "</div>\n"; /* div case_data */

echo "<script type=\"text/javascript\">
		autocomplete('casesearchkey', 'autocomplete-case-popup', 'ajax.php', 'autocomplete-case-data', 'case_data')
	</script>\n";

//
// Follow-up data (only for new case, not edit case)
//
if (! $case) {
	echo '<p class="normal_text">';
	echo '<input type="checkbox"' . isChecked(_session('add_fu')) . 'name="add_fu" id="box_new_followup" onclick="display_block(\'new_followup\', \'flip\')"; />';
	echo '<label for="box_new_followup">' . "Add a follow-up to the case" . '</label>'; // TRAD
	echo "</p>\n";

	echo '<div id="new_followup" ' . (_session('add_fu') ? '' : ' style="display: none;"') . '>';

	show_page_subtitle("Follow-up information", 'followups_intro'); // TRAD

	echo '<div id="autocomplete-fu-alt">';
	$fu = new LcmFollowupInfoUI();
	$fu->printEdit();
	echo "</div>\n";

	echo "</div>\n";
}

// Different buttons for edit existing and for new case
if ($existing) {
	echo '<p><button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button></p>\n";
} else {
	// More buttons for 'extended' mode
	if ($prefs['mode'] == 'extended') {
		echo '<p>';
		echo '<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('add_and_go_to_details') . '</button>';
		echo '<button name="submit" type="submit" value="addnew" class="simple_form_btn">' . _T('add_and_open_new') . "</button>\n";
		echo "</p>\n";
	} else {
		// Less buttons in simple mode
		echo '<p><button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('button_validate') . "</button></p>\n";
	}
}

echo '<input type="hidden" name="admin" value="' . $_SESSION['form_data']['admin'] . "\" />\n";
echo '<input type="hidden" name="ref_edit_case" value="' . $_SESSION['form_data']['ref_edit_case'] . "\" />\n";

echo "</form>\n\n";

// Reset error messages and form data
$_SESSION['errors'] = array();
$_SESSION['case_data'] = array(); // DEPRECATED
$_SESSION['form_data'] = array();

lcm_page_end();

?>
