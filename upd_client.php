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

	$Id: upd_client.php,v 1.12 2005/03/19 00:19:16 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

// Clear all previous errors
$_SESSION['errors'] = array();
$_SESSION['client'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value)
	$_SESSION['client'][$key] = $value;

//
// Validate form data
//

if (! $_SESSION['client']['name_first'])
	$_SESSION['errors']['name_first'] = _T('person_input_name_first') . ' ' . _T('warning_field_mandatory');

if (! $_SESSION['client']['name_last'])
	$_SESSION['errors']['name_last'] = _T('person_input_name_last') . ' ' . _T('warning_field_mandatory');

if (! ($_SESSION['client']['gender'] == 'unknown'
		|| $_SESSION['client']['gender'] == 'female'
		|| $_SESSION['client']['gender'] == 'male'))
	$_SESSION['errors']['name_last'] = _T('person_input_gender') . ' ' . 'Incorrect format.';

if (count($_SESSION['errors'])) {
    header("Location: $HTTP_REFERER");
	exit;
}

$cl = "name_first = '" . clean_input($_SESSION['client']['name_first']) . "',
	name_middle = '" . clean_input($_SESSION['client']['name_middle']) . "',
	name_last = '" . clean_input($_SESSION['client']['name_last']) . "',
	gender = '" . clean_input($_SESSION['client']['gender']) . "',
	citizen_number = '" . clean_input($_SESSION['client']['citizen_number']) . "',
	address = '" . clean_input($_SESSION['client']['address']) . "',
	civil_status = '" . clean_input($_SESSION['client']['civil_status']) . "',
	income = '" . clean_input($_SESSION['client']['income']) . "'";

if ($_SESSION['client']['id_client'] > 0) {
	$q = "UPDATE lcm_client
		SET date_update = NOW(), 
			$cl 
		WHERE id_client = $id_client";
	
	lcm_query($q);
} else {
	$q = "INSERT INTO lcm_client
			SET id_client = 0,
				date_creation = NOW(),
				date_update = NOW(),
				$cl";

	$_SESSION['client']['id_client'] = lcm_insert_id(lcm_query($q));

	//
	// Attach client to case (Case -> Add Client -> Create new client)
	//
	if (isset($_REQUEST['attach_case'])) {
		$attach_case = intval($_REQUEST['attach_case']);

		if ($attach_case > 0) {
			$q = "INSERT INTO lcm_case_client_org (id_case, id_client, id_org)
					VALUES (" . $attach_case . ", " . $_SESSION['client']['id_client'] . ", 0)";

			lcm_query($q);
		}
	}
}

//
// Add organisation
//
if (!empty($_SESSION['client']['new_org'])) {
	$q = "REPLACE INTO lcm_client_org
		VALUES (" . $_SESSION['client']['id_client'] . ',' . $_SESSION['client']['new_org'] . ")";
	$result = lcm_query($q);
}

//
// Insert/update client contacts
//

include_lcm('inc_contacts');

//
// Update existing contacts
//
if (isset($_REQUEST['contact_value'])) {
	$contacts = $_REQUEST['contact_value'];
	$c_ids = $_REQUEST['contact_id'];
	$c_types = $_REQUEST['contact_type'];
	// $c_delete = $_REQUEST['del_contact'];

	//
	// Check if the contacts provided are really attached to the author
	// or else the author can provide a form with false contacts.
	//
	$all_contacts = get_contacts('client', $_SESSION['client']['id_client']);
	for ($cpt = 0; $c_ids[$cpt]; $cpt++) {
		$valid = false;

		foreach ($all_contacts as $c)
			if ($c['id_contact'] == $c_ids[$cpt])
				$valid = true;

		if (! $valid)
			die("Invalid modification of contacts detected.");
	}

	for ($cpt = 0; isset($c_ids[$cpt]); $cpt++) {
		if (isset($_REQUEST['del_contact_' . $c_ids[$cpt]]) && $_REQUEST['del_contact_' . $c_ids[$cpt]]) {
			delete_contact($c_ids[$cpt]);
		} else {
			// Check for doubles, etc. -> the hell with it! [ML] 2005-01-18
			update_contact($c_ids[$cpt], $contacts[$cpt]);
		}
	}
}

//
// New contacts
//
if (isset($_REQUEST['new_contact_value'])) {
	$cpt = 0;
	$new_contacts = $_REQUEST['new_contact_value'];
	$c_type_names = $_REQUEST['new_contact_type_name'];

	while (isset($new_contacts[$cpt])) {
		// Process only new contacts which have a value
		if ($new_contacts[$cpt]) {
			// And make sure that they have a "type of contact"
			if ($c_type_names[$cpt]) {
				add_contact('client', $_SESSION['client']['id_client'], $c_type_names[$cpt], $new_contacts[$cpt]);
			} else {
				$_SESSION['errors']['new_contact_' . $cpt] = "Please specify the type of contact."; // TRAD
				$_SESSION['client']['new_contact_' . $cpt] = $new_contacts[$cpt];
			}
		}

		$cpt++;
	}
}


// Go to the 'view details' page of the author
header('Location: client_det.php?client=' . $_SESSION['client']['id_client']);

?>
