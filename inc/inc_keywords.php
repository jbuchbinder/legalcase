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

	$Id: inc_keywords.php,v 1.11 2005/03/28 13:21:59 mlutfy Exp $
*/

if (defined('_INC_KEYWORDS')) return;
define('_INC_KEYWORDS', '1');

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
// get_keywords_from_group_name: Returns all keywords inside a given group name.
// 
function get_keywords_from_group_name($kwg_name) {

	// 1- Get ID for name (check cache first)

	// 2- call get_keywords_in_group_id()

	lcm_panic("Not implemented");
}

//
// get_keywords_in_group_id: Returns all keywords inside a given
// group ID.
// 
function get_keywords_in_group_id($kwg_id) {
	$ret = array();

	$query = "SELECT * 
				FROM lcm_keyword
				WHERE id_group = " . intval($kwg_id);

	$result = lcm_query($query);

	while ($row = lcm_fetch_array($result)) 
		$ret[$row['name']] = $row;

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
	
	$query = "SELECT *
				FROM lcm_keyword_" . $type . " as kwlist, lcm_keyword as kwinfo
				WHERE id_" . $type . " = " . $id . " 
				  AND kwinfo.id_keyword = kwlist.id_keyword";
	
	$result = lcm_query($query);

	if (lcm_num_rows($result))
		return lcm_fetch_array($result);
	else
		return array();
}

function update_keywords_request($type_obj, $id_obj) {

	//
	// Update existing keywords
	//
	if (isset($_REQUEST['keyword_value'])) {

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
