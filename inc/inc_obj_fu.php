<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

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

	$Id: inc_obj_fu.php,v 1.27 2008/04/07 19:13:03 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_FU')) return;
define('_INC_OBJ_FU', '1');

include_lcm('inc_db');
include_lcm('inc_obj_generic');

class LcmFollowup extends LcmObject {
	// Note: Since PHP5 we should use "private", and generates a warning,
	// but we must support PHP >= 4.0.
	var $data; 

	function LcmFollowup($id_fu = 0, $id_case = 0) {
		$id_fu = intval($id_fu);
		$id_case = intval($id_case);

		$this->data = array();

		if ($id_fu > 0) { 
			$query = "SELECT fu.*, a.name_first, a.name_middle, a.name_last, " .
						lcm_query_subst_time('fu.date_start', 'fu.date_end') . " as length
					FROM lcm_followup as fu, lcm_author as a
					WHERE id_followup = $id_fu
					  AND fu.id_author = a.id_author";
	
			$result = lcm_query($query);
	
			if (($row = lcm_fetch_array($result))) 
				foreach ($row as $key => $val) 
					$this->data[$key] = $val;
		} else {
			if ($id_case > 0) {
				$this->data['id_case'] = $id_case;
			}

			// Dates
			$this->data['date_start'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
			$this->data['date_end']   = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'

			// Set appointment start/end/reminder times to current time
			$this->data['app_start_time'] = date('Y-m-d H:i:s');
			$this->data['app_end_time'] = date('Y-m-d H:i:s');
			$this->data['app_reminder'] = date('Y-m-d H:i:s');

			if (isset($_REQUEST['stage']))
				$this->data['new_stage'] = _request('stage');

			if (isset($_REQUEST['type']))
				$this->data['type'] = _request('type');
		}

		// If any, populate form values submitted
		foreach($_REQUEST as $key => $value) {
			$nkey = $key;

			if (substr($key, 0, 3) == 'fu_')
				$nkey = substr($key, 3);

			$this->data[$nkey] = clean_input(_request($key));
		}

		// If any, populate with session variables (for error reporting)
		if (isset($_SESSION['form_data']) && count($_SESSION['errors'])) {
			foreach($_SESSION['form_data'] as $key => $value) {
				$nkey = $key;

				if (substr($key, 0, 3) == 'fu_')
					$nkey = substr($key, 3);

				$this->data[$nkey] = clean_input(_session($key));
			}
		}

		// date_start
		if (get_datetime_from_array($_SESSION['form_data'], 'start', 'start', -1, false) != -1)
			$this->data['date_start'] = get_datetime_from_array($_SESSION['form_data'], 'start', 'start', '', false);
	}

	function validate() {
		$errors = array();

		// * Check for id_case
		if (! ($this->getDataInt('id_case') > 0))
			$errors['id_case'] = "Internal error: No id_case found";

		// * Check for fu type
		if (! $this->getDataString('type'))
			$errors['type'] = _Ti('fu_input_type') . _T('warning_field_mandatory');

		// * Check if fu type exists
		if (! get_kw_from_name('followups', $this->getDataString('type')))
			$errors['type'] = _Ti('fu_input_type') . "Unknown type"; // TRAD

		// * Check start date
		$unix_date_start = strtotime($this->getDataString('date_start'));

		if (($unix_date_start < 0) || ! checkdate_sql($this->getDataString('date_start')))
			$errors['date_start'] = _Ti('time_input_date_start') . 'Invalid start date.'; // TRAD

		if (! is_numeric($this->getDataFloat('sumbilled', 0.0)))
			$errors['sumbilled'] = _Ti('fu_input_sum_billed') . 'Incorrect format, must be 00000.00'; // TRAD

		// * Check end date
		// [ML] This is probably very buggy, because I re-wrote parts of it
		// to make it LCM 0.7.0 compliant, but it's a hell of a mess!
		// And parts of this code should be in the constructor.
		global $prefs;
		if ($prefs['time_intervals'] == 'absolute') {
			if (isempty_datetime_from_array($_SESSION['form_data'], 'end', 'date_only')) {
				// Set to default empty date if all fields empty
				$this->data['date_end'] = '0000-00-00 00:00:00';
			} elseif (! isset_datetime_from_array($_SESSION['form_data'], 'end', 'date_only')) {
				// Report error if some of the fields empty
				$this->data['date_end'] = get_datetime_from_array($_SESSION['form_data'], 'end', 'start', '', false);
				$errors['date_end'] = 'Partial end date!'; // TRAD
			} else {
				$this->data['date_end'] = get_datetime_from_array($_SESSION['form_data'], 'end', 'start', '', false);
				$unix_date_end = strtotime($this->getDataString('date_end'));

				if ( ($unix_date_end<0) || !checkdate_sql($this->getDataString('date_end')))
					$errors['date_end'] = 'Invalid end date.'; // TRAD
			}
		} else {
			$valid_interval = true;
			$unix_date_end = $unix_date_start;

			$_SESSION['form_data']['delta_days'] = trim($_SESSION['form_data']['delta_days']);
			$_SESSION['form_data']['delta_hours'] = trim($_SESSION['form_data']['delta_hours']);
			$_SESSION['form_data']['delta_minutes'] = trim($_SESSION['form_data']['delta_minutes']);

			if (is_numeric(_session('delta_days', 0)) && _session('delta_days', 0) >= 0)
				$unix_date_end += (_session('delta_days', 0)) * 86400;
			else
				$valid_interval = false;

			if (is_numeric(_session('delta_hours', 0)) && _session('delta_hours', 0) >= 0)
				$unix_date_end += (_session('delta_hours', 0)) * 3600;
			else
				$valid_interval = false;

			if (is_numeric(_session('delta_minutes', 0)) && _session('delta_minutes', 0) >= 0)
				$unix_date_end += (_session('delta_minutes', 0)) * 60;
			else
				$valid_interval = false;


			if ($valid_interval) {
				$this->data['date_end'] = date('Y-m-d H:i:s', $unix_date_end);
			} else {
				$errors['date_end'] = _Ti('time_input_length') . 'Invalid time interval.'; // TRAD
				$this->data['date_end'] = $_SESSION['form_data']['date_start'];
			}
		}

		// Description
		/* [ML] This was requested to be optional (MG, PDO)
		   if ( !(strlen($this->data['description']) > 0) )
		   $errors['description'] = _Ti('fu_input_description') . _T('warning_field_mandatory');
		 */

		validate_update_keywords_request('followup', $this->getDataInt('id_followup'));

		if ($_SESSION['errors'])
			$errors = array_merge($errors, $_SESSION['errors']);

		//
		// Custom validation functions
		//
		$id_case = $this->getDataInt('id_case');
		$fields = array('description' => 'FollowupDescription');

		foreach ($fields as $f => $func) {
			if (include_validator_exists($f)) {
				include_validator($f);
				$class = "LcmCustomValidate$func";
				$data = $this->getDataString($f);
				$v = new $class();

				if ($err = $v->validate($id_case, $data)) 
					$errors[$f] = _Ti('fu_input_' . $f) . $err;
			}
		}

		return $errors;
	}

	function save() {
		$errors = $this->validate();

		if (count($errors))
			return $errors;

		//
		// Update
		//
		$fl = " date_start = '" . $this->getDataString('date_start') . "',
				date_end   = '" . $this->getDataString('date_end') . "',
				type       = '" . $this->getDataString('type') . "',
				sumbilled  = " . $this->getDataFloat('sumbilled', 0.00);

		if ($this->getDataString('type') == 'stage_change') {
			// [ML] To be honest, we should "assert" most of the
			// following values, but "new_stage" is the most important.
			lcm_assert_value($this->getDataString('new_stage', '__ASSERT__'));

			$desc = array(
					'description'  => $this->getDataString('description'),
					'result'       => $this->getDataString('result'),
					'conclusion'   => $this->getDataString('conclusion'),
					'sentence'     => $this->getDataString('sentence'),
					'sentence_val' => $this->getDataString('sentence_val'),
					'new_stage'    => $this->getDataString('new_stage'));

			$fl .= ", description = '" . serialize($desc) . "'";
		} elseif (is_status_change($this->getDataString('type'))) {
			$desc = array(
					'description'  => $this->getDataString('description'),
					'result'       => $this->getDataString('result'),
					'conclusion'   => $this->getDataString('conclusion'),
					'sentence'     => $this->getDataString('sentence'),
					'sentence_val' => $this->getDataString('sentence_val'));

			$fl .= ", description = '" . serialize($desc) . "'";
		} else {
			$fl .= ", description  = '" . $this->getDataString('description') . "'";
		}

		if ($this->getDataInt('id_followup') > 0) {
			// Edit of existing follow-up
			$id_followup = $this->getDataInt('id_followup');
		
			if (!allowed($this->getDataInt('id_case'), 'e')) 
				lcm_panic("You don't have permission to modify this case's information. (" . $this->getDataInt('id_case') . ")");

			// TODO: check if hiding this FU is allowed
			if (allowed($this->getDataInt('id_case'), 'a')
					&& (! (is_status_change($this->getDataString('type'))
							|| $this->getDataString('type') == 'assignment'
							|| $this->getDataString('type') == 'unassignment')))
			{
				if ($this->getDataString('delete'))
					$fl .= ", hidden = 'Y'";
				else
					$fl .= ", hidden = 'N'";
			} else {
				$fl .= ", hidden = 'N'";
			}

			$q = "UPDATE lcm_followup SET $fl WHERE id_followup = $id_followup";
			$result = lcm_query($q);

			// Get stage of the follow-up entry
			$q = "SELECT id_stage, case_stage FROM lcm_followup WHERE id_followup = $id_followup";
			$result = lcm_query($q);

			if ($row = lcm_fetch_array($result)) {
				$case_stage = lcm_assert_value($row['case_stage']);
			} else {
				lcm_panic("There is no such follow-up (" . $id_followup . ")");
			}

			// Update the related lcm_stage entry
			$q = "UPDATE lcm_stage SET
					date_conclusion = '" . $this->getDataString('date_end') . "',
					kw_result = '" . $this->getDataString('result') . "',
					kw_conclusion = '" . $this->getDataString('conclusion') . "',
					kw_sentence = '" . $this->getDataString('sentence') . "',
					sentence_val = '" . $this->getDataString('sentence_val') . "',
					date_agreement = '" . $this->getDataString('date_end') . "'
				WHERE id_case = " . $this->getDataInt('id_case') . "
				  AND kw_case_stage = '" . $case_stage . "'";

			lcm_query($q);
		} else {
			// New follow-up
			if (!allowed($this->getDataInt('id_case'), 'w'))
				lcm_panic("You don't have permission to add information to this case. (" . $this->getDataInt('id_case') . ")");

			// Get the current case stage
			$q = "SELECT id_stage, stage FROM lcm_case WHERE id_case=" . $this->getDataInt('id_case', '__ASSERT__');
			$result = lcm_query($q);

			if ($row = lcm_fetch_array($result)) {
				$case_stage = lcm_assert_value($row['stage']);
				$case_stage_id = lcm_assert_value($row['id_stage']);
			} else {
				lcm_panic("There is no such case (" . $this->getDataInt('id_case') . ")");
			}

			// Add the new follow-up
			$q = "INSERT INTO lcm_followup
					SET id_case=" . $this->getDataInt('id_case') . ",
						id_author=" . $GLOBALS['author_session']['id_author'] . ",
						$fl,
						id_stage = $case_stage_id,
						case_stage='$case_stage'";
	
			lcm_query($q);
			$this->data['id_followup'] = lcm_insert_id('lcm_followup', 'id_followup');
	
			// Set relation to the parent appointment, if any
			if ($this->getDataInt('id_app')) {
				$q = "INSERT INTO lcm_app_fu 
						SET id_app=" . $this->getDataInt('id_app') . ",
							id_followup=" . $this->getDataInt('id_followup', '__ASSERT__') . ",
							relation='child'";
				$result = lcm_query($q);
			}

			// Update case status
			$status = '';
			$stage = '';
			switch ($this->getDataString('type')) {
				case 'conclusion' :
					$status = 'closed';
					break;
				case 'suspension' :
					$status = 'suspended';
					break;
				case 'opening' :
				case 'resumption' :
				case 'reopening' :
					$status = 'open';
					break;
				case 'merge' :
					$status = 'merged';
					break;
				case 'deletion':
					$status = 'deleted';
					break;
				case 'stage_change' :
					$stage = lcm_assert_value($this->getDataString('new_stage'));
					break;
			}
		
			if ($status || $stage) {
				$q = "UPDATE lcm_case
						SET " . ($status ? "status='$status'" : '') . ($status && $stage ? ',' : '') . ($stage ? "stage='$stage'" : '') . "
						WHERE id_case=" . $this->getDataInt('id_case');

				lcm_query($q);

				// Close the lcm_stage
				// XXX for now, date_agreement is not used
				if ($status == 'open') {
					// case is being re-opened, so erase previously entered info
					$q = "UPDATE lcm_stage
							SET
								date_conclusion = '0000-00-00 00:00:00',
								id_fu_conclusion = 0,
								kw_result = '',
								kw_conclusion = '',
								kw_sentence = '',
								sentence_val = '',
								date_agreement = '0000-00-00 00:00:0'
							WHERE id_case = " . $this->getDataInt('id_case') . "
							  AND kw_case_stage = '" . $case_stage . "'";
				} else {
					$q = "UPDATE lcm_stage
							SET
								date_conclusion = '" . $this->getDataString('date_end') . "',
								id_fu_conclusion = " . $this->getDataInt('id_followup') . ",
								kw_result = '" . $this->getDataString('result') . "',
								kw_conclusion = '" . $this->getDataString('conclusion') . "',
								kw_sentence = '" . $this->getDataString('sentence') . "',
								sentence_val = '" . $this->getDataString('sentence_val') . "',
								date_agreement = '" . $this->getDataString('date_end') . "'
							WHERE id_case = " . $this->getDataInt('id_case', '__ASSERT__') . "
							  AND kw_case_stage = '" . $case_stage . "'";
				}
	
				lcm_query($q);
			}

			// If creating a new case stage, make new lcm_stage entry
			if ($stage) {
				$q = "INSERT INTO lcm_stage SET
							id_case = " . $this->getDataInt('id_case', '__ASSERT__') . ",
							kw_case_stage = '" . lcm_assert_value($stage) . "',
							date_creation = NOW(),
							id_fu_creation = " . $this->getDataInt('id_followup');

				lcm_query($q);
			}
		}

		// Keywords
		update_keywords_request('followup', $this->getDataInt('id_followup'));

		return $errors;
	}
}

class LcmFollowupInfoUI extends LcmFollowup {
	var $show_conclusion;
	var $show_sum_billed;

	function LcmFollowupInfoUI($id_fu = 0) {
		$this->LcmFollowup($id_fu);

		// In printEdit(), whether to show "conclusion" fields
		$this->show_conclusion = false;

		if (_request('submit') == 'set_status' || _request('submit') == 'set_stage') {
			$this->show_conclusion = true;
		} elseif (_session('type') == 'stage_change' || is_status_change(_session('type'))) {
			$this->show_conclusion = true;
		}

		// In printEdit(), whether to check for sumbilled
		$this->show_sum_billed = read_meta('fu_sum_billed');
	}

	function printGeneral($show_subtitle = true, $allow_edit = true) {
		if ($show_subtitle)
			show_page_subtitle(_T('generic_subtitle_general'), 'cases_intro');

		echo '<ul class="info">';
		// TODO: fix html
		
		// Author
		echo '<li>'
			. '<span class="label2">' . _Ti('case_input_author') . '</span>'
			. '<span class="value2">' . get_author_link($this->data) . '</span>'
			. "</li>\n";
		
		// Date start
		echo '<li>'
			. '<span class="label2">' . _Ti('time_input_date_start') . '</span>'
			. '<span class="value2">' . format_date($this->data['date_start']) . '</span>'
			. "</li>\n";
		
		// Date end
		echo '<li>'
			. '<span class="label2">' . _Ti('time_input_date_end') . '</span>'
			. '<span class="value2">' . format_date($this->data['date_end']) . '</span>'
			. "</li>\n";
		
		// Date length
		echo '<li>'
			. '<span class="label2">' . _Ti('time_input_length') . '</span>'
			. '<span class="value2">' . format_time_interval_prefs($this->data['length']) . '</span>'
			. "</li>\n";
		
		// FU type
		echo '<li>'
			. '<span class="label2">' . _Ti('fu_input_type') . '</span>'
			. '<span class="value2">' . _Tkw('followups', $this->data['type']) . '</span>'
			. "</li>\n";

		// Keywords
		show_all_keywords('followup', $this->getDataInt('id_followup'));
		
		// Conclusion for case/status change
		/* [ML] 2008-01-30 Should not be necessary, done by get_fu_description()
		if ($this->data['type'] == 'status_change' || $this->data['type'] == 'stage_change') {
			$tmp = lcm_unserialize($this->data['description']);

			var_dump($tmp);

			echo '<li>'
				. '<span class="label2">' . _Ti('fu_input_conclusion') .  '</span>';

			echo '<span class="value2">';
		
			if (read_meta('case_result') == 'yes' && $tmp['result'])
				echo _Tkw('_crimresults', $tmp['result']) . "<br />\n";
			
			echo _Tkw('conclusion', $tmp['conclusion']) . '</span>';
			echo "</li>\n";
		
			echo '<li>'
				. '<span class="label2">' . _Ti('fu_input_sentence') . '</li>'
				. '<span class="value2">' . _Tkw('sentence', $tmp['sentence']) . '</span>'
				. "</li>\n";
		}
		*/
		
		// Description
		$desc = get_fu_description($this->data, false);
		
		echo '<li class="large">'
			. '<span class="label2">' . _Ti('fu_input_description') . '</span>'
			. '<span class="value2">' . $desc . '</span>'
			. "</li>\n";
		
		// Sum billed (if activated from policy)
		if ($this->show_sum_billed == 'yes') {
			echo '<li>'
				. '<span class="label2">' . _T('fu_input_sum_billed') . '</span>'
				. '<span class="value2">';

			echo  format_money(clean_output($this->data['sumbilled']));
			$currency = read_meta('currency');
			echo htmlspecialchars($currency);

			echo '</span>';
			echo "</li>\n";
		}
						
		echo "</ul>\n";
	}

	// XXX error checking! ($_SESSION['errors'])
	function printEdit() {
		global $prefs; 

		$admin = allowed($this->getDataInt('id_case'), 'a'); // FIXME
		$edit  = allowed($this->getDataInt('id_case'), 'e'); // FIXME
		$write = allowed($this->getDataInt('id_case'), 'w'); // FIXME (put in constructor)

		// FIXME: not sure whether this works as previously
		$dis = isDisabled(! ($admin || $edit));
	
		echo '<table class="tbl_usr_dtl" width="99%">' . "\n";
		echo '<tr><td>';
		echo f_err_star('date_start') . _T('fu_input_date_start'); 
		echo "</td>\n";
		echo "<td>";

		$name = (($admin || $edit) ? 'start' : '');
		echo get_date_inputs($name, $this->data['date_start'], false);
		echo ' ' . _T('time_input_time_at') . ' ';
		echo get_time_inputs($name, $this->data['date_start']);

		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr><td>";

		echo f_err_star('date_end') . (($prefs['time_intervals'] == 'absolute') ? _T('fu_input_date_end') : _T('fu_input_time_length'));
		echo "</td>\n";
		echo '<td>';

		if ($prefs['time_intervals'] == 'absolute') {
			// Buggy code, so isolated most important cases
			if ($this->data['id_followup'] == 0)
				$name = 'end';
			elseif ($edit)
				$name = 'end';
			else
				// user can 'finish' entering data
				$name = (($admin || ($edit && ($this->data['date_end']=='0000-00-00 00:00:00'))) ? 'end' : '');

			echo get_date_inputs($name, $this->data['date_end']);
			echo ' ';
			echo _T('time_input_time_at') . ' ';
			echo get_time_inputs($name, $this->data['date_end']);
		} else {
			$name = '';

			// Buggy code, so isolated most important cases
			if ($this->getDataInt('id_followup') == 0)
				$name = 'delta';
			elseif ($edit)
				$name = 'delta';
			else
				// user can 'finish' entering data
				$name = (($admin || ($edit && ($this->getDataString('date_end') =='0000-00-00 00:00:00'))) ? 'delta' : '');

			if (empty($_SESSION['errors'])) {
				$interval = (($this->getDataString('date_end') != '0000-00-00 00:00:00') ?
						strtotime($this->getDataString('date_end')) - strtotime($this->getDataString('date_start')) : 0);
				echo get_time_interval_inputs($name, $interval);
			} else {
				echo get_time_interval_inputs_from_array($name, $this->data);
			}
		}

		echo "</td>\n";
		echo "</tr>\n";
	
		// Show 'conclusion' options
		if ($this->show_conclusion) {
			$kws_conclusion = get_keywords_in_group_name('conclusion');
			$kws_result = get_keywords_in_group_name('_crimresults');
	
			echo "<tr>\n";
			echo "<td>" . _Ti('fu_input_conclusion') . "</td>\n";
			echo '<td>';
	
			// Result
			if (read_meta('case_result') == 'yes') {
				echo '<select ' . $dis . ' name="result" size="1" class="sel_frm">' . "\n";
	
				$default = '';
				if ($this->data['result'])
					$default = $this->data['result'];
	
				foreach ($kws_result as $kw) {
					$sel = isSelected($kw['name'] == $default);
					echo '<option ' . $sel . ' value="' . $kw['name'] . '">' . _T(remove_number_prefix($kw['title'])) . "</option>\n";
				}
	
				echo "</select><br/>\n";
			}
	
			// Conclusion
			echo '<select ' . $dis . ' name="conclusion" size="1" class="sel_frm">' . "\n";
	
			$default = '';
			if ($this->data['conclusion'])
				$default = $this->data['conclusion'];
	
			foreach ($kws_conclusion as $kw) {
				$sel = isSelected($kw['name'] == $default);
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
			if ($this->data['sentence'])
				$default = $this->data['sentence'];
	
			echo "<!-- " . $default . " -->\n";
	
			foreach ($kws_sentence as $kw) {
				$sel = ($kw['name'] == $default ? ' selected="selected"' : '');
				echo '<option ' . $sel . ' value="' . $kw['name'] . '">'
					. _T(remove_number_prefix($kw['title']), array('currency' => read_meta('currency')))
					. "</option>\n";
			}
	
			echo "</select>\n";
	
			// If sentence, for how much?
			echo '<input type="text" name="sentence_val" size="10" value="' . $this->data['sentence_val'] . '" />';
			echo "</td>\n";
			echo "</tr>\n";
		}
	
	
		if (_request('submit') == 'set_status' || is_status_change($this->getDataString('type'))) {
			// Change status
			echo "<tr>\n";
			echo "<td>" . _T('case_input_status') . "</td>\n";
			echo "<td>";

			echo '<input type="hidden" name="type" value="' . $this->getDataString('type') . '" />' . "\n";
			echo _T('kw_followups_' . $this->data['type'] . '_title');

			echo "</td>\n";
			echo "</tr>\n";
		} elseif (_request('submit') == 'set_stage' || $this->getDataString('type') == 'stage_change') {
			// Change stage
			echo "<tr>\n";
			echo "<td>" . _T('fu_input_next_stage') . "</td>\n";
			echo "<td>";

			echo '<input type="hidden" name="type" value="' . $this->getDataString('type') . '" />' . "\n";

			// This is to compensate an old bug, when 'case stage' was not stored in fu.description
			// and therefore editing a follow-up would not give correct information.
			// Bug was in CVS of 0.4.3 between 19-20 April 2005. Should not affect many people.
			if (($s = $this->getDataString('new_stage'))) {
				echo '<input type="hidden" name="new_stage" value="' .  $s . '" />' . "\n";
				echo _Tkw('stage', $s);
			} else {
				echo "New stage information not available";
			}

			echo "</td>\n";
			echo "</tr>\n";

			if (($s = $this->getDataString('new_stage'))) {
				// Update stage keywords (if any)
				$stage = get_kw_from_name('stage', $s);
				$id_stage = $stage['id_keyword'];
				show_edit_keywords_form('stage', $this->data['id_case'], $id_stage);
			}
		} elseif ($this->getDataString('type') == 'assignment' || $this->getDataString('type') == 'unassignment') {
			// Do not allow assignment/un-assignment follow-ups to be changed
			echo "<tr>\n";
			echo "<td>" . _T('fu_input_next_stage') . "</td>\n";
			echo "<td>";

			echo '<input type="hidden" name="type" value="' . $this->getDataString('type') . '" />' . "\n";
			echo _Tkw('followups', $this->getDataString('type'));

			echo "</td>\n";
			echo "</tr>\n";
		} else {
			// The usual follow-up
			echo "<tr>\n";
			echo "<td>" . _T('fu_input_type') . "</td>\n";
			echo "<td>";
			echo '<select ' . $dis . ' name="type" size="1" class="sel_frm">' . "\n";

			$default_fu = get_suggest_in_group_name('followups');
			$futype_kws = get_keywords_in_group_name('followups');
			$kw_found = false;

			foreach($futype_kws as $kw) {
				$sel = isSelected($kw['name'] == $default_fu);
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

		// Keywords (if any)
		show_edit_keywords_form('followup', $this->getDataInt('id_followup'));
	
		// Description
		echo "<tr>\n";
		echo '<td valign="top">' . f_err_star('description') . _T('fu_input_description') . "</td>\n";
		echo '<td>';

		if ($this->getDataString('type') == 'assignment' || $this->getDataString('type') == 'unassignment') {
			// Do not allow edit of assignment
			echo '<input type="hidden" name="description" value="' . $this->getDataString('description') . '" />' . "\n";
			echo get_fu_description($this->data);
		} else {
			echo '<textarea ' . $dis . ' name="description" rows="15" cols="60" class="frm_tarea">';
			echo clean_output($this->getDataString('description'));
			echo "</textarea>";
		}

		echo "</td></tr>\n";
	

		// Sum billed field
		if ($this->show_sum_billed == "yes") {
			echo '<tr>';
			echo '<td>' . _T('fu_input_sum_billed') . "</td>\n";
			echo '<td>';
			echo '<input ' . $dis . ' name="sumbilled" '
				. 'value="' . clean_output($this->getDataString('sumbilled')) . '" '
				. 'class="search_form_txt" size="10" />';

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
		if ($this->getDataInt('id_followup')
				&& allowed($this->data['id_case'], 'a')
				&& ! (is_status_change($this->data['type'])
					|| $this->data['type'] == 'assignment'
					|| $this->data['type'] == 'unassignment'))
		{
			$checked = ($this->getDataString('hidden') == 'Y' ? ' checked="checked" ' : '');

			echo '<p class="normal_text">';
			echo '<input type="checkbox"' . $checked . ' name="delete" id="box_delete" />';
			echo '<label for="box_delete">' . _T('fu_info_delete') . '</label>';
			echo "</p>\n";
		}
	
		// Add followup appointment
		if (! _request('followup')) {
			echo "<!-- Add appointment? -->\n";
			echo '<p class="normal_text">';
			echo '<input type="checkbox" name="add_appointment" id="box_new_app" onclick="display_block(\'new_app\', \'flip\')" />';
			echo '<label for="box_new_app">' . _T('fu_info_add_future_activity') . '</label>';
			echo "</p>\n";

			echo '<div id="new_app" style="display: none;">';
			echo '<table class="tbl_usr_dtl" width="99%">' . "\n";
			echo "<!-- Start time -->\n\t\t<tr><td>";
			echo _T('time_input_date_start');
			echo "</td><td>";
			echo get_date_inputs('app_start', $this->data['app_start_time'], false);
			echo ' ' . _T('time_input_time_at') . ' ';
			echo get_time_inputs('app_start', $this->data['app_start_time']);
			echo f_err_star('app_start_time');
			echo "</td></tr>\n";

			echo "<!-- End time -->\n\t\t<tr><td>";
			echo (($prefs['time_intervals'] == 'absolute') ? _T('time_input_date_end') : _T('app_input_time_length'));
			echo "</td><td>";
			if ($prefs['time_intervals'] == 'absolute') {
				echo get_date_inputs('app_end', $this->data['app_end_time']);
				echo ' ' . _T('time_input_time_at') . ' ';
				echo get_time_inputs('app_end', $this->data['app_end_time']);
				echo f_err_star('app_end_time');
			} else {
				$interval = ( ($this->data['app_end_time']!='0000-00-00 00:00:00') ?
						strtotime($this->data['app_end_time']) - strtotime($this->data['app_start_time']) : 0);
				//	echo _T('calendar_info_time') . ' ';
				echo get_time_interval_inputs('app_delta', $interval);
				echo f_err_star('app_end_time');
			}
			echo "</td></tr>\n";

			/* [ML] Removing, not useful for now
			   echo "<!-- Reminder -->\n\t\t<tr><td>";
			   echo (($prefs['time_intervals'] == 'absolute') ? _T('app_input_reminder_time') : _T('app_input_reminder_offset'));
			   echo "</td><td>";
			   if ($prefs['time_intervals'] == 'absolute') {
			   echo get_date_inputs('app_reminder', $this->data['app_reminder']);
			   echo ' ' . _T('time_input_time_at') . ' ';
			   echo get_time_inputs('app_reminder', $this->data['app_reminder']);
			   echo f_err_star('app_reminder');
			   } else {
			   $interval = ( ($this->data['app_end_time']!='0000-00-00 00:00:00') ?
			   strtotime($this->data['app_start_time']) - strtotime($this->data['app_reminder']) : 0);
			//	echo _T('calendar_info_time') . ' ';
			echo get_time_interval_inputs('app_rem_offset', $interval);
			echo " " . _T('time_info_before_start');
			echo f_err_star('app_reminder');
			}
			echo "</td></tr>\n";
			 */

			// TODO: [ML] a bit of testing to see if this survives an error on new case
			// I suspect it doesn't..
			echo "<!-- Appointment title -->\n\t\t<tr><td>";
			echo f_err_star('app_title') . _T('app_input_title');
			echo "</td><td>";
			echo '<input type="text" ' . $dis . ' name="app_title" size="50" value="';
			echo clean_output($this->getDataString('app_title')) . '" class="search_form_txt" />';
			echo "</td></tr>\n";

			echo "<!-- Appointment type -->\n\t\t<tr><td>";
			echo _T('app_input_type');
			echo "</td><td>";
			echo '<select ' . $dis . ' name="app_type" size="1" class="sel_frm">';

			global $system_kwg;

			if ($_SESSION['fu_app_data']['type'])
				$default_app = $_SESSION['fu_app_data']['type'];
			else {
				$app_kwg = get_kwg_from_name('appointments');
				$default_app = $app_kwg['suggest'];
			}

			$opts = array();
			foreach($system_kwg['appointments']['keywords'] as $kw)
				$opts[$kw['name']] = _T(remove_number_prefix($kw['title']));
			asort($opts);

			foreach($opts as $k => $opt) {
				$sel = isSelected($k == $default_app);
				echo "<option value='$k'$sel>$opt</option>\n";
			}

			echo '</select>';
			echo "</td></tr>\n";

			echo "<!-- Appointment description -->\n";
			echo "<tr><td valign=\"top\">";
			echo _T('app_input_description');
			echo "</td><td>";
			echo '<textarea ' . $dis . ' name="app_description" rows="5" cols="60" class="frm_tarea">';
			echo clean_output($this->getDataString('app_description'));
			echo '</textarea>';
			echo "</td></tr>\n";
			echo "</table>\n";
			echo "</div>\n";
		}
	}
}

?>
