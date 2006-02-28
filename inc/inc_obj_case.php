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

	$Id: inc_obj_case.php,v 1.1 2006/02/28 17:11:29 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_CASE')) return;
define('_INC_OBJ_CASE', '1');

include_lcm('inc_db');
include_lcm('inc_contacts');

class LcmCase {
	// Note: Since PHP5 we should use "private", and generates a warning,
	// but we must support PHP >= 4.0.
	var $data; 
	var $cases;
	var $cases_start_from;

	function LcmCase($id_case = 0) {
		$id_case = intval($id_case);
		$this->data = array();

		if (! ($id_case > 0))
			return;

		$query = "SELECT * FROM lcm_case WHERE id_case = $id_case";
		$result = lcm_query($query);

		if (($row = lcm_fetch_array($result))) 
			foreach ($row as $key => $val) 
				$this->data[$key] = $val;
	}

}

class LcmCaseInfoUI extends LcmCase {
	function LcmCaseInfoUI($id_case = 0) {
		$this->LcmCase($id_case);
	}

	function printGeneral($show_subtitle = true, $allow_edit = true) {
		if ($show_subtitle)
			show_page_subtitle(_T('generic_subtitle_general'), 'cases_intro');

		$add   = allowed($this->data['case'], 'w');
		$edit  = allowed($this->data['case'], 'e');
		$admin = allowed($this->data['case'], 'a');

		echo '<ul class="info">';

		// Case ID
		echo '<li>'
			. '<span class="label1">' . _Ti('case_input_id') . '</span>'
			. '<span class="value1">' . $this->data['id_case'] . '</span>'
			. "</li>\n";

		// Case title
		echo '<li>'
			. '<span class="label1">' . _Ti('case_input_title') . '</span>'
			. '<span class="value1">' . $this->data['title'] . '</span>'
			. "</li>\n";

		// Show users assigned to the case
		$q = "SELECT id_case,lcm_author.id_author,name_first,name_middle,name_last
				FROM lcm_case_author,lcm_author
				WHERE (id_case=" . $this->data['id_case'] . "
				  AND lcm_case_author.id_author=lcm_author.id_author)";
		
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
				echo '&nbsp;<a href="edit_auth.php?case=' . $case . '&amp;author=' . $author['id_author'] . '"'
					. ' title="' .
					_T('case_tooltip_view_access_rights', array('author' => $name)) . '">'
					. '<img src="images/jimmac/stock_access_rights-16.png" width="16" height="16" border="0" />'
					. '</a>';
				echo "</span>\n";
			}

			$cpt++;
		}
		
		echo "</li>\n";
				
		echo '<li>'
			. '<span class="label2">'
			. _Ti('case_input_date_creation')
			. '</span>'
			. '<span class="value2">'
			. format_date($this->data['date_creation'])
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
					. format_date($this->data['date_assignment'])
					. '</span>'
					. "</li>\n";
		}

		// Total time spent on case (redundant with "reports/times")
		$query = "SELECT sum(IF(UNIX_TIMESTAMP(fu.date_end) > 0, 
						UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) as time 
					FROM lcm_followup as fu 
					WHERE fu.id_case = " . $this->data['id_case'] . "
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
		
		if ($case_legal_reason == 'yes')
			echo '<li>'
				. '<span class="label2">'
				. _Ti('case_input_legal_reason') 
				. '</span>'
				. '<span class="value2">'
				. clean_output($this->data['legal_reason'])
				. '</span>'
				. "</li>\n";

		if ($case_alledged_crime == 'yes')
			echo '<li>'
				. '<span class="label2">'
				. _Ti('case_input_alledged_crime')
				. '</span>'
				. '<span class="value2">'
				. clean_output($this->data['alledged_crime'])
				. '</span>'
				. "</li>\n";

		// Keywords
		show_all_keywords('case', $this->data['id_case']);

		if ($this->data['stage']) {
			// There should always be a stage, but in early versions, < 0.6.0,
			// it might have been missing, causing a lcm_panic().
			$stage = get_kw_from_name('stage', $this->data['stage']);
			$id_stage = $stage['id_keyword'];
			show_all_keywords('stage', $this->data['id_case'], $id_stage);
		}

		// Notes
		echo '<li class="large">'
			. '<span class="label2">'
			. _Ti('case_input_notes')
			. '</span>'
			. '<span class="value2">'
			. nl2br($this->data['notes'])
			. '</span>'
			. "</li>\n";

	//	echo "</ul>\n";
	//	echo "<p class='normal_text'>";

		// Show case status (if closed, only site admin can re-open)
		if ($allow_edit && allowed($this->data['id_case'], 'a')) {
			// Change status form
			echo "<form action='edit_fu.php' method='get'>\n";
			echo "<input type='hidden' name='case' value='" . $this->data['case'] . "' />\n";

			echo _Ti('case_input_status');
			echo "<select name='type' class='sel_frm' onchange='lcm_show(\"submit_status\")'>\n";

			// in inc/inc_acc.php
			$statuses = get_possible_case_statuses($this->data['status']);

			foreach ($statuses as $s => $futype) {
				$sel = ($s == $this->data['status'] ? ' selected="selected"' : '');
				echo '<option value="' . $futype . '"' . $sel . '>' . _T('case_status_option_' . $s) . "</option>\n";
			}

			echo "</select>\n";
			echo "<button type='submit' name='submit' id='submit_status' value='set_status' style='visibility: hidden;' class='simple_form_btn'>" . _T('button_validate') . "</button>\n";
			echo "</form>\n";
		} else {
			echo '<li>' . _Ti('case_input_status') . _T('case_status_option_' . $this->data['status']) . "</li>\n";
		}

		// Show case stage
		if ($allow_edit && $admin) {
			// Change stage form
			echo "<form action='edit_fu.php' method='get'>\n";
			echo "<input type='hidden' name='case' value='" . $this->data['case'] . "' />\n";
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
		} else {
			echo '<li>' . _Ti('case_input_stage') . _Tkw('stage', $this->data['stage']) . "</li>\n";
		}

		// If case closed, show conclusion
		if ($this->data['status'] == 'closed') {
			// get the last relevant conclusion
			$q_tmp = "SELECT * 
				FROM lcm_followup
				WHERE id_case = $case
				AND (type = 'conclusion'
						OR type = 'stage_change')
				ORDER BY id_followup DESC 
				LIMIT 1";
			$r_tmp = lcm_query($q_tmp);
			$row_tmp = lcm_fetch_array($r_tmp);

			if ($row_tmp) {
				echo '<div style="background: #f0f0f0; padding: 4px; border: 1px solid #aaa;">';
				echo _Ti('fu_input_conclusion');
				echo get_fu_description($row_tmp, false);
				echo ' <a class="content_link" href="fu_det.php?followup=' . $row_tmp['id_followup'] . '">...</a>';
				echo "</div>\n";
				echo "<br />\n";
			}
		}

		echo '<li>' . _Ti('case_input_collaboration') . '</li>';
		echo "<ul style='padding-top: 1px; margin-top: 1px;'>";
		echo "<li>" . _Ti('case_input_collaboration_read') . _T('info_' . ($this->data['public'] ? 'yes' : 'no')) . "</li>\n";
		echo "<li>" . _Ti('case_input_collaboration_write') . _T('info_' . ($this->data['pub_write'] ? 'yes' : 'no')) . "</li>\n";
		echo "</ul>\n";
		echo "</ul>\n";
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
