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

	$Id: inc_keywords.php,v 1.25 2005/04/20 08:56:10 mlutfy Exp $
*/

if (defined('_INC_KEYWORDS')) return;
define('_INC_KEYWORDS', '1');

include_lcm('inc_filters');

//
// get_kwg_all: Returns all keyword groups (kwg) of a given
// type. If type is 'user', then all keyword groups of type
// case, followup, client, org and author are returned.
// 
function get_kwg_all($type, $exclude_empty = false) {
	$ret = array();

	if ($type == 'user')
		$in_type = "IN ('case', 'stage', 'followup', 'client', 'org', 'client_org')";
	else
		$in_type = "= '" . addslashes($type) . "'";

	if ($exclude_empty) {
		$query = "SELECT kwg.*, COUNT(k.id_keyword) as cpt
					FROM lcm_keyword_group as kwg, lcm_keyword as k
					WHERE type $in_type
					  AND kwg.id_group = k.id_group
					GROUP BY id_group
					HAVING cpt > 0";
	} else {
		$query = "SELECT *
					FROM lcm_keyword_group
					WHERE type $in_type";
	}

	$result = lcm_query($query);

	while ($row = lcm_fetch_array($result)) 
		$ret[$row['name']] = $row;
	
	return $ret;
}

function get_kwg_applicable_for($type_obj, $id_obj, $id_obj_sec = 0) {
	$ret = array();

	if (! ($type_obj == 'case' || $type_obj == 'stage' || $type_obj == 'client' || $type_obj == 'org' || $type_obj == 'author'))
		lcm_panic("Unknown type_obj: " . $type_obj);
	
	// Build 'NOT IN' list (already applied keywords, with quantity 'one')
	$not_in_str = "";

	if ($id_obj) {
		if ($type_obj == 'stage') {
			$query = "SELECT DISTINCT kwg.id_group, kwg.quantity
					FROM lcm_keyword_case as ko, lcm_keyword as k, lcm_keyword_group as kwg
					WHERE k.id_keyword = ko.id_keyword
					  AND k.id_group = kwg.id_group
					  AND ko.id_case = " . $id_obj . "
					  AND ko.id_stage = " . $id_obj_sec . "
					  AND kwg.quantity = 'one'";
		} else {
			$query = "SELECT DISTINCT kwg.id_group, kwg.quantity
					FROM lcm_keyword_" . $type_obj . " as ko, lcm_keyword as k, lcm_keyword_group as kwg
					WHERE k.id_keyword = ko.id_keyword
					  AND k.id_group = kwg.id_group
					  AND ko.id_" . $type_obj . " = " . $id_obj . "
					  AND kwg.quantity = 'one'";
		}
		
		$result = lcm_query($query);
	
		$not_in_list = array();
		while ($row = lcm_fetch_array($result))
			array_push($not_in_list, $row['id_group']);
		
		$not_in_str = implode(',', $not_in_list);
	}

	// Get list of keyword groups which can be applied to object
	$query = "SELECT kwg.*, COUNT(k.id_keyword) as cpt
				FROM lcm_keyword_group as kwg, lcm_keyword as k
				WHERE kwg.ac_author = 'Y' AND (type = '$type_obj' ";
	
	if ($type_obj == 'client' || $type_obj == 'org')
		$query .= " OR type = 'client_org' ";
				
	$query .= "	) AND kwg.id_group = k.id_group ";
	
	if ($not_in_str)
		$query .= " AND kwg.id_group NOT IN (" . $not_in_str . ") ";

	$query .= " GROUP BY id_group HAVING cpt > 0";
	
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
	$query = "SELECT *
				FROM lcm_keyword_group
				WHERE name = '" . $kwg_name . "'";
	$result = lcm_query($query);

	if (! lcm_num_rows($result))
		lcm_panic("Invalid keyword group (ID = " . $id_group . ")");

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

	if ($system_kwg[$kwg_name]) {
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
	if (! ($type == 'case' || $type == 'stage' || $type == 'client' || $type == 'org' || $type == 'author'))
		lcm_panic("Unknown type: " . $type);
	
	if ($type == 'stage') {
		$query = "SELECT kwlist.*, kwinfo.*, kwg.title as kwg_title
				FROM lcm_keyword_case as kwlist, lcm_keyword as kwinfo, lcm_keyword_group as kwg
				WHERE id_case = " . $id . " 
				  AND kwinfo.id_keyword = kwlist.id_keyword
				  AND kwg.id_group = kwinfo.id_group
				  AND kwlist.id_stage = " . $id_sec;
	} else {
		$query = "SELECT kwlist.*, kwinfo.*, kwg.title as kwg_title
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

// show keywords in (ex) 'case_det.php', therefore, it is in a <p> ... </p>
function show_all_keywords($type_obj, $id_obj, $id_obj_sec = 0) {
	$all_kw = get_keywords_applied_to($type_obj, $id_obj, $id_obj_sec);

	foreach ($all_kw as $kw) {
		echo _Ti($kw['kwg_title']) . _T(remove_number_prefix($kw['title']));

		if ($kw['value'])
			echo ": " . $kw['value']; // TRAD ?

		echo "<br/>\n";
	}
}

function show_edit_keywords_form($type_obj, $id_obj, $id_obj_sec = 0) {
	if (! ($type_obj == 'case' || $type_obj == 'stage' || $type_obj == 'client' || $type_obj == 'org'))
		lcm_panic("Invalid object type requested");

	//
	// Show current keywords (already attached to object)
	//
	if ($id_obj) {
		$current_kws = get_keywords_applied_to($type_obj, $id_obj, $id_obj_sec);
		$cpt = 0;
	
		foreach ($current_kws as $kw) {
			$kwg = get_kwg_from_id($kw['id_group']);
			$show_kw_value = false;
		
			echo "<tr>\n";
			echo "<td>" . f_err_star('FIXME') . _Ti($kwg['title'])
				. "<br />(" . _T('keywords_input_policy_' . $kwg['policy']) . ")</td>\n";

			echo "<td>";
			echo '<input type="hidden" name="kwg_id_' . $type_obj . '[]" value="' . $kwg['id_group'] . '" />' . "\n";
			echo '<input type="hidden" name="kw_entry_' . $type_obj . '[]" value="' . $kw['id_entry'] . '" />' . "\n";
			echo '<select name="kw_value_' . $type_obj . '[]">';
			echo '<option value="">' . '' . "</option>\n";

			$kw_for_kwg = get_keywords_in_group_id($kwg['id_group']);
			foreach ($kw_for_kwg as $kw1) {
				if ($kw1['hasvalue'] == 'Y')
					$show_kw_value = true;

				$sel = ($kw1['id_keyword'] == $kw['id_keyword'] ? ' selected="selected"' : '');
				echo '<option value="' . $kw1['id_keyword'] . '"' . $sel . '>' . _T(remove_number_prefix($kw1['title'])) . "</option>\n";
			}

			echo "</select>\n";

			echo '<label for="id_del_keyword_' . $type_obj . $cpt . '">'
				. '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" alt="Delete?" title="Delete?" />' // TRAD
				. '</label>&nbsp;<input type="checkbox" id="id_del_keyword' . $type_obj . $cpt . '" name="kw_del_' . $type_obj . $cpt . '"/>';

			if ($show_kw_value) {
				echo "<br />\n";
				echo '<input type="text" name="kw_entryval_' . $type_obj . $cpt . '" ' . 'value="' . $kw["value"] . '" />' . "\n";
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
		echo '<td>' . f_err_star('keyword_' . $type_obj . $cpt_kw) . _Ti($kwg['title']) 
			. "<br />(" . _T('keywords_input_policy_' . $kwg['policy']) . ")</td>\n";

		$kw_for_kwg = get_keywords_in_group_id($kwg['id_group']);
		if (count($kw_for_kwg)) {
			echo "<td>";
			echo '<input type="hidden" name="new_kwg_' . $type_obj . '_id[]" value="' . $kwg['id_group'] . '" />' . "\n";
			echo '<select name="new_keyword_' . $type_obj . '_value[]">';
			echo '<option value="">' . '' . "</option>\n";

			$show_kw_value = false;

			foreach ($kw_for_kwg as $kw) {
				if ($kw['hasvalue'] == 'Y')
					$show_kw_value = true;

				$sel = ($kwg['suggest'] == $kw['name'] ? ' selected="selected" ' : '');
				echo '<option ' . $sel . ' value="' . $kw['id_keyword'] . '">' 
					. _T(remove_number_prefix($kw['title']))
					. "</option>\n";
			}

			echo "</select>\n";

			if ($show_kw_value) {
				echo "<br />\n";
				echo '<input type="text" name="new_kw_entryval_' . $type_obj . $cpt . '" ' . 'value="" />' . "\n";
			}

			echo "</td>\n";
		} else {
			// This should not happen, we should get only non-empty groups
		}
		
		echo "</tr>\n";
		$cpt_kw++;
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
		for ($cpt = 0; $kw_entries[$cpt]; $cpt++) {
			// TODO
		}

		for ($cpt = 0; isset($kw_entries[$cpt]); $cpt++) {
			if ($_REQUEST['kw_del_' . $type_obj . $cpt] || empty($kw_values[$cpt])) {
				if ($type_obj == 'stage') {
					$query = "DELETE FROM lcm_keyword_case
								WHERE id_entry = " . $kw_entries[$cpt];
				} else {
					$query = "DELETE FROM lcm_keyword_" . $type_obj . "
								WHERE id_entry = " . $kw_entries[$cpt];
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

				if ($_REQUEST['new_kw_entryval_' . $type_obj . $cpt])
					$query .= ", value = '" . $_REQUEST['new_kw_entryval_' . $type_obj . $cpt] . "'";

				lcm_query($query);
			}

			$cpt++;
		}
	}
}

?>
