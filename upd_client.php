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

	$Id: upd_client.php,v 1.7 2005/01/18 15:05:46 mlutfy Exp $
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
	$_SESSION['errors']['name_first'] = _T('person_input_name_first') . ' ' . 'Mandatory field.';

if (! $_SESSION['client']['name_last'])
	$_SESSION['errors']['name_last'] = _T('person_input_name_last') . ' ' . 'Mandatory field.';

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

	lcm_query($q);
	$_SESSION['client']['id_client'] = lcm_insert_id();
}

// Go to the 'view details' page of the author
header('Location: client_det.php?client=' . $_SESSION['client']['id_client']);

?>
