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

	$Id: edit_fu.php,v 1.116 2006/11/14 19:14:11 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_obj_fu');

// Read the policy settings
$fu_sum_billed = read_meta('fu_sum_billed');
$admin = ($GLOBALS['author_session']['status']=='admin');

// Don't clear form data if comming back from upd_fu with errors
if (! isset($_SESSION['form_data']))
	$_SESSION['form_data'] = array();

// Set the returning page, usually, there should not be, therefore
// it will send back to "fu_det.php?followup=NNN" after update.
$_SESSION['form_data']['ref_edit_fu'] = _request('ref');

	if (isset($_GET['followup'])) {
		$_SESSION['followup'] = intval($_GET['followup']);

		// Fetch the details on the specified follow-up
		$q="SELECT *
			FROM lcm_followup as fu
			WHERE fu.id_followup=" . $_SESSION['followup'];

		$result = lcm_query($q);

		if (! ($row = lcm_fetch_array($result)))
			lcm_panic("Edit follow-up: invalid 'follow-up id': " . $_SESSION['followup']);

		// Set the case ID, to which this followup belongs
		$case = $row['id_case'];

		foreach($row as $key=>$value) {
			$_SESSION['form_data'][$key] = $value;
		}

		if (empty($_SESSION['errors'])) {
			// If editing "stage change"..
			if ($row['type'] == 'stage_change') 
				$old_stage = $row['case_stage'];

			// Get new stage from description field
			$tmp = lcm_unserialize($_SESSION['form_data']['description']);
			if (isset($tmp['new_stage']))
				$new_stage = $tmp['new_stage'];

			// Case conclusion, if appropriate
			if ($_SESSION['form_data']['type'] == 'stage_change' || is_status_change($_SESSION['form_data']['type'])) {
				// description might be empty
				if (isset($tmp['description']))
					$_SESSION['form_data']['description'] = $tmp['description'];

				if ($tmp['result'])
					$_SESSION['form_data']['result'] = $tmp['result'];

				if ($tmp['conclusion'])
					$_SESSION['form_data']['conclusion'] = $tmp['conclusion'];

				if ($tmp['sentence'])
					$_SESSION['form_data']['sentence'] = $tmp['sentence'];

				if ($tmp['sentence_val'])
					$_SESSION['form_data']['sentence_val'] = $tmp['sentence_val'];
			}
		}
	} else {
		unset($_SESSION['followup']);
		$case = intval($_GET['case']);

		if (! ($case > 0))
			lcm_panic("Edit follow-up: invalid 'case id': " . $_GET['case']); // TRAD?

		// Check for access rights
		if (!allowed($case,'w'))
			lcm_panic("You don't have permission to add information to this case"); // TRAD

		// Setup default values
		$_SESSION['form_data']['id_case'] = $case; // Link to the case

		if (empty($_SESSION['errors'])) {
			$_SESSION['form_data']['date_start'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
			$_SESSION['form_data']['date_end']   = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'

			// Set appointment start/end/reminder times to current time
			$_SESSION['form_data']['app_start_time'] = date('Y-m-d H:i:s');
			$_SESSION['form_data']['app_end_time'] = date('Y-m-d H:i:s');
			$_SESSION['form_data']['app_reminder'] = date('Y-m-d H:i:s');

			if (isset($_REQUEST['stage']))
				$new_stage = $_REQUEST['stage'];

			if (isset($_REQUEST['type']))
				$_SESSION['form_data']['type'] = $_REQUEST['type'];
		}

			//
			// Check if the followup is created from appointment
			//
			$app = intval($_GET['app']);
			if (! empty($app)) {
				$q = "SELECT * FROM lcm_app WHERE id_app=$app";
				$result = lcm_query($q);

				if (! ($row = lcm_fetch_array($result)))
					lcm_panic("There's no such appointment (app = $app)");

				// Get participant author(s)
				$participants = array();
				$q = "SELECT lcm_author_app.*,lcm_author.name_first,lcm_author.name_middle,lcm_author.name_last
					FROM lcm_author_app, lcm_author
					WHERE (id_app=$app AND lcm_author_app.id_author=lcm_author.id_author)";
				$res_author = lcm_query($q);
				if (lcm_num_rows($res_author)>0) {
					while ($author = lcm_fetch_array($res_author)) {
						$participants[] = get_person_name($author);
					}
				}

				// Get appointment client(s)
				$q = "SELECT aco.*, c.name_first, c.name_middle, c.name_last, o.name
					FROM lcm_app_client_org as aco
					NATURAL JOIN lcm_client as c
					LEFT JOIN lcm_org as o ON (o.id_org = aco.id_org)
					WHERE (id_app = $app AND aco.id_client = c.id_client)";

				$res_client = lcm_query($q);

				if (lcm_num_rows($res_client)>0) {
					while ($client = lcm_fetch_array($res_client))
						$participants[] = get_person_name($client)
							. ( ($client['id_org'] > 0) ? " of " . $client['name'] : ''); // TRAD
				}

				$_SESSION['form_data']['id_app'] = $app;

			if (empty($_SESSION['errors'])) {
				// Propose a description based on the appointment
				$_SESSION['form_data']['description'] = _T('fu_info_after_event', array(
							'title' => _Ti(_Tkw('appointments', $row['type'])) . $row['title'],
							'date' => format_date($row['start_time']),
							'participants' => join(', ', $participants)));

				$_SESSION['form_data']['date_start'] = $row['start_time'];
				$_SESSION['form_data']['date_end']   = $row['end_time'];
				$_SESSION['form_data']['description'] = str_replace('&nbsp;', ' ', $_SESSION['form_data']['description']);
			}
		}
	}

//
// Check for access rights
//
$edit  = allowed($_SESSION['form_data']['id_case'], 'e');
$write = allowed($_SESSION['form_data']['id_case'], 'w');

if (!($admin || $write))
	lcm_panic("You don't have permission to add follow-ups to this case");

if (isset($_SESSION['followup']) && (! $edit))
	lcm_panic("You do not have the permission to edit existing follow-ups");

//
// Change status/stage: check for if case status/stage is different than current
//

$statuses = get_possible_case_statuses();

// yes, stupid patch because of annoying PHP warnings
// the whole code needs a rewrite anyway.. too much spagetti!
if (! isset($_REQUEST['submit']))
	$_REQUEST['submit'] = '';

if ($_REQUEST['submit'] == 'set_status') {
	// Get case status
	$result = lcm_query("SELECT status FROM lcm_case WHERE id_case = " . $case);
	$row = lcm_fetch_array($result);

	if ($statuses[$_REQUEST['type']] == $row['status'])
		header('Location: ' . $_SERVER['HTTP_REFERER']);
} elseif ($_REQUEST['submit'] == 'set_stage') {
	// Get case stage
	$result = lcm_query("SELECT stage FROM lcm_case WHERE id_case = " . $case);
	$row = lcm_fetch_array($result);
	$old_stage = $row['stage'];

	if ($statuses[$_REQUEST['stage']] == $row['stage'])
		header('Location: ' . $_SERVER['HTTP_REFERER']);
}

//
// Decide whether to show 'conclusion' fields
//
$show_conclusion = false;

if ($_REQUEST['submit'] == 'set_status' || $_REQUEST['submit'] == 'set_stage') {
	$show_conclusion = true;
} elseif ($_SESSION['form_data']['type'] == 'stage_change' || is_status_change($_SESSION['form_data']['type'])) {
	$show_conclusion = true;
}

//
// Start page
//
if (isset($_SESSION['followup']))
	lcm_page_start(_T('title_fu_edit'), '', '', 'cases_followups');
else {
	if (isset($_REQUEST['type'])) {
		if ($_REQUEST['type'] == 'stage_change')
			lcm_page_start(_T('title_fu_change_stage'), '', '', 'cases_intro#stage');
		else
			lcm_page_start(_T('title_fu_change_status'), '', '', 'cases_intro#status');
	} else {
		lcm_page_start(_T('title_fu_new'), '', '', 'cases_followups');
	}
}

show_context_start();
show_context_case_title($case, 'followups');
show_context_case_involving($case);

// For 'change status' // FIXME (for edit existing fu?)
if ($_REQUEST['submit'] == 'set_status')
	show_context_item(_Ti('fu_input_current_status') . _T('case_status_option_' . $row['status']));

// For 'change stage'
if (isset($old_stage) && $old_stage)
	show_context_item(_Ti('fu_input_current_stage') . _Tkw('stage', $old_stage));

// Show stage information [ML] Not very efficient, I know, but I prefer to avoid spagetti
if ($_SESSION['form_data']['case_stage']) {
	// if editing an existing followup..
	$stage_info = get_kw_from_name('stage', $_SESSION['form_data']['case_stage']);
	$id_stage = $stage_info['id_keyword'];
	show_context_stage($case, $id_stage);
} elseif (isset($old_stage) && $old_stage) {
	// setting new stage
	$stage_info = get_kw_from_name('stage', $old_stage);
	$id_stage = $stage_info['id_keyword'];
	show_context_stage($case, $id_stage);
} else {
	// Normal follow-up
	$result = lcm_query("SELECT stage FROM lcm_case WHERE id_case = " . $case);
	$row = lcm_fetch_array($result);

	if ($row['stage']) {
		$stage_info = get_kw_from_name('stage', $row['stage']);
		$id_stage = $stage_info['id_keyword'];
		show_context_stage($case, $id_stage);
	}
}

show_context_end();

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

// Disable inputs when edit is not allowed for the field
$dis = (($admin || $edit) ? '' : 'disabled="disabled"');

echo '<form action="upd_fu.php" method="post">' . "\n";

$obj_fu = new LcmFollowupInfoUI($_SESSION['follow']);
$obj_fu->printEdit();

echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
echo '<input type="hidden" name="id_followup" value="' .  _session('id_followup') . '" />';
echo '<input type="hidden" name="id_case" value="' . _session('id_case') . '">';
echo '<input type="hidden" name="id_app" value="' . _session('id_app', 0) . '">';
echo '<input type="hidden" name="ref_edit_fu" value="' . _session('ref_edit_fu') . '">';
echo "</form>\n";

lcm_page_end();

// Clear the errors, in case user jumps to other 'edit' page
$_SESSION['errors'] = array();
$_SESSION['form_data'] = array();
$_SESSION['fu_data'] = array(); // DEPRECATED LCM 0.7.0

?>
