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

	$Id: edit_app.php,v 1.12 2005/03/02 15:39:19 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

$admin = ($GLOBALS['author_session']['status']=='admin');

if (empty($_SESSION['errors'])) {
	// Clear form data
	$_SESSION['app_data'] = array('ref_edit_app' => ( $_GET['ref'] ? clean_input($_GET['ref']) : $GLOBALS['HTTP_REFERER']) );

	if ($_GET['app']>0) {
		$app = intval($_GET['app']);

		// Fetch the details on the specified appointment
		$q="SELECT *
			FROM lcm_app
			WHERE id_app=$app";

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach($row as $key=>$value) {
				$_SESSION['app_data'][$key] = $value;
			}

			// Get appointment participants
			$q = "SELECT lcm_author.id_author,name_first,name_middle,name_last
				FROM lcm_author_app,lcm_author
				WHERE lcm_author_app.id_author=lcm_author.id_author
					AND id_app=$app";
			$result = lcm_query($q);
			$_SESSION['authors'] = array();
			while ($row = lcm_fetch_array($result))
				$_SESSION['authors'][$row['id_author']] = $row;

			// Check the access rights
			if (! ($admin || isset($_SESSION['authors'][ $GLOBALS['author_session']['id_author'] ])))
				die("You are not involved in this appointment!");
				
		} else die("There's no such appointment!");

		// Set the case ID, to which this appointment belongs
		//$case = $_SESSION['app_data']['id_case'];
		$modify = ($_SESSION['app_data']['id_author'] == $GLOBALS['author_session']['id_author']);
	} else {
		unset($app);
		if ($_GET['case'] > 0) {
			$_SESSION['app_data']['id_case'] = intval($_GET['case']);
		}
		$modify = true;

		// Setup default values
		$_SESSION['app_data']['start_time'] = date('Y-m-d H:i:s');
		$_SESSION['app_data']['end_time']   = date('Y-m-d H:i:s');
		$_SESSION['app_data']['reminder']   = date('Y-m-d H:i:s');
	}

} else if ( array_key_exists('author_added',$_SESSION['errors']) ) {
	// Refresh appointment participants
	$q = "SELECT lcm_author.id_author,name_first,name_middle,name_last
		FROM lcm_author_app,lcm_author
		WHERE lcm_author_app.id_author=lcm_author.id_author
			AND id_app=$app";
	$result = lcm_query($q);
	$_SESSION['authors'] = array();
	while ($row = lcm_fetch_array($result))
		$_SESSION['authors'][$row['id_author']] = $row;
}

if ($_SESSION['app_data']['id_app']>0)
	lcm_page_start("Edit appointment");
else
	lcm_page_start("New appointment");

if ($_SESSION['app_data']['id_case']>0) {
	// Show a bit of background on the case
	echo "<ul style=\"padding-left: 0.5em; padding-top: 0.2; padding-bottom: 0.2; font-size: 12px;\">\n";

	// Name of case
	$query = "SELECT title
			FROM lcm_case
			WHERE id_case=" . $_SESSION['app_data']['id_case'];

	$result = lcm_query($query);
	while ($row = lcm_fetch_array($result))  // should be only once
		echo '<li style="list-style-type: none;">' . _T('info_appointment_to_case') . " " . $row['title'] . "</li>\n";

	// We dump all the clients and org in the same array, then show
	// them on screen in a more densed way
	// Could be more esthetic or ergonomic, but works for now..
	$query = "SELECT cl.id_client, name_first, name_middle, name_last
				FROM lcm_case_client_org as cco, lcm_client as cl
				WHERE cco.id_case=" . $_SESSION['app_data']['id_case'] . "
				AND cco.id_client = cl.id_client";
	
	$result = lcm_query($query);
	$numrows = lcm_num_rows($result);
	$current = 0;
	
	$all_clients = array();
	
	while ($all_clients[] = lcm_fetch_array($result));
	
	$query = "SELECT org.name, cco.id_client, org.id_org
				FROM lcm_case_client_org as cco, lcm_org as org
				WHERE cco.id_case=" . $_SESSION['app_data']['id_case'] . "
				AND cco.id_org = org.id_org";
	
	$result = lcm_query($query);
	$numrows += lcm_num_rows($result);
	
	// TODO: It would be nice to have the name of the contact for that
	// organisation, if any, but then again, not the end of the world.
	// (altough I we make a library of common functions, it will defenitely
	// be a good thing to have)
	while ($all_clients[] = lcm_fetch_array($result));
	
	if ($numrows > 0)
		echo '<li style="list-style-type: none;">' . _T('info_appointment_involving') . " ";
	
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
}

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

// Disable inputs when edit is not allowed for the field
$dis = (($admin || ($edit && $modify)) ? '' : 'disabled');
?>

<form action="upd_app.php" method="POST">
	<table class="tbl_usr_dtl" width="99%">

		<!-- Start time -->
		<tr><td><?php echo _T('app_input_date_start'); ?></td>
			<td><?php echo _T('calendar_info_date') . ' ';  
				$name = (($admin || ($edit && $modify)) ? 'start' : '');
				echo get_date_inputs($name, $_SESSION['app_data']['start_time'], false);
				echo ' ' . _T('calendar_info_time') . ' ';
				echo get_time_inputs($name, $_SESSION['app_data']['start_time']);
				echo f_err_star('start_time',$_SESSION['errors']); ?>
			</td>
		</tr>

		<!-- End time -->
		<tr><td><?php echo (($prefs['time_intervals'] == 'absolute') ? _T('app_input_end_time') : _T('app_input_time_length')); ?></td>
			<td><?php 
				if ($prefs['time_intervals'] == 'absolute') {
					$name = (($admin || ($edit && ($_SESSION['app_data']['end_time']=='0000-00-00 00:00:00'))) ? 'end' : '');
					echo _T('calendar_info_date') . ' '; 
					echo get_date_inputs($name, $_SESSION['app_data']['end_time']);
					echo ' ';
					echo _T('calendar_info_time') . ' ';
					echo get_time_inputs($name, $_SESSION['app_data']['end_time']);
					echo f_err_star('end_time',$_SESSION['errors']);
				} else {
					$name = (($admin || ($edit && ($_SESSION['app_data']['end_time']=='0000-00-00 00:00:00'))) ? 'delta' : '');
					$interval = ( ($_SESSION['app_data']['end_time']!='0000-00-00 00:00:00') ?
							strtotime($_SESSION['app_data']['end_time']) - strtotime($_SESSION['app_data']['start_time']) : 0);
				//	echo _T('calendar_info_time') . ' ';
					echo get_time_interval_inputs($name, $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
					echo f_err_star('end_time',$_SESSION['errors']);
				} ?>
			</td>
		</tr>

		<!-- Reminder -->
		<tr><td><?php echo (($prefs['time_intervals'] == 'absolute') ? _T('app_input_reminder_time') : _T('app_input_reminder_offset')); ?></td>
			<td><?php 
				if ($prefs['time_intervals'] == 'absolute') {
					$name = (($admin || ($edit && ($_SESSION['app_data']['end_time']=='0000-00-00 00:00:00'))) ? 'reminder' : '');
					echo _T('calendar_info_date') . ' '; 
					echo get_date_inputs($name, $_SESSION['app_data']['end_time']);
					echo ' ';
					echo _T('calendar_info_time') . ' ';
					echo get_time_inputs($name, $_SESSION['app_data']['end_time']);
					echo f_err_star('end_time',$_SESSION['errors']);
				} else {
					$name = (($admin || ($edit && ($_SESSION['app_data']['end_time']=='0000-00-00 00:00:00'))) ? 'rem_offset' : '');
					$interval = ( ($_SESSION['app_data']['end_time']!='0000-00-00 00:00:00') ?
							strtotime($_SESSION['app_data']['end_time']) - strtotime($_SESSION['app_data']['start_time']) : 0);
				//	echo _T('calendar_info_time') . ' ';
					echo get_time_interval_inputs($name, $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
					echo " before the start time";
					echo f_err_star('end_time',$_SESSION['errors']);
				} ?>
			</td>
		</tr>

		<!-- Appointment type -->
		<tr><td><?php echo _T('app_input_type'); ?></td>
			<td><select <?php echo $dis; ?> name="type" size="1" class="sel_frm">
			<?php

			global $system_kwg;

			if ($_SESSION['app_data']['type'])
				$default_app = $_SESSION['app_data']['type'];
			else
				$default_app = $system_kwg['appointments']['suggest'];

			foreach($system_kwg['appointments']['keywords'] as $kw) {
				$sel = ($kw['name'] == $default_app ? ' selected="selected"' : '');
				echo "<option value='" . $kw['name'] . "'" . "$sel>" . _T($kw['title']) . "</option>\n";
			}

			?>
			</select></td></tr>

		<!-- Appointment title -->
		<tr><td valign="top"><?php echo _T('app_input_title'); ?></td>
			<td><input <?php echo $dis; ?> name="title" size="40" value="<?php
			echo clean_output($_SESSION['app_data']['title']) . "\" /></td></tr>\n"; ?>

		<!-- Appointment description -->
		<tr><td valign="top"><?php echo _T('app_input_description'); ?></td>
			<td><textarea <?php echo $dis; ?> name="description" rows="15" cols="40" class="frm_tarea"><?php
			echo clean_output($_SESSION['app_data']['description']) . "</textarea></td></tr>\n";

		// Appointment participants - authors
		echo "\t\t<tr><td valign=\"top\">";
		echo _T('app_input_authors');
		echo "</td><td>";
		$q = '';
		foreach($_SESSION['authors'] as $author) {
			$q .= ($q ? ', ' : '');
			$q .= njoin(array($author['name_first'],$author['name_middle'],$author['name_last']));
		}
		echo "\t\t\t$q\n";
		// List rest of the authors to add
		$q = "SELECT lcm_author.id_author,lcm_author.name_first,lcm_author.name_middle,lcm_author.name_last
			FROM lcm_author
			LEFT JOIN lcm_author_app
			USING (id_author)
			WHERE id_app IS NULL";
		$result = lcm_query($q);
//		echo "\t\t<form action=\"" . $_SERVER['REQUEST_URI'] . "\" method=\"POST\">\n";
		echo "\t\t\t<select name=\"author\">\n";
		echo "\t\t\t\t<option selected value=\"0\">- Select author -</option>\n";
		while ($row = lcm_fetch_array($result)) {
			echo "\t\t\t\t<option value=\"" . $row['id_author'] . '">'
				. njoin(array($row['name_first'],$row['name_middle'],$row['name_last']))
				. "</option>\n";
		}
		echo "\t\t\t</select>\n";
		echo "\t\t\t<button name=\"submit\" type=\"submit\" value=\"add_author\" class=\"simple_form_btn\">" . 'Add' . "</button>\n";
//		echo "\t\t</form>\n";
		echo "\t\t</td></tr>\n";
		
		// Appointment participants - clients
		echo "\t\t<tr><td valign=\"top\">";
		echo _T('app_input_clients');
		echo "</td><td>";
		$q = "SELECT lcm_client.name_first,lcm_client.name_middle,lcm_client.name_last,lcm_org.name
			FROM lcm_client,lcm_app_client_org
			LEFT JOIN lcm_org USING (id_org)
			WHERE id_app=" . ( $_SESSION['app_data']['id_app'] ? $_SESSION['app_data']['id_app'] : 0 ) . "
				AND lcm_client.id_client=lcm_app_client_org.id_client";
		$result = lcm_query($q);
		$q = '';
		while ($row = lcm_fetch_array($result)) {
			$q .= ($q ? ', ' : '');
			$q .= njoin(array($row['name_first'],$row['name_middle'],$row['name_last']))
				. ( ($row['name']) ? " of " . $row['name'] : '');
		}
		echo "\t\t\t$q\n";
		// List rest of the clients to add
		$q = "SELECT c.id_client,c.name_first,c.name_last,co.id_org,o.name
			FROM lcm_client AS c
			LEFT JOIN lcm_client_org AS co USING (id_client)
			LEFT JOIN lcm_org AS o ON (co.id_org=o.id_org)
			LEFT JOIN lcm_app_client_org AS aco ON (aco.id_client=c.id_client AND aco.id_app=";
		$q .= ( $_SESSION['app_data']['id_app'] ? $_SESSION['app_data']['id_app'] : 0 ) . ")
			WHERE id_app IS NULL";
		
		$result = lcm_query($q);
		echo "\t\t\t<select name=\"client\">\n";
		echo "\t\t\t\t<option selected value=\"0\">- Select client -</option>\n";
		while ($row = lcm_fetch_array($result)) {
			echo "\t\t\t\t<option value=\"" . $row['id_client'] . ':' . $row['id_org'] . '">'
				. njoin(array($row['name_first'],$row['name_middle'],$row['name_last']))
				. ($row['name'] ? ' of ' . $row['name'] : '')
				. "</option>\n";
		}
		echo "\t\t\t</select>\n";
		echo "\t\t\t<button name=\"submit\" type=\"submit\" value=\"add_client\" class=\"simple_form_btn\">" . 'Add' . "</button>\n";
		echo "\t\t</td></tr>\n";

		echo "	</table>\n";

		// Form buttons
		if ($_SESSION['app_data']['id_app']>0) {
			// When editing appointment
			echo '	<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
			if ($prefs['mode'] == 'extended')
				echo '<button name="reset" type="reset" class="simple_form_btn">' . _T('button_reset') . "</button>\n";
		} else {
			// When adding appointment(s)
			if ($prefs['mode'] == 'extended') {
				// More buttons for 'extended' mode
				echo '<button name="submit" type="submit" value="add" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
				echo '<button name="submit" type="submit" value="addnew" class="simple_form_btn">' . _T('add_and_open_new') . "</button>\n";
				echo '<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('add_and_go_to_details') . "</button>\n"; }
			else	// Less buttons in simple mode
				echo '<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
		}
	?>

	<input type="hidden" name="id_app" value="<?php echo $app; ?>">
	<input type="hidden" name="id_case" value="<?php echo $_SESSION['app_data']['id_case']; ?>">
	<input type="hidden" name="ref_edit_app" value="<?php echo $_SESSION['app_data']['ref_edit_app']; ?>">
</form>

<?php
	lcm_page_end();
?>
