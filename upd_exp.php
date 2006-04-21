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

	$Id: upd_exp.php,v 1.4 2006/04/21 16:01:26 mlutfy Exp $
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

$id_expense = _request('id_expense', 0);
$id_comment = _request('id_comment', 0);
$edit_comment = _request('edit_comment', 0);

$ref_url = "edit_exp.php?expense=$id_expense&edit_comment=$edit_comment&c=$id_comment";

if ($_SERVER['HTTP_REFERER'])
	$ref_url = $_SERVER['HTTP_REFERER'];

//
// Update data
//
if ($id_comment || $edit_comment) {
	$obj = new LcmExpenseComment($id_expense, $id_comment);
	$errs = $obj->save($true);

	if (! count($errs) && _request('new_exp_status')) {
		$obj = new LcmExpense($id_expense);
		$errs = $obj->setStatus(_request('new_exp_status'));
	}
} else {
	$obj = new LcmExpense($id_expense);
	$errs = $obj->save();
}


if (count($errs)) {
	$_SESSION['errors'] = array_merge($_SESSION['errors'], $errs);
	lcm_header("Location: " . $ref_url);
	exit;
}

//
// Go to the 'view details' page
//

lcm_header('Location: exp_det.php?expense=' . $obj->getDataInt('id_expense', '__ASSERT__'));

?>
