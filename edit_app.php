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

	$Id: edit_app.php,v 1.35 2005/04/05 13:19:16 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

$admin = ($GLOBALS['author_session']['status']=='admin');
$title_onfocus = '';

if (empty($_SESSION['errors'])) {
	// Clear form data
	$_SESSION['app_data'] = array('ref_edit_app' => ( $_GET['ref'] ? clean_input($_GET['ref']) : $GLOBALS['HTTP_REFERER']) );
	$_SESSION['authors'] = array();

	if ($_GET['app']>0) {
		$_SESSION['app_data']['id_app'] = intval($_GET['app']);

		// Fetch the details on the specified appointment
		$q="SELECT *
			FROM lcm_app
			WHERE id_app=" . $_SESSION['app_data']['id_app'];

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach($row as $key=>$value) {
				$_SESSION['app_data'][$key] = $value;
			}

			// Get appointment participants
			$q = "SELECT lcm_author.id_author,name_first,name_middle,name_last
				FROM lcm_author_app,lcm_author
				WHERE lcm_author_app.id_author=lcm_author.id_author
					AND id_app=" . $_SESSION['app_data']['id_app'];
			$result = lcm_query($q);

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
		// This is new appointment
		$_SESSION['app_data']['id_app'] = 0;
		
		// New appointment created from case
		if (!empty($_GET['case'])) {
			$_SESSION['app_data']['id_case'] = intval($_GET['case']);
		}

		// New appointment created from followup
		if (!empty($_GET['followup'])) {
			$_SESSION['app_data']['id_followup'] = intval($_GET['followup']);
			if (empty($_SESSION['app_data']['id_case'])) {
				$result = lcm_query("SELECT id_case FROM lcm_followup WHERE id_followup=" . $_SESSION['app_data']['id_followup']);
				if ($row = lcm_fetch_array($result))
					$_SESSION['app_data']['id_case'] = $row['id_case'];
			}
		}


		$modify = true;

		// Setup default values
		$_SESSION['app_data']['title'] = _T('title_app_new');
		if (!empty($_GET['time'])) {
			$time = rawurldecode($_GET['time']);
		} else {
			$time = date('Y-m-d H:i:s');
		}
		$_SESSION['app_data']['start_time'] = $time;
		$_SESSION['app_data']['end_time']   = $time;
		$_SESSION['app_data']['reminder']   = $time;

		// erases the "New appointment" when focuses (taken from Spip)
		$title_onfocus = " onfocus=\"if(!title_antifocus) { this.value = ''; title_antifocus = true;}\" "; 
		
		// Set author as appointment participants
		$q = "SELECT id_author,name_first,name_middle,name_last
			FROM lcm_author
			WHERE id_author=" . $GLOBALS['author_session']['id_author'];
		$result = lcm_query($q);

		while ($row = lcm_fetch_array($result))
			$_SESSION['authors'][$row['id_author']] = $row;

	}

} else if ( array_key_exists('author_added',$_SESSION['errors']) || array_key_exists('author_removed',$_SESSION['errors']) ) {
	// Refresh appointment participants
	$q = "SELECT lcm_author.id_author,name_first,name_middle,name_last
		FROM lcm_author_app,lcm_author
		WHERE lcm_author_app.id_author=lcm_author.id_author
			AND id_app=" . $_SESSION['app_data']['id_app'];
	$result = lcm_query($q);
	$_SESSION['authors'] = array();
	while ($row = lcm_fetch_array($result))
		$_SESSION['authors'][$row['id_author']] = $row;
}

if ($_SESSION['app_data']['id_app'] > 0)
	lcm_page_start(_T('title_app_edit'));
else
	lcm_page_start(_T('title_app_new'));

if ($_SESSION['app_data']['id_case'] > 0) {
	// Show a bit of background on the case
	show_context_start();
	show_context_case_title($_SESSION['app_data']['id_case']);
	show_context_case_involving($_SESSION['app_data']['id_case']);
	show_context_end();
}

// Show the errors (if any)
echo show_all_errors($_SESSION['errors']);

// Disable inputs when edit is not allowed for the field
$dis = (($admin || ($edit && $modify)) ? '' : 'disabled');


?>

<form action="upd_app.php" method="post">
	<table class="tbl_usr_dtl" width="99%">

		<!-- Start time -->
		<tr>
<?php

	echo "<td>" . f_err_star('start_time') . _T('app_input_date_start') . "</td>\n";
	echo "<td>";

	$name = (($admin || ($edit && $modify)) ? 'start' : '');
	echo get_date_inputs($name, $_SESSION['app_data']['start_time'], false);
	echo ' ' . _T('time_input_time_at') . ' ';
	echo get_time_inputs($name, $_SESSION['app_data']['start_time']);

	echo "</td>\n";

?>
		</tr>
		<!-- End time -->
		<tr>
<?php

	if ($prefs['time_intervals'] == 'absolute') {
		echo "<td>" . f_err_star('end_time') . _T('app_input_date_end') . "</td>\n";
		echo "<td>";

		$name = (($admin || ($edit && ($_SESSION['app_data']['end_time']=='0000-00-00 00:00:00'))) ? 'end' : '');
		echo get_date_inputs($name, $_SESSION['app_data']['end_time']);
		echo ' ';
		echo _T('time_input_time_at') . ' ';
		echo get_time_inputs($name, $_SESSION['app_data']['end_time']);

		echo "</td>\n";
	} else {
		echo "<td>" . f_err_star('end_time') . _T('app_input_time_length') . "</td>\n";
		echo "<td>";

		$name = (($admin || ($edit && ($_SESSION['app_data']['end_time']=='0000-00-00 00:00:00'))) ? 'delta' : '');
		$interval = ( ($_SESSION['app_data']['end_time']!='0000-00-00 00:00:00') ?
				strtotime($_SESSION['app_data']['end_time']) - strtotime($_SESSION['app_data']['start_time']) : 0);
		echo get_time_interval_inputs($name, $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));

		echo "</td>\n";
	}

?>

		</tr>

		<!-- Reminder -->
		<tr>
		
<?php
	
	if ($prefs['time_intervals'] == 'absolute') {
		echo "<td>" . f_err_star('reminder') . _T('app_input_reminder_time') . "</td>\n";
		echo "<td>";

		$name = (($admin || ($edit && ($_SESSION['app_data']['end_time']=='0000-00-00 00:00:00'))) ? 'reminder' : '');
		echo get_date_inputs($name, $_SESSION['app_data']['reminder']);
		echo ' ';
		echo _T('time_input_time_at') . ' ';
		echo get_time_inputs($name, $_SESSION['app_data']['reminder']);

		echo "</td>\n";
	} else {
		echo "<td>" . f_err_star('reminder') . _T('app_input_reminder_offset') . "</td>\n";
		echo "<td>";

		$name = (($admin || ($edit && ($_SESSION['app_data']['end_time']=='0000-00-00 00:00:00'))) ? 'rem_offset' : '');
		$interval = ( ($_SESSION['app_data']['end_time']!='0000-00-00 00:00:00') ?
				strtotime($_SESSION['app_data']['start_time']) - strtotime($_SESSION['app_data']['reminder']) : 0);
		echo get_time_interval_inputs($name, $interval, ($prefs['time_intervals_notation']=='hours_only'), ($prefs['time_intervals_notation']=='floatdays_hours_minutes'));
		echo " " . _T('time_info_before_start');
		echo f_err_star('reminder',$_SESSION['errors']);

		echo "</td>\n";
	}

?>

		</tr>

		<!-- Appointment title -->
		<tr><td valign="top"><?php echo f_err_star('title') . _T('app_input_title'); ?></td>
			<td><input type="text" <?php echo $title_onfocus . $dis; ?> name="title" size="50" value="<?php
			echo clean_output($_SESSION['app_data']['title']) . "\" /></td></tr>\n"; ?>

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

		<!-- Appointment description -->
		<tr><td valign="top"><?php echo _T('app_input_description'); ?></td>
			<td><textarea <?php echo $dis; ?> name="description" rows="5" cols="40" class="frm_tarea"><?php
			echo clean_output($_SESSION['app_data']['description']) . "</textarea></td></tr>\n";

		// Appointment participants - authors
		echo "\t\t<tr><td valign=\"top\">";
		echo _T('app_input_authors');
		echo "</td><td>";
		if (count($_SESSION['authors'])>0) {
			$q = '';
			$author_ids = array();
			foreach($_SESSION['authors'] as $author) {
				// $q .= ($q ? ', ' : '');
				$author_ids[] = $author['id_author'];
				$q .= get_person_name($author);

				if ($author['id_author'] != $author_session['id_author'])
					$q .= '&nbsp;(<label for="id_rem_author' . $author['id_author'] . '"><img src="images/jimmac/stock_trash-16.png" width="16" height="16" alt="Remove?" title="Remove?" /></label>&nbsp;<input type="checkbox" id="id_rem_author' . $author['id_author'] . '" name="rem_author[]" value="' . $author['id_author'] . '" />)'; // TRAD

				$q .= "<br />\n";

			}
			echo "\t\t\t$q\n";
		}
		// List rest of the authors to add
/*		$q = "SELECT lcm_author.id_author,lcm_author.name_first,lcm_author.name_middle,lcm_author.name_last
			FROM lcm_author
			LEFT JOIN lcm_author_app
			ON (lcm_author.id_author=lcm_author_app.id_author AND id_app=" . $_SESSION['app_data']['id_app'] . ")
			WHERE id_app IS NULL";
*/
		
		$q = "SELECT id_author,name_first,name_middle,name_last
			FROM lcm_author
			WHERE id_author NOT IN (" . join(',',$author_ids) . ")";
		$result = lcm_query($q);
		echo "\t\t\t<select name=\"author\">\n";
		echo "\t\t\t\t<option selected='selected' value=\"0\">- Select author -</option>\n"; // TRAD
		while ($row = lcm_fetch_array($result)) {
			echo "\t\t\t\t<option value=\"" . $row['id_author'] . '">'
				. get_person_name($row)
				. "</option>\n";
		}
		echo "\t\t\t</select>\n";
		echo "\t\t\t<button name=\"submit\" type=\"submit\" value=\"add_author\" class=\"simple_form_btn\">" . 'Add' . "</button>\n"; // TRAD
		echo "\t\t</td></tr>\n";
		
		// Appointment participants - clients
		echo "\t\t<tr><td valign=\"top\">";
		echo _T('app_input_clients');
		echo "</td><td>";
		$q = "SELECT lcm_client.id_client,lcm_client.name_first,lcm_client.name_middle,lcm_client.name_last,lcm_org.id_org,lcm_org.name
			FROM lcm_client,lcm_app_client_org
			LEFT JOIN lcm_org USING (id_org)
			WHERE id_app=" . $_SESSION['app_data']['id_app'] . "
				AND lcm_client.id_client=lcm_app_client_org.id_client";
		$result = lcm_query($q);
		$q = '';
		while ($row = lcm_fetch_array($result)) {
			// $q .= ($q ? ', ' : '');
			$q .= get_person_name($row) . ( ($row['name']) ? " of " . $row['name'] : ''); // TRAD
			$q .= '&nbsp;(<label for="id_rem_client' . $row['id_client'] . ':' . $row['id_org'] . '">';
			$q .= '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" alt="Remove?" title="Remove?" /></label>&nbsp;';
			$q .= '<input type="checkbox" id="id_rem_client' . $row['id_client'] . ':' . $row['id_org'] . '" name="rem_client[]" value="' . $row['id_client'] . ':' . $row['id_org'] . '"/>)<br />';	// TRAD
		}
		echo "\t\t\t$q\n";
		
		// List rest of the clients to add
		$q = "SELECT c.id_client,c.name_first,c.name_last,co.id_org,o.name
			FROM lcm_client AS c
			LEFT JOIN lcm_client_org AS co USING (id_client)
			LEFT JOIN lcm_org AS o ON (co.id_org=o.id_org)
			LEFT JOIN lcm_app_client_org AS aco ON (aco.id_client=c.id_client AND aco.id_app=" . $_SESSION['app_data']['id_app'] . ")
			WHERE id_app IS NULL";
		
		$result = lcm_query($q);
		echo "\t\t\t<select name=\"client\">\n";
		echo "\t\t\t\t<option selected='selected' value=\"0\">- Select client -</option>\n"; // TRAD
		while ($row = lcm_fetch_array($result)) {
			echo "\t\t\t\t<option value=\"" . $row['id_client'] . ':' . $row['id_org'] . '">'
				. get_person_name($row)
				. ($row['name'] ? ' of ' . $row['name'] : '') // TRAD
				. "</option>\n";
		}
		echo "\t\t\t</select>\n";
		echo "\t\t\t<button name=\"submit\" type=\"submit\" value=\"add_client\" class=\"simple_form_btn\">" . 'Add' . "</button>\n"; // TRAD
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

		echo '<input type="hidden" name="id_app" value="' . $_SESSION['app_data']['id_app'] . '" />' . "\n";
		echo '<input type="hidden" name="id_case" value="' . $_SESSION['app_data']['id_case'] . '" />' . "\n";
		echo '<input type="hidden" name="id_followup" value="' . $_SESSION['app_data']['id_followup'] . '" />' . "\n";

		// because of XHTML validation...
		$ref_link = new Link($_SESSION['app_data']['ref_edit_app']);
		
		echo '<input type="hidden" name="ref_edit_app" value="' . $ref_link->getUrl() . '" />' . "\n";

	?>

</form>

<?php

lcm_page_end();

// Clear the errors, in case user jumps to other 'edit' page
$_SESSION['errors'] = array();

?>
