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

	$Id: upd_client.php,v 1.5 2004/11/23 13:29:00 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

session_start();

// Register $errors array - just in case
if (!session_is_registered("errors"))
    session_register("errors");

// Clear all previous errors
$errors=array();

// Register form data in the session
if(!session_is_registered("client_data"))
    session_register("client_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $client_data[$key] = $value;

// Check submitted information
if (strtotime($client_data['date_creation']) < 0) { $errors['date_creation'] = 'Invalid creation date!'; }
//if (strtotime($client_data['date_update']) < 0) { $errors['date_update'] = 'Invalid update date!'; }

// Add timestamp
$client_data['date_update'] = date('Y-m-d H:i:s'); // now

if (count($errors)) {
    header("Location: $HTTP_REFERER");
    exit;
} else {
	$cl = "name_first='" . clean_input($client_data['name_first']) . "',
		name_middle='" . clean_input($client_data['name_middle']) . "',
		name_last='" . clean_input($client_data['name_last']) . "',
		gender='" . clean_input($client_data['gender']) . "',
		citizen_number='" . clean_input($client_data['citizen_number']) . "',
		address='" . clean_input($client_data['address']) . "',
		civil_status='" . clean_input($client_data['civil_status']) . "',
		income='" . clean_input($client_data['income']) . "'";

    if ($id_client>0) {
		// Prepare query
		$q = "UPDATE lcm_client SET date_update=NOW(),$cl WHERE id_client=$id_client";
    } else {
		$q = "INSERT INTO lcm_client SET id_client=0,date_creation=NOW(),date_update=NOW(),$cl";
    }

    if (!($result = lcm_query($q)))
		die("$q<br>\nError ".lcm_errno().": ".lcm_error());

    // Clear the session
    session_destroy();

    // Send user back to add/edit page's referer
    header('Location: ' . $client_data['ref_edit_client']);
}

?>
