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

	$Id: edit_client.php,v 1.42 2005/03/24 09:31:43 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_contacts');

// Get input value(s)
$client = intval($_GET['client']);

// Get site preferences
$client_name_middle = read_meta('client_name_middle');
$client_citizen_number = read_meta('client_citizen_number');
$client_civil_status = read_meta('client_civil_status');
$client_income = read_meta('client_income');

if (empty($_SESSION['errors'])) {
	$client_data = array('id_client' => 0,'referer' => $HTTP_REFERER);

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
	if (! $_SESSION['client_data']['id_client'])
		$_SESSION['client_data']['id_client'] = 0;

	if (isset($_SESSION['client_data']))
		foreach($_SESSION['client_data'] as $key => $value)
			$client_data[$key] = $value;

}

if ($client > 0) {
	lcm_page_start(_T('title_client_edit') . ' ' . get_person_name($client_data));
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
		. '<input type="hidden" name="id_client" value="' . $client_data['id_client'] . '" /></td></tr>' . "\n";
}

echo '<tr><td>' . f_err_star('name_first', $_SESSION['errors']) . _T('person_input_name_first') . '</td>' . "\n";
echo '<td><input name="name_first" value="' . clean_output($client_data['name_first']) . '" class="search_form_txt" /></td></tr>' . "\n";

// [ML] always show middle name, if any, no matter the configuration
if ($client_data['name_middle'] || $client_name_middle == 'yes') {
	echo '<tr><td>' . f_err_star('name_middle', $_SESSION['errors']) . _T('person_input_name_middle') . '</td>' . "\n";
	echo '<td><input name="name_middle" value="' . clean_output($client_data['name_middle']) . '" class="search_form_txt" /></td></tr>' . "\n";
}
	
echo '<tr><td>' . f_err_star('name_last', $_SESSION['errors']) . _T('person_input_name_last') . '</td>' . "\n";
echo '<td><input name="name_last" value="' . clean_output($client_data['name_last']) . '" class="search_form_txt" /></td></tr>' . "\n";

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

	if ($client_data['id_client']) {
		echo "<tr>\n";
		echo '<td>' . _Ti('time_input_date_creation') . '</td>';
		echo '<td>' . format_date($client_data['date_creation'], 'full') . '</td>';
		echo "</tr>\n";
	}

	if ($client_citizen_number == 'yes') {
		echo "<tr>\n";
		echo '<td>' . _T('person_input_citizen_number') . '</td>';
		echo '<td><input name="citizen_number" value="' . clean_output($client_data['citizen_number']) . '" class="search_form_txt"></td>';
		echo "</tr>\n";
	}

	/*
	echo "<tr>\n";
	echo '<td>' .  _T('person_input_address') . '</td>';
	echo '<td><textarea name="address" rows="3" class="frm_tarea">' . clean_output($client_data['address']) . '</textarea></td>';
	echo "</tr>\n";
	*/

	global $system_kwg;
	
	if ($client_civil_status == 'yes') {
		echo "<tr>\n";
		echo '<td>' . _T('person_input_civil_status') . '</td>';
		echo '<td>';
		echo '<select name="civil_status">';

		if (! $client_data['civil_status'])
			$client_data['civil_status'] = $system_kwg['civilstatus']['suggest'];

		foreach($system_kwg['civilstatus']['keywords'] as $kw) {
			$sel = ($client_data['civil_status'] == $kw['name'] ? ' selected="selected"' : '');
			echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>';
		}

		echo '</select>';
		echo '</td>';
		echo "</tr>\n";
	}

	if ($client_income == 'yes') {
		echo "<tr>\n";
		echo '<td>' . _T('person_input_income') . '</td>';
		echo '<td>';
		echo '<select name="civil_status">';
		
		if (! $client_data['income'])
			$client_data['income'] = $system_kwg['income']['suggest'];

		foreach($system_kwg['income']['keywords'] as $kw) {
			$sel = ($client_data['income'] == $kw['name'] ? ' selected="selected"' : '');
			echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>';
		}
		
		echo '</select>';
		echo '</td>';
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

	show_edit_contacts_form('client', $client_data['id_client']);

	//
	// Organisations this client represents
	//
	echo "\t<tr>";
	echo '<td colspan="2" align="center" valign="middle" class="heading">';
	echo '<h4>' . _T('client_subtitle_organisations') . '</h4>';
	echo '</td>';
	echo "</tr>\n";
	$q = "SELECT name FROM lcm_client_org, lcm_org WHERE id_client=" . $client_data['id_client'] . " AND lcm_client_org.id_org=lcm_org.id_org";
	$result = lcm_query($q);
	$orgs = array();
	while ($row = lcm_fetch_array($result)) {
		$orgs[] = $row['name'];
	}
	echo "\t<tr><td>" . 'Representative of:' . '</td><td>' . join(', ',$orgs) . (count($orgs)>0 ? '&nbsp;' : ''); // TRAD
	$q = "SELECT lcm_org.id_org,name,id_client
		FROM lcm_org
		LEFT JOIN lcm_client_org
		ON (id_client=" . $client_data['id_client'] . "
		AND lcm_org.id_org=lcm_client_org.id_org)
		WHERE id_client IS NULL";
	$result = lcm_query($q);
	if (lcm_num_rows($result) > 0) {
		echo "\t\t<select name=\"new_org\">\n";
		echo "\t\t\t<option selected='selected' value=\"0\">- Select organisation -</option>\n"; // TRAD
		while ($row = lcm_fetch_array($result)) {
			echo "\t\t\t<option value=\"" . $row['id_org'] . '">' . $row['name'] . "</option>\n";
		}
		echo "\t\t</select>\n";
		echo "\t\t<button name=\"submit\" type=\"submit\" value=\"add_org\" class=\"simple_form_btn\">" . 'Add' . "</button>\n"; // TRAD
	}
	echo "\t</td></tr>\n";

?>

	</table>

	<p><button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate') ?></button></p>
	<input type="hidden" name="ref_edit_client" value="<?php echo $HTTP_REFERER ?>" />
</form>

<?php
	lcm_page_end();

	// Reset error messages
	$_SESSION['errors'] = array();
	$_SESSION['client'] = array();
?>
