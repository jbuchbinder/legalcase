<?php

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

if ($case > 0) {
	$q="SELECT id_case, title, id_court_archive, FROM_UNIXTIME(date_creation),
			FROM_UNIXTIME(date_assignment), legal_reason, alledged_crime,
			status, public, pub_write
		FROM lcm_case
		WHERE id_case=$case";

	$result = lcm_query($q);

	// Process the output of the query
	if ($row = lcm_fetch_array($result)) {

		// Check for access rights
		if (!($row['public'] || allowed($case,'r'))) {
			die(_T('error_no_read_permission'));
		}
		$add = allowed($case,'w');
		$edit = allowed($case,'e');
		$admin = allowed($case,'a');

		// Show case details
		lcm_page_start(_T('case_details') . ": " . $row['title']);

		if ($edit)
			echo ' [<a href="edit_case.php?case=' . $row['id_case'] . '">' . _T('edit_case_information') . '</a>]';
		echo "<br>\n" . _T('case_id') . ": " . $row['id_case'] . "<br>\n";

		// Show users, assigned to the case
		echo _T('case_user_s') . ': ';
		$q = "SELECT id_case,lcm_author.id_author,name_first,name_middle,name_last
			FROM lcm_case_author,lcm_author
			WHERE (id_case=$case
				AND lcm_case_author.id_author=lcm_author.id_author)";
		// Do the query
		$authors = lcm_query($q);
		// Show the results
		while ($user = lcm_fetch_array($authors)) {
			if ($admin) echo '<a href="edit_auth.php?case=' . $case . '&amp;author=' . $user['id_author'] . '">';
			echo clean_output($user['name_first'] . ' ' . $user['name_middle'] . ' ' . $user['name_last']);
			if ($admin) echo '</a>';
			echo '; ';
		}
		if ($admin) echo '[<a href="sel_auth.php?case=' . $case . '">' . _T('add_user_case') . '</a>]';
		echo "<br>\n";
		echo _T('court_archive_id') . ': ' . clean_output($row['id_court_archive']) . "<br>\n";
		echo _T('creation_date') . ': ' . format_date($row['date_creation']) . "<br>\n";

		// [ML] FIXME: Not very clear how this should work
		if ($row['date_assignment'])
			echo _T('assignment_date') . ': ' .  format_date($row['date_assignment']) . "<br>\n";
		else
			echo _T('assignment_date') . _T('typo_column') . ' ' . "Click to assign (?)<br/>\n";

		echo _T('legal_reason') . ': ' . clean_output($row['legal_reason']) . "<br>\n";
		echo _T('alledged_crime') . ': ' . clean_output($row['alledged_crime']) . "<br>\n";
		echo _T('status') . ': ' . clean_output($row['status']) . "<br>\n";
		echo _T('public') . ': ' . _T('Read') . '=';
		if ($row['public']) echo 'Yes';
		else echo 'No';
		echo ', ' . _T('Write') . '=';
		if ($row['pub_write']) echo 'Yes';
		else echo 'No';
		echo "<br>\n";

		echo '<h3>' . _T('case_clients') . ':</h3>';
		echo "\n\t\t<table border='1'>\n";
		echo '<caption>' . _T('organisations'). ':</caption>';

		// Show case organization(s)
		$q="SELECT lcm_org.id_org,name
			FROM lcm_case_client_org,lcm_org
			WHERE id_case=$case AND lcm_case_client_org.id_org=lcm_org.id_org";

		$result = lcm_query($q);

		while ($row = lcm_fetch_array($result)) {
			echo '<tr><td><a href="org_det.php?org=' . $row['id_org'] . '">' . clean_output($row['name']) . "</a></td>\n";
			if ($edit)
				echo '<td><a href="edit_org.php?org=' . $row['id_org'] . '">' . _T('edit') . '</a></td>';
			echo "</tr>\n";
		}

		if ($add)
			echo "<tr><td><a href=\"sel_org.php?case=$case\">" . _T('add_organisation_s') . "</a></td><td></td></tr>";

		echo "\t\t</table><br>\n\n\t\t<table border>\n";
		echo "\t\t<caption>" . _T('clients') . ":</caption>\n";

		// Show case client(s)
		$q="SELECT lcm_client.id_client,name_first,name_middle,name_last
			FROM lcm_case_client_org,lcm_client
			WHERE id_case=$case AND lcm_case_client_org.id_client=lcm_client.id_client";

		$result = lcm_query($q);

		while ($row = lcm_fetch_array($result)) {
			echo '<tr><td>';
			echo  clean_output($row['name_first'] . ' ' . $row['name_middle'] . ' ' .$row['name_last']);
			echo "</td>\n";
			if ($edit)
				echo '<td><a href="edit_client.php?client=' . $row['id_client'] . '">' . _T('edit') . '</a></td>';
			echo "</tr>\n";
		}
		if ($add)
			echo "<tr><td><a href=\"sel_client.php?case=$case\">" . _T('add_client_s') . "</a></td><td></td></tr>\n";

		echo "\t\t</table><br>\n";

	} else die(_T('error_no_such_case'));

	echo "\n\n<br/>\n";
	echo "\n<form name='frm_find_fu' class='search_form' action='case_det.php?case=$case' method='post'>Find followup:&nbsp;<input type='text' name='find_fu_string' size='10' class='search_form_txt' value='$find_fu_string' />\n";

	echo "\n<table border='0' width='99%' align='center' class='tbl_data'>
	<caption>" . (strlen($find_fu_string)>1 ? _T('case_followups') : _T('case_followups_containing') . $find_fu_string) . ":</caption>
	<tr><th class='tbl_head'>" . _T('date') . "</th>
		<th class='tbl_head'>" . _T('type') . "</th>
		<th class='tbl_head'>" . _T('description') . "</th>
		<th class='tbl_head'></th></tr>\n";

	// Prepare query
	$q = "SELECT id_followup,date_start,type,description
		FROM lcm_followup
		WHERE (id_case=$case";

	if (strlen($find_fu_string)>1) {
		$q .= " AND ((date_start LIKE '%$find_fu_string%')
					OR (type LIKE '%$find_fu_string%')
					OR (description LIKE '%$find_fu_string%'))";
	}

	$q .= ")";

	// Do the query
	$result = lcm_query($q);

	// Process the output of the query
	for ($i = 0 ; ($row = lcm_fetch_array($result)) ; $i++) {
		// Show followup
		echo "\t<tr><td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
		echo highlight_matches(clean_output(date(_T('date_format_short'),strtotime($row['date_start'])))) . "</td>\n";
		echo "\t\t<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
		echo highlight_matches(clean_output($row['type'])) . "</td>\n";
		if (strlen($row['description'])<30) $short_description = $row['description'];
		else $short_description = substr($row['description'],0,30) . '...';
		echo "\t\t<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
		echo highlight_matches(clean_output($short_description)) . "</td>\n";
		if ($edit)
			echo "\t\t<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>"
			. '<a href="edit_fu.php?followup=' . $row['id_followup'] . '">' . _T('Edit') . "</a></td>\n";
		echo "\t</tr>\n";
	}
	if ($add)
		echo "<tr><td colspan=\"3\" class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>"
		. "<a href=\"edit_fu.php?case=$case\">" . _T('new_followup') . "</a></td>\n\t<td></td>\n</tr>\n";

	echo "</table>\n";

	lcm_page_end();
} else {
	lcm_page_start(_T('title_error'));
	echo "<p>" . _T('error_no_case_specified') . "</p>\n";
	lcm_page_end();
}

?>
