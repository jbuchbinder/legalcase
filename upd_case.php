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

	$Id: upd_case.php,v 1.54 2006/03/02 22:32:57 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

global $author_session;

// Clear all previous errors
$_SESSION['errors'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value)
    $_SESSION['form_data'][$key] = $value;


//
// Clean (most of the) input
//

$id_case = _request('id_case', 0);

//
// Create client, if requested
//

if ($_REQUEST['add_client']) {
	include_lcm('inc_obj_client');

	$client = new LcmClient();
	$errs = $client->save();

	if (count($errs)) {
		$_SESSION['errors'] = array_merge($_SESSION['errors'], $errs);
	} else {
		$_SESSION['form_data']['attach_client'] = $client->getDataInt('id_client', '__ASSERT__');
	}
}

$_SESSION['form_data']['title'] = clean_input(_session('title'));
$_SESSION['form_data']['legal_reason'] = clean_input(_session('legal_reason'));
$_SESSION['form_data']['alledged_crime'] = clean_input(_session('alledged_crime'));
$_SESSION['form_data']['case_notes'] = clean_input(_session('case_notes'));
$_SESSION['form_data']['status'] = clean_input(_session('status'));
$_SESSION['form_data']['stage'] = clean_input(_session('stage'));

//
// Check case data for validity
//

// * Title must be non-empty
if (!$_SESSION['form_data']['title'])
	$_SESSION['errors']['title'] = _Ti('case_input_title') . _T('warning_field_mandatory');

// * Date assignment must be a vaid date
$_SESSION['form_data']['date_assignment'] = get_datetime_from_array($_SESSION['form_data'], 'assignment', 'start', date('Y-m-d H:i:s'));

if (! checkdate_sql($_SESSION['form_data']['date_assignment']))
	$_SESSION['errors']['date_assignment'] = _Ti('case_input_date_assigned') . 'Invalid date.'; // TRAD

// * TODO: Status must be a valid option (where do we have official list?)
if (! $_SESSION['form_data']['status'])
	$_SESSION['errors']['status'] = _Ti('case_input_status') . _T('warning_field_mandatory');

// * TODO: Stage must be a valid keyword
if (! $_SESSION['form_data']['stage'])
	$_SESSION['errors']['stage'] = _Ti('case_input_stage') . _T('warning_field_mandatory');

validate_update_keywords_request('case', $id_case);

if (count($_SESSION['errors'])) {
	header("Location: ". $_SERVER['HTTP_REFERER']);
    exit;
}

//
// Create the case in the database
//

$fl = "title='"              . $_SESSION['form_data']['title']            . "',
		date_assignment = '" . $_SESSION['form_data']['date_assignment']  . "',
		legal_reason='"      . $_SESSION['form_data']['legal_reason']     . "',
		alledged_crime='"    . $_SESSION['form_data']['alledged_crime']   . "',
		notes = '"           . $_SESSION['form_data']['case_notes']       . "',
	    status='"            . $_SESSION['form_data']['status']           . "',
	    stage='"             . $_SESSION['form_data']['stage']            . "'";

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
		$public_access_rights .= "public=" . (int)($_SESSION['form_data']['public'] == 'yes');
	}

	if ((read_meta('case_write_always') == 'yes') && $author_session['status'] != 'admin') {
		// impose system setting
		$public_access_rights .= ", pub_write=" . (int)(read_meta('case_default_write') == 'yes');
	} else {
		// write user selection
		$public_access_rights .= ", pub_write=" . (int)($_SESSION['form_data']['pub_write'] == 'yes');
	}

	if (isset($_REQUEST['id_case']))
		$id_case = intval($_REQUEST['id_case']);
	else
		$id_case = 0;

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

		// Update lcm_stage entry for case creation (of first stage!)
		// [ML] This doesn't make so much sense, but better than nothing imho..
		$q = "SELECT min(id_entry) as id_entry FROM lcm_stage WHERE id_case = $id_case";
		$tmp_result = lcm_query($q);
	
		if (($tmp_row = lcm_fetch_array($tmp_result))) {
			$q = "UPDATE lcm_stage
					SET date_creation = '" . $_SESSION['form_data']['date_assignment'] . "'
					WHERE id_entry = " . $tmp_row['id_entry'];

			lcm_query($q);
		}
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
				SET id_followup = 0,
					id_case = $id_case, 
					id_author = $id_author,
					type = 'assignment',
					case_stage = '" . $_SESSION['form_data']['stage'] . "',
					date_start = NOW(),
					date_end = NOW(),
					description='" . $id_author . "'";

		lcm_query($q);
		$id_followup = lcm_insert_id();

		// Add lcm_stage entry
		$q = "INSERT INTO lcm_stage SET
				id_case = $id_case,
				kw_case_stage = '" . $_SESSION['form_data']['stage'] . "',
				date_creation = '" . $_SESSION['form_data']['date_assignment'] . "',
				id_fu_creation = $id_followup";

		lcm_query($q);
	}

	// Keywords
	update_keywords_request('case', $id_case);

	$stage = get_kw_from_name('stage', $_SESSION['form_data']['stage']);
	$id_stage = $stage['id_keyword'];
	update_keywords_request('stage', $id_case, $id_stage);

	// [ML] I don't understand why: header("Location: $ref_edit_case");
	// [AG] Because "edit_case" could be invoked from diferent places i.e. edit existing case or add new or other.
	// [AG] User could come to edit from listcases.php or case_det.php. Also, other references could be added later.
	// [AG] In each case the return page will be different.

	//header("Location: case_det.php?case=$id_case");
	$ref_edit_case = ($_SESSION['form_data']['ref_edit_case'] ? $_SESSION['form_data']['ref_edit_case'] : "case_det.php?case=$id_case");
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
	if ($_SESSION['form_data']['attach_client']) {
		header("Location: add_client.php?case=$id_case"
			. "&clients[]=" .  $_SESSION['form_data']['attach_client'] 
			. "&ref_sel_client=" . rawurlencode($send_to));
		exit;
	}

	// Send to add_org if any org to attach
	if ($_SESSION['form_data']['attach_org']) {
		header("Location: add_org.php?case=$id_case"
			. "&orgs[]=" .  $_SESSION['form_data']['attach_org'] 
			. "&ref_sel_client=" . rawurlencode($send_to));
		exit;
	}

	header("Location: " . $send_to);

?>
