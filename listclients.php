<?php

include('inc/inc.php');

function highlight_matches($source,$match) {
	// Initialize variables
	$model = strtolower($source);
	$match = strtolower($match);
	$p = 0;
	$result = '';
	$ml = strlen($match);
	if ($ml>0) {
		$i = strpos($model,$match);

		// Cycle each match
		while (!($i === false)) {
			$result .= (substr($source,$p,$i-$p) . '<b>' . substr($source,$i,$ml) . '</b>');
			$p = $i + $ml;
			$i = strpos($model,$match,$p);
		}
	}
	$result .= substr($source,$p,strlen($source)-$p);
	return $result;
}

// Prepare query
$q = "SELECT id_client,name_first,name_middle,name_last
		FROM lcm_client";

if (strlen($find_client_string)>1) {
	// Add search criteria
	$q .= " WHERE ((name_first LIKE '%$find_client_string%')
			OR (name_middle LIKE '%$find_client_string%')
			OR (name_last LIKE '%$find_client_string%'))";
	lcm_page_start("Client(s), containing '$find_client_string':");
} else {
	lcm_page_start("List of client(s)");
}

// Do the query
$result = lcm_query($q);

// Output table tags
?>
<table border>
	<tr>
		<th>Client name</th>
		<th></th>
	</tr>
<?php
while ($row = lcm_fetch_array($result)) {
?>
	<tr>
		<td><a href="client_det.php?client=<?php echo $row['id_client'] . '">';
		$fullname = $row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last'];
		echo highlight_matches($fullname,$find_client_string);
?></td>
		<td><a href="edit_client.php?client=<?php echo $row['id_client']; ?>">Edit</a></td>
	</tr>
<?php
}
?>
	<tr>
		<td><a href="edit_client.php">Add new client</a></td>
		<td></td>
	</tr>
</table>
<?php

lcm_page_end();
?>
