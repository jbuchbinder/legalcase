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
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Start session
session_start();

// Register $errors array - just in case
if (!session_is_registered("errors"))
    session_register("errors");

// Clear all previous errors
$errors=array();

// Register form data in the session
if(!session_is_registered("case_data"))
    session_register("case_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $case_data[$key]=$value;

// Check case data for validity
if (!$case_data['title']) $errors['title'] = _T('error_no_case_name');

if (count($errors)) {
    header("Location: $HTTP_REFERER");
    exit;
} else {
	//$cl = '(id_case,title,id_court_archive,date_creation,date_assignment,legal_reason,alledged_crime,status)';
	//$vl = "($id_case,'$title','$id_court_archive','$date_creation',";
	//$vl .= "'$date_assignment','$legal_reason','$alledged_crime','$status')";
	$fl = "title='" . clean_input($case_data['title']) . "',
			id_court_archive='" . clean_input($case_data['id_court_archive']) . "',";
//			date_creation='" . $case_data['date_creation'] . "',
// [AG] Creation date derived from MySQL server to prevent user manipulation
	$fl .= "
			date_assignment='" . clean_input($case_data['date_assignment']) . "',
			legal_reason='" . clean_input($case_data['legal_reason']) . "',
			alledged_crime='" . clean_input($case_data['alledged_crime']) . "',
			status='" . clean_input($case_data['status']) . "'";

// Put public access rights settings in a separate string
	$public_access_rights = '';
	if ($case_data['public'] || read_meta('case_read_always'))
		$public_access_rights .= "public=1";
	else 
		$public_access_rights .= "public=0";
		
	if ($case_data['pub_write'] || read_meta('case_write_always')) 
		$public_access_rights .= ", pub_write=1";
	else 
		$public_access_rights .= ", pub_write=0";

	if ($id_case > 0) {
		// Check access rights
		if (!allowed($id_case,'e')) die("You don't have permission to change this case's information!");
		// If admin access is allowed, set all fields
		if (allowed($id_case,'a')) $q = "UPDATE lcm_case SET $fl,$public_access_rights WHERE id_case=$id_case";
		else $q = "UPDATE lcm_case SET $fl WHERE id_case=$id_case";
	} else {
		$q = "INSERT INTO lcm_case SET id_case=0,date_creation=NOW(),$fl,$public_access_rights";
		$result = lcm_query($q);
		$id_case = lcm_insert_id();

		// Insert new case_author relation
		$q = "INSERT INTO lcm_case_author SET
				id_case=$id_case,
				id_author=$id_author,
				ac_read=1,
				ac_write=1,
				ac_admin=1";
	}

	// Some advanced ideas for future use
	//$q="INSERT INTO lcm_case SET id_case=$id_case,$fl ON DUPLICATE KEY UPDATE $fl";
	//$q="INSERT INTO lcm_case $cl VALUES $vl ON DUPLICATE KEY UPDATE $fl";

	$result = lcm_query($q);

    // Clear the session
    session_destroy();

	// [ML] I don't understand why: header("Location: $ref_edit_case");
	// [AG] Because "edit_case" could be invoked from diferent places i.e. edit existing case or add new or other.
	// [AG] User could come to edit from listcases.php or case_det.php. Also, other references could be added later.
	// [AG] In each case the return page will be different.

	//header("Location: case_det.php?case=$id_case");

	// Proceed accoring to the button type
	switch ($submit) {
		case 'addnew':
			header("Location: edit_case.php?case=0&ref=" . $case_data['ref_edit_case']);
			break;
		case 'adddet':
			header("Location: case_det.php?case=$id_case");
			break;
		default :
			header("Location: " . $case_data['ref_edit_case']);
	}
}
?>
