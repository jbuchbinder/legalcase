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

	$Id: set_case_status.php,v 1.16 2005/02/10 09:08:11 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Get input values
$case = intval($_GET['case']);
$status = clean_input($_GET['status']);

// Check if case_id is set
if (!($case>0)) die("Which case?");

// Check access rights
if ( !(($GLOBALS['author_session']['status'] == 'admin') || allowed($case,'we')) )
	die("You don't have rights to set this case status!");

// Get site preferences
$fu_sum_billed = read_meta('fu_sum_billed');

// Check if there are no errors - new followup
if (! count($_SESSION['errors'])) {
	// Clear followup data
	$_SESSION['fu-data'] = array();
}

// Get case details
$q = "SELECT * FROM lcm_case WHERE id_case=$case";
$result = lcm_query($q);
if (!($row=lcm_fetch_array($result))) die("There is no such case!");

switch ($status) {
//
// Open case
//
	case 'open' :
		// Check the current case status
		switch ($row['status']) {
			case 'suspended' :
				// Set defaults
				$page_title = 'Resuming case: ' . clean_output($row['title']);
				$date_title = 'Resumption:';
				$type = 'resumption';
				$date_start = date('Y-m-d H:i:s');
				break;
			case 'closed' :
				// Set defaults
				$page_title = 'Re-opening case: ' . clean_output($row['title']);
				$date_title = 'Re-opening:';
				$type = 'reopening';
				$date_start = date('Y-m-d H:i:s');
				break;
			case 'open' :
			case 'merged' :
				header('Location: ' . $GLOBALS['HTTP_REFERER']);
				exit;
				break;
			case 'draft' :
			default :
				// Set defaults
				$page_title = 'Opening case: ' . clean_output($row['title']);
				$date_title = 'Start:';
				$type = 'opening';
				$date_start = date('Y-m-d H:i:s');
				break;
		}
		// Start the page
		lcm_page_start($page_title);

		// Show the errors (if any)
		echo show_all_errors($_SESSION['errors']);
		
		// Write form
		echo "<form action='upd_fu.php' method='POST'>
	<table class='tbl_usr_dtl' width='99%'>
		<tr><td>$date_title</td>
			<td>";
		echo _T('calendar_info_date');
		echo get_date_inputs('start', $date_start, false);
		echo ' ' . _T('calendar_info_time') . ' ';
		echo get_time_inputs('start', $date_start);
		echo ' ' . f_err_star('date_start',$errors);
		echo "</td>
		</tr>
		<tr><td>Type:</td>
			<td><input type='hidden' name='type' value='$type'>$type</td>
		</tr>
		<tr><td valign='top'>Description:</td>
			<td><textarea name='description' rows='15' cols='40' class='frm_tarea'>" . $_SESSION['fu_data']['description'] . "</textarea></td>
		</tr>\n";
		if ($fu_sum_billed == 'yes') {
			echo "\t\t<tr><td>Sum billed:</td>
			<td><input name='sumbilled' value='" . $_SESSION['fu_data']['sumbilled'] . "' class='search_form_txt' size='10' />";
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
		</tr>\n";
		}
		echo "\t</table>
	<button name='submit' type='submit' value='submit' class='simple_form_btn'>" . _T('button_validate') . "</button>
	<input type='hidden' name='id_case' value='$case'>
	<input type='hidden' name='ref_edit_fu' value='" . $GLOBALS['HTTP_REFERER'] . "'>
</form>\n";

		lcm_page_end();
		break;
//
// Close case
//
	case 'closed' :
		// Check if the case is already closed
		if (($row['status'] == 'closed') || ($row['status'] == 'merged') || ($row['status'] == 'draft')) {
			header('Location: ' . $GLOBALS['HTTP_REFERER']);
			break;
		}
		// Start the page
		lcm_page_start("Closing case: " . clean_output($row['title']));
		// Set defaults
		$type = 'conclusion';
		$date_start = date('Y-m-d H:i:s');

		// Write form
		echo '<form action="upd_fu.php" method="POST">
	<table class="tbl_usr_dtl" width="99%">
		<tr><td>Close:</td>
			<td>';
		echo _T('calendar_info_date');
		echo get_date_inputs('start', $date_start, false);
		echo ' ' . _T('calendar_info_time') . ' ';
		echo get_time_inputs('start', $date_start);
		echo ' ' . f_err_star('date_start',$errors);
		echo "</td>
		</tr>
		<tr><td>Type:</td>
			<td><input type='hidden' name='type' value='$type'>$type</td>
		</tr>
		<tr><td valign='top'>Description:</td>
			<td><textarea name='description' rows='15' cols='40' class='frm_tarea'>" . $_SESSION['fu_data']['description'] . "</textarea></td>
		</tr>\n";
		if ($fu_sum_billed == 'yes') {
			echo "\t\t<tr><td>Sum billed:</td>
			<td><input name='sumbilled' value='" . $_SESSION['fu_data']['sumbilled'] . "' class='search_form_txt' size='10' />";
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
		</tr>\n";
		}
		echo "\t</table>
	<button name='submit' type='submit' value='submit' class='simple_form_btn'>" . _T('button_validate') . "</button>
	<input type='hidden' name='id_case' value='$case'>
	<input type='hidden' name='ref_edit_fu' value='" . $GLOBALS['HTTP_REFERER'] . "'>
</form>";

		lcm_page_end();
		break;
//
// Suspend case
//
	case 'suspended' :
		// Check if the case is already suspended or closed
		if ($row['status'] != 'open') {
			header('Location: ' . $GLOBALS['HTTP_REFERER']);
			break;
		}
		// Start the page
		lcm_page_start("Suspending case: " . clean_output($row['title']));
		// Set defaults
		$type = 'suspension';
		$date_start = date('Y-m-d H:i:s');

		// Write form
		echo '<form action="upd_fu.php" method="POST">
	<table class="tbl_usr_dtl" width="99%">
		<tr><td>Suspension:</td>
			<td>';
		echo _T('calendar_info_date');
		echo get_date_inputs('start', $date_start, false);
		echo ' ' . _T('calendar_info_time') . ' ';
		echo get_time_inputs('start', $date_start);
		echo ' ' . f_err_star('date_start',$errors);
		echo "</td>
		</tr>
		<tr><td>Type:</td>
			<td><input type='hidden' name='type' value='$type'>$type</td>
		</tr>
		<tr><td valign='top'>Description:</td>
			<td><textarea name='description' rows='15' cols='40' class='frm_tarea'>" . $_SESSION['fu_data']['description'] . "</textarea></td>
		</tr>\n";
		if ($fu_sum_billed == 'yes') {
			echo "\t\t<tr><td>Sum billed:</td>
				<td><input name='sumbilled' value='" . $_SESSION['fu_data']['sumbilled'] . "' class='search_form_txt' size='10' />";
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
		</tr>\n";
		}
		echo "\t</table>
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
		// Check if the case is already merged
		if (($row['status'] == 'merged') || ($row['status'] == 'draft')) {
			header('Location: ' . $GLOBALS['HTTP_REFERER']);
			break;
		}
		// Start the page
		lcm_page_start("Merging case: " . clean_output($row['title']));
		// Set defaults
		$type = 'merge';
		$date_start = date('Y-m-d H:i:s');

		// Write form
		echo '<form action="merge_case.php" method="POST">
	<table class="tbl_usr_dtl" width="99%">
		<tr><td>Merge:</td>
			<td>';
		echo _T('calendar_info_date');
		echo get_date_inputs('start', $date_start, false);
		echo ' ' . _T('calendar_info_time') . ' ';
		echo get_time_inputs('start', $date_start);
		echo ' ' . f_err_star('date_start',$errors);
		echo "</td>
		</tr>
		<tr><td>Type:</td>
			<td><input type='hidden' name='type' value='$type'>$type</td>
		</tr>\n";

		// Select case to merge to
		echo "\t\t<tr><td>Merge to case:</td>\n";
		echo "\t\t\t<td><select name='destination'>\n";
		$q = "SELECT id_case,title
				FROM lcm_case
				WHERE id_case<>$case
				ORDER BY title";
		$result = lcm_query($q);

		// Set the length of short followup title
		$title_length = (($prefs['screen'] == "wide") ? 30 : 65);

		// Write the <select> options
		while ($row = lcm_fetch_array($result)) {
			if (strlen($row['title'])<$title_length) $short_title = $row['title'];
			else $short_title = substr($row['title'],0,$title_length) . '...';
			echo "\t\t\t\t<option value='" . $row['id_case'] . "'>" . clean_output($short_title) . "</option>\n";
		}
		echo "\t\t\t</select></td>\n";
		echo "\t\t</tr>\n";

		// Description
		echo "		<tr><td valign='top'>Description:</td>
			<td><textarea name='description' rows='15' cols='40' class='frm_tarea'>" . $_SESSION['fu_data']['description'] . "</textarea></td>
		</tr>\n";
		if ($fu_sum_billed == 'yes') {
			echo "\t\t<tr><td>Sum billed:</td>
			<td><input name='sumbilled' value='" . $_SESSION['fu_data']['sumbilled'] . "' class='search_form_txt' size='10' />";
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
		</tr>\n";
		}
		echo "\t</table>
	<button name='submit' type='submit' value='submit' class='simple_form_btn'>" . _T('button_validate') . "</button>
	<input type='hidden' name='id_case' value='$case'>
	<input type='hidden' name='ref_edit_fu' value='" . $GLOBALS['HTTP_REFERER'] . "'>
</form>";

		lcm_page_end();
		break;
//
// All other statuses
//
	default:
		header('Location: ' . $GLOBALS['HTTP_REFERER']);

}

?>
