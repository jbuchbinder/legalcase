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

	$Id: upd_app.php,v 1.9 2005/03/24 21:06:15 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Clear all previous errors
$_SESSION['errors'] = array();

$id_app = 0;
if (isset($_REQUEST['id_app']))
	$id_app = intval($_REQUEST['id_app']);

// Get form data from POST fields
foreach($_POST as $key => $value)
    $_SESSION['app_data'][$key]=$value;

// Convert day, month, year, hour, minute to date/time
// Check submitted information
// start_time
$unix_start_time = strtotime($_SESSION['app_data']['start_year'] . '-' . $_SESSION['app_data']['start_month'] . '-' . $_SESSION['app_data']['start_day'] . ' '
						. (isset($_SESSION['app_data']['start_hour']) ? $_SESSION['app_data']['start_hour'] : '00') . ':'
						. (isset($_SESSION['app_data']['start_minutes']) ? $_SESSION['app_data']['start_minutes'] : '00') . ':'
						. (isset($_SESSION['app_data']['start_seconds']) ? $_SESSION['app_data']['start_seconds'] : '00'));

if ($unix_start_time<0)
	$_SESSION['errors']['start_time'] = 'Invalid start time!';
else 
	$_SESSION['app_data']['start_time'] = date('Y-m-d H:i:s', $unix_start_time);

// end_time
if ($prefs['time_intervals']=='absolute') {
	// Set to default empty date if all fields empty
	if (!($_SESSION['app_data']['end_year'] || $_SESSION['app_data']['end_month'] || $_SESSION['app_data']['end_day']))
		$_SESSION['app_data']['end_time'] = '0000-00-00 00:00:00';
		// Report error if some of the fields empty
	elseif (!$_SESSION['app_data']['end_year'] || !$_SESSION['app_data']['end_month'] || !$_SESSION['app_data']['end_day']) {
		$_SESSION['errors']['end_time'] = 'Partial end time!';
		$_SESSION['app_data']['end_time'] = ($_SESSION['app_data']['end_year'] ? $_SESSION['app_data']['end_year'] : '0000') . '-'
							. ($_SESSION['app_data']['end_month'] ? $_SESSION['app_data']['end_month'] : '00') . '-'
							. ($_SESSION['app_data']['end_day'] ? $_SESSION['app_data']['end_day'] : '00') . ' '
							. ($_SESSION['app_data']['end_hour'] ? $_SESSION['app_data']['end_hour'] : '00') . ':'
							. ($_SESSION['app_data']['end_minutes'] ? $_SESSION['app_data']['end_minutes'] : '00') . ':'
							. ($_SESSION['app_data']['end_seconds'] ? $_SESSION['app_data']['end_seconds'] : '00');
	} else {
		// Join fields and check resulting date
		$unix_end_time = strtotime($_SESSION['app_data']['end_year'] . '-' . $_SESSION['app_data']['end_month'] . '-' . $_SESSION['app_data']['end_day']
					. ' ' . $_SESSION['app_data']['end_hour'] . ':' . $_SESSION['app_data']['end_minutes'] . ':'
					. (isset($_SESSION['app_data']['end_seconds']) ? $_SESSION['app_data']['end_seconds'] : '00'));
	
		if ($unix_end_time<0)
			$_SESSION['errors']['end_time'] = 'Invalid end time!';
		else 
			$_SESSION['app_data']['end_time'] = date('Y-m-d H:i:s',$unix_end_time);
	}
} else {
	if ( ! (isset($_SESSION['app_data']['delta_days']) && (!is_numeric($_SESSION['app_data']['delta_days']) || $_SESSION['app_data']['delta_days'] < 0) ||
		isset($_SESSION['app_data']['delta_hours']) && (!is_numeric($_SESSION['app_data']['delta_hours']) || $_SESSION['app_data']['delta_hours'] < 0) ||
		isset($_SESSION['app_data']['delta_minutes']) && (!is_numeric($_SESSION['app_data']['delta_minutes']) || $_SESSION['app_data']['delta_minutes'] < 0) ) ) {
		$unix_end_time = $unix_start_time
				+ $_SESSION['app_data']['delta_days'] * 86400
				+ $_SESSION['app_data']['delta_hours'] * 3600
				+ $_SESSION['app_data']['delta_minutes'] * 60;
		$_SESSION['app_data']['end_time'] = date('Y-m-d H:i:s', $unix_end_time);
	} else {
		$_SESSION['errors']['end_time'] = 'Invalid time interval!';	// TRAD
		$_SESSION['app_data']['end_time'] = $_SESSION['app_data']['start_time'];
	}
}

// reminder
if ($prefs['time_intervals']=='absolute') {
	// Set to default empty date if all fields empty
	if (!($_SESSION['app_data']['reminder_year'] || $_SESSION['app_data']['reminder_month'] || $_SESSION['app_data']['reminder_day']))
		$_SESSION['app_data']['reminder'] = '0000-00-00 00:00:00';
		// Report error if some of the fields empty
	elseif (!$_SESSION['app_data']['reminder_year'] || !$_SESSION['app_data']['reminder_month'] || !$_SESSION['app_data']['reminder_day']) {
		$_SESSION['errors']['reminder'] = 'Partial reminder time!';
		$_SESSION['app_data']['reminder'] = ($_SESSION['app_data']['reminder_year'] ? $_SESSION['app_data']['reminder_year'] : '0000') . '-'
							. ($_SESSION['app_data']['reminder_month'] ? $_SESSION['app_data']['reminder_month'] : '00') . '-'
							. ($_SESSION['app_data']['reminder_day'] ? $_SESSION['app_data']['reminder_day'] : '00') . ' '
							. ($_SESSION['app_data']['reminder_hour'] ? $_SESSION['app_data']['reminder_hour'] : '00') . ':'
							. ($_SESSION['app_data']['reminder_minutes'] ? $_SESSION['app_data']['reminder_minutes'] : '00') . ':'
							. ($_SESSION['app_data']['reminder_seconds'] ? $_SESSION['app_data']['reminder_seconds'] : '00');
	} else {
		// Join fields and check resulting time
		$unix_reminder_time = strtotime($_SESSION['app_data']['reminder_year'] . '-' . $_SESSION['app_data']['reminder_month'] . '-' . $_SESSION['app_data']['reminder_day']
					. ' ' . $_SESSION['app_data']['reminder_hour'] . ':' . $_SESSION['app_data']['reminder_minutes'] . ':'
					. (isset($_SESSION['app_data']['reminder_seconds']) ? $_SESSION['app_data']['reminder_seconds'] : '00'));
	
		if ($unix_reminder_time<0)
			$_SESSION['errors']['reminder'] = 'Invalid reminder time!';
		else 
			$_SESSION['app_data']['reminder'] = date('Y-m-d H:i:s',$unix_reminder_time);
	}
} else {
	if ( ! (isset($_SESSION['app_data']['rem_offset_days']) && (!is_numeric($_SESSION['app_data']['rem_offset_days']) || $_SESSION['app_data']['rem_offset_days'] < 0) ||
		isset($_SESSION['app_data']['rem_offset_hours']) && (!is_numeric($_SESSION['app_data']['rem_offset_hours']) || $_SESSION['app_data']['rem_offset_hours'] < 0) ||
		isset($_SESSION['app_data']['rem_offset_minutes']) && (!is_numeric($_SESSION['app_data']['rem_offset_minutes']) || $_SESSION['app_data']['rem_offset_minutes'] < 0) ) ) {
		$unix_reminder_time = $unix_start_time
				- $_SESSION['app_data']['rem_offset_days'] * 86400
				- $_SESSION['app_data']['rem_offset_hours'] * 3600
				- $_SESSION['app_data']['rem_offset_minutes'] * 60;
		$_SESSION['app_data']['reminder'] = date('Y-m-d H:i:s', $unix_reminder_time);
	} else {
		$_SESSION['errors']['reminder'] = 'Invalid reminder offset!';	// TRAD
		$_SESSION['app_data']['reminder'] = $_SESSION['app_data']['start_time'];
	}
}

// title
if (!(strlen($_SESSION['app_data']['title'])>0)) $_SESSION['errors']['title'] = 'Appointment title should not be empty!';

//
// Check if errors found
//
if (count($_SESSION['errors'])) {
	// Errors, return to editing page
	header("Location: " . $GLOBALS['HTTP_REFERER']);
	exit;
} else {
	// No errors, proceed with database update
	$fl="	type		= '" . clean_input($_SESSION['app_data']['type']) . "',
		title		= '" . clean_input($_SESSION['app_data']['title']) . "',
		description	= '" . clean_input($_SESSION['app_data']['description']) . "',
		start_time	= '" . $_SESSION['app_data']['start_time'] . "',
		end_time	= '" . $_SESSION['app_data']['end_time'] . "',
		reminder	= '" . $_SESSION['app_data']['reminder'] . "'
		";

	// Insert/update appointment
	if ($id_app>0) {
		// Update existing appointment
		$q="UPDATE lcm_app SET $fl,date_update=NOW() WHERE id_app=$id_app";
		// Only admin or appointment author itself could change it
		if ( !($GLOBALS['author_session']['status'] === 'admin') )
			$q .= "AND id_author=" . $GLOBALS['author_session']['id_author'];
		if (!($result = lcm_query($q)))
			lcm_panic("$q <br />\nError ".lcm_errno().": ".lcm_error());
	} else {
		// Add the new appointment
		$q = "INSERT INTO lcm_app SET id_app=0";
		// Add case ID if available
		$q .= ( ($_SESSION['app_data']['id_case']) ? ',id_case=' . $_SESSION['app_data']['id_case'] : '' );
		// Add ID of the creator
		$q .= ',id_author=' . $GLOBALS['author_session']['id_author'];
		// Add the rest of the fields
		$q .= ",$fl,date_creation=NOW()";

		if (!($result = lcm_query($q))) 
			lcm_panic("$q<br>\nError ".lcm_errno().": ".lcm_error());

		$id_app = lcm_insert_id();
		$_SESSION['app_data']['id_app'] = $id_app;

		// Add relationship with the creator
		$q = "INSERT INTO lcm_author_app SET id_app=$id_app,id_author=" . $GLOBALS['author_session']['id_author'];

		if (!($result = lcm_query($q))) 
			lcm_panic("$q<br>\nError ".lcm_errno().": ".lcm_error());

	}

	// Add/update appointment participants (authors)
	if (!empty($_SESSION['app_data']['author'])) {
		$q = "INSERT IGNORE INTO lcm_author_app SET id_app=$id_app,id_author=" . $_SESSION['app_data']['author'];
		if ($result = lcm_query($q))
			$_SESSION['errors']['author_added'] = "An author was added to the participants of this appointment.";
	}

	// Add/update appointment clients/organisations
	if (!empty($_SESSION['app_data']['client'])) {
		$client_org = explode(':',$_SESSION['app_data']['client']);
		$q = "INSERT IGNORE INTO lcm_app_client_org SET id_app=$id_app";
		$q .= ',id_client=' . $client_org[0];
		if ($client_org[1]) $q .= ',id_org=' . $client_org[1];
		if ($result = lcm_query($q))
			$_SESSION['errors']['client_added'] = "An client/organisation was added to the participants of this appointment.";
	}

	// Check if author or client/organisation was added
	if (!empty($_SESSION['errors'])) {
//		header('Location: ' . $_SERVER['HTTP_REFERER'] );
		$ref_url = parse_url($_SERVER['HTTP_REFERER']);
		parse_str($ref_url['query'],$params);
		$params['app'] = $id_app;
		foreach ($params as $k => $v) {
			$params[$k] = $k . '=' . urlencode($v);
		}
		header('Location: edit_app.php?' . join('&',$params) );
		exit;
	}
	
	// Send user back to add/edit page's referer or (default) to appointment detail page
	switch ($_SESSION['app_data']['submit']) {
		case 'add_author':
		case 'add_client':
			// Go back to edit the same appointment. Save the original referer
			header('Location: ' . $_SERVER['HTTP_REFERER'] );
			break;
		case 'add' :
			// Go back to the edit page's referer
			unset($_SESSION['errors']);
			header('Location: ' . ($_SESSION['app_data']['ref_edit_app'] ? $_SESSION['app_data']['ref_edit_app'] : "app_det.php?app=$id_app"));
			break;
		case 'addnew' :
			// Open new appointment. Save the original referer
			unset($_SESSION['errors']);
			header('Location: edit_app.php?app=0&ref=' . ($_SESSION['app_data']['ref_edit_app'] ? $_SESSION['app_data']['ref_edit_app'] : "app_det.php?app=$id_app") );
			break;
		case 'adddet' :
		case 'submit' :
		default :
			// Go to appointment details
			unset($_SESSION['errors']);
			header("Location: app_det.php?app=$id_app");
	}	
	exit;
}

?>
