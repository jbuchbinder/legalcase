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

	$Id: upd_fu.php,v 1.57 2006/11/22 23:37:06 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_obj_fu');


// Clear all previous errors
$_SESSION['errors'] = array();

$id_followup = intval(_request('id_followup', 0));

// Get form data from POST fields
foreach($_POST as $key => $value)
    $_SESSION['form_data'][$key]=$value;

// Get old FU data, if updating
$old_fu_data = array();

if ($id_followup) {
	$q = "SELECT *
			FROM lcm_followup
			WHERE id_followup = $id_followup";

	$result = lcm_query($q);

	if (! ($old_fu_data = lcm_fetch_array($result)))
		lcm_panic("Could not find requested follow-up");
}

///////////////////////////////////////////////////////////////////////
//	Consequent appointment information error checking
///////////////////////////////////////////////////////////////////////
if (isset($_SESSION['form_data']['add_appointment'])) {
	// Convert day, month, year, hour, minute to date/time
	// Check submitted information

	//
	// Start time
	//
	$_SESSION['form_data']['app_start_time'] = get_datetime_from_array($_SESSION['form_data'], 'app_start', 'start', '', false);
	$unix_app_start_time = strtotime($_SESSION['form_data']['app_start_time']);
	
	if (($unix_app_start_time<0) || ! checkdate_sql($_SESSION['form_data']['app_start_time']))
		$_SESSION['errors']['app_start_time'] = 'Invalid appointment start time!'; // TRAD
	
	//
	// End time
	//
	if ($prefs['time_intervals'] == 'absolute') {
		// Set to default empty date if all fields empty
		if (!($_SESSION['form_data']['app_end_year'] || $_SESSION['form_data']['app_end_month'] || $_SESSION['form_data']['app_end_day']))
			$_SESSION['form_data']['app_end_time'] = '0000-00-00 00:00:00';
			// Report error if some of the fields empty TODO
		elseif (!$_SESSION['form_data']['app_end_year'] || !$_SESSION['form_data']['app_end_month'] || !$_SESSION['form_data']['app_end_day']) {
			$_SESSION['errors']['app_end_time'] = 'Partial appointment end time!';
			$_SESSION['form_data']['app_end_time'] = get_datetime_from_array($_SESSION['form_data'], 'app_end', 'start', '', false);
		} else {
			// Join fields and check resulting date
			$_SESSION['form_data']['app_end_time'] = get_datetime_from_array($_SESSION['form_data'], 'app_end', 'start', '', false);
			$unix_app_end_time = strtotime($_SESSION['form_data']['app_end_time']);
	
			if ( ($unix_app_end_time<0) || !checkdate($_SESSION['form_data']['app_end_month'],$_SESSION['form_data']['app_end_day'],$_SESSION['form_data']['app_end_year']) )
				$_SESSION['errors']['app_end_time'] = 'Invalid appointment end time!';
		}
	} else {
		if ( ! (isset($_SESSION['form_data']['app_delta_days']) && (!is_numeric($_SESSION['form_data']['app_delta_days']) || $_SESSION['form_data']['app_delta_days'] < 0) ||
			isset($_SESSION['form_data']['app_delta_hours']) && (!is_numeric($_SESSION['form_data']['app_delta_hours']) || $_SESSION['form_data']['app_delta_hours'] < 0) ||
			isset($_SESSION['form_data']['app_delta_minutes']) && (!is_numeric($_SESSION['form_data']['app_delta_minutes']) || $_SESSION['form_data']['app_delta_minutes'] < 0) ) ) {
			$unix_app_end_time = $unix_app_start_time
					+ $_SESSION['form_data']['app_delta_days'] * 86400
					+ $_SESSION['form_data']['app_delta_hours'] * 3600
					+ $_SESSION['form_data']['app_delta_minutes'] * 60;
			$_SESSION['form_data']['app_end_time'] = date('Y-m-d H:i:s', $unix_app_end_time);
		} else {
			$_SESSION['errors']['app_end_time'] = _Ti('app_input_time_length') . _T('time_warning_invalid_format') . ' (' . $_SESSION['form_data']['app_delta_hours'] . ')'; // XXX
			$_SESSION['form_data']['app_end_time'] = $_SESSION['form_data']['app_start_time'];
		}
	}
	
	// reminder
	if ($prefs['time_intervals']=='absolute') {
		// Set to default empty date if all fields empty
		if (!($_SESSION['form_data']['app_reminder_year'] || $_SESSION['form_data']['app_reminder_month'] || $_SESSION['form_data']['app_reminder_day']))
			$_SESSION['form_data']['app_reminder'] = '0000-00-00 00:00:00';
			// Report error if some of the fields empty
		elseif (!$_SESSION['form_data']['app_reminder_year'] || !$_SESSION['form_data']['app_reminder_month'] || !$_SESSION['form_data']['app_reminder_day']) {
			$_SESSION['errors']['app_reminder'] = 'Partial appointment reminder time!'; // TRAD
			$_SESSION['form_data']['app_reminder'] = get_datetime_from_array($_SESSION['form_data'], 'app_reminder', 'start', '', false);
		} else {
			// Join fields and check resulting time
			$_SESSION['form_data']['app_reminder'] = get_datetime_from_array($_SESSION['form_data'], 'app_reminder', 'start', '', false);
			$unix_app_reminder_time = strtotime($_SESSION['form_data']['app_reminder']);
	
			if ( ($unix_app_reminder_time<0) || !checkdate($_SESSION['form_data']['app_reminder_month'],$_SESSION['form_data']['app_reminder_day'],$_SESSION['form_data']['app_reminder_year']) )
				$_SESSION['errors']['app_reminder'] = 'Invalid appointment reminder time!'; // TRAD
		}
	} else {
		if ( ! (isset($_SESSION['form_data']['app_rem_offset_days']) && (!is_numeric($_SESSION['form_data']['app_rem_offset_days']) || $_SESSION['form_data']['app_rem_offset_days'] < 0) ||
			isset($_SESSION['form_data']['app_rem_offset_hours']) && (!is_numeric($_SESSION['form_data']['app_rem_offset_hours']) || $_SESSION['form_data']['app_rem_offset_hours'] < 0) ||
			isset($_SESSION['form_data']['app_rem_offset_minutes']) && (!is_numeric($_SESSION['form_data']['app_rem_offset_minutes']) || $_SESSION['form_data']['app_rem_offset_minutes'] < 0) ) ) {
			$unix_app_reminder_time = $unix_app_start_time
					- $_SESSION['form_data']['app_rem_offset_days'] * 86400
					- $_SESSION['form_data']['app_rem_offset_hours'] * 3600
					- $_SESSION['form_data']['app_rem_offset_minutes'] * 60;
			$_SESSION['form_data']['app_reminder'] = date('Y-m-d H:i:s', $unix_app_reminder_time);
		} else {
			$_SESSION['errors']['app_reminder'] = _Ti('app_input_reminder') . _T('time_warning_invalid_format') . ' (' . $_SESSION['form_data']['app_rem_offset_hours'] . ')'; // XXX
			$_SESSION['form_data']['app_reminder'] = $_SESSION['form_data']['app_start_time'];
		}
	}
	
	// title
	if (! $_SESSION['form_data']['app_title'])
		$_SESSION['errors']['app_title'] = _Ti('app_input_title') . _T('warning_field_mandatory');
}

//
// Check if any errors found
//
if (count($_SESSION['errors'])) {
    lcm_header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

///////////////////////////////////////////////////////////////////////
//	Followup information update
///////////////////////////////////////////////////////////////////////

$fu = new LcmFollowup($id_followup);
$errs = $fu->save();

if (count ($errs))
	$_SESSION['errors'] = array_merge($_SESSION['errors'], $errs);

if (count($_SESSION['errors'])) {
    lcm_header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

if (! $id_followup)
	$id_followup = $fu->getDataInt('id_followup', '__ASSERT__');

//
// Update stage keywords
//
if (isset($_REQUEST['new_stage']) && $_REQUEST['new_stage']) {
	$stage_info = get_kw_from_name('stage', $_REQUEST['new_stage']);
	$id_stage = $stage_info['id_keyword'];
	update_keywords_request('stage', $_SESSION['form_data']['id_case'], $id_stage);
}

//
// Update lcm_case.date_update (if fu.date_start > c.date_update)
//

$q = "SELECT date_update FROM lcm_case WHERE id_case = " . $fu->getDataInt('id_case', '__ASSERT__');
$result = lcm_query($q);

if (($row = lcm_fetch_array($result))) {
	if ($fu->getDataString('date_start', '__ASSERT__') > $row['date_update']) {
		$q = "UPDATE lcm_case
				SET date_update = '" . $fu->getDatastring('date_start') . "'
				WHERE id_case = " . $fu->getDataInt('id_case', '__ASSERT__');

		lcm_query($q);
	}
} else {
	lcm_panic("Query returned no results.");
}
		
///////////////////////////////////////////////////////////////////////
//	Consequent appointment information update
///////////////////////////////////////////////////////////////////////
if (isset($_SESSION['form_data']['add_appointment'])) {
	// No errors, proceed with database update
	$fl="	type		= '" . clean_input($_SESSION['form_data']['app_type']) . "',
		title		= '" . clean_input($_SESSION['form_data']['app_title']) . "',
		description	= '" . clean_input($_SESSION['form_data']['app_description']) . "',
		start_time	= '" . $_SESSION['form_data']['app_start_time'] . "',
		end_time	= '" . $_SESSION['form_data']['app_end_time'] . "',
		reminder	= '" . $_SESSION['form_data']['app_reminder'] . "'
		";

	// Add the new appointment
	$q = "INSERT INTO lcm_app SET ";
	// Add case ID
	$q .= 'id_case = ' . $_SESSION['form_data']['id_case'] . ',';
	// Add ID of the creator
	$q .= 'id_author = ' . $GLOBALS['author_session']['id_author'] . ',';
	// Add the rest of the fields
	$q .= "$fl, date_creation = NOW()";

	$result = lcm_query($q);

	// Get new appointment's ID
	$id_app = lcm_insert_id('lcm_app', 'id_app');
	$_SESSION['form_data']['id_app'] = $id_app;

	// Add relationship with the creator
	lcm_query("INSERT INTO lcm_author_app SET id_app=$id_app,id_author=" . $GLOBALS['author_session']['id_author']);

	// Add followup relation
	lcm_query("INSERT INTO lcm_app_fu SET id_app=$id_app,id_followup=$id_followup,relation='parent'");
}

// Send user back to add/edit page's referer or (default) to followup detail page
lcm_header('Location: fu_det.php?followup=' . $id_followup);

exit;

?>
