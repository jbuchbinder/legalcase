<?php

include('inc/inc.php');
lcm_page_start("Select client(s)");

$case = intval($_GET['case']);

if (! $case)
	die("There's no such case!");

//
// Show only clients who are not already in the case 
// Extract the clients on the case, then put them in a "not in" list
//
$q = "SELECT *
		FROM lcm_case_client_org
		WHERE id_case = $case";

$result = lcm_query($q);

$q2 = "SELECT id_client,name_first,name_middle,name_last
		FROM lcm_client
		WHERE id_client NOT IN (0";

// Build "not in" list
while ($row = lcm_fetch_array($result)) {
	$q2 .= ',' . $row['id_client'];
}

$q2 .= ')';

$result = lcm_query($q2);

?>

<ul><li>Todo: Search for client + if list too long, show only search.</li>
<li>Todo: Show case overview.</li></ul>

<form action="add_client.php" method="post">
	<table border="0" class="tbl_usr_dtl">

		<tr>
			<th class="heading">&nbsp;</th>
			<th class="heading" width="350">Client name</th>
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
			<td></td>
<?php
	echo '<td><a href="edit_client.php?attach_case=' . $case . '" class="content_link">' . 'Create a new client and attach to case' . '</a></td>' . "\n";
?>
		<td></td>
		</tr>
	</table>
	<input type="hidden" name="case" value="<?php echo $case; ?>">
	<input type="hidden" name="ref_sel_client" value="<?php echo $GLOBALS['HTTP_REFERER']; ?>">
	<button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate'); ?></button>
</form>

<?php

lcm_page_end();

?>
