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

	$Id: edit_org.php,v 1.27 2006/08/22 12:37:48 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_contacts');
include_lcm('inc_obj_org');

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

		$result = lcm_query($q);

		// Process the output of the query
		if ($row = lcm_fetch_array($result)) {
			// Get org details
			foreach($row as $key=>$value) {
				$_SESSION['form_data'][$key]=$value;
			}
		}
	}
}

if ($org) 
	lcm_page_start(_T('title_org_edit'), '', '', 'clients_neworg');
else
	lcm_page_start(_T('title_org_new'), '', '', 'clients_neworg');

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

echo '<form action="upd_org.php" method="post">' . "\n";

$obj_org = new LcmOrgInfoUI($org);
$obj_org->printEdit();

echo '<input type="hidden" name="ref_edit_org" value="' . _session('ref_edit_org') . '" />' . "\n";
echo '<p><button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button></p>\n";
echo "</form>\n";

// Clear errors and form data
$_SESSION['errors'] = array();
$_SESSION['form_data'] = array();
$_SESSION['org_data'] = array(); // DEPRECATED since 0.6.4

lcm_page_end();

?>
