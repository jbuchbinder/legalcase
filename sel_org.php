<?php

include('inc/inc.php');
lcm_page_start("Select organisation(s)");

$case = intval($_GET['case']);

if ($case>0) {
	// Prepare query
	$q = "SELECT *
		  FROM lcm_case_client_org
		  WHERE id_case=$case";

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
<form action="add_org.php" method="post">
	<h3>List if organisations</h3>
	<table border="0" class="tbl_usr_dtl">
		<tr>
			<th class="heading">&nbsp;</th>
			<th class="heading">Organisation name</th>
			<th class="heading">&nbsp;</th>
		</tr>
<?php
	while ($row = lcm_fetch_array($result)) {
?>
		<tr>
			<td><input type="checkbox" name="orgs[]" value="<?php echo $row['id_org']; ?>"></td>
			<td><?php echo $row['name']; ?></td>
			<td><a href="edit_org.php?org=<?php echo $row['id_org']; ?>" class="content_link">Edit</a></td>
		</tr>
<?php
	}
?>
		<tr>
			<td></td>
			<td><a href="edit_org.php" class="content_link">Add new organisation</a></td>
			<td></td>
		</tr>
	</table>
	<input type="hidden" name="case" value="<?php echo $case; ?>">
	<input type="hidden" name="ref_sel_org" value="<?php echo $GLOBALS['HTTP_REFERER']; ?>">
	<button name="submit" type="submit" value="submit">Add organisation(s) to the case</button>
	<button name="reset" type="reset" value="reset">Clear selected</button>
</form>
<?php

} else {
	die("There's no such case!");
}

lcm_page_end();
?>
