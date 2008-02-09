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

	$Id: case_det.php,v 1.178 2008/02/09 17:07:00 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_obj_case');

// Read parameters
$case = intval(_request('case'));
$fu_order = "DESC";

// Read site configuration settings
$case_assignment_date = read_meta('case_assignment_date');
$case_alledged_crime  = read_meta('case_alledged_crime');
$case_legal_reason    = read_meta('case_legal_reason');
$case_allow_modif     = read_meta('case_allow_modif');
$modify = ($case_allow_modif == 'yes');

if (isset($_GET['fu_order']))
	if ($_GET['fu_order'] == 'ASC' || $_GET['fu_order'] == 'DESC')
		$fu_order = clean_input($_GET['fu_order']);

if (! ($case > 0)) {
	header("Location: listcases.php");
	exit;
}


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
		lcm_page_start(_T('title_case_details') . " #" . $row['id_case'] . ' ' . $row['title'], '', '', 'cases_intro');

		// [ML] This will probably never be implemented
		// echo "<div id=\"breadcrumb\"><a href=\"". getenv("HTTP_REFERER") ."\">List of cases</a> &gt; ". $row['title'] ."</div>";

		// Show tabs
		$groups = array(
			'general' => array('name' => _T('generic_tab_general'), 'tooltip' => _T('case_subtitle_general')),
		//	'followups' => array('name' => _T('generic_tab_followups'), 'tooltip' => _T('case_subtitle_followups')),
			'appointments' => array('name' => _T('generic_tab_agenda'), 'tooltip' => _T('case_subtitle_appointments')),
			'exps' => array('name' => 'Requests', 'tooltip' => 'Internal requests'), // TRAD
			'times' => array('name' => _T('generic_tab_reports'), 'tooltip' => _T('case_subtitle_times')),
			'attachments' => array('name' => _T('generic_tab_documents'), 'tooltip' => _T('case_subtitle_attachments')));
		$tab = ( isset($_GET['tab']) ? $_GET['tab'] : 'general' );
		show_tabs($groups,$tab,$_SERVER['REQUEST_URI']);

		echo show_all_errors();

		switch ($tab) {
			//
			// General tab
			//
			case 'general' :
				echo "<fieldset class='info_box'>";

				$obj_case_ui = new LcmCaseInfoUI($row['id_case']);
				$obj_case_ui->printGeneral();
		
				if ($edit && $modify)
					echo '<p><a href="edit_case.php?case=' . $row['id_case'] . '" class="edit_lnk">' . _T('edit_case_information') . '</a></p>';

	// [ML] This is not useful at the moment.. there is no import
	// and the XML spec of the export needs improvement.
	//			if ($GLOBALS['author_session']['status'] == 'admin')
	//				echo '<p><a href="export.php?item=case&amp;id=' . $row['id_case'] . '" class="exp_lnk">' . _T('export_button_case') . '</a></p>';


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
							echo '<span class="noprint"><label for="id_del_client' . $row['id_client'] . '">';
							echo '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" '
								. 'alt="' . _T('case_info_delete_client') . '" title="' .  _T('case_info_delete_client') . '" />';
							echo '</label>&nbsp;';
							echo '<input type="checkbox" onclick="lcm_show(\'btn_delete\')" '
								. 'id="id_del_client' . $row['id_client'] . '" name="id_del_client[]" '
								. 'value="' . $row['id_client'] . '" /></span>';
							echo "</td>\n";
						}

						echo "</tr>\n";
					}
				}
		
				//
				// Show case organization(s)
				//
				$q="SELECT o.id_org,name
					FROM lcm_case_client_org as cco, lcm_org as o
					WHERE id_case = $case AND cco.id_org = o.id_org";
		
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
					if (read_meta('org_hide_all') != 'yes')
						echo "<a href=\"sel_org.php?case=$case\" class=\"add_lnk\">" . _T('case_button_add_org') .  "</a>";
						
					echo "<br /></p>\n";
				}
		
				echo "</form>\n";
			//	echo "</fieldset>\n";
				// break; // XXX  [ML] testing
			//
			// Case followups
			//
			case 'followups' :
				// [ML] Since 0.7.1, this tab is not called directly,
				// it is part of the "general" tab
				echo '<a name="followups"></a>' . "\n";

				$obj_case_ui = new LcmCaseInfoUI($case);
				$obj_case_ui->printFollowups(true);

				if ($add) {
					echo '<p class="normal_text">';
					echo "<a href=\"edit_fu.php?case=$case\" class=\"create_new_lnk\">" . _T('new_followup') . "</a>&nbsp;\n";
					echo "</p>\n";
				}

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
							array('title' => ( ($prefs['time_intervals'] == 'absolute') ? _Th('time_input_date_end') : _Th('time_input_length') ) ),
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
			// Time spent on case by authors
			//
			case 'times' :
				// List authors on the case
				$show_more_times = (_request('more_times') ? true : false);

				$q = "SELECT
						a.id_author, name_first, name_middle, name_last,
						sum(IF(UNIX_TIMESTAMP(fu.date_end) > 0,
							UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) as time,
						sum(sumbilled) as sumbilled
					FROM  lcm_author as a, lcm_followup as fu
					WHERE fu.id_author = a.id_author
					  AND fu.id_case = $case
					  AND fu.hidden = 'N'
					GROUP BY fu.id_author";
				$result = lcm_query($q);

				// Show table headers
				echo '<fieldset class="info_box">';
				show_page_subtitle(_T('case_subtitle_times'), 'reports_intro');

				$link_details = new Link();
				$link_details->addVar('more_times', intval((! $show_more_times)));
			
				echo "<table border='0' class='tbl_usr_dtl' width='99%'>\n";
				echo "<tr>\n";
				echo "<th class='heading'>" // TODO add title on href
					. _Th('case_input_author') . '&nbsp;'
					. '<a title="' . _T('fu_button_stats_' . ($show_more_times ? 'less' : 'more')) . '" href="' . $link_details->getUrl() . '">'
					. '<img src="images/spip/' . ($show_more_times ? 'moins' : 'plus') . '.gif" alt="" border="0" />'
					. '</a>'
					. "</th>\n";
				echo "<th class='heading' width='120' nowrap='nowrap' align='right'>" .  _Th('time_input_length') . ' (' . _T('time_info_short_hour') . ")</th>\n";

				$total_time = 0;
				$total_sum_billed = 0.0;
				$meta_sum_billed = read_meta('fu_sum_billed');

				if ($meta_sum_billed == 'yes') {
					$currency = read_meta('currency');
					echo "<th class='heading' width='120' nowrap='nowrap' align='right'>" . _Th('fu_input_sum_billed') . ' (' . $currency . ")</th>\n";
				}

				echo "</tr>\n";

				// Show table contents & calculate total
				while ($row = lcm_fetch_array($result)) {
					$total_time += $row['time'];
					$total_sum_billed += $row['sumbilled'];

					echo "<tr><td>";
					echo get_person_name($row);
					echo '</td><td align="right" valign="top">';
					echo format_time_interval_prefs($row['time']);
					echo "</td>\n";

					if ($meta_sum_billed == 'yes') {
						echo '<td align="right" valign="top">';
						echo format_money($row['sumbilled']);
						echo "</td>\n";
					}

					if ($show_more_times) {
						$fu_types = get_keywords_in_group_name('followups', false);
						$html = "";
						
						foreach ($fu_types as $f) {
							$q2 = "SELECT type,
									sum(IF(UNIX_TIMESTAMP(fu.date_end) > 0,
										UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) as time,
									sum(sumbilled) as sumbilled
								FROM  lcm_followup as fu
								WHERE fu.id_case = $case
								  AND fu.id_author = " . $row['id_author'] . "
								  AND fu.hidden = 'N'
								  AND fu.type = '" . $f['name'] . "'
								GROUP BY fu.type";

							$r2 = lcm_query($q2);

							// FIXME: css for "ul/li" is a bit weird, but without specifying the height,
							// the text is displayed under the line...
							// But we should probably scrap the whole table anyway
							while (($row2 = lcm_fetch_array($r2))) {
								// either:  futype (70%) + length (15%) + sumbilled (15%)
								// or only: futype (70%) + length (30%)

								$html .= "<li style='clear: both; height: 1.4em; width: 100%;'>";

								$html .= '<div style="float: left; text-align: left;">' 
										. _Tkw('followups', $row2['type']) . ": "
										. '</div>';

								if ($meta_sum_billed == 'yes') 
									$html .= '<div style="width: 120px; float: right; text-align: right;">' 
											.  format_money($row2['sumbilled']) 
											. '</div>';

								$html .= '<div style="width: 120px; float: right; text-align: right;">' 
										. format_time_interval_prefs($row2['time']) 
										. '</div>';

								$html .= "</li>\n";
							}
						}

						if ($html) {
							echo "</tr>\n";
							echo "<tr>";

							if ($meta_sum_billed == 'yes')
								echo "<td colspan='3'>";
							else
								echo "<td colspan='2'>";

							echo '<ul class="info" style="padding-left: 1.5em">'
								. $html
								. "</ul>\n";

							echo "</td>";
						}
					}
					
					echo "</tr>\n";
				}

				// Show total case hours
				echo "<tr>\n";
				echo "<td><strong>" . _Ti('generic_input_total') . "</strong></td>\n";
				echo "<td align='right'><strong>";
				echo format_time_interval_prefs($total_time);
				echo "</strong></td>\n";

				if ($meta_sum_billed == 'yes') {
					echo '<td align="right"><strong>';
					echo format_money($total_sum_billed);
					echo "</strong></td>\n";
				}
				
				echo "</tr>\n";
				echo "</table>\n";
				echo "</fieldset>\n";

				break;
			//
			// Internal requests (expenses) related to this case
			//
			case 'exps':
				include_lcm('inc_obj_exp');
				$exp_list = new LcmExpenseListUI();

				$exp_list->setSearchTerm($find_exp_string);
				$exp_list->setCase($case);

				$exp_list->start();
				$exp_list->printList();
				$exp_list->finish();

				echo '<p><a href="edit_exp.php?case=' . $case . '" class="create_new_lnk">' . _T('expense_button_new') . "</a></p>\n";

				break;
			//
			// Case attachments
			//
			case 'attachments' :
				echo '<fieldset class="info_box">';
				show_page_subtitle(_T('case_subtitle_attachments'), 'tools_documents');

				echo '<form enctype="multipart/form-data" action="attach_file.php" method="post">' . "\n";
				echo '<input type="hidden" name="case" value="' . $case . '" />' . "\n";

				// List of attached files
				show_attachments_list('case', $case);

				// Attach new file form
				if ($add)
					show_attachments_upload('case', $case, $_SESSION['user_file']['name'], $_SESSION['user_file']['description']);

				echo '<input type="submit" name="submit" value="' . _T('button_validate') . '" class="search_form_btn" />' . "\n";
				echo "</form>\n";

				echo "</fieldset>\n";

				$_SESSION['user_file'] = array();

				break;
		}
	} else {
		lcm_page_start(_T('title_error'));
		// [ML] Maybe not worth translating, since it should never happen. // TRAD
		echo "<p>" . _Ti('title_error') . 'The case no. "' . htmlspecialchars($case) . '" does not exist in the database.' . "</p>\n";
	}

	$_SESSION['errors'] = array();
	$_SESSION['case_data'] = array(); // DEPRECATED
	$_SESSION['form_data'] = array();
	$_SESSION['fu_data'] = array();

	lcm_page_end();

?>
