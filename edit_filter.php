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
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Initiate session
session_start();

if (empty($errors)) {

    // Clear form data
    $filter_data = array();

	// Set the returning page
	if (isset($ref)) $filter_data['ref_edit_filter'] = $ref;
	else $filter_data['ref_edit_filter'] = $GLOBALS['HTTP_REFERER'];

	// Register case type variable for the session
	if (!session_is_registered("existing"))
		session_register("existing");

	// Find out if this is existing or new case
	$existing = ($filter > 0);

	if ($existing) {
		// Check access rights
		//if (!allowed($case,'e')) die(_T('error_no_edit_permission'));

		$q = "SELECT *
			FROM lcm_filter
			WHERE id_filter=$filter";

		$result = lcm_query($q);

		// Register filter ID as session variable
	    if (!session_is_registered("filter"))
			session_register("filter");

		if ($row = lcm_fetch_array($result)) {
			foreach ($row as $key => $value) {
				$filter_data[$key] = $value;
			}
		}

		//$admin = allowed($case,'a');

	} else {
		// Set default values for the new filter
		$filter_data['id_author'] = $GLOBALS['author_session']['id_author'];
		$filter_data['date_creation'] = date(_T('date_format')); // was: date('Y-m-d H:i:s');
		//$filter_data['public'] = read_meta('case_default_read');
		//$filter_data['pub_write'] = read_meta('case_default_write');

		//$admin = true;

	}
}

// Start the page with the proper title
if ($existing) lcm_page_start(_T('edit_filter_details'));
else lcm_page_start(_T('new_filter'));

	// Show filter data form
	echo "\n<form action='upd_filter.php' method='POST'>
		<table class='tbl_usr_dtl'>\n";
	// Show filter ID if available
	if ($filter_data['id_filter']) {
		echo "\t<tr><td>" . _T('filter_id') . ":</td><td>" . $filter_data['id_filter'] . "
			<input type=\"hidden\" name=\"id_filter\" value=\"" .  $filter_data['id_filter'] . "\"></td></tr>\n";
	}
	// Show author ID
	echo "
		<tr><td>" . _T('author_id') . ":</td><td>" . $filter_data['id_author'] . "
			<input type=\"hidden\" name=\"id_author\" value=\"" . $filter_data['id_author'] . "\"></td></tr>";
	// Show filter title
	echo"
		<tr><td>" . _T('filter_title') . ":</td>
			<td><input name=\"title\" value=\"" . clean_output($filter_data['title']) . "\" class=\"search_form_txt\">";
	echo f_err('title',$errors) . "</td></tr>";
	echo"
		<tr><td>" . _T('filter_type') . ":</td>
			<td><select name=\"type\">
				<option" . (($filter_data['type']='AND') ? ' selected' : '') . ">AND</option>
				<option" . (($filter_data['type']='OR') ? ' selected' : '') . ">OR</option>
			</select></td></tr>";

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
//			if ($filter_data['public']) echo ' checked';
//			echo "></td>\n";
//		}
//
//		if (!read_meta('case_write_always') || $admin) {
//			echo '			<td><input type="checkbox" name="pub_write" value="yes"';
//			if ($filter_data['pub_write']) echo ' checked';
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

	// Different buttons for edit existing and for new case
	if ($existing) {
		echo '	<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('save') . "</button>\n";
	} else {
		echo '	<button name="submit" type="submit" value="add" class="simple_form_btn">' . _T('add') . '</button>
	<button name="submit" type="submit" value="addnew" class="simple_form_btn">' . _T('add_and_open_new') . '</button>
	<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('add_and_go_to_details') . "</button>\n";
	}

//	if ($existing)
		echo '	<button name="reset" type="reset" class="simple_form_btn">' . _T('button_reset') . "</button>\n";

	echo '	<input type="hidden" name="ref_edit_filter" value="' . $filter_data['ref_edit_filter'];
	echo '">
</form>

';
	lcm_page_end();
?>
