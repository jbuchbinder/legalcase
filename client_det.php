<?php

include('inc/inc.php');
include('inc/inc_acc.php');
lcm_page_start("Client details");

if ($client>0) {
	// Prepare query
	$q="SELECT *
		FROM lcm_client
		WHERE lcm_client.id_client=$client";

	// Do the query
	$result = lcm_query($q);

	// Process the output of the query
	if ($row = lcm_fetch_array($result)) {

	/* Saved for future use
		// Check for access rights
		if (!($row['public'] || allowed($client,'r'))) {
			die("You don't have permission to view this client details!");
		}
		$edit = allowed($client,'w');
	*/
		$edit = true;

		// Show client details
		echo '<p class="normal_text">Name: ' . $row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last'] . "<br>\n";
		echo 'Client ID: ' . $row['id_client'] . "<br>\n";
		echo 'Citizen number: ' . $row['citizen_number'] . "<br>\n";
		echo 'Address: ' . $row['address'] . "<br>\n";
		echo 'Civil status: ' . $row['civil_status'] . "<br>\n";
		echo 'Income: ' . $row['income'] . "<br>\n";
		echo 'Creation date: ' . $row['date_creation'] . "<br>\n";
		echo 'Last update date: ' . $row['date_update'] . "<br>\n";
		if ($edit)
			echo ' [<a href="edit_client.php?client=' . $row['id_client'] . '" class="content_link"><strong>Edit client information</strong></a>]';

		?></p><h3>Organisation(s) represented by this client:</h3>
		<table border="0" class="tbl_usr_dtl">
		    <tr>
			<th class="heading">Organisation name</th>
			<th class="heading">&nbsp;</th>
		    </tr>
		<?php

		// Show organisation(s)
		$q="SELECT lcm_org.id_org,name
			FROM lcm_client_org,lcm_org
			WHERE id_client=$client
				AND lcm_client_org.id_org=lcm_org.id_org";

		// Do the query
		$result = lcm_query($q);

		while ($row = lcm_fetch_array($result)) {
			echo '<tr><td><a href="org_det.php?org=' . $row['id_org'] . '" class="content_link">' . $row['name'] . "</a></td>\n<td>";
			if ($edit)
				echo '<a href="edit_org.php?org=' . $row['id_org'] . '" class="content_link">Edit</a>';
			echo "</td></tr>\n";
		}

		if ($edit)
			echo "<tr><td><a href=\"sel_org_cli.php?client=$client\" class=\"content_link\"><strong>Add organisation(s)</strong></a></td><td></td></tr>";

		?>
		</table><br>
		<?php

	} else die("There's no such client!");
} else die("Which client?");

lcm_page_end();
?>
