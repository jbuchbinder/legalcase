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

	$Id: inc_obj_client.php,v 1.1 2006/02/26 00:54:59 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_CLIENT')) return;
define('_INC_OBJ_CLIENT', '1');

include_lcm('inc_db');
include_lcm('inc_contacts');

class Client {
	// Note: Since PHP5 we should use "private", and generates a warning,
	// but we must support PHP >= 4.0.
	var $data; 
	var $cases;
	var $cases_start_from;

	function Client($id_client = 0) {
		$id_client = intval($id_client);
		$this->data = array();
		$this->cases = null;
		$this->case_start_from = 0;

		if (! ($id_client > 0))
			return $data;

		if ($id_client) {
			$query = "SELECT * FROM lcm_client WHERE id_client = $id_client";
			$result = lcm_query($query);

			if (($row = lcm_fetch_array($result))) 
				foreach ($row as $key => $val) 
					$this->data[$key] = $val;
		}
	}

	private function loadCases($list_pos = 0) {
		global $prefs;

		$q = "SELECT clo.id_case, c.*
				FROM lcm_case_client_org as clo, lcm_case as c
				WHERE clo.id_client = " . $this->data['id_client'] . "
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

		lcm_debug("loadCases: loaded " . count($this->cases) . " cases");
	}

	function getCaseStart() {
		$start_from = _request('list_pos', 0);

		// just in case
		if (! ($start_from >= 0)) $start_from = 0;
		if (! $prefs['page_rows']) $prefs['page_rows'] = 10; 

		$this->cases = array();
		$this->case_start_from = $start_from;
		$this->loadCases($start_from);
	}

	function getCaseDone() {
		lcm_debug("getCaseDone: " . count($this->cases));
		// return ! (bool) (count($this->cases));
		if (count($this->cases))
			return false;
		else
			return true;
	}

	function getCaseIterator() {
		global $prefs;

		if ($this->getCaseDone)
			lcm_panic("Client::getCaseIterator called but getCaseDone() returned true");

		$ret = array_shift($this->cases);

		if ($this->getCaseDone())
			$this->loadCases($start_from + $prefs['page_rows']);

		return $ret;
	}

	function getCaseTotal() {
		$query = "SELECT count(*) as cpt
				FROM lcm_case_client_org as clo, lcm_case as c
				WHERE clo.id_client = " . $this->data['id_client'] . "
				AND clo.id_case = c.id_case ";

		$result = lcm_query($query);

		if (($row = lcm_fetch_array($result)))
			return $row['cpt'];
		else
			return 0;
	}
}

class ClientInfoUI extends Client {
	function ClientHtml($id_client = 0) {
		$this->Client($id_client);
	}

	function printGeneral() {
		show_page_subtitle(_T('generic_subtitle_general'), 'clients_intro');

		echo '<ul class="info">';
		echo '<li>' 
			. '<span class="label1">' . _Ti('client_input_id') . '</span>'
			. '<span class="value1">' . $this->data['id_client'] . '</span>'
			. "</li>\n";

		echo '<li>'
			. '<span class="label1">' . _Ti('person_input_name') . '</span>'
			. '<span class="value1">' . get_person_name($this->data) . '</span>'
			. "</li>\n";

		if ($this->data['gender'] == 'male' || $this->data['gender'] == 'female')
			$gender = _T('person_input_gender_' . $this->data['gender']);
		else
			$gender = _T('info_not_available');

		echo '<li>'
			. '<span class="label1">' . _Ti('person_input_gender') . '</span>'
			. '<span class="value1">' . $gender . '</span>'
			. "</li>\n";

		if (read_meta('client_citizen_number') == 'yes')
			echo '<li>'
				. '<span class="label2">' . _Ti('person_input_citizen_number') . '</span>'
				. '<span class="value2">' . $this->data['citizen_number'] . '</span>'
				. "</li>\n";

		if (read_meta('client_civil_status') == 'yes') {
			// [ML] Patch for bug #1372138 (LCM < 0.6.4)
			if (isset($this->data['civil_status']) && $this->data['civil_status'])
				$civil_status = $this->data['civil_status'];
			else
				$civil_status = 'unknown';

			echo '<li>'
				. '<span class="label2">' . _Ti('person_input_civil_status') . '</span>'
				. '<span class="value2">' . _Tkw('civilstatus', $civil_status) . '</span>'
				. "</li>\n";
		}

		if (read_meta('client_income') == 'yes') {
			// [ML] Patch for bug #1372138 (LCM < 0.6.4)
			if (isset($this->data['income']) && $this->data['income'])
				$income = $this->data['income'];
			else
				$income = 'unknown';

			echo '<li>' 
				. '<span class="label2">' . _Ti('person_input_income') . '</span>'
				. '<span class="value2">' . _Tkw('income', $income) . '</span>'
				. "</li>\n";
		}

		show_all_keywords('client', $this->data['id_client']);

		echo '<li>'
			. '<span class="label2">' . _Ti('case_input_date_creation') . '</span>'
			. '<span class="value2">' . format_date($this->data['date_creation']) . '</span>'
			. "</li>\n";

		echo '<li class="large">'
			. '<span class="label2">' . _Ti('client_input_notes') . '</span>' 
			. '<span class="value2">'. nl2br($this->data['notes']) . '</span>'
			. "</li>\n";
		echo "</ul>\n";

		// Show client contacts (if any)
		show_all_contacts('client', $this->data['id_client']);
	}

	function printCases() {
		$cpt = 0;
		$my_list_pos = intval(_request('list_pos', 0));

		show_page_subtitle(_T('client_subtitle_cases'), 'cases_participants');

		echo "<p class=\"normal_text\">\n";
		show_listcase_start();

		for ($cpt = 0, $this->getCaseStart(); (! $this->getCaseDone()); $cpt++)
			show_listcase_item($this->getCaseIterator(), $cpt);

		if (! $cpt)
			echo "No cases";

		show_listcase_end($my_list_pos, $this->getCaseTotal());
		echo "</p>\n";
		echo "</fieldset>\n";
	}

	// XXX error checking! ($_SESSION['errors'])
	function printEdit() {
		echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";
		
		if($form_data['id_client']) {
			echo "<tr><td>" . _T('client_input_id') . "</td>\n";
			echo "<td>" . $form_data['id_client']
				. '<input type="hidden" name="id_client" value="' . $form_data['id_client'] . '" /></td></tr>' . "\n";
		}
		
		echo '<tr><td>' . f_err_star('name_first', $_SESSION['errors']) . _T('person_input_name_first') . '</td>' . "\n";
		echo '<td><input name="name_first" value="' . clean_output($form_data['name_first']) . '" class="search_form_txt" /></td></tr>' . "\n";
		
		// [ML] always show middle name, if any, no matter the configuration
		if ($form_data['name_middle'] || $client_name_middle == 'yes') {
			echo '<tr><td>' . f_err_star('name_middle', $_SESSION['errors']) . _T('person_input_name_middle') . '</td>' . "\n";
			echo '<td><input name="name_middle" value="' . clean_output($form_data['name_middle']) . '" class="search_form_txt" /></td></tr>' . "\n";
		}
			
		echo '<tr><td>' . f_err_star('name_last', $_SESSION['errors']) . _T('person_input_name_last') . '</td>' . "\n";
		echo '<td><input name="name_last" value="' . clean_output($form_data['name_last']) . '" class="search_form_txt" /></td></tr>' . "\n";
		
		echo '<tr><td>' . f_err_star('gender', $_SESSION['errors']) . _T('person_input_gender') . '</td>' . "\n";
		echo '<td><select name="gender" class="sel_frm">' . "\n";
		
		$opt_sel_male = $opt_sel_female = $opt_sel_unknown = '';
		
		if ($form_data['gender'] == 'male')
			$opt_sel_male = 'selected="selected" ';
		else if ($form_data['gender'] == 'female')
			$opt_sel_female = 'selected="selected" ';
		else
			$opt_sel_unknown = 'selected="selected" ';
		
		echo '<option ' . $opt_sel_unknown . 'value="unknown">' . _T('info_not_available') . "</option>\n";
		echo '<option ' . $opt_sel_male . 'value="male">' . _T('person_input_gender_male') . "</option>\n";
		echo '<option ' . $opt_sel_female . 'value="female">' . _T('person_input_gender_female') . "</option>\n";
		
		echo "</select>\n";
		echo "</td></tr>\n";
		
		if ($form_data['id_client']) {
			echo "<tr>\n";
			echo '<td>' . _Ti('time_input_date_creation') . '</td>';
			echo '<td>' . format_date($form_data['date_creation'], 'full') . '</td>';
			echo "</tr>\n";
		}
		
		if ($client_citizen_number == 'yes') {
			echo "<tr>\n";
			echo '<td>' . _T('person_input_citizen_number') . '</td>';
			echo '<td><input name="citizen_number" value="' . clean_output($form_data['citizen_number']) . '" class="search_form_txt"></td>';
			echo "</tr>\n";
		}
		
		global $system_kwg;
		
		if ($client_civil_status == 'yes') {
			echo "<tr>\n";
			echo '<td>' . _Ti('person_input_civil_status') . '</td>';
			echo '<td>';
			echo '<select name="civil_status">';
	
			if (! $form_data['civil_status']) {
				if ($form_data['id_client']) {
					$form_data['civil_status'] = $system_kwg['civilstatus']['keywords']['unknown']['name'];
				} else {
					$form_data['civil_status'] = $system_kwg['civilstatus']['suggest'];
				}
	
			}
	
			foreach($system_kwg['civilstatus']['keywords'] as $kw) {
				$sel = ($form_data['civil_status'] == $kw['name'] ? ' selected="selected"' : '');
				echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>';
			}
	
			echo '</select>';
			echo '</td>';
			echo "</tr>\n";
		}
		
		if ($client_income == 'yes') {
			echo "<tr>\n";
			echo '<td>' . _Ti('person_input_income') . '</td>';
			echo '<td>';
			echo '<select name="income">';
			
			if (! $form_data['income']) {
				if ($form_data['id_client']) {
					$form_data['income'] = $system_kwg['income']['keywords']['unknown']['name'];
				} else {
					$form_data['income'] = $system_kwg['income']['suggest'];
				}
			}

			foreach($system_kwg['income']['keywords'] as $kw) {
				$sel = ($form_data['income'] == $kw['name'] ? ' selected="selected"' : '');
				echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>';
			}
			
			echo '</select>';
			echo '</td>';
			echo "</tr>\n";
		}
	
		//
		// Keywords, if any
		//
		show_edit_keywords_form('client', $form_data['id_client']);
	
		// Notes
		echo "<tr>\n";
		echo "<td>" . f_err_star('notes') . _Ti('client_input_notes') . "</td>\n";
		echo '<td><textarea name="notes" id="input_notes" class="frm_tarea" rows="3" cols="60">'
			. clean_output($form_data['notes'])
			. "</textarea>\n"
			. "</td>\n";
		echo "</tr>\n";
	
		//
		// Contacts (e-mail, phones, etc.)
		//
		
		echo "<tr>\n";
		echo '<td colspan="2" align="center" valign="middle" class="heading">';
		echo '<h4>' . _T('client_subtitle_contacts') . '</h4>';
		echo '</td>';
		echo "</tr>\n";
	
		show_edit_contacts_form('client', $form_data['id_client']);
		
		echo "</table>\n";
	}
}

?>
