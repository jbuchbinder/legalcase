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

	$Id: upd_client.php,v 1.20 2006/03/17 18:03:12 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_obj_client');

// Clear all previous errors
$_SESSION['errors'] = array();
$_SESSION['form_data'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value)
	$_SESSION['form_data'][$key] = $value;

$ref_upd_client = 'edit_client.php?client=' . _session('id_client');
if ($_SERVER['HTTP_REFERER'])
	$ref_upd_client = $_SERVER['HTTP_REFERER'];

//
// Update data
//

$client = new LcmClient(_session('id_client'));
$errs = $client->save();

if (count($errs)) {
	$_SESSION['errors'] = array_merge($_SESSION['errors'], $errs);
	lcm_header("Location: " . $ref_upd_client);
	exit;
}

//
// Add organisation
//
if (_session('new_org')) {
	$q = "REPLACE INTO lcm_client_org
		VALUES (" . _session('id_client') . ',' . _session('new_org') . ")";
	$result = lcm_query($q);
}

//
// Go to the 'view details' page of the author
//

// small reminder, if the client was created from the "add client to case" (Case details)
$attach = "";
if (isset($_SESSION['form_data']['attach_case']))
	$attach = "&attach_case=" . $_SESSION['form_data']['attach_case'];

lcm_header('Location: client_det.php?client=' . $client->getDataInt('id_client', '__ASSERT__') . $attach);

?>
