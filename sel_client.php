<?php

include('inc/inc.php');
lcm_page_start("Select client(s)");

$case = intval($_GET['case']);

if ($case>0) {
	// Prepare query
	$q = "SELECT *
		  FROM lcm_case_client_org
		  WHERE id_case=$case";

	// Do the query
	$result = lcm_query($q);

	// Prepare list query
	$q = "SELECT id_client,name_first,name_middle,name_last
		  FROM lcm_client
		  WHERE id_client NOT IN (0";

	// Process the output of the query
	while ($row = lcm_fetch_array($result)) {
		// Add clients to NOT IN list
		$q .= ',' . $row['id_client'];
	}
	$q .= ')';

	// Do the query
	$result = lcm_query($q);
?>
<form action="add_client.php" method="post">
	<!--h3>List if clients</h3-->
	<table border="0" width="99%" class="tbl_usr_dtl">

		<tr>
			<th class="heading">&nbsp;</th>
			<th class="heading">Client name</th>
			<th class="heading">&nbsp;</th>
		</tr>
<?php
	while ($row = lcm_fetch_array($result)) {
?>
		<tr>
			<td><input type="checkbox" name="clients[]" value="<?php echo $row['id_client']; ?>"></td>
			<td><?php echo $row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last']; ?></td>
			<td><a href="edit_client.php?client=<?php echo $row['id_client']; ?>">Edit</a></td>
		</tr>
<?php
	}
?>
		<tr>
			<td></td>
			<td><a href="edit_client.php">Add new client</a></td>
			<td></td>
		</tr>
	</table>
	<input type="hidden" name="case" value="<?php echo $case; ?>">
	<input type="hidden" name="ref_sel_client" value="<?php echo $GLOBALS['HTTP_REFERER']; ?>">
	<button name="submit" type="submit" value="submit">Add client(s) to the case</button>
	<button name="reset" type="reset" value="reset">Clear selected</button>
</form>
<?php

} else {
	die("There's no such case!");
}

lcm_page_end();
?>
