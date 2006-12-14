<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2007 Free Software Foundation, Inc.

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

	$Id: upd_rep.php,v 1.13 2006/12/14 19:34:02 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

global $author_session;

// Clear all previous errors
$_SESSION['errors'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value) {
	$_SESSION['form_data'][$key] = _request($key);
    $rep_data[$key] = _request($key);
}

// Clean input values
$_SESSION['form_data']['id_report'] = intval(_session('id_report'));
$_SESSION['form_data']['id_author'] = $author_session['id_author'];

// Check report data for validity
if (! _session('title'))
	$_SESSION['errors']['title'] = _Ti('rep_input_title') . _T('warning_field_mandatory');

if (count($_SESSION['errors'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

//
// Proceed with update of report info
//

$fl = "title = '" . _session('title') . "', "
	. "id_author = " . _session('id_author') . ", "
	. "description = '" . _session('description') . "', "
	. "notes = '" . _session('notes') . "', "
	. "date_update = NOW()";

if (! _session('id_report')) {
	if (_session('filecustom')) {
		if (! preg_match("/^[-_A-Za-z0-9]+$/", _session('filecustom')))
			$_SESSION['errors']['filecustom'] = htmlspecialchars(_session('filecustom')) . ": " . "Report file name has illegal characters"; // TRAD
		elseif (! include_custom_report_exists(_session('filecustom')))
			$_SESSION['errors']['filecustom'] = htmlspecialchars(_session('filecustom')) . ": " . "Report file does not exist"; // TRAD
		else
			$fl .= ", filecustom = '" . _session('filecustom') . "'";
	}
}

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


if (_session('id_report') > 0) {
	// Check access rights
	// if (!allowed($id_report,'e')) die("You don't have permission to change this case's information!");
	// If admin access is allowed, set all fields

	if (true)
		$q = "UPDATE lcm_report SET $fl WHERE id_report = " . _session('id_report');
	else 
		$q = "UPDATE lcm_report SET $fl WHERE id_report = " . _session('id_report');
	
	lcm_query($q);
} else {
	$q = "INSERT INTO lcm_report
			SET date_creation=NOW(),
				line_src_type = '',
				line_src_name = '',
				col_src_type = '',
				col_src_name = '',
				filecustom = '',
				$fl";

	$result = lcm_query($q);
	$_SESSION['form_data']['id_report'] = lcm_insert_id('lcm_report', 'id_report');

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


// Forward to upd_rep_field.php if custom_report
if (_session('filecustom'))
	lcm_header("Location: upd_rep_field.php?" . "rep=" . _session('id_report')
			. "&filecustom=" . _session('filecustom'));
else
	lcm_header("Location: rep_det.php?rep=" . _session('id_report'));

?>
