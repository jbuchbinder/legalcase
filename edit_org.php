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

	$Id: edit_org.php,v 1.19 2005/03/02 14:56:57 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

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
	lcm_page_start("Edit organisation details");
else
	lcm_page_start("New organisation");

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

?>

<form action="upd_org.php" method="POST">
<fieldset class="info_box">
	<!-- strong>Organisation ID:</strong><br />
	<?php echo $_SESSION['org_data']['id_org']; ?><br /><br / -->
	<input type="hidden" name="id_org" value="<?php echo $_SESSION['org_data']['id_org']; ?>">
	
	<strong><?php echo _T('org_input_name'); ?></strong><br />
	<input name="name" value="<?php echo clean_output($_SESSION['org_data']['name']); ?>" class="search_form_txt">
	<?php echo f_err_star('name',$_SESSION['errors']); ?><br /><br />
	
	<!-- strong>Created on:</strong><br />
	<input name="date_creation" value="<?php echo clean_output($_SESSION['org_data']['date_creation']); ?>" class="search_form_txt">
	<?php echo f_err_star('date_creation',$_SESSION['errors']); ?><br /><br / -->
	
	<!-- strong>Updated on:</strong><br />
	<input name="date_update" value="<?php echo clean_output($_SESSION['org_data']['date_update']); ?>" class="search_form_txt">
	<?php echo f_err_star('date_update',$_SESSION['errors']); ?><br /><br / -->
	<strong>Address:</strong><br />
	<textarea name="address" cols="50" rows="3" class="frm_tarea"><?php echo clean_output($_SESSION['org_data']['address']); ?></textarea><br /><br />
<?php
	echo '<input type="hidden" name="ref_edit_org" value="' . $_SESSION['org_data']['ref_edit_org'] . '">' . "\n";
	echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . '</button>' . "\n";
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
