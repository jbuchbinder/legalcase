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

	$Id: upd_case.php,v 1.35 2005/03/09 15:38:19 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Clear all previous errors
$_SESSION['errors'] = array();

// Register form data in the session
//if(!session_is_registered("case_data"))
//    session_register("case_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $_SESSION['case_data'][$key]=$value;

// Check case data for validity
if (!$_SESSION['case_data']['title'])
	$_SESSION['errors']['title'] = _T('case_warning_no_title');

if (count($_SESSION['errors'])) {
    header("Location: $HTTP_REFERER");
    exit;
} else {
	//$cl = '(id_case,title,id_court_archive,date_creation,date_assignment,legal_reason,alledged_crime,status)';
	//$vl = "($id_case,'$title','$id_court_archive','$date_creation',";
	//$vl .= "'$date_assignment','$legal_reason','$alledged_crime','$status')";
	$fl = "title='" . clean_input($_SESSION['case_data']['title']) . "',
			id_court_archive='" . clean_input($_SESSION['case_data']['id_court_archive']) . "',";
	$fl .= "
			legal_reason='" . clean_input($_SESSION['case_data']['legal_reason']) . "',
			alledged_crime='" . clean_input($_SESSION['case_data']['alledged_crime']) . "'";

	// Add status to the list of fields
	$fl .= ",status='" . $_SESSION['case_data']['status'] . "'";

	// Add stage to the list of fields
	$fl .= ",stage='" . $_SESSION['case_data']['stage'] . "'";

// Put public access rights settings in a separate string
	$public_access_rights = '';
	if ($_SESSION['case_data']['public'] || read_meta('case_read_always'))
		$public_access_rights .= "public=1";
	else
		$public_access_rights .= "public=0";

	if ($_SESSION['case_data']['pub_write'] || read_meta('case_write_always'))
		$public_access_rights .= ", pub_write=1";
	else
		$public_access_rights .= ", pub_write=0";

	if ($id_case > 0) {
		// This is modification of existing case

		// Check access rights
		if (!allowed($id_case,'e')) die("You don't have permission to change this case's information!");

		// If admin access is allowed, set all fields
		if (allowed($id_case,'a'))
			$q = "UPDATE lcm_case SET $fl,$public_access_rights WHERE id_case=$id_case";
		else
			$q = "UPDATE lcm_case SET $fl WHERE id_case=$id_case";
	} else {
		// This is new case
		$q = "INSERT INTO lcm_case SET id_case=0,date_creation=NOW(),$fl,$public_access_rights";
		$result = lcm_query($q);
		$id_case = lcm_insert_id($result);
		$id_author = $GLOBALS['author_session']['id_author'];

		// Insert new case_author relation
		$q = "INSERT INTO lcm_case_author SET
				id_case=$id_case,
				id_author=$id_author,
				ac_read=1,
				ac_write=1,
				ac_admin=1";
		$result = lcm_query($q);

		// Get author information
		$q = "SELECT *
				FROM lcm_author
				WHERE id_author=$id_author";
		$result = lcm_query($q);
		$author_data = lcm_fetch_array($result);

		// Add 'assignment' followup to the case
		$q = "INSERT INTO lcm_followup
				SET id_followup=0,id_case=$id_case,id_author=$id_author,type='assignment',description='";
		$q .= njoin(array($author_data['name_first'], $author_data['name_middle'], $author_data['name_last']));
		$q .= " created the case and is auto-assigned to it',date_start=NOW()";
		$result = lcm_query($q);

		// Set case date_assigned to NOW()
		$q = "UPDATE lcm_case
				SET date_assignment=NOW()
				WHERE id_case=$id_case";

		// Last query is executed outside this block, so don't put lcm_query() for it!
	}

	// Some advanced ideas for future use
	//$q="INSERT INTO lcm_case SET id_case=$id_case,$fl ON DUPLICATE KEY UPDATE $fl";
	//$q="INSERT INTO lcm_case $cl VALUES $vl ON DUPLICATE KEY UPDATE $fl";

	$result = lcm_query($q);

	// [ML] I don't understand why: header("Location: $ref_edit_case");
	// [AG] Because "edit_case" could be invoked from diferent places i.e. edit existing case or add new or other.
	// [AG] User could come to edit from listcases.php or case_det.php. Also, other references could be added later.
	// [AG] In each case the return page will be different.

	//header("Location: case_det.php?case=$id_case");
	$ref_edit_case = ($_SESSION['case_data']['ref_edit_case'] ? $_SESSION['case_data']['ref_edit_case'] : "case_det.php?case=$id_case");
	$send_to = '';

	// Proceed accoring to the button type
	switch ($submit) {
		case 'addnew':
			$send_to = "edit_case.php?case=0&ref=$ref_edit_case";
			// header("Location: edit_case.php?case=0&ref=$ref_edit_case");
			break;
		case 'adddet':
			$send_to = "case_det.php?case=$id_case";
			// header("Location: case_det.php?case=$id_case");
			break;
		default :
			$send_to = $ref_edit_case;
			// header("Location: $ref_edit_case");
	}

	// Send to add_client if any client to attach
	if ($_SESSION['case_data']['attach_client']) {
		header("Location: add_client.php?case=$id_case"
			. "&clients[]=" .  $_SESSION['case_data']['attach_client'] 
			. "&ref_sel_client=" . rawurlencode($send_to));
		exit;
	}

	header("Location: " . $send_to);
}
?>
