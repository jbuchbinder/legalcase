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

	$Id: edit_rep.php,v 1.7 2005/02/03 11:44:35 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

if (empty($_SESSION['errors'])) {

    // Clear form data
    $rep_data = array();

	// Set the returning page
	if (isset($ref)) $rep_data['ref_edit_rep'] = $ref;
	else $rep_data['ref_edit_rep'] = $GLOBALS['HTTP_REFERER'];

	// Register case type variable for the session
	if (!session_is_registered("existing"))
		session_register("existing");

	// Find out if this is existing or new case
	$existing = ($rep > 0);

	if ($existing) {
		// Check access rights
		//if (!allowed($case,'e')) die(_T('error_no_edit_permission'));

		$q = "SELECT *
			FROM lcm_report
			WHERE id_report=$rep";

		$result = lcm_query($q);

		// Register report ID as session variable
	    if (!session_is_registered("rep"))
			session_register("rep");

		if ($row = lcm_fetch_array($result)) {
			foreach ($row as $key => $value) {
				$rep_data[$key] = $value;
			}
		}

		//$admin = allowed($case,'a');

	} else {
		// Set default values for the new report
		$rep_data['id_author'] = $GLOBALS['author_session']['id_author'];

		//$rep_data['public'] = read_meta('case_default_read');
		//$rep_data['pub_write'] = read_meta('case_default_write');

		//$admin = true;

	}
}

// Start the page with the proper title
if ($existing) 
	lcm_page_start(_T('edit_rep_details'));
else 
	lcm_page_start(_T('new_report'));

echo "\n<form action='upd_rep.php' method='post'>
	<table class='tbl_usr_dtl' width='99%'>\n";

if ($rep_data['id_report']) {
	echo "\t<tr><td>" . _T('report_id') . ":</td><td>" . $rep_data['id_report'] . "
		<input type=\"hidden\" name=\"id_report\" value=\"" .  $rep_data['id_report'] . "\"></td></tr>\n";
}

echo "
	<tr><td>" . _T('author_id') . ":</td><td>" . $rep_data['id_author'] . "
		<input type=\"hidden\" name=\"id_author\" value=\"" . $rep_data['id_author'] . "\"></td></tr>
	<tr><td>" . f_err_star('title', $_SESSION['errors']) . _T('rep_title') . ":</td>
		<td><input name=\"title\" value=\"" . clean_output($rep_data['title']) . "\" class=\"search_form_txt\"></td>
	</tr>\n";

echo "<tr>\n";

// Description
echo '<td colspan="2">' . "<p>Description:</p>\n";
echo '<textarea name="description" rows="5" cols="40" class="frm_tarea">';
echo $rep_data['description'];
echo "</textarea>\n";

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
//			if ($rep_data['public']) echo ' checked';
//			echo "></td>\n";
//		}
//
//		if (!read_meta('case_write_always') || $admin) {
//			echo '			<td><input type="checkbox" name="pub_write" value="yes"';
//			if ($rep_data['pub_write']) echo ' checked';
//			echo "></td>\n";
//		}
//? >				</tr>
//				</table>
//			</td>
//		</tr>
//
//<?php
//	}

echo "</table>\n";

// Validation buttons
echo '	<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";

// More buttons for 'extended' mode
if ($prefs['mode'] == 'extended') {
	echo '<button name="submit" type="submit" value="addnew" class="simple_form_btn">' . _T('add_and_open_new') . "</button>\n";
	echo '<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('add_and_go_to_details') . "</button>\n";
	echo '	<button name="reset" type="reset" class="simple_form_btn">' . _T('button_reset') . "</button>\n";
}

echo '<input type="hidden" name="ref_edit_rep" value="' . $rep_data['ref_edit_rep'] . '">' . "\n";
echo '</form>' . "\n";

lcm_page_end();

?>
