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

	$Id: org_det.php,v 1.16 2005/03/23 22:24:43 antzi Exp $
*/

include('inc/inc.php');
include('inc/inc_acc.php');

$org = (isset($_REQUEST['org']) ? intval($_REQUEST['org']) : 0);

if ($org <= 0)
	die("Which organisation?");	// TRAD

$q = "SELECT *
		FROM lcm_org
		WHERE lcm_org.id_org=$org";

$result = lcm_query($q);

if ($row = lcm_fetch_array($result)) {
	lcm_page_start("Organisation: " . $row['name']);	// TRAD

	/* Saved for future use
	// Check for access rights
	if (!($row['public'] || allowed($case,'r'))) {
		die("You don't have permission to view this case!");
	}
	$edit = allowed($case,'w');
	*/
	$edit = true;

	// Show tabs
	$groups = array(
				'general' => _T('org_tab_general'),
				'representatives' => _T('org_tab_representatives'),
				'cases' => _T('org_tab_cases'),
				'attachments' => _T('org_tab_attachments'));

	$tab = ( isset($_GET['tab']) ? $_GET['tab'] : 'general' );
	show_tabs($groups,$tab,$_SERVER['REQUEST_URI']);

	switch ($tab) {
		//
		// Show organisation general information
		//
		case 'general':
			echo '<fieldset class="info_box">';
			echo '<div class="prefs_column_menu_head">' . _T('org_subtitle_view_general') . "</div>\n";
			echo '<p class="normal_text">';
		
			//		echo "\n<br />Organisation ID: " . $row['id_org'] . "<br />\n";
			//		echo 'Organisation name: ' . $row['name'] . "<br />\n";
			echo 'Address: ' . $row['address'] . "<br />\n"; // TRAD
			echo _Ti('time_input_date_created') . format_date($row['date_creation'], 'short') . "<br />\n";
			// [ML] echo 'Last update: ' . format_date($row['date_update'], 'short') . "<br />\n";
		
			if ($edit)
				echo '<br /><a href="edit_org.php?org=' . $row['id_org'] . '" class="edit_lnk">Edit organisation information</a><br />'; // TRAD
		
			echo "<br /></p>\n";
			echo "</fieldset>\n";

			break;

		//
		// Show organisation representatives
		//
		case 'representatives' :
			echo '<fieldset class="info_box">';
			echo '<div class="prefs_column_menu_head">' . _T('org_subtitle_representatives') . "</div><br />\n";

			// Show organisation representative(s)
			$q = "SELECT cl.id_client, name_first, name_middle, name_last
					FROM lcm_client_org as clo, lcm_client as cl
					WHERE id_org = $org 
						AND clo.id_client = cl.id_client";
		
			$result = lcm_query($q);
			$show_table = false;
		
			if (lcm_num_rows($result)) {
				$show_table = true;
		?>
				<table class="tbl_usr_dtl">
				<tr>
					<th class="heading"><?php echo "Representative(s):"; /* TRAD */ ?></th>
				</tr>
		<?php
			}
		
			while ($row = lcm_fetch_array($result)) {
				echo '<tr><td><a href="client_det.php?client=' . $row['id_client'] . '" class="content_link">';
				echo get_person_name($row) . "</a></td></tr>\n";
			}
		
			if ($show_table)
				echo "</table>";
		
			if ($edit)
				echo "<br /><a href=\"sel_cli_org.php?org=$org\" class=\"add_lnk\">Add representative(s)</a><br />"; // TRAD
		
			echo "<br /></fieldset>";

			break;

		//
		// Show recent cases
		//
		case 'cases':

			$q = "SELECT clo.id_case, c.title, c.date_creation, c.id_court_archive, c.status
					FROM lcm_case_client_org as clo, lcm_case as c
					WHERE id_org = $org
					AND clo.id_case = c.id_case ";

			// Sort cases by creation date
			$case_order = 'DESC';
			if (isset($_REQUEST['case_order']))
				if ($_REQUEST['case_order'] == 'ASC' || $_REQUEST['case_order'] == 'DESC')
					$case_order = $_REQUEST['case_order'];

			$q .= " ORDER BY c.date_creation " . $case_order;

			$result = lcm_query($q);
			$number_of_rows = lcm_num_rows($result);
			$list_pos = 0;

			if (isset($_REQUEST['list_pos']))
				$list_pos = $_REQUEST['list_pos'];

			if ($list_pos >= $number_of_rows)
				$list_pos = 0;

			// Position to the page info start
			if ($list_pos > 0)
				if (!lcm_data_seek($result,$list_pos))
					lcm_panic("Error seeking position $list_pos in the result");

			if (lcm_num_rows($result)) {
				echo '<fieldset class="info_box">' . "\n";
				echo '<div class="prefs_column_menu_head">' . _T('org_subtitle_cases') . "</div>\n";
				show_listcase_start();

				for ($cpt = 0; $row1 = lcm_fetch_array($result); $cpt++) {
					show_listcase_item($row1, $cpt);
				}

				show_listcase_end($list_pos, $number_of_rows);
				echo "</fieldset>\n";
			}

			break;
		//
		// Organisation attachments
		//
		case 'attachments' :
			echo '<fieldset class="info_box">';
			echo '<div class="prefs_column_menu_head">' . _T('org_subtitle_attachments') . '</div>';
			echo "<p class=\"normal_text\">\n";

			// List of attached files
			show_attachments_list('org', $org);

			// Attach new file form
			if ($edit)
				show_attachments_upload('org', $org);

			echo '</fieldset>';

			break;

	}

	// Show this in all tabs
	echo '<p>';
	echo '<a href="edit_case.php?case=0&amp;attach_org=' . $row['id_org'] . '" class="create_new_lnk">';
	echo "Open new case involving this organisation"; // TRAD
	echo "</a>";
	echo "</p>\n";

} else die("There's no such organisation!");

lcm_page_end();

?>
