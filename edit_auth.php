<?php

include('inc/inc.php');

lcm_page_start("Edit authors rights");

if ($case>0) {
	// Prepare query
	$q = "SELECT *
		FROM lcm_case_author,lcm_author
		WHERE (id_case=$case
			AND lcm_case_author.id_author=lcm_author.id_author";
	if ($author>0)
		$q .= " AND lcm_author.id_author=$author";
	$q .= ')';

	// Do the query
	$result = lcm_query($q);

?>
<h1>Edit access rights for case <?php echo $case; ?></h1>
<form action="upd_auth.php" method="POST">
	<table border><caption>Access rights</caption>
		<tr><th align="center">User</th>
			<th align="center">Read</th>
			<th align="center">Write</th>
			<th align="center">Edit</th>
			<th align="center">Admin</th>
		</tr>
<?php

	// Process the output of the query
	while ($row = lcm_fetch_array($result)) {
		echo '		<tr><td>' . $row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last'] . "</td>\n";
		echo '			<td align="center">';
		echo '<input type="checkbox" name="auth[' . $row['id_author'] . "][ac_read]" . '" value="1"';
		if ($row['ac_read']) echo ' checked';
		echo "></td>\n";
		echo '			<td align="center">';
		echo '<input type="checkbox" name="auth[' . $row['id_author'] . "][ac_write]" . '" value="1"';
		if ($row['ac_write']) echo ' checked';
		echo "></td>\n";
		echo '			<td align="center">';
		echo '<input type="checkbox" name="auth[' . $row['id_author'] . "][ac_edit]" . '" value="1"';
		if ($row['ac_edit']) echo ' checked';
		echo "></td>\n";
		echo '			<td align="center">';
		echo '<input type="checkbox" name="auth[' . $row['id_author'] . "][ac_admin]" . '" value="1"';
		if ($row['admin']) echo ' checked';
		echo "></td>\n";
	}
?>
		</tr>
	</table>
	<button name="submit" type="submit" value="submit">Save</button>
	<button name="reset" type="reset">Reset</button>
	<input type="hidden" name="case" value="<?php echo $case; ?>">
	<input type="hidden" name="ref_edit_auth" value="<?php echo $HTTP_REFERER; ?>">
</form>
<?php

} else die ("Which case?");

	lcm_page_end();
?>
