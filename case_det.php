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

	$Id: case_det.php,v 1.86 2005/02/22 16:00:32 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Read parameters
$case = intval($_GET['case']);
$fu_order = "DESC";

// Read site configuration settings
$case_court_archive = read_meta('case_court_archive');
$case_assignment_date = read_meta('case_assignment_date');
$case_alledged_crime = read_meta('case_alledged_crime');
$case_allow_modif = read_meta('case_allow_modif');
$modify = ($case_allow_modif == 'yes');

if (isset($_GET['fu_order']))
	if ($_GET['fu_order'] == 'ASC' || $_GET['fu_order'] == 'DESC')
		$fu_order = clean_input($_GET['fu_order']);

if ($case > 0) {
	$q="SELECT id_case, title, id_court_archive, date_creation, date_assignment,
			legal_reason, alledged_crime, status, public, pub_write
		FROM lcm_case
		WHERE id_case=$case";

	$result = lcm_query($q);

	// Process the output of the query
	if ($row = lcm_fetch_array($result)) {

		// Check for access rights
		if (!(($GLOBALS['author_session']['status'] == 'admin') || $row['public'] || allowed($case,'r'))) {
			die(_T('error_no_read_permission'));
		}

		$add = allowed($case,'w');
		$edit = ($GLOBALS['author_session']['status'] == 'admin') || allowed($case,'e');
		$admin = ($GLOBALS['author_session']['status'] == 'admin') || allowed($case,'a');

		// Show case details
		lcm_page_start(_T('title_case_details') . " " . $row['title']);

		echo "<div id=\"breadcrumb\"><a href=\"". getenv("HTTP_REFERER") ."\">List of cases</a> &gt; ". $row['title'] ."</div>";

		// Show tabs
		$groups = array('general','clients','followups','attachments');
		$tab = ( isset($_GET['tab']) ? $_GET['tab'] : 0 );
		//show_tabs($groups,$tab,$_SERVER['REQUEST_URI']);
		show_tabs($groups,$tab,$_SERVER['SCRIPT_NAME']);

		switch ($tab) {
			//
			// General tab
			//
			case 0 :
				echo "<fieldset class='info_box'>";
				echo "<div class='prefs_column_menu_head'>" . _T('case_subtitle_general') . "</div>";
				echo "<p class='normal_text'>";
		
				// Edit case link was here!
		
				// [AG] Case ID irrelevant to the user
				//echo "\n" . _T('case_input_id') . " " . $row['id_case'] . "<br>\n";
		
				// Show users, assigned to the case
				// TODO: use case_input_authors if many authors
				echo _T('case_input_author') . ' ';
				$q = "SELECT id_case,lcm_author.id_author,name_first,name_middle,name_last
					FROM lcm_case_author,lcm_author
					WHERE (id_case=$case
						AND lcm_case_author.id_author=lcm_author.id_author)";
		
				$authors = lcm_query($q);
		
				// Show the results
				//echo "<ul class=\"simple_list\">\n";
		
				$q = '';
				while ($user = lcm_fetch_array($authors)) {
					if ($q) $q .= "; \n";
					if ($admin) $q .= '<a href="edit_auth.php?case=' . $case . '&amp;author=' . $user['id_author'] . '" class="content_link">';
					$q .= clean_output($user['name_first'] . ' ' . $user['name_middle'] . ' ' . $user['name_last']);
					if ($admin) $q .= '</a>';
				}
				echo "$q<br />\n";
		
				//echo "</ul>";
		
				// Add user to the case link was here
		
				if ($case_court_archive == 'yes')
					echo _T('case_input_court_archive') . ' ' . clean_output($row['id_court_archive']) . "<br>\n";
				echo _T('case_input_date_creation') . ' ' . format_date($row['date_creation']) . "<br>\n";
		
				if ($case_assignment_date == 'yes') {
					// [ML] FIXME: Not very clear how this should work
					if ($row['date_assignment'])
						echo _T('case_input_date_assigned') . ' ' .  format_date($row['date_assignment']) . "<br>\n";
					else
						echo _T('case_input_date_assigned') . ' ' . "Click to assign (?)<br/>\n";
				}
		
				echo _T('case_input_legal_reason') . ' ' . clean_output($row['legal_reason']) . "<br>\n";
				if ($case_alledged_crime == 'yes')
					echo _T('case_input_alledged_crime') . ' ' . clean_output($row['alledged_crime']) . "<br>\n";
		
				// Show case status
				if ($edit) {
					// Change status form
					echo "<form action='set_case_status.php' method='GET'>\n";
					echo "\t" . _T('case_input_status') . "&nbsp;";
					echo "<input type='hidden' name='case' value='$case'>\n";
					echo "\t<select name='status' class='sel_frm'>\n";
					$statuses = array('draft','open','suspended','closed','merged');
					foreach ($statuses as $s)
						echo "\t\t<option" .  (($s == $row['status']) ? ' selected' : '') . ">$s</option>\n";
					echo "\t</select>\n";
					echo "\t<button type='submit' name='submit' value='set_status' class='simple_form_btn'>" . _T('button_validate') . "</button>\n";
					echo "</form>\n";
				} else {
					echo _T('case_input_status') . "&nbsp;" . clean_output($row['status']) . "<br>\n";
				}
		
				echo _T('public') . ': ' . _T('Read') . '=';
				echo ($row['public'] ? 'Yes' : 'No');
				echo ', ' . _T('Write') . '=';
				echo ($row['pub_write'] ? 'Yes' : 'No');
				echo "</p><br /><br />\n";
		
				if ($edit && $modify)
					echo '&nbsp;<a href="edit_case.php?case=' . $row['id_case'] . '" class="edit_lnk">' . _T('edit_case_information') . '</a>';
		
				if ($admin) echo '&nbsp;<a href="sel_auth.php?case=' . $case . '" class="add_lnk">' . _T('add_user_case') . '</a>';
		
				echo "<br /><br />\n";
				echo "</fieldset>\n";

				break;
			//
			// Case clients / organisations
			//
			case 1 :
				//
				// Main table for attached organisations and clients
				//
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('case_subtitle_clients') . '</div>';
				echo '<p class="normal_text">';
		
				//echo '<table border="1" width="99%">' . "\n";
				//echo '<tr><td align="left" valign="top" width="50%">' . "\n";
		
				//
				// Show case client(s)
				//
				$html_show = false;
				$html = '<table border="0" width="99%" class="tbl_usr_dtl">' . "\n";
				//$html .= "<tr>\n";
				/*
				$html .= '<th class="heading" colspan="3">' . _T('case_input_clients') . '</th>';
				$html .= '</tr>' . "\n";
				*/
		
				$q="SELECT cl.id_client, cl.name_first, cl.name_middle, cl.name_last
					FROM lcm_case_client_org as clo, lcm_client as cl
					WHERE id_case = $case AND clo.id_client = cl.id_client";
		
				$result = lcm_query($q);
		
				while ($row = lcm_fetch_array($result)) {
					$html .= "<tr>\n";
					$html .= '<td width="25" align="center"><img src="images/jimmac/stock_person.png" alt="" height="16" width="16" /></td>' . "\n";
					$html .= '<td><a href="client_det.php?client=' . $row['id_client'] . '" class="content_link">';
					$html .=  clean_output($row['name_first'] . ' ' . $row['name_middle'] . ' ' .$row['name_last']);
					$html .= "</a></td>\n";
		
					if ($edit)
						$html .= '<td><a href="edit_client.php?client=' . $row['id_client'] . '" class="content_link">' . _T('edit') . '</a></td>' . "\n";
		
					$html .= "</tr>\n";
					$html_show = true;
				}
		
				//
				// Show case organization(s)
				//
				$q="SELECT lcm_org.id_org,name
					FROM lcm_case_client_org,lcm_org
					WHERE id_case=$case AND lcm_case_client_org.id_org=lcm_org.id_org";
		
				$result = lcm_query($q);
		
				while ($row = lcm_fetch_array($result)) {
					$html .= "<tr>\n";
					$html .= '<td width="25" align="center"><img src="images/jimmac/stock_people.png" alt="" height="16" width="16" /></td>' . "\n";
					$html .= '<td><a href="org_det.php?org=' . $row['id_org'] . '" class="content_link">';
					$html .= clean_output($row['name']);
					$html .= "</a></td>\n";
		
					if ($edit)
						$html .= '<td><a href="edit_org.php?org=' . $row['id_org'] . '" class="content_link">' . _T('edit') . '</a></td>' . "\n";
		
					$html .= "</tr>\n";
					$html_show = true;
				}
		
				$html .= "</table>\n\n";
		
				if ($html_show)
					echo $html;
		
				if ($add) {
					echo "<br /><a href=\"sel_client.php?case=$case\" class=\"add_lnk\">" . _T('case_button_add_client') . "</a>\n";
					echo "<a href=\"sel_org.php?case=$case\" class=\"add_lnk\">" . _T('case_button_add_org') . "</a><br /><br />";
				}
		
				//echo "</td></tr></table>\n\n";
				
				echo "</fieldset>";
				
				break;
			//
			// Case followups
			//
			case 2 :
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('case_subtitle_followups') . '</div>';
				echo "<p class=\"normal_text\">\n";
			
				echo "\n\n\t\n\t<table border='0' class='tbl_usr_dtl' width='99%'>
					<tr><th class='heading'>";
				switch ($fu_order) {
					case 'ASC':
						echo "<a href='case_det.php?case=$case&amp;fu_order=DESC' class='content_link'>" . _T('date') . '</a> <img src="images/lcm/asc_desc_arrow.gif" width="9" height="11" alt="" />';
						break;
					case 'DESC':
						echo "<a href='case_det.php?case=$case&amp;fu_order=ASC' class='content_link'>" . _T('date') . '</a> <img src="images/lcm/desc_asc_arrow.gif" width="9" height="11" alt="" />';
						break;
					default:
						echo "<a href='case_det.php?case=$case&amp;fu_order=DESC' class='content_link'>" . _T('date') . '</a> <img src="images/lcm/asc_desc_arrow.gif" width="9" height="11" alt="" />';
				}
			//	echo _T('date') .
				echo "</th>";
				echo "<th class='heading'>" . _T('type') . "</th>";
				echo "<th class='heading'>" . _T('time') . "</th>";
				echo "<th class='heading'>" . _T('description') . "</th>";
				echo "<th class='heading'>&nbsp;</th></tr>\n";
			
				// Prepare query
				$q = "SELECT id_followup,date_start,date_end,type,description
					FROM lcm_followup
					WHERE id_case=$case";
			
				// Add ordering
				if ($fu_order) $q .= " ORDER BY date_start $fu_order";
			
				// Do the query
				$result = lcm_query($q);
			
				// Set the length of short followup title
				$title_length = (($prefs['screen'] == "wide") ? 48 : 115);
			
				// Process the output of the query
				while ($row = lcm_fetch_array($result)) {
					// Show followup
					echo '<tr><td>' . format_date($row['date_start'], 'short') . '</td>';
					echo '<td>' . _T('kw_followups_' . $row['type'] . '_title') . '</td>';
					
					// Time
					echo '<td>';
					$fu_date_end = vider_date($row['date_end']);
					$fu_time = ($fu_date_end ? strtotime($row['date_end']) - strtotime($row['date_start']) : 0);
					
					$fu_days = (int) ($fu_time / 86400);
					$fu_hours = (int) ( ($fu_time % 86400) / 3600);
					$fu_minutes = (int) ( ($fu_time % 3600) / 60);
			
					echo ($fu_days ? $fu_days . 'd ' : '');
					echo ($fu_hours ? $fu_hours . 'h ' : '');
					echo ($fu_minutes ? $fu_minutes . 'm' : '');
			
					echo '</td>';
			
					// Description
					if (strlen(lcm_utf8_decode($row['description'])) < $title_length) 
						$short_description = $row['description'];
					else
						$short_description = substr($row['description'],0,$title_length) . '...';
			
					echo '<td><a href="fu_det.php?followup=' . $row['id_followup'] . '" class="content_link">' . clean_output($short_description) . '</a></td>';
			
					if ($edit)
						echo '<td><a href="edit_fu.php?followup=' . $row['id_followup'] . '" class="content_link">' . _T('Edit') . '</a></td>';
					echo "</tr>\n";
				}
			
				echo "\t</table>\n";
			
				if ($add)
					echo "<br /><a href=\"edit_fu.php?case=$case\" class=\"create_new_lnk\">" . _T('new_followup') . "</a><br /><br />\n";
			
				echo "</p></fieldset>";
				
				break;
			//
			// Case attachments
			//
			case 3 :
				// Attach new file form
				if ($add) {
					echo '<fieldset class="info_box">';
					echo '<form enctype="multipart/form-data" action="attach_file.php" method="post">' . "\n";
					echo "<input type=\"hidden\" name=\"case\" value=\"$case\" />\n";
					echo '<input type="hidden" name="MAX_FILE_SIZE" value="300000" />' . "\n";
					echo '<strong>Filename:</strong><br /><input type="file" name="filename" size="40" />' . "\n";
					echo "<br /><br />\n";
					echo '<strong>Description:</strong><br /><input type="text" name="description" class="search_form_txt" /><br /><br />' . "\n";
					echo '<input type="submit" name="submit" value="Attach" class="search_form_btn" />' . "\n";
					echo "</form>\n";
					echo '</fieldset>';
				}

				// List of attached files
				$q = "SELECT * FROM lcm_case_attachment WHERE id_case=$case";
				$result = lcm_query($q);
				$i = lcm_num_rows($result);
				if ($i > 0) {
					echo "<table border='0' align='center' class='tbl_usr_dtl' width='99%'>\n";
					echo "\t<tr><th class=\"heading\">Filename</th>
						<th class=\"heading\">Type</th>
						<th class=\"heading\">Size</th>
						<th class=\"heading\">Description</th></tr>\n";
					for ($i=0 ; $row = lcm_fetch_array($result) ; $i++) {
						echo "\t<tr>";
						echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">'
							. '<a href="view_file.php?file_id=' . $row['id_attachment'] . '" class="content_link">'
							. $row['filename'] . '</a></td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . $row['type'] . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . $row['size'] . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . clean_output($row['description']) . '</td>';
						echo "</tr>\n";
					}
					echo "</table>\n";
				}

				break;
		}
	} else die(_T('error_no_such_case'));

	lcm_page_end();
} else {
	lcm_page_start(_T('title_error'));
	echo "<p>" . _T('error_no_case_specified') . "</p>\n";
	lcm_page_end();
}

?>
