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

	$Id: inc_obj_case.php,v 1.3 2006/03/01 21:57:12 mlutfy Exp $
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
	var $followups;
	var $fu_start_from;

	function LcmCase($id_case = 0) {
		$id_case = intval($id_case);
		$this->data = array();
		$this->fu_start_from = 0;

		if (! ($id_case > 0))
			return;

		$query = "SELECT * FROM lcm_case WHERE id_case = $id_case";
		$result = lcm_query($query);

		if (($row = lcm_fetch_array($result))) 
			foreach ($row as $key => $val) 
				$this->data[$key] = $val;
	}

	/* private */
	function loadFollowups($list_pos = 0) {
		global $prefs;

		$q = "SELECT fu.id_followup, fu.date_start, fu.date_end, fu.type, fu.description, fu.case_stage,
					fu.hidden, a.name_first, a.name_middle, a.name_last
				FROM lcm_followup as fu, lcm_author as a
				WHERE id_case = " . $this->data['id_case'] . "
				  AND fu.id_author = a.id_author ";

		// TODO
		// Add date_start filter!

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
			for ($cpt = 0; (($cpt < $prefs['page_rows']) && ($row = lcm_fetch_array($result))); $cpt++)
				array_push($this->followups, $row);
		}
	}

	function getFollowupStart() {
		$start_from = _request('list_pos', 0);

		// just in case
		if (! ($start_from >= 0)) $start_from = 0;
		if (! $prefs['page_rows']) $prefs['page_rows'] = 10; 

		$this->followups = array();
		$this->fu_start_from = $start_from;
		$this->loadFollowups($start_from);
	}

	function getFollowupDone() {
		return ! (bool) (count($this->followups));
	}

	function getFollowupIterator() {
		global $prefs;

		if ($this->getFollowupDone)
			lcm_panic("LcmClient::getFollowupIterator called but getFollowupDone() returned true");

		$ret = array_shift($this->followups);

		if ($this->getFollowupDone())
			$this->loadFollowups($start_from + $prefs['page_rows']);

		return $ret;
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

	function getName() {
		return get_person_name($this->data);
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

		echo '<li>' . _Ti('case_input_collaboration');
		echo "<ul style='padding-top: 1px; margin-top: 1px;'>";
		echo "<li>" . _Ti('case_input_collaboration_read') . _T('info_' . ($this->data['public'] ? 'yes' : 'no')) . "</li>\n";
		echo "<li>" . _Ti('case_input_collaboration_write') . _T('info_' . ($this->data['pub_write'] ? 'yes' : 'no')) . "</li>\n";
		echo "</ul>\n";
		echo "</li>\n";
		echo "</ul>\n";
	}

	// XXX error checking! ($_SESSION['errors'])
	function printEdit() {
		echo '<table class="tbl_usr_dtl">' . "\n";
		
		// Case ID (if editing existing case)
		if ($this->data['id_case']) {
			echo "<tr>"
				. "<td>" . _T('case_input_id') . "</td>"
				. "<td>" . $this->data['id_case']
				. '<input type="hidden" name="id_case" value="' . $this->data['id_case'] . '\" />'
				. "</td></tr>\n";
		}
		
		echo '<tr><td><label for="input_title">'
			. f_err_star('title', $_SESSION['errors']) . _T('case_input_title')
			. "</label></td>\n";
		echo '<td><input size="35" name="title" id="input_case_title" value="'
			. clean_output($this->data['title'])
			. '" class="search_form_txt" />';
		echo "</td></tr>\n";
		
		// Date of earlier assignment
		if ($case_assignment_date == 'yes') {
			echo "<tr>\n";
			echo "<td>" . f_err_star('date_assignment') . _Ti('case_input_date_assigned') . "</td>\n";
			echo "<td>" 
				. get_date_inputs('assignment', $this->data['date_assignment'], false)
				. "</td>\n";
			echo "</tr>\n";
		}
			
		// Legal reason
		if ($case_legal_reason == 'yes') {
			echo '<tr><td><label for="input_legal_reason">' . _T('case_input_legal_reason') . "</label></td>\n";
			echo '<td>';
			echo '<textarea name="legal_reason" id="input_legal_reason" class="frm_tarea" rows="2" cols="60">';
			echo clean_output($this->data['legal_reason']);
			echo "</textarea>";
			echo "</td>\n";
			echo "</tr>\n";
		}
		
		// Alledged crime
		if ($case_alledged_crime == 'yes') {
			echo '<tr><td><label for="input_alledged_crime">' . _T('case_input_alledged_crime') . "</label></td>\n";
			echo '<td>';
			echo '<textarea name="alledged_crime" id="input_alledged_crime" class="frm_tarea" rows="2" cols="60">';
			echo clean_output($this->data['alledged_crime']);
			echo '</textarea>';
			echo "</td>\n";
			echo "</tr>\n";
		}
		
		// Keywords (if any)
		show_edit_keywords_form('case', $this->data['id_case']);
		
		$id_stage = 0; // new case, stage not yet known
		if ($this->data['stage']) {
			$stage = get_kw_from_name('stage', $this->data['stage']);
			$id_stage = $stage['id_keyword'];
		}

		show_edit_keywords_form('stage', $this->data['id_case'], $id_stage);
		
		// Notes
		echo "<tr>\n";
		echo "<td><label for='input_notes'>" . f_err_star('notes') . _Ti('case_input_notes') . "</label></td>\n";
		echo '<td><textarea name="notes" id="input_notes" class="frm_tarea" rows="3" cols="60">'
			. clean_output($this->data['notes'])
			. "</textarea>\n"
			. "</td>\n";
		echo "</tr>\n";
		
		// Case status
		echo '<tr><td><label for="input_status">' . f_err_star('status') . _Ti('case_input_status') . "</label></td>\n";
		echo '<td>';
		echo '<select name="status" id="input_status" class="sel_frm">' . "\n";
		$statuses = ($existing ? array('draft','open','suspended','closed','merged') : array('draft','open') );
		
		foreach ($statuses as $s) {
			$sel = ($s == $this->data['status'] ? ' selected="selected"' : '');
			echo '<option value="' . $s . '"' . $sel . ">" 
				. _T('case_status_option_' . $s)
				. "</option>\n";
		}

		echo "</select></td>\n";
		echo "</tr>\n";
		
		// Case stage
		if (! $this->data['stage'])
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
			$dis = ( allowed($this->data['id_case'], 'a') ? '' : ' disabled="disabled"');
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

			if ( (read_meta('case_write_always') != 'yes') || $_SESSION['form_data']['admin']) {
				echo '<li style="list-style-type: none;">';
				echo '<input type="checkbox" name="pub_write" id="case_public_write" value="yes"';

				if ($_SESSION['form_data']['pub_write'])
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

	function printFollowups() {
		$cpt = 0;
		$my_list_pos = intval(_request('list_pos', 0));

		show_page_subtitle(_T('case_subtitle_followups'), 'cases_followups');

		echo "<p class=\"normal_text\">\n";
		show_listfu_start('general', false);

		for ($cpt = 0, $this->getFollowupStart(); (! $this->getFollowupDone()); $cpt++) {
			$item = $this->getFollowupIterator();
			show_listfu_item($item, $cpt);
		}

		if (! $cpt)
			echo "No followups";

		show_list_end($my_list_pos, $this->getFollowupTotal(), true);
		echo "</p>\n";
	}
}

?>
