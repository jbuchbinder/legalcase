<?php

include('inc/inc.php');
lcm_page_start("Select representative(s)");

$org = intval($_GET['org']);

if ($org>0) {
	// Prepare query
	$q = "SELECT *
		  FROM lcm_client_org
		  WHERE id_org=$org";

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
<form action="add_cli_org.php" method="post">
<h3>List if persons</h3>
	<table class="tbl_usr_dtl">
		<tr>
			<th class="heading">&nbsp;</th>
			<th class="heading">Person name</th>
			<th class="heading">&nbsp;</th>
		</tr>
<?php
	while ($row = lcm_fetch_array($result)) {
?>
		<tr>
			<td><input type="checkbox" name="clients[]" value="<?php echo $row['id_client']; ?>"></td>
			<td><?php echo $row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last']; ?></td>
			<td><a href="edit_client.php?client=<?php echo $row['id_client']; ?>" class="content_link">Edit</a></td>
		</tr>
<?php
	}
?>
		<tr>
			<td>&nbsp;</td>
			<td><a href="edit_client.php" class="content_link"><strong>Add new person</strong></a></td>
			<td>&nbsp;</td>
		</tr>
	</table>
	<input type="hidden" name="org" value="<?php echo $org; ?>">
	<input type="hidden" name="ref_sel_cli_org" value="<?php echo $HTTP_REFERER ?>">
	<button name="submit" type="submit" value="submit" class="simple_form_btn">Add representative(s)</button>
	<button name="reset" type="reset" value="reset" class="simple_form_btn">Clear selected</button>
</form>
<?php

} else {
	die("There's no such organisation!");
}

lcm_page_end();
?>
