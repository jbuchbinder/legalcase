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

	$Id: upd_fu.php,v 1.31 2005/02/10 09:12:08 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');


// Clear all previous errors
$_SESSION['errors'] = array();

$id_followup = 0;
if (isset($_REQUEST['id_followup']) && $_REQUEST['id_followup'] > 0)
	$id_followup = $_REQUEST['id_followup'];

// Register form data in the session
// XXX [ML] use $_SESSION
//if(!session_is_registered("fu_data"))
//    session_register("fu_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $_SESSION['fu_data'][$key]=$value;

// Convert day, month, year to date
// Check submitted information
// date_start
$unix_date_start = strtotime($_SESSION['fu_data']['start_year'] . '-' . $_SESSION['fu_data']['start_month'] . '-' . $_SESSION['fu_data']['start_day'] . ' '
						. (isset($_SESSION['fu_data']['start_hour']) ? $_SESSION['fu_data']['start_hour'] : '00') . ':'
						. (isset($_SESSION['fu_data']['start_minutes']) ? $_SESSION['fu_data']['start_minutes'] : '00') . ':'
						. (isset($_SESSION['fu_data']['start_seconds']) ? $_SESSION['fu_data']['start_seconds'] : '00'));

if ($unix_date_start<0)
	$_SESSION['errors']['date_start'] = 'Invalid start date!';
else 
	$_SESSION['fu_data']['date_start'] = date('Y-m-d H:i:s', $unix_date_start);

// date_end
if ($prefs['time_intervals']=='absolute') {
	// Set to default empty date if all fields empty
	if (!($_SESSION['fu_data']['end_year'] || $_SESSION['fu_data']['end_month'] || $_SESSION['fu_data']['end_day']))
		$_SESSION['fu_data']['date_end'] = '0000-00-00 00:00:00';
		// Report error if some of the fields empty
	elseif (!$_SESSION['fu_data']['end_year'] || !$_SESSION['fu_data']['end_month'] || !$_SESSION['fu_data']['end_day']) {
		$_SESSION['errors']['date_end'] = 'Partial end date!';
		$_SESSION['fu_data']['date_end'] = ($_SESSION['fu_data']['end_year'] ? $_SESSION['fu_data']['end_year'] : '0000') . '-'
							. ($_SESSION['fu_data']['end_month'] ? $_SESSION['fu_data']['end_month'] : '00') . '-'
							. ($_SESSION['fu_data']['end_day'] ? $_SESSION['fu_data']['end_day'] : '00') . ' '
							. ($_SESSION['fu_data']['end_hour'] ? $_SESSION['fu_data']['end_hour'] : '00') . ':'
							. ($_SESSION['fu_data']['end_minutes'] ? $_SESSION['fu_data']['end_minutes'] : '00') . ':'
							. ($_SESSION['fu_data']['end_seconds'] ? $_SESSION['fu_data']['end_seconds'] : '00');
	} else {
		// Join fields and check resulting date
		$unix_date_end = strtotime($_SESSION['fu_data']['end_year'] . '-' . $_SESSION['fu_data']['end_month'] . '-' . $_SESSION['fu_data']['end_day']
					. ' ' . $_SESSION['fu_data']['end_hour'] . ':' . $_SESSION['fu_data']['end_minutes'] . ':'
					. (isset($_SESSION['fu_data']['end_seconds']) ? $_SESSION['fu_data']['end_seconds'] : '00'));
	
		if ($unix_date_end<0)
			$_SESSION['errors']['date_end'] = 'Invalid end date!';
		else 
			$_SESSION['fu_data']['date_end'] = date('Y-m-d H:i:s',$unix_date_end);
	}
} else {
	$unix_date_end = $unix_date_start
			+ $_SESSION['fu_data']['delta_days'] * 86400
			+ $_SESSION['fu_data']['delta_hours'] * 3600
			+ $_SESSION['fu_data']['delta_minutes'] * 60;
	$_SESSION['fu_data']['date_end'] = date('Y-m-d H:i:s', $unix_date_end);
}

if (count($_SESSION['errors'])) {
    header("Location: " . $GLOBALS['HTTP_REFERER']);
    exit;
} else {
	// global $author_session;

	$fl="	date_start   = '" . clean_input($_SESSION['fu_data']['date_start']) . "',
		date_end     = '" . clean_input($_SESSION['fu_data']['date_end']) . "',
		type         = '" . clean_input($_SESSION['fu_data']['type']) . "',
		description  = '" . clean_input($_SESSION['fu_data']['description']) . "',
		sumbilled    = '" . clean_input($_SESSION['fu_data']['sumbilled']) . "'";

	if ($id_followup>0) {
		// Check access rights
		if (!allowed($id_case,'e')) die("You don't have permission to modify this case's information!");

		$q="UPDATE lcm_followup SET $fl WHERE id_followup = $id_followup";
		if (!($result = lcm_query($q)))
			lcm_panic("$q <br />\nError ".lcm_errno().": ".lcm_error());
	} else {
		// Check access rights
		if (!allowed($id_case,'w'))
			die("You don't have permission to add information to this case!");

		// Update case status
		switch ($_SESSION['fu_data']['type']) {
			case 'conclusion' :
				$status = 'closed';
				break;
			case 'suspension' :
				$status = 'suspended';
				break;
			case 'opening' :
			case 'resumption' :
			case 'reopening' :
				$status = 'open';
				break;
			case 'merge' :
				$status = 'merged';
				break;
			default: $status = '';
		}
		
		if ($status) {
			$q = "UPDATE lcm_case
					SET status='$status'
					WHERE id_case=$id_case";
			$result = lcm_query($q);
		}
		
		// Add the new follow-up
		$q = "INSERT INTO lcm_followup SET id_followup=0,id_case=$id_case,id_author=" . $GLOBALS['author_session']['id_author'] . ",$fl";

		if (!($result = lcm_query($q))) 
			lcm_panic("$q<br>\nError ".lcm_errno().": ".lcm_error());

		$id_followup = lcm_insert_id();
	}

	// Send user back to add/edit page's referer or (default) to followup detail page
	header('Location: ' . ($_SESSION['fu_data']['ref_edit_fu'] ? $_SESSION['fu_data']['ref_edit_fu'] : "fu_det.php?followup=$id_followup"));
	
	exit;
}

?>
