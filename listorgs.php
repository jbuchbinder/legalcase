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
<table border>
	<tr>
		<th>Organisation name</th>
		<th></th>
	</tr>
<?php
while ($row = lcm_fetch_array($result)) {
?>
	<tr>
		<td><a href="org_det.php?org=<?php echo $row['id_org'] . '">';
		echo highlight_matches($row['name'],$find_org_string);
?></td>
		<td><a href="edit_org.php?org=<?php echo $row['id_org']; ?>">Edit</a></td>
	</tr>
<?php
}
?>
	<tr>
		<td><a href="edit_org.php">Add new organisation</a></td>
		<td></td>
	</tr>
</table>
<?php

lcm_page_end();
?>
