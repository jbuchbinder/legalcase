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

	$Id: case_det.php,v 1.99 2005/03/06 14:35:10 antzi Exp $
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
			legal_reason, alledged_crime, status, stage, public, pub_write
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
		$groups = array('general' => _T('case_tab_general'),
				'clients' => _T('case_tab_clients'),
				'appointments' => _T('case_tab_appointments'),
				'followups' => _T('case_tab_followups'),
				'times' => _T('case_tab_times'),
				'attachments' => _T('case_tab_attachments'));
		$tab = ( isset($_GET['tab']) ? $_GET['tab'] : 'general' );
		show_tabs($groups,$tab,$_SERVER['REQUEST_URI']);

		switch ($tab) {
			//
			// General tab
			//
			case 'general' :
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
					echo "<form action='set_case_status.php' method='get'>\n";
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

				// Show case stage
				if ($edit) {
					// Change stage form
					echo "<form action='set_case_stage.php' method='get'>\n";
					echo "\t" . _T('case_input_stage') . "&nbsp;";
					echo "<input type='hidden' name='case' value='$case'>\n";
					echo "\t<select name='stage' class='sel_frm'>\n";

					global $system_kwg;

					foreach($system_kwg['stage']['keywords'] as $kw) {
						$sel = ($kw['name'] == $row['stage'] ? ' selected="selected"' : '');
						echo "\t\t<option value='" . $kw['name'] . "'" . "$sel>" . _T($kw['title']) . "</option>\n";
					}
					echo "\t</select>\n";
					echo "\t<button type='submit' name='submit' value='set_stage' class='simple_form_btn'>" . _T('button_validate') . "</button>\n";
					echo "</form>\n";
				} else {
					echo _T('case_input_stage') . "&nbsp;" . clean_output($row['stage']) . "<br>\n";
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
			case 'clients' :
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
			// Case appointments
			//
			case 'appointments' :
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('case_subtitle_appointments') . '</div>';
				echo "<p class=\"normal_text\">\n";

				$q = "SELECT *
					FROM lcm_app
					WHERE lcm_app.id_case=$case";
				$result = lcm_query($q);
				
				// Get the number of rows in the result
				$number_of_rows = lcm_num_rows($result);
				if ($number_of_rows) {
					echo "<table border='0' align='center' class='tbl_usr_dtl' width='99%'>\n";
					echo "\t<tr>";
					echo '<th class="heading">Start time</th>';
					echo '<th class="heading">' . ( ($prefs['time_intervals'] == 'absolute') ? 'End time' : 'Duration' ) . '</th>';
					echo '<th class="heading">Type</th>';
					echo '<th class="heading">Title</th>';
					echo '<th class="heading">Reminder</th>';
					echo '<th class="heading">Action</th>';
					echo "</tr>\n";
				
					// Check for correct start position of the list
					$list_pos = 0;
					
					if (isset($_REQUEST['list_pos']))
						$list_pos = $_REQUEST['list_pos'];
					
					if ($list_pos>=$number_of_rows) $list_pos = 0;
					
					// Position to the page info start
					if ($list_pos>0)
						if (!lcm_data_seek($result,$list_pos))
							die("Error seeking position $list_pos in the result");
					
					// Show page of the list
					for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
						echo "\t<tr>";
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
							. date('d.m.y H:i',strtotime($row['start_time'])) . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
							. ( ($prefs['time_intervals'] == 'absolute') ?
								date('d.m.y H:i',strtotime($row['end_time'])) :
								format_time_interval(strtotime($row['end_time']) - strtotime($row['start_time']),
											($prefs['time_intervals_notation'] == 'hours_only') )
							) . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">' . $row['type'] . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
							. '<a href="app_det.php?app=' . $row['id_app'] . '">' . $row['title'] . '</a></td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
							. date('d.m.y H:i',strtotime($row['reminder'])) . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
							. '<a href="edit_app.php?app=' . $row['id_app'] . '">' . _T('edit') . '</a></td>';
						echo "</tr>\n";
					}
					
					echo "</table>\n\n";
				
					if ($number_of_rows>$prefs['page_rows']) {
						echo '<table border="0" align="center" width="99%" class="page_numbers">
					<tr><td align="left" width="15%">';
				
						// Show link to previous page
						if ($list_pos>0) {
							echo "<a href=\"case_det.php?case=$case&amp;tab=appointments&amp;list_pos=";
							echo ( ($list_pos>$prefs['page_rows']) ? ($list_pos - $prefs['page_rows']) : 0);
							if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
							echo '" class="content_link">< Prev</a> ';
						}
				
						echo "</td>\n\t\t<td align='center' width='70%'>";
				
						// Show page numbers with direct links
						$list_pages = ceil($number_of_rows / $prefs['page_rows']);
						if ($list_pages>1) {
							echo 'Go to page: ';
							for ($i=0 ; $i<$list_pages ; $i++) {
								if ($i==floor($list_pos / $prefs['page_rows'])) echo '[' . ($i+1) . '] ';
								else {
									echo "<a href=\"case_det.php?case=$case&amp;tab=appointments&amp;list_pos="
										. ($i*$prefs['page_rows']);
									if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
									echo '" class="content_link">' . ($i+1) . '</a> ';
								}
							}
						}
						
						echo "</td>\n\t\t<td align='right' width='15%'>";
						
						// Show link to next page
						$next_pos = $list_pos + $prefs['page_rows'];
						if ($next_pos<$number_of_rows) {
							echo "<a href=\"case_det.php?case=$case&amp;tab=appointments&amp;list_pos=$next_pos";
							if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
							echo '" class="content_link">Next ></a>';
						}
						
						echo "</td>\n\t</tr>\n</table>\n";
					}
				
				}

				echo "<br /><a href=\"edit_app.php?case=$case&amp;app=0\" class=\"create_new_lnk\">New appointment</a><br /><br />\n";

				break;
			//
			// Case followups
			//
			case 'followups' :
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('case_subtitle_followups') . '</div>';
				echo "<p class=\"normal_text\">\n";
			
				echo "\t<table border='0' class='tbl_usr_dtl' width='99%'>\n";
				echo "\t\t<tr><th class='heading'>";
				switch ($fu_order) {
					case 'ASC':
						echo "<a href='case_det.php?case=$case&amp;fu_order=DESC&amp;tab=followups' class='content_link'>" . _T('date_start') . '</a> <img src="images/lcm/asc_desc_arrow.gif" width="9" height="11" alt="" />';
						break;
					case 'DESC':
						echo "<a href='case_det.php?case=$case&amp;fu_order=ASC&amp;tab=followups' class='content_link'>" . _T('date_start') . '</a> <img src="images/lcm/desc_asc_arrow.gif" width="9" height="11" alt="" />';
						break;
					default:
						echo "<a href='case_det.php?case=$case&amp;fu_order=DESC&amp;tab=followups' class='content_link'>" . _T('date_start') . '</a> <img src="images/lcm/asc_desc_arrow.gif" width="9" height="11" alt="" />';
				}
			//	echo _T('date') .
				echo "</th>";
				echo "<th class='heading'>" . 'Author' . "</th>";
				echo "<th class='heading'>"
					. _T( (($prefs['time_intervals'] == 'absolute') ? 'date_end' : 'time_length') ) . "</th>";
				echo "<th class='heading'>" . _T('type') . "</th>";
				echo "<th class='heading'>" . _T('description') . "</th>";
				echo "<th class='heading'>&nbsp;</th></tr>\n";
			
				// Prepare query
				$q = "SELECT	lcm_followup.id_followup,
						lcm_followup.date_start,
						lcm_followup.date_end,
						lcm_followup.type,
						lcm_followup.description,
						lcm_author.name_first,
						lcm_author.name_middle,
						lcm_author.name_last
					FROM lcm_followup, lcm_author
					WHERE id_case=$case AND lcm_followup.id_author=lcm_author.id_author";
			
				// Add ordering
				if ($fu_order) $q .= " ORDER BY date_start $fu_order";
			
				// Do the query
				$result = lcm_query($q);
			
				// Set the length of short followup title
				$title_length = (($prefs['screen'] == "wide") ? 48 : 115);
			
				// Process the output of the query
				while ($row = lcm_fetch_array($result)) {
					echo "\t\t";
					
					// Start date
					echo '<tr><td>' . format_date($row['date_start'], 'short') . '</td>';
					
					// Author initials
					echo '<td>';
					echo substr($row['name_first'],0,1);
					echo substr($row['name_middle'],0,1);
					echo substr($row['name_last'],0,1);
					echo '</td>';
					
					// Time
					echo '<td>';
					$fu_date_end = vider_date($row['date_end']);
					if ($prefs['time_intervals'] == 'absolute') {
						if ($fu_date_end) echo format_date($row['date_end'],'short');
					} else {
						$fu_time = ($fu_date_end ? strtotime($row['date_end']) - strtotime($row['date_start']) : 0);
						echo format_time_interval($fu_time,($prefs['time_intervals_notation'] == 'hours_only'));
					}
					echo '</td>';

					// Type
					echo '<td>' . _T('kw_followups_' . $row['type'] . '_title') . '</td>';
			
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
			// Time spent on case by authors
			//
			case 'times' :
				// Get the information from database
/*
				// List followup authors, which are on the case
				$q = "SELECT	name_first,
						name_middle,
						name_last,
						sum(UNIX_TIMESTAMP(lcm_followup.date_end)-UNIX_TIMESTAMP(lcm_followup.date_start)) as time
					FROM	lcm_case_author,
						lcm_author,
						lcm_followup
					WHERE	lcm_case_author.id_author=lcm_author.id_author
						AND lcm_case_author.id_case=$case
						AND lcm_case_author.id_case=lcm_followup.id_case
						AND lcm_case_author.id_author=lcm_followup.id_author
						AND UNIX_TIMESTAMP(lcm_followup.date_end) > 0
					GROUP BY lcm_case_author.id_author";
*/
				// List all followup authors
				$q = "SELECT	name_first,
						name_middle,
						name_last,
						sum(IF(UNIX_TIMESTAMP(lcm_followup.date_end) > 0,
							UNIX_TIMESTAMP(lcm_followup.date_end)-UNIX_TIMESTAMP(lcm_followup.date_start),
							0)) as time
					FROM	lcm_author,
						lcm_followup
					WHERE	lcm_followup.id_author=lcm_author.id_author
						AND lcm_followup.id_case=$case
					GROUP BY lcm_followup.id_author";
				$result = lcm_query($q);

				// Show table headers
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('case_subtitle_times') . '</div>';
				echo "<p class=\"normal_text\">\n";
			
				echo "\n\n\t<table border='0' class='tbl_usr_dtl' width='99%'>";
				echo "\t\t<tr>";
				echo "<th class='heading'>" . 'Author' . "</th>";
				echo "<th class='heading'>" . 'Time spent on the case' . "</th>";
				echo "</tr>\n";

				// Show table contents & calculate total
				$total_time = 0;
				while ($row = lcm_fetch_array($result)) {
					$total_time += $row['time'];
					echo "\t\t<tr><td>";
					echo njoin(array($row['name_first'],$row['name_middle'],$row['name_last']));
					echo '</td><td>';
					echo format_time_interval($row['time'],($prefs['time_intervals_notation'] == 'hours_only'));
					echo "</td></tr>\n";
				}

				// Show total case hours
				echo "\t\t<tr><td><strong>" . 'TOTAL:' . "</strong></td><td><strong>";
				echo format_time_interval($total_time,($prefs['time_intervals_notation'] == 'hours_only'));
				echo "</strong></td></tr>\n";

				// Close table
				echo "\t</table>\n</p></fieldset>\n";
				break;
			//
			// Case attachments
			//
			case 'attachments' :
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('case_subtitle_attachments') . '</div>';
				echo "<p class=\"normal_text\">\n";

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
					echo "</table><br />\n";
				}

				// Attach new file form
				if ($add) {
					echo '<div class="prefs_column_menu_head">' . 'Add new document' . '</div>';
					echo '<form enctype="multipart/form-data" action="attach_file.php" method="post">' . "\n";
					echo "<input type=\"hidden\" name=\"case\" value=\"$case\" />\n";
					echo '<input type="hidden" name="MAX_FILE_SIZE" value="300000" />' . "\n";
					echo '<strong>Filename:</strong><br /><input type="file" name="filename" size="65" />' . "\n";
					echo "<br />\n";
					echo '<strong>Description:</strong><br /><input type="text" name="description" class="search_form_txt" />&nbsp;' . "\n";
					echo '<input type="submit" name="submit" value="Attach" class="search_form_btn" />' . "\n";
					echo "</form>\n";
				}

				echo '</fieldset>';

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
