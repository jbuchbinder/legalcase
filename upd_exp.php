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

	$Id: upd_exp.php,v 1.1 2006/03/22 23:27:22 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_obj_exp');

// Clear all previous errors
$_SESSION['errors'] = array();
$_SESSION['form_data'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value)
	$_SESSION['form_data'][$key] = $value;

$ref_url = 'edit_exp.php?expense=' . _session('id_expense', 0);
if ($_SERVER['HTTP_REFERER'])
	$ref_url = $_SERVER['HTTP_REFERER'];

//
// Update data
//

$expense = new LcmExpense(_session('id_expense', 0));
$errs = $expense->save();

if (count($errs)) {
	$_SESSION['errors'] = array_merge($_SESSION['errors'], $errs);
	lcm_header("Location: " . $ref_url);
	exit;
}

//
// Go to the 'view details' page
//

lcm_header('Location: exp_det.php?expense=' . $expense->getDataInt('id_expense', '__ASSERT__'));

?>
