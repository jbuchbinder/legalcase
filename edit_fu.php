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

	$Id: edit_fu.php,v 1.108 2005/05/16 08:52:39 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_keywords');

// Read the policy settings
$fu_sum_billed = read_meta('fu_sum_billed');
$admin = ($GLOBALS['author_session']['status']=='admin');

if (empty($_SESSION['errors'])) {
    // Clear form data
	// [ML] FIXME: referer may be null, should default to fu_det.php?fu=...
	// [AG] Since id_followup of new follow-ups is not known at this point,
	// default redirection to fu_det.php is done in upd_fu.php
	$_SESSION['fu_data'] = array('ref_edit_fu' => $GLOBALS['HTTP_REFERER']);

	if (isset($_GET['followup'])) {
		$_SESSION['followup'] = intval($_GET['followup']);

		// Fetch the details on the specified follow-up
		$q="SELECT *
			FROM lcm_followup as fu
			WHERE fu.id_followup=" . $_SESSION['followup'];

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach($row as $key=>$value) {
				$_SESSION['fu_data'][$key] = $value;
			}
		} else lcm_panic("Edit follow-up: invalid 'follow-up id': " . $_SESSION['followup']);

		// Set the case ID, to which this followup belongs
		$case = $_SESSION['fu_data']['id_case'];

		// If editing "stage change"..
		if ($row['type'] == 'stage_change') 
			$old_stage = $row['case_stage'];

		// Get new stage from description field
		$tmp = unserialize((get_magic_quotes_runtime() ? stripslashes($_SESSION['fu_data']['description']) : $_SESSION['fu_data']['description']));
		if (isset($tmp['new_stage']))
			$new_stage = $tmp['new_stage'];

		// Case conclusion, if appropriate
		if ($_SESSION['fu_data']['type'] == 'stage_change' || is_status_change($_SESSION['fu_data']['type'])) {
			// description might be empty
			if (isset($tmp['description']))
				$_SESSION['fu_data']['description'] = $tmp['description'];

			if ($tmp['conclusion'])
				$_SESSION['fu_data']['conclusion'] = $tmp['conclusion'];

			if ($tmp['sentence'])
				$_SESSION['fu_data']['sentence'] = $tmp['sentence'];

			if ($tmp['sentence_val'])
				$_SESSION['fu_data']['sentence_val'] = $tmp['sentence_val'];
		}

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

		if (isset($_REQUEST['stage']))
			$new_stage = $_REQUEST['stage'];

		if (isset($_REQUEST['type']))
			$_SESSION['fu_data']['type'] = $_REQUEST['type'];

		//
		// Check if the followup is created from appointment
		//
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

			// Propose a description based on the appointment
			$_SESSION['fu_data']['description'] = _T('fu_info_after_event', array(
						'title' => _Ti(_Tkw('appointments', $row['type'])) . $row['title'],
						'date' => format_date($row['start_time']),
						'participants' => join(', ', $participants)));

			$_SESSION['fu_data']['id_app'] = $app;
			$_SESSION['fu_data']['date_start'] = $row['start_time'];
			$_SESSION['fu_data']['date_end']   = $row['end_time'];
			$_SESSION['fu_data']['description'] = str_replace('&nbsp;', ' ', $_SESSION['fu_data']['description']);
		}
	}
}

//
// Check for access rights
//
$edit  = allowed($_SESSION['fu_data']['id_case'], 'e');
$write = allowed($_SESSION['fu_data']['id_case'], 'w');

if (!($admin || $write))
	lcm_panic("You don't have permission to add follow-ups to this case");

if (isset($_SESSION['followup']) && (! $edit))
	lcm_panic("You do not have the permission to edit existing follow-ups");

//
// Change status/stage: check for if case status/stage is different than current
//

$statuses = get_possible_case_statuses();

if ($_REQUEST['submit'] == 'set_status') {
	// Get case status
	$result = lcm_query("SELECT status FROM lcm_case WHERE id_case = " . $case);
	$row = lcm_fetch_array($result);

	if ($statuses[$_REQUEST['type']] == $row['status'])
		header('Location: ' . $GLOBALS['HTTP_REFERER']);
}

if ($_REQUEST['submit'] == 'set_stage') {
	// Get case stage
	$result = lcm_query("SELECT stage FROM lcm_case WHERE id_case = " . $case);
	$row = lcm_fetch_array($result);
	$old_stage = $row['stage'];

	if ($statuses[$_REQUEST['stage']] == $row['stage'])
		header('Location: ' . $GLOBALS['HTTP_REFERER']);
}

//
// Decide whether to show 'conclusion' fields
//
$show_conclusion = false;

if ($_REQUEST['submit'] == 'set_status' || $_REQUEST['submit'] == 'set_stage') {
	$show_conclusion = true;
} elseif ($_SESSION['fu_data']['type'] == 'stage_change' || is_status_change($_SESSION['fu_data']['type'])) {
	$show_conclusion = true;
}

//
// Start page
//
if (isset($_SESSION['followup']))
	lcm_page_start(_T('title_fu_edit'), '', '', 'cases_followups');
else {
	if (isset($_REQUEST['type'])) {
		if ($_REQUEST['type'] == 'stage_change')
			lcm_page_start(_T('title_fu_change_stage'), '', '', 'cases_intro#stage');
		else
			lcm_page_start(_T('title_fu_change_status'), '', '', 'cases_intro#status');
	} else {
		lcm_page_start(_T('title_fu_new'), '', '', 'cases_followups');
	}
}

show_context_start();
show_context_case_title($case, 'followups');
show_context_case_involving($case);

// For 'change status' // FIXME (for edit existing fu?)
if ($_REQUEST['submit'] == 'set_status')
	show_context_item(_Ti('fu_input_current_status') . _T('case_status_option_' . $row['status']));

// For 'change stage'
if (isset($old_stage) && $old_stage)
	show_context_item(_Ti('fu_input_current_stage') . _Tkw('stage', $old_stage));

// Show stage information [ML] Not very efficient, I know, but I prefer to avoid spagetti
if ($_SESSION['fu_data']['case_stage']) {
	// if editing an existing followup..
	$stage_info = get_kw_from_name('stage', $_SESSION['fu_data']['case_stage']);
	$id_stage = $stage_info['id_keyword'];
	show_context_stage($case, $id_stage);
} elseif (isset($old_stage) && $old_stage) {
	// setting new stage
	$stage_info = get_kw_from_name('stage', $old_stage);
	$id_stage = $stage_info['id_keyword'];
	show_context_stage($case, $id_stage);
} else {
	// Normal follow-up
	$result = lcm_query("SELECT stage FROM lcm_case WHERE id_case = " . $case);
	$row = lcm_fetch_array($result);

	if ($row['stage']) {
		$stage_info = get_kw_from_name('stage', $row['stage']);
		$id_stage = $stage_info['id_keyword'];
		show_context_stage($case, $id_stage);
	}
}

show_context_end();

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

// Disable inputs when edit is not allowed for the field
$dis = (($admin || $edit) ? '' : 'disabled="disabled"');
?>

<form action="upd_fu.php" method="post">
	<table class="tbl_usr_dtl" width="99%">
		<tr><td><?php echo f_err_star('date_start') . _T('fu_input_date_start'); ?></td>
			<td><?php 
				$name = (($admin || $edit) ? 'start' : '');
				echo get_date_inputs($name, $_SESSION['fu_data']['date_start'], false);
				echo ' ' . _T('time_input_time_at') . ' ';
				echo get_time_inputs($name, $_SESSION['fu_data']['date_start']);
				?>
			</td>
		</tr>
		<tr><td><?php echo f_err_star('date_end') . (($prefs['time_intervals'] == 'absolute') ? _T('fu_input_date_end') : _T('fu_input_time_length')); ?></td>
			<td><?php 
				if ($prefs['time_intervals'] == 'absolute') {
					// Buggy code, so isolated most important cases
					if ($_SESSION['fu_data']['id_followup'] == 0)
						$name = 'end';
					elseif ($edit)
						$name = 'end';
					else
						// user can 'finish' entering data
						$name = (($admin || ($edit && ($_SESSION['fu_data']['date_end']=='0000-00-00 00:00:00'))) ? 'end' : '');

					echo get_date_inputs($name, $_SESSION['fu_data']['date_end']);
					echo ' ';
					echo _T('time_input_time_at') . ' ';
					echo get_time_inputs($name, $_SESSION['fu_data']['date_end']);
				} else {
					$name = '';

					// Buggy code, so isolated most important cases
					if ($_SESSION['fu_data']['id_followup'] == 0)
						$name = 'delta';
					elseif ($edit)
						$name = 'delta';
					else
						// user can 'finish' entering data
						$name = (($admin || ($edit && ($_SESSION['fu_data']['date_end']=='0000-00-00 00:00:00'))) ? 'delta' : '');

					$interval = ( ($_SESSION['fu_data']['date_end']!='0000-00-00 00:00:00') ?
							strtotime($_SESSION['fu_data']['date_end']) - strtotime($_SESSION['fu_data']['date_start']) : 0);
					echo get_time_interval_inputs($name, $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
				} ?>
			</td>
		</tr>
<?php

	// Show 'conclusion' options
	if ($show_conclusion) {
		$kws_conclusion = get_keywords_in_group_name('conclusion');

		echo "<tr>\n";
		echo "<td>" . _Ti('fu_input_conclusion') . "</td>\n";
		echo '<td>';
		echo '<select ' . $dis . ' name="conclusion" size="1" class="sel_frm">' . "\n";

		$default = '';
		if ($_SESSION['fu_data']['conclusion'])
			$default = $_SESSION['fu_data']['conclusion'];

		foreach ($kws_conclusion as $kw) {
			$sel = ($kw['name'] == $default ? ' selected="selected"' : '');
			echo '<option ' . $sel . ' value="' . $kw['name'] . '">' . _T(remove_number_prefix($kw['title'])) . "</option>\n";
		}

		echo "</select>\n";
		echo "</td>\n";
		echo "</tr>\n";

		// If guilty, what sentence?
		$kws_sentence = get_keywords_in_group_name('sentence');

		echo "<tr>\n";
		echo "<td>" . _Ti('fu_input_sentence') . "</td>\n";
		echo '<td>';
		echo '<select ' . $dis . ' name="sentence" size="1" class="sel_frm">' . "\n"; 

		$default = '';
		if ($_SESSION['fu_data']['sentence'])
			$default = $_SESSION['fu_data']['sentence'];

		echo "<!-- " . $default . " -->\n";

		foreach ($kws_sentence as $kw) {
			$sel = ($kw['name'] == $default ? ' selected="selected"' : '');
			echo '<option ' . $sel . ' value="' . $kw['name'] . '">' . _T(remove_number_prefix($kw['title'])) . "</option>\n";
		}

		echo "</select>\n";

		// If sentence, for how much?
		echo '<input type="text" name="sentence_val" size="10" value="' . $_SESSION['fu_data']['sentence_val'] . '" />';
		echo "</td>\n";
		echo "</tr>\n";
	}


			if ($_REQUEST['submit'] == 'set_status' || is_status_change($_SESSION['fu_data']['type'])) {
				// Change status
				echo "<tr>\n";
				echo "<td>" . _T('case_input_status') . "</td>\n";
				echo "<td>";

				echo '<input type="hidden" name="type" value="' . $_SESSION['fu_data']['type'] . '" />' . "\n";
				echo _T('kw_followups_' . $_SESSION['fu_data']['type'] . '_title');

				echo "</td>\n";
				echo "</tr>\n";
			} elseif ($_REQUEST['submit'] == 'set_stage' || $_SESSION['fu_data']['type'] == 'stage_change') {
				// Change stage
				echo "<tr>\n";
				echo "<td>" . _T('fu_input_next_stage') . "</td>\n";
				echo "<td>";

				echo '<input type="hidden" name="type" value="' . $_SESSION['fu_data']['type'] . '" />' . "\n";

				// This is to compensate an old bug, when 'case stage' was not stored in fu.description
				// and therefore editing a follow-up would not give correct information.
				// Bug was in CVS of 0.4.3 between 19-20 April 2005. Should not affect many people.
				if (isset($new_stage)) {
					echo '<input type="hidden" name="new_stage" value="' .  $new_stage . '" />' . "\n";
					echo _Tkw('stage', $new_stage);
				} else {
					echo "New stage information not available";
				}

				echo "</td>\n";
				echo "</tr>\n";

				if (isset($new_stage)) {
					// Update stage keywords (if any)
					$stage = get_kw_from_name('stage', $new_stage); // $_SESSION['fu_data']['case_stage']);
					$id_stage = $stage['id_keyword'];
					show_edit_keywords_form('stage', $_SESSION['fu_data']['id_case'], $id_stage);
				}
			} elseif ($_SESSION['fu_data']['type'] == 'assignment' || $_SESSION['fu_data']['type'] == 'unassignment') {
				// Do not allow assignment/un-assignment follow-ups to be changed
				echo "<tr>\n";
				echo "<td>" . _T('fu_input_next_stage') . "</td>\n";
				echo "<td>";

				echo '<input type="hidden" name="type" value="' . $_SESSION['fu_data']['type'] . '" />' . "\n";
				echo _Tkw('followups', $_SESSION['fu_data']['type']);

				echo "</td>\n";
				echo "</tr>\n";
			} else {
				// The usual follow-up
				echo "<tr>\n";
				echo "<td>" . _T('fu_input_type') . "</td>\n";
				echo "<td>";
				echo '<select ' . $dis . ' name="type" size="1" class="sel_frm">' . "\n";

				if ($_SESSION['fu_data']['type'])
					$default_fu = $_SESSION['fu_data']['type'];
				else
					$default_fu = $system_kwg['followups']['suggest'];

				$futype_kws = get_keywords_in_group_name('followups');
				$kw_found = false;

				foreach($futype_kws as $kw) {
					$sel = ($kw['name'] == $default_fu ? ' selected="selected"' : '');
					if ($sel) $kw_found = true;
					echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T(remove_number_prefix($kw['title'])) . "</option>\n";
				}

				// Exotic case where the FU keyword was hidden by the administrator,
				// but an old follow-up using that keyword is being edited.
				if (! $kw_found)
					echo '<option selected="selected" value="' . $default_fu . '">' . _Tkw('followups', $default_fu) . "</option>\n";

				echo "</select>\n";
				echo "</td>\n";
				echo "</tr>\n";
			}

		// Description
		echo "<tr>\n";
		echo '<td valign="top">' . f_err_star('description') . _T('fu_input_description') . "</td>\n";
		echo '<td>';

		if ($_SESSION['fu_data']['type'] == 'assignment' || $_SESSION['fu_data']['type'] == 'unassignment') {
			// Do not allow edit of assignment
			echo '<input type="hidden" name="description" value="' . $_SESSION['fu_data']['description'] . '" />' . "\n";
			echo get_fu_description($_SESSION['fu_data']);
		} else {
			echo '<textarea ' . $dis . ' name="description" rows="15" cols="60" class="frm_tarea">';
			echo clean_output($_SESSION['fu_data']['description']);
			echo "</textarea>";
		}

		echo "</td></tr>\n";


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
		
		echo "</table>\n\n";

		// Add followup appointment
		if (!isset($_GET['followup'])) {
			echo "<!-- Add appointment? -->\n";
			echo '<p class="normal_text">';
			echo '<input type="checkbox" name="add_appointment" id="box_new_app" onclick="display_block(\'new_app\', \'flip\')"; />';
			echo '<label for="box_new_app">' . _T('fu_info_add_future_activity') . '</label>';
			echo "</p>\n";

			echo '<div id="new_app" style="display: none;">';
			echo '<table class="tbl_usr_dtl" width="99%">' . "\n";
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
				echo " " . _T('time_info_before_start');
				echo f_err_star('app_reminder',$_SESSION['errors']);
			}
			echo "</td></tr>\n";

			echo "<!-- Appointment title -->\n\t\t<tr><td>";
			echo f_err_star('app_title') . _T('app_input_title');
			echo "</td><td>";
			echo '<input type="text" ' . $title_onfocus . $dis . ' name="app_title" size="50" value="';
			echo clean_output($_SESSION['fu_data']['app_title']) . '" class="search_form_txt" />';
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

			echo "<!-- Appointment description -->\n";
			echo "<tr><td valign=\"top\">";
			echo _T('app_input_description');
			echo "</td><td>";
			echo '<textarea ' . $dis . ' name="app_description" rows="5" cols="60" class="frm_tarea">';
			echo clean_output($_SESSION['fu_data']['app_description']);
			echo '</textarea>';
			echo "</td></tr>\n";
			echo "</table>\n";
			echo "</div>\n";
		}

		if (isset($_SESSION['followup'])) {
			// Allow case admin to hide the follow-up
			// TODO
		}

		echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";

		if (isset($_SESSION['followup'])) {
			if ($prefs['mode'] == 'extended')
				echo '<button name="reset" type="reset" class="simple_form_btn">' . _T('button_reset') . "</button>\n";
		} else {
			// More buttons for 'extended' mode
			if ($prefs['mode'] == 'extended') {
				echo '<button name="submit" type="submit" value="add" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
				echo '<button name="submit" type="submit" value="addnew" class="simple_form_btn">' . _T('add_and_open_new') . "</button>\n";
				echo '<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('add_and_go_to_details') . "</button>\n";
			}
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
