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

	$Id: case_det.php,v 1.111 2005/03/18 16:18:45 mlutfy Exp $
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

		// [ML] This will probably never be implemented
		// echo "<div id=\"breadcrumb\"><a href=\"". getenv("HTTP_REFERER") ."\">List of cases</a> &gt; ". $row['title'] ."</div>"; // TRAD

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
				echo "<div class='prefs_column_menu_head'>"
					. "<div style='float: right'>" . lcm_help('cases_intro') . "</div>"
					. _T('case_subtitle_general') 
					. "</div>";
				echo "<p class='normal_text'>";
		
				// Edit case link was here!
		
				// Show users, assigned to the case
				// TODO: use case_input_authors if many authors
				echo _T('case_input_author') . ' ';
				$q = "SELECT id_case,lcm_author.id_author,name_first,name_middle,name_last
					FROM lcm_case_author,lcm_author
					WHERE (id_case=$case
						AND lcm_case_author.id_author=lcm_author.id_author)";
		
				$authors = lcm_query($q);
		
				$q = '';
				while ($user = lcm_fetch_array($authors)) {
					if ($q) $q .= "; \n";
					if ($admin) $q .= '<a href="edit_auth.php?case=' . $case . '&amp;author=' . $user['id_author'] . '" class="content_link">';
					$q .= clean_output($user['name_first'] . ' ' . $user['name_middle'] . ' ' . $user['name_last']);
					if ($admin) $q .= '</a>';
				}
				echo "$q<br />\n";
		
				// Add user to the case link was here

				// [ML] Added ID back, since it was requested by users/testers
				// as a way to cross-reference paper documentation
				echo "\n" . _T('case_input_id') . " " . $row['id_case'] . "<br />\n";
		
				if ($case_court_archive == 'yes')
					echo _T('case_input_court_archive') . ' ' . clean_output($row['id_court_archive']) . "<br />\n";
				echo _T('case_input_date_creation') . ' ' . format_date($row['date_creation']) . "<br />\n";
		
				if ($case_assignment_date == 'yes') {
					// [ML] Case is assigned/unassigned when authors are added/remove
					// + case is auto-assigned when created, so the 'else' should not happen
					if ($row['date_assignment'])
						echo _T('case_input_date_assigned') . ' ' . format_date($row['date_assignment']) . "<br />\n";
					else
						echo _T('case_input_date_assigned') . ' ' . "Click to assign<br/>\n";
				}
		
				echo _T('case_input_legal_reason') . ' ' . clean_output($row['legal_reason']) . "<br />\n";
				if ($case_alledged_crime == 'yes')
					echo _T('case_input_alledged_crime') . ' ' . clean_output($row['alledged_crime']) . "<br />\n";

				// Show case status
				if ($edit) {
					// Change status form
					echo "<form action='set_case_status.php' method='get'>\n";
					echo "<input type='hidden' name='case' value='$case' />\n";

					echo "\t" . _T('case_input_status') . "&nbsp;";
					echo "\t<select name='status' class='sel_frm'>\n";
					$statuses = array('draft','open','suspended','closed','merged');
					foreach ($statuses as $s)
						echo "\t\t<option" .  (($s == $row['status']) ? ' selected="selected"' : '') . ">" . _T('case_status_option_' . $s) . "</option>\n";
					echo "\t</select>\n";
					echo "\t<button type='submit' name='submit' value='set_status' class='simple_form_btn'>" . _T('button_validate') . "</button>\n";
					echo "</form>\n";
				} else {
					echo _T('case_input_status') . "&nbsp;" . clean_output($row['status']) . "<br />\n";
				}

				// Show case stage
				if ($edit) {
					// Change stage form
					echo "<form action='set_case_stage.php' method='get'>\n";
					echo "\t" . _T('case_input_stage') . "&nbsp;";
					echo "<input type='hidden' name='case' value='$case' />\n";
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
					echo _T('case_input_stage') . "&nbsp;" . clean_output($row['stage']) . "<br />\n";
				}

				echo _T('public') . ': ' . _T('Read') . '=';
				echo ($row['public'] ? 'Yes' : 'No');
				echo ', ' . _T('Write') . '=';
				echo ($row['pub_write'] ? 'Yes' : 'No');
				echo "</p><br /><br />\n";
		
				if ($edit && $modify)
					echo '<p><a href="edit_case.php?case=' . $row['id_case'] . '" class="edit_lnk">' . _T('edit_case_information') . '</a></p>';
		
				if ($admin) echo '<p><a href="sel_auth.php?case=' . $case . '" class="add_lnk">' . _T('add_user_case') . '</a></p>';
		
				echo "<br />\n";
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
				echo '<div class="prefs_column_menu_head">'
					. "<div style='float: right'>" . lcm_help('clients_intro') . "</div>"
					. _T('case_subtitle_clients') 
					. '</div>';
				echo '<p class="normal_text">';
		
				//
				// Show case client(s)
				//
				$q="SELECT cl.id_client, cl.name_first, cl.name_middle, cl.name_last
					FROM lcm_case_client_org as clo, lcm_client as cl
					WHERE id_case = $case AND clo.id_client = cl.id_client";
		
				$result = lcm_query($q);
				$header_shown = false;

				if (lcm_num_rows($result)) {
					$header_shown = true;
					echo '<table border="0" width="99%" class="tbl_usr_dtl">' . "\n";
				}
		
				while ($row = lcm_fetch_array($result)) {
					echo "<tr>\n";
					echo '<td width="25" align="center">';
					echo '<img src="images/jimmac/stock_person.png" alt="" height="16" width="16" />';
					echo '</td>' . "\n";
					echo '<td><a style="display: block" href="client_det.php?client=' . $row['id_client'] . '" class="content_link">';
					echo  get_person_name($row);
					echo "</a></td>\n";
					echo "</tr>\n";
				}
		
				//
				// Show case organization(s)
				//
				$q="SELECT lcm_org.id_org,name
					FROM lcm_case_client_org,lcm_org
					WHERE id_case=$case AND lcm_case_client_org.id_org=lcm_org.id_org";
		
				$result = lcm_query($q);

				if (lcm_num_rows($result)) {
					if (! $header_shown) {
						echo '<table border="0" width="99%" class="tbl_usr_dtl">' . "\n";
						$header_shown = true;
					}
				}
		
				while ($row = lcm_fetch_array($result)) {
					echo "<tr>\n";
					echo '<td width="25" align="center"><img src="images/jimmac/stock_people.png" alt="" height="16" width="16" /></td>' . "\n";
					echo '<td><a style="display: block;" href="org_det.php?org=' . $row['id_org'] . '" class="content_link">';
					echo clean_output($row['name']);
					echo "</a></td>\n";
		
					echo "</tr>\n";
				}
		
				if ($header_shown)
					echo "</table>\n\n";
		
				if ($add) {
					echo "<br /><a href=\"sel_client.php?case=$case\" class=\"add_lnk\">" . _T('case_button_add_client') . "</a>\n";
					echo "<a href=\"sel_org.php?case=$case\" class=\"add_lnk\">" . _T('case_button_add_org') . "</a><br /><br />";
				}
		
				echo "</fieldset>";
				break;

			//
			// Case appointments
			//
			case 'appointments' :
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' 
					. "<div style='float: right'>" . lcm_help('agenda_intro') . "</div>"
					. _T('case_subtitle_appointments') 
					. '</div>';
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
					echo '<th class="heading">Start time</th>'; // TRAD
					echo '<th class="heading">' . ( ($prefs['time_intervals'] == 'absolute') ? 'End time' : 'Duration' ) . '</th>'; // TRAD
					echo '<th class="heading">Type</th>'; // TRAD
					echo '<th class="heading">Title</th>'; // TRAD
					echo '<th class="heading">Reminder</th>'; // TRAD
					echo '<th class="heading">Action</th>'; // TRAD
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
							. date('d.m.y H:i',strtotime($row['start_time'])) . '</td>'; // FIXME [ML] use format_date for i18n
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
							. ( ($prefs['time_intervals'] == 'absolute') ?
								date('d.m.y H:i',strtotime($row['end_time'])) : /* FIXME [ML] */
								format_time_interval(strtotime($row['end_time']) - strtotime($row['start_time']),
											($prefs['time_intervals_notation'] == 'hours_only') )
							) . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">' . $row['type'] . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
							. '<a href="app_det.php?app=' . $row['id_app'] . '" class="content_link">' . $row['title'] . '</a></td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
							. date('d.m.y H:i',strtotime($row['reminder'])) . '</td>'; // FIXME [ML]
						echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
							. '<a href="edit_app.php?app=' . $row['id_app'] . '" class="content_link">' . _T('edit') . '</a></td>';
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
							echo 'Go to page: '; // TRAD
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
							echo '" class="content_link">Next ></a>'; // TRAD
						}
						
						echo "</td>\n\t</tr>\n</table>\n";
					}
				
				}

				echo "<br /><a href=\"edit_app.php?case=$case&amp;app=0\" class=\"create_new_lnk\">New appointment</a><br /><br />\n"; // TRAD

				break;
			//
			// Case followups
			//
			case 'followups' :
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' 
					. "<div style='float: right'>" . lcm_help('clients_followups') . "</div>"
					. _T('case_subtitle_followups') 
					. '</div>';
				echo "<p class=\"normal_text\">\n";

				$headers[0]['title'] = "Date start"; // TRAD
				$headers[0]['order'] = 'fu_order';
				$headers[0]['default'] = 'ASC';
				$headers[1]['title'] = "Length"; // TRAD
				$headers[1]['order'] = 'no_order';
				$headers[2]['title'] = "Author"; // TRAD
				$headers[2]['order'] = 'no_order';
				$headers[3]['title'] = "Type"; // TRAD
				$headers[3]['order'] = 'no_order';
				$headers[4]['title'] = "Description"; // TRAD
				$headers[4]['order'] = 'no_order';
			
				show_list_start($headers);
			
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
				if ($fu_order) $q .= " ORDER BY date_start $fu_order, id_followup $fu_order";
			
				$result = lcm_query($q);

				// Check for correct start position of the list
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
			
				// Set the length of short followup title
				$title_length = (($prefs['screen'] == "wide") ? 48 : 115);
			
				// Process the output of the query
				for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))); $i++) {
					echo "<tr>\n";
					
					// Start date
					echo '<td>' . format_date($row['date_start'], 'short') . '</td>';
					
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

					// Author initials
					echo '<td>';
					echo get_person_initials($row);
					echo '</td>';
					
					// Type
					echo '<td>' . _T('kw_followups_' . $row['type'] . '_title') . '</td>';

					// Description
					if (strlen(lcm_utf8_decode($row['description'])) < $title_length) 
						$short_description = $row['description'];
					else
						$short_description = substr($row['description'],0,$title_length) . '...';
			
					echo '<td>';
					echo '<a href="fu_det.php?followup=' . $row['id_followup'] . '" class="content_link">' . clean_output($short_description) . '</a>';
					echo '</td>';
			
					/* [ML]
					if ($edit)
						echo '<td><a href="edit_fu.php?followup=' . $row['id_followup'] . '" class="content_link">' . _T('Edit') . '</a></td>';
					*/

					echo "</tr>\n";
				}
			
				show_list_end($list_pos, $number_of_rows);

				echo "<br />\n";

				if ($add)
					echo "<a href=\"edit_fu.php?case=$case\" class=\"create_new_lnk\">" . _T('new_followup') . "</a>&nbsp;\n";

				echo '<a href="case_activity.php?case=' . $case . '" class="create_new_lnk">' . 'Printable list of activities' . "</a>\n";
				echo "<br /><br />\n";
			
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
				$q = "SELECT
						name_first, name_middle, name_last,
						sum(IF(UNIX_TIMESTAMP(fu.date_end) > 0,
							UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) as time,
						sum(sumbilled) as sumbilled
					FROM  lcm_author as a, lcm_followup as fu
					WHERE fu.id_author = a.id_author AND fu.id_case = $case
					GROUP BY fu.id_author";
				$result = lcm_query($q);

				// Show table headers
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('case_subtitle_times') . '</div>';
				echo "<p class=\"normal_text\">\n";
			
				echo "<table border='0' class='tbl_usr_dtl' width='99%'>\n";
				echo "<tr>\n";
				echo "<th class='heading'>" . 'Author' . "</th>\n"; // TRAD
				echo "<th class='heading' width='1%' nowrap='nowrap'>" . 'Time spent' . ' (' . 'hrs' . ")</th>\n"; // TRAD

				$total_time = 0;
				$total_sum_billed = 0.0;
				$meta_sum_billed = read_meta('fu_sum_billed');

				if ($meta_sum_billed == 'yes') {
					$currency = read_meta('currency');
					echo "<th class='heading' width='1%' nowrap='nowrap'>" . 'Sum billed' . ' (' . $currency . ")</th>\n"; // TRAD
				}

				echo "</tr>\n";

				// Show table contents & calculate total
				while ($row = lcm_fetch_array($result)) {
					echo "<!-- Total = " . $total_sum_billed . " - row = " . $row['sumbilled'] . " -->\n";

					$total_time += $row['time'];
					$total_sum_billed += $row['sumbilled'];

					echo "<tr><td>";
					echo get_person_name($row);
					echo '</td><td align="right">';
					echo format_time_interval($row['time'],($prefs['time_intervals_notation'] == 'hours_only'));
					echo "</td>\n";

					if ($meta_sum_billed == 'yes') {
						echo '<td align="right">';
						echo format_money($row['sumbilled']);
						echo "</td>\n";
					}
					
					echo "</tr>\n";
				}

				// Show total case hours
				echo "<tr>\n";
				echo "<td><strong>" . 'TOTAL:' . "</strong></td>\n"; // TRAD
				echo "<td align='right'><strong>";
				echo format_time_interval($total_time,($prefs['time_intervals_notation'] == 'hours_only'));
				echo "</strong></td>\n";

				if ($meta_sum_billed == 'yes') {
					echo '<td align="right"><strong>';
					echo format_money($total_sum_billed);
					echo "</strong></td>\n";
				}
				
				echo "</tr>\n";

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
					// TRAD ++
					echo "\t<tr><th class=\"heading\">Filename</th>
						<th class=\"heading\">Type</th>
						<th class=\"heading\">Size</th>
						<th class=\"heading\">Description</th></tr>\n";
					for ($i=0 ; $row = lcm_fetch_array($result) ; $i++) {
						echo "\t<tr>";
						echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">'
							. '<a href="view_file.php?type=case&amp;file_id=' . $row['id_attachment']
							. '" class="content_link">' . $row['filename'] . '</a></td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . $row['type'] . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . $row['size'] . '</td>';
						echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . clean_output($row['description']) . '</td>';
						echo "</tr>\n";
					}
					echo "</table><br />\n";
				}

				// Attach new file form
				if ($add) {
					echo '<div class="prefs_column_menu_head">' . 'Add new document' . '</div>'; // TRAD
					echo '<form enctype="multipart/form-data" action="attach_file.php" method="post">' . "\n";
					echo "<input type=\"hidden\" name=\"case\" value=\"$case\" />\n";
					echo '<input type="hidden" name="MAX_FILE_SIZE" value="300000" />' . "\n";
					echo '<strong>Filename:</strong><br /><input type="file" name="filename" size="40" />' . "\n"; // TRAD
					echo "<br />\n";
					echo '<strong>Description:</strong><br /><input type="text" name="description" class="search_form_txt" />&nbsp;' . "\n"; // TRAD
					echo '<input type="submit" name="submit" value="' . _T('button_validate') . '" class="search_form_btn" />' . "\n";
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
