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

	$Id: inc_keywords.php,v 1.42 2006/11/15 19:44:20 mlutfy Exp $
*/

if (defined('_INC_KEYWORDS')) return;
define('_INC_KEYWORDS', '1');

include_lcm('inc_filters');

// define legal "objects" which accept keywords
$GLOBALS['legal_obj'] = array('case' => 1, 'stage' => 1, 'client' => 1, 'org' => 1, 'followup' => 1);

//
// get_kwg_all: Returns all keyword groups (kwg) of a given
// type. If type is 'user', then all keyword groups of type
// case, followup, client, org and author are returned.
// Type may also be 'contact'.
// 
function get_kwg_all($type, $exclude_empty = false, $show_subgroups = false) {
	$ret = array();

	if ($type == 'user')
		$in_type = "IN ('case', 'stage', 'followup', 'client', 'org', 'client_org')";
	elseif ($type)
		$in_type = "= '" . addslashes($type) . "'";

	if ($exclude_empty) {
		$query = "SELECT kwg.*, COUNT(k.id_keyword) as cpt
					FROM lcm_keyword_group as kwg, lcm_keyword as k
					WHERE kwg.id_group = k.id_group ";

		if ($in_type)
			$query .= " AND type $in_type ";

		if (! $show_subgroups)
			$query .= " AND id_parent = 0 ";

		// pgsql requires that we group on all fields
		$query .= " GROUP BY kwg.id_group, kwg.name, kwg.title, kwg.description, kwg.type, kwg.policy, kwg.quantity, kwg.suggest, kwg.ac_admin, kwg.ac_author, kwg.id_parent
					HAVING COUNT(k.id_keyword) > 0";
	} else {
		$query = "SELECT *
					FROM lcm_keyword_group ";

		if ($in_type)
			$query .= " WHERE type $in_type ";

		// [ML] If importing an old DB (ex: form 0.6.4) in LCM 0.7.x, 
		// ignore this, or the import will panic.
		if ((! $show_subgroups) && read_meta('lcm_db_version') >= 47)
			$query .= " AND id_parent = 0 ";
	}

	$result = lcm_query($query);

	while ($row = lcm_fetch_array($result)) {
		$ret[$row['title']] = $row;
		$ret[$row['title']]['title'] = remove_number_prefix($row['title']);
	}

	ksort($ret);
	reset($ret);
	
	return $ret;
}

function get_kwg_applicable_for($type_obj, $id_obj, $id_obj_sec = 0) {
	$ret = array();

	if (! $GLOBALS['legal_obj'][$type_obj])
		lcm_panic("Unknown type_obj: " . $type_obj);
	
	// Build 'NOT IN' list (already applied keywords, with quantity 'one')
	$not_in_str = "";

	if ($id_obj) {
		if ($type_obj == 'stage') {
			$query = "SELECT DISTINCT kwg.id_group, kwg.id_parent, kwg.title, kwg.quantity, kwg.policy
					FROM lcm_keyword_case as ko, lcm_keyword as k, lcm_keyword_group as kwg
					WHERE k.id_keyword = ko.id_keyword
					  AND k.id_group = kwg.id_group
					  AND ko.id_case = " . $id_obj . "
					  AND ko.id_stage = " . $id_obj_sec . "
					  AND kwg.quantity = 'one'";
		} else {
			$query = "SELECT DISTINCT kwg.id_group, kwg.id_parent, kwg.title, kwg.quantity, kwg.policy
					FROM lcm_keyword_" . $type_obj . " as ko, lcm_keyword as k, lcm_keyword_group as kwg
					WHERE k.id_keyword = ko.id_keyword
					  AND k.id_group = kwg.id_group
					  AND ko.id_" . $type_obj . " = " . $id_obj . "
					  AND kwg.quantity = 'one'";
		}
		
		$result = lcm_query($query);
	
		$not_in_list = array();
		while ($row = lcm_fetch_array($result)) {
			array_push($not_in_list, $row['id_group']);

			if ($row['id_parent']) {
				// Add parents info to 'not in' list, so that it is not shown as new kwg
				$tmp_id_kwg = $row['id_parent'];

				do {
					array_push($not_in_list, $tmp_id_kwg);
					$kwg_parent = get_kwg_from_id($tmp_id_kwg);
					$tmp_id_kwg = $kwg_parent['id_parent'];
				} while ($tmp_id_kwg);
			}
		}
		
		$not_in_str = implode(',', $not_in_list);
	}

	// Get list of keyword groups which can be applied to object
	$query = "SELECT kwg.id_group, kwg.name, kwg.title, kwg.policy, kwg.suggest, kwg.quantity
				FROM lcm_keyword_group as kwg
				WHERE kwg.ac_author = 'Y' AND kwg.id_parent = 0 AND (type = '$type_obj' ";
	
	if ($type_obj == 'client' || $type_obj == 'org')
		$query .= " OR type = 'client_org' ";
				
	$query .= "	) ";
	
	if ($not_in_str)
		$query .= " AND kwg.id_group NOT IN (" . $not_in_str . ") ";

	$result = lcm_query($query);

	while ($row = lcm_fetch_array($result)) 
		$ret[$row['name']] = $row;
	
	return $ret;
}

//
// get_kwg_from_id: Returns the keyword group associated
// with the provided ID.
//
function get_kwg_from_id($id_group) {
	$query = "SELECT *
				FROM lcm_keyword_group
				WHERE id_group = " . intval($id_group);
	$result = lcm_query($query);

	if (! lcm_num_rows($result))
		lcm_panic("Invalid keyword group (ID = " . $id_group . ")");

	return lcm_fetch_array($result);
}

function get_kwg_from_name($kwg_name) {
	global $system_kwg;

	// Check cache
	if (isset($system_kwg[$kwg_name]))
		return $system_kwg[$kwg_name];

	$query = "SELECT *
				FROM lcm_keyword_group
				WHERE name = '" . $kwg_name . "'";
	$result = lcm_query($query);

	if (! lcm_num_rows($result))
		lcm_panic("Invalid keyword group (name = " . $kwg_name . ")");

	return lcm_fetch_array($result);
}

//
// get_kw_from_id: Returns the keyword associated with the provided ID.
//
function get_kw_from_id($id_keyword) {
	$query = "SELECT k.*, kwg.type, kwg.name as kwg_name
				FROM lcm_keyword as k, lcm_keyword_group as kwg
				WHERE kwg.id_group = k.id_group
				AND id_keyword = " . intval($id_keyword);
	$result = lcm_query($query);

	if (! lcm_num_rows($result))
		lcm_panic("Invalid keyword (ID = " . $id_keyword . ")");

	return lcm_fetch_array($result);
}

function get_kw_from_name($kwg_name, $kw_name) {
	global $system_kwg;

	if (! $kwg_name)
		lcm_panic("missing kwg_name");

	if (! $kw_name)
		lcm_panic("missing kw_name");

	// Check cache
	if (isset($system_kwg[$kwg_name]['keywords'][$kw_name]))
		return $system_kwg[$kwg_name]['keywords'][$kw_name];

	// Get from DB
	$kwg_info = get_kwg_from_name($kwg_name);
	$query = "SELECT *
				FROM lcm_keyword
				WHERE id_group = " . $kwg_info['id_group'] . "
				  AND name = '" . $kw_name . "'";
	
	$result = lcm_query($query);
	return lcm_fetch_array($result);
}

//
// get_keywords_in_group_name: Returns all keywords inside a given group name.
// 
function get_keywords_in_group_name($kwg_name, $visible_only = true) {
	global $system_kwg;

	$ret = array();

	if (isset($system_kwg[$kwg_name]) && $system_kwg[$kwg_name]) {
		foreach($system_kwg[$kwg_name]['keywords'] as $kw)
			if ($kw['ac_author'] == 'Y')
				$ret[_T($kw['title'])] = $kw;

		ksort($ret);
		reset($ret);

		return $ret;
	}

	// Not in cache, then get from DB
	$query = "SELECT id_group 
				FROM lcm_keyword_group
				WHERE name = '" . $kwg_name . "'";
	
	$result = lcm_query($query);
	
	if ($row = lcm_fetch_array($result))
		return get_keywords_in_group_id($row['id_group'], $visible_only);

	lcm_panic("Keyword group not found: $kwg_name");
}

//
// get_subgroups_in_group_id: Returns all keyword groups inside a given group ID.
// 
function get_subgroups_in_group_id($kwg_id, $visible_only = true) {
	$ret = array();

	$query = "SELECT * 
				FROM lcm_keyword_group
				WHERE id_parent = " . intval($kwg_id);
	
	if ($visible_only)
		$query .= " AND ac_author = 'Y'";
	
	$result = lcm_query($query);

	while ($row = lcm_fetch_array($result)) 
		$ret[$row['title']] = $row;

	ksort($ret);
	reset($ret);

	return $ret;
}

function get_suggest_in_group_name($kwg_name) {
	global $system_kwg;

	$ret = "";

	// Check cache first
	if (isset($system_kwg[$kwg_name]) && $system_kwg[$kwg_name])
		if (isset($system_kwg[$kwg_name]['suggest']))
			return $system_kwg[$kwg_name]['suggest'];

	// Not in cache, then get from DB
	$query = "SELECT suggest
				FROM lcm_keyword_group
				WHERE name = '" . $kwg_name . "'";
	
	$result = lcm_query($query);
	
	if ($row = lcm_fetch_array($result))
		return $row['suggest'];

	lcm_panic("Keyword group not found: $kwg_name");
}

//
// get_keywords_in_group_id: Returns all keywords inside a given
// group ID.
// 
function get_keywords_in_group_id($kwg_id, $visible_only = true) {
	$ret = array();

	$query = "SELECT * 
				FROM lcm_keyword
				WHERE id_group = " . intval($kwg_id);
	
	if ($visible_only)
		$query .= " AND ac_author = 'Y'";
	
	$result = lcm_query($query);

	while ($row = lcm_fetch_array($result)) 
		$ret[$row['title']] = $row;

	ksort($ret);
	reset($ret);

	return $ret;
}

//
// check_if_kwg_name_unique: Returns true if keyword group name is unique.
//
function check_if_kwg_name_unique($name) {
	$query = "SELECT id_group
				FROM lcm_keyword_group
				WHERE name = '" . clean_input($name) . "'";
	
	$result = lcm_query($query);

	return (lcm_num_rows($result) == 0);
}

// Return a list of keywords attached to type (case/client/org/..) for a given ID
function get_keywords_applied_to($type, $id, $id_sec = 0) {
	if (! $GLOBALS['legal_obj'][$type])
		lcm_panic("Unknown type: " . $type);
	
	if ($type == 'stage') {
		$query = "SELECT kwlist.*, kwinfo.*, kwg.title as kwg_title, kwg.name as kwg_name
				FROM lcm_keyword_case as kwlist, lcm_keyword as kwinfo, lcm_keyword_group as kwg
				WHERE id_case = " . $id . " 
				  AND kwinfo.id_keyword = kwlist.id_keyword
				  AND kwg.id_group = kwinfo.id_group
				  AND kwlist.id_stage = " . $id_sec;
	} else {
		$query = "SELECT kwlist.*, kwinfo.*, kwg.title as kwg_title, kwg.name as kwg_name
				FROM lcm_keyword_" . $type . " as kwlist, lcm_keyword as kwinfo, lcm_keyword_group as kwg
				WHERE id_" . $type . " = " . $id . " 
				  AND kwinfo.id_keyword = kwlist.id_keyword
				  AND kwg.id_group = kwinfo.id_group";

		if ($type == 'case')
			$query .= " AND kwlist.id_stage = 0";
	}
	
	$result = lcm_query($query);

	$ret = array();
	while ($row = lcm_fetch_array($result))
		array_push($ret, $row);
	
	return $ret;
}

// Rather specific to show_all_keywords()
function get_parent_kwg_name($name) {
	$ret = "";
	$kwg = get_kwg_from_name($name);

	if ($kwg['id_parent']) {
		lcm_log("get_parent_kwg_name: has parent: " . $kwg['id_parent']);
		$parent_kwg = get_kwg_from_id($kwg['id_parent']);

		if ($parent_kwg['id_parent']) {
			lcm_log("get_parent_kwg_name: PARENT has parent: " . $parent_kwg['id_parent']);
			$ret = get_parent_kwg_name($parent_kwg['name']);
		}

		$ret .= _T(remove_number_prefix($parent_kwg['title'])) . ' / ';
	}

	return $ret;
}

// show keywords in (ex) 'case_det.php', therefore, it is in a <ul> ... </ul>
function show_all_keywords($type_obj, $id_obj, $id_obj_sec = 0) {
	$all_kw = get_keywords_applied_to($type_obj, $id_obj, $id_obj_sec);

	foreach ($all_kw as $kw) {
		echo '<li>'
			. '<span class="label1">' . get_parent_kwg_name($kw['kwg_name']) . _Ti(remove_number_prefix($kw['kwg_title'])) . '</span>'
			. '<span class="value1">' . _T(remove_number_prefix($kw['title'])) . '</span>';

		if ($kw['value'])
			echo ": " . '<span class="value1">' . $kw['value'] . '</span>';

		echo "</li>\n";
	}
}

function show_edit_keywords_form_kws_in_kwg() {


}

function show_edit_keywords_form($type_obj, $id_obj, $id_obj_sec = 0) {
	include_lcm('inc_access');

	if (! $GLOBALS['legal_obj'][$type_obj])
		lcm_panic("Invalid object type requested");

	if ($id_obj) {
		//
		// Show current keywords (already attached to object)
		//
		$current_kws = get_keywords_applied_to($type_obj, $id_obj, $id_obj_sec);
		$cpt = 0;
	
		foreach ($current_kws as $kw) {
			$kwg = get_kwg_from_id($kw['id_group']);
			$show_kw_value = false;
		
			echo "<tr>\n";
			echo "<td>"
				. "<label for=\"kw_value_$type_obj$cpt\">"
				. f_err_star('kwg' . $kwg['id_group']) . _Ti(remove_number_prefix($kwg['title']))
				. "</label>"
				. "<br />(" . _T('keywords_input_policy_' . $kwg['policy']) . ")"
				. "</td>\n";

			echo "<td>";

			$kw_for_kwg = get_keywords_in_group_id($kwg['id_group']);

			if (count($kw_for_kwg)) {
				echo '<input type="hidden" name="kwg_id_' . $type_obj . '[]" value="' . $kwg['id_group'] . '" />' . "\n";
				echo '<input type="hidden" name="kw_entry_' . $type_obj . '[]" value="' . $kw['id_entry'] . '" />' . "\n";
				echo '<select id="kw_value_' . $type_obj . $cpt . '" name="kw_value_' . $type_obj . '[]">';
				echo '<option value="">' . '' . "</option>\n";

				foreach ($kw_for_kwg as $kw1) {
					if ($kw1['hasvalue'] == 'Y')
						$show_kw_value = true;

					$sel = '';

					if (isset($_SESSION['form_data']['kw_value_' . $type_obj][$cpt])
							&& $_SESSION['form_data']['kw_value_' . $type_obj][$cpt] == $kw1['id_keyword'])
					{
						$sel = ' selected="selected" ';
					} elseif ($kw1['id_keyword'] == $kw['id_keyword']) {
						$sel = ' selected="selected" ';
					}

					echo '<option value="' . $kw1['id_keyword'] . '"' . $sel . '>' . _T(remove_number_prefix($kw1['title'])) . "</option>\n";
				}

				echo "</select>\n";
			}

			// Check if keyword policy = mandatory, and quantity = one
			$kwg = get_kwg_from_id($kw['id_group']);

			if (! ($kwg['policy'] == 'mandatory' && $kwg['quantity'] == 'one')) {
				echo '<label for="kw_del_' . $type_obj . $cpt . '">'
					. '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" alt="Delete?" title="Delete?" />' // TRAD
					. '</label>&nbsp;<input type="checkbox" id="kw_del_' . $type_obj . $cpt . '" name="kw_del_' . $type_obj . $cpt . '"/>';
			}

			if ($show_kw_value) {
				// Use value if submitted with the form, else use previous one
				if (isset($_SESSION['form_data']['kw_entryval_' . $type_obj .  $cpt]))
					$tmp_value = $_SESSION['form_data']['kw_entryval_' . $type_obj . $cpt];
				else
					$tmp_value = $kw['value'];
				
				echo "<br />\n";
				echo '<input type="text" name="kw_entryval_' . $type_obj . $cpt . '" ' . 'value="' . $tmp_value . '" />' . "\n";
			}
			
			echo "</td>\n";
			echo "</tr>\n";
			$cpt++;
		}
	}

	//
	// New keywords
	//
	$kwg_for_case = get_kwg_applicable_for($type_obj, $id_obj, $id_obj_sec);
	$cpt_kw = 0;

	foreach ($kwg_for_case as $kwg) {
		echo "<tr>\n";
		echo '<td><label for="new_keyword_' . $type_obj . $cpt_kw . '">'
			. f_err_star('keyword_' . $type_obj . $cpt_kw) // probably not used (DEPRECATED)
			. f_err_star('kwg' . $kwg['id_group'])
			. _Ti(remove_number_prefix($kwg['title'])) . '</label>'
			. "<br />(" . _T('keywords_input_policy_' . $kwg['policy']) . ")</td>\n";

		echo "<td>";

		$kw_for_kwg = get_keywords_in_group_id($kwg['id_group']);

		if (count($kw_for_kwg)) {
			$obj_id_ajax = 'kw_' . create_random_password(15, time());

			echo '<input type="hidden" name="new_kwg_' . $type_obj . '_id[]" value="' . $kwg['id_group'] . '" />' . "\n";
			echo '<select id="new_keyword_' . $type_obj . $cpt_kw . '" '
				. 'name="new_keyword_' . $type_obj . '_value[]" '
				. "onchange=\"getKeywordInfo('get_kwg_in','" . $kwg['name'] . "', '$type_obj', $id_obj, 0, '$obj_id_ajax')\"" // XXX
				. '>';
			echo '<option value="">' . '' . "</option>\n";

			$show_kw_value = false;

			foreach ($kw_for_kwg as $kw) {
				if ($kw['hasvalue'] == 'Y')
					$show_kw_value = true;

				// For default value, use the form_data (if present), else use suggested keyword
				$sel = '';

				if (isset($_SESSION['form_data']['new_keyword_' . $type_obj . '_value'][$cpt_kw])
					&& $_SESSION['form_data']['new_keyword_' . $type_obj . '_value'][$cpt_kw] == $kw['id_keyword']) 
				{
					$sel = ' selected="selected" ';
				} elseif ($kwg['suggest'] == $kw['name']) {
					$sel = ' selected="selected" ';
				}

				// $sel = ($kwg['suggest'] == $kw['name'] ? ' selected="selected" ' : '');
				echo '<option ' . $sel . ' value="' . $kw['id_keyword'] . '">' 
					. _T(remove_number_prefix($kw['title']))
					. "</option>\n";
			}

			echo "</select>\n";

			if ($show_kw_value) {
				$tmp_value = '';
				if (isset($_SESSION['form_data']['new_kw_entryval_' . $type_obj . $cpt_kw]))
					$tmp_value = $_SESSION['form_data']['new_kw_entryval_' . $type_obj . $cpt_kw];

				echo "<br />\n";
				echo '<input type="text" name="new_kw_entryval_' . $type_obj . $cpt_kw . '" ' . 'value="' . $tmp_value . '" />' . "\n";
			}

			echo '<div id="' . $obj_id_ajax . '"></div>' . "\n";
		} else {
			//
			// Sub-keyword group(s), since no top-level keywords
			//
			$sub_kwgs = get_subgroups_in_group_id($kwg['id_group']);

			if (count($sub_kwgs)) {
				echo '<input type="hidden" name="nop_kwg_' . $type_obj . '_id[]" value="' . $kwg['id_group'] . '" />' . "\n";

				$obj_id_ajax = 'kw_' . create_random_password(15, time());
				$sel_id = 'nop_kwg_' . $type_obj . $cpt_kw;
				$sel_name = 'nop_keyword_' . $type_obj . '_value[]';

				echo "<select id=\"$sel_id\" name=\"$sel_name\" "
					. "onchange=\"getKeywordInfo('get_kwg_in', this.value, '$type_obj', $id_obj, 0, '$obj_id_ajax')\""
					. '>';
				echo '<option value="">' . '' . "</option>\n";

				foreach ($sub_kwgs as $sg) {
					echo '<option value="' . $sg['name'] . '">' . _T(remove_number_prefix($sg['title'])) . "</option>\n";
				}

				echo "</select>\n";
				echo '<div id="' . $obj_id_ajax . '"></div>' . "\n";
			}

			echo '<div id="keywords_in_group_data' . $kwg['id_group'] . '"></div>' . "\n";
		}
		
		echo "</td>\n";
		echo "</tr>\n";
		$cpt_kw++;
	}
}

function validate_update_keywords_request($type_obj, $id_obj, $id_obj_sec = 0) {

	// - Check if, for all mandatory keywords, there is
	//   -* a new keyword to be attached
	//   -* not for deletion

	$kw_entries = _request('kw_entry_' . $type_obj);
	$kw_values  = _request('kw_value_' . $type_obj);
	$kwg_ids    = _request('kwg_id_' . $type_obj);

	$new_keywords = _request('new_keyword_' . $type_obj . '_value');
	$new_kwg_id = _request('new_kwg_' . $type_obj . '_id');

	$nop_keywords = _request('nop_keyword_' . $type_obj . '_value');
	$nop_kwg_id = _request('nop_kwg_' . $type_obj . '_id');

	$kwg_count = array();
	$kwg_applicable = get_kwg_applicable_for($type_obj, $id_obj, $id_obj_sec);

	foreach ($kwg_applicable as $kwg) {
		// TODO; validate sub-kwg if any (make recursive function?)

		if ($kwg['policy'] == 'mandatory') {
			// check in already applied keywords
			for ($cpt = 0; isset($kw_entries[$cpt]); $cpt++) {
				if (_request('kw_del_' . $type_obj . $cpt)) {
					// for deletion
					if (isset($kwg_count[$kwg_ids[$cpt]]))
						$kwg_count[$kwg_ids[$cpt]]--;
					else
						$kwg_count[$kwg_ids[$cpt]] = 0;
				} else if ((isset($kw_values[$cpt]) && $kw_values[$cpt])) {
					// for update
					if (isset($kwg_count[$kwg_ids[$cpt]]))
						$kwg_count[$kwg_ids[$cpt]]++;
					else
						$kwg_count[$kwg_ids[$cpt]] = 1;
				}
			}

			// check in new keywords
			for ($cpt = 0; isset($new_keywords[$cpt]); $cpt++) {
				if ($new_keywords[$cpt]) {
					if (isset($kwg_count[$new_kwg_id[$cpt]]))
						$kwg_count[$new_kwg_id[$cpt]]++;
					else
						$kwg_count[$new_kwg_id[$cpt]] = 1;
				}
			}

			// check in 'nop' (keyword groups, for which their sub-kwg will have keywords
			for ($cpt = 0; isset($nop_keywords[$cpt]); $cpt++) {
				if ($nop_keywords[$cpt]) {
					if (isset($kwg_count[$nop_kwg_id[$cpt]]))
						$kwg_count[$nop_kwg_id[$cpt]]++;
					else
						$kwg_count[$nop_kwg_id[$cpt]] = 1;
				}
			}

			if (! (isset($kwg_count[$kwg['id_group']]) && $kwg_count[$kwg['id_group']] > 0)) {
				$_SESSION['errors']['kwg' . $kwg['id_group']] = _Ti($kwg['title']) . _T('warning_field_mandatory');
			}
		}
	}
}

function update_keywords_request($type_obj, $id_obj, $id_obj_sec = 0) {

	//
	// Update existing keywords
	//
	if (isset($_REQUEST['kw_value_' . $type_obj])) {
		$kw_entries = $_REQUEST['kw_entry_' . $type_obj];
		$kw_values  = $_REQUEST['kw_value_' . $type_obj];
		$kwg_ids    = $_REQUEST['kwg_id_' . $type_obj];

		// Check if the keywords provided are really attached to the object
		for ($cpt = 0; isset($kw_entries[$cpt]) && $kw_entries[$cpt]; $cpt++) {
			// TODO
		}

		for ($cpt = 0; isset($kw_entries[$cpt]); $cpt++) {
			if ($_REQUEST['kw_del_' . $type_obj . $cpt] || empty($kw_values[$cpt])) {
				if ($type_obj == 'stage') {
					// The id_case and id_stage are specified explicitely
					// even if redundant for security (not to delete on other 
					// cases) and for integrety checks.
					$query = "DELETE FROM lcm_keyword_case
								WHERE id_case = $id_obj
								  AND id_stage = $id_obj_sec
								  AND id_entry = " . $kw_entries[$cpt];
				} else {
					// The id_$type_obj (ex: case, client, org) is specified
					// explicitely even if redundant for security and integrity.
					$query = "DELETE FROM lcm_keyword_" . $type_obj . "
								WHERE id_$type_obj = $id_obj
								  AND id_entry = " . $kw_entries[$cpt];
				}
			} else if ($kw_values[$cpt]) {
				if ($type_obj == 'stage') {
					$query = "UPDATE lcm_keyword_case
								SET id_keyword = " . $kw_values[$cpt] . ",
									id_stage = " . $id_obj_sec;
				} else {
					$query = "UPDATE lcm_keyword_" . $type_obj . " 
								SET id_keyword = " . $kw_values[$cpt];
				}

				if ($_REQUEST['kw_entryval_' . $type_obj . $cpt])
					$query .= ", value = '" . $_REQUEST['kw_entryval_' . $type_obj . $cpt] . "'";

				$query .= " WHERE id_entry = " . $kw_entries[$cpt];
			}

			lcm_query($query);
		}
	}

	//
	// New keywords
	//

	if ($id_obj && isset($_REQUEST['new_keyword_' . $type_obj . '_value'])) {
		$cpt = 0;
		$new_keywords = $_REQUEST['new_keyword_' . $type_obj . '_value'];
		$new_kwg_id = $_REQUEST['new_kwg_' . $type_obj . '_id'];

		while(isset($new_keywords[$cpt])) {
			// Process new keywords which have a value
			if ($new_keywords[$cpt]) {
				if (! $new_kwg_id[$cpt])
					lcm_panic("Empty kwg name");

				// optionally, we can validate whether it makes sense
				// to apply this kwg to this 'object' ... (TODO ?)

				if ($type_obj == 'stage') {
					$query = "INSERT INTO lcm_keyword_case
							SET id_keyword = " . $new_keywords[$cpt] . ",
								id_case  = " . $id_obj . ",
								id_stage = " . $id_obj_sec;
				} else {
					$query = "INSERT INTO lcm_keyword_" . $type_obj . "
							SET id_keyword = " . $new_keywords[$cpt] . ",
								id_" . $type_obj . " = " . $id_obj;
				}

				if (isset($_REQUEST['new_kw_entryval_' . $type_obj . $cpt])) {
					if ($_REQUEST['new_kw_entryval_' . $type_obj . $cpt])
						$query .= ", value = '" . $_REQUEST['new_kw_entryval_' . $type_obj . $cpt] . "'";
					else
						$query = "";
				} 

				if ($query)
					lcm_query($query);
			}

			$cpt++;
		}
	}
}

?>
