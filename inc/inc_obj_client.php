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

	$Id: inc_obj_client.php,v 1.19 2008/04/07 19:25:31 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_CLIENT')) return;
define('_INC_OBJ_CLIENT', '1');

include_lcm('inc_obj_generic');
include_lcm('inc_db');
include_lcm('inc_contacts');

class LcmClient extends LcmObject {
	// Note: Since PHP5 we should use "private", and generates a warning,
	// but we must support PHP >= 4.0.
	var $cases;
	var $case_start_from;

	function LcmClient($id_client = 0) {
		$id_client = intval($id_client);
		$this->cases = null;
		$this->case_start_from = 0;

		$this->LcmObject();

		if ($id_client > 0) {
			$query = "SELECT * FROM lcm_client WHERE id_client = $id_client";
			$result = lcm_query($query);

			if (($row = lcm_fetch_array($result))) 
				foreach ($row as $key => $val) 
					$this->data[$key] = $val;
		}

		// If any, populate form values submitted
		foreach($_REQUEST as $key => $value) {
			$nkey = $key;

			if (substr($key, 0, 7) == 'client_')
				$nkey = substr($key, 7);

			$this->data[$nkey] = _request($key);
		}

		// If any, populate with session variables (for error reporting)
		if (isset($_SESSION['form_data'])) {
			foreach($_SESSION['form_data'] as $key => $value) {
				$nkey = $key;

				if (substr($key, 0, 7) == 'client_')
					$nkey = substr($key, 7);

				$this->data[$nkey] = _session($key);
			}
		}

		if (get_datetime_from_array($_SESSION['form_data'], 'date_birth', 'start', -1) != -1)
			$this->data['date_birth'] = get_datetime_from_array($_SESSION['form_data'], 'date_birth', 'start');
	}

	/* private */
	function loadCases($list_pos = 0) {
		global $prefs;

		$q = "SELECT clo.id_case, c.*
				FROM lcm_case_client_org as clo, lcm_case as c
				WHERE clo.id_client = " . $this->getDataInt('id_client', '__ASSERT__') . "
				AND clo.id_case = c.id_case ";

		// Sort cases by creation date
		$case_order = 'DESC';
		if (_request('case_order') == 'ASC' || _request('case_order') == 'DESC')
				$case_order = _request('case_order');
		
		$q .= " ORDER BY c.date_creation " . $case_order;

		$result = lcm_query($q);
		$number_of_rows = lcm_num_rows($result);
			
		if ($list_pos >= $number_of_rows)
			return;
				
		// Position to the page info start
		if ($list_pos > 0)
			if (!lcm_data_seek($result,$list_pos))
				lcm_panic("Error seeking position $list_pos in the result");

		if (lcm_num_rows($result)) {
			for ($cpt = 0; (($cpt < $prefs['page_rows']) && ($row = lcm_fetch_array($result))); $cpt++)
				array_push($this->cases, $row);
		}
	}

	function getCaseStart() {
		global $prefs;

		$start_from = _request('list_pos', 0);

		// just in case
		if (! ($start_from >= 0)) $start_from = 0;
		if (! $prefs['page_rows']) $prefs['page_rows'] = 10; 

		$this->cases = array();
		$this->case_start_from = $start_from;
		$this->loadCases($start_from);
	}

	function getCaseDone() {
		return ! (bool) (count($this->cases));
	}

	function getCaseIterator() {
		global $prefs;

		if ($this->getCaseDone())
			lcm_panic("LcmClient::getCaseIterator called but getCaseDone() returned true");

		$ret = array_shift($this->cases);
		$this->case_start_from++;

		if ($this->getCaseDone()) {
			lcm_debug('not done, reloading: ' . count($this->cases));
			$this->loadCases($this->case_start_from + $prefs['page_rows']);
		}

		lcm_debug("getCaseIterator " . count($this->cases));

		return $ret;
	}

	function getCaseTotal() {
		static $cpt_total_cache = null;

		if (is_null($cpt_total_cache)) {
			$query = "SELECT count(*) as cpt
					FROM lcm_case_client_org as clo, lcm_case as c
					WHERE clo.id_client = " . $this->getDataInt('id_client', '__ASSERT__') . "
					  AND clo.id_case = c.id_case ";

			$result = lcm_query($query);

			if (($row = lcm_fetch_array($result)))
				$cpt_total_cache = $row['cpt'];
			else
				$cpt_total_cache = 0;
		}

		return $cpt_total_cache;
	}

	function getName() {
		return get_person_name($this->data);
	}

	function validate() {
		$errors = array();

		if (! $this->getDataString('name_first'))
			$errors['name_first'] = _Ti('person_input_name_first') . _T('warning_field_mandatory');

		if (! $this->getDataString('name_last'))
			$errors['name_last'] = _Ti('person_input_name_last') . _T('warning_field_mandatory');

		if (read_meta('client_name_middle') == 'yes_mandatory' && (!$this->getDataString('name_middle')))
			$errors['name_middle'] = _Ti('person_input_name_middle') . _T('warning_field_mandatory');

		if (read_meta('client_citizen_number') == 'yes_mandatory' && (!$this->getDataString('citizen_number')))
			$errors['citizen_number'] = _Ti('person_input_citizen_number') . _T('warning_field_mandatory');

		if (read_meta('client_civil_status') == 'yes_mandatory' && (!$this->getDataString('civil_status')))
			$errors['civil_status'] = _Ti('person_input_civil_status') . _T('warning_field_mandatory');

		if (read_meta('client_income') == 'yes_mandatory' && (!$this->getDataString('income')))
			$errors['income'] = _Ti('person_input_income') . _T('warning_field_mandatory');

		// * Check gender
		$genders = array('unknown' => 1, 'female' => 1, 'male' => 1);

		if (! array_key_exists($this->getDataString('gender'), $genders))
			$errors['gender'] = _Ti('person_input_gender') . 'Incorrect format.'; // TRAD FIXME

		// * Check for date of birth
		$meta_date_birth = read_meta('client_date_birth');
		$date_birth = $this->getDataString('date_birth');

		if ($meta_date_birth == 'yes_mandatory' && (! $date_birth || $date_birth == -1)) {
			$errors['date_birth'] = _Ti('person_input_date_birth') . _T('warning_field_mandatory');
		} else if ($date_birth) {
			if (! isset_datetime_from_array($_SESSION['form_data'], 'date_birth', 'date_only')) {
				$errors['date_birth'] = _Ti('person_input_date_birth') . "Partial date."; // TRAD
			} else {
				$unix_date_birth = strtotime($date_birth);

				if ( ($unix_date_birth < 0) || !checkdate_sql($date_birth))
					$errors['date_birth'] = 'Invalid end date.'; // TRAD
			}
		}

		//
		// Custom validation functions
		//

		// * Client name (special function)
		if (include_validator_exists('client_name')) {
			include_validator('client_name');
			$foo = new LcmCustomValidateClientName();

			$test = array('first', 'last');
			
			if (substr(read_meta('client_name_middle'), 0, 3) == 'yes')
				array_push($test, 'middle');

			foreach ($test as $t) {
				$n = $this->getDataString('name_' . $t);

				if ($err = $foo->validate($this->getDataInt('id_client'), $t, $n))
					$errors['name_' . $t] = _Ti('person_input_name_' . $t) . $err;
			}
		}

		// * other fields
		$id_client = $this->getDataInt('id_client');

		$fields = array('citizen_number' => 'ClientCitizenNumber', 
					'civil_status' => 'ClientCivilStatus',
					'income' => 'ClientIncome', 
					'gender' => 'PersonGender');

		foreach ($fields as $f => $func) {
			if (include_validator_exists($f)) {
				include_validator($f);
				$class = "LcmCustomValidate$func";
				$data = $this->getDataString($f);
				$v = new $class();

				if ($err = $v->validate($id_client, $data)) 
					$errors[$f] = _Ti('person_input_' . $f) . $err;
			}
		}

		return $errors;
	}

	//
	// Save client record in DB (create/update)
	// Returns array of errors, if any
	//
	function save() {
		$errors = $this->validate();

		if (count($errors))
			return $errors;

		//
		// Update record in database
		//
		$cl = "name_first = '"  . clean_input($this->getDataString('name_first')) . "',
			   name_middle = '" . clean_input($this->getDataString('name_middle')) . "',
			   name_last = '"   . clean_input($this->getDataString('name_last')) . "',
			   gender = '"      . clean_input($this->getDataString('gender')) . "',
			   notes = '"       . clean_input($this->getDataString('notes')) . "'"; // , 

		if ($this->getDataString('date_birth'))
			$cl .= ", date_birth = '" . $this->getDataString('date_birth') . "'";
	
		$cl .= ", citizen_number = '" . clean_input($this->getDataString('citizen_number')) . "'";
		$cl .= ", civil_status = '" . clean_input($this->getDataString('civil_status')) . "'";
		$cl .= ", income = '" . clean_input($this->getDataString('income')) . "'";
	
		if ($this->getDataInt('id_client') > 0) {
			$q = "UPDATE lcm_client
				SET date_update = NOW(), 
					$cl 
				WHERE id_client = " . $this->getDataInt('id_client', '__ASSERT__');
		
			lcm_query($q);
		} else {
			$q = "INSERT INTO lcm_client
					SET date_creation = NOW(),
						date_update = NOW(),
						$cl";
	
			$result = lcm_query($q);
			$this->data['id_client'] = lcm_insert_id('lcm_client', 'id_client');
		}

		// Keywords
		update_keywords_request('client', $this->getDataInt('id_client'));

		if ($_SESSION['errors'])
			$errors = array_merge($_SESSION['errors'], $errors);

		// Insert/update client contacts
		include_lcm('inc_contacts');
		update_contacts_request('client', $this->getDataInt('id_client'));

		if ($_SESSION['errors'])
			$errors = array_merge($_SESSION['errors'], $errors);

		return $errors;
	}
}

class LcmClientInfoUI extends LcmClient {
	function LcmClientInfoUI($id_client = 0) {
		$this->LcmClient($id_client);
	}

	function printGeneral($show_subtitle = true) {
		$meta_citizen_number = read_meta('client_citizen_number');
		$meta_civil_status = read_meta('client_civil_status');
		$meta_income = read_meta('client_income');
		$meta_date_birth = read_meta('client_date_birth');

		if ($show_subtitle)
			show_page_subtitle(_T('generic_subtitle_general'), 'clients_intro');

		echo '<ul class="info">';
		echo '<li>' 
			. '<span class="label1">' . _Ti('client_input_id') . '</span>'
			. '<span class="value1">' . $this->getDataInt('id_client') . '</span>'
			. "</li>\n";

		echo '<li>'
			. '<span class="label1">' . _Ti('person_input_name') . '</span>'
			. '<span class="value1">' . $this->getName() . '</span>'
			. "</li>\n";

		if ($this->data['gender'] == 'male' || $this->getDataString('gender') == 'female')
			$gender = _T('person_input_gender_' . $this->getDataString('gender'));
		else
			$gender = _T('info_not_available');

		if (substr($meta_date_birth, 0, 3) == 'yes') {
			echo "<li>" . _Ti('person_input_date_birth');

			if (($birth = $this->getDataString('data_birth'))) {
				echo format_date($birth)
					. " (" . _T('person_info_years_old', array('years' => years_diff($this->getDataString('date_birth')))) . ")";
			} else {
				echo _T('info_not_available');
			}

			echo "</li>\n";
		}

		echo '<li>'
			. '<span class="label1">' . _Ti('person_input_gender') . '</span>'
			. '<span class="value1">' . $gender . '</span>'
			. "</li>\n";

		if (substr($meta_citizen_number, 0, 3) == 'yes')
			echo '<li>'
				. '<span class="label2">' . _Ti('person_input_citizen_number') . '</span>'
				. '<span class="value2">' . clean_output($this->getDataString('citizen_number')) . '</span>'
				. "</li>\n";

		if (substr($meta_civil_status, 0, 3) == 'yes') {
			// [ML] Patch for bug #1372138 (LCM < 0.6.4)
			$civil_status = $this->getDataString('civil_status', 'unknown');

			echo '<li>'
				. '<span class="label2">' . _Ti('person_input_civil_status') . '</span>'
				. '<span class="value2">' . _Tkw('civilstatus', $civil_status) . '</span>'
				. "</li>\n";
		}

		if (substr($meta_income, 0, 3) == 'yes') {
			// [ML] Patch for bug #1372138 (LCM < 0.6.4)
			$income = $this->getDataString('income', 'unknown');

			echo '<li>' 
				. '<span class="label2">' . _Ti('person_input_income') . '</span>'
				. '<span class="value2">' . _Tkw('income', $income) . '</span>'
				. "</li>\n";
		}

		show_all_keywords('client', $this->getDataInt('id_client'));

		echo '<li>'
			. '<span class="label2">' . _Ti('case_input_date_creation') . '</span>'
			. '<span class="value2">' . format_date($this->getDataString('date_creation')) . '</span>'
			. "</li>\n";

		echo '<li class="large">'
			. '<span class="label2">' . _Ti('client_input_notes') . '</span>' 
			. '<span class="value2">'. nl2br(clean_output($this->getDataString('notes'))) . '</span>'
			. "</li>\n";
		echo "</ul>\n";

		// Show client contacts (if any)
		show_all_contacts('client', $this->getDataInt('id_client'));
	}

	function printAttach() {
		echo '<input type="hidden" name="attach_client" value="' . $this->getDataInt('id_client', '__ASSERT__') . '" />' . "\n";
	}

	function printCases($find_case_string = '') {
		global $prefs;

		$cpt = 0;
		$my_list_pos = intval(_request('list_pos', 0));

		show_page_subtitle(_T('client_subtitle_cases'), 'cases_participants');

		echo "<p class=\"normal_text\">\n";
		show_listcase_start();

		for ($cpt = 0, $this->getCaseStart(); (! $this->getCaseDone()) && ($cpt < $prefs['page_rows']); $cpt++) {
			$item = $this->getCaseIterator();
			show_listcase_item($item, $cpt, $find_case_string, 'javascript:;', 'onclick="getCaseInfo(' . $item['id_case'] . ')"');
		}

		if (! $cpt)
			echo "No cases";

		show_listcase_end($my_list_pos, $this->getCaseTotal());
		echo "</p>\n";
		echo "</fieldset>\n";
	}

	function printEdit() {
		// Get site preferences
		$client_name_middle = read_meta('client_name_middle');
		$client_citizen_number = read_meta('client_citizen_number');
		$client_civil_status = read_meta('client_civil_status');
		$client_income = read_meta('client_income');
		$meta_date_birth = read_meta('client_date_birth');

		echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";
		
		if($this->getDataInt('id_client')) {
			echo "<tr><td>" . _T('client_input_id') . "</td>\n";
			echo "<td>" . $this->getDataInt('id_client')
				. '<input type="hidden" name="id_client" value="' . $this->getDataInt('id_client') . '" /></td></tr>' . "\n";
		}

		// Client name
		echo '<tr><td>' . f_err_star('name_first') . _T('person_input_name_first') . '</td>' . "\n";
		echo '<td><input name="name_first" value="' . clean_output($this->getDataString('name_first')) . '" class="search_form_txt" /></td></tr>' . "\n";
		
		// [ML] always show middle name, if any, no matter the configuration
		if ($this->getDataString('name_middle') || substr($client_name_middle, 0, 3) == 'yes') {
			echo '<tr><td>' . f_err_star('name_middle') . _T('person_input_name_middle') . '</td>' . "\n";
			echo '<td><input name="name_middle" value="' . clean_output($this->getDataString('name_middle')) . '" class="search_form_txt" /></td></tr>' . "\n";
		}
			
		echo '<tr><td>' . f_err_star('name_last') . _T('person_input_name_last') . '</td>' . "\n";
		echo '<td><input name="name_last" value="' . clean_output($this->getDataString('name_last')) . '" class="search_form_txt" /></td></tr>' . "\n";
		
		if (substr($meta_date_birth, 0, 3) == 'yes') {
			echo "<tr>\n";
			echo "<td>" . f_err_star('date_birth') . _Ti('person_input_date_birth') . "</td>\n";
			echo "<td>" 
				. get_date_inputs('date_birth', $this->getDataString('date_birth'), true)
				. "</td>\n";
			echo "</tr>\n";
		}
		
		echo '<tr><td>' . f_err_star('gender') . _T('person_input_gender') . '</td>' . "\n";
		echo '<td><select name="gender" class="sel_frm">' . "\n";

		$opt_sel_male = $opt_sel_female = $opt_sel_unknown = '';
		
		if ($this->getDataString('gender') == 'male')
			$opt_sel_male = 'selected="selected" ';
		else if ($this->getDataString('gender') == 'female')
			$opt_sel_female = 'selected="selected" ';
		else
			$opt_sel_unknown = 'selected="selected" ';
		
		echo '<option ' . $opt_sel_unknown . 'value="unknown">' . _T('info_not_available') . "</option>\n";
		echo '<option ' . $opt_sel_male . 'value="male">' . _T('person_input_gender_male') . "</option>\n";
		echo '<option ' . $opt_sel_female . 'value="female">' . _T('person_input_gender_female') . "</option>\n";
		
		echo "</select>\n";
		echo "</td></tr>\n";
		
		if ($this->getDataString('id_client')) {
			echo "<tr>\n";
			echo '<td>' . _Ti('time_input_date_creation') . '</td>';
			echo '<td>' . format_date($this->getDataString('date_creation'), 'full') . '</td>';
			echo "</tr>\n";
		}
		
		if (substr($client_citizen_number, 0, 3) == 'yes') {
			echo "<tr>\n";
			echo '<td>' . f_err_star('citizen_number') .  _T('person_input_citizen_number') . '</td>';
			echo '<td><input name="citizen_number" value="' .  clean_output($this->getDataString('citizen_number')) . '" class="search_form_txt" /></td>';
			echo "</tr>\n";
		}
		
		if (substr($client_civil_status, 0, 3) == 'yes') {
			echo "<tr>\n";
			echo '<td>' . f_err_star('civil_status') . _Ti('person_input_civil_status') . '</td>';
			echo '<td>';
			echo '<select name="civil_status">';

			if (! $this->getDataInt('id_client')) 
				echo '<option value=""></option>';

			$kwg = get_kwg_from_name('civilstatus');
			$all_kw = get_keywords_in_group_name('civilstatus');
	
			// A bit overkill, but if the user made the error of not entering
			// a valid civil_status, make sure that the field stays empty
			if (! $this->getDataString('civil_status') || ! count($_SESSION['errors'])) {
				if ($this->getDataInt('id_client')) {
					$this->data['civil_status'] = $all_kw['unknown']['name'];
				} else {
					$this->data['civil_status'] = $kwg['suggest'];
				}
			}
	
			foreach($all_kw as $kw) {
				$sel = ($this->getDataString('civil_status') == $kw['name'] ? ' selected="selected"' : '');
				echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>';
			}
	
			echo '</select>';
			echo '</td>';
			echo "</tr>\n";
		}

		if (substr($client_income, 0, 3) == 'yes') {
			echo "<tr>\n";
			echo '<td>' . f_err_star('income') .  _Ti('person_input_income') . '</td>';
			echo '<td>';
			echo '<select name="income">';

			if (! $this->getDataInt('id_client')) 
				echo '<option value=""></option>';

			$kwg = get_kwg_from_name('income');
			$all_kw = get_keywords_in_group_name('income');
			
			if (! $this->getDataString('income') && ! count($_SESSION['errors'])) {
				if ($this->getDataInt('id_client')) {
					$this->data['income'] = $all_kw['unknown']['name'];
				} else {
					$this->data['income'] = $kwg['suggest'];
				}
			}

			foreach($all_kw as $kw) {
				$sel = ($this->getDataString('income') == $kw['name'] ? ' selected="selected"' : '');
				echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>';
			}
			
			echo '</select>';
			echo '</td>';
			echo "</tr>\n";
		}
	
		//
		// Keywords, if any
		//
		show_edit_keywords_form('client', $this->getDataInt('id_client'));
	
		// Notes
		echo "<tr>\n";
		echo "<td>" . f_err_star('client_notes') . _Ti('client_input_notes') . "</td>\n";
		echo '<td><textarea name="client_notes" id="input_client_notes" class="frm_tarea" rows="3" cols="60">'
			. clean_output($this->getDataString('notes'))
			. "</textarea>\n"
			. "</td>\n";
		echo "</tr>\n";
	
		//
		// Contacts (e-mail, phones, etc.)
		//
		echo "<tr>\n";
		echo '<td colspan="2" align="center" valign="middle">';
		show_page_subtitle(_T('client_subtitle_contacts'));
		echo '</td>';
		echo "</tr>\n";
	
		show_edit_contacts_form('client', $this->getDataInt('id_client'));
		
		echo "</table>\n";
	}
}

?>
