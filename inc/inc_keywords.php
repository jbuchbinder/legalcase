<?php

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
