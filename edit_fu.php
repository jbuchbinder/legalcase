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
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

$fu_data=array();

// Initiate session
session_start();

if (empty($errors)) {
    // Clear form data
    $fu_data = array('referer'=>$HTTP_REFERER);

	if (isset($followup)) {
		// Register followup as session variable
	    if (!session_is_registered("followup"))
			session_register("followup");

		// Prepare query
		$q="SELECT *
			FROM lcm_followup
			WHERE id_followup=$followup";

		// Do the query
		$result = lcm_query($q);

		// Process the output of the query
		if ($row = lcm_fetch_array($result)) {
			// Get followup details
			foreach($row as $key=>$value) {
				$fu_data[$key] = $value;
			}
		} else die("There's no such follow-up!");

		// Check for access rights
		if (!allowed($fu_data['id_case'],'e')) die("You don't have permission to edit this case's information!");
	} else {
		if ($case>0) {
			// Check for access rights
			if (!allowed($case,'w')) die("You don't have permission to add information to this case!");

			// Setup default values
			$fu_data['id_case'] = $case; // Link to the case
			$fu_data['date_start'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
		} else die("Add followup to which case?");
	}
}

$types=array("assignment","suspension","delay","conclusion","consultation","correspondance","travel","other");

// Edit followup details form

// lcm_page_start("Follow-up details");
lcm_page_start("Edit follow-up");

?>

<!-- [ML:repetition] <h1>Edit follow-up information:</h1 -->

<form action="upd_fu.php" method="POST">
	<table><caption>Details of follow-up:</caption>
		<!-- [ML;tech-talk] tr><th>Parameter</th><th>Value</th></tr -->
		<tr><td>Start date:</td>
			<td>
				<!-- <input name="date_start" value="<?php echo clean_output($fu_data['date_start']); ?>">
				<?php echo f_err('date_start',$errors); ?> -->

				<?php echo get_date_inputs('start', $fu_data['date_start']); ?>
			</td></tr>
		<tr><td>End date:</td>
			<td>
				<!-- <input name="date_end" value="<?php echo clean_output($fu_data['date_end']); ?>">
				<?php echo f_err('date_end',$errors); ?> -->
			
				<?php echo get_date_inputs('end', $fu_data['date_end']); ?>
			</td></tr>
		<tr><td>Type:</td>
			<td><select name="type" size="1"><option selected><?php echo clean_output($fu_data['type']); ?></option>
			<?php
			foreach($types as $item) {
				if ($item != $fu_data['type']) {
					echo "<option>$item</option>\n";
				}
			} ?>
			</select></td></tr>
		<tr><td>Description:</td>
			<td><textarea name="description" rows="5" cols="30"><?php
			echo clean_output($fu_data['description']); ?></textarea></td></tr>
		<tr><td>Sum billed:</td>
			<td><input name="sumbilled" value="<?php echo clean_output($fu_data['sumbilled']); ?>"></td></tr>
	</table>
	<button name="submit" type="submit" value="submit">Save</button>
	<button name="reset" type="reset">Reset</button>
	<input type="hidden" name="id_followup" value="<?php echo $fu_data['id_followup']; ?>">
	<input type="hidden" name="id_case" value="<?php echo $fu_data['id_case']; ?>">
	<input type="hidden" name="ref_edit_fu" value="<?php echo $fu_data['referer']; ?>">
</form>

<?php
	lcm_page_end();
?>
