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
if(!session_is_registered("filter_data"))
    session_register("filter_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $filter_data[$key]=$value;

// Clean input values
$filter_data['id_filter'] = intval($filter_data['id_filter']);
$filter_data['id_author'] = intval($filter_data['id_author']);

// Check filter data for validity
if (!$filter_data['title']) $errors['title'] = _T('error_no_filter_name');

if (count($errors)) {
    header("Location: " . $GLOBALS['HTTP_REFERER']);
    exit;
} else {
	$fl = "title='" . clean_input($filter_data['title']) . "',type='" . clean_input($filter_data['type']) . "',id_author=" . $filter_data['id_author'] . ",date_update=NOW()";

// Put public access rights settings in a separate string
//	$public_access_rights = '';
//	if ($filter_data['public'] || read_meta('case_read_always'))
//		$public_access_rights .= "public=1";
//	else
//		$public_access_rights .= "public=0";

//	if ($filter_data['pub_write'] || read_meta('case_write_always'))
//		$public_access_rights .= ", pub_write=1";
//	else
//		$public_access_rights .= ", pub_write=0";

	if ($id_filter > 0) {
		// Check access rights
		//if (!allowed($id_filter,'e')) die("You don't have permission to change this case's information!");
		// If admin access is allowed, set all fields
		if (true) $q = "UPDATE lcm_filter SET $fl WHERE id_filter=$id_filter";
		else $q = "UPDATE lcm_filter SET $fl WHERE id_filter=$id_filter";
	} else {
		$q = "INSERT INTO lcm_filter SET id_filter=0,date_creation=NOW(),$fl";
		$result = lcm_query($q);
		$id_filter = lcm_insert_id();

		// Insert new case_author relation
		//$q = "INSERT INTO lcm_case_author SET
		//		id_case=$id_case,
		//		id_author=$id_author,
		//		ac_read=1,
		//		ac_write=1,
		//		ac_admin=1";
	}

	// Some advanced ideas for future use
	//$q="INSERT INTO lcm_case SET id_case=$id_case,$fl ON DUPLICATE KEY UPDATE $fl";
	//$q="INSERT INTO lcm_case $cl VALUES $vl ON DUPLICATE KEY UPDATE $fl";

	$result = lcm_query($q);

    // Clear the session
    session_destroy();

	//header("Location: filter_det.php?filter=$id_filter");

	// Proceed accoring to the button type
	switch ($submit) {
		case 'addnew':
			header("Location: edit_filter.php?filter=0&ref=" . $filter_data['ref_edit_filter']);
			break;
		case 'adddet':
			header("Location: case_filter.php?filter=$id_filter");
			break;
		default :
			header("Location: " . $filter_data['ref_edit_filter']);
	}
}
?>
