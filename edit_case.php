<?php

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Initiate session
session_start();

if (empty($errors)) {

    // Clear form data
    $case_data = array();

	// Set the returning page
	if (isset($ref)) $case_data['ref_edit_case'] = $ref;
	else $case_data['ref_edit_case'] = $HTTP_REFERER;

	// Register case type variable for the session
	if (!session_is_registered("existing"))
		session_register("existing");

	// Find out if this is existing or new case
	$existing = ($case > 0);

	if ($existing) {
		// Check access rights
		if (!allowed($case,'e')) die(_T('error_no_edit_permission'));

		$q = "SELECT *
			FROM lcm_case
			WHERE id_case=$case";

		$result = lcm_query($q);

		// Register case ID as session variable
	    if (!session_is_registered("case"))
			session_register("case");

		if ($row = lcm_fetch_array($result)) {
			foreach ($row as $key => $value) {
				$case_data[$key] = $value;
			}
		}

		$admin = allowed($case,'a');

	} else {
		// Set default values for the new case
		$case_data['id_author'] = $GLOBALS['author_session']['id_author'];
		$case_data['date_creation'] = date(_T('date_format')); // was: date('Y-m-d H:i:s');
		$case_data['public'] = read_meta('case_default_read');
		$case_data['pub_write'] = read_meta('case_default_write');

		$admin = true;

	}
}

// Start the page with the proper title
if ($existing) lcm_page_start(_T('edit_case_details'));
else lcm_page_start(_T('new_case'));

	echo "\n<form action=\"upd_case.php\" method=\"POST\">
		<table class=\"tbl_usr_dtl\">
			<!-- caption>" . _T('case_details') . "</caption -->
			<!-- tr><th>" . _T('parameter') . "</th><th>" . _T('value') .  "</th></tr -->\n";
	if ($case_data['id_case']) {
		echo "\t<tr><td>" . _T('case_id') . ":</td><td>" . $case_data['id_case'] . "
			<input type=\"hidden\" name=\"id_case\" value=\"" .  $case_data['id_case'] . "\"></td></tr>\n";
	}

	echo "
		<tr><td>" . _T('author_id') . ":</td><td>" . $case_data['id_author'] . "
			<input type=\"hidden\" name=\"id_author\" value=\"" . $case_data['id_author'] . "\"></td></tr>
		<tr><td>" . _T('case_title') . ":</td>
			<td><input name=\"title\" value=\"" . clean_output($case_data['title']) . "\" class=\"search_form_txt\">";
	echo f_err('title',$errors) . "</td></tr>
		<tr><td>" . _T('court_archive_id') . ":</td>
			<td><input name=\"id_court_archive\" value=\"" . clean_output($case_data['id_court_archive']) . "\" class=\"search_form_txt\"></td></tr>";
// [AG] Creation date not shown upon ML request
//		<tr><td>" . _T('creation_date') . ":</td>
//			<td>" . $case_data['date_creation'] . "</td></tr>
	echo "
			<tr><td>" . _T('assignment_date') . ":</td>
			<td><input name=\"date_assignment\" value=\"" . clean_output($case_data['date_assignment']) . "\" class=\"search_form_txt\"></td></tr>
		<tr><td>" . _T('legal_reason') . ":</td>
			<td><input name=\"legal_reason\" value=\"" . clean_output($case_data['legal_reason']) . "\" class=\"search_form_txt\"></td></tr>
		<tr><td>" . _T('alledged_crime') . ":</td>
			<td><input name=\"alledged_crime\" value=\"" .  clean_output($case_data['alledged_crime']) . "\" class=\"search_form_txt\"></td></tr>
		<tr><td>" . _T('case_status') . ":</td>
			<td><input name=\"status\" value=\"" . clean_output($case_data['status']) . "\" class=\"search_form_txt\"></td></tr>
	";

	if ($admin || !read_meta('case_read_always') || !read_meta('case_write_always')) {
		echo "\t<tr><td>" . _T('public') . "</td>
			<td>
				<table>
				<tr>\n";

		if (!read_meta('case_read_always') || $admin) echo "			<td>" . _T('read') . "</td>\n";
		if (!read_meta('case_write_always') || $admin) echo "			<td>" . _T('write') . "</td>\n";

		echo "</tr><tr>\n";

		if (!read_meta('case_read_always') || $admin) {
			echo '			<td><input type="checkbox" name="public" value="yes"';
			if ($case_data['public']) echo ' checked';
			echo "></td>\n";
		}

		if (!read_meta('case_write_always') || $admin) {
			echo '			<td><input type="checkbox" name="pub_write" value="yes"';
			if ($case_data['pub_write']) echo ' checked';
			echo "></td>\n";
		}
?>				</tr>
				</table>
			</td>
		</tr>

<?php
	}

	echo "</table>\n";

	// Different buttons for edit existing and for new case
	if ($existing) {
		echo '	<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('save') . "</button>\n";
	} else {
		echo '	<button name="submit" type="submit" value="add" class="simple_form_btn">' . _T('add') . '</button>
	<button name="submit" type="submit" value="addnew" class="simple_form_btn">' . _T('add_and_open_new') . '</button>
	<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('add_and_go_to_details') . "</button>\n";
	}
	echo '	<button name="reset" type="reset" class="simple_form_btn">' . _T('reset') . "</button>\n";
	echo '	<input type="hidden" name="ref_edit_case" value="' . $case_data['ref_edit_case'];
	echo '">
</form>

';
	lcm_page_end();
?>
