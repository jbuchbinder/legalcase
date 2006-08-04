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

	$Id: edit_rep.php,v 1.19 2006/08/04 21:18:34 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

if (empty($_SESSION['errors'])) {

	// Clear form data
	$_SESSION['form_data'] = array();

	// Set the returning page
	$_SESSION['form_data']['ref_edit_rep'] = _request('ref', $_SERVER['HTTP_REFERER']);

	// Read input values
	$_SESSION['form_data']['id_report'] = intval(_request('rep'));

	// If adding new custom report
	$_SESSION['form_data']['filecustom'] = _request('filecustom');

	if (_session('id_report')) {
		// [ML] NOTE: This is wrong. If count(errors) then this check is skipped?
		// + make sure that this test is also done in 'upd_rep.php'
		
		// Check access rights
		//if (!allowed($case,'e')) die(_T('error_no_edit_permission'));

		$q = "SELECT *
			FROM lcm_report
			WHERE id_report=" . _session('id_report');

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach ($row as $key => $value) {
				$_SESSION['form_data'][$key] = $value;
			}
		}

		//$admin = allowed($case,'a');

	} else {
		// Set default values for the new report
		//$_SESSION['form_data']['public'] = read_meta('case_default_read');
		//$_SESSION['form_data']['pub_write'] = read_meta('case_default_write');

		//$admin = true;

	}
}

// Input validation
if (_session('filecustom')) {
	if (! preg_match("/^[-_A-Za-z0-9]+$/", _session('filecustom')))
		$_SESSION['errors']['filecustom'] = htmlspecialchars(_session('filecustom'))
										. ": " . "Report file name has illegal characters"; // TRAD
	elseif (! include_custom_report_exists(_session('filecustom')))
		$_SESSION['errors']['filecustom'] = htmlspecialchars(_session('filecustom'))
										. ": " . "Report file does not exist"; // TRAD

	if ($_SESSION['errors']['filecustom'])
		$_SESSION['form_data']['filecustom'] = '';
}

// Start the page with the proper title
if (_session('id_report'))
	lcm_page_start(_T('title_rep_edit') . " " . _session('title'), '', '', 'reports_intro');
else 
	lcm_page_start(_T('title_rep_new'), '', '', 'reports_intro');

echo show_all_errors();


if ($_SESSION['form_data']['filecustom']) {
	include_custom_report($_SESSION['form_data']['filecustom']);

	$rep_specs = new CustomReportSpecs();

	echo '<p class="normal_text">';

	if (_session('id_report'))
		echo "This report is using the custom report in '" . $_SESSION['form_data']['filecustom'] . "'"; // TRAD
	else
		echo "This report will use the custom report in '" . $_SESSION['form_data']['filecustom'] . "'"; // TRAD
	
	echo ": " . $rep_specs->getDescription() . "</p>\n";
}

echo "<fieldset class=\"info_box\">\n";
echo "<form action='upd_rep.php' method='post'>\n";

if ($_SESSION['form_data']['filecustom']) {
	echo '<input type="hidden" name="filecustom" value="' . $_SESSION['form_data']['filecustom'] . '" />' . "\n";
}

if ($_SESSION['form_data']['id_report']) {
	echo "<strong>". _Ti('rep_input_id') . "</strong>&nbsp;" . $_SESSION['form_data']['id_report'] . "
		<input type=\"hidden\" name=\"id_report\" value=\"" .
		$_SESSION['form_data']['id_report'] . "\">\n";
		
	// [ML] echo "&nbsp;|&nbsp;\n";
}

// Title of report
echo "<p>" . f_err_star('title') ."<strong>". _Ti('rep_input_title') . "</strong><br />";
echo '<input name="title" value="' . clean_output($_SESSION['form_data']['title']) . '" class="search_form_txt"></p>' . "\n";

// Description
echo '<p>' . "<strong>" . _Ti('rep_input_description') . "</strong><br />\n";
echo '<textarea name="description" rows="5" cols="40" class="frm_tarea">';
echo $_SESSION['form_data']['description'];
echo "</textarea></p>\n";

// Notes
echo '<p>' . "<strong>" . _Ti('rep_input_notes') . "</strong><br />\n";
echo '<textarea name="notes" rows="5" cols="40" class="frm_tarea">';
echo $_SESSION['form_data']['notes'];
echo "</textarea></p>\n";

//	if ($admin || !read_meta('case_read_always') || !read_meta('case_write_always')) {
//		echo "\t<tr><td>" . _T('public') . "</td>
//			<td>
//				<table>
//				<tr>\n";
//
//		if (!read_meta('case_read_always') || $admin) echo "			<td>" . _T('read') . "</td>\n";
//		if (!read_meta('case_write_always') || $admin) echo "			<td>" . _T('write') . "</td>\n";
//
//		echo "</tr><tr>\n";
//
//		if (!read_meta('case_read_always') || $admin) {
//			echo '			<td><input type="checkbox" name="public" value="yes"';
//			if ($_SESSION['form_data']['public']) echo ' checked';
//			echo "></td>\n";
//		}
//
//		if (!read_meta('case_write_always') || $admin) {
//			echo '			<td><input type="checkbox" name="pub_write" value="yes"';
//			if ($_SESSION['form_data']['pub_write']) echo ' checked';
//			echo "></td>\n";
//		}
//? >				</tr>
//				</table>
//			</td>
//		</tr>
//
//<?php
//	}

//echo "</table>\n";

// Submit button
echo '<input type="hidden" name="ref_edit_rep" value="' . _session('ref_edit_rep') . '">' . "\n";
echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
echo '</form>' . "\n";

echo "</fieldset>";

// Clear errors
$_SESSION['errors'] = array();
$_SESSION['form_data'] = array();
$_SESSION['rep_data'] = array(); // DEPRECATED LCM 0.7.0

lcm_page_end();

?>
