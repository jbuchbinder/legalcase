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

	$Id: upd_app.php,v 1.22 2006/03/21 16:50:59 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Clear all previous errors
$_SESSION['errors'] = array();

$id_app = _request('id_app', 0);

// Get form data from POST fields
foreach($_POST as $key => $value)
    $_SESSION['form_data'][$key]=$value;

//
// Check access rights
//

$ac = get_ac_app($id_app);

// XXX FIXME make better check?
if (! $ac['w'])
	die("access denied");

// Convert day, month, year, hour, minute to date/time
// Check submitted information

// XXX for some reason (bad memory), date_start doesn't allow the user to leave
// some fields empty, but date_end (in absolute more) does. Hence extra validation.

//
// Start date
//
$_SESSION['form_data']['start_time'] = get_datetime_from_array($_SESSION['form_data'], 'start', 'start', '', false);
$unix_start_time = strtotime($_SESSION['form_data']['start_time']);

if (($unix_start_time < 0) || ! checkdate_sql($_SESSION['form_data']['start_time']))
	$_SESSION['errors']['start_time'] = _Ti('time_input_date_start') . 'Invalid date'; // TRAD

//
// End date
//
if ($prefs['time_intervals'] == 'absolute') {
	$_SESSION['form_data']['end_time'] = get_datetime_from_array($_SESSION['form_data'], 'end', 'start', '', false);

	// Set to default empty date if all fields empty
	if (! isset_datetime_from_array($_SESSION['form_data'], 'end', 'date_only')) { 
		$_SESSION['errors']['end_time'] = _Ti('time_input_date_end') . 'Invalid date'; // TRAD
	} else {
		$unix_end_time = strtotime($_SESSION['form_data']['end_time']);

		if (($unix_end_time < 0) || !checkdate_sql($_SESSION['form_data']['end_time'])) 
			$_SESSION['errors']['end_time'] = _Ti('time_input_date_end') . 'Invalid date'; // TRAD
	}
} else {
	if ( ! (isset($_SESSION['form_data']['delta_days']) && (!is_numeric($_SESSION['form_data']['delta_days']) || $_SESSION['form_data']['delta_days'] < 0) ||
		isset($_SESSION['form_data']['delta_hours']) && (!is_numeric($_SESSION['form_data']['delta_hours']) || $_SESSION['form_data']['delta_hours'] < 0) ||
		isset($_SESSION['form_data']['delta_minutes']) && (!is_numeric($_SESSION['form_data']['delta_minutes']) || $_SESSION['form_data']['delta_minutes'] < 0) ) ) {
		$unix_end_time = $unix_start_time
				+ $_SESSION['form_data']['delta_days'] * 86400
				+ $_SESSION['form_data']['delta_hours'] * 3600
				+ $_SESSION['form_data']['delta_minutes'] * 60;
		$_SESSION['form_data']['end_time'] = date('Y-m-d H:i:s', $unix_end_time);
	} else {
		$_SESSION['errors']['end_time'] = _Ti('app_input_time_length') . _T('time_warning_invalid_format') . ' (' . $_SESSION['form_data']['delta_hours'] . ')'; // XXX
		$_SESSION['form_data']['end_time'] = $_SESSION['form_data']['start_time'];
	}
}

if (!count($_SESSION['errors']) && $unix_end_time < $unix_start_time)
	$_SESSION['errors']['end_time'] = "The date interval is not valid (end before start)"; // TRAD

// reminder
if ($prefs['time_intervals']=='absolute') {
	// Set to default empty date if all fields empty
	if (!($_SESSION['form_data']['reminder_year'] || $_SESSION['form_data']['reminder_month'] || $_SESSION['form_data']['reminder_day']))
		$_SESSION['form_data']['reminder'] = '0000-00-00 00:00:00';
		// Report error if some of the fields empty
	elseif (!$_SESSION['form_data']['reminder_year'] || !$_SESSION['form_data']['reminder_month'] || !$_SESSION['form_data']['reminder_day']) {
		$_SESSION['errors']['reminder'] = 'Incomplete reminder time'; // TRAD
		$_SESSION['form_data']['reminder'] = get_datetime_from_array($_SESSION['form_data'], 'reminder', 'start', '', false);
	} else {
		// Join fields and check resulting time
		$_SESSION['form_data']['reminder'] = get_datetime_from_array($_SESSION['form_data'], 'reminder', 'start', '', false);
		$unix_reminder_time = strtotime($_SESSION['form_data']['reminder']);

		if ( ($unix_reminder_time<0) || !checkdate($_SESSION['form_data']['reminder_month'],$_SESSION['form_data']['reminder_day'],$_SESSION['form_data']['reminder_year']) )
			$_SESSION['errors']['reminder'] = 'Invalid reminder time!'; // TRAD
	}
} else {
	if ( ! (isset($_SESSION['form_data']['rem_offset_days']) && (!is_numeric($_SESSION['form_data']['rem_offset_days']) || $_SESSION['form_data']['rem_offset_days'] < 0) ||
		isset($_SESSION['form_data']['rem_offset_hours']) && (!is_numeric($_SESSION['form_data']['rem_offset_hours']) || $_SESSION['form_data']['rem_offset_hours'] < 0) ||
		isset($_SESSION['form_data']['rem_offset_minutes']) && (!is_numeric($_SESSION['form_data']['rem_offset_minutes']) || $_SESSION['form_data']['rem_offset_minutes'] < 0) ) ) {
		$unix_reminder_time = $unix_start_time
				- $_SESSION['form_data']['rem_offset_days'] * 86400
				- $_SESSION['form_data']['rem_offset_hours'] * 3600
				- $_SESSION['form_data']['rem_offset_minutes'] * 60;
		$_SESSION['form_data']['reminder'] = date('Y-m-d H:i:s', $unix_reminder_time);
	} else {
		$_SESSION['errors']['reminder'] = _Ti('app_input_reminder') . _T('time_warning_invalid_format') . ' (' . $_SESSION['form_data']['rem_offset_hours'] . ')'; // XXX
		$_SESSION['form_data']['reminder'] = $_SESSION['form_data']['start_time'];
	}
}

// title
if (!(strlen($_SESSION['form_data']['title'])>0)) $_SESSION['errors']['title'] = 'Appointment title should not be empty!';	// TRAD

//
// Check if errors found
//
if (count($_SESSION['errors'])) {
	// Errors, return to editing page
	lcm_header("Location: " . $_SERVER['HTTP_REFERER']);
	exit;
} else {
	// No errors, proceed with database update
	$fl=" type      = '" . _session('type') . "',
		title       = '" . _session('title') . "',
		description = '" . _session('description') . "',
		start_time  = '" . _session('start_time') . "',
		end_time    = '" . _session('end_time') . "',
		reminder    = '" . _session('reminder') . "'
		";

	// Insert/update appointment
	if ($id_app>0) {
		// Update existing appointment
		$q="UPDATE lcm_app SET $fl,date_update=NOW() WHERE id_app = $id_app ";
		// Only admin or appointment author itself could change it
		if ( !($GLOBALS['author_session']['status'] === 'admin') )
			$q .= " AND id_author = " . $GLOBALS['author_session']['id_author'];

		$result = lcm_query($q);
	} else {
		// Add the new appointment
		$q = "INSERT INTO lcm_app SET ";
		// Add case ID if available
		$q .= (_session('id_case') ? 'id_case=' . _session('id_case') . ',' : '');
		// Add ID of the creator
		$q .= 'id_author = ' . $GLOBALS['author_session']['id_author'] . ',';
		// Add the rest of the fields
		$q .= "$fl, date_creation = NOW()";

		$result = lcm_query($q);

		$id_app = lcm_insert_id('lcm_app', 'id_app');
		$_SESSION['form_data']['id_app'] = $id_app;

		// Add relationship with the creator
		lcm_query("INSERT INTO lcm_author_app SET id_app=$id_app,id_author=" . $GLOBALS['author_session']['id_author']);

		// Add relationship with the parent followup (if any)
		if (!empty($_SESSION['form_data']['id_followup']))
			lcm_query("INSERT INTO lcm_app_fu SET id_app=$id_app,id_followup=" . $_SESSION['form_data']['id_followup'] . ",relation='parent'");

	}

	// Add/update appointment participants (authors)
	if (_session('author')) {
		$q = "INSERT INTO lcm_author_app SET id_app = $id_app, id_author = " . _session('author');

		lcm_query($q, true); // ignore errors
		$_SESSION['errors']['author_added'] = "An author was added to the participants of this appointment."; // TRAD
	}

	// Remove appointment participants (authors)
	if (_session('rem_author')) {
		$q = "DELETE FROM lcm_author_app WHERE id_app=$id_app AND id_author IN (" . join(',', _session('rem_author')) . ")";
		if ( ($result = lcm_query($q)) && (mysql_affected_rows() > 0) ) // XXX MySQL SPECIFIC
			$_SESSION['errors']['author_removed'] = "Author(s) was/were removed from participants of this appointment.";
		// Clean author removal list
		unset($_SESSION['form_data']['rem_author']);
	}

	// Add/update appointment clients/organisations
	if (_session('client')) {
		$client_org = explode(':', _session('client'));
		$q = "INSERT INTO lcm_app_client_org SET id_app=$id_app";
		$q .= ',id_client=' . $client_org[0];

		if ($client_org[1])
			$q .= ',id_org=' . $client_org[1];

		lcm_query($q, true); // ignore errors
		$_SESSION['errors']['client_added'] = "An client/organisation was added to the participants of this appointment.";
	}

	// Remove appointment participants (clients/organisations)
	if (_session('rem_client')) {
		$q = "DELETE FROM lcm_app_client_org WHERE id_app=$id_app AND (0";
		foreach($_SESSION['form_data']['rem_client'] as $rem_cli) {
			$client_org = explode(':',$rem_cli);
			$co .= 'id_client=' . $client_org[0];
			if ($client_org[1])
				$co = "($co AND id_org=" . $client_org[1] . ')';
			$q .= " OR $co";
		}
		$q .= ")";
		if ( ($result = lcm_query($q)) && (mysql_affected_rows() > 0) ) // XXX MySQL SPECIFIC
			$_SESSION['errors']['client_added'] = "An client/organisation was added to the participants of this appointment.";
		// Clean client removal list
		unset($_SESSION['form_data']['rem_client']);
	}

	// Check if author or client/organisation was added
	if (count($_SESSION['errors'])) {
		$ref_url = parse_url($_SERVER['HTTP_REFERER']);
		parse_str($ref_url['query'],$params);
		$params['app'] = $id_app;

		foreach ($params as $k => $v)
			$params[$k] = $k . '=' . urlencode($v);

		lcm_header('Location: edit_app.php?' . join('&',$params) );
		exit;
	}
	
	// Send user back to add/edit page's referer or (default) to appointment detail page
	switch (_session('submit')) {
		case 'add_author':
		case 'add_client':
			// Go back to edit the same appointment. Save the original referer
			lcm_header('Location: ' . $_SERVER['HTTP_REFERER']);
			break;
		case 'add' :
			// Go back to the edit page's referer
			unset($_SESSION['errors']);
			lcm_header('Location: ' . _session('ref_edit_app', "app_det.php?app=$id_app"));
			break;
		case 'addnew' :
			// Open new appointment. Save the original referer
			unset($_SESSION['errors']);
			lcm_header('Location: edit_app.php?app=0&ref=' . _session('ref_edit_app', "app_det.php?app=$id_app"));
			break;
		case 'adddet' :
		case 'submit' :
		default :
			// Go to appointment details
			unset($_SESSION['errors']);
			lcm_header("Location: app_det.php?app=$id_app");
	}	
	exit;
}

?>
