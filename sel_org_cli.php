<?php

include('inc/inc.php');
lcm_page_start("Select organisation(s)");

if ($client>0) {
	// Prepare query
	$q = "SELECT *
		  FROM lcm_client_org
		  WHERE id_client=$client";

	// Do the query
	$result = lcm_query($q);

	// Prepare list query
	$q = "SELECT id_org,name
		  FROM lcm_org
		  WHERE id_org NOT IN (0";

	// Process the output of the query
	while ($row = lcm_fetch_array($result)) {
		// Add org in NOT IN list
		$q .= ',' . $row['id_org'];
	}
	$q .= ')';

	// Do the query
	$result = lcm_query($q);
?>
<form action="add_org_cli.php" method="post">
	<table border>
		<caption>List if organisations</caption>
		<tr>
			<th></th>
			<th>Organisation name</th>
			<th></th>
		</tr>
<?php
	while ($row = lcm_fetch_array($result)) {
?>
		<tr>
			<td><input type="checkbox" name="orgs[]" value="<?php echo $row['id_org']; ?>"></td>
			<td><?php echo $row['name']; ?></td>
			<td><a href="edit_org.php?org=<?php echo $row['id_org']; ?>">Edit</a></td>
		</tr>
<?php
	}
?>
		<tr>
			<td></td>
			<td><a href="edit_org.php">Add new organisation</a></td>
			<td></td>
		</tr>
	</table>
	<input type="hidden" name="client" value="<?php echo $client; ?>">
	<input type="hidden" name="ref_sel_org_cli" value="<?php echo $HTTP_REFERER ?>">
	<button name="submit" type="submit" value="submit">Add organisation(s)</button>
	<button name="reset" type="reset" value="reset">Clear selected</button>
</form>
<?php

} else {
	die("There's no such client!");
}

lcm_page_end();
?>
