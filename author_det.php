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

	$Id: author_det.php,v 1.11 2005/03/24 15:26:15 mlutfy Exp $
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
		lcm_page_start("Author details: $fullname"); // TRAD

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
			// Contacts tab
			//
			case 'general' :
				//
				// Show client general information
				//
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('generic_subtitle_general') . "</div>\n";

				echo '<p class="normal_text">';
				echo _Ti('author_input_id') . ' ' . $author_data['id_author'] . "<br/>\n";

				// Show author contacts (if any)
				$hide_emails = read_meta('hide_emails');
				$contacts = get_contacts('author', $author);

				echo "</p>\n";

				$html = '<div class="prefs_column_menu_head">' . _T('generic_subtitle_contacts') . "</div>\n";
				$html .= '<table border="0" align="center" class="tbl_usr_dtl" width="99%">' . "\n";

				$i = 0;
				foreach($contacts as $c) {
					// Check if the contact is an e-mail
					if (strpos($c['name'],'email') === 0) {
						if (! ($hide_emails == 'yes' && $author_session['status'] != 'admin')) {
							$html .= "\t<tr>";
							$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . _T($c['title']) . ":</td>";
							$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
							$html .= '<a href="mailto:' . $c['value'] . '">' . $c['value'] . '</a></td>';
							$html .= "</tr>\n";
							$i++;
						}
					} else {
						$html .= "\t<tr>";
						$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . _T($c['title']) . ":</td>";
						$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . $c['value'] . "</td>";
						$html .= "</tr>\n";
						$i++;
					}
				}

				$html .= "</table><br />\n";

				if ($i > 0)
					echo $html;

				//
				// Show 'edit author' button, if allowed
				//
				if (($GLOBALS['author_session']['status'] == 'admin') ||
					($author == $GLOBALS['author_session']['id_author']))
						echo '<p class="normal_text"><a href="edit_author.php?author=' . $author . "\" class=\"edit_lnk\">Edit author data</a></p>\n"; // TRAD

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
						lcm_panic("Error seeking position $list_pos in the result");	// TRAD

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
				echo "<p class=\"normal_text\">\n";

				$headers[0]['title'] = _Th('time_input_date_start');
				$headers[0]['order'] = 'fu_order';
				$headers[0]['default'] = 'ASC';
				$headers[1]['title'] = _Th('time_input_length');
				$headers[1]['order'] = 'no_order';
				$headers[2]['title'] = _Th('fu_input_type');
				$headers[2]['order'] = 'no_order';
				$headers[3]['title'] = _Th('fu_input_description');
				$headers[3]['order'] = 'no_order';
			
				show_list_start($headers);
			
				$q = "SELECT	id_followup, date_start, date_end, type, description
					FROM lcm_followup
					WHERE id_author=$author";
			
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
						lcm_panic("Error seeking position $list_pos in the result");	// TRAD
			
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

				echo '<a href="case_activity.php?case=' . $case . '" class="create_new_lnk">' . 'Printable list of activities' . "</a>\n";	// TRAD
				echo "<br /><br />\n";
			
				echo "</p></fieldset>";
				
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
				echo "<th class='heading'>" . _Th('author_input_case') . "</th>\n";	// TRAD
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
