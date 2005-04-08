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

	$Id: set_case_stage.php,v 1.2 2005/04/08 05:59:41 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Get input values
$case = intval($_GET['case']);
$stage = clean_input($_GET['stage']);

// Check if case_id is set
if (!($case>0)) die("Which case?");

// Check access rights
if ( !(($GLOBALS['author_session']['status'] == 'admin') || allowed($case,'we')) )
	die("You don't have rights to set this case status!");

// Get site preferences
//$fu_sum_billed = read_meta('fu_sum_billed');

// Check if there are no errors - new followup
if (! count($_SESSION['errors'])) {
	// Clear followup data
	$_SESSION['fu_data'] = array();
}

// Get case details
$q = "SELECT * FROM lcm_case WHERE id_case=$case";
$result = lcm_query($q);

// Check if the case exists
if (!($row=lcm_fetch_array($result)))
	die("There is no such case.");

// Check if the stage is changed
if ($row['stage'] == $stage) {
	header('Location: ' . $GLOBALS['HTTP_REFERER']);
	exit;
}

// Start the page
lcm_page_start('Changing case stage: ' . $row['title']); // TRAD

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

// Write form
echo "<form action='upd_fu.php' method='post'>
	<table class='tbl_usr_dtl' width='99%'>
		<tr><td>" . _Ti('time_input_date_start') . "</td>
			<td>";

$date_start = date('Y-m-d H:i:s');
echo get_date_inputs('start', $date_start, false);
echo ' ' . _T('time_input_time_at') . ' ';
echo get_time_inputs('start', $date_start);
echo ' ' . f_err_star('date_start',$errors);

echo "</td>\n";
echo "</tr><tr>\n";
echo "<td>" . "Type:" . /* TRAD */ "</td>
			<td><input type='hidden' name='type' value='stage_change'>" . _T('kw_followups_stage_change_title') . "</td>
		</tr>
		<tr><td valign='top'>" . f_err_star('description') . "Description:" . /* TRAD */ "</td>
			<td><textarea name='description' rows='15' cols='40' class='frm_tarea'>" . $_SESSION['fu_data']['description'] . "</textarea></td>
		</tr>\n";
echo "\t</table>

	<p><button name='submit' type='submit' value='submit' class='simple_form_btn'>" . _T('button_validate') . "</button></p>

	<input type='hidden' name='id_case' value='$case'>
	<input type='hidden' name='new_stage' value='$stage'>
	<input type='hidden' name='ref_edit_fu' value='" . $GLOBALS['HTTP_REFERER'] . "'>
</form>\n";

lcm_page_end();

?>
