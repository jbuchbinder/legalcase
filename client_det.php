<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2005 Free Software Foundation, Inc.

	This program is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published by the
	Free Software Foundation; either version 2 of the License, or (at your
	option) any later version.

	This program is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
	or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
	for more details.

	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
	59 Temple Place, Suite 330, Boston, MA  02111-1307, USA

	$Id: client_det.php,v 1.6 2004/11/23 12:53:03 mlutfy Exp $
*/

include('inc/inc.php');
include('inc/inc_acc.php');


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

		if ($row['gender'] == 'male' || $row['gender'] == 'female')
			$gender = _T('person_gender_' . $row['gender']);
		else
			$gender = _T('info_not_available');


		// [ML] TODO: Show as a list with UL + LI without bullets (accessibility)

		// Show client details
		lcm_page_start("Client: " . $row['name_first'] . ' ' .  $row['name_middle'] . ' ' . $row['name_last']);
		echo '<p class="normal_text">';
		echo 'Client ID: ' . $row['id_client'] . "<br/>\n";
		echo 'Gender: ' . $gender . "<br/>\n";
		echo 'Citizen number: ' . $row['citizen_number'] . "<br/>\n";
		echo 'Address: ' . $row['address'] . "<br/>\n";
		echo 'Civil status: ' . $row['civil_status'] . "<br/>\n";
		echo 'Income: ' . $row['income'] . "<br/>\n";
		echo 'Creation date: ' . format_date($row['date_creation']) . "<br/>\n";
		// [ML] echo 'Last update date: ' . $row['date_update'] . "<br/>\n";
		echo "</p>\n";

		if ($edit)
			echo '<p class="normal_text">[<a href="edit_client.php?client=' . $row['id_client'] .  '" class="content_link"><strong>Edit client information</strong></a>]</p>' . "\n";

		?>
		
		<h3>Organisation(s) represented by this client:</h3>
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
