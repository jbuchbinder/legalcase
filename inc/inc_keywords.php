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

	$Id: inc_keywords.php,v 1.7 2005/03/03 16:06:45 mlutfy Exp $
*/

if (defined('_INC_KEYWORDS')) return;
define('_INC_KEYWORDS', '1');

//
// get_kwg_all: Returns all keyword groups (kwg) of a given
// type. If type is 'user', then all keyword groups of type
// case, followup, client, org and author are returned.
// 
function get_kwg_all($type) {
	$ret = array();

	if ($type == 'user')
		$in_type = "IN ('case', 'followup', 'client', 'org', 'author')";
	else
		$in_type = "= '" . addslashes($type) . "'";

	$query = "SELECT *
				FROM lcm_keyword_group
				WHERE type $in_type";

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

	return lcm_fetch_array($result);
}

//
// get_kw_from_id: Returns the keyword associated
// with the provided ID.
//
function get_kw_from_id($id_keyword) {
	$query = "SELECT k.*, kwg.type, kwg.name as kwg_name
				FROM lcm_keyword as k, lcm_keyword_group as kwg
				WHERE kwg.id_group = k.id_group
				AND id_keyword = " . intval($id_keyword);
	$result = lcm_query($query);

	return lcm_fetch_array($result);
}

//
// get_keywords_in_group_name: Returns all keywords inside a given
// group name.
// 
function get_keywords_in_group_name($kwg_name) {

	// 1- Get ID for name (check cache first)

	// 2- call get_keywords_in_group_id()
	
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


?>
