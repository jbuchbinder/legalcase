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

	$Id: edit_client.php,v 1.50 2006/03/20 23:03:10 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_contacts');
include_lcm('inc_obj_client');

// Get input value(s)
$id_client = intval(_request('client', 0));

// Get site preferences
$client_name_middle = read_meta('client_name_middle');
$client_citizen_number = read_meta('client_citizen_number');
$client_civil_status = read_meta('client_civil_status');
$client_income = read_meta('client_income');

if (empty($_SESSION['errors'])) {
	$form_data = array('id_client' => 0,'referer' => $_SERVER['HTTP_REFERER']);

	if ($id_client > 0) {
		$q = 'SELECT * 
				FROM lcm_client 
				WHERE id_client = ' . $id_client;

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach($row as $key=>$value) {
				$form_data[$key] = $value;
			}
		}
	}
} else {
	// Fetch previously submitted values, if any
	if (! $_SESSION['form_data']['id_client'])
		$_SESSION['form_data']['id_client'] = 0;

	if (isset($_SESSION['form_data']))
		foreach($_SESSION['form_data'] as $key => $value)
			$form_data[$key] = $value;

}

if ($id_client > 0) {
	lcm_page_start(_T('title_client_edit') . ' ' . get_person_name($form_data), '', '', 'clients_newclient');
} else {
	lcm_page_start(_T('title_client_new'), '', '', 'clients_newclient');
}

echo show_all_errors();

echo '<form action="upd_client.php" method="post">' . "\n";

if (_request('attach_case')) {
	echo '<input type="hidden" name="attach_case" id="attach_case" value="'
		. _request('attach_case')
		. '" />' . "\n";
}

$obj_client = new LcmClientInfoUI($form_data['id_client']);
$obj_client->printEdit();


	//
	// Organisations this client represents
	//
	/* [ML] too confusing
	echo "<tr>\n";
	echo '<td colspan="2" align="center" valign="middle" class="heading">';
	echo '<h4>' . _T('client_subtitle_organisations') . '</h4>';
	echo '</td>';
	echo "</tr>\n";
	$q = "SELECT name FROM lcm_client_org, lcm_org WHERE id_client=" . $form_data['id_client'] . " AND lcm_client_org.id_org=lcm_org.id_org";
	$result = lcm_query($q);
	$orgs = array();
	while ($row = lcm_fetch_array($result)) {
		$orgs[] = $row['name'];
	}
	echo "\t<tr><td>" . 'Representative of:' . '</td><td>' . join(', ',$orgs) . (count($orgs)>0 ? '&nbsp;' : ''); // TRAD
	$q = "SELECT lcm_org.id_org,name,id_client
		FROM lcm_org
		LEFT JOIN lcm_client_org
		ON (id_client=" . $form_data['id_client'] . "
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
	echo "</td>\n</tr>\n";
	*/

?>

	<p><button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate') ?></button></p>
	<input type="hidden" name="ref_edit_client" value="<?php echo $_SERVER['HTTP_REFERER'] ?>" />
</form>

<?php
	lcm_page_end();

	// Reset error messages
	$_SESSION['errors'] = array();
	$_SESSION['form_data'] = array();
	$_SESSION['client'] = array(); // DEPRECATED
?>
