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

	$Id: edit_fu.php,v 1.61 2005/02/10 09:02:40 makaveev Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_keywords_default');

// Initiate session
// [ML] now in inc_auth session_start();

// Read the policy settings
$fu_sum_billed = read_meta('fu_sum_billed');
$fu_allow_modif = read_meta('fu_allow_modif');
$modify = ($fu_allow_modif == 'yes');
$admin = ($GLOBALS['author_session']['status']=='admin');

if (empty($_SESSION['errors'])) {
    // Clear form data
	// [ML] FIXME: referer may be null, should default to fu_det.php?fu=...
	// [AG] Since id_followup of new follow-ups is not known at this point,
	// default redirection to fu_det.php is done in upd_fu.php
	$_SESSION['fu_data'] = array('ref_edit_fu' => $GLOBALS['HTTP_REFERER']);

	if (isset($_GET['followup'])) {
		$_SESSION['followup'] = intval($_GET['followup']);

		// Register followup as session variable
//		if (!session_is_registered("followup"))
//			session_register("followup");

		// Fetch the details on the specified follow-up
		$q="SELECT *
			FROM lcm_followup
			WHERE id_followup=" . $_SESSION['followup'];

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach($row as $key=>$value) {
				$_SESSION['fu_data'][$key] = $value;
			}
		} else die("There's no such follow-up!");

		// Set the case ID, to which this followup belongs
		$case = $_SESSION['fu_data']['id_case'];
	} else {
		unset($_SESSION['followup']);
		if ($_GET['case'] > 0) {
			$case = intval($_GET['case']);

			// Check for access rights
			if (!allowed($case,'w'))
				die("You don't have permission to add information to this case!");

			// Setup default values
			$_SESSION['fu_data']['id_case'] = $case; // Link to the case
			$_SESSION['fu_data']['date_start'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
			$_SESSION['fu_data']['date_end']   = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
		} else {
			die("Add followup to which case?");
		}
	}

	// Check for access rights
	$edit = allowed($_SESSION['fu_data']['id_case'],'e');
	if (!($admin || $edit))
		die("You don't have permission to edit this case's information!");

}

if (isset($_SESSION['followup']))
	lcm_page_start("Edit follow-up");
else
	lcm_page_start("New follow-up");

// Show a bit of background on the case

echo "<ul style=\"padding-left: 0.5em; padding-top: 0.2; padding-bottom: 0.2; font-size: 12px;\">\n";

// Name of case
$query = "SELECT title
		FROM lcm_case
		WHERE id_case=$case";

$result = lcm_query($query);
while ($row = lcm_fetch_array($result))  // should be only once
	echo '<li style="list-style-type: none;">' . _T('info_followup_to_case') . " " . $row['title'] . "</li>\n";

// We dump all the clients and org in the same array, then show
// them on screen in a more densed way
// Could be more esthetic or ergonomic, but works for now..
$query = "SELECT cl.id_client, name_first, name_middle, name_last
			FROM lcm_case_client_org as cco, lcm_client as cl
			WHERE cco.id_case=$case
			  AND cco.id_client = cl.id_client";

$result = lcm_query($query);
$numrows = lcm_num_rows($result);
$current = 0;

$all_clients = array();

while ($all_clients[] = lcm_fetch_array($result));

$query = "SELECT org.name, cco.id_client, org.id_org
			FROM lcm_case_client_org as cco, lcm_org as org
			WHERE cco.id_case=$case
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

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

// Disable inputs when edit is not allowed for the field
$dis = (($admin || ($edit && $modify)) ? '' : 'disabled');
?>

<form action="upd_fu.php" method="POST">
	<table class="tbl_usr_dtl" width="99%">
		<tr><td><?php echo _T('fu_input_date_start'); ?></td>
			<td><?php echo _T('calendar_info_date');  
				$name = (($admin || ($edit && $modify)) ? 'start' : '');
				echo get_date_inputs($name, $_SESSION['fu_data']['date_start'], false);
				echo ' ' . _T('calendar_info_time') . ' ';
				echo get_time_inputs($name, $_SESSION['fu_data']['date_start']);
				echo f_err_star('date_start',$errors); ?>
			</td>
		</tr>
		<tr><td><?php echo (($prefs['time_intervals'] == 'absolute') ? _T('fu_input_date_end') : _T('fu_input_time_length')); ?></td>
			<td><?php 
				if ($prefs['time_intervals'] == 'absolute') {
					$name = (($admin || ($edit && ($_SESSION['fu_data']['date_end']=='0000-00-00 00:00:00'))) ? 'end' : '');
					echo _T('calendar_info_date'); 
					echo get_date_inputs($name, $_SESSION['fu_data']['date_end']);
					echo ' ';
					echo _T('calendar_info_time') . ' ';
					echo get_time_inputs($name, $_SESSION['fu_data']['date_end']);
					echo f_err_star('date_end',$errors);
				} else {
					$name = (($admin || ($edit && ($_SESSION['fu_data']['date_end']=='0000-00-00 00:00:00'))) ? 'delta' : '');
					$interval = ( ($_SESSION['fu_data']['date_end']!='0000-00-00 00:00:00') ?
							strtotime($_SESSION['fu_data']['date_end']) - strtotime($_SESSION['fu_data']['date_start']) : 0);
					echo _T('calendar_info_time') . ' ';
					echo get_time_interval_inputs($name, $interval);
					echo f_err_star('date_end',$errors);
				} ?>
			</td>
		</tr>
		<tr><td><?php echo _T('fu_input_type'); ?></td>
			<td><select <?php echo $dis; ?> name="type" size="1" class="sel_frm">
			<?php

			global $system_kwg;

			if ($_SESSION['fu_data']['type'])
				$default_fu = $_SESSION['fu_data']['type'];
			else
				$default_fu = $system_kwg['followups']['suggest'];

			foreach($system_kwg['followups']['keywords'] as $kw) {
				$sel = ($kw['name'] == $default_fu ? ' selected="selected"' : '');
				echo "<option value='" . $kw['name'] . "'" . "$sel>" . _T($kw['title']) . "</option>\n";
			}

			?>
			</select></td></tr>
		<tr><td valign="top"><?php echo _T('fu_input_description'); ?></td>
			<td><textarea <?php echo $dis; ?> name="description" rows="15" cols="40" class="frm_tarea"><?php
			echo clean_output($_SESSION['fu_data']['description']) . "</textarea></td></tr>\n";
// Sum billed field
			if ($fu_sum_billed == "yes") {
?>		<tr><td><?php echo _T('fu_input_sum_billed'); ?></td>
			<td><input <?php echo $dis; ?> name="sumbilled" value="<?php echo
			clean_output($_SESSION['fu_data']['sumbilled']); ?>" class="search_form_txt" size='10' />
			<?php
				// [ML] If we do this we may as well make a function
				// out of it, but not sure where to place it :-)
				// This code is also in config_site.php
				$currency = read_meta('currency');
				if (empty($currency)) {
					$current_lang = $GLOBALS['lang'];
					$GLOBALS['lang'] = read_meta('default_language');
					$currency = _T('currency_default_format');
					$GLOBALS['lang'] = $current_lang;
				}

				echo htmlspecialchars($currency);
				echo "</td></tr>";
			}
		echo "	</table>\n";

		if (isset($_SESSION['followup'])) {
			echo '	<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
			if ($prefs['mode'] == 'extended')
				echo '<button name="reset" type="reset" class="simple_form_btn">' . _T('button_reset') . "</button>\n";
		} else {
			// More buttons for 'extended' mode
			if ($prefs['mode'] == 'extended') {
				echo '<button name="submit" type="submit" value="add" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
				echo '<button name="submit" type="submit" value="addnew" class="simple_form_btn">' . _T('add_and_open_new') . "</button>\n";
				echo '<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('add_and_go_to_details') . "</button>\n"; }
			else	// Less buttons in simple mode
				echo '<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
		}
	?>

	<input type="hidden" name="id_followup" value="<?php echo $_SESSION['fu_data']['id_followup']; ?>">
	<input type="hidden" name="id_case" value="<?php echo $_SESSION['fu_data']['id_case']; ?>">
	<input type="hidden" name="ref_edit_fu" value="<?php echo $_SESSION['fu_data']['ref_edit_fu']; ?>">
</form>

<?php
	lcm_page_end();
?>
