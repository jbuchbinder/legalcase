<?php

include('inc/inc.php');
include('inc/inc_acc.php');
lcm_page_start("Organisation details");

if ($org>0) {
	// Prepare query
	$q="SELECT *
		FROM lcm_org
		WHERE lcm_org.id_org=$org";

	// Do the query
	$result = lcm_query($q);

	// Process the output of the query
	if ($row = lcm_fetch_array($result)) {

	/* Saved for future use
		// Check for access rights
		if (!($row['public'] || allowed($case,'r'))) {
			die("You don't have permission to view this case!");
		}
		$edit = allowed($case,'w');
	*/
		$edit = true;

		// Show organisation details
//		echo '<h3>Details for organisation:</h3><p class="normal_text"><strong>' . $row['name'] .'</strong>';
		echo '<p class="normal_text"><strong>' . $row['name'] ."</strong><br />\n";
//		echo "\n<br />Organisation ID: " . $row['id_org'] . "<br />\n";
//		echo 'Organisation name: ' . $row['name'] . "<br />\n";
		echo 'Address: ' . $row['address'] . "<br />\n";
		echo 'Created on: ' . $row['date_creation'] . "<br />\n";
		echo 'Last update: ' . $row['date_update'] . "<br />\n";
		if ($edit)
			echo ' [<a href="edit_org.php?org=' . $row['id_org'] . '" class="content_link"><strong>Edit organisation information</strong></a>]';

		?></p><!--h3>Representative(s) of this organisation:</h3-->

		<table class="tbl_usr_dtl">
			<tr>
			    <th class="heading">Representative(s):</th>
			    <th class="heading">&nbsp;</th>
			</tr>
		<?php

		// Show organisation representative(s)
		$q="SELECT lcm_client.id_client,name_first,name_middle,name_last
			FROM lcm_client_org,lcm_client
			WHERE id_org=$org AND lcm_client_org.id_client=lcm_client.id_client";

		// Do the query
		$result = lcm_query($q);

		while ($row = lcm_fetch_array($result)) {
			echo '<tr><td><a href="client_det.php?client=' . $row['id_client'] . '" class="content_link">';
			echo $row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last'] . "</a></td>\n<td>";
			if ($edit)
				echo '<a href="edit_client.php?client=' . $row['id_client'] . '" class="content_link">Edit</a>';
			echo "</td></tr>\n";
		}

		if ($edit)
			echo "<tr><td><a href=\"sel_cli_org.php?org=$org\" class=\"content_link\"><strong>Add representative(s)</strong></a></td><td>&nbsp;</td></tr>";

		?>
		</table><br>
		<?php

	} else die("There's no such organisation!");
} else die("Which organisation?");

lcm_page_end();
?>
