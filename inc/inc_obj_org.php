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

	$Id: inc_obj_org.php,v 1.3 2006/09/15 15:52:24 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_ORG')) return;
define('_INC_OBJ_ORG', '1');

include_lcm('inc_obj_generic');
include_lcm('inc_db');
include_lcm('inc_contacts');

class LcmOrg extends LcmObject {
	// Note: Since PHP5 we should use "private", and generates a warning,
	// but we must support PHP >= 4.0.
	var $cases;
	var $case_start_from;

	function LcmOrg($id_org = 0) {
		$id_org = intval($id_org);
		$this->cases = null;
		$this->case_start_from = 0;

		$this->LcmObject();

		if ($id_org > 0) {
			$query = "SELECT * FROM lcm_org WHERE id_org = $id_org";
			$result = lcm_query($query);

			if (($row = lcm_fetch_array($result))) 
				foreach ($row as $key => $val) 
					$this->data[$key] = $val;
		}

		// If any, populate form values submitted
		foreach($_REQUEST as $key => $value) {
			$nkey = $key;

			if (substr($key, 0, 7) == 'org_')
				$nkey = substr($key, 4);

			$this->data[$nkey] = _request($key);
		}

		// If any, populate with session variables (for error reporting)
		if (isset($_SESSION['form_data'])) {
			foreach($_SESSION['form_data'] as $key => $value) {
				$nkey = $key;

				if (substr($key, 0, 7) == 'org_')
					$nkey = substr($key, 4);

				$this->data[$nkey] = _session($key);
			}
		}
	}

	/* private */
	function loadCases($list_pos = 0) {
		global $prefs;

		$q = "SELECT clo.id_case, c.*
				FROM lcm_case_client_org as clo, lcm_case as c
				WHERE clo.id_org = " . $this->getDataInt('id_org', '__ASSERT__') . "
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
			lcm_panic("LcmOrg::getCaseIterator called but getCaseDone() returned true");

		return array_shift($this->cases);
	}

	function getCaseTotal() {
		static $cpt_total_cache = null;

		if (is_null($cpt_total_cache)) {
			$query = "SELECT count(*) as cpt
					FROM lcm_case_client_org as clo, lcm_case as c
					WHERE clo.id_org = " . $this->getDataInt('id_org', '__ASSERT__') . "
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

		if (! $this->getDataString('name'))
			$errors['name'] = _Ti('org_input_name') . _T('warning_field_mandatory');

		validate_update_keywords_request('org', $this->getDataInt('id_org'));

		if ($_SESSION['errors'])
			$errors = array_merge($errors, $_SESSION['errors']);

		//
		// Custom validation functions
		//

		$id_org = $this->getDataInt('id_org');

		$fields = array('name' => 'OrgName', 
					'court_reg' => 'OrgCourtReg',
					'tax_number' => 'OrgTaxNumber', 
					'stat_number' => 'OrgStatNumber');

		foreach ($fields as $f => $func) {
			if (include_validator_exists($f)) {
				include_validator($f);
				$class = "LcmCustomValidate$func";
				$data = $this->getDataString($f);
				$v = new $class();

				if ($err = $v->validate($id_client, $data)) 
					$errors[$f] = _Ti('org_input_' . $f) . $err;
			}
		}

		return $errors;
	}

	//
	// Save organisation record in DB (create/update)
	// Returns array of errors, if any
	//
	function save() {
		$errors = $this->validate();

		if (count($errors))
			return $errors;

		//
		// Update record in database
		//

		// Record data in database
		$ol="name='" . clean_input($this->getDataString('name')) . "', "
			. "court_reg='" . clean_input($this->getDataString('court_reg')) .  "', "
			. "tax_number='" . clean_input($this->getDataString('tax_number')) .  "', "
			. "stat_number='" . clean_input($this->getDataString('stat_number')) . "', "
			. "notes='" . clean_input($this->getDataString('notes')) . "'";
	
		if ($this->getDataInt('id_org') > 0) {
			$q = "UPDATE lcm_org SET date_update=NOW(),$ol WHERE id_org = " . $this->getDataInt('id_org');
			$result = lcm_query($q);
		} else {
			$q = "INSERT INTO lcm_org SET date_update = NOW(), date_creation = NOW(), $ol";
			$result = lcm_query($q);
			$this->setDataInt('id_org', lcm_insert_id('lcm_org', 'id_org'));

			// Just by precaution
			$_SESSION['form_data']['id_org'] = $this->getDataInt('id_org');
	
			// If there is an error (ex: in contacts), we should send back to 'org_det.php?org=XX'
			// not to 'org_det.php?org=0'.
			$ref_upd_org = 'edit_org.php?org=' . $this->getDataInt('id_org');
		}

		// Keywords
		update_keywords_request('org', $this->getDataInt('id_org'));

		if ($_SESSION['errors'])
			$errors = array_merge($_SESSION['errors'], $errors);

		// Insert/update client contacts
		include_lcm('inc_contacts');
		update_contacts_request('org', $this->getDataInt('id_org'));

		if ($_SESSION['errors'])
			$errors = array_merge($_SESSION['errors'], $errors);

		return $errors;
	}
}

class LcmOrgInfoUI extends LcmOrg {
	function LcmOrgInfoUI($id_org = 0) {
		$this->LcmOrg($id_org);
	}

	function printGeneral($show_subtitle = true) {
		if ($show_subtitle)
			show_page_subtitle(_T('generic_subtitle_general'), 'clients_intro');

		echo '<ul class="info">';

		echo '<li>'
			. '<span class="label1">' . _Ti('org_input_id') . '</span>'
			. '<span class="value1">' . $this->getDataInt('id_org') . '</span>'
			. "</li>\n";

		echo '<li>' 
			. '<span class="label1">' . _Ti('org_input_name') . '</span>'
			. '<span class="value1">' . $this->getDataString('name') . '</span>'
			. "</li>\n";

		echo '<li>'
			. '<span class="label2">' . _Ti('org_input_court_reg') . '</span>'
			. '<span class="value2">' . $this->getDataString('court_reg') . '</span>'
			. "</li>\n";

		echo '<li>'
			. '<span class="label2">' . _Ti('org_input_tax_number') . '</span>'
			. '<span class="value2">' . $this->getDataString('tax_number') . '</span>'
			. "</li>\n";

		echo '<li>'
			. '<span class="label2">' . _Ti('org_input_stat_number') . '</span>'
			. '<span class="value2">' . $this->getDataString('stat_number') . '</span>'
			. "</li>\n";

		echo '<li>'
			. '<span class="label2">' . _Ti('time_input_date_creation') . '</span>' 
			. '<span class="value2">' . format_date($this->getDataString('date_creation'), 'full') . '</span>'
			. "</li>\n";

		show_all_keywords('org', $this->getDataInt('id_org'));

		echo '<li class="large">'
			. '<span class="label2">' . _Ti('org_input_notes') . '</span>'
			. '<span class="value2">' . nl2br($this->getDataString('notes')) . '</span>'
			. "</li>\n";

		echo "</ul>\n";

		// Show client contacts (if any)
		show_all_contacts('org', $this->getDataInt('id_org'));
	}

	function printAttach() {
		echo '<input type="hidden" name="attach_org" value="' . $this->getDataInt('id_org', '__ASSERT__') . '" />' . "\n";
	}

	function printCases($find_case_string = '') {
		$cpt = 0;
		$my_list_pos = intval(_request('list_pos', 0));

		show_page_subtitle(_T('org_subtitle_cases'), 'cases_participants');

		echo "<p class=\"normal_text\">\n";
		show_listcase_start();

		for ($cpt = 0, $this->getCaseStart(); (! $this->getCaseDone()); $cpt++) {
			$item = $this->getCaseIterator();
			show_listcase_item($item, $cpt, $find_case_string, 'javascript:;', 'onclick="getCaseInfo(' . $item['id_case'] . ')"');
		}

		if (! $cpt)
			echo "No cases"; // TRAD

		show_listcase_end($my_list_pos, $this->getCaseTotal());
		echo "</p>\n";
		echo "</fieldset>\n";
	}

	function printEdit() {
		echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";
		
		// Organisation ID
		if ($this->getDataInt('id_org')) {
			echo "<tr>\n";
			echo "<td>" . _Ti('org_input_id') . "</td>\n";
			echo "<td>" . $this->getDataInt('id_org')
				. '<input type="hidden" name="id_org" value="' . $this->getDataInt('id_org') . '" />'
				. "</td>\n";
			echo "</tr>\n";
		}

		// Organisation name
		echo "<tr>\n";
		echo "<td>" . f_err_star('name') . _Ti('org_input_name') . "</td>\n";
		echo '<td><input name="name" value="' . clean_output($this->getDataString('name')) . '" class="search_form_txt" />'
			. "</td>\n";
		echo "</tr>\n";

		// Court registration number
		echo "<tr>\n";
		echo "<td>" . f_err_star('court_reg') . _Ti('org_input_court_reg') . "</td>\n";
		echo '<td><input name="court_reg" value="' . clean_output($this->getDataString('court_reg')) . '" class="search_form_txt" />'
			. "</td>\n";
		echo "</tr>\n";

		// Tax number
		echo "<tr>\n";
		echo "<td>" . f_err_star('tax_number') . _Ti('org_input_tax_number') . "</td>\n";
		echo '<td><input name="tax_number" value="' . clean_output($this->getDataString('tax_number')) . '" class="search_form_txt" />'
			. "</td>\n";
		echo "</tr>\n";

		// Statistical number
		echo "<tr>\n";
		echo "<td>" . f_err_star('stat_number') . _Ti('org_input_stat_number') . "</td>\n";
		echo '<td><input name="stat_number" value="' . clean_output($this->getDataString('stat_number')) . '" class="search_form_txt" />'
			. "</td>\n";
		echo "</tr>\n";

		// Creation date
		if ($this->getDataInt('id_org')) {
			echo "<tr>\n";
			echo '<td>' . _Ti('time_input_date_creation') . '</td>';
			echo '<td>' . format_date($this->getDataString('date_creation'), 'full') . '</td>';
			echo "</tr>\n";
		}
	
		//
		// Keywords, if any
		//
		show_edit_keywords_form('org', $this->getDataInt('id_org'));

		// Notes
		echo "<tr>\n";
		echo "<td>" . f_err_star('notes') . _Ti('org_input_notes') . "</td>\n";
		echo '<td><textarea name="notes" id="input_notes" class="frm_tarea" rows="3" cols="60">'
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
	
		show_edit_contacts_form('org', $this->getDataInt('id_org'));
		
		echo "</table>\n";
	}
}

?>
