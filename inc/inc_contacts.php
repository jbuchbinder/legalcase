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

	$Id: inc_contacts.php,v 1.42 2006/09/08 13:28:23 mlutfy Exp $
*/


// Execute only once
if (defined('_INC_CONTACTS')) return;
define('_INC_CONTACTS', '1');

include_lcm('inc_keywords');

function get_contact_type_id($name) {
	$kwg = get_kwg_from_name($name);
	return $kwg['id_group'];
}

// type_person should be of the enum in the database (author, client, org, ..)
// type_contact is the name of the contact, and can be a comma seperated list.
//
// For example: get_contacts('author', $id_author, 'email_main,email_alternate')
//    will return all e-mail contacts for an author
// And: get_contacts('author', $id_author, 'email_main,address_main', 'not')
//    will return all contacts except email_main and address_main
function get_contacts($type_person, $id, $type_contact = '', $not = '') {
	$contacts = array();

	if (! $id)
		return $contacts;

	// In case there is still some deprecated code
	if ($type_contact == 'email')
		$type_contact = 'email_main';
	
	$query = "SELECT c.type_contact, c.value, c.id_contact, c.date_update, kwg.name, kwg.title, kwg.policy, kwg.quantity
				FROM lcm_contact as c, lcm_keyword_group as kwg
				WHERE kwg.id_group = c.type_contact
					AND id_of_person = " . intval($id) . " 
					AND type_person = '" . addslashes($type_person) . "' ";

	if ($not)
		$not = 'NOT'; // avoid typos

	if ($type_contact) {
		$all_types = explode(",", $type_contact);
		$id_type_contact = "";
		$seperator = "";

		foreach ($all_types as $t) {
			$kwg = get_kwg_from_name($t);
			$id_type_contact .= $seperator . $kwg['id_group'];
			$seperator = ", ";
		}

		$query .= "AND type_contact " . $not . " IN (" . addslashes($id_type_contact) . ")";
	}

	$result = lcm_query($query);
	$tmp_row = array();

	while($row = lcm_fetch_array($result)) {
		$contacts[$row['title'] . $row['id_contact']] = $row;
		$contacts[$row['title'] . $row['id_contact']]['title'] = remove_number_prefix($row['title']);
	}

	ksort($contacts);
	reset($contacts);

	return $contacts;
}

function get_contact_by_id($id_contact) {
	if (! $id_contact)
		return NULL;

	$query = "SELECT *
				FROM lcm_contact
				WHERE id_contact = " . intval($id_contact);
	
	$result = lcm_query($query);

	if (($row = lcm_fetch_array($result)))
		return $row;
	else
		return NULL;
}

function add_contact($type_person, $id_person, $type_contact, $value) {
	if (! $id_person)
		lcm_panic("add_contact: no id_person was provided");

	$validator_file = 'contact';
	$validator_func = 'LcmCustomValidateContact';

	// Because initially contact types did not start with '+', but then
	// there would always be small parts of the code missing them..
	if ($type_contact{0} != '+')
		$type_contact = '+' . $type_contact;

	// This way, we can validate 'phone_home' or 'phone_mobile' using validate_contact_phone.php
	// and class LcmCustomValidateContactPhone()
	if (preg_match("/^\+?([A-Za-z0-9]+)_/", $type_contact, $regs)) {
		$validator_file .= '_' . $regs[1];
		$validator_func .= ucfirst($regs[1]);
		lcm_debug("*********** MATCHES: " . $type_contact . ":" . $validator_file);
	}

	if (include_validator_exists($validator_file)) {
		include_validator($validator_file);
		$foo = new $validator_func();

		if ($err = $foo->validate($type_contact, $id_person, $type_contact, $value))
			return $err;
	}

	if ($type_contact == '+email')
		$type_contact = get_contact_type_id('+email_main');
	else
		$type_contact = get_contact_type_id($type_contact);

	$query = "INSERT INTO lcm_contact (type_person, id_of_person, type_contact, value, date_update)
		VALUES('" . addslashes($type_person) . "', " . intval($id_person) . ", "
			. intval($type_contact) . ", " . "'" . addslashes($value) . "', NOW())";

	lcm_query($query);
	return '';
}

// Access rights check is the responsability of parent function
function update_contact($id_contact, $new_value) {
	if (! $id_contact)
		lcm_panic("update_contact: no id_contact was provided");

	$old_info = get_contact_by_id($id_contact);
	$kw = get_kwg_from_id($old_info['type_contact']);
	$type_contact = $kw['name'];
	$validator_file = 'contact';
	$validator_func = 'LcmCustomValidateContact';

	// This way, we can validate 'phone_home' or 'phone_mobile' using validate_contact_phone.php
	if (preg_match("/^\+?([A-Za-z0-9]+)_/", $type_contact, $regs)) {
		$validator_file .= '_' . $regs[1];
		$validator_func .= ucfirst($regs[1]);
		lcm_debug("*********** MATCHES: " . $type_contact . ":" . $validator_file);
	}

	
	if (include_validator_exists($validator_file)) {
		include_validator($validator_file);
		$foo = new $validator_func();

		if ($err = $foo->validate($old_info['type_person'], $old_info['id_of_person'], $type_contact, $new_value))
			return $err;
	}

	if ($old_info['value'] != $new_value) {
		$query = "UPDATE lcm_contact
			SET value = '" . clean_input($new_value) . "', 
				date_update = NOW()
					WHERE id_contact = " . intval($id_contact);

		lcm_query($query);
	}

	return '';
}

// Access rights check is the responsability of parent function
function delete_contact($id_contact) {
	if (! $id_contact)
		lcm_panic("delete_contact: no id_contact was provided");
	
	$query = "DELETE FROM lcm_contact
				WHERE id_contact = " . intval($id_contact);

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
	$value = clean_input($value);

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
				if ($type_contact{0} != '+')
					$type_contact = '+' . $type_contact;

				$type_contact = get_contact_type_id($type_contact);
			case "integer":
				$query .= " AND (type_contact = $type_contact)";
				break;
			case "array":
				$qs = '';
				foreach ($type_contact as $tc) {
					if (gettype($tc) == 'string') {
						if ($tc{0} != '+') $tc = '+' . $tc;
						$tc = get_contact_type_id($tc);
					}

					$tc = intval($tc);
					$qs .= ($qs ? ',' : '') . $tc;
				}

				$query .= " AND (type_contact IN ($qs)";
				break;
			default:
				lcm_panic("Wrong is_existing_contact type_contact ($type_contact)");
		}
	}

	$query .= ")";
	$result = lcm_query($query);

	return (lcm_num_rows($result) > 0);
}

function show_existing_contact($c, $num) {
	// FIXME: This has a minor bug: if there was an error in (ex:) the title
	// of the user/client/org, and the value of a contact was changed, then
	// the modification will be lost, because we didn't use the $_SESSION value.
	
	echo '<tr><td align="left" valign="top">' 
		. f_err_star('upd_contact_' . $num)
		. f_err_star('contact_' . $c['name'])
		. _Ti($c['title'])
		. ($c['policy'] != 'optional' ? '<br/>(' . _T('keywords_input_policy_' . $c['policy']) . ')' : '')
		. "</td>\n";
	echo '<td align="left" valign="top">';

	echo '<input name="contact_id[]" id="contact_id_' . $num . '" '
		. 'type="hidden" value="' . $c['id_contact'] . '" />' . "";
	echo '<input name="contact_type[]" id="contact_type_' . $num . '" '
		. 'type="hidden" value="' . $c['type_contact'] . '" />' . "";

	// [ML] Removed spaces (nbsp) between elements, or it causes the layout
	// to show on two lines when using a large font.
	echo '<input name="contact_value[]" id="contact_value_' . $num . '" type="text" '
		. 'class="search_form_txt" size="35" value="' 
		. (isset($_SESSION['form_data']['contact_value'][$num]) ?  $_SESSION['form_data']['contact_value'][$num] : clean_output($c['value']))
		. '"/>';
	echo f_err_star('email') . "";

	if ($c['policy'] != 'mandatory') {
		echo '<label for="id_del_contact' . $num . '">';
		echo '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" alt="' . _T('generic_info_delete_contact') . '" title="' . _T('generic_info_delete_contact') . '" />';
		echo '</label>';
		echo '&nbsp;<input type="checkbox" id="id_del_contact' . $num . '" name="del_contact_' . $c['id_contact'] . '"/>';
	}

	echo "</td>\n</tr>\n\n";
}



// For new contact (may be specific 'email_main', etc. or empty for combobox)
// Should be used in a two column html table (ID + Value)
// The $exceptions allow to filter out contact types which are quantity = 'one'.
function show_new_contact($num_new, $type_person, $ctype = "__add__", $exceptions = array()) {
	$all_contact_types = get_kwg_all('contact');

	// There may be a config error, or admin removed all contact types
	if (! count($all_contact_types))
		return; 

	echo "<tr>\n";

	// Contact type (either specific or 'Add contact')
	echo '<td align="left" valign="top">' . f_err_star('new_contact_' . $num_new);

	if ($ctype == "__add__") {
		echo _Ti('generic_input_contact_other');
	} else {
		$c = get_kwg_from_name($ctype);

		echo f_err_star('contact_' . $c['name']);
		echo _Ti(remove_number_prefix($c['title']));
		echo ($c['policy'] != 'optional' ? '<br/>(' . _T('keywords_input_policy_' . $c['policy']) . ')' : '');
	}

	echo '</td>';
	echo '<td align="left" valign="top">';


	// Avoids that the values in these fields get lost when there is an error after submitting the form
	$value = '';
	$type = '';

	if (isset($_SESSION['form_data']['new_contact_type_name'][$num_new]))
		$type = $_SESSION['form_data']['new_contact_type_name'][$num_new];

	if (isset($_SESSION['form_data']['new_contact_value'][$num_new]))
		$value = $_SESSION['form_data']['new_contact_value'][$num_new];

	if ($ctype == "__add__") {
		echo "<div>\n";
		echo '<select name="new_contact_type_name[]" id="new_contact_type_' . $num_new . '" class="sel_frm">' . "\n";
		echo "<option value=''>" . " ... " . "</option>\n";

		foreach ($all_contact_types as $contact) {
			if (! ($contact['quantity'] == 'one' && isset($exceptions[$contact['name']]) && $type != $contact['name'])) {
				$sel = isSelected($type == $contact['name']);
				echo "<option value='" . $contact['name'] . "' $sel>" . _T($contact['title']) . "</option>\n";
			}
		}

		echo "</select>\n";
		echo "</div>\n";

		echo "<div>\n";
		echo '<input type="text" size="40" name="new_contact_value[]" id="new_contact_value_' . $num_new . '" ';
		echo ' value="' . $value . '" ';
		echo 'class="search_form_txt" />' . "\n";
		echo "</div>\n";
	} else {
		echo '<input name="new_contact_type_name[]" id="new_contact_type_name_' . $num_new . '" '
			. 'type="hidden" value="' . $ctype . '" />' . "\n";

		echo '<input name="new_contact_value[]" id="new_contact_value_' . $num_new . '" type="text" '
			. 'class="search_form_txt" size="35" value="' . $value . '"/>&nbsp;';
	}

	echo "</td>\n";
	echo "</tr>\n";
}

function show_edit_contacts_form($type_person, $id_person) {
	$cpt = 0;
	$cpt_new = 0;

	//
	// Start by showing the mandatory / recommended contact types
	//
	$all_contact_types = get_kwg_all('contact');
	$seen_contacts = array();

	foreach ($all_contact_types as $c) {
		if ($c['policy'] == 'mandatory' || $c['policy'] == 'recommended') {
			$foo = get_contacts($type_person, $id_person, $c['name']);

			if (count($foo)) {
				foreach ($foo as $f) {
					show_existing_contact($f, $cpt);
					$cpt++;
				}
			} else {
				show_new_contact($cpt_new, $type_person, $c['name']);
				$cpt_new++;
			}

			$seen_contacts[$c['name']] = 1;
		}
	}

	//
	// Show all the rest
	//
	$contacts_other = get_contacts($type_person, $id_person, implode(',', array_keys($seen_contacts)), 'not');
	
	foreach ($contacts_other as $contact) {
		show_existing_contact($contact, $cpt);
		$seen_contacts[$contact['name']] = 1;
		$cpt++;
	}

	// Show "other new contact" (twice)
	show_new_contact($cpt_new, $type_person, '__add__', $seen_contacts);
	$cpt_new++;

	show_new_contact($cpt_new, $type_person, '__add__', $seen_contacts);
	$cpt_new++;
}

function show_all_contacts($type_person, $id_of_person) {
	global $author_session;
	$show_emails = ! (read_meta('hide_emails') && ($author_session['status'] != 'admin'));

	$contacts = get_contacts($type_person, $id_of_person);
	$html = "";
	$i = 0;

	if (! count($contacts))
		return;

	show_page_subtitle(_T('generic_subtitle_contacts'));
	echo '<table border="0" class="tbl_usr_dtl" width="100%">' . "\n";

	foreach($contacts as $c) {
		// Check if the contact is an e-mail
		echo "<tr>\n";
		echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . _T($c['title']) . ":</td>\n";
		echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";

		if ($show_emails && strpos($c['name'],'email') === 0)
			echo '<a href="mailto:' . $c['value'] . '" class="content_link">' . $c['value'] . '</a></td>\n';
		else
			echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . $c['value'] . "</td>\n";

		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">' 
			. ($c['date_update'] != null ? format_date($c['date_update'], 'date_short') : '')
			. "</td>\n";
		echo "</tr>\n";

		$i++;
	}

	echo "</table>\n";
	echo "<br />\n";
}

function update_contacts_request($type_person, $id_of_person) {
	// This will be useful later, to check mandatory/optional contacts
	$all_contact_kwg = get_kwg_all('contact');

	//
	// Update existing contacts
	//
	if (isset($_REQUEST['contact_value'])) {
		$contacts = $_REQUEST['contact_value'];
		$c_ids = $_REQUEST['contact_id'];
		$c_types = $_REQUEST['contact_type'];

		//
		// Check if the contacts provided are really attached to the person
		// or else the user can provide a form with false contacts.
		//
		$all_contacts = get_contacts($type_person, $id_of_person);
		for ($cpt = 0; isset($c_ids[$cpt]) && $c_ids[$cpt]; $cpt++) {
			$valid = false;

			foreach ($all_contacts as $c)
				if ($c['id_contact'] == $c_ids[$cpt])
					$valid = true;

			if (! $valid)
				lcm_panic("Invalid modification of existing contact detected.");
		}

		for ($cpt = 0; isset($c_ids[$cpt]); $cpt++) {
			// Check first to see if the contact is mandatory
			$kwg = get_kwg_from_id($c_types[$cpt]);
			$delete_allowed = true;

			if ($kwg['policy'] == 'mandatory') {
				// XXX Having policy == 'mandatory' but quantity = many
				// really makes a mess, and is not handled.

				$delete_allowed = false;
			}

			if (_request('del_contact_' . $c_ids[$cpt])) {
				if ($delete_allowed) {
					lcm_debug("Contact DEL: $type_person, $id_of_person, " . $c_ids[$cpt], 1);
					delete_contact($c_ids[$cpt]);
				} else {
					$_SESSION['errors']['upd_contact_' . $cpt] = _T('warning_field_mandatory');
				}
			} else {
				if ((! $delete_allowed) && (! $contacts[$cpt])) {
					$_SESSION['errors']['upd_contact_' . $cpt] = _T('warning_field_mandatory');
				} else {
					lcm_debug("Contact UPD: $type_person, $id_of_person, " . $c_ids[$cpt] . ' = ' . $contacts[$cpt], 1);
					$err = update_contact($c_ids[$cpt], $contacts[$cpt]);

					if ($err)
						$_SESSION['errors']['upd_contact_' . $cpt] = $err;
				}
			}
		}
	}

	//
	// New contacts
	//
	if (isset($_REQUEST['new_contact_value'])) {
		$cpt = 0;
		$new_contacts = $_REQUEST['new_contact_value'];
		$c_type_names = $_REQUEST['new_contact_type_name'];

		while (isset($new_contacts[$cpt])) {
			// Process only new contacts which have a value
			if ($new_contacts[$cpt]) {
				// And make sure that they have a "type of contact"
				if ($c_type_names[$cpt]) {
					lcm_debug("Contact NEW: $type_person, $id_of_person, Name = " . $c_type_names[$cpt] . ', ' . $new_contacts[$cpt], 1);
					$err = add_contact($type_person, $id_of_person, $c_type_names[$cpt], $new_contacts[$cpt]);

					if ($err) 
						$_SESSION['errors']['new_contact_' . $cpt] = $err;
				} else {
					$_SESSION['errors']['new_contact_' . $cpt] = "Please specify the type of contact."; // TRAD
				}
			}

			$cpt++;
		}
	}

	//
	// Check if all mandatory contacts were provided
	//
	$all_contacts = get_contacts($type_person, $id_of_person);
	
	foreach ($all_contact_kwg as $c) {
		if ($c['policy'] == 'mandatory') {
			$found = false;

			foreach ($all_contacts as $a)
				if ($a['name'] == $c['name'] && trim($a['value']))
					$found = true;
			
			if (! $found)
				$_SESSION['errors']['contact_' . $c['name']] = _Ti($c['title']) . _T('warning_field_mandatory');
		}
	}
}

?>
