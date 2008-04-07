<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2008 Free Software Foundation, Inc.

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

	$Id: inc_obj_case.php,v 1.32 2008/04/07 19:14:08 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_CASE')) return;
define('_INC_OBJ_CASE', '1');

include_lcm('inc_obj_generic');
include_lcm('inc_db');
include_lcm('inc_contacts');

class LcmCase extends LcmObject {
	// Note: Since PHP5 we should use "private", and generates a warning,
	// but we must support PHP >= 4.0.
	var $followups;
	var $fu_start_from;

	function LcmCase($id_case = 0) {
		$id_case = intval($id_case);
		$this->fu_start_from = 0;

		$this->LcmObject();

		if ($id_case > 0) {
			$query = "SELECT * FROM lcm_case WHERE id_case = $id_case";
			$result = lcm_query($query);

			if (($row = lcm_fetch_array($result))) 
				foreach ($row as $key => $val) 
					$this->data[$key] = $val;

			// Case stage
			$stage = get_kw_from_name('stage', $this->getDataString('stage'));
			$this->data['id_stage'] = $stage['id_keyword'];
		}

		// If any, populate form values submitted
		foreach($_REQUEST as $key => $value) {
			$nkey = $key;

			if (substr($key, 0, 5) == 'case_')
				$nkey = substr($key, 5);

			$this->data[$nkey] = clean_input(_request($key));
		}

		// If any, populate with session variables (for error reporting)
		if (isset($_SESSION['form_data'])) {
			foreach($_SESSION['form_data'] as $key => $value) {
				$nkey = $key;

				if (substr($key, 0, 5) == 'case_')
					$nkey = substr($key, 5);

				$this->data[$nkey] = clean_input(_session($key));
			}
		}

		if ((! $id_case) || get_datetime_from_array($_SESSION['form_data'], 'assignment', 'start', -1) != -1)
			$this->data['date_assignment'] = get_datetime_from_array($_SESSION['form_data'], 'assignment', 'start', date('Y-m-d H:i:s'));
	}

	/* private */
	function loadFollowups($list_pos = 0) {
		global $prefs;

		$q = "SELECT fu.id_followup, fu.date_start, fu.date_end, fu.type, fu.description, fu.case_stage,
					fu.hidden, a.name_first, a.name_middle, a.name_last
				FROM lcm_followup as fu, lcm_author as a
				WHERE id_case = " . $this->getDataInt('id_case', '__ASSERT__') . "
				  AND fu.id_author = a.id_author ";

		// Date filters (from interface)
		if (($date_start = get_datetime_from_array($this->data, 'date_start', 'start', -1)) != -1)
			$q .= " AND fu.date_start >= '$date_start' ";

		if (($date_end = get_datetime_from_array($this->data, 'date_end', 'start', -1)) != -1)
			$q .= " AND fu.date_end <= '$date_end' ";

		// Sort follow-ups by creation date
		$fu_order = 'DESC';
		if (_request('fu_order') == 'ASC' || _request('fu_order') == 'DESC')
			$fu_order = _request('fu_order');
		
		$q .= " ORDER BY fu.date_start " . $fu_order;

		$result = lcm_query($q);
		$number_of_rows = lcm_num_rows($result);
			
		if ($list_pos >= $number_of_rows)
			return;
				
		// Position to the page info start
		if ($list_pos > 0)
			if (!lcm_data_seek($result,$list_pos))
				lcm_panic("Error seeking position $list_pos in the result");

		if (lcm_num_rows($result)) {
			for ($cpt = 0; (($cpt < $prefs['page_rows'] || _request('list_pos') == 'all') && ($row = lcm_fetch_array($result))); $cpt++)
				array_push($this->followups, $row);
		}
	}

	function getFollowupStart() {
		global $prefs;

		$this->fu_start_from = _request('list_pos', 0);

		// just in case
		if (! ($this->fu_start_from >= 0)) $this->fu_start_from = 0;
		if (! $prefs['page_rows']) $prefs['page_rows'] = 10; 

		$this->followups = array();
		$this->loadFollowups($this->fu_start_from);
	}

	function getFollowupDone() {
		return ! (bool) (count($this->followups));
	}

	function getFollowupIterator() {
		global $prefs;

		if ($this->getFollowupDone())
			lcm_panic("LcmClient::getFollowupIterator called but getFollowupDone() returned true");

		return array_shift($this->followups);
	}

	function getFollowupTotal() {
		static $cpt_total_cache = null;

		if (is_null($cpt_total_cache)) {
			$query = "SELECT count(*) as cpt
						FROM lcm_followup as fu, lcm_author as a
						WHERE id_case = " . $this->data['id_case'] . "
						  AND fu.id_author = a.id_author ";

			$result = lcm_query($query);

			if (($row = lcm_fetch_array($result)))
				$cpt_total_cache = $row['cpt'];
			else
				$cpt_total_cache = 0;
		}

		return $cpt_total_cache;
	}

	function addClient($id_client) {
		// TODO: Permissions?
		// TODO: Check if client already on case?

		$query = "INSERT INTO lcm_case_client_org
					SET id_case = " . $this->data['id_case'] . ",
						id_client = $id_client";

		lcm_query($query);
	}

	function removeClient($id_client) {
		$query = "DELETE FROM lcm_case_client_org
					WHERE id_case = " . $this->data['id_case'] . "
					  AND id_client = $id_client";

		lcm_query($query);
	}

	function validate() {
		$errors = array();

		// * Title must be non-empty
		if (! $this->getDataString('title')) 
			$errors['title'] = _Ti('case_input_title') . _T('warning_field_mandatory');

		// * Date assignment must be a valid date
		if (! checkdate_sql($this->getDataString('date_assignment')))
			$errors['date_assignment'] = _Ti('case_input_date_assigned') . 'Invalid date.'; // TRAD

		// * Depending on site policy, legal reason may be mandatory
		if (read_meta('case_legal_reason') == 'yes_mandatory' && (!$this->getDataString('legal_reason')))
			$errors['legal_reason'] = _Ti('case_input_legal_reason') . _T('warning_field_mandatory');

		// * Depending on site policy, alleged crime may be mandatory
		if (read_meta('case_alledged_crime') == 'yes_mandatory' && (!$this->getDataString('alledged_crime')))
			$errors['alledged_crime'] = _Ti('case_input_alledged_crime') . _T('warning_field_mandatory');

		// * TODO: Status must be a valid option (where do we have official list?)
		if (! $this->getDataString('status'))
			$errors['status'] = _Ti('case_input_status') . _T('warning_field_mandatory');

		// * TODO: Stage must be a valid keyword
		if (! $this->getDataString('stage'))
			$errors['stage'] = _Ti('case_input_stage') . _T('warning_field_mandatory');

		validate_update_keywords_request('case', $this->getDataInt('id_case'));
		validate_update_keywords_request('stage', $this->getDataInt('id_case'), $this->getDataInt('id_stage'));

		if ($_SESSION['errors'])
			$errors = array_merge($errors, $_SESSION['errors']);

		//
		// Custom validation functions
		//
		$id_case = $this->getDataInt('id_case');
		$fields = array('title' => 'CaseTitle',
					'legal_reason' => 'CaseLegalReason',
					'alledged_crime' => 'CaseAllegedCrime',
					'status' => 'CaseStatus',
					'stage' => 'CaseStage');

		foreach ($fields as $f => $func) {
			if (include_validator_exists($f)) {
				include_validator($f);
				$class = "LcmCustomValidate$func";
				$data = $this->getDataString($f);
				$v = new $class();

				if ($err = $v->validate($id_case, $data)) 
					$errors[$f] = _Ti('case_input_' . $f) . $err;
			}
		}

		return $errors;
	}

	function save() {
		global $author_session;

		$errors = $this->validate();

		if (count($errors))
			return $errors;

		//
		// Create the case in the database
		//

		/* [ML] Note: the 'case_notes' field is refered to as only 'notes'
		 * since the constructor of the class strips 'case_' prefixes
		 */

		$fl = "title='"              . $this->getDataString('title')            . "',
				date_assignment = '" . $this->getDataString('date_assignment')  . "',
				legal_reason='"      . $this->getDataString('legal_reason')     . "',
				alledged_crime='"    . $this->getDataString('alledged_crime')   . "',
				notes = '"           . $this->getDataString('notes')            . "',
			    status='"            . $this->getDataString('status')           . "',
			    stage='"             . $this->getDataString('stage')            . "'";

		// Put public access rights settings in a separate string
		$public_access_rights = '';

		/* 
		 * [ML] Important note: the meta 'case_*_always' defines whether the user
		 * has the choice of whether read/write should be allowed or not. If not,
		 * we take the system default value in 'case_default_*'.
		 */

		if ((read_meta('case_read_always') == 'yes') && $author_session['status'] != 'admin') {
			// impose system setting
			$public_access_rights .= "public=" . (int)(read_meta('case_default_read') == 'yes');
		} else {
			// write user selection
			$public_access_rights .= "public=" . (int)($this->getDataString('public') == 'yes');
		}

		if ((read_meta('case_write_always') == 'yes') && $author_session['status'] != 'admin') {
			// impose system setting
			$public_access_rights .= ", pub_write=" . (int)(read_meta('case_default_write') == 'yes');
		} else {
			// write user selection
			$public_access_rights .= ", pub_write=" . (int)($this->getDataString('pub_write') == 'yes');
		}

		if ($this->getDataInt('id_case') > 0) {
			// This is modification of existing case
			$id_case = $this->getDataInt('id_case');

			// Check access rights
			if (!allowed($id_case,'e'))
				lcm_panic("You don't have permission to change this case's information!");

			// If admin access is allowed, set all fields
			if (allowed($id_case,'a'))
				$q = "UPDATE lcm_case SET $fl,$public_access_rights WHERE id_case=$id_case";
			else
				$q = "UPDATE lcm_case SET $fl WHERE id_case=$id_case";

			lcm_query($q);

			// Update lcm_stage entry for case creation (of first stage!)
			// [ML] This doesn't make so much sense, but better than nothing imho..
			$q = "SELECT min(id_entry) as id_entry FROM lcm_stage WHERE id_case = $id_case";
			$tmp_result = lcm_query($q);

			if (($tmp_row = lcm_fetch_array($tmp_result))) {
				$q = "UPDATE lcm_stage
					SET date_creation = '" . $this->getDataString('date_assignment') . "'
					WHERE id_entry = " . $tmp_row['id_entry'];

				lcm_query($q);
			}
		} else {
			// This is new case
			$q = "INSERT INTO lcm_case SET id_stage = 0, date_creation = NOW(), date_update = NOW(), $fl,$public_access_rights";
			$result = lcm_query($q);
			$id_case = lcm_insert_id('lcm_case', 'id_case');
			$id_author = $author_session['id_author'];

			$this->data['id_case'] = $id_case;

			// Insert new case_author relation
			// [AG] The user creating case should always have 'admin' access right, otherwise only admin could add new user(s) to the case
			$q = "INSERT INTO lcm_case_author SET
				id_case = $id_case,
				id_author = $id_author,
				ac_read=1,
				ac_write=1,
				ac_edit=" . (int)(read_meta('case_allow_modif') == 'yes') . ",
				ac_admin=1";

			$result = lcm_query($q);

			// Get author information
			$q = "SELECT *
				FROM lcm_author
				WHERE id_author=$id_author";

			$result = lcm_query($q);
			$author_data = lcm_fetch_array($result);

			// Add 'assignment' followup to the case
			$q = "INSERT INTO lcm_followup
				SET id_case = $id_case, 
					id_stage = 0,
					id_author = $id_author,
					type = 'assignment',
					case_stage = '" . $this->getDataString('stage') . "',
					date_start = NOW(),
					date_end = NOW(),
					sumbilled = 0,
					description='" . $id_author . "'";

			lcm_query($q);
			$id_followup = lcm_insert_id('lcm_followup', 'id_followup');

			// Add lcm_stage entry
			$q = "INSERT INTO lcm_stage SET
				id_case = $id_case,
						kw_case_stage = '" . $this->getDataString('stage') . "',
						date_creation = '" . $this->getDataString('date_assignment') . "',
						id_fu_creation = $id_followup";

			lcm_query($q);
			$id_stage = lcm_insert_id('lcm_stage', 'id_entry');

			// Update the id_stage entry for lcm_case
			lcm_query("UPDATE lcm_case SET id_stage = $id_stage WHERE id_case = $id_case");
			lcm_query("UPDATE lcm_followup SET id_stage = $id_stage WHERE id_followup = $id_followup");
		}

		// Keywords
		update_keywords_request('case', $this->getDataInt('id_case'));

		$stage = get_kw_from_name('stage', $this->getDataString('stage'));
		$id_stage = $stage['id_keyword'];
		update_keywords_request('stage', $id_case, $id_stage);


		return $errors;
	}
}

class LcmCaseInfoUI extends LcmCase {
	function LcmCaseInfoUI($id_case = 0) {
		$this->LcmCase($id_case);
	}

	function printGeneral($show_subtitle = true, $allow_edit = true) {
		// Read site configuration preferences
		$case_assignment_date = read_meta('case_assignment_date');
		$case_alledged_crime  = read_meta('case_alledged_crime');
		$case_legal_reason    = read_meta('case_legal_reason');

		if ($show_subtitle)
			show_page_subtitle(_T('generic_subtitle_general'), 'cases_intro');

		$add   = allowed($this->data['case'], 'w');
		$edit  = allowed($this->data['case'], 'e');
		$admin = allowed($this->data['case'], 'a');

		//
		// Show various stages info
		//
		$q = "SELECT * FROM lcm_stage WHERE id_case = " . $this->data['case'] . " ORDER BY date_creation DESC";
		$result = lcm_query($q);

		echo '<div style="float: right; width: 180px;">';
		show_page_subtitle(_T('case_subtitle_stage'), 'cases_intro');
		echo '<ul>';

		while (($row = lcm_fetch_array($result))) {
			echo '<li>'
				. format_date($row['date_creation'], 'date_short') .  ': <br/>'
				. _Tkw('stage', $row['kw_case_stage'])
				. '</li>';
		}

		echo "</ul>\n";
		echo "</div>\n";

		//
		// Show case info
		//
		echo '<ul class="info">';

		// Case ID
		echo '<li>'
			. '<span class="label1">' . _Ti('case_input_id') . '</span>'
			. '<span class="value1">' . show_case_id($this->getDataInt('id_case')) . '</span>'
			. "</li>\n";

		// Case title
		echo '<li>'
			. '<span class="label1">' . _Ti('case_input_title') . '</span>'
			. '<span class="value1">' . $this->getDataString('title') . '</span>'
			. "</li>\n";

		// Show users assigned to the case
		$q = "SELECT id_case, a.id_author, name_first, name_middle, name_last
				FROM lcm_case_author as ca, lcm_author as a
				WHERE (id_case=" . $this->getDataInt('id_case') . "
				  AND ca.id_author = a.id_author)";
		
		$authors_result = lcm_query($q);
		$cpt = 0;

		if (lcm_num_rows($authors_result) > 1)
			echo '<li>' 
				. '<span class="label2">'
				. _Ti('case_input_authors')
				. '</span>';
		else
			echo '<li>'
				. '<span class="label2">'
				. _Ti('case_input_author')
				. '</span>';

		while ($author = lcm_fetch_array($authors_result)) {
			if ($cpt)
				echo "; ";

			$name = htmlspecialchars(get_person_name($author));

			echo '<span class="value2">'
				. '<a href="author_det.php?author=' . $author['id_author'] . '" class="content_link"'
				. ' title="' . _T('case_tooltip_view_author_details', array('author' => $name)) . '">'
				. $name
				. "</a>"
				. '</span>';

			if ($admin) {
				echo '<span class="noprint">';
				echo '&nbsp;<a href="edit_auth.php?case=' . $this->getDataInt('id_case') . '&amp;author=' . $author['id_author'] . '"'
					. ' title="' .
					_T('case_tooltip_view_access_rights', array('author' => $name)) . '">'
					. '<img src="images/jimmac/stock_access_rights-16.png" width="16" height="16" border="0" alt="" />'
					. '</a>';
				echo "</span>\n";
			}

			$cpt++;
		}

		// [ML] FIXME Double-check if this is OK here in all scenarios
		if ($admin) {
			echo '<span class="noprint">';
			echo '<a href="sel_auth.php?case=' . $this->getDataInt('id_case') . '" title="' . _T('add_user_case') . '">'
				. '<img src="images/jimmac/stock_attach-16.png" width="16" height="16" border="0" alt="' . _T('add_user_case') . '" />'
				. '</a>';
			echo "</span>\n";
		}
		
		echo "</li>\n";
				
		echo '<li>'
			. '<span class="label2">'
			. _Ti('case_input_date_creation')
			. '</span>'
			. '<span class="value2">'
			. format_date($this->getDataString('date_creation'))
			. '</span>'
			. "</li>\n";
		
		if ($case_assignment_date == 'yes') {
			// [ML] Case is assigned/unassigned when authors are added/remove
			// + case is auto-assigned when created.
			if ($this->data['date_assignment'])
				echo '<li>' 
					. '<span class="label2">'
					. _Ti('case_input_date_assigned')
					. '</span>'
					. '<span class="value2">'
					. format_date($this->getDataString('date_assignment'))
					. '</span>'
					. "</li>\n";
		}

		// Total time spent on case (redundant with "reports/times")
		$query = "SELECT " . lcm_query_sum_time('fu.date_start', 'fu.date_end') . " as time
					FROM lcm_followup as fu 
					WHERE fu.id_case = " . $this->getDataInt('id_case', '__ASSERT__') . "
					  AND fu.hidden = 'N'";
				
		$result = lcm_query($query);
		$row_tmp = lcm_fetch_array($result);

		echo '<li>'
			. '<span class="label2">'
			. _Ti('case_input_total_time') 
			. '</span>'
			. '<span class="value2">'
			. format_time_interval_prefs($row_tmp['time']) . '&nbsp;' . _T('time_info_short_hour')
			. '</span>'
			. "</li>\n";
		
		if (substr($case_legal_reason, 0, 3) == 'yes')
			echo '<li>'
				. '<span class="label2">'
				. _Ti('case_input_legal_reason') 
				. '</span>'
				. '<span class="value2">'
				. clean_output($this->getDataString('legal_reason'))
				. '</span>'
				. "</li>\n";

		if (substr($case_alledged_crime, 0, 3) == 'yes')
			echo '<li>'
				. '<span class="label2">'
				. _Ti('case_input_alledged_crime')
				. '</span>'
				. '<span class="value2">'
				. clean_output($this->getDataString('alledged_crime'))
				. '</span>'
				. "</li>\n";

		// Keywords
		show_all_keywords('case', $this->getDataInt('id_case'));

		if ($this->data['stage']) {
			// There should always be a stage, but in early versions, < 0.6.0,
			// it might have been missing, causing a lcm_panic().
			$stage = get_kw_from_name('stage', $this->getDataString('stage', '__ASSERT__'));
			$id_stage = $stage['id_keyword'];
			show_all_keywords('stage', $this->getDataInt('id_case'), $id_stage);
		}

		// Notes
		echo '<li class="large">'
			. '<span class="label2">'
			. _Ti('case_input_notes')
			. '</span>'
			. '<span class="value2">'
			. nl2br($this->getDataString('notes'))
			. '</span>'
			. "</li>\n";

	//	echo "</ul>\n";
	//	echo "<p class='normal_text'>";

		if ($allow_edit && $admin) {
			// Show case status (if closed, only site admin can re-open)
			echo '<li>';


			echo "<form action='edit_fu.php' method='get'>\n";
			echo "<input type='hidden' name='case' value='" . $this->getDataInt('id_case') . "' />\n";

			echo _Ti('case_input_status');
			echo "<select name='type' class='sel_frm' onchange='lcm_show(\"submit_status\")'>\n";

			// in inc/inc_acc.php
			$statuses = get_possible_case_statuses($this->getDataString('status'));

			foreach ($statuses as $s => $futype) {
				$sel = ($s == $this->getDataString('status') ? ' selected="selected"' : '');
				echo '<option value="' . $futype . '"' . $sel . '>' . _T('case_status_option_' . $s) . "</option>\n";
			}

			echo "</select>\n";
			echo "<button type='submit' name='submit' id='submit_status' value='set_status' style='visibility: hidden;' class='simple_form_btn'>" . _T('button_validate') . "</button>\n";
			echo "</form>\n";

			// Show case stage
			echo "<form action='edit_fu.php' method='get'>\n";
			echo "<input type='hidden' name='case' value='" . $this->getDataInt('id_case') . "' />\n";
			echo "<input type='hidden' name='type' value='stage_change' />\n";

			echo _Ti('case_input_stage');
			echo "<select name='stage' class='sel_frm' onchange='lcm_show(\"submit_stage\")'>\n";

			$stage_kws = get_keywords_in_group_name('stage');
			foreach ($stage_kws as $kw) {
				$sel = ($kw['name'] == $this->data['stage'] ? ' selected="selected"' : '');
				echo "\t\t<option value='" . $kw['name'] . "'" . "$sel>" . _T(remove_number_prefix($kw['title'])) . "</option>\n";
			}

			echo "</select>\n";
			echo "<button type='submit' name='submit' id='submit_stage' value='set_stage' style='visibility: hidden;' class='simple_form_btn'>" . _T('button_validate') . "</button>\n";
			echo "</form>\n";

			echo "</li>\n";
		} else {
			echo '<li>' . _Ti('case_input_status') . _T('case_status_option_' . $this->getDataString('status')) . "</li>\n";
			echo '<li>' . _Ti('case_input_stage') . _Tkw('stage', $this->data['stage']) . "</li>\n";
		}

		// If case closed, show conclusion
		if ($this->data['status'] == 'closed') {
			// get the last relevant conclusion
			$q_tmp = "SELECT * 
				FROM lcm_followup
				WHERE id_case = " . $this->getDataInt('id_case') . "
				AND (type = 'conclusion'
						OR type = 'stage_change')
				ORDER BY id_followup DESC 
				LIMIT 1";
			$r_tmp = lcm_query($q_tmp);
			$row_tmp = lcm_fetch_array($r_tmp);

			if ($row_tmp) {
				echo '<li>';
				echo '<div style="background: #f0f0f0; padding: 4px; border: 1px solid #aaa;">';
				echo _Ti('fu_input_conclusion');
				echo get_fu_description($row_tmp, false);
				echo ' <a class="content_link" href="fu_det.php?followup=' . $row_tmp['id_followup'] . '">...</a>';
				echo "</div>\n";
				echo "</li>\n";
			}
		}

		echo '<li>' . _Ti('case_input_collaboration');
		echo "<ul style='padding-top: 1px; margin-top: 1px;'>";
		echo "<li>" . _Ti('case_input_collaboration_read') . _T('info_' . ($this->getDataInt('public') ? 'yes' : 'no')) . "</li>\n";
		echo "<li>" . _Ti('case_input_collaboration_write') . _T('info_' . ($this->getDataInt('pub_write') ? 'yes' : 'no')) . "</li>\n";
		echo "</ul>\n";
		echo "</li>\n";
		echo "</ul>\n";

		// clear the right column with stage info
		echo "<div style='clear: right;'></div>\n";
	}

	// XXX error checking! ($_SESSION['errors'])
	function printEdit() {
		// Read site configuration preferences
		$case_assignment_date = read_meta('case_assignment_date');
		$case_alledged_crime  = read_meta('case_alledged_crime');
		$case_legal_reason    = read_meta('case_legal_reason');
		$case_allow_modif     = read_meta('case_allow_modif');

		echo '<table class="tbl_usr_dtl">' . "\n";
		
		// Case ID (if editing existing case)
		if ($this->getDataInt('id_case')) {
			echo "<tr>"
				. "<td>" . _T('case_input_id') . "</td>"
				. "<td>" . $this->getDataInt('id_case')
				. '<input type="hidden" name="id_case" value="' . $this->getDataInt('id_case') . '" />'
				. "</td></tr>\n";
		}
		
		echo '<tr><td><label for="input_case_title">' 
			. f_err_star('title') . _T('case_input_title')
			. "</label></td>\n";
		echo '<td><input size="35" name="title" id="input_case_title" value="'
			. clean_output($this->getDataString('title'))
			. '" class="search_form_txt" />';
		echo "</td></tr>\n";
		
		// Date of earlier assignment
		if ($case_assignment_date == 'yes') {
			echo "<tr>\n";
			echo "<td>" . f_err_star('date_assignment') . _Ti('case_input_date_assigned') . "</td>\n";
			echo "<td>" 
				. get_date_inputs('assignment', $this->getDataString('date_assignment'), false)
				. "</td>\n";
			echo "</tr>\n";
		}
			
		// Legal reason
		if (substr($case_legal_reason, 0, 3) == 'yes') {
			echo '<tr><td><label for="input_legal_reason">' . f_err_star('legal_reason') . _T('case_input_legal_reason') . "</label>"
				. ($case_legal_reason == 'yes_mandatory' ? '<br/>(' . _T('keywords_input_policy_mandatory') . ')' : '')
				. "</td>\n";
			echo '<td>';
			echo '<textarea name="legal_reason" id="input_legal_reason" class="frm_tarea" rows="2" cols="60">';
			echo clean_output($this->getDataString('legal_reason'));
			echo "</textarea>";
			echo "</td>\n";
			echo "</tr>\n";
		}
		
		// Alledged crime
		if (substr($case_alledged_crime, 0, 3) == 'yes') {
			echo '<tr><td><label for="input_alledged_crime">' . f_err_star('alledged_crime') . _T('case_input_alledged_crime') . "</label>"
				. ($case_alledged_crime == 'yes_mandatory' ? '<br/>(' . _T('keywords_input_policy_mandatory') . ')' : '')
				. "</td>\n";
			echo '<td>';
			echo '<textarea name="alledged_crime" id="input_alledged_crime" class="frm_tarea" rows="2" cols="60">';
			echo clean_output($this->getDataString('alledged_crime'));
			echo '</textarea>';
			echo "</td>\n";
			echo "</tr>\n";
		}
		
		// Keywords (if any)
		show_edit_keywords_form('case', $this->getDataInt('id_case'));
		
		$id_stage = 0; // new case, stage not yet known
		if ($this->getDataString('stage')) {
			$stage = get_kw_from_name('stage', $this->getDataString('stage', '__ASSERT__'));
			$id_stage = $stage['id_keyword'];
		}

		show_edit_keywords_form('stage', $this->getDataInt('id_case'), $id_stage);
		
		// Notes
		echo "<tr>\n";
		echo "<td><label for='input_case_notes'>" . f_err_star('case_notes') . _Ti('case_input_notes') . "</label></td>\n";
		echo '<td><textarea name="case_notes" id="input_case_notes" class="frm_tarea" rows="3" cols="60">'
			. clean_output($this->getDataString('notes'))
			. "</textarea>\n"
			. "</td>\n";
		echo "</tr>\n";
		
		// Case status
		echo '<tr><td><label for="input_status">' . f_err_star('status') . _Ti('case_input_status') . "</label></td>\n";
		echo '<td>';
		echo '<select name="status" id="input_status" class="sel_frm">' . "\n";
		$statuses = ($this->getDataInt('id_case') ? array('draft','open','suspended','closed','merged') : array('draft','open') );
		
		foreach ($statuses as $s) {
			$sel = ($s == $this->getDataString('status') ? ' selected="selected"' : '');
			echo '<option value="' . $s . '"' . $sel . ">" 
				. _T('case_status_option_' . $s)
				. "</option>\n";
		}

		echo "</select></td>\n";
		echo "</tr>\n";
		
		// Case stage
		if (! $this->getDataString('stage'))
			$this->data['stage'] = get_suggest_in_group_name('stage');
		
		$kws = get_keywords_in_group_name('stage');
		
		echo '<tr><td><label for="input_stage">' . f_err_star('stage') . _T('case_input_stage') . "</label></td>\n";
		echo '<td><select name="stage" id="input_stage" class="sel_frm">' . "\n";
		foreach($kws as $kw) {
			$sel = ($kw['name'] == $this->data['stage'] ? ' selected="selected"' : '');
			echo "\t\t\t\t<option value='" . $kw['name'] . "'" . "$sel>" . _T(remove_number_prefix($kw['title'])) . "</option>\n";
		}
		echo "</select></td>\n";
		echo "</tr>\n";
		
		// Public access rights
		// FIXME FIXME FIXME
		if ( $this->data['admin'] || (read_meta('case_read_always') != 'yes') || (read_meta('case_write_always') != 'yes') ) {
			$dis = isDisabled(! allowed($this->getDataInt('id_case'), 'a'));
			echo '<tr><td colspan="2">' . _T('case_input_collaboration')
				.  ' <br /><ul>';

			if ( (read_meta('case_read_always') != 'yes') || $GLOBALS['author_session']['status'] == 'admin') {
				echo '<li style="list-style-type: none;">';
				echo '<input type="checkbox" name="public" id="case_public_read" value="yes"';

				if ($_SESSION['form_data']['public'])
					echo ' checked="checked"';

				echo "$dis />";
				echo '<label for="case_public_read">' . _T('case_input_collaboration_read') . "</label></li>\n";
			}

			if ( (read_meta('case_write_always') != 'yes') || _session('admin')) {
				echo '<li style="list-style-type: none;">';
				echo '<input type="checkbox" name="pub_write" id="case_public_write" value="yes"';

				if (_session('pub_write'))
					echo ' checked="checked"';

				echo "$dis />";
				echo '<label for="case_public_write">' . _T('case_input_collaboration_write') . "</label></li>\n";
			}

			echo "</ul>\n";

			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
	}

	function printFollowups($show_filters = false) {
		$cpt = 0;
		$my_list_pos = intval(_request('list_pos', 0));

		show_page_subtitle(_T('case_subtitle_followups'), 'cases_followups');

		// Show filters (if not shown in ajaxed page)
		if ($show_filters) {
			// By default, show from "case creation date" to NOW().
			$link = new Link();
			$link->delVar('date_start_day');
			$link->delVar('date_start_month');
			$link->delVar('date_start_year');
			$link->delVar('date_end_day');
			$link->delVar('date_end_month');
			$link->delVar('date_end_year');
			echo $link->getForm();

			$date_end = get_datetime_from_array($_REQUEST, 'date_end', 'end', '0000-00-00 00:00:00'); // date('Y-m-d H:i:s'));
			$date_start = get_datetime_from_array($_REQUEST, 'date_start', 'start', '0000-00-00 00:00:00'); // $row['date_creation']);

			echo _Ti('time_input_date_start');
			echo get_date_inputs('date_start', $date_start);

			echo _Ti('time_input_date_end');
			echo get_date_inputs('date_end', $date_end);
			echo ' <button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
			echo "</form>\n";

			echo "<div style='margin-bottom: 4px;'>&nbsp;</div>\n"; // FIXME patch for now (leave small space between filter and list)
		}

		show_listfu_start('general', false);

		for ($cpt = 0, $this->getFollowupStart(); (! $this->getFollowupDone()); $cpt++) {
			$item = $this->getFollowupIterator();
			show_listfu_item($item, $cpt);
		}

		if (! $cpt)
			echo "No followups"; // TRAD

		show_list_end($my_list_pos, $this->getFollowupTotal(), true);
	}
}

class LcmCaseListUI extends LcmObject {
	var $list_pos;
	var $number_of_rows;

	// filters (other smaller details are set in LcmObjet's data)
	var $search;
	var $date_start;
	var $date_end;

	function LcmCaseListUI() {
		$this->LcmObject();

		$this->search = '';
		$this->date_start = '';
		$this->date_end = '';

		$this->list_pos = intval(_request('list_pos', 0));
		$this->number_of_rows = 0;
	}

	function setSearchTerm($term) {
		$this->search = $term;
	}

	function setDateInterval($start, $end) {
		if ($start && $start != -1)
			$this->date_start = $start;

		if ($end && $end != -1)
			$this->date_end = $end;
	}

	function start() {
		show_listcase_start();
	}

	function printList() {
		global $prefs;

		// Select cases of which the current user is author
		$q = "SELECT DISTINCT c.id_case, title, status, public, pub_write, date_creation
			FROM lcm_case as c NATURAL JOIN lcm_case_author as a ";

		if ($this->search)
			$q .= " NATURAL LEFT JOIN lcm_keyword_case as kc ";

		//
		// Apply filters to SELECT output
		//

		$q .= " WHERE 1=1 ";

		// Add search criteria, if any
		if ($this->search) {
			$q .= " AND (";

			if (is_numeric($this->search))
				$q .= " (c.id_case = $this->search) OR ";
		
			$q .= " (kc.value LIKE '%" . $this->search . "%') OR "
				. " (c.title LIKE '%" . $this->search . "%') ";

			$q .= " )";
		}

		//
		// Case owner: may be used by listcases.php, archives.php, author_det.php, etc.
		// Also, it may be a user checking another user's profile (in that case, show only public cases)
		// or it may be an admin checking another user's profile. etc.
		//
		global $author_session;

		$owner_filter = $this->getDataString('owner', $prefs['case_owner']);
		$owner_id     = $this->getDataInt('id_author', $author_session['id_author']);

		$q_owner = " (a.id_author = " . $owner_id;

		if ($owner_id == $author_session['id_author']) {
			// Either in listcases, or user looking at his page in author_det
			if ($owner_filter == 'public')
				$q_owner .= " OR c.public = 1";

			if ($author_session['status'] == 'admin' && $owner_filter == 'all')
				$q_owner .= " OR 1=1 ";
		} else {
			// If not an admin, show only public cases of that user
			if ($author_session['status'] != 'admin')
				$q_owner .= " AND c.public = 1";
		}

		$q_owner .= " ) ";
		$q .= " AND " . $q_owner; 

		// Period (date_creation) to show
		if ($this->date_start || $this->date_end) {
			if ($this->date_start)
				$q .= " AND date_creation >= '" . $this->date_start . "'";

			if ($this->date_end)
				$q .= " AND date_creation <= '" . $this->date_end . "'";
		} else {
			if ($prefs['case_period'] < 1900) // since X days
				$q .= " AND " . lcm_query_subst_time('date_creation', 'NOW()') . ' < ' . $prefs['case_period'] * 3600 * 24;
			else // for year X
				$q .= " AND " . lcm_query_trunc_field('date_creation', 'year') . ' = ' . $prefs['case_period'];
		}

		//
		// Sort results
		//

		$sort_clauses = array();
		$sort_allow = array('ASC' => 1, 'DESC' => 1);

		// Sort cases by creation date
		if ($sort_allow[_request('status_order')])
			$sort_clauses[] = "status " . _request('status_order');

		if ($sort_allow[_request('case_order')])
			$sort_clauses[] = 'date_creation ' . _request('case_order');
		elseif ($sort_allow[_request('upddate_order')])
			$sort_clauses[] = "date_update " . _request('upddate_order');
		else
			$sort_clauses[] = 'date_creation DESC'; // default

		$q .= " ORDER BY " . implode(', ', $sort_clauses);

		$result = lcm_query($q);

		// Check for correct start position of the list
		$this->number_of_rows = lcm_num_rows($result);

		if ($this->list_pos >= $this->number_of_rows)
			$this->list_pos = 0;

		// Position to the page info start
		if ($this->list_pos > 0) {
			if (! lcm_data_seek($result, $this->list_pos))
				lcm_panic("Error seeking position " . $this->list_pos . " in the result");
		}

		for ($i = 0; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))); $i++)
			show_listcase_item($row, $i, $this->search);
	}

	function finish() {
		show_listcase_end($this->list_pos, $this->number_of_rows);
	}
}

?>
