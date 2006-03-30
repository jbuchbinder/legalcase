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

	$Id: exp_det.php,v 1.3 2006/03/30 01:06:44 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_obj_exp');

$expense = intval(_request('expense'));

if (! ($expense > 0))
	die("Missing id expense.");

lcm_page_start(_T('title_expense_view') . ' ' . _request('expense'), '', '', 'expenses_intro');

		/* Saved for future use
			// Check for access rights
			if (!($row['public'] || allowed($client,'r'))) {
				die("You don't have permission to view this client details!");
			}
			$edit = allowed($client,'w');
		*/

//
// Show general information
//
echo '<fieldset class="info_box">';

$obj_expense = new LcmExpenseInfoUI($expense);
$obj_expense->printGeneral();

// if ($edit)
	echo '<p><a href="edit_exp.php?expense=' . $expense . '" class="edit_lnk">' . _T('expense_button_edit') . '</a></p>' . "\n";

$obj_expense->printComments();

// if ($edit)
	echo '<p><a href="edit_exp.php?edit_comment=1&expense=' . $expense . '" class="edit_lnk">' . _T('expense_button_comment') . '</a></p>' . "\n";

echo "</fieldset>\n";

// Clear session info
$_SESSION['form_data'] = array();
$_SESSION['errors'] = array();

lcm_page_end();
?>
