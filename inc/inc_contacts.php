<?php

// Execute only once
if (defined('_INC_CONTACTS')) return;
define('_INC_CONTACTS', '1');

// type_person should be of the enum in the database (author, client, org, ..)
function get_contacts($type_person, $id, $type_contact = '') {
	$contacts = array();

	// XXX FIXME TODO very temporary untill we solved this issue..
	// Liste de metas qui retournent des listes de IDs? 1,2,3,4 ?
	if ($type_contact == 'email')
		$type_contact = 1;
	else
		echo "Wrong get_contact_author type $type_contact";

	$query = "SELECT type_contact, value
				FROM lcm_contact
				WHERE id_of_person = " . intval($id) . " 
					AND type_person = '" . addslashes($type_person) . "' ";

	if ($type_contact)
		$query .= "AND type_contact IN (" . addslashes($type_contact) . ")";

	$result = lcm_query($query);

	while($row = lcm_fetch_array($result))
		$contacts[] = $row;

	return $contacts;
}

function add_contact($type_person, $id, $type_contact, $value) {
	// XXX FIXME TODO very temporary untill we solved this issue..
	if ($type_contact == 'email')
		$type_contact = 1;
	else
		echo "Wrong get_contact_author type ($type_contact)";

	$query = "INSERT INTO lcm_contact (type_person, id_of_person, type_contact, value)
		VALUES('" . addslashes($type_person) . "', " . intval($id) . ", "
			. intval($type_contact) . ", " . "'" . addslashes($value) . "')";

	lcm_query($query);
}

function is_existing_contact($type_person, $id = 0, $type_contact, $value) {
	// XXX FIXME TODO very temporary untill we solved this issue..
	if ($type_contact == 'email')
		$type_contact = 1;
	else
		echo "Wrong get_contact_author type ($type_contact)";
	
	$id = intval($id);
	$type_contact = intval($type_contact);
	$value = addslashes($value);

	$query = "SELECT id_contact
				FROM lcm_contact
				WHERE value = '" . $value . "'";
	
	if ($type_contact)
		$query .= " AND type_contact = " . $type_contact;

	if ($id)
		$query .= " AND id_person = " . $id;

	$result = lcm_query($query);
	return (lcm_num_rows($result) > 0);
}

?>
