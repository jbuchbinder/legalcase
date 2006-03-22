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

	$Id: inc_obj_exp.php,v 1.1 2006/03/22 23:27:40 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_EXPENSE')) return;
define('_INC_OBJ_EXPENSE', '1');

include_lcm('inc_obj_generic');

class LcmExpense extends LcmObject {
	// Note: Since PHP5 we should use "private", and generates a warning,
	// but we must support PHP >= 4.0.
	var $comments;
	var $comment_start_from;

	function LcmExpense($id_expense = 0) {
		$id_expense = intval($id_expense);
		$this->comments = null;
		$this->comment_start_from = 0;

		$this->LcmObject();

		if ($id_expense > 0) {
			$query = "SELECT * FROM lcm_expense WHERE id_expense = $id_expense";
			$result = lcm_query($query);

			if (($row = lcm_fetch_array($result))) 
				foreach ($row as $key => $val) 
					$this->data[$key] = $val;
		}

		// If any, populate form values submitted
		foreach($_REQUEST as $key => $value) {
			$nkey = $key;

			if (substr($key, 0, 7) == 'expense_')
				$nkey = substr($key, 7);

			$this->data[$nkey] = _request($key);
		}

		// If any, populate with session variables (for error reporting)
		if (isset($_SESSION['form_data'])) {
			foreach($_SESSION['form_data'] as $key => $value) {
				$nkey = $key;

				if (substr($key, 0, 7) == 'expense_')
					$nkey = substr($key, 7);

				$this->data[$nkey] = _session($key);
			}
		}
	}

	/* private */
	function loadComments($list_pos = 0) {
		global $prefs;

		$q = "SELECT ec.*
				FROM lcm_expense_comment as ec, lcm_expense as e
				WHERE ec.id_expense = " . $this->getDataInt('id_expense', '__ASSERT__') . "
				AND ec.id_expense = e.id_expense ";

		// Sort cases by creation date
		$case_order = 'DESC';
		if (_request('expense_order') == 'ASC' || _request('expense_order') == 'DESC')
				$case_order = _request('expense_order');
		
		$q .= " ORDER BY ec.date_creation " . $case_order;

		$result = lcm_query($q);
		$number_of_rows = lcm_num_rows($result);
			
		if ($list_pos >= $number_of_rows)
			return;
				
		// Position to the page info start
		if ($list_pos > 0)
			if (!lcm_data_seek($result, $list_pos))
				lcm_panic("Error seeking position $list_pos in the result");

		if (lcm_num_rows($result)) {
			for ($cpt = 0; (($cpt < $prefs['page_rows']) && ($row = lcm_fetch_array($result))); $cpt++)
				array_push($this->comments, $row);
		}
	}

	function getCommentStart() {
		$start_from = _request('list_pos', 0);

		// just in case
		if (! ($start_from >= 0)) $start_from = 0;
		if (! $prefs['page_rows']) $prefs['page_rows'] = 10; 

		$this->comments = array();
		$this->comment_start_from = $start_from;
		$this->loadCases($start_from);
	}

	function getCommentDone() {
		return ! (bool) (count($this->comments));
	}

	function getCommentIterator() {
		global $prefs;

		if ($this->getCommentDone)
			lcm_panic("LcmComment::getCommentIterator called but getCommentDone() returned true");

		$ret = array_shift($this->comments);

		if ($this->getCommentDone())
			$this->loadComments($start_from + $prefs['page_rows']);

		return $ret;
	}

	function getCommentTotal() {
		static $cpt_total_cache = null;

		if (is_null($cpt_total_cache)) {
			$query = "SELECT count(*) as cpt
					FROM lcm_expense_comment as ec
					WHERE ec.id_expense = " . $this->getDataInt('id_expense', '__ASSERT__');

			$result = lcm_query($query);

			if (($row = lcm_fetch_array($result)))
				$cpt_total_cache = $row['cpt'];
			else
				$cpt_total_cache = 0;
		}

		return $cpt_total_cache;
	}

	function validate() { // TODO
		$errors = array();

		if (!$this->getDataString('description'))
			$errors['description'] = _Ti('expense_input_description') . _T('warning_field_mandatory');

		//
		// Custom validation functions
		//

		// * other fields
		$id_expense = $this->getDataInt('id_expense');

		$fields = array('description' => 'ExpenseDescription'); 

		foreach ($fields as $f => $func) {
			if (include_validator_exists($f)) {
				include_validator($f);
				$class = "LcmCustomValidate$func";
				$data = $this->getDataString($f);
				$v = new $class();

				if ($err = $v->validate($id_client, $data)) 
					$errors[$f] = _Ti('expense_input_' . $f) . $err;
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
		$cl = "type   = '"      . $this->getDataString('type') . "',
			   cost   = "      . $this->getDataInt('cost') . ",
			   description = '" . $this->getDataString('description') . "',
			   date_update = 'NOW()',
			   pub_read  = 1,
			   pub_write = 1";
		
		// XXX add case where id_admin should be updated
		// XXX add status (user can delete, admin can grant/approve/delete)
	
		if ($this->getDataInt('id_expense') > 0) {
			$q = "UPDATE lcm_expense
				SET $cl 
				WHERE id_expense = " . $this->getDataInt('id_expense', '__ASSERT__');
		
			lcm_query($q);
		} else {
			$q = "INSERT INTO lcm_expense
					SET date_creation = NOW(), 
						id_admin    = 0,
						id_author   = " . $this->getDataInt('id_author') . ",
						id_followup = "  . $this->getDataInt('id_followup') . ",
						id_case     = "      . $this->getDataInt('id_case') . ",
						status      = 'pending',
						$cl";
	
			$result = lcm_query($q);
			$this->data['id_expense'] = lcm_insert_id('lcm_expense', 'id_expense');
		}

		return $errors;
	}
}

class LcmExpenseInfoUI extends LcmExpense {
	function LcmExpenseInfoUI($id_expense = 0) {
		$this->LcmExpense($id_expense);
	}

	function printGeneral($show_subtitle = true) {
		if ($show_subtitle)
			show_page_subtitle(_T('generic_subtitle_general'), 'expenses_intro');

		echo '<ul class="info">';
		echo '<li>' 
			. '<span class="label1">' . _Ti('expense_input_id') . '</span>'
			. '<span class="value1">' . $this->getDataInt('id_expense') . '</span>'
			. "</li>\n";

		echo '<li class="large">'
			. '<span class="label2">' . _Ti('expenses_input_description') . '</span>' 
			. '<span class="value2">'. nl2br(clean_output($this->getDataString('description'))) . '</span>'
			. "</li>\n";

		echo '<li>'
			. '<span class="label2">' . _Ti('expense_input_cost') . '</span>'
			. '<span class="value2">' .  format_money($this->getDataInt('cost')) . '</span>'
			. "</li>\n";

		echo "</ul>\n";
	}

	function printComments($find_case_string = '') {
		$cpt = 0;
		$my_list_pos = intval(_request('list_pos', 0));

		show_page_subtitle(_T('expenses_subtitle_comments'), 'expenses_comments');

		echo '<ul>';

		for ($cpt = 0, $this->getCommentStart(); (! $this->getCommentDone()); $cpt++) {
			$item = $this->getCommentIterator();
			echo '<li>' . $item . "</li>\n";
		}

		if (! $cpt)
			echo "<li>No comments</li>";

		show_listcase_end($my_list_pos, $this->getCommentTotal());
		echo "</ul>\n";
	}

	function printEdit() {
		echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";

		// TODO:
		// Ajouter contexte
		// Ajouter auteur, etc.
		// Ajouter "approved by" si id_admin != 0
		
		if($this->getDataInt('id_expense')) {
			echo "<tr><td>" . _T('expense_input_id') . "</td>\n";
			echo "<td>" . $this->getDataInt('id_expense')
				. '<input type="hidden" name="id_expense" value="' . $this->getDataInt('id_expense') . '" /></td></tr>' . "\n";
		}

		echo '<tr><td>' . f_err_star('description') . _T('expense_input_description') . '</td>' . "\n";
		echo '<td><textarea name="description" id="input_expense_description" class="frm_tarea" rows="3" cols="60">'
			. clean_output($this->getDataString('description'))
			. "</textarea>\n"
			. "</td>\n";
		echo "</tr>\n";
		
		echo '<tr><td>' . f_err_star('type') . _T('expense_input_type') . '</td>' . "\n";
		echo '<td><input name="type" value="' . clean_output($this->getDataString('type')) . '" class="search_form_txt" /></td></tr>' . "\n";

		// TODO: add currency
		echo '<tr><td>' . f_err_star('cost') . _T('expense_input_cost') . '</td>' . "\n";
		echo '<td><input name="cost" value="' . $this->getDataInt('cost') . '" class="search_form_txt" /></td></tr>' . "\n";
		
		echo "<tr>\n";
		echo "<td>" . f_err_star('comment') . _Ti('expense_input_comment') . "</td>\n";
		echo '<td><textarea name="comment" id="input_expense_comment" class="frm_tarea" rows="3" cols="60">'
			. clean_output($this->getDataString('comment'))
			. "</textarea>\n"
			. "</td>\n";
		echo "</tr>\n";
	
		echo "</table>\n";
	}
}

?>
