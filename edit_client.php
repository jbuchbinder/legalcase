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

	$Id: edit_client.php,v 1.33 2005/03/01 08:55:57 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_contacts');

// Get input value(s)
$client = intval($_GET['client']);

// Get site preferences
$client_name_middle = read_meta('client_name_middle');
$client_citizen_number = read_meta('client_citizen_number');

if (empty($_SESSION['errors'])) {
	$client_data = array();
	$client_data['referer'] = $HTTP_REFERER;

	if ($client > 0) {
		$q = 'SELECT * 
				FROM lcm_client 
				WHERE id_client=' . $client;

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach($row as $key=>$value) {
				$client_data[$key] = $value;
			}
		}
	}
} else {
	// Fetch previously submitted values, if any
	if (isset($_SESSION['client']))
		foreach($_SESSION['client'] as $key => $value)
			$client_data[$key] = $value;
}

if ($client > 0) {
	lcm_page_start(_T('title_client_edit')
		. $client_data['name_first'] . ' '
		. ( ($client_name_middle == 'yes') ? $client_data['name_middle'] . ' ' : '' )
		. $client_data['name_last']);
} else {
	lcm_page_start(_T('title_client_new'));
}

if (isset($_SESSION['errors']))
	echo show_all_errors($_SESSION['errors']);

echo '<form action="upd_client.php" method="post">' . "\n";

if (isset($_REQUEST['attach_case'])) {
	echo '<input type="hidden" name="attach_case" id="attach_case" value="'
		. $_REQUEST['attach_case']
		. '" />' . "\n";
}

echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";

if($client_data['id_client']) {
	echo "<tr><td>" . _T('client_input_id') . "</td>\n";
	echo "<td>" . $client_data['id_client']
		. '<input type="hidden" name="id_client" value="' . $client_data['id_client'] . '"></td></tr>' . "\n";
}

echo '<tr><td>' . f_err_star('name_first', $_SESSION['errors']) . _T('person_input_name_first') . '</td>' . "\n";
echo '<td><input name="name_first" value="' . clean_output($client_data['name_first']) . '" class="search_form_txt"></td></tr>' . "\n";

if ($client_name_middle == 'yes') {
	echo '<tr><td>' . f_err_star('name_middle', $_SESSION['errors']) . _T('person_input_name_middle') . '</td>' . "\n";
	echo '<td><input name="name_middle" value="' . clean_output($client_data['name_middle']) . '" class="search_form_txt"></td></tr>' . "\n";
}
	
echo '<tr><td>' . f_err_star('name_last', $_SESSION['errors']) . _T('person_input_name_last') . '</td>' . "\n";
echo '<td><input name="name_last" value="' . clean_output($client_data['name_last']) . '" class="search_form_txt"></td></tr>' . "\n";

echo '<tr><td>' . f_err_star('gender', $_SESSION['errors']) . _T('person_input_gender') . '</td>' . "\n";
echo '<td><select name="gender" class="sel_frm">' . "\n";

$opt_sel_male = $opt_sel_female = $opt_sel_unknown = '';

if ($client_data['gender'] == 'male')
	$opt_sel_male = 'selected="selected" ';
else if ($client_data['gender'] == 'female')
	$opt_sel_female = 'selected="selected" ';
else
	$opt_sel_unknown = 'selected="selected" ';

echo '<option ' . $opt_sel_unknown . 'value="unknown">' . _T('info_not_available') . "</option>\n";
echo '<option ' . $opt_sel_male . 'value="male">' . _T('person_input_gender_male') . "</option>\n";
echo '<option ' . $opt_sel_female . 'value="female">' . _T('person_input_gender_female') . "</option>\n";

?>
				</select>
			</td></tr>

<?php
	echo "<tr>\n";
	echo '<td>' . "Created on:" . '</td>';
	echo '<td>' . format_date($client_data['date_creation'], 'short') . '</td>';
	echo "</tr>\n";

	if ($client_citizen_number == 'yes') {
		echo "<tr>\n";
		echo '<td>' . _T('person_input_citizen_number') . '</td>';
		echo '<td><input name="citizen_number" value="' . clean_output($client_data['citizen_number']) . '" class="search_form_txt"></td>';
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo '<td>' .  _T('person_input_address') . '</td>';
	echo '<td><textarea name="address" rows="3" class="frm_tarea">' . clean_output($client_data['address']) . '</textarea></td>';
	echo "</tr>\n";
	
	if ($client_civil_status == 'yes') {
		echo "<tr>\n";
		echo '<td>' . _T('person_input_civil_status') . '</td>';
		echo '<td><input name="civil_status" value="' . clean_output($client_data['civil_status']) . '" class="search_form_txt"></td>';
		echo "</tr>\n";
	}

	if ($client_income == 'yes') {
		echo "<tr>\n";
		echo '<td>' . _T('person_input_income') . '</td>';
		echo '<td><input name="income" value="' . clean_output($client_data['income']) . '" class="search_form_txt"></td>';
		echo "</tr>\n";
	}

	//
	// Contacts (e-mail, phones, etc.)
	//
	
	echo "<tr>\n";
	echo '<td colspan="2" align="center" valign="middle" class="heading">';
	echo '<h4>' . _T('client_subtitle_contacts') . '</h4>';
	echo '</td>';
	echo "</tr>\n";

	$cpt = 0;
	$cpt_new = 0;

	$emailmain_exists = false;
	$addrmain_exists = false;

	$contacts_emailmain = get_contacts('client', $client_data['id_client'], 'email_main');
	$contacts_addrmain = get_contacts('client', $client_data['id_client'], 'address_main');
	$contacts_other = get_contacts('client', $client_data['id_client'], 'email_main,address_main', 'not');
	$contacts = get_contacts('client', $client_data['id_client']);

/*
	function print_existing_contact($c, $num) {
		echo '<tr><td align="right" valign="top">' . _T($c['title']) . "\n";
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

		echo '<label for="id_del_contact' . $num . '"><img src="images/jimmac/stock_trash-16.png" width="16" height="16" alt="Delete?" title="Delete?" /></label>&nbsp;<input type="checkbox" id="id_del_contact' . $num . '" name="del_contact_' . $c['id_contact'] . '"/>';

		echo "</td>\n</tr>\n\n";

	}

	// For new specific type of contact, such as 'email_main', 'address_main'
	function print_new_contact($type_kw, $type_name, $num_new) {
		echo '<tr><td align="right" valign="top">' . _T("kw_contacts_" . $type_kw . "_title") . "\n";
		echo '<td align="left" valign="top">';
		echo '<input name="new_contact_type_name[]" id="new_contact_type_name_' . $num_new . '" '
			. 'type="hidden" value="' . $type_name . '" />' . "\n";

		echo '<input name="new_contact_value[]" id="new_contact_value_' . $num_new . '" type="text" '
			. 'class="search_form_txt" size="35" value=""/>&nbsp;';
		
		echo "</td>\n</tr>\n\n";
	}
*/
/*	
	// First show the main address
	foreach ($contacts_addrmain as $contact) {
		print_existing_contact($contact, $cpt); 
		$cpt++;
		$addrmain_exists = true;
	}

	if (! $addrmain_exists) {
		print_new_contact('addressmain', 'address_main', $cpt_new);
		$cpt_new++;
	}

	// Second show the email_main
	foreach ($contacts_emailmain as $contact) {
		print_existing_contact($contact, $cpt);
		$cpt++;
		$emailmain_exists = true;
	}

	if (! $emailmain_exists) {
		print_new_contact('emailmain', 'email_main', $cpt_new);
		$cpt_new++;
	}

	// Show all the rest
	foreach ($contacts_other as $contact) {
		print_existing_contact($contact, $cpt);
		$cpt++;
	}
*/

	// Show all contacts
	foreach ($contacts as $contact) {
		print_existing_contact($contact, $cpt);
		$cpt++;
	}

	// Show "new contact"
?>
		<tr>
			<td align="right" valign="top">
			
			<?php
				echo f_err_star('new_contact_' . $cpt_new, $_SESSION['errors']);
				echo "Add contact";
			?>
			
			</td>
			<td align="left" valign="top">
				<div>
				<?php
					global $system_kwg;

					echo '<select name="new_contact_type_name[]" id="new_contact_type_' . $cpt_new . '" class="sel_frm">' . "\n";
					echo "<option value=''>" . "- select contact type -" . "</option>\n";

					foreach ($system_kwg['contacts']['keywords'] as $contact) {
					//	if ($contact['name'] != 'email_main' && $contact['name'] != 'address_main') {
							echo "<option value='" . $contact['name'] . "'>" . _T($contact['title']) . "</option>\n";
					//	}
					}
					echo "</select>\n";

				?>
				</div>
				<div>
					<input type='text' size='40' name='new_contact_value[]' id='new_contact_value_<?php echo $cpt_new; ?>' 
					
					<?php 
						echo ' value="' . $client_data['new_contact_' . $cpt_new] . '" ';
						$cpt_new++;
					?>
						
					class='search_form_txt' />
				</div>
			</td>
		</tr>
	</table>
	<br />
	<button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate') ?></button>
	<input type="hidden" name="ref_edit_client" value="<?php echo $HTTP_REFERER ?>">
</form>

<?php
	lcm_page_end();

	// Reset error messages
	$_SESSION['errors'] = array();
	$_SESSION['client'] = array();
?>
