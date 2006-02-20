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

	$Id: edit_org.php,v 1.25 2006/02/20 03:14:30 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_contacts');

// Initialise variables
$org = intval($_GET['org']);

if (empty($_SESSION['errors'])) {
	// Clear form data
	$_SESSION['form_data']=array();
	$_SESSION['form_data']['ref_edit_org'] = $_REQUEST['HTTP_REFERER'];

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
				$_SESSION['form_data'][$key]=$value;
			}
		}
	} else {
		// Setup default values
		//$_SESSION['form_data']['date_creation'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
	}
}

if ($org) 
	lcm_page_start(_T('title_org_edit'), '', '', 'clients_neworg');
else
	lcm_page_start(_T('title_org_new'), '', '', 'clients_neworg');

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

echo '<form action="upd_org.php" method="post">' . "\n";
echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";

// Organisation ID
if ($_SESSION['form_data']['id_org']) {
	echo "<tr>\n";
	echo "<td>" . _Ti('org_input_id') . "</td>\n";
	echo "<td>" . $_SESSION['form_data']['id_org'] 
		. '<input type="hidden" name="id_org" value="' . $_SESSION['form_data']['id_org'] . '" />'
		. "</td>\n";
	echo "</tr>\n";
}

// Organisation name
echo "<tr>\n";
echo "<td>" . f_err_star('name') . _Ti('org_input_name') . "</td>\n";
echo '<td><input name="name" value="' . clean_output($_SESSION['form_data']['name']) . '" class="search_form_txt" />'
	. "</td>\n";
echo "</tr>\n";

// Court registration number
echo "<tr>\n";
echo "<td>" . f_err_star('court_reg') . _Ti('org_input_court_reg') . "</td>\n";
echo '<td><input name="court_reg" value="' . clean_output($_SESSION['form_data']['court_reg']) . '" class="search_form_txt" />'
	. "</td>\n";
echo "</tr>\n";

// Tax number
echo "<tr>\n";
echo "<td>" . f_err_star('tax_number') . _Ti('org_input_tax_number') . "</td>\n";
echo '<td><input name="tax_number" value="' . clean_output($_SESSION['form_data']['tax_number']) . '" class="search_form_txt" />'
	. "</td>\n";
echo "</tr>\n";

// Statistical number
echo "<tr>\n";
echo "<td>" . f_err_star('stat_number') . _Ti('org_input_stat_number') . "</td>\n";
echo '<td><input name="stat_number" value="' . clean_output($_SESSION['form_data']['stat_number']) . '" class="search_form_txt" />'
	. "</td>\n";
echo "</tr>\n";

// Notes
echo "<tr>\n";
echo "<td>" . f_err_star('notes') . _Ti('org_input_notes') . "</td>\n";
echo '<td><textarea name="notes" id="input_notes" class="frm_tarea" rows="3" cols="60">'
	. clean_output($_SESSION['form_data']['notes'])
	. "</textarea>\n"
	. "</td>\n";
echo "</tr>\n";

// Creation date
if ($_SESSION['form_data']['id_org']) {
	echo "<tr>\n";
	echo '<td>' . _Ti('time_input_date_creation') . '</td>';
	echo '<td>' . format_date($_SESSION['form_data']['date_creation'], 'full') . '</td>';
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

show_edit_contacts_form('org', $_SESSION['form_data']['id_org']);

echo "</table>\n";

echo '<input type="hidden" name="ref_edit_org" value="' . $_SESSION['form_data']['ref_edit_org'] . '" />' . "\n";
echo '<p><button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button></p>\n";

if ($org && $prefs['mode'] == 'extended')
	echo '<button name="reset" type="reset" class="simple_form_btn">' . _T('button_reset') . '</button>' . "\n";

echo "</form>\n";

// Clear errors and form data
$_SESSION['errors'] = array();
$_SESSION['form_data'] = array();
$_SESSION['org_data'] = array(); // DEPRECATED since 0.6.4

lcm_page_end();

?>
