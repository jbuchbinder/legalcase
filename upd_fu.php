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

	$Id: upd_fu.php,v 1.25 2005/01/26 22:13:12 mlutfy Exp $
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
if(!session_is_registered("fu_data"))
    session_register("fu_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $fu_data[$key]=$value;

// Convert day, month, year to date
// Check submitted information
// date_start
$unix_date_start = strtotime($fu_data['start_year'] . '-' . $fu_data['start_month'] . '-' . $fu_data['start_day'] . ' ' .
							 $fu_data['start_hour'] . ':' . $fu_data['start_minutes'] . ':' .
							 (isset($fu_data['start_seconds']) ? $fu_data['start_seconds'] : '00'));

if ($unix_date_start<0)
	$_SESSION['errors']['date_start'] = 'Invalid start date!';
else 
	$fu_data['date_start'] = date('Y-m-d H:i:s', $unix_date_start);

// date_end
// Set to default empty date if all fields empty
if (!($fu_data['end_year'] || $fu_data['end_month'] || $fu_data['end_day']))
	$fu_data['date_end'] = '0000-00-00 00:00:00';
	// Report error if some of the fields empty
elseif (!$fu_data['end_year'] || !$fu_data['end_month'] || !$fu_data['end_day']) {
	$_SESSION['errors']['date_end'] = 'Partial end date!';
	$fu_data['date_end'] = ($fu_data['end_year'] ? $fu_data['end_year'] : '0000') . '-'
						. ($fu_data['end_month'] ? $fu_data['end_month'] : '00') . '-'
						. ($fu_data['end_day'] ? $fu_data['end_day'] : '00') . ' 00:00:00';
} else {
	// Join fields and check resulting date
	$unix_date_end = strtotime($fu_data['end_year'] . '-' . $fu_data['end_month'] . '-' . $fu_data['end_day']);

	if ($unix_date_end<0)
		$_SESSION['errors']['date_end']='Invalid end date!';
	else 
		$fu_data['date_end'] = date('Y-m-d H:i:s',$unix_date_end);
}

if (count($_SESSION['errors'])) {
    header("Location: " . $GLOBALS['HTTP_REFERER']);
    exit;
} else {
	global $author_session;

    $fl="id_author   =  " . $author_session['id_author'] . ",
		date_start   = '" . clean_input($fu_data['date_start']) . "',
		date_end     = '" . clean_input($fu_data['date_end']) . "',
		type         = '" . clean_input($fu_data['type']) . "',
		description  = '" . clean_input($fu_data['description']) . "',
    	sumbilled    = '" . clean_input($fu_data['sumbilled']) . "'";

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
		switch ($fu_data['type']) {
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
		$q = "INSERT INTO lcm_followup SET id_followup=0,id_case=$id_case,$fl";

		if (!($result = lcm_query($q))) 
			lcm_panic("$q<br>\nError ".lcm_errno().": ".lcm_error());

		$id_followup = lcm_insert_id();
    }

    // Send user back to add/edit page's referer or (default) to followup detail page
    header('Location: ' . ($fu_data['ref_edit_fu'] ? $fu_data['ref_edit_fu'] : "fu_det.php?followup=$id_followup"));
	exit;
}

?>
