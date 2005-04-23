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

	$Id: upd_case.php,v 1.48 2005/04/23 11:59:45 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

global $author_session;

// Clear all previous errors
$_SESSION['errors'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value)
    $_SESSION['case_data'][$key]=$value;

// Check case data for validity
if (!$_SESSION['case_data']['title'])
	$_SESSION['errors']['title'] = _T('case_warning_no_title');

// Date assignment (check only if a date is provided)
$_SESSION['case_data']['date_assignment'] = get_datetime_from_array($_SESSION['case_data'], 'assignment', 'start', date('Y-m-d H:i:s'));

if (! checkdate_sql($_SESSION['case_data']['date_assignment']))
	$_SESSION['errors']['date_assignment'] = _Ti('case_input_date_assigned') . 'Invalid date.'; // TRAD

if (count($_SESSION['errors'])) {
    header("Location: $HTTP_REFERER");
    exit;
}

	$fl = "title='" . clean_input($_SESSION['case_data']['title']) . "',
			id_court_archive='" . clean_input($_SESSION['case_data']['id_court_archive']) . "',
			date_assignment = '" . $_SESSION['case_data']['date_assignment'] . "',
			legal_reason='" . clean_input($_SESSION['case_data']['legal_reason']) . "',
			alledged_crime='" . clean_input($_SESSION['case_data']['alledged_crime']) . "',
			notes = '" . clean_input($_SESSION['case_data']['notes']) . "'";

	// Add status to the list of fields
	$fl .= ",status='" . $_SESSION['case_data']['status'] . "'";

	// Add stage to the list of fields
	$fl .= ",stage='" . $_SESSION['case_data']['stage'] . "'";

	// Put public access rights settings in a separate string
	$public_access_rights = '';

	/* 
	 * [ML] Important note: the meta 'case_*_always' defines whether the user
	 * has the choice of whether read/write should be allowed or not. If not,
	 * we take the system default value in 'case_default_*'.
	 */

	if ((read_meta('case_read_always') == 'yes') && $author_session['status'] != 'admin') {
		// impose system setting
		$public_access_rights .= "public=" . (int)(read_meta('case_default_read') == 'yes');
	} else {
		// write user selection
		$public_access_rights .= "public=" . (int)($_SESSION['case_data']['public'] == 'yes');
	}

	lcm_log("status == " . $author_session['status']);

	if ((read_meta('case_write_always') == 'yes') && $author_session['status'] != 'admin') {
		// impose system setting
		$public_access_rights .= ", pub_write=" . (int)(read_meta('case_default_write') == 'yes');
	} else {
		// write user selection
		$public_access_rights .= ", pub_write=" . (int)($_SESSION['case_data']['pub_write'] == 'yes');
	}

	if ($id_case > 0) {
		// This is modification of existing case

		// Check access rights
		if (!allowed($id_case,'e'))
			lcm_panic("You don't have permission to change this case's information!");

		// If admin access is allowed, set all fields
		if (allowed($id_case,'a'))
			$q = "UPDATE lcm_case SET $fl,$public_access_rights WHERE id_case=$id_case";
		else
			$q = "UPDATE lcm_case SET $fl WHERE id_case=$id_case";

		$result = lcm_query($q);
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
				ac_edit=" . (int)(read_meta('case_allow_modif') == 'yes') . ",
				ac_admin=1";
		// [AG] The user creating case should always have 'admin' access right, otherwise only admin could add new user(s) to the case
		$result = lcm_query($q);

		// Get author information
		$q = "SELECT *
				FROM lcm_author
				WHERE id_author=$id_author";
		$result = lcm_query($q);
		$author_data = lcm_fetch_array($result);

		// Add 'assignment' followup to the case
		$q = "INSERT INTO lcm_followup
				SET id_followup = 0, id_case = $id_case, id_author = $id_author, type = 'assignment',
					date_start = NOW(), date_end = NOW(), description='" . $id_author . "'";

		$result = lcm_query($q);

		// Set case date_assigned to NOW()
		$q = "UPDATE lcm_case
				SET date_assignment = NOW()
				WHERE id_case = $id_case";

		$result = lcm_query($q);
	}

	// Keywords
	include_lcm('inc_keywords');
	update_keywords_request('case', $id_case);

	$stage = get_kw_from_name('stage', $_SESSION['case_data']['stage']);
	$id_stage = $stage['id_keyword'];
	update_keywords_request('stage', $id_case, $id_stage);

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

	// Send to add_org if any org to attach
	if ($_SESSION['case_data']['attach_org']) {
		header("Location: add_org.php?case=$id_case"
			. "&orgs[]=" .  $_SESSION['case_data']['attach_org'] 
			. "&ref_sel_client=" . rawurlencode($send_to));
		exit;
	}

	header("Location: " . $send_to);

?>
