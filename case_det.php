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

	$Id: case_det.php,v 1.158 2005/05/13 08:16:55 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_keywords');

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
	$q="SELECT *
		FROM lcm_case
		WHERE id_case=$case";

	$result = lcm_query($q);

	// Process the output of the query
	if ($row = lcm_fetch_array($result)) {

		// Check for access rights
		if (! allowed($case, 'r')) {
			// [ML] I usually would not care about such errors, since they happen
			// only when the user messes around with URLs, but since I modified the 
			// access control test, I am paranoid :-) Feel free to scrap later.
			lcm_page_start(_T('title_error'));
			echo _T('error_no_read_permission');
			lcm_page_end();
			exit;
		}

		$add   = allowed($case,'w');
		$edit  = allowed($case,'e');
		$admin = allowed($case,'a');

		// Show case details
		lcm_page_start(_T('title_case_details') . " " . $row['title'], '', '', 'cases_intro');

		// [ML] This will probably never be implemented
		// echo "<div id=\"breadcrumb\"><a href=\"". getenv("HTTP_REFERER") ."\">List of cases</a> &gt; ". $row['title'] ."</div>";

		// Show tabs
		$groups = array('general' => _T('generic_tab_general'),
				// [ML] 'clients' => _T('generic_tab_clients'),
				'followups' => _T('generic_tab_followups'),
				'appointments' => _T('generic_tab_agenda'),
				'times' => _T('generic_tab_reports'),
				'attachments' => _T('generic_tab_documents'));
		$tab = ( isset($_GET['tab']) ? $_GET['tab'] : 'general' );
		show_tabs($groups,$tab,$_SERVER['REQUEST_URI']);

		echo show_all_errors($_SESSION['errors']);

		switch ($tab) {
			//
			// General tab
			//
			case 'general' :
				echo "<fieldset class='info_box'>";
				show_page_subtitle(_T('generic_subtitle_general'), 'cases_intro');

				echo "<p class='normal_text'>";
		
				// Show users assigned to the case
				$q = "SELECT id_case,lcm_author.id_author,name_first,name_middle,name_last
					FROM lcm_case_author,lcm_author
					WHERE (id_case=$case
						AND lcm_case_author.id_author=lcm_author.id_author)";
		
				$authors_result = lcm_query($q);
				$cpt = 0;

				if (lcm_num_rows($authors) > 1)
					echo _Ti('case_input_authors');
				else
					echo _Ti('case_input_author');

				while ($author = lcm_fetch_array($authors_result)) {
					if ($cpt)
						echo "; ";

					$name = htmlspecialchars(get_person_name($author));

					echo '<a href="author_det.php?author=' . $author['id_author'] . '" class="content_link"'
						. ' title="' . _T('case_tooltip_view_author_details', array('author' => $name)) . '">'
						. $name
						. "</a>";

					if ($admin) {
						echo '&nbsp;<a href="edit_auth.php?case=' . $case . '&amp;author=' . $author['id_author'] . '"'
							. ' title="' .
							_T('case_tooltip_view_access_rights', array('author' => $name)) . '">'
							. '<img src="images/jimmac/stock_access_rights-16.png" width="16" height="16" border="0" />'
							. '</a>';
					}

					$cpt++;
				}
		
				echo "<br />";
				
				// Case ID
				echo "\n" . _Ti('case_input_id') . $row['id_case'] . "<br />\n";
		
				if ($case_court_archive == 'yes')
					echo _Ti('case_input_court_archive') . clean_output($row['id_court_archive']) . "<br />\n";
				echo _Ti('case_input_date_creation') . format_date($row['date_creation']) . "<br />\n";
		
				if ($case_assignment_date == 'yes') {
					// [ML] Case is assigned/unassigned when authors are added/remove
					// + case is auto-assigned when created.
					if ($row['date_assignment'])
						echo _Ti('case_input_date_assigned') . format_date($row['date_assignment']) . "<br />\n";
				}

				// Total time spent on case (redundant with "reports/times")
				$query = "SELECT sum(IF(UNIX_TIMESTAMP(fu.date_end) > 0, 
									UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) as time 
							FROM lcm_followup as fu 
							WHERE fu.id_case = " . $row['id_case'];
				
				$result = lcm_query($query);
				$row_tmp = lcm_fetch_array($result);

				echo _Ti('case_input_total_time') . format_time_interval_prefs($row_tmp['time']) . "<br />\n";
		
				echo _Ti('case_input_legal_reason') . clean_output($row['legal_reason']) . "<br />\n";
				if ($case_alledged_crime == 'yes')
					echo _Ti('case_input_alledged_crime') . clean_output($row['alledged_crime']) . "<br />\n";

				// Keywords
				include_lcm('inc_keywords');
				show_all_keywords('case', $row['id_case']);

				$stage = get_kw_from_name('stage', $row['stage']);
				$id_stage = $stage['id_keyword'];
				show_all_keywords('stage', $row['id_case'], $id_stage);

				// Notes
				echo _Ti('case_input_notes') . "<br />\n";
				echo nl2br($row['notes']);

				// Show case status (if closed, only site admin can re-open)
				if (allowed($case, 'a')) {
					// Change status form
					echo "<form action='edit_fu.php' method='get'>\n";
					echo "<input type='hidden' name='case' value='$case' />\n";

					echo _Ti('case_input_status');
					echo "<select name='type' class='sel_frm' onchange='lcm_show(\"submit_status\")'>\n";

					// in inc/inc_acc.php
					$statuses = get_possible_case_statuses($row['status']);

					foreach ($statuses as $s => $futype) {
						$sel = ($s == $row['status'] ? ' selected="selected"' : '');
						echo '<option value="' . $futype . '"' . $sel . '>' . _T('case_status_option_' . $s) . "</option>\n";
					}

					echo "</select>\n";
					echo "<button type='submit' name='submit' id='submit_status' value='set_status' style='visibility: hidden;' class='simple_form_btn'>" . _T('button_validate') . "</button>\n";
					echo "</form>\n";
				} else {
					echo _Ti('case_input_status') . clean_output($row['status']) . "<br />\n";
				}

				// Show case stage
				if ($admin) {
					// Change stage form
					echo "<form action='edit_fu.php' method='get'>\n";
					echo "<input type='hidden' name='case' value='$case' />\n";
					echo "<input type='hidden' name='type' value='stage_change' />\n";

					echo _Ti('case_input_stage');
					echo "<select name='stage' class='sel_frm' onchange='lcm_show(\"submit_stage\")'>\n";

					$stage_kws = get_keywords_in_group_name('stage');
					foreach ($stage_kws as $kw) {
						$sel = ($kw['name'] == $row['stage'] ? ' selected="selected"' : '');
						echo "\t\t<option value='" . $kw['name'] . "'" . "$sel>" . _T($kw['title']) . "</option>\n";
					}
				
					echo "</select>\n";
					echo "<button type='submit' name='submit' id='submit_stage' value='set_stage' style='visibility: hidden;' class='simple_form_btn'>" . _T('button_validate') . "</button>\n";
					echo "</form>\n";
				} else {
					echo _Ti('case_input_stage') . clean_output($row['stage']) . "<br />\n";
				}

				// If case closed, show conclusion
				if ($row['status'] == 'closed') {
					// get the last relevant conclusion
					$q_tmp = "SELECT * 
								FROM lcm_followup
								WHERE id_case = $case
								  AND (type = 'conclusion'
								   OR type = 'stage_change')
								ORDER BY id_followup DESC 
								LIMIT 1";
					$r_tmp = lcm_query($q_tmp);
					$row = lcm_fetch_array($r_tmp);

					if ($row) {
						echo '<div style="background: #dddddd;">';
						echo _Ti('fu_input_conclusion');
						echo get_fu_description($row);
						echo ' <a class="content_link" href="fu_det.php?followup=' . $row['id_followup'] . '">...</a>';
						echo "</div>\n";
						echo "<br />\n";
					}
				}

				echo _Ti('case_input_collaboration');
				echo "<ul style='padding-top: 1px; margin-top: 1px;'>";
				echo "<li>" . _Ti('case_input_collaboration_read') . _T('info_' . ($row['public'] ? 'yes' : 'no')) . "</li>\n";
				echo "<li>" . _Ti('case_input_collaboration_write') . _T('info_' . ($row['pub_write'] ? 'yes' : 'no')) . "</li>\n";
				echo "</ul>\n";
				echo "</p>\n";
		
				if ($edit && $modify)
					echo '<p><a href="edit_case.php?case=' . $row['id_case'] . '" class="edit_lnk">' . _T('edit_case_information') . '</a></p>';

				if ($GLOBALS['author_session']['status'] == 'admin')
					echo '<p><a href="export.php?item=case&amp;id=' . $row['id_case'] . '" class="exp_lnk">' . _T('export_button_case') . '</a></p>';

				if ($admin)
					echo '<p><a href="sel_auth.php?case=' . $case . '" class="add_lnk">' . _T('add_user_case') . '</a></p>';
		
				echo "<br />\n";

				//
				// Show case client(s)
				//
				echo '<a name="clients"></a>' . "\n";
				show_page_subtitle(_T('case_subtitle_clients'), 'cases_participants');

				echo '<form action="add_client.php" method="get">' . "\n";
				echo '<input type="hidden" name="case" value="' . $case . '" />' . "\n";

				$q="SELECT cl.id_client, cl.name_first, cl.name_middle, cl.name_last
					FROM lcm_case_client_org as clo, lcm_client as cl
					WHERE id_case = $case AND clo.id_client = cl.id_client";
		
				$result = lcm_query($q);
				$header_shown = false;

				if (lcm_num_rows($result)) {
					$header_shown = true;
					echo '<table border="0" width="99%" class="tbl_usr_dtl">' . "\n";
		
					while ($row = lcm_fetch_array($result)) {
						echo "<tr>\n";

						// icon
						echo '<td width="25" align="center">';
						echo '<img src="images/jimmac/stock_person.png" alt="" height="16" width="16" />';
						echo '</td>' . "\n";

						// name
						echo '<td><a style="display: block" href="client_det.php?client=' . $row['id_client'] . '" class="content_link">';
						echo  get_person_name($row);
						echo "</a></td>\n";

						// delete icon (if admin rights)
						if ($admin) {
							echo '<td width="1%" nowrap="nowrap">';
							echo '<label for="id_del_client' . $row['id_client'] . '">';
							echo '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" '
								. 'alt="' . _T('case_info_delete_client') . '" title="' .  _T('case_info_delete_client') . '" />';
							echo '</label>&nbsp;';
							echo '<input type="checkbox" onclick="lcm_show(\'btn_delete\')" '
								. 'id="id_del_client' . $row['id_client'] . '" name="id_del_client[]" '
								. 'value="' . $row['id_client'] . '" />';
							echo "</td>\n";
						}

						echo "</tr>\n";
					}
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
		
					while ($row = lcm_fetch_array($result)) {
						echo "<tr>\n";
						// icon
						echo '<td width="25" align="center"><img src="images/jimmac/stock_people.png" alt="" height="16" width="16" /></td>' . "\n";

						// name
						echo '<td><a style="display: block;" href="org_det.php?org=' . $row['id_org'] . '" class="content_link">';
						echo clean_output($row['name']);
						echo "</a></td>\n";

						// delete icon (if admin rights)
						if ($admin) {
							echo '<td width="1%" nowrap="nowrap">';
							echo '<label for="id_del_org' . $row['id_org'] . '">';
							echo '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" '
								. 'alt="' . _T('case_info_delete_org') . '" title="' . _T('case_info_delete_org') . '" />';
							echo '</label>&nbsp;';
							echo '<input type="checkbox" onclick="lcm_show(\'btn_delete\')" '
								. 'id="id_del_org' . $row['id_org'] . '" name="id_del_org[]" '
								. 'value="' . $row['id_org'] . '" />';
							echo "</td>\n";
						}

						echo "</tr>\n";
					}
				}
		
				if ($header_shown) {
					echo "</table>\n\n";
					echo '<p align="right" style="visibility: hidden">';
					echo '<input type="submit" name="submit" id="btn_delete" value="' . _T('button_validate') . '" class="search_form_btn" />';
					echo "</p>\n";
				} else {
					echo '<p class="normal_text">' . _T('case_info_client_emptylist') . "</p>\n";
				}
		
				if ($admin) {
					echo "<p><a href=\"sel_client.php?case=$case\" class=\"add_lnk\">" . _T('case_button_add_client') . "</a>\n";
					echo "<a href=\"sel_org.php?case=$case\" class=\"add_lnk\">" . _T('case_button_add_org') . "</a><br /></p>\n";
				}
		
				echo "</form>\n";
				echo "</fieldset>\n";
				break;

			//
			// Case appointments
			//
			case 'appointments' :
				echo '<fieldset class="info_box">' . "\n";
				show_page_subtitle(_T('case_subtitle_appointments'), 'tools_agenda');

				echo "<p class=\"normal_text\">\n";

				$q = "SELECT *
					FROM lcm_app as a
					WHERE a.id_case=$case";
				$result = lcm_query($q);
				
				// Get the number of rows in the result
				$number_of_rows = lcm_num_rows($result);
				if ($number_of_rows) {
					$headers = array( array('title' => _Th('time_input_date_start')),
							array('title' => ( ($prefs['time_intervals'] == 'absolute') ? _Th('time_input_date_end') : _Th('time_input_duration') ) ),
							array('title' => _Th('app_input_type')),
							array('title' => _Th('app_input_title')),
							array('title' => _Th('app_input_reminder')) );

					show_list_start($headers);

					// Check for correct start position of the list
					$list_pos = 0;
					
					if (isset($_REQUEST['list_pos']))
						$list_pos = $_REQUEST['list_pos'];

					if (is_numeric($list_pos)) {
						if ($list_pos >= $number_of_rows)
							$list_pos = 0;

						// Position to the page info start
						if ($list_pos > 0)
							if (!lcm_data_seek($result,$list_pos))
								lcm_panic("Error seeking position $list_pos in the result");

						$show_all = false;
					} elseif ($list_pos == 'all') {
						$show_all = true;
					}
					
					// Show page of the list
					for ($i = 0 ; ((($i<$prefs['page_rows']) || $show_all) && ($row = lcm_fetch_array($result))) ; $i++) {
						$css = ' class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '"';

						echo "<tr>\n";
						echo "<td $css>" . format_date($row['start_time'], 'short') . '</td>';

						echo "<td $css>"
							. ( ($prefs['time_intervals'] == 'absolute') ?
								format_date($row['end_time'], 'short') : 
								format_time_interval_prefs(strtotime($row['end_time']) - strtotime($row['start_time'])) 
							) . '</td>';

						echo "<td $css>" . _Tkw('appointments', $row['type']) . '</td>';
						echo "<td $css>" . '<a href="app_det.php?app=' . $row['id_app'] . '" class="content_link">' . $row['title'] . '</a></td>';
						echo "<td $css>" . format_date($row['reminder'], 'short') . '</td>';
						echo "</tr>\n";
					}

					show_list_end($list_pos, $number_of_rows, true);
				}

				echo "<p><a href=\"edit_app.php?case=$case&amp;app=0\" class=\"create_new_lnk\">" . _T('app_button_new') . "</a></p>\n";

				echo "</p>\n";
				echo "</fieldset>\n";

				break;
			//
			// Case followups
			//
			case 'followups' :
				echo '<fieldset class="info_box">';
				show_page_subtitle(_T('case_subtitle_followups'), 'cases_followups');

				// By default, show from "case creation date" to NOW().
				$link = new Link();
				$link->delVar('date_start_day');
				$link->delVar('date_start_month');
				$link->delVar('date_start_year');
				$link->delVar('date_end_day');
				$link->delVar('date_end_month');
				$link->delVar('date_end_year');
				echo $link->getForm();

				echo "<p class=\"normal_text\">\n";
				$date_end = get_datetime_from_array($_REQUEST, 'date_end', 'end', '0000-00-00 00:00:00'); // date('Y-m-d H:i:s'));
				$date_start = get_datetime_from_array($_REQUEST, 'date_start', 'start', '0000-00-00 00:00:00'); // $row['date_creation']);

				echo _Ti('time_input_date_start');
				echo get_date_inputs('date_start', $date_start);

				echo _Ti('time_input_date_end');
				echo get_date_inputs('date_end', $date_end);
				echo ' <button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
				echo "</p>\n";
				echo "</form>\n";


				echo "<p class=\"normal_text\">\n";

				$headers = array();
				$headers[0]['title'] = _Th('time_input_date_start');
				$headers[0]['order'] = 'fu_order';
				$headers[0]['default'] = 'ASC';
				$headers[1]['title'] = ( ($prefs['time_intervals'] == 'absolute') ? _Th('time_input_date_end') : _Th('time_input_length') );
				$headers[2]['title'] = _Th('case_input_author');
				$headers[3]['title'] = _Th('fu_input_type');
				$headers[4]['title'] = _Th('fu_input_description');
			
				show_list_start($headers);
			
				$q = "SELECT fu.id_followup, fu.date_start, fu.date_end, fu.type, fu.description, fu.case_stage,
						a.name_first, a.name_middle, a.name_last
					FROM lcm_followup as fu, lcm_author as a
					WHERE id_case = $case
					  AND fu.id_author = a.id_author ";

				if (year($date_start) != '0000')
					$q .= " AND UNIX_TIMESTAMP(date_start) >= UNIX_TIMESTAMP('" . $date_start . "')";

				if (year($date_end) != '0000')
					$q .= " AND UNIX_TIMESTAMP(date_end) <= UNIX_TIMESTAMP('" . $date_end . "')";
			
				// Add ordering
				if ($fu_order) $q .= " ORDER BY date_start $fu_order, id_followup $fu_order";
			
				$result = lcm_query($q);

				// Check for correct start position of the list
				$number_of_rows = lcm_num_rows($result);
				$list_pos = 0;
				
				if (isset($_REQUEST['list_pos']))
					$list_pos = $_REQUEST['list_pos'];

				if (is_numeric($list_pos)) {
					if ($list_pos >= $number_of_rows)
						$list_pos = 0;
				
					// Position to the page info start
					if ($list_pos > 0)
						if (!lcm_data_seek($result,$list_pos))
							lcm_panic("Error seeking position $list_pos in the result");
				
					$show_all = false;
				} elseif ($list_pos == 'all') {
					$show_all = true;
				}
				
				// Position to the page info start
				if ($list_pos > 0)
					if (!lcm_data_seek($result,$list_pos))
						lcm_panic("Error seeking position $list_pos in the result");
			
				// Process the output of the query
				for ($i = 0 ; ((($i<$prefs['page_rows']) || $show_all) && ($row = lcm_fetch_array($result))); $i++) {
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
						echo format_time_interval_prefs($fu_time);
					}
					echo '</td>';

					// Author initials
					echo '<td>';
					echo get_person_initials($row);
					echo '</td>';
					
					// Type
					echo '<td>' . _Tkw('followups', $row['type']) . '</td>';

					// Description
					$short_description = get_fu_description($row);
			
					echo '<td>';
					echo '<a href="fu_det.php?followup=' . $row['id_followup'] . '" class="content_link">' . $short_description . '</a>';
					echo '</td>';
			
					echo "</tr>\n";
				}
			
				show_list_end($list_pos, $number_of_rows, true);

				echo "<br />\n";

				if ($add)
					echo "<a href=\"edit_fu.php?case=$case\" class=\"create_new_lnk\">" . _T('new_followup') . "</a>&nbsp;\n";

				echo "<br /><br />\n";
				echo "</p>\n";
				echo "</fieldset>\n";
				
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
				show_page_subtitle(_T('case_subtitle_times'), 'reports_intro');

				echo "<p class=\"normal_text\">\n";
			
				echo "<table border='0' class='tbl_usr_dtl' width='99%'>\n";
				echo "<tr>\n";
				echo "<th class='heading'>" . _Th('case_input_author') . "</th>\n";
				echo "<th class='heading' width='1%' nowrap='nowrap'>" . 'Time spent' . ' (' . 'hrs' . ")</th>\n"; // TRAD

				$total_time = 0;
				$total_sum_billed = 0.0;
				$meta_sum_billed = read_meta('fu_sum_billed');

				if ($meta_sum_billed == 'yes') {
					$currency = read_meta('currency');
					echo "<th class='heading' width='1%' nowrap='nowrap'>" . _Th('fu_input_sum_billed') . ' (' . $currency . ")</th>\n";
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
					echo format_time_interval_prefs($row['time']);
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
				echo format_time_interval_prefs($total_time);
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
				// Show the errors (if any)
				echo show_all_errors($_SESSION['errors']);

				echo '<fieldset class="info_box">';
				show_page_subtitle(_T('case_subtitle_attachments'), 'tools_documents');

				echo "<p class=\"normal_text\">\n";

				echo '<form enctype="multipart/form-data" action="attach_file.php" method="post">' . "\n";
				echo '<input type="hidden" name="case" value="' . $case . '" />' . "\n";

				// List of attached files
				show_attachments_list('case', $case);

				// Attach new file form
				if ($add)
					show_attachments_upload('case', $case, $_SESSION['user_file']['name'], $_SESSION['user_file']['description']);

				echo '<input type="submit" name="submit" value="' . _T('button_validate') . '" class="search_form_btn" />' . "\n";
				echo "</form>\n";

				echo "</p>\n";
				echo "</fieldset>\n";

				$_SESSION['errors'] = array();
				$_SESSION['user_file'] = array();

				break;
		}
	} else {
		lcm_page_start(_T('title_error'));
		echo _T('error_no_such_case');
	}

	$_SESSION['errors'] = array();
	$_SESSION['case_data'] = array();
	$_SESSION['fu_data'] = array();

	lcm_page_end();
} else {
	lcm_page_start(_T('title_error'));
	echo "<p>" . _T('error_no_case_specified') . "</p>\n";
	lcm_page_end();
}

?>
