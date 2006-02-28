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

	$Id: inc_obj_fu.php,v 1.2 2006/02/28 18:33:43 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_FU')) return;
define('_INC_OBJ_FU', '1');

include_lcm('inc_db');

class LcmFollowup {
	// Note: Since PHP5 we should use "private", and generates a warning,
	// but we must support PHP >= 4.0.
	var $data; 

	function LcmFollowup($id_fu = 0) {
		$id_fu = intval($id_fu);
		$this->data = array();

		if (! ($id_fu > 0))
			return;

		$query = "SELECT fu.*, a.name_first, a.name_middle, a.name_last,
					IF(UNIX_TIMESTAMP(fu.date_end) > 0, UNIX_TIMESTAMP(fu.date_end) - UNIX_TIMESTAMP(fu.date_start), 0) as length
				FROM lcm_followup as fu, lcm_author as a
				WHERE id_followup = $id_fu
				  AND fu.id_author = a.id_author";

		$result = lcm_query($query);

		if (($row = lcm_fetch_array($result))) 
			foreach ($row as $key => $val) 
				$this->data[$key] = $val;
	}

}

class LcmFollowupInfoUI extends LcmFollowup {
	function LcmFollowupInfoUI($id_fu = 0) {
		$this->LcmFollowup($id_fu);
	}

	function printGeneral($show_subtitle = true, $allow_edit = true) {
		if ($show_subtitle)
			show_page_subtitle(_T('generic_subtitle_general'), 'cases_intro');

		echo '<table class="tbl_usr_dtl" width="99%">' . "\n";
		
		// Author
		echo "<tr>\n";
		echo '<td>' . _Ti('case_input_author') . "</td>\n";
		echo '<td>' . get_author_link($this->data) . "</td>\n";
		echo "</tr>\n";
		
		// Date start
		echo "<tr>\n";
		echo '<td>' . _Ti('time_input_date_start') . "</td>\n";
		echo '<td>' . format_date($this->data['date_start']) . "</td>\n";
		echo "</tr>\n";
		
		// Date end
		echo "<tr>\n";
		echo '<td>' . _Ti('time_input_date_end') . "</td>\n";
		echo '<td>' . format_date($this->data['date_end']) . "</td>\n";
		echo "</tr>\n";
		
		// Date length
		echo "<tr>\n";
		echo '<td>' . _Ti('time_input_length') . "</td>\n";
		echo '<td>' . format_time_interval_prefs($this->data['length']) . "</td>\n";
		echo "</tr>\n";
		
		// FU type
		echo "<tr>\n";
		echo '<td>' . _Ti('fu_input_type') . "</td>\n";
		echo '<td>' . _Tkw('followups', $this->data['type']) . "</td>\n";
		echo "</tr>\n";
		
		// Conclusion for case/status change
		if ($this->data['type'] == 'status_change' || $this->data['type'] == 'stage_change') {
			$tmp = lcm_unserialize($this->data['description']);
		
			echo "<tr>\n";
			echo '<td>' . _Ti('fu_input_conclusion') . "</td>\n";
		
			echo '<td>';
		
			if (read_meta('case_result') == 'yes' && $tmp['result'])
				echo _Tkw('_crimresults', $tmp['result']) . "<br />\n";
			
			echo _Tkw('conclusion', $tmp['conclusion']) . "</td>\n";
			echo "</tr>\n";
		
			echo "<tr>\n";
			echo '<td>' . _Ti('fu_input_sentence') . "</td>\n";
			echo '<td>' . _Tkw('sentence', $tmp['sentence']) . "</td>\n";
			echo "</tr>\n";
		}
		
		// Description
		$desc = get_fu_description($this->data, false);
		
		echo "<tr>\n";
		echo '<td valign="top">' . _T('fu_input_description') . "</td>\n";
		echo '<td>' . $desc . "</td>\n";
		echo "</tr>\n";
		
		// Sum billed (if activated from policy)
		$fu_sum_billed = read_meta('fu_sum_billed');
		
		if ($fu_sum_billed == 'yes') {
			echo "<tr><td>" . _T('fu_input_sum_billed') . "</td>\n";
			echo "<td>";
			echo format_money(clean_output($this->data['sumbilled']));
			$currency = read_meta('currency');
			echo htmlspecialchars($currency);
			echo "</td></tr>\n";
		}
						
		echo "</table>\n";
	}

	// XXX error checking! ($_SESSION['errors'])
	function printEdit() {
		echo '<table class="tbl_usr_dtl" width="99%">' . "\n";
		echo '<tr><td>';
		echo f_err_star('date_start') . _T('fu_input_date_start'); 
		echo "</td>\n";
		echo "<td>";

		$name = (($admin || $edit) ? 'start' : '');
		echo get_date_inputs($name, $_SESSION['fu_data']['date_start'], false);
		echo ' ' . _T('time_input_time_at') . ' ';
		echo get_time_inputs($name, $_SESSION['fu_data']['date_start']);

		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr><td>";

		echo f_err_star('date_end') . (($prefs['time_intervals'] == 'absolute') ? _T('fu_input_date_end') : _T('fu_input_time_length'));
		echo "</td>\n";
		echo '<td>';

		if ($prefs['time_intervals'] == 'absolute') {
			// Buggy code, so isolated most important cases
			if ($_SESSION['fu_data']['id_followup'] == 0)
				$name = 'end';
			elseif ($edit)
				$name = 'end';
			else
				// user can 'finish' entering data
				$name = (($admin || ($edit && ($_SESSION['fu_data']['date_end']=='0000-00-00 00:00:00'))) ? 'end' : '');

			echo get_date_inputs($name, $_SESSION['fu_data']['date_end']);
			echo ' ';
			echo _T('time_input_time_at') . ' ';
			echo get_time_inputs($name, $_SESSION['fu_data']['date_end']);
		} else {
			$name = '';

			// Buggy code, so isolated most important cases
			if ($_SESSION['fu_data']['id_followup'] == 0)
				$name = 'delta';
			elseif ($edit)
				$name = 'delta';
			else
				// user can 'finish' entering data
				$name = (($admin || ($edit && ($_SESSION['fu_data']['date_end']=='0000-00-00 00:00:00'))) ? 'delta' : '');

			if (empty($_SESSION['errors'])) {
				$interval = ( ($_SESSION['fu_data']['date_end']!='0000-00-00 00:00:00') ?
						strtotime($_SESSION['fu_data']['date_end']) - strtotime($_SESSION['fu_data']['date_start']) : 0);
				echo get_time_interval_inputs($name, $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
			} else {
				echo get_time_interval_inputs_from_array($name, $_SESSION['fu_data'], ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
			}
		}

		echo "</td>\n";
		echo "</tr>\n";
	
		// Show 'conclusion' options
		if ($show_conclusion) {
			$kws_conclusion = get_keywords_in_group_name('conclusion');
			$kws_result = get_keywords_in_group_name('_crimresults');
	
			echo "<tr>\n";
			echo "<td>" . _Ti('fu_input_conclusion') . "</td>\n";
			echo '<td>';
	
			// Result
			if (read_meta('case_result') == 'yes') {
				echo '<select ' . $dis . ' name="result" size="1" class="sel_frm">' . "\n";
	
				$default = '';
				if ($_SESSION['fu_data']['result'])
					$default = $_SESSION['fu_data']['result'];
	
				foreach ($kws_result as $kw) {
					$sel = ($kw['name'] == $default ? ' selected="selected"' : '');
					echo '<option ' . $sel . ' value="' . $kw['name'] . '">' . _T(remove_number_prefix($kw['title'])) . "</option>\n";
				}
	
				echo "</select><br/>\n";
			}
	
			// Conclusion
			echo '<select ' . $dis . ' name="conclusion" size="1" class="sel_frm">' . "\n";
	
			$default = '';
			if ($_SESSION['fu_data']['conclusion'])
				$default = $_SESSION['fu_data']['conclusion'];
	
			foreach ($kws_conclusion as $kw) {
				$sel = ($kw['name'] == $default ? ' selected="selected"' : '');
				echo '<option ' . $sel . ' value="' . $kw['name'] . '">' . _T(remove_number_prefix($kw['title'])) . "</option>\n";
			}
	
			echo "</select>\n";
			echo "</td>\n";
			echo "</tr>\n";
	
			// If guilty, what sentence?
			$kws_sentence = get_keywords_in_group_name('sentence');
	
			echo "<tr>\n";
			echo "<td>" . _Ti('fu_input_sentence') . "</td>\n";
			echo '<td>';
			echo '<select ' . $dis . ' name="sentence" size="1" class="sel_frm">' . "\n"; 
	
			$default = '';
			if ($_SESSION['fu_data']['sentence'])
				$default = $_SESSION['fu_data']['sentence'];
	
			echo "<!-- " . $default . " -->\n";
	
			foreach ($kws_sentence as $kw) {
				$sel = ($kw['name'] == $default ? ' selected="selected"' : '');
				echo '<option ' . $sel . ' value="' . $kw['name'] . '">'
					. _T(remove_number_prefix($kw['title']), array('currency' => read_meta('currency')))
					. "</option>\n";
			}
	
			echo "</select>\n";
	
			// If sentence, for how much?
			echo '<input type="text" name="sentence_val" size="10" value="' . $_SESSION['fu_data']['sentence_val'] . '" />';
			echo "</td>\n";
			echo "</tr>\n";
		}
	
	
		if ($_REQUEST['submit'] == 'set_status' || is_status_change($_SESSION['fu_data']['type'])) {
			// Change status
			echo "<tr>\n";
			echo "<td>" . _T('case_input_status') . "</td>\n";
			echo "<td>";

			echo '<input type="hidden" name="type" value="' . $_SESSION['fu_data']['type'] . '" />' . "\n";
			echo _T('kw_followups_' . $_SESSION['fu_data']['type'] . '_title');

			echo "</td>\n";
			echo "</tr>\n";
		} elseif ($_REQUEST['submit'] == 'set_stage' || $_SESSION['fu_data']['type'] == 'stage_change') {
			// Change stage
			echo "<tr>\n";
			echo "<td>" . _T('fu_input_next_stage') . "</td>\n";
			echo "<td>";

			echo '<input type="hidden" name="type" value="' . $_SESSION['fu_data']['type'] . '" />' . "\n";

			// This is to compensate an old bug, when 'case stage' was not stored in fu.description
			// and therefore editing a follow-up would not give correct information.
			// Bug was in CVS of 0.4.3 between 19-20 April 2005. Should not affect many people.
			if (isset($new_stage)) {
				echo '<input type="hidden" name="new_stage" value="' .  $new_stage . '" />' . "\n";
				echo _Tkw('stage', $new_stage);
			} else {
				echo "New stage information not available";
			}

			echo "</td>\n";
			echo "</tr>\n";

			if (isset($new_stage)) {
				// Update stage keywords (if any)
				$stage = get_kw_from_name('stage', $new_stage); // $_SESSION['fu_data']['case_stage']);
				$id_stage = $stage['id_keyword'];
				show_edit_keywords_form('stage', $_SESSION['fu_data']['id_case'], $id_stage);
			}
		} elseif ($_SESSION['fu_data']['type'] == 'assignment' || $_SESSION['fu_data']['type'] == 'unassignment') {
			// Do not allow assignment/un-assignment follow-ups to be changed
			echo "<tr>\n";
			echo "<td>" . _T('fu_input_next_stage') . "</td>\n";
			echo "<td>";

			echo '<input type="hidden" name="type" value="' . $_SESSION['fu_data']['type'] . '" />' . "\n";
			echo _Tkw('followups', $_SESSION['fu_data']['type']);

			echo "</td>\n";
			echo "</tr>\n";
		} else {
			// The usual follow-up
			echo "<tr>\n";
			echo "<td>" . _T('fu_input_type') . "</td>\n";
			echo "<td>";
			echo '<select ' . $dis . ' name="type" size="1" class="sel_frm">' . "\n";

			if ($_SESSION['fu_data']['type'])
				$default_fu = $_SESSION['fu_data']['type'];
			else
				$default_fu = $system_kwg['followups']['suggest'];

			$futype_kws = get_keywords_in_group_name('followups');
			$kw_found = false;

			foreach($futype_kws as $kw) {
				$sel = ($kw['name'] == $default_fu ? ' selected="selected"' : '');
				if ($sel) $kw_found = true;
				echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T(remove_number_prefix($kw['title'])) . "</option>\n";
			}

			// Exotic case where the FU keyword was hidden by the administrator,
			// but an old follow-up using that keyword is being edited.
			if (! $kw_found)
				echo '<option selected="selected" value="' . $default_fu . '">' . _Tkw('followups', $default_fu) . "</option>\n";

			echo "</select>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
	
		// Description
		echo "<tr>\n";
		echo '<td valign="top">' . f_err_star('description') . _T('fu_input_description') . "</td>\n";
		echo '<td>';

		if ($_SESSION['fu_data']['type'] == 'assignment' || $_SESSION['fu_data']['type'] == 'unassignment') {
			// Do not allow edit of assignment
			echo '<input type="hidden" name="description" value="' . $_SESSION['fu_data']['description'] . '" />' . "\n";
			echo get_fu_description($_SESSION['fu_data']);
		} else {
			echo '<textarea ' . $dis . ' name="description" rows="15" cols="60" class="frm_tarea">';
			echo clean_output($_SESSION['fu_data']['description']);
			echo "</textarea>";
		}

		echo "</td></tr>\n";
	

		// Sum billed field
		if ($fu_sum_billed == "yes") {
			echo '<tr>';
			echo '<td>' . _T('fu_input_sum_billed') . "</td>\n";
			echo '<td>';
			echo '<input ' . $dis . ' name="sumbilled" '
				. 'value="' . clean_output($_SESSION['fu_data']['sumbilled']) . '" '
				. 'class="search_form_txt" size='10' />';

			// [ML] If we do this we may as well make a function
			// out of it, but not sure where to place it :-)
			// This code is also in config_site.php
			$currency = read_meta('currency');
			if (empty($currency)) {
				$current_lang = $GLOBALS['lang'];
				$GLOBALS['lang'] = read_meta('default_language');
				$currency = _T('currency_default_format');
				$GLOBALS['lang'] = $current_lang;
			}
	
			echo htmlspecialchars($currency);
			echo "</td></tr>\n";
		}
			
		echo "</table>\n\n";
	
		// XXX FIXME: Should probably be in some function "is_system_fu"
		// or even "is_deletable"
		if ($_SESSION['fu_data']['id_followup']
				&& allowed($_SESSION['fu_data']['id_case'], 'a')
				&& ! (is_status_change($_SESSION['fu_data']['type'])
					|| $_SESSION['fu_data']['type'] == 'assignment'
					|| $_SESSION['fu_data']['type'] == 'unassignment'))
		{
			$checked = ($_SESSION['fu_data']['hidden'] == 'Y' ? ' checked="checked" ' : '');

			echo '<p class="normal_text">';
			echo '<input type="checkbox"' . $checked . ' name="delete" id="box_delete" />';
			echo '<label for="box_delete">' . _T('fu_info_delete') . '</label>';
			echo "</p>\n";
		}
	
		// Add followup appointment
		if (!isset($_GET['followup'])) {
			echo "<!-- Add appointment? -->\n";
			echo '<p class="normal_text">';
			echo '<input type="checkbox" name="add_appointment" id="box_new_app" onclick="display_block(\'new_app\', \'flip\')"; />';
			echo '<label for="box_new_app">' . _T('fu_info_add_future_activity') . '</label>';
			echo "</p>\n";

			echo '<div id="new_app" style="display: none;">';
			echo '<table class="tbl_usr_dtl" width="99%">' . "\n";
			echo "<!-- Start time -->\n\t\t<tr><td>";
			echo _T('app_input_date_start');
			echo "</td><td>";
			echo get_date_inputs('app_start', $_SESSION['fu_data']['app_start_time'], false);
			echo ' ' . _T('time_input_time_at') . ' ';
			echo get_time_inputs('app_start', $_SESSION['fu_data']['app_start_time']);
			echo f_err_star('app_start_time',$_SESSION['errors']);
			echo "</td></tr>\n";

			echo "<!-- End time -->\n\t\t<tr><td>";
			echo (($prefs['time_intervals'] == 'absolute') ? _T('app_input_date_end') : _T('app_input_time_length'));
			echo "</td><td>";
			if ($prefs['time_intervals'] == 'absolute') {
				echo get_date_inputs('app_end', $_SESSION['fu_data']['app_end_time']);
				echo ' ' . _T('time_input_time_at') . ' ';
				echo get_time_inputs('app_end', $_SESSION['fu_data']['app_end_time']);
				echo f_err_star('app_end_time',$_SESSION['errors']);
			} else {
				$interval = ( ($_SESSION['fu_data']['app_end_time']!='0000-00-00 00:00:00') ?
						strtotime($_SESSION['fu_data']['app_end_time']) - strtotime($_SESSION['fu_data']['app_start_time']) : 0);
				//	echo _T('calendar_info_time') . ' ';
				echo get_time_interval_inputs('app_delta', $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
				echo f_err_star('app_end_time',$_SESSION['errors']);
			}
			echo "</td></tr>\n";

			/* [ML] Removing, not useful for now
			   echo "<!-- Reminder -->\n\t\t<tr><td>";
			   echo (($prefs['time_intervals'] == 'absolute') ? _T('app_input_reminder_time') : _T('app_input_reminder_offset'));
			   echo "</td><td>";
			   if ($prefs['time_intervals'] == 'absolute') {
			   echo get_date_inputs('app_reminder', $_SESSION['fu_data']['app_reminder']);
			   echo ' ' . _T('time_input_time_at') . ' ';
			   echo get_time_inputs('app_reminder', $_SESSION['fu_data']['app_reminder']);
			   echo f_err_star('app_reminder',$_SESSION['errors']);
			   } else {
			   $interval = ( ($_SESSION['fu_data']['app_end_time']!='0000-00-00 00:00:00') ?
			   strtotime($_SESSION['fu_data']['app_start_time']) - strtotime($_SESSION['fu_data']['app_reminder']) : 0);
			//	echo _T('calendar_info_time') . ' ';
			echo get_time_interval_inputs('app_rem_offset', $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
			echo " " . _T('time_info_before_start');
			echo f_err_star('app_reminder',$_SESSION['errors']);
			}
			echo "</td></tr>\n";
			 */

			echo "<!-- Appointment title -->\n\t\t<tr><td>";
			echo f_err_star('app_title') . _T('app_input_title');
			echo "</td><td>";
			echo '<input type="text" ' . $title_onfocus . $dis . ' name="app_title" size="50" value="';
			echo clean_output($_SESSION['fu_data']['app_title']) . '" class="search_form_txt" />';
			echo "</td></tr>\n";

			echo "<!-- Appointment type -->\n\t\t<tr><td>";
			echo _T('app_input_type');
			echo "</td><td>";
			echo '<select ' . $dis . ' name="app_type" size="1" class="sel_frm">';

			global $system_kwg;

			if ($_SESSION['fu_app_data']['type'])
				$default_app = $_SESSION['fu_app_data']['type'];
			else
				$default_app = $system_kwg['appointments']['suggest'];

			$opts = array();
			foreach($system_kwg['appointments']['keywords'] as $kw)
				$opts[$kw['name']] = _T(remove_number_prefix($kw['title']));
			asort($opts);

			foreach($opts as $k => $opt) {
				$sel = ($k == $default_app ? ' selected="selected"' : '');
				echo "<option value='$k'$sel>$opt</option>\n";
			}

			echo '</select>';
			echo "</td></tr>\n";

			echo "<!-- Appointment description -->\n";
			echo "<tr><td valign=\"top\">";
			echo _T('app_input_description');
			echo "</td><td>";
			echo '<textarea ' . $dis . ' name="app_description" rows="5" cols="60" class="frm_tarea">';
			echo clean_output($_SESSION['fu_data']['app_description']);
			echo '</textarea>';
			echo "</td></tr>\n";
			echo "</table>\n";
			echo "</div>\n";
		}

		if (isset($_SESSION['followup'])) {
			// Allow case admin to hide the follow-up
			// TODO
		}
	}
}

?>
