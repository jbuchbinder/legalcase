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

	$Id: inc_contacts.php,v 1.23 2005/06/01 08:07:10 mlutfy Exp $
*/


// Execute only once
if (defined('_INC_CONTACTS')) return;
define('_INC_CONTACTS', '1');

function get_contact_type_id($name) {
	global $system_kwg;

	if (array_key_exists($name, $system_kwg['contacts']['keywords']))
		return $system_kwg['contacts']['keywords'][$name]['id_keyword'];
	else {
		// Attempt to make it more error resistant because there seems
		// to be cases where a 'write_metas()' may have failed.
		$query = "SELECT id_keyword FROM lcm_keyword WHERE name = '" . clean_input($name) . "'";
		$result = lcm_query($query);
		if ($row = lcm_fetch_array($result)) {
			lcm_log("get_contact_type_id: there was a meta problem, I called write_meta() again");
			lcm_log("get_contact_type_id: .. you may want to check the permissions to inc/data/ directory");
			write_metas();
			return $row['id_keyword'];
		}
	}

	lcm_panic("get_contact_type_id: keyword $name does not exist");
}

// type_person should be of the enum in the database (author, client, org, ..)
// type_contact is the name of the contact, and can be a comma seperated list.
//
// For example: get_contacts('author', $id_author, 'email_main,email_alternate')
//    will return all e-mail contacts for an author
// And: get_contacts('author', $id_author, 'email_main,address_main', 'not')
//    will return all contacts except email_main and address_main
function get_contacts($type_person, $id, $type_contact = '', $not = '') {
	global $system_kwg;
	$contacts = array();

	if (! $id)
		return $contacts;

	// In case there is still some deprecated code
	if ($type_contact == 'email')
		$type_contact = 'email_main';
	
	$query = "SELECT type_contact, value, id_contact
				FROM lcm_contact
				WHERE id_of_person = " . intval($id) . " 
					AND type_person = '" . addslashes($type_person) . "' ";

	if ($not)
		$not = 'NOT'; // avoid typos

	if ($type_contact) {
		$all_types = explode(",", $type_contact);
		$id_type_contact = "";
		$seperator = "";

		foreach ($all_types as $t) {
			$id_type_contact .= $seperator . $system_kwg['contacts']['keywords'][$t]['id_keyword'];
			$seperator = ", ";
		}

		$query .= "AND type_contact " . $not . " IN (" . addslashes($id_type_contact) . ")";
	}

	$result = lcm_query($query);
	$tmp_row = array();

	while($row = lcm_fetch_array($result)) {
		// Perhaps not the most efficient, but very practical
		$tmp_row['type_contact'] = $row['type_contact'];
		$tmp_row['value'] = $row['value'];
		$tmp_row['name'] = 'unknown';
		$tmp_row['title'] = 'unknown';
		$tmp_row['id_contact'] = $row['id_contact'];

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

	if ($type_contact == 'email')
		$type_contact = get_contact_type_id('email_main');
	else
		$type_contact = get_contact_type_id($type_contact);

	$query = "INSERT INTO lcm_contact (type_person, id_of_person, type_contact, value)
		VALUES('" . addslashes($type_person) . "', " . intval($id_person) . ", "
			. intval($type_contact) . ", " . "'" . addslashes($value) . "')";

	lcm_query($query);
}

// Access rights check is the responsability of parent function
function update_contact($id_contact, $new_value) {
	if (! $id_contact)
		lcm_panic("update_contact: no id_contact was provided");

	$query = "UPDATE lcm_contact
				SET value = '" . clean_input($new_value) . "'
				WHERE id_contact = " . intval($id_contact);

	lcm_query($query);
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

function show_existing_contact($c, $num) {
	// XXX CSS
	echo '<tr><td align="left" valign="top">' . _Ti($c['title']) . "</td>\n";
	echo '<td align="left" valign="top">';

	echo '<input name="contact_id[]" id="contact_id_' . $num . '" '
		. 'type="hidden" value="' . $c['id_contact'] . '" />' . "";
	echo '<input name="contact_type[]" id="contact_type_' . $num . '" '
		. 'type="hidden" value="' . $c['type_contact'] . '" />' . "";

	// [ML] Removed spaces (nbsp) between elements, or it causes the layout
	// to show on two lines when using a large font.
	echo '<input name="contact_value[]" id="contact_value_' . $num . '" type="text" '
		. 'class="search_form_txt" size="35" value="' . clean_output($c['value']) . '"/>';
	echo f_err('email', $_SESSION['errors']) . "";

	echo '<label for="id_del_contact' . $num . '">';
	echo '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" alt="' . _T('generic_info_delete_contact') . '" title="' . _T('generic_info_delete_contact') . '" />';
	echo '</label>';
	echo '&nbsp;<input type="checkbox" id="id_del_contact' . $num . '" name="del_contact_' . $c['id_contact'] . '"/>';

	echo "</td>\n</tr>\n\n";
}



// For new contact (may be specific 'email_main', etc. or empty for combobox)
// Should be used in a two column table (ID + Value)
function show_new_contact($num_new, $type_person, $type_kw = "__add__", $type_name = "__add__") {
	echo "<tr>\n";

	// Contact type (either specific or 'Add contact')
	echo '<td align="left" valign="top">'
		. f_err_star('new_contact_' . $num_new, $_SESSION['errors']);

	if ($type_kw == "__add__") {
		echo _Ti('generic_input_contact_other');
	} else {
		// echo _Ti("kw_contacts_" . $type_kw . "_title");
		echo _Ti(_Tkw('contacts', $type_kw));
	}

	echo '</td>';
	echo '<td align="left" valign="top">';


	// [ML] clean this.. one day...
	// It avoids that the values in these fields get lost when there is an error after submitting the form
	$value = '';
	$type = '';

	if ($type_person == 'client' || $type_person == 'org') {
		if ($_SESSION[$type_person . '_data']['new_contact_type_name'][$num_new])
			$type = $_SESSION[$type_person . '_data']['new_contact_type_name'][$num_new];

		if ($_SESSION[$type_person . '_data']['new_contact_value'][$num_new])
			$value = $_SESSION[$type_person . '_data']['new_contact_value'][$num_new];
	} else if ($type_person == 'author') {
		if ($_SESSION['usr']['new_contact_type_name'][$num_new])
			$type = $_SESSION['usr']['new_contact_type_name'][$num_new];

		if ($_SESSION['usr']['new_contact_value'][$num_new])
			$value = $_SESSION['usr']['new_contact_value'][$num_new];
	}

	if ($type_name == "__add__") {
		global $system_kwg;

		echo "<div>\n";
		echo '<select name="new_contact_type_name[]" id="new_contact_type_' . $num_new . '" class="sel_frm">' . "\n";
		echo "<option value=''>" . "- select contact type -" . "</option>\n"; // TRAD

		foreach ($system_kwg['contacts']['keywords'] as $contact) {
			$sel = ($type == $contact['name'] ? 'selected="selected"' : '');
			echo "<option value='" . $contact['name'] . "' $sel>" . _T($contact['title']) . "</option>\n";
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
			. 'type="hidden" value="' . $type_name . '" />' . "\n";

		echo '<input name="new_contact_value[]" id="new_contact_value_' . $num_new . '" type="text" '
			. 'class="search_form_txt" size="35" value="' . $value . '"/>&nbsp;';
	}

	echo "</td>\n";
	echo "</tr>\n";
}

function show_edit_contacts_form($type_person, $id_person) {
	$cpt = 0;
	$cpt_new = 0;

	$emailmain_exists = false;
	$addrmain_exists = false;

	$contacts_emailmain = get_contacts($type_person, $id_person, 'email_main');
	$contacts_addrmain = get_contacts($type_person, $id_person, 'address_main');
	$contacts_other = get_contacts($type_person, $id_person, 'email_main,address_main', 'not');
	
	// First show the main address
	foreach ($contacts_addrmain as $contact) {
		show_existing_contact($contact, $cpt); 
		$cpt++;
		$addrmain_exists = true;
	}

	if (! $addrmain_exists) {
		show_new_contact($cpt, $type_person, 'address_main', 'address_main');
		$cpt_new++;
	}

	// Second show the email_main
	foreach ($contacts_emailmain as $contact) {
		show_existing_contact($contact, $cpt);
		$cpt++;
		$emailmain_exists = true;
	}

	if (! $emailmain_exists) {
		show_new_contact($cpt_new, $type_person, 'email_main', 'email_main');
		$cpt_new++;
	}

	// Show all the rest
	foreach ($contacts_other as $contact) {
		show_existing_contact($contact, $cpt);
		$cpt++;
	}

	// Show "new contact"
	show_new_contact($cpt_new, $type_person);
	$cpt_new++;

	show_new_contact($cpt_new, $type_person);
	$cpt_new++;
}

function show_all_contacts($type_person, $id_of_person) {
	global $author_session;
	$hide_emails = read_meta('hide_emails');

	$contacts = get_contacts($type_person, $id_of_person);

	$html = '<div class="prefs_column_menu_head">' . _T('generic_subtitle_contacts') . "</div>\n";
	$html .= '<table border="0" class="tbl_usr_dtl" width="100%">' . "\n";

	$i = 0;
	foreach($contacts as $c) {
		// Check if the contact is an e-mail
		if (strpos($c['name'],'email') === 0) {
			if (! ($hide_emails == 'yes' && $author_session['status'] != 'admin')) {
				$html .= "\t<tr>";
				$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . _T($c['title']) . ":</td>";
				$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
				$html .= '<a href="mailto:' . $c['value'] . '" class="content_link">' . $c['value'] . '</a></td>';
				$html .= "</tr>\n";
				$i++;
			}
		} else {
			$html .= "\t<tr>";
			$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . _T($c['title']) . ":</td>";
			$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . $c['value'] . "</td>";
			$html .= "</tr>\n";
			$i++;
		}
	}

	$html .= "</table><br />\n";

	if ($i > 0)
		echo $html;
}

function update_contacts_request($type_person, $id_of_person) {

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
		for ($cpt = 0; $c_ids[$cpt]; $cpt++) {
			$valid = false;

			foreach ($all_contacts as $c)
				if ($c['id_contact'] == $c_ids[$cpt])
					$valid = true;

			if (! $valid)
				lcm_panic("Invalid modification of existing contact detected.");
		}

		for ($cpt = 0; isset($c_ids[$cpt]); $cpt++) {
			if (isset($_REQUEST['del_contact_' . $c_ids[$cpt]]) && $_REQUEST['del_contact_' . $c_ids[$cpt]]) {
				delete_contact($c_ids[$cpt]);
			} else {
				update_contact($c_ids[$cpt], $contacts[$cpt]);
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
					add_contact($type_person, $id_of_person, $c_type_names[$cpt], $new_contacts[$cpt]);
				} else {
					$_SESSION['errors']['new_contact_' . $cpt] = "Please specify the type of contact."; // TRAD
				}
			}

			$cpt++;
		}
	}
}

?>
