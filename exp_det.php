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

	$Id: exp_det.php,v 1.4 2006/04/04 23:33:49 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_obj_exp');

$expense = intval(_request('expense'));

if (! ($expense > 0))
	die("Missing id expense.");

lcm_page_start(_T('title_expense_view') . ' ' . _request('expense'), '', '', 'expenses_intro');

//
// Show general information
//
echo '<fieldset class="info_box">';

$obj_expense = new LcmExpenseInfoUI($expense);
$obj_expense->printGeneral();

$obj_exp_ac = new LcmExpenseAccess(0, 0, $obj_expense);
$obj_expense->printComments();

echo "</fieldset>\n";

// Clear session info
$_SESSION['form_data'] = array();
$_SESSION['errors'] = array();

lcm_page_end();

?>
