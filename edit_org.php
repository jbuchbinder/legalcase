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

	$Id: edit_org.php,v 1.18 2005/02/04 09:12:21 makaveev Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

// Initiate session
// [ML] now in inc_auth session_start();

// Initialise variables
$org = intval($_GET['org']);

if (empty($errors)) {
    // Clear form data
    $org_data=array();
	$org_data['referer'] = $HTTP_REFERER;

	if (isset($org)) {
		// Register org as session variable
	    if (!session_is_registered("org"))
			session_register("org");

		// Prepare query
		$q="SELECT *
			FROM lcm_org
			WHERE id_org=$org";

		// Do the query
		$result = lcm_query($q);

		// Process the output of the query
		if ($row = mysql_fetch_array($result)) {
			// Get org details
			foreach($row as $key=>$value) {
				$org_data[$key]=$value;
			}
		}
	} else {
		// Setup default values
		$org_data['date_creation'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
	}
}

if ($org) 
	lcm_page_start("Edit organisation details");
else
	lcm_page_start("New organisation");
?>

<form action="upd_org.php" method="POST">
<fieldset class="info_box">
	<!-- strong>Organisation ID:</strong><br />
	<?php echo $org_data['id_org']; ?>
	<input type="hidden" name="id_org" value="<?php echo $org_data['id_org']; ?>"><br /><br / -->
	
	<strong><?php echo _T('org_input_name'); ?></strong><br />
	<input name="name" value="<?php echo clean_output($org_data['name']); ?>" class="search_form_txt"><br /><br />
	
	<!-- strong>Created on:</strong><br />
	<input name="date_creation" value="<?php echo clean_output($org_data['date_creation']); ?>" class="search_form_txt">
	<?php echo f_err('date_creation',$errors); ?><br /><br / -->
	
	<!-- strong>Updated on:</strong><br />
	<input name="date_update" value="<?php echo clean_output($org_data['date_update']); ?>" class="search_form_txt">
	<?php echo f_err('date_update',$errors); ?><br /><br / -->
	<strong>Address:</strong><br />
	<textarea name="address" cols="50" rows="3" class="frm_tarea"><?php echo clean_output($org_data['address']); ?></textarea><br /><br />
<?php
	echo '<input type="hidden" name="ref_edit_org" value="' . $GLOBALS['HTTP_REFERER'] . '">' . "\n";
	echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . '</button>' . "\n";
	if ($org && $prefs['mode'] == 'extended')
		echo '<button name="reset" type="reset" class="simple_form_btn">' . _T('button_reset') . '</button>' . "\n";
?>
</fieldset>
</form>

<?php
	lcm_page_end();
?>
