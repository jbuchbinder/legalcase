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

	$Id: set_case_status.php,v 1.3 2004/12/17 18:25:00 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Get input values
$case = intval($_GET['case']);
$status = clean_input($_GET['status']);

if (!($case>0)) die("Which case?");

if ( !(($GLOBALS['author_session']['status'] == 'admin') || allowed($case,'we')) )
	die("You don't have rights to set this case status!");

$q = "SELECT * FROM lcm_case WHERE id_case=$case";
$result = lcm_query($q);
if (!($row=lcm_fetch_array($result))) die("There is no such case!");

switch ($status) {
//
// Open case
//
	case 'open' :
		break;
//
// Close case
//
	case 'closed' :
		// Start page
		lcm_page_start("New follow-up");
		// Set defaults
		$type = 'conclusion';
		$date_start = date('Y-m-d H:i:s');

		// Write form
		echo '<form action="upd_fu.php" method="POST">
	<table class="tbl_usr_dtl" width="99%">
		<tr><td>Close date:</td>
			<td>';
		echo get_date_inputs('start', $date_start, false);
//		echo f_err('date_start',$errors);
		echo "</td>
		</tr>
		<tr><td>Type:</td>
			<td><input type='hidden' name='type' value='$type'>$type</td>
		</tr>
		<tr><td valign='top'>Description:</td>
			<td><textarea name='description' rows='15' cols='40' class='frm_tarea'></textarea></td>
		</tr>
		<tr><td>Sum billed:</td>
			<td><input name='sumbilled' value='0' class='search_form_txt' size='10' />";
		// [ML] If we do this we may as well make a function
		// out of it, but not sure where to place it :-)
		// This code is also in config_site.php
		$currency = read_meta('currency');
		if (empty($currency)) {
			$current_lang = $GLOBALS['lang'];
			$GLOBALS['lang'] = read_meta('default_language');
			$currency = _T('currency_default_format');
			$GLOBALS['lang'] = $current_lang;
		}

		echo htmlspecialchars($currency);
		echo "</td>
		</tr>
	</table>
	<button name='submit' type='submit' value='submit' class='simple_form_btn'>" . _T('button_validate') . "</button>
	<input type='hidden' name='id_case' value='$case'>
	<input type='hidden' name='ref_edit_fu' value='" . $GLOBALS['HTTP_REFERER'] . "'>
</form>";

		lcm_page_end();
		break;
//
// Merge case
//
	case 'merged' :
		break;
}

?>
