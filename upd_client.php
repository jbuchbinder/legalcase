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

	$Id: upd_client.php,v 1.18 2006/02/20 03:24:27 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

// Clear all previous errors
$_SESSION['errors'] = array();
$_SESSION['form_data'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value)
	$_SESSION['form_data'][$key] = $value;

$_SESSION['form_data']['id_client'] = intval($_SESSION['form_data']['id_client']);

$ref_upd_client = 'edit_client.php?client=' . $_SESSION['form_data']['id_client'];
if ($_SERVER['HTTP_REFERER'])
	$ref_upd_client = $_SERVER['HTTP_REFERER'];

//
// Validate form data
//

if (! $_SESSION['form_data']['name_first'])
	$_SESSION['errors']['name_first'] = _Ti('person_input_name_first') . _T('warning_field_mandatory');

if (! $_SESSION['form_data']['name_last'])
	$_SESSION['errors']['name_last'] = _Ti('person_input_name_last') . _T('warning_field_mandatory');

if (! ($_SESSION['form_data']['gender'] == 'unknown'
		|| $_SESSION['form_data']['gender'] == 'female'
		|| $_SESSION['form_data']['gender'] == 'male'))
	$_SESSION['errors']['name_last'] = _Ti('person_input_gender') . 'Incorrect format.'; // TRAD

if (count($_SESSION['errors'])) {
    header("Location: " . $ref_upd_client);
	exit;
}

$cl = "name_first = '" . clean_input($_SESSION['form_data']['name_first']) . "',
	name_middle = '" . clean_input($_SESSION['form_data']['name_middle']) . "',
	name_last = '" . clean_input($_SESSION['form_data']['name_last']) . "',
	gender = '" . clean_input($_SESSION['form_data']['gender']) . "',
	notes = '" . clean_input($_SESSION['form_data']['notes']) . "'"; // , 

if (clean_input($_SESSION['form_data']['citizen_number']))
	$cl .= ", citizen_number = '" . clean_input($_SESSION['form_data']['citizen_number']) . "'";
	
if (clean_input($_SESSION['form_data']['civil_status']))
	$cl .= ", civil_status = '" . clean_input($_SESSION['form_data']['civil_status']) . "'";

if (clean_input($_SESSION['form_data']['income']))
	$cl .= ", income = '" . clean_input($_SESSION['form_data']['income']) . "'";

if ($_SESSION['form_data']['id_client'] > 0) {
	$q = "UPDATE lcm_client
		SET date_update = NOW(), 
			$cl 
		WHERE id_client = " . $_SESSION['form_data']['id_client'];
	
	lcm_query($q);
} else {
	$q = "INSERT INTO lcm_client
			SET id_client = 0,
				date_creation = NOW(),
				date_update = NOW(),
				$cl";

	$result = lcm_query($q);
	$_SESSION['form_data']['id_client'] = lcm_insert_id($result);

	// If there is an error (ex: in contacts), we should send back to 'client_det.php?client=XX'
	// not to 'client_det.php?client=0'.
	$ref_upd_client = 'edit_client.php?client=' . $_SESSION['form_data']['id_client'];

	//
	// Attach client to case (Case -> Add Client -> Create new client)
	//
	if (isset($_REQUEST['attach_case'])) {
		$attach_case = intval($_REQUEST['attach_case']);

		if ($attach_case > 0) {
			$q = "INSERT INTO lcm_case_client_org (id_case, id_client, id_org)
					VALUES (" . $attach_case . ", " . $_SESSION['form_data']['id_client'] . ", 0)";

			lcm_query($q);
		}
	}
}

//
// Add organisation
//
if (!empty($_SESSION['form_data']['new_org'])) {
	$q = "REPLACE INTO lcm_client_org
		VALUES (" . $_SESSION['form_data']['id_client'] . ',' . $_SESSION['form_data']['new_org'] . ")";
	$result = lcm_query($q);
}

// Keywords
update_keywords_request('client', $_SESSION['form_data']['id_client']);

//
// Insert/update client contacts
//

include_lcm('inc_contacts');
update_contacts_request('client', $_SESSION['form_data']['id_client']);

if (count($_SESSION['errors'])) {
	header('Location: ' . $ref_upd_client);
	exit;
}

//
// Go to the 'view details' page of the author
//

// small reminder, if the client was created from the "add client to case" (Case details)
$attach = "";
if (isset($_SESSION['form_data']['attach_case']))
	$attach = "&attach_case=" . $_SESSION['form_data']['attach_case'];

header('Location: client_det.php?client=' . $_SESSION['form_data']['id_client'] . $attach);

?>
