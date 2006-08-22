<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

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

	$Id: upd_org.php,v 1.15 2006/08/22 12:37:48 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_obj_org');

// Clear all previous errors
$_SESSION['errors'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value)
	$_SESSION['form_data'][$key] = $value;

$_SESSION['form_data']['id_org'] = intval(_session('id_org', 0));

$ref_upd_org = 'edit_org.php?org=' . _session('id_org');
if ($GLOBALS['HTTP_REFERER'])
	$ref_upd_org = $_SERVER['HTTP_REFERER'];

$obj_org = new LcmOrg(_session('id_org'));
$errs = $obj_org->save();

if (count($errs)) {
	$_SESSION['errors'] = array_merge($_SESSION['errors'], $errs);
	lcm_header("Location: " . $ref_upd_org);
	exit;
}

if (count($_SESSION['errors'])) {
	header('Location: ' . $ref_upd_org);
	exit;
}

// Go to the 'view details' page of the organisation
lcm_header('Location: org_det.php?org=' . _session('id_org'));

?>
