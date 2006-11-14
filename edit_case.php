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

	$Id: edit_case.php,v 1.96 2006/11/14 19:14:48 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

include_lcm('inc_obj_client');
include_lcm('inc_obj_org');
include_lcm('inc_obj_case');
include_lcm('inc_obj_fu');

$id_case = 0;

// Don't clear form data if comming back from upd_case with errors
if (! isset($_SESSION['form_data']))
	$_SESSION['form_data'] = array();

if (empty($_SESSION['errors'])) {
	// Set the returning page, usually, there should not be, therefore
	// it will send back to "case_det.php?case=NNN" after update.
	$_SESSION['form_data']['ref_edit_case'] = _request('ref');

	$id_case = intval(_request('case'));

	if ($id_case) {
		// Check access rights
		if (!allowed($id_case,'e')) die(_T('error_no_edit_permission'));

		$q = "SELECT *
			FROM lcm_case
			WHERE id_case = $id_case";

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach ($row as $key => $value) {
				$_SESSION['form_data'][$key] = $value;
			}
		}

		$_SESSION['form_data']['admin'] = allowed($id_case,'a');

	} else {
		// Set default values for the new case
		$_SESSION['form_data']['date_assignment'] = date('Y-m-d H:i:s');
		$_SESSION['form_data']['public'] = (int) (read_meta('case_default_read') == 'yes');
		$_SESSION['form_data']['pub_write'] = (int) (read_meta('case_default_write') == 'yes');

		if (isset($GLOBALS['case_default_status']) && $GLOBALS['case_default_status'] == 'draft')
			$_SESSION['form_data']['status'] = 'draft';
		else
			$_SESSION['form_data']['status'] = 'open';

		$_SESSION['form_data']['admin'] = true;

	}
}

$attach_client = 0;
$attach_org = 0;

if (! $id_case) {
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
	$org = new LcmOrg($attach_org);

	// Leave empty if user did the error of leaving it blank
	if (! isset($_SESSION['form_data']['title']))
		$_SESSION['form_data']['title'] = $info['name'];
}


// Start page and title
if ($id_case)
	lcm_page_start(_T('title_case_edit'), '', '', 'cases_intro#edit');
else
	lcm_page_start(_T('title_case_new'), '', '', 'cases_intro#new');

// Show the errors (if any)
echo show_all_errors();

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

if (! $id_case) {
	if ($attach_org) {
		show_page_subtitle(_Th('title_org_view'), 'clients_intro');

		$org = new LcmOrgInfoUI($attach_org);
		$org->printGeneral(false);
		$org->printCases();
		$org->printAttach();
	}

	if ($attach_client) {
		show_page_subtitle(_Th('title_client_view'), 'clients_intro');

		$client = new LcmClientInfoUI($attach_client);
		$client->printGeneral(false);
		$client->printCases();
		$client->printAttach();
	}
	
	if ((! $attach_client) && (! $attach_org)) {
		//
		// Find or create an organisation for case
		//
		if (read_meta('case_new_showorg') == 'yes') {
			show_page_subtitle(_Th('title_org_view'), 'clients_intro');
	
			echo '<p class="normal_text">';
			echo '<input type="checkbox"' . isChecked(_session('add_org')) .  'name="add_org" id="box_new_org" onclick="display_block(\'new_org\', \'flip\')" />';
			echo '<label for="box_new_org">' . _T('case_button_add_org') . '</label>';
			echo "</p>\n";
	
			// Open box that hides this form by default
			echo '<div id="new_org" ' . (_session('add_org') ? '' : ' style="display: none;"') . '>';
	
			echo "<div style='overflow: hidden; width: 100%;'>";
			echo '<div style="float: left; text-align: right; width: 29%;">';
			echo '<p class="normal_text" style="margin: 0; padding: 4px;">' .  _Ti('input_search_org') . '</p>';
			echo "</div>\n";
	
			echo '<div style="float: right; width: 69%;">';
			echo '<p class="normal_text" style="margin: 0; padding: 4px;"><input type="text" autocomplete="off" name="orgsearchkey" id="orgsearchkey" size="25" />' . "</p>\n";
			echo '<span id="autocomplete-org-popup" class="autocomplete" style="position: absolute; visibility: hidden;"><span></span></span>';
			echo '</div>';
	
			echo '<div style="clear: right;"></div>';
	
			echo '<div id="autocomplete-org-data"></div>' . "\n";
			echo "</div>\n";
	
			echo '<div id="autocomplete-org-alt">';
			$org = new LcmOrgInfoUI();
			$org->printEdit();
			echo '</div>';
	
			echo "<script type=\"text/javascript\">
				autocomplete('orgsearchkey', 'autocomplete-org-popup', 'ajax.php', 'autocomplete-org-data', 'autocomplete-org-alt')
				</script>\n";
	
			echo "</div>\n"; // closes box that hides this form by default
		}

		//
		// For to find or create new client for case
		//
		show_page_subtitle(_Th('title_client_view'), 'clients_intro');

		echo '<p class="normal_text">';
		echo '<input type="checkbox"' . isChecked(_session('add_client')) . 'name="add_client" id="box_new_client" onclick="display_block(\'new_client\', \'flip\')" />';
		echo '<label for="box_new_client">' . _T('case_button_add_client') . '</label>';
		echo "</p>\n";

		// Open box that hides this form by default
		echo '<div id="new_client" ' . (_session('add_client') ? '' : ' style="display: none;"') . '>';

		echo "<div style='overflow: hidden; width: 100%;'>";
		echo '<div style="float: left; text-align: right; width: 29%;">';
		echo '<p class="normal_text" style="margin: 0; padding: 4px;">' .  _Ti('input_search_client') . '</p>';
		echo "</div>\n";

		echo '<div style="float: right; width: 69%;">';
		echo '<p class="normal_text" style="margin: 0; padding: 4px;"><input type="text" name="clientsearchkey" id="clientsearchkey" size="25" />' . "</p>\n";
		echo '<span id="autocomplete-client-popup" class="autocomplete" style="visibility: hidden;"><span></span></span>';
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

		echo "</div>\n"; // closes box that hides this form by default
	}
}

if (! $id_case) {
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
	
$obj_case = new LcmCaseInfoUI($id_case);
$obj_case->printEdit();

echo "</div>\n"; /* div case_data */

echo "<script type=\"text/javascript\">
		autocomplete('casesearchkey', 'autocomplete-case-popup', 'ajax.php', 'autocomplete-case-data', 'case_data')
	</script>\n";

//
// Follow-up data (only for new case, not edit case)
//
if (! $id_case) {
	echo '<p class="normal_text">';
	echo '<input type="checkbox"' . isChecked(_session('add_fu')) . 'name="add_fu" id="box_new_followup" onclick="display_block(\'new_followup\', \'flip\')" />';
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

echo '<p><button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button></p>\n";

echo '<input type="hidden" name="admin" value="' . $_SESSION['form_data']['admin'] . "\" />\n";
echo '<input type="hidden" name="ref_edit_case" value="' . $_SESSION['form_data']['ref_edit_case'] . "\" />\n";

echo "</form>\n\n";

// Reset error messages and form data
$_SESSION['errors'] = array();
$_SESSION['case_data'] = array(); // DEPRECATED
$_SESSION['form_data'] = array();

lcm_page_end();

?>
