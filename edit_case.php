<?php

include('inc/inc.php');
include('inc/inc_acc.php');

$case_data = array();

if ($case > 0) {
	lcm_page_start("Edit case details");

	// Check access rights
	if (!allowed($case,'e')) die("You don't have permission to edit this case!");

	$q = "SELECT *
		  FROM lcm_case
		  WHERE id_case=$case";

	$result = lcm_query($q);

	if ($row = lcm_fetch_array($result)) {
		foreach ($row as $key => $value) {
			$case_data[$key] = $value;
		}
	}
} else {
	global $author_session;

	lcm_page_start("New case");

	$case_data['id_author'] = $author_session['id_author'];
	$case_data['date_creation'] = date('Y-m-d H:i:s');
}

?>

<form action="upd_case.php" method="POST">
	<table>
		<caption>Case details</caption>
		<tr><th>Parameter</th><th>Value</th></tr>
		<tr><td>Case ID:</td><td><?php echo $case_data['id_case']; ?>
			<input type="hidden" name="id_case" value="<?php echo $case_data['id_case']; ?>"></td></tr>
		<tr><td>Author ID:</td><td><?php echo $case_data['id_author']; ?>
			<input type="hidden" name="id_author" value="<?php echo $case_data['id_author']; ?>"></td></tr>
		<tr><td>Case title:</td>
			<td><input name="title" value="<?php echo htmlspecialchars($case_data['title']); ?>"></td></tr>
		<tr><td>Court archive:</td>
			<td><input name="id_court_archive" value="<?php echo htmlspecialchars($case_data['id_court_archive']); ?>"></td></tr>
		<tr><td>Date created:</td>
			<td><?php echo $case_data['date_creation']; ?></td></tr>
		<tr><td>Date assigned:</td>
			<td><input name="date_assignment" value="<?php echo $case_data['date_assignment']; ?>"></td></tr>
		<tr><td>Legal reason:</td>
			<td><input name="legal_reason" value="<?php echo htmlspecialchars($case_data['legal_reason']); ?>"></td></tr>
		<tr><td>Alledged crime:</td>
			<td><input name="alledged_crime" value="<?php echo htmlspecialchars($case_data['alledged_crime']); ?>"></td></tr>
		<tr><td>Case status:</td>
			<td><input name="status" value="<?php echo $case_data['status']; ?>"></td></tr>
		<tr><td>Public:</td>
			<td><input type="checkbox" name="public" value="yes"<?php
			if ($case_data['public']) echo ' checked'; ?>></td></tr>
	</table>

	<button name="submit" type="submit" value="submit">Save</button>
	<button name="reset" type="reset">Reset</button>
	<input type="hidden" name="date_creation" value="<?php echo $case_data['date_creation']; ?>">
	<input type="hidden" name="ref_edit_case" value="<?php echo $HTTP_REFERER ?>">
</form>

<?php
	lcm_page_end();
?>
