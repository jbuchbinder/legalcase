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

	$Id: edit_client.php,v 1.15 2004/11/23 13:23:58 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');


if (isset($_REQUEST['client']) && $_REQUEST['client'] > 0)
	$client = intval($_REQUEST['client']);

$client_data = array();
session_start();

if (empty($errors)) {
    // Clear form data
    $client_data = array('referer' => $HTTP_REFERER);

	if (isset($client)) {
		// Register client as session variable
	    if (!session_is_registered("client"))
			session_register("client");

		$q = 'SELECT * FROM lcm_client WHERE id_client=' . $client;
		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach($row as $key=>$value) {
				$client_data[$key] = $value;
			}
		}
	} else {
		// Setup default values
		$client_data['date_creation'] = date('Y-m-d H:i:s'); // now
		$client_data['date_update'] = date('Y-m-d H:i:s'); // now
	}
}

if ($client) {
	lcm_page_start("Edit client: " 
		. $client_data['name_first'] . ' ' 
		. $client_data['name_middle'] . ' '
		. $client_data['name_last']);
} else {
	lcm_page_start("New client");
}

?>

<form action="upd_client.php" method="post">
	<table class="tbl_usr_dtl">
<?php
	if($client_data['id_client']) {
		echo "<tr><td>Client ID:</td>\n";
		echo "<td>" . $client_data['id_client']
			. '<input type="hidden" name="id_client" value="' . $client_data['id_client'] . '"></td></tr>' . "\n";
	}
?>
		<tr><td><?php echo _T('person_input_name_first') ?></td>
			<td><input name="name_first" value="<?php echo clean_output($client_data['name_first']); ?>" class="search_form_txt"></td></tr>
		<tr><td><?php echo _T('person_input_name_middle') ?></td>
			<td><input name="name_middle" value="<?php echo clean_output($client_data['name_middle']); ?>" class="search_form_txt"></td></tr>
		<tr><td><?php echo _T('person_input_name_last') ?></td>
			<td><input name="name_last" value="<?php echo clean_output($client_data['name_last']); ?>" class="search_form_txt"></td></tr>
		<tr><td><?php echo _T('person_input_gender') ?></td>
			<td><select name="gender">
					<option value="unknown"><?php echo _T('info_not_available') ?></option>
					<option value="male"><?php echo _T('person_input_gender_male') ?></option>
					<option value="female"><?php echo _T('person_input_gender_female') ?></option>
				</select>
			</td></tr>
		<tr><td>Created on:</td>
			<td><?php echo clean_output(date(_T('date_format_short'),strtotime($client_data['date_creation']))); ?></td></tr>
		<tr><td>Citizen number:</td>
			<td><input name="citizen_number" value="<?php echo clean_output($client_data['citizen_number']); ?>" class="search_form_txt"></td></tr>
		<tr><td>Address:</td>
			<td><textarea name="address" rows="3" class="frm_tarea"><?php echo clean_output($client_data['address']); ?></textarea></td></tr>
		<tr><td>Civil status:</td>
			<td><input name="civil_status" value="<?php echo clean_output($client_data['civil_status']); ?>" class="search_form_txt"></td></tr>
		<tr><td>Income:</td>
			<td><input name="income" value="<?php echo clean_output($client_data['income']); ?>" class="search_form_txt"></td></tr>
	</table>

	<button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate') ?></button>
	<!-- [ML] button name="reset" type="reset" class="simple_form_btn">Reset</button -->
	<input type="hidden" name="ref_edit_client" value="<?php echo $HTTP_REFERER ?>">
</form>

<?php
	lcm_page_end();
?>
