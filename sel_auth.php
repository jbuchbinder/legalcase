<?php

include('inc/inc.php');
lcm_page_start("Select users(s)");

if ($case>0) {
	// Prepare query
	$q = "SELECT *
		  FROM lcm_case_author
		  WHERE id_case=$case";

	// Do the query
	$result = lcm_query($q);

	// Prepare list query
	$q = "SELECT id_author,name_first,name_middle,name_last
		  FROM lcm_author
		  WHERE id_author NOT IN (0";

	// Process the output of the query
	while ($row = lcm_fetch_array($result)) {
		// Add clients to NOT IN list
		$q .= ',' . $row['id_author'];
	}
	$q .= ')';

	// Do the query
	$result = lcm_query($q);
?>
<form action="add_auth.php" method="post">
	<table border>
		<caption>List if users</caption>
		<tr>
			<th></th>
			<th>User name</th>
		</tr>
<?php
	while ($row = lcm_fetch_array($result)) {
?>
		<tr>
			<td><input type="checkbox" name="authors[]" value="<?php echo $row['id_author']; ?>"></td>
			<td><?php echo $row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last']; ?></td>
		</tr>
<?php
	}
?>
	</table>
	<input type="hidden" name="case" value="<?php echo $case; ?>">
	<input type="hidden" name="ref_sel_auth" value="<?php echo $HTTP_REFERER ?>">
	<button name="submit" type="submit" value="submit">Add user(s) to the case</button>
	<button name="reset" type="reset" value="reset">Clear selected</button>
</form>
<?php

} else {
	die("There's no such case or author!");
}

lcm_page_end();
?>
