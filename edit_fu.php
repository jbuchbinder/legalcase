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

	$Id: edit_fu.php,v 1.82 2005/04/01 16:55:23 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_keywords');

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
		$case = intval($_GET['case']);

		if (! ($case > 0))
			lcm_panic("Edit follow-up: invalid 'case id': " . $_GET['case']);

		// Check for access rights
		if (!allowed($case,'w'))
			lcm_panic("You don't have permission to add information to this case");

		// Setup default values
		$_SESSION['fu_data']['id_case'] = $case; // Link to the case
		$_SESSION['fu_data']['date_start'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
		$_SESSION['fu_data']['date_end']   = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'

		// Set appointment start/end/reminder times to current time
		$_SESSION['fu_data']['app_start_time'] = date('Y-m-d H:i:s');
		$_SESSION['fu_data']['app_end_time'] = date('Y-m-d H:i:s');
		$_SESSION['fu_data']['app_reminder'] = date('Y-m-d H:i:s');

		// Check if the followup is created from appointment
		$app = intval($_GET['app']);
		if (! empty($app)) {
			$q = "SELECT * FROM lcm_app WHERE id_app=$app";
			$result = lcm_query($q);

			if (! ($row = lcm_fetch_array($result)))
				lcm_panic("There's no such appointment (app = $app)");

			// Get participant author(s)
			$participants = array();
			$q = "SELECT lcm_author_app.*,lcm_author.name_first,lcm_author.name_middle,lcm_author.name_last
				FROM lcm_author_app, lcm_author
				WHERE (id_app=$app AND lcm_author_app.id_author=lcm_author.id_author)";
			$res_author = lcm_query($q);
			if (lcm_num_rows($res_author)>0) {
				while ($author = lcm_fetch_array($res_author)) {
					$participants[] = get_person_name($author);
				}
			}

			// Get appointment client(s)
			$q = "SELECT lcm_app_client_org.*,lcm_client.name_first,lcm_client.name_middle,lcm_client.name_last,lcm_org.name
				FROM lcm_app_client_org, lcm_client
				LEFT JOIN  lcm_org ON lcm_app_client_org.id_org=lcm_org.id_org
				WHERE (id_app=$app AND lcm_app_client_org.id_client=lcm_client.id_client)";

			$res_client = lcm_query($q);

			if (lcm_num_rows($res_client)>0) {
				while ($client = lcm_fetch_array($res_client))
					$participants[] = get_person_name($client)
						. ( ($client['id_org'] > 0) ? " of " . $client['name'] : ''); // TRAD
			}

			// First i18n attempt..
			$_SESSION['fu_data']['description'] = _T('fu_info_after_event', array(
						'title' => _Ti(get_kw_title($row['type'])) . $row['title'],
						'date' => format_date($row['start_time']),
						'participants' => join(', ', $participants)));

			$_SESSION['fu_data']['description'] = str_replace('&nbsp;', ' ', $_SESSION['fu_data']['description']);

			/*
			   $_SESSION['fu_data']['description'] = 'Following the ' . _T(get_kw_title($row['type']))	
			   . ' (' . $row['title'] . ') from ' . str_replace('&nbsp;', ' ', format_date($row['start_time']));

			   $_SESSION['fu_data']['description'] .= ' involving ' . join(', ', $participants);
			 */

			// Set start and end times of the followup from the appointment
			$_SESSION['fu_data']['date_start'] = $row['start_time'];
			$_SESSION['fu_data']['date_end']   = $row['end_time'];

			// Save appointment ID as session variable
			$_SESSION['fu_data']['id_app'] = $app;
		}
	}

	// Check for access rights
	$edit = allowed($_SESSION['fu_data']['id_case'],'e');
	if (!($admin || $edit))
		lcm_panic("You don't have permission to edit this case's information");

}

if (isset($_SESSION['followup']))
	lcm_page_start(_T('title_fu_edit'));
else
	lcm_page_start(_T('title_fu_new'));

// Show a bit of background on the case
show_context_start();
show_context_case_title($case);
show_context_case_involving($case);
show_context_end();

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

// Disable inputs when edit is not allowed for the field
$dis = (($admin || ($edit && $modify)) ? '' : 'disabled');
?>

<form action="upd_fu.php" method="post">
	<table class="tbl_usr_dtl" width="99%">
		<tr><td><?php echo _T('fu_input_date_start'); ?></td>
			<td><?php 
				$name = (($admin || ($edit && $modify)) ? 'start' : '');
				echo get_date_inputs($name, $_SESSION['fu_data']['date_start'], false);
				echo ' ' . _T('time_input_time_at') . ' ';
				echo get_time_inputs($name, $_SESSION['fu_data']['date_start']);
				echo f_err_star('date_start', $errors); ?>
			</td>
		</tr>
		<tr><td><?php echo (($prefs['time_intervals'] == 'absolute') ? _T('fu_input_date_end') : _T('fu_input_time_length')); ?></td>
			<td><?php 
				if ($prefs['time_intervals'] == 'absolute') {
					$name = (($admin || ($edit && ($_SESSION['fu_data']['date_end']=='0000-00-00 00:00:00'))) ? 'end' : '');
					echo get_date_inputs($name, $_SESSION['fu_data']['date_end']);
					echo ' ';
					echo _T('time_input_time_at') . ' ';
					echo get_time_inputs($name, $_SESSION['fu_data']['date_end']);
					echo f_err_star('date_end',$errors);
				} else {
					$name = (($admin || ($edit && ($_SESSION['fu_data']['date_end']=='0000-00-00 00:00:00'))) ? 'delta' : '');
					$interval = ( ($_SESSION['fu_data']['date_end']!='0000-00-00 00:00:00') ?
							strtotime($_SESSION['fu_data']['date_end']) - strtotime($_SESSION['fu_data']['date_start']) : 0);
					echo get_time_interval_inputs($name, $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
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

			$opts = array();
			foreach($system_kwg['followups']['keywords'] as $kw)
				$opts[$kw['name']] = _T($kw['title']);
			asort($opts);

			foreach($opts as $k => $opt) {
				$sel = ($k == $default_fu ? ' selected="selected"' : '');
				echo "<option value='$k'$sel>$opt</option>\n";
			}

			?>
			</select></td></tr>
		<tr><td valign="top"><?php echo f_err_star('description') . _T('fu_input_description'); ?></td>
			<td><textarea <?php echo $dis; ?> name="description" rows="15" cols="60" class="frm_tarea"><?php
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
// Add followup appointment
		if (!isset($_GET['followup'])) {
			echo "<!-- Add appointment? -->\n\t\t<tr><td>";
			echo 'Add consequent appointment?';	// TRAD
			echo "</td><td>";
			echo '<input type="checkbox" name="add_appointment" />';
			echo "</td></tr>\n";

			echo "<!-- Start time -->\n\t\t<tr><td>";
			echo _T('app_input_date_start');
			echo "</td><td>";
			echo get_date_inputs('app_start', $_SESSION['fu_data']['app_start_time'], false);
			echo ' ' . _T('time_input_time_at') . ' ';
			echo get_time_inputs('app_start', $_SESSION['fu_data']['app_start_time']);
			echo f_err_star('app_start_time',$_SESSION['errors']);
			echo "</td></tr>\n";

			echo "<!-- End time -->\n\t\t<tr><td>";
			echo (($prefs['time_intervals'] == 'absolute') ? _T('app_input_date_end') : _T('app_input_time_length'));
			echo "</td><td>";
			if ($prefs['time_intervals'] == 'absolute') {
				echo get_date_inputs('app_end', $_SESSION['fu_data']['app_end_time']);
				echo ' ' . _T('time_input_time_at') . ' ';
				echo get_time_inputs('app_end', $_SESSION['fu_data']['app_end_time']);
				echo f_err_star('app_end_time',$_SESSION['errors']);
			} else {
				$interval = ( ($_SESSION['fu_data']['app_end_time']!='0000-00-00 00:00:00') ?
						strtotime($_SESSION['fu_data']['app_end_time']) - strtotime($_SESSION['fu_data']['app_start_time']) : 0);
			//	echo _T('calendar_info_time') . ' ';
				echo get_time_interval_inputs('app_delta', $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
				echo f_err_star('app_end_time',$_SESSION['errors']);
			}
			echo "</td></tr>\n";

			echo "<!-- Reminder -->\n\t\t<tr><td>";
			echo (($prefs['time_intervals'] == 'absolute') ? _T('app_input_reminder_time') : _T('app_input_reminder_offset'));
			echo "</td><td>";
			if ($prefs['time_intervals'] == 'absolute') {
				echo get_date_inputs('app_reminder', $_SESSION['fu_data']['app_reminder']);
				echo ' ' . _T('time_input_time_at') . ' ';
				echo get_time_inputs('app_reminder', $_SESSION['fu_data']['app_reminder']);
				echo f_err_star('app_reminder',$_SESSION['errors']);
			} else {
				$interval = ( ($_SESSION['fu_data']['app_end_time']!='0000-00-00 00:00:00') ?
						strtotime($_SESSION['fu_data']['app_start_time']) - strtotime($_SESSION['fu_data']['app_reminder']) : 0);
			//	echo _T('calendar_info_time') . ' ';
				echo get_time_interval_inputs('app_rem_offset', $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
				echo " before the start time"; // TRAD
				echo f_err_star('app_reminder',$_SESSION['errors']);
			}
			echo "</td></tr>\n";

			echo "<!-- Appointment title -->\n\t\t<tr><td>";
			echo _T('app_input_title');
			echo "</td><td>";
			echo '<input type="text" ' . $title_onfocus . $dis . ' name="app_title" size="50" value="';
			echo clean_output($_SESSION['fu_data']['app_title']) . '" />';
			echo "</td></tr>\n";

			echo "<!-- Appointment type -->\n\t\t<tr><td>";
			echo _T('app_input_type');
			echo "</td><td>";
			echo '<select ' . $dis . ' name="app_type" size="1" class="sel_frm">';

			global $system_kwg;

			if ($_SESSION['fu_app_data']['type'])
				$default_app = $_SESSION['fu_app_data']['type'];
			else
				$default_app = $system_kwg['appointments']['suggest'];

			$opts = array();
			foreach($system_kwg['appointments']['keywords'] as $kw)
				$opts[$kw['name']] = _T($kw['title']);
			asort($opts);

			foreach($opts as $k => $opt) {
				$sel = ($k == $default_app ? ' selected="selected"' : '');
				echo "<option value='$k'$sel>$opt</option>\n";
			}

			echo '</select>';
			echo "</td></tr>\n";

			echo "<!-- Appointment description -->\n\t\t<tr><td valign=\"top\">";
			echo _T('app_input_description');
			echo "</td><td>";
			echo '<textarea ' . $dis . ' name="app_description" rows="5" cols="40" class="frm_tarea">';
			echo clean_output($_SESSION['fu_data']['app_description']);
			echo '</textarea>';
			echo "</td></tr>\n";
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
	<input type="hidden" name="id_app" value="<?php echo $_SESSION['fu_data']['id_app']; ?>">
	<input type="hidden" name="ref_edit_fu" value="<?php echo $_SESSION['fu_data']['ref_edit_fu']; ?>">
</form>

<?php
	lcm_page_end();

	// Clear the errors, in case user jumps to other 'edit' page
	$_SESSION['errors'] = array();
	$_SESSION['fu_data'] = array();
?>
