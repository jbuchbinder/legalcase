<?php

include('inc/inc.php');
include('inc/inc_acc.php');

$case_data = array();

$existing = ($case > 0);

if ($existing) {
	lcm_page_start(_T('edit_case_details'));

	// Check access rights
	if (!allowed($case,'e')) die(_T('error_no_edit_permission'));

	$q = "SELECT *
		  FROM lcm_case
		  WHERE id_case=$case";

	$result = lcm_query($q);

	if ($row = lcm_fetch_array($result)) {
		foreach ($row as $key => $value) {
			$case_data[$key] = $value;
		}
	}

	$admin = allowed($case,'a');

} else {
	lcm_page_start(_T('new_case'));

	// Set default values for the new case
	$case_data['id_author'] = $GLOBALS['author_session']['id_author'];
	$case_data['date_creation'] = date(_T('date_format')); // was: date('Y-m-d H:i:s');
	$case_data['public'] = read_meta('case_default_read');
	$case_data['pub_write'] = read_meta('case_default_write');

	$admin = true;

}

	echo "

<form action=\"upd_case.php\" method="POST">
	<table>
		<caption>" . _T('case_details') . "</caption>
		<tr><th>" . _T('parameter') . "</th><th>" . _T('value') . "</th></tr>
		<tr><td>" . _T('case_id') . ":</td><td>" . $case_data['id_case'] . "
			<input type=\"hidden\" name=\"id_case\" value=\"" . $case_data['id_case'] . "\"></td></tr>
		<tr><td>" . _T('author_id') . ":</td><td>" . $case_data['id_author'] . "
			<input type=\"hidden\" name=\"id_author\" value=\"" . $case_data['id_author'] . "\"></td></tr>
		<tr><td>" . _T('case_title') . ":</td>
			<td><input name=\"title\" value=\"" . htmlspecialchars($case_data['title']) . "\"></td></tr>
		<tr><td>" . _T('court archive_id') . ":</td>
			<td><input name=\"id_court_archive\" value=\"" . htmlspecialchars($case_data['id_court_archive']) . "\"></td></tr>
		<tr><td>" . _T('creation_date') . ":</td>
			<td>" . $case_data['date_creation'] . "</td></tr>
		<tr><td>" . _T('assignment_date') . ":</td>
			<td><input name=\"date_assignment\" value=\"" . $case_data['date_assignment'] . "\"></td></tr>
		<tr><td>" . _T('legal_reason') . ":</td>
			<td><input name=\"legal_reason\" value=\"" . htmlspecialchars($case_data['legal_reason']) . "\"></td></tr>
		<tr><td>" . _T('alledged_crime') . ":</td>
			<td><input name=\"alledged_crime\" value=\"" . htmlspecialchars($case_data['alledged_crime'] . "\"></td></tr>
		<tr><td>" . _T('case_status') . ":</td>
			<td><input name=\"status\" value=\"" . $case_data['status'] . "\"></td></tr>
	</table>
	";
	if ($admin || !read_meta('case_read_always') || !read_meta('case_write_always')) { ?>
	<table>
		<tr><td></td>
<?php
		if (read_meta('case_read_always') || $admin) echo "			<td>" . _T('read') . "</td>\n";
		if (read_meta('case_write_always') || $admin) echo "			<td>" . _T('write') . "</td>\n";
		echo "		</tr>
		<tr><td>" . _T('public') . ":</td>\n";
		if (read_meta('case_read_always') || $admin) {
			echo '			<td><input type="checkbox" name="public" value="yes"';
			if ($case_data['public']) echo ' checked';
			echo "></td>\n";
		}
		if (read_meta('case_write_always') || $admin) {
			echo '			<td><input type="checkbox" name="pub_write" value="yes"';
			if ($case_data['pub_write']) echo ' checked';
			echo "></td>";
		}
?>		</tr>
	</table>
<?php
	}

// Different buttons for edit existing and for new case
	if ($existing) {
		echo '	<button name="submit" type="submit" value="submit">' . _T('save') . "</button>\n";
	} else {
		echo '	<button name="submit" type="submit" value="add">' . _T('add') . '</button>
	<button name="submit" type="submit" value="addnew">' . _T('add_and_open_new') . '</button>
	<button name="submit" type="submit" value="adddet">' . _T('add_and_go_to_details') . "</button>\n";
	}
	echo '	<button name="reset" type="reset">' . _T('reset') . '</button>
	<input type="hidden" name="date_creation" value="' . $case_data['date_creation'] '">
	<input type="hidden" name="ref_edit_case" value="';
	if ($ref) echo $ref;
	else echo $HTTP_REFERER;
	echo '">
</form>

';
	lcm_page_end();
?>
