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

	$Id: edit_org.php,v 1.20 2005/03/24 10:37:06 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_contacts');

// Initialise variables
$org = intval($_GET['org']);

if (empty($_SESSION['errors'])) {
	// Clear form data
	$_SESSION['org_data']=array();
	$_SESSION['org_data']['ref_edit_org'] = $GLOBALS['HTTP_REFERER'];

	if (!empty($org)) {
		// Prepare query
		$q="SELECT *
			FROM lcm_org
			WHERE id_org=$org";

		// Do the query
		$result = lcm_query($q);

		// Process the output of the query
		if ($row = lcm_fetch_array($result)) {
			// Get org details
			foreach($row as $key=>$value) {
				$_SESSION['org_data'][$key]=$value;
			}
		}
	} else {
		// Setup default values
		//$_SESSION['org_data']['date_creation'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
	}
}

if ($org) 
	lcm_page_start("Edit organisation details"); // TRAD
else
	lcm_page_start("New organisation"); // TRAD

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

echo '<form action="upd_org.php" method="post">' . "\n";
echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";

// Organisation ID
if ($_SESSION['org_data']['id_org']) {
	echo "<tr>\n";
	echo "<td>" . _Ti('org_input_id') . "</td>\n";
	echo "<td>" . $_SESSION['org_data']['id_org'] 
		. '<input type="hidden" name="id_org" value="' . $_SESSION['org_data']['id_org'] . '" />'
		. "</td>\n";
	echo "</tr>\n";
}

// Organisation name
echo "<tr>\n";
echo "<td>" . f_err_star('name') . _Ti('org_input_name') . "</td>\n";
echo '<td><input name="name" value="' . clean_output($_SESSION['org_data']['name']) . '" class="search_form_txt" />'
	. "</td>\n";
echo "</tr>\n";

// Creation date
if ($_SESSION['org_data']['id_org']) {
	echo "<tr>\n";
	echo '<td>' . _Ti('time_input_date_creation') . '</td>';
	echo '<td>' . format_date($_SESSION['org_data']['date_creation'], 'full') . '</td>';
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

show_edit_contacts_form('org', $_SESSION['org_data']['id_org']);

/*
	<strong>Address:</strong><br />
	<textarea name="address" cols="50" rows="3" class="frm_tarea"><?php echo clean_output($_SESSION['org_data']['address']); ?></textarea><br /><br />
*/

echo "</table>\n";

echo '<input type="hidden" name="ref_edit_org" value="' . $_SESSION['org_data']['ref_edit_org'] . '">' . "\n";
echo '<p><button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button></p>\n";

if ($org && $prefs['mode'] == 'extended')
	echo '<button name="reset" type="reset" class="simple_form_btn">' . _T('button_reset') . '</button>' . "\n";

?>
</fieldset>
</form>

<?php
	// Clear errors, in case user 'jumps' to other edit page
	$_SESSION['errors'] = array();

	lcm_page_end();
?>
