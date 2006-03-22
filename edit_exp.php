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

	$Id: edit_exp.php,v 1.1 2006/03/22 23:27:22 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_obj_exp');

// Don't clear form data if comming back from upd_exp with errors
if (! isset($_SESSION['form_data']))
	$_SESSION['form_data'] = array();

// Set the returning page, usually, there should not be, therefore
// it will send back to "fu_det.php?followup=NNN" after update.
$_SESSION['form_data']['ref_edit_fu'] = _request('ref');

//
// Check for access rights
//

// TODO
$edit  = 1;
$write = 1;

if (!($admin || $write))
	lcm_panic("You don't have permission to add follow-ups to this case");

//
// Start page
//

if (_request('comment')) {
	if (! _request('expense'))
		lcm_panic("Missing expense ID");
	
	lcm_page_start(_T('title_expense_comment'), '', '', 'expenses');
} else {
	if (_request('expense'))
		lcm_page_start(_T('title_expense_edit'), '', '', 'expenses');
	else 
		lcm_page_start(_T('title_expense_new'), '', '', 'expenses');
}

/* TODO
show_context_start();
show_context_case_title($case, 'followups');
show_context_case_involving($case);
*/

show_context_end();

// Show the errors (if any)
echo show_all_errors();

echo '<form action="upd_exp.php" method="post">' . "\n";

if (_request('comment')) {
	$obj_exp = new LcmExpenseInfoUI(_request('expense', 0));
	$obj_exp->printGeneral();

	show_page_subtitle(_T('expenses_subtitle_comment'), 'expenses_comment');

	echo "<p>and add comment box here..</p>"; // TODO

} else {
	$obj_exp = new LcmExpenseInfoUI(_request('expense', 0));
	$obj_exp->printEdit();
}

echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";

echo '<input type="hidden" name="id_followup" value="' . _session('id_followup', 0) . '">
	<input type="hidden" name="id_case" value="' . _session('id_case') . '">
	<input type="hidden" name="ref_edit_fu" value="' . _session('ref_url') . '">' . "\n";

echo "</form>\n";

lcm_page_end();

// Clear the errors, in case user jumps to other 'edit' page
$_SESSION['errors'] = array();
$_SESSION['form_data'] = array();

?>
