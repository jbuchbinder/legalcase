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
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

$case = intval($_GET['case']);

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
		if (!(($GLOBALS['author_session']['status'] = 'admin') || $row['public'] || allowed($case,'r'))) {
			die(_T('error_no_read_permission'));
		}
		$add = allowed($case,'w');
		$edit = ($GLOBALS['author_session']['status'] = 'admin') || allowed($case,'e');
		$admin = ($GLOBALS['author_session']['status'] = 'admin') || allowed($case,'a');

		// Show case details
		lcm_page_start(_T('case_details') . ": " . $row['title']);
		
		echo "<fieldset class='info_box'><div class='prefs_column_menu_head'>About this case</div><p class='normal_text'>";
		
		//Edit case link was here!
		
		echo "\n" . _T('case_id') . ": " . $row['id_case'] . "<br>\n";
		
		// Show users, assigned to the case
		echo _T('case_user_s') . ': ';
		$q = "SELECT id_case,lcm_author.id_author,name_first,name_middle,name_last
			FROM lcm_case_author,lcm_author
			WHERE (id_case=$case
				AND lcm_case_author.id_author=lcm_author.id_author)";
		// Do the query
		$authors = lcm_query($q);
		// Show the results
		
		//echo "<ul class=\"simple_list\">\n";
		
		while ($user = lcm_fetch_array($authors)) {
			if ($admin) echo '<a href="edit_auth.php?case=' . $case . '&amp;author=' . $user['id_author'] . '" class="content_link">';
			echo clean_output($user['name_first'] . ' ' . $user['name_middle'] . ' ' . $user['name_last']);
			if ($admin) echo '</a>';
			echo '; ';
		}
		
		//echo "</ul>";
		
		//Add user to the case link was here
		
		echo "<br />\n";
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
		echo "</p><br />\n";
		
		if ($edit)
			echo '&nbsp;<a href="edit_case.php?case=' . $row['id_case'] . '" class="edit_lnk">' . _T('edit_case_information') . '</a>';
		
		if ($admin) echo '&nbsp;<a href="sel_auth.php?case=' . $case . '" class="add_lnk">' . _T('add_user_case') . '</a>';
			
		echo "<br /><br /></fieldset>";
		
		echo "<fieldset class=\"info_box\"><div class=\"prefs_column_menu_head\">" . _T('case_clients') . "</div><p class=\"normal_text\">";
		
		//first table
		echo "<table border=\"0\" width=\"99%\">\n<tr>\n<td align=\"left\" valign=\"top\" width=\"50%\">";
		
		echo "\n\t\t<table border='0' class='tbl_usr_dtl'>\n";
		echo "<th class='heading'>" . _T('organisations'). ":</th><th class='heading'>&nbsp;</th>";

		// Show case organization(s)
		$q="SELECT lcm_org.id_org,name
			FROM lcm_case_client_org,lcm_org
			WHERE id_case=$case AND lcm_case_client_org.id_org=lcm_org.id_org";

		$result = lcm_query($q);

		while ($row = lcm_fetch_array($result)) {
			echo '<tr><td><a href="org_det.php?org=' . $row['id_org'] . '" class="content_link">' . clean_output($row['name']) . "</a></td>\n";
			if ($edit)
				echo '<td><a href="edit_org.php?org=' . $row['id_org'] . '" class="content_link">' . _T('edit') . '</a></td>';
			echo "</tr>\n";
		}
		
		echo "\t\t</table>";
		
		if ($add)
			echo "<br /><a href=\"sel_org.php?case=$case\" class=\"add_lnk\">" . _T('add_organisation_s') . "</a><br />";
		
		echo "</td>\n<td align=\"left\" valign=\"top\" width=\"50%\">";
		//second table
			
		echo "<table border='0' class='tbl_usr_dtl'>\n";
		echo "\t\t<th class='heading'>" . _T('clients') . ":</th>\n\t\t<th class='heading'>&nbsp;</th>";

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
				echo '<td><a href="edit_client.php?client=' . $row['id_client'] . '" class="content_link">' . _T('edit') . '</a></td>';
			echo "</tr>\n";
		}
		
		echo "\t\t</table>\n";
		
		if ($add)
			echo "<br /><a href=\"sel_client.php?case=$case\" class=\"add_lnk\">" . _T('add_client_s') . "</a><br />\n";

		echo "</td></tr></table>";

	} else die(_T('error_no_such_case'));
	
	echo "</p><br /></fieldset>";
	
	echo "<fieldset class=\"info_box\"><div class=\"prefs_column_menu_head\">" . _T('case_followups') . "</div><p class=\"normal_text\">\n";
	echo "\n\n\t\n\t<table border='0' class='tbl_usr_dtl' width='99%'>
	<tr><th class='heading'>" . _T('date') . "</th><th class='heading'>" . _T('type') . "</th><th class='heading'>" . _T('description') . "</th><th class='heading'>&nbsp;</th></tr>\n";

	// Prepare query
	$q = "SELECT id_followup,date_start,type,description
		FROM lcm_followup
		WHERE id_case=$case";

	// Do the query
	$result = lcm_query($q);

	// Process the output of the query
	while ($row = lcm_fetch_array($result)) {
		// Show followup
		echo '<tr><td>' . clean_output(date(_T('date_format_short'),strtotime($row['date_start']))) . '</td>';
		echo '<td>' . clean_output($row['type']) . '</td>';
		if (strlen($row['description'])<30) $short_description = $row['description'];
		else $short_description = substr($row['description'],0,30) . '...';
		echo '<td>' . clean_output($short_description) . '</td>';
		if ($edit)
			echo '<td><a href="edit_fu.php?followup=' . $row['id_followup'] . '" class="content_link">' . _T('Edit') . '</a></td>';
		echo "</tr>\n";
	}
	
	echo "\t</table>\n";
	
	if ($add)
		echo "<br /><a href=\"edit_fu.php?case=$case\" class=\"create_new_lnk\">" . _T('new_followup') . "</a><br /><br />\n";
	
	echo "</p></fieldset>";
		
	lcm_page_end();
} else {
	lcm_page_start(_T('title_error'));
	echo "<p>" . _T('error_no_case_specified') . "</p>\n";
	lcm_page_end();
}

?>
