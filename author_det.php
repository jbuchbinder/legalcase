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

	$Id: author_det.php,v 1.15 2005/04/04 10:34:07 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_contacts');

// Initialise variables
$author = intval($_REQUEST['author']);

if ($author > 0) {
	// Get author data
	$q = "SELECT *
			FROM lcm_author
			WHERE id_author = $author";
	$result = lcm_query($q);

	if ($author_data = lcm_fetch_array($result)) {
		// Start the page
		$fullname = get_person_name($author_data);
		lcm_page_start(_T('title_author_view') . ' ' . $fullname);

		// [ML] for future use? Would not be bad to have a link: "Go back to: <a..>ref_name</a>"
		// echo "<p>REF = <a href='" . $_REQUEST['ref'] . "'>test</a>\n";

		// Show tabs
		$groups = array('general' => _T('generic_tab_general'),
				'cases' => _T('generic_tab_cases'),
				'followups' => _T('generic_tab_followups'),
				'times' => _T('generic_tab_reports'));
				// [ML] better not to support this, high risk of abuse // 'attachments' => _T('generic_tab_documents'));
		$tab = ( isset($_GET['tab']) ? $_GET['tab'] : 'general' );
		show_tabs($groups,$tab,$_SERVER['REQUEST_URI']);

		switch ($tab) {
			//
			// General tab
			//
			case 'general' :
				//
				// Show client general information
				//
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('generic_subtitle_general') . "</div>\n";

				echo '<p class="normal_text">';
				echo _Ti('authoredit_input_id') . $author_data['id_author'] . "<br />\n";
				echo _Ti('person_input_name') . get_person_name($author_data) . "<br />\n";
				echo _Ti('authoredit_input_status') . _T('authoredit_input_status_' . $author_data['status']) . "<br />\n";

				echo "</p>\n";
				
				// Show author contacts (if any)
				show_all_contacts('author', $author_data['id_author']);


				//
				// Show 'edit author' button, if allowed
				//
				if (($GLOBALS['author_session']['status'] == 'admin') ||
					($author == $GLOBALS['author_session']['id_author']))
						echo '<p class="normal_text"><a href="edit_author.php?author=' . $author . '" class="edit_lnk">'
							. _T('authoredit_button_edit') . "</a></p>\n";

				echo "</fieldset>\n";

				break;
			//
			// Cases tab
			//
			case 'cases':
				// Show recent cases
				$q = "SELECT c.id_case, title, date_creation, id_court_archive, status
						FROM lcm_case_author as a, lcm_case as c
						WHERE id_author = " . $author . "
						AND a.id_case = c.id_case ";

				// Sort cases by creation date
				$case_order = 'DESC';
				if (isset($_REQUEST['case_order']))
					if ($_REQUEST['case_order'] == 'ASC' || $_REQUEST['case_order'] == 'DESC')
						$case_order = $_REQUEST['case_order'];
				
				$q .= " ORDER BY date_creation " . $case_order;
		
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
					echo '<div class="prefs_column_menu_head">' 
						.  _T('author_subtitle_cases', array('author' => get_person_name($author_data)))
						. "</div>\n";
					show_listcase_start();
		
					for ($cpt = 0; $row1 = lcm_fetch_array($result); $cpt++) {
						show_listcase_item($row1, $cpt);
					}

					show_listcase_end($list_pos, $number_of_rows);
					echo "</fieldset>\n";
				}

				break;
			//
			// Author followups
			//
			case 'followups' :
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' 
					. "<div style='float: right'>" . lcm_help('author_followups') . "</div>"
					. _T('author_subtitle_followups', array('author' => get_person_name($author_data)))
					. '</div>';

				// By default, show from "now() - 1 month" to NOW().
				$link = new Link();
				$link->delVar('date_start_day');
				$link->delVar('date_start_month');
				$link->delVar('date_start_year');
				$link->delVar('date_end_day');
				$link->delVar('date_end_month');
				$link->delVar('date_end_year');
				echo $link->getForm();

				echo "<p class=\"normal_text\">\n";
				$date_end = get_datetime_from_array($_REQUEST, 'date_end', date('Y-m-d'));
				$date_start = get_datetime_from_array($_REQUEST, 'date_start', date('Y-m-d', strtotime("-1 month" . $date_end)));

				echo _Ti('time_input_date_start');
				echo get_date_inputs('date_start', $date_start);

				echo _Ti('time_input_date_end');
				echo get_date_inputs('date_end', $date_end);
				echo ' <button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
				echo "</p>\n";
				echo "</form>\n";

				echo "<p class=\"normal_text\">\n";

				$headers[0]['title'] = _Th('time_input_date_start');
				$headers[0]['order'] = 'fu_order';
				$headers[0]['default'] = 'ASC';
				$headers[1]['title'] = _Th('time_input_length');
				$headers[1]['order'] = 'no_order';
				$headers[2]['title'] = _Th('case_input_id');
				$headers[2]['order'] = 'no_order';
				$headers[3]['title'] = _Th('fu_input_type');
				$headers[3]['order'] = 'no_order';
				$headers[4]['title'] = _Th('fu_input_description');
				$headers[4]['order'] = 'no_order';
			
				show_list_start($headers);
			
				$q = "SELECT id_followup, id_case, date_start, date_end, type, description
					FROM lcm_followup
					WHERE id_author = $author
					  AND UNIX_TIMESTAMP(date_start) > UNIX_TIMESTAMP('" . $date_start . "')
					  AND UNIX_TIMESTAMP(date_end) < UNIX_TIMESTAMP('" . $date_end . "')";
			
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
				if ($list_pos > 0 && $list_pos != 'all')
					if (!lcm_data_seek($result,$list_pos))
						lcm_panic("Error seeking position $list_pos in the result");
			
				// Set the length of short followup title
				$title_length = (($prefs['screen'] == "wide") ? 48 : 115);

				// Process the output of the query
				for ($i = 0 ; (($i<$prefs['page_rows'] || $list_pos == 'all') && ($row = lcm_fetch_array($result))); $i++) {
					echo "<tr>\n";
					$td = '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">';
					
					// Start date
					echo $td . format_date($row['date_start'], 'short') . '</td>';
					
					// Time
					echo $td;
					$fu_date_end = vider_date($row['date_end']);
					if ($prefs['time_intervals'] == 'absolute') {
						if ($fu_date_end) echo format_date($row['date_end'],'short');
					} else {
						$fu_time = ($fu_date_end ? strtotime($row['date_end']) - strtotime($row['date_start']) : 0);
						echo format_time_interval($fu_time,($prefs['time_intervals_notation'] == 'hours_only'));
					}
					echo "</td>\n";

					// Case ID
					echo $td;
					echo '<a class="content_link" href="case_det.php?case=' . $row['id_case'] . '">' . $row['id_case'] . "</a>";
					echo "</td>\n";

					// Type
					echo $td . _T('kw_followups_' . $row['type'] . '_title') . '</td>';

					// Description
					if ($row['type'] == 'assignment' && is_numeric($row['description'])) {
						$res1 = lcm_query("SELECT * FROM lcm_author WHERE id_author = " . $row['description']);
						$author1 = lcm_fetch_array($res1);
						$short_description = _T('case_info_author_assigned', array('name' => get_person_name($author1)));
					} else{
						if (strlen(lcm_utf8_decode($row['description'])) < $title_length) 
							$short_description = $row['description'];
						else
							$short_description = substr($row['description'],0,$title_length) . '...';
					}
			
					echo $td;
					echo '<a href="fu_det.php?followup=' . $row['id_followup'] . '" class="content_link">' . clean_output($short_description) . '</a>';
					echo "</td>\n";
			
					echo "</tr>\n";
				}
			
				show_list_end($list_pos, $number_of_rows, true);

				echo "</p>\n";

				// Total hours for period
				$q = "SELECT sum(IF(UNIX_TIMESTAMP(date_end) > UNIX_TIMESTAMP(date_start), 
								UNIX_TIMESTAMP(date_end)-UNIX_TIMESTAMP(date_start), 0)) as total_time
					FROM lcm_followup
					WHERE id_author = $author
				 	GROUP BY id_author";

				$result = lcm_query($q);
				$row = lcm_fetch_array($result);
				
				echo '<p class="normal_text">';
				echo 'Total hours: ' . format_time_interval($row['total_time'], true) . "<br />\n"; // TRAD
				echo "</p>\n";

				// echo "<p class='content_link'>\n";
				// echo '<a href="case_activity.php?case=' . $case . '" class="create_new_lnk">' . 'Printable list of activities' . "</a>\n";	// TRAD
				// echo "<br /><br />\n";
			
				// echo "</p>\n";
				echo "</fieldset>\n";
				
				break;
			//
			// Time spent on case by authors
			//
			case 'times' :
				// Get the information from database

				// List all case followups of this authors
				$q = "SELECT
						c.title,
						sum(IF(UNIX_TIMESTAMP(fu.date_end) > 0,
							UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) as time,
						sum(sumbilled) as sumbilled
					FROM  lcm_case as c, lcm_followup as fu
					WHERE fu.id_case = c.id_case AND fu.id_author = $author
					GROUP BY fu.id_case";
				$result = lcm_query($q);

				// Show table headers
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' 
					. _T('author_subtitle_reports', array('author' => get_person_name($author_data))) 
					. '</div>';
				echo "<p class=\"normal_text\">\n";
			
				echo "<table border='0' class='tbl_usr_dtl' width='99%'>\n";
				echo "<tr>\n";
				echo "<th class='heading'>" . _Th('author_input_case') . "</th>\n";
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

					echo '<tr><td>' . $row['title'] . '</td><td align="right">';
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
		}

		lcm_page_end();
	} else {
		die("There's no such author!");
	}
} else {
	die("Which author?");
}

?>
