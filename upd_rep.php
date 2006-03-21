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

	$Id: upd_rep.php,v 1.11 2006/03/21 16:18:56 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Clear all previous errors
$_SESSION['errors'] = array();

// Register form data in the session
if(!session_is_registered("rep_data"))
    session_register("rep_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $rep_data[$key]=$value;

// Clean input values
$rep_data['id_report'] = intval($rep_data['id_report']);
$rep_data['id_author'] = intval($rep_data['id_author']);

// Check report data for validity
if (!$rep_data['title']) 
	$_SESSION['errors']['title'] = _Ti('rep_input_title') . _T('warning_field_mandatory');

if (count($_SESSION['errors'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

//
// Proceed with update of report info
//

$fl = "title = '" . clean_input($rep_data['title']) . "', "
	. "id_author = " . $rep_data['id_author'] . ", "
	. "description = '" . clean_input($rep_data['description']) . "', "
	. "notes = '" . clean_input($rep_data['notes']) . "', "
	. "date_update = NOW()";

// Put public access rights settings in a separate string
//	$public_access_rights = '';
//	if ($rep_data['public'] || read_meta('case_read_always'))
//		$public_access_rights .= "public=1";
//	else
//		$public_access_rights .= "public=0";

//	if ($rep_data['pub_write'] || read_meta('case_write_always'))
//		$public_access_rights .= ", pub_write=1";
//	else
//		$public_access_rights .= ", pub_write=0";


if ($rep_data['id_report'] > 0) {
	// Check access rights
	// if (!allowed($id_report,'e')) die("You don't have permission to change this case's information!");
	// If admin access is allowed, set all fields

	if (true)
		$q = "UPDATE lcm_report SET $fl WHERE id_report = " . $rep_data['id_report'];
	else 
		$q = "UPDATE lcm_report SET $fl WHERE id_report = " . $rep_data['id_report'];
	
	lcm_query($q);
} else {
	$q = "INSERT INTO lcm_report SET date_creation=NOW(),$fl";
	$result = lcm_query($q);
	$rep_data['id_report'] = lcm_insert_id('lcm_report', 'id_report');

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
// $result = lcm_query($q);

//header("Location: rep_det.php?rep=$id_report");
$ref_edit_rep = ($rep_data['ref_edit_rep'] ? $rep_data['ref_edit_rep'] : "rep_det.php?rep=" . $rep_data['id_report']);

// Proceed according to the button type
switch ($submit) {
	case 'addnew':
		header("Location: edit_rep.php?rep=0&ref=$ref_edit_rep");
		break;
	case 'adddet':
		header("Location: rep_det.php?rep=" . $rep_data['id_report']);
		break;
	default:
		// [ML] header("Location: $ref_edit_rep");
		header("Location: rep_det.php?rep=" . $rep_data['id_report']);
}

?>
