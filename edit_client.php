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

	$Id: edit_client.php,v 1.24 2005/02/07 22:57:41 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

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

echo '<table class="tbl_usr_dtl">' . "\n";

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
echo '<td><select name="gender">' . "\n";

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
		<tr><td>Created on:</td>
			<td><?php echo format_date($client_data['date_creation'], 'short'); ?></td></tr>
<?php if ($client_citizen_number == 'yes') {
?>		<tr><td><?php echo _T('person_input_citizen_number'); ?></td>
			<td><input name="citizen_number" value="<?php echo clean_output($client_data['citizen_number']); ?>" class="search_form_txt"></td></tr>
<?php }
?>		<tr><td><?php echo _T('person_input_address'); ?></td>
			<td><textarea name="address" rows="3" class="frm_tarea"><?php echo clean_output($client_data['address']); ?></textarea></td></tr>
		<tr><td><?php echo _T('person_input_civil_status'); ?></td>
			<td><input name="civil_status" value="<?php echo clean_output($client_data['civil_status']); ?>" class="search_form_txt"></td></tr>
		<tr><td><?php echo _T('person_input_income'); ?></td>
			<td><input name="income" value="<?php echo clean_output($client_data['income']); ?>" class="search_form_txt"></td></tr>
	</table>

	<button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate') ?></button>
	<input type="hidden" name="ref_edit_client" value="<?php echo $HTTP_REFERER ?>">
</form>

<?php
	lcm_page_end();

	// Reset error messages
	$_SESSION['errors'] = array();
	$_SESSION['client'] = array();
?>
