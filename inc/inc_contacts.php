<?php

// Execute only once
if (defined('_INC_CONTACTS')) return;
define('_INC_CONTACTS', '1');

function get_contact_type_id($name) {
	global $system_kwg;

	if (array_key_exists($name, $system_kwg['contacts']['keywords'])) {
		return $system_kwg['contacts']['keywords'][$name]['id_keyword'];
	} else {
		lcm_log("get_contact_type_id: keyword $name does not exist");
		lcm_log(lcm_getbacktrace());
		return 0;
	}
}

// type_person should be of the enum in the database (author, client, org, ..)
function get_contacts($type_person, $id, $type_contact = '') {
	global $system_kwg;
	$contacts = array();

	// XXX FIXME TODO very temporary untill we solved this issue..
	if ($type_contact == 'email')
		$type_contact = 1;

	$query = "SELECT type_contact, value
				FROM lcm_contact
				WHERE id_of_person = " . intval($id) . " 
					AND type_person = '" . addslashes($type_person) . "' ";

	if ($type_contact)
		$query .= "AND type_contact IN (" . addslashes($type_contact) . ")";

	$result = lcm_query($query);
	$tmp_row = array();

	while($row = lcm_fetch_array($result)) {
		// Perhaps not the most efficient, but very practical
		$tmp_row['type_contact'] = $row['type_contact'];
		$tmp_row['value'] = $row['value'];
		$tmp_row['name'] = 'unknown';
		$tmp_row['title'] = 'unknown';

		foreach ($system_kwg['contacts']['keywords'] as $c) {
			if ($c['id_keyword'] == $row['type_contact']) {
				$tmp_row['name'] = $c['name'];
				$tmp_row['title'] = $c['title'];
			}
		}
		
		$contacts[] = $tmp_row;
	}

	return $contacts;
}

function add_contact($type_person, $id, $type_contact, $value) {
	if ($type_contact == 'email')
		$type_contact = get_contact_type_id('email_main');
	else
		$type_contact = get_contact_type_id($type_contact);

	$query = "INSERT INTO lcm_contact (type_person, id_of_person, type_contact, value)
		VALUES('" . addslashes($type_person) . "', " . intval($id) . ", "
			. intval($type_contact) . ", " . "'" . addslashes($value) . "')";

	lcm_query($query);
}

function is_existing_contact($type_person, $id = 0, $type_contact, $value) {
	// XXX FIXME TODO very temporary untill we solved this issue..
	if ($type_contact == 'email')
//		$type_contact = 1;
//		[AG] I assume that 'email' means any e-mail contact type
//		If not, $type_contact should be set here to what 'email' means
		$type_contact = array('email_main','email_alternate');
//	else
//		echo "Wrong get_contact_author type ($type_contact)";

	$id = intval($id);
//	$type_contact = intval($type_contact);
	$value = addslashes($value);

	$query = "SELECT id_contact
				FROM lcm_contact
				WHERE ((value = '$value')";

	if ($type_person)
		$query .= " AND (type_person = '$type_person')";

	if ($id)
		$query .= " AND (id_of_person = $id)";

	if ($type_contact) {
		// [AG] Let's try this - we accept for $type_contact integer, string or array of integers or strings
		// Thus we can specify more flexible searches
		switch (gettype($type_contact)) {
			case "string":
				$type_contact = get_contact_type_id($type_contact);
			case "integer":
				$query .= " AND (type_contact = $type_contact)";
				break;
			case "array":
				$qs = '';
				foreach ($type_contact as $tc) {
					if (gettype($tc)=='string') $tc = get_contact_type_id($tc);
					$tc = intval($tc);
					$qs .= ($qs ? ',' : '') . $tc;
				}
				$query .= " AND (type_contact IN ($qs)";
				break;
			default:
				echo "Wrong is_existing_contact type_contact ($type_contact)";
		}

	}

	$query .= ")";

	$result = lcm_query($query);
	return (lcm_num_rows($result) > 0);
}

?>
