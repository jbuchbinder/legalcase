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

// Initiate session
session_start();

if (empty($errors)) {
    // Clear form data
    $fu_data = array('ref_edit_fu'=>$HTTP_REFERER);

	if (isset($followup)) {
		// Register followup as session variable
	    if (!session_is_registered("followup"))
			session_register("followup");

		// Debug code
		echo "<!-- Followup: " . $followup . ", intval=" . intval($followup) . "-->\n";

		// Fetch the details on the specified follow-up
		$q="SELECT *
			FROM lcm_followup
			WHERE id_followup=" . intval($followup);

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach($row as $key=>$value) {
				$fu_data[$key] = $value;
			}
		} else die("There's no such follow-up!");

		// Check for access rights
		if (!allowed($fu_data['id_case'],'e'))
			die("You don't have permission to edit this case's information!");
	} else {
		if ($case > 0) {
			// Check for access rights
			if (!allowed($case,'w'))
				die("You don't have permission to add information to this case!");

			// Setup default values
			$fu_data['id_case'] = $case; // Link to the case
			$fu_data['date_start'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
		} else {
			die("Add followup to which case?");
		}
	}
}

$types = array("assignment","suspension","delay","conclusion","consultation","correspondance","travel","other");

if ($followup)
	lcm_page_start("Edit follow-up");
else
	lcm_page_start("New follow-up");

// Show a bit of background on the case
echo '<ul style="padding-left: 0.5em; padding-top: 0.2; padding-bottom: 0.2;">';

// Name of case
$query = "SELECT title
		FROM lcm_case
		WHERE id_case = " . intval($case);

$result = lcm_query($query);
while ($row = lcm_fetch_array($result))  // should be only once
	echo '<li style="list-style-type: none;">' . _T('info_followup_to_case') . " " . $row['title'] . "</li>\n";

// We dump all the clients and org in the same array, then show
// them on screen in a more densed way
// Could be more esthetic or ergonomic, but works for now..
$query = "SELECT cl.id_client, name_first, name_middle, name_last
			FROM lcm_case_client_org as cco, lcm_client as cl
			WHERE cco.id_case = " . intval($case) . "
			  AND cco.id_client = cl.id_client";

$result = lcm_query($query);
$numrows = lcm_num_rows($result);
$current = 0;

$all_clients = array();

while ($all_clients[] = lcm_fetch_array($result));

$query = "SELECT org.name, cco.id_client, org.id_org
			FROM lcm_case_client_org as cco, lcm_org as org
			WHERE cco.id_case = " . intval($case) . "
			  AND cco.id_org = org.id_org";

$result = lcm_query($query);
$numrows += lcm_num_rows($result);

// TODO: It would be nice to have the name of the contact for that
// organisation, if any, but then again, not the end of the world.
// (altough I we make a library of common functions, it will defenitely
// be a good thing to have)
while ($all_clients[] = lcm_fetch_array($result));

if ($numrows > 0)
	echo '<li style="list-style-type: none;">' . _T('info_followup_involving') . " ";

foreach ($all_clients as $client) {
	if ($client['id_client']) {
		echo '<a href="client_det.php?client=' . $client['id_client'] . '" class="content_link">'
			. $client['name_first'] . ' ' . $client['name_middle'] . ' ' . $client['name_last']
			. '</a>';

		if (++$current < $numrows)
			echo ", ";
	} else if ($client['id_org']) {
		echo '<a href="org_det.php?org=' . $client['id_org'] . '" class="content_link">'
			. $client['name']
			. '</a>';

		if (++$current < $numrows)
			echo ", ";
	}

}

if ($numrows > 0)
	echo "</li>\n";

echo "</ul>\n";

?>

<form action="upd_fu.php" method="POST">
	<table class="tbl_usr_dtl" width="99%">
		<tr><td>Start date:</td>
			<td><?php echo get_date_inputs('start', $fu_data['date_start'], false); ?><?php
			echo f_err('date_start',$errors); ?>
			</td>
		</tr>
		<tr><td>End date:</td>
			<td><?php echo get_date_inputs('end', $fu_data['date_end']); ?><?php
			echo f_err('date_end',$errors); ?>
		</tr>
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
			<td><textarea name="description" rows="15" cols="40" class="frm_tarea"><?php
			echo clean_output($fu_data['description']); ?></textarea></td></tr>
		<tr><td>Sum billed:</td>
			<td><input name="sumbilled" value="<?php echo clean_output($fu_data['sumbilled']); ?>" class="search_form_txt"></td></tr>
	</table>
	<button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate') ?></button>

	<?php
		if ($followup)
			echo '<button name="reset" type="reset" class="simple_form_btn">' . _T('button_reset') . '</button>'
	?>

	<input type="hidden" name="id_followup" value="<?php echo $fu_data['id_followup']; ?>">
	<input type="hidden" name="id_case" value="<?php echo $fu_data['id_case']; ?>">
	<input type="hidden" name="ref_edit_fu" value="<?php echo $fu_data['ref_edit_fu']; ?>">
</form>

<?php
	lcm_page_end();
?>
