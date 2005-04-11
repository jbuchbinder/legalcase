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

	$Id: inc_keywords.php,v 1.19 2005/04/11 09:15:56 mlutfy Exp $
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
		$in_type = "IN ('case', 'followup', 'client', 'org', 'client_org')";
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

function get_kwg_applicable_for($type_obj, $id_obj) {
	$ret = array();

	if (! ($type_obj == 'case' || $type_obj == 'client' || $type_obj == 'org' || $type_obj == 'author'))
		lcm_panic("Unknown type_obj: " . $type_obj);
	
	// Build 'NOT IN' list (already applied keywords, with quantity 'one')
	$query = "SELECT DISTINCT kwg.id_group, kwg.quantity
				FROM lcm_keyword_" . $type_obj . " as ko, lcm_keyword as k, lcm_keyword_group as kwg
				WHERE k.id_keyword = ko.id_keyword
				  AND k.id_group = kwg.id_group
				  AND ko.id_" . $type_obj . " = " . $id_obj . "
				  AND kwg.quantity = 'one'";
	
	$result = lcm_query($query);

	$not_in_list = array();
	while ($row = lcm_fetch_array($result))
		array_push($not_in_list, $row['id_group']);
	
	$not_in_str = implode(',', $not_in_list);

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

// Get keyword title
function get_kw_title($name) {
	$query = "SELECT title FROM lcm_keyword WHERE name='" . clean_input($name) . "'";
	$result = lcm_query($query);
	if ($row = lcm_fetch_array($result))
		return $row['title'];
	else
		return false;
}

// Return a list of keywords attached to type (case/client/org/..) for a given ID
function get_keywords_applied_to($type, $id) {
	if (! ($type == 'case' || $type == 'client' || $type == 'org' || $type == 'author'))
		lcm_panic("Unknown type: " . $type);
	
	$query = "SELECT kwlist.*, kwinfo.*, kwg.title as kwg_title
				FROM lcm_keyword_" . $type . " as kwlist, lcm_keyword as kwinfo, lcm_keyword_group as kwg
				WHERE id_" . $type . " = " . $id . " 
				  AND kwinfo.id_keyword = kwlist.id_keyword
				  AND kwg.id_group = kwinfo.id_group";
	
	$result = lcm_query($query);

	$ret = array();
	while ($row = lcm_fetch_array($result))
		array_push($ret, $row);
	
	return $ret;
}

// show keywords in (ex) 'case_det.php', therefore, it is in a <p> ... </p>
function show_all_keywords($type_obj, $id_obj) {
	$all_kw = get_keywords_applied_to($type_obj, $id_obj);

	foreach ($all_kw as $kw) {
		echo _Ti($kw['kwg_title']) . $kw['title'] . "<br/>\n";
	}
}

function show_edit_keywords_form($type_obj, $id_obj) {
	if (! ($type_obj == 'case' || $type_obj == 'client' || $type_obj == 'org'))
		lcm_panic("Invalid object type requested");

	//
	// Show current keywords (already attached to object)
	//
	if ($id_obj) {
		$current_kws = get_keywords_applied_to($type_obj, $id_obj);
		$cpt = 0;
	
		foreach ($current_kws as $kw) {
			$kwg = get_kwg_from_id($kw['id_group']);
		
			echo "<tr>\n";
			echo "<td>" . f_err_star('FIXME') . _Ti($kwg['title'])
				. "<br />(" . _T('keywords_input_policy_' . $kwg['policy']) . ")</td>\n";

			echo "<td>";
			echo '<input type="hidden" name="kwg_id[]" value="' . $kwg['id_group'] . '" />' . "\n";
			echo '<input type="hidden" name="kw_entry[]" value="' . $kw['id_entry'] . '" />' . "\n";
			echo '<select name="kw_value[]">';
			echo '<option value="">' . '' . "</option>\n";

			$kw_for_kwg = get_keywords_in_group_id($kwg['id_group']);
			foreach ($kw_for_kwg as $kw1) {
				$sel = ($kw1['id_keyword'] == $kw['id_keyword'] ? ' selected="selected"' : '');
				echo '<option value="' . $kw1['id_keyword'] . '"' . $sel . '>' . _T($kw1['title']) . "</option>\n";
			}

			echo "</select>\n";

			echo '<label for="id_del_keyword' . $cpt . '">'
				. '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" alt="Delete?" title="Delete?" />' // TRAD
				. '</label>&nbsp;<input type="checkbox" id="id_del_keyword' . $cpt . '" name="kw_del_' . $cpt . '"/>';

			echo "</td>\n";
			echo "</tr>\n";
			$cpt++;
		}
	}

	//
	// New keywords
	//
	$kwg_for_case = get_kwg_applicable_for($type_obj, $id_obj);
	$cpt_kw = 0;

	foreach ($kwg_for_case as $kwg) {
		echo "<tr>\n";
		echo '<td>' . f_err_star('keyword_' . $cpt_kw) . _Ti($kwg['title']) 
			. "<br />(" . _T('keywords_input_policy_' . $kwg['policy']) . ")</td>\n";

		$kw_for_kwg = get_keywords_in_group_id($kwg['id_group']);
		if (count($kw_for_kwg)) {
			echo "<td>";
			echo '<input type="hidden" name="new_kwg_id[]" value="' . $kwg['id_group'] . '" />' . "\n";
			echo '<select name="new_keyword_value[]">';
			echo '<option value="">' . '' . "</option>\n";

			foreach ($kw_for_kwg as $kw)
				echo '<option value="' . $kw['id_keyword'] . '">' . _T($kw['title']) . "</option>\n";

			echo "</select>\n";
			echo "</td>\n";
		} else {
			// This should not happen, we should get only non-empty groups
		}
		
		echo "</tr>\n";
		$cpt_kw++;
	}
}

function update_keywords_request($type_obj, $id_obj) {

	//
	// Update existing keywords
	//
	if (isset($_REQUEST['kw_value'])) {
		$kw_entries = $_REQUEST['kw_entry'];
		$kw_values  = $_REQUEST['kw_value'];
		$kwg_ids    = $_REQUEST['kwg_id'];

		// Check if the keywords provided are really attached to the object
		for ($cpt = 0; $kw_entries[$cpt]; $cpt++) {
			// TODO
		}

		for ($cpt = 0; isset($kw_entries[$cpt]); $cpt++) {
			if ($_REQUEST['kw_del_' . $cpt] || empty($kw_values[$cpt])) {
				$query = "DELETE FROM lcm_keyword_" . $type_obj . "
							WHERE id_entry = " . $kw_entries[$cpt];
			} else if ($kw_values[$cpt]) {
				$query = "UPDATE lcm_keyword_" . $type_obj . " 
							SET id_keyword = " . $kw_values[$cpt] . "
							WHERE id_entry = " . $kw_entries[$cpt];
			}

			lcm_query($query);
		}
	}

	//
	// New keywords
	//

	if ($id_obj && isset($_REQUEST['new_keyword_value'])) {
		$cpt = 0;
		$new_keywords = $_REQUEST['new_keyword_value'];
		$new_kwg_id = $_REQUEST['new_kwg_id'];

		while(isset($new_keywords[$cpt])) {
			// Process new keywords which have a value
			if ($new_keywords[$cpt]) {
				if (! $new_kwg_id[$cpt])
					lcm_panic("Empty kwg name");

				// optionally, we can validate whether it makes sense
				// to apply this kwg to this 'object' ... (TODO ?)

				$query = "INSERT INTO lcm_keyword_" . $type_obj . "
						SET id_keyword = " . $new_keywords[$cpt] . ",
							id_" . $type_obj . " = " . $id_obj;

				lcm_query($query);
			}

			$cpt++;
		}
	}
}

?>
