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

	$Id: upd_case.php,v 1.56 2006/03/06 23:31:37 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_obj_case');

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

if (_request('add_client')) {
	include_lcm('inc_obj_client');

	$client = new LcmClient();
	$errs = $client->save();

	if (count($errs)) {
		$_SESSION['errors'] = array_merge($_SESSION['errors'], $errs);
	} else {
		$_SESSION['form_data']['attach_client'] = $client->getDataInt('id_client', '__ASSERT__');
	}
}

//
// Create or update case data
//

$case = new LcmCase($id_case);
$errs = $case->save();

if (count($errs)) {
	$_SESSION['errors'] = array_merge($_SESSION['errors'], $errs);
	header("Location: ". $_SERVER['HTTP_REFERER']);
    exit;
}


//
// Create follow-up data
//

if (_request('add_fu')) {
	include_lcm('inc_obj_fu');

	$fu = new LcmFollowup(0, $case->getDataInt('id_case'));
	$errs = $fu->save();

	if (count($errs)) {
		$_SESSION['errors'] = array_merge($_SESSION['errors'], $errs);
		header("Location: ". $_SERVER['HTTP_REFERER']);
		exit;
	}
}

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
