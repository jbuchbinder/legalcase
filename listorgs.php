<?php

include('inc/inc.php');

// Prepare query
$q = "SELECT id_org,name
		FROM lcm_org";

if (strlen($find_org_string)>1) {
	// Add search criteria
	$q .= " WHERE (name LIKE '%$find_org_string%')";
	lcm_page_start("Organisation(s), containing '$find_org_string':");
} else {
	lcm_page_start("List of organisation(s)");
}

// Do the query
$result = lcm_query($q);

// Output table tags
?>
<table class="tbl_usr_dtl">
	<tr>
		<th class="heading">Organisation name</th>
		<th class="heading">&nbsp;</th>
	</tr>
<?php
while ($row = lcm_fetch_array($result)) {
?>
	<tr>
		<td><a href="org_det.php?org=<?php echo $row['id_org'] . '" class="content_link">';
		echo highlight_matches(clean_output($row['name']),$find_org_string);
?></td>
		<td><a href="edit_org.php?org=<?php echo $row['id_org']; ?>" class="content_link">Edit</a></td>
	</tr>
<?php
}
?>
	<tr>
		<td><a href="edit_org.php" class="content_link"><strong>Add new organisation</strong></a></td>
		<td>&nbsp;</td>
	</tr>
</table>
<?php

lcm_page_end();
?>
