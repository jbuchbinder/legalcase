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

	$Id: upd_org.php,v 1.8 2005/03/24 12:03:23 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

// Clear all previous errors
$_SESSION['errors'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value)
	$_SESSION['org_data'][$key] = $value;

$_SESSION['org_data']['id_org'] = intval($_SESSION['org_data']['id_org']);

$ref_upd_org = 'edit_org.php?org=' . $_SESSION['org_data']['id_org'];
if ($GLOBALS['HTTP_REFERER'])
	$ref_upd_org = $GLOBALS['HTTP_REFERER'];

// Check submitted information
if (! $_SESSION['org_data']['name'])
	$_SESSION['errors']['name'] = _Ti('org_input_name') . _T('warning_field_mandatory'); 

if (count($_SESSION['errors'])) {
	// Return to edit page
	header("Location: " . $ref_upd_org);
	exit;
}


	// Record data in database
	$ol="name='" . clean_input($_SESSION['org_data']['name']) . "'," .
		"address='" . clean_input($_SESSION['org_data']['address']) . "'";

	if ($_SESSION['data_org']['id_org'] > 0) {
		$q = "UPDATE lcm_org SET date_update=NOW(),$ol WHERE id_org = " . $_SESSION['data_org']['id_org'];
		$result = lcm_query($q);
	} else {
		$q = "INSERT INTO lcm_org SET id_org=0,date_update=NOW(),$ol";
		$result = lcm_query($q);
		$_SESSION['data_org']['id_org'] = lcm_insert_id($result);

		// If there is an error (ex: in contacts), we should send back to 'org_det.php?org=XX'
		// not to 'org_det.php?org=0'.
		$ref_upd_org = 'edit_org.php?org=' . $_SESSION['org_data']['id_org'];
	}


//
// Contacts
//

include_lcm('inc_contacts');
update_contacts_request('org', $_SESSION['org_data']['id_org']);

if (count($_SESSION['errors'])) {
	header('Location: ' . $ref_upd_org);
	exit;
}

// Go to the 'view details' page of the organisation
header('Location: org_det.php?org=' . $_SESSION['data_org']['id_org']);

?>
