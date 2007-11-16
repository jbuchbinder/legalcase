<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2007 Free Software Foundation, Inc.

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

	$Id: edit_app.php,v 1.52 2007/11/16 16:29:08 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

$admin = ($GLOBALS['author_session']['status']=='admin');
$title_onfocus = '';

$ac = get_ac_app($_GET['app']);

if (! $ac['w'])
	die("access denied");

if (empty($_SESSION['errors'])) {
	// Clear form data
	$_SESSION['form_data'] = array('ref_edit_app' => ( _request('ref') ? _request('ref') : $_SERVER['HTTP_REFERER']) );
	$_SESSION['authors'] = array();

	if ($_GET['app']>0) {
		$_SESSION['form_data']['id_app'] = intval(_request('app'));

		// Fetch the details on the specified appointment
		$q="SELECT *
			FROM lcm_app
			WHERE id_app=" . _session('id_app');

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {
			foreach($row as $key=>$value) {
				$_SESSION['form_data'][$key] = $value;
			}

			// Get appointment participants
			$q = "SELECT au.id_author, au.name_first, au.name_middle, au.name_last
				FROM lcm_author_app as ap, lcm_author au
				WHERE ap.id_author = au.id_author
					AND id_app=" . _session('id_app') . "
				ORDER BY au.name_first, au.name_middle, au.name_last";
			$result = lcm_query($q);

			while ($row = lcm_fetch_array($result))
				$_SESSION['authors'][$row['id_author']] = $row;

			// Check the access rights
			if (! ($admin || isset($_SESSION['authors'][ $GLOBALS['author_session']['id_author'] ])))
				die("You are not involved in this appointment!");
				
		} else die("There's no such appointment!");

	} else {
		// This is new appointment
		$_SESSION['form_data']['id_app'] = 0;
		
		// New appointment created from case
		if (!empty($_GET['case']))
			$_SESSION['form_data']['id_case'] = intval(_request('case'));

		// New appointment created from followup
		if (($id_followup = intval(_request('followup')))) { 
			$_SESSION['form_data']['id_followup'] = $id_followup;

			if (! _session('id_case')) {
				$result = lcm_query("SELECT id_case FROM lcm_followup WHERE id_followup = $id_followup");

				if ($row = lcm_fetch_array($result))
					$_SESSION['form_data']['id_case'] = $row['id_case'];
			}
		}

		// Setup default values
		$_SESSION['form_data']['title'] = _T('title_app_new');

		if (_request('time')) {
			$time = rawurldecode(_request('time'));
		} else {
			$time = date('Y-m-d H:i:s');
		}

		$_SESSION['form_data']['start_time'] = $time;
		$_SESSION['form_data']['end_time']   = $time;
		$_SESSION['form_data']['reminder']   = $time;

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
			AND id_app=" . $_SESSION['form_data']['id_app'] . "
		ORDER BY name_first,name_middle,name_last";
	$result = lcm_query($q);
	$_SESSION['authors'] = array();
	while ($row = lcm_fetch_array($result))
		$_SESSION['authors'][$row['id_author']] = $row;
}

// [ML] not clean hack, fix "delete" option
if (! empty($_SESSION['errors'])) {
	if ($_SESSION['form_data']['hidden'])
		$_SESSION['form_data']['hidden'] = 'Y';
}

if (_session('id_app', 0) > 0)
	lcm_page_start(_T('title_app_edit'), '', '', 'tools_agenda');
else
	lcm_page_start(_T('title_app_new'), '', '', 'tools_agenda');

if (_session('id_case', 0) > 0) {
	// Show a bit of background on the case
	show_context_start();
	show_context_case_title(_session('id_case'));
	show_context_case_involving(_session('id_case'));
	show_context_end();
}

// Show the errors (if any)
echo show_all_errors();

// Disable inputs when edit is not allowed for the field
$ac = get_ac_app($app, _session('id_case'));

$admin = $ac['a'];
$write = $ac['w'];
$edit  = $ac['e'];

$dis = ($edit ? '' : 'disabled="disabled"');

?>

<form action="upd_app.php" method="post">
	<table class="tbl_usr_dtl" width="99%">

		<!-- Start time -->
		<tr>
<?php

	echo "<td>" . f_err_star('start_time') . _T('time_input_date_start') . "</td>\n";
	echo "<td>";

	$name = ($edit ? 'start' : '');
	echo get_date_inputs($name, _session('start_time'), false);
	echo ' ' . _T('time_input_time_at') . ' ';
	echo get_time_inputs($name, _session('start_time'));

	echo "</td>\n";

?>
		</tr>
		<!-- End time -->
		<tr>
<?php

	if ($prefs['time_intervals'] == 'absolute') {
		echo "<td>" . f_err_star('end_time') . _T('time_input_date_end') . "</td>\n";
		echo "<td>";

		$name = (($admin || ($edit && ($_SESSION['form_data']['end_time']=='0000-00-00 00:00:00'))) ? 'end' : '');
		echo get_date_inputs($name, $_SESSION['form_data']['end_time']);
		echo ' ';
		echo _T('time_input_time_at') . ' ';
		echo get_time_inputs($name, $_SESSION['form_data']['end_time']);

		echo "</td>\n";
	} else {
		echo "<td>" . f_err_star('end_time') . _T('app_input_time_length') . "</td>\n";
		echo "<td>";

		$name = (($admin || ($edit && ($_SESSION['form_data']['end_time']=='0000-00-00 00:00:00'))) ? 'delta' : '');
		$interval = ( ($_SESSION['form_data']['end_time']!='0000-00-00 00:00:00') ?
				strtotime($_SESSION['form_data']['end_time']) - strtotime($_SESSION['form_data']['start_time']) : 0);
		echo get_time_interval_inputs($name, $interval);

		echo "</td>\n";
	}

?>

		</tr>

		<!-- Reminder -->
		
<?php
	/*
	[ML] Removing this because it's rather confusing + little gain in usability.
	Might be good in the future if we send e-mail reminders, for example.

	echo "<tr>\n";

	if ($prefs['time_intervals'] == 'absolute') {
		echo "<td>" . f_err_star('reminder') . _T('app_input_reminder_time') . "</td>\n";
		echo "<td>";

		$name = (($admin || ($edit && ($_SESSION['form_data']['end_time']=='0000-00-00 00:00:00'))) ? 'reminder' : '');
		echo get_date_inputs($name, $_SESSION['form_data']['reminder']);
		echo ' ';
		echo _T('time_input_time_at') . ' ';
		echo get_time_inputs($name, $_SESSION['form_data']['reminder']);

		echo "</td>\n";
	} else {
		echo "<td>" . f_err_star('reminder') . _T('app_input_reminder_offset') . "</td>\n";
		echo "<td>";

		$name = (($admin || ($edit && ($_SESSION['form_data']['end_time']=='0000-00-00 00:00:00'))) ? 'rem_offset' : '');
		$interval = ( ($_SESSION['form_data']['end_time']!='0000-00-00 00:00:00') ?
				strtotime($_SESSION['form_data']['start_time']) - strtotime($_SESSION['form_data']['reminder']) : 0);
		echo get_time_interval_inputs($name, $interval);
		echo " " . _T('time_info_before_start');
		echo f_err_star('reminder');

		echo "</td>\n";
	}

	echo "</tr>\n";
	*/

?>

		<!-- Appointment title -->
		<tr><td valign="top"><?php echo f_err_star('title') . _T('app_input_title'); ?></td>
			<td><input type="text" <?php echo $title_onfocus . $dis; ?> name="title" size="50" value="<?php
			echo clean_output($_SESSION['form_data']['title']) . "\" /></td></tr>\n"; ?>

		<!-- Appointment type -->
		<tr><td><?php echo _T('app_input_type'); ?></td>
			<td><select <?php echo $dis; ?> name="type" size="1" class="sel_frm">
			<?php

			global $system_kwg;

			if ($_SESSION['form_data']['type'])
				$default_app = $_SESSION['form_data']['type'];
			else
				$default_app = $system_kwg['appointments']['suggest'];

			foreach($system_kwg['appointments']['keywords'] as $kw) {
				$sel = ($kw['name'] == $default_app ? ' selected="selected"' : '');
				echo "<option value='" . $kw['name'] . "'" . "$sel>" . _T(remove_number_prefix($kw['title'])) . "</option>\n";
			}

			?>
			</select></td></tr>

		<!-- Appointment description -->
		<tr><td valign="top"><?php echo _T('app_input_description'); ?></td>
			<td><textarea <?php echo $dis; ?> name="description" rows="5" cols="40" class="frm_tarea"><?php
			echo clean_output(_session('description')) . "</textarea></td></tr>\n";

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
			ON (lcm_author.id_author=lcm_author_app.id_author AND id_app=" . $_SESSION['form_data']['id_app'] . ")
			WHERE id_app IS NULL";
*/
		
		$q = "SELECT id_author,name_first,name_middle,name_last
			FROM lcm_author " .
			(count($author_ids) ? " WHERE id_author NOT IN (" . join(',',$author_ids) . ")" : "") . "
			ORDER BY name_first,name_middle,name_last";
		$result = lcm_query($q);

		echo '<select name="author">' . "\n";
		echo '<option selected="selected" value="0"> ... </option>' . "\n";

		while ($row = lcm_fetch_array($result)) {
			echo "<option value=\"" . $row['id_author'] . '">'
				. get_person_name($row)
				. "</option>\n";
		}
		echo "</select>\n";
		echo "<button name=\"submit\" type=\"submit\" value=\"add_author\" class=\"simple_form_btn\">" . 'Add' . "</button>\n"; // TRAD
		echo "</td></tr>\n";
		
		// Appointment participants - clients
		echo '<tr><td valign="top">';
		echo _T('app_input_clients');
		echo "</td><td>";

		$q = "SELECT c.id_client, c.name_first, c.name_middle, c.name_last, o.id_org, o.name
			FROM lcm_client as c, lcm_app_client_org aco
			LEFT JOIN lcm_org as o USING (id_org)
			WHERE id_app = " . _session('id_app', 0) . "
				AND c.id_client = aco.id_client
			ORDER BY c.name_first, c.name_middle, c.name_last, o.name";

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
		$q = "SELECT c.id_client, c.name_first, c.name_last, co.id_org, o.name
			FROM lcm_client AS c
			LEFT JOIN lcm_client_org AS co USING (id_client)
			LEFT JOIN lcm_org AS o ON (co.id_org = o.id_org)
			LEFT JOIN lcm_app_client_org AS aco ON (aco.id_client = c.id_client AND aco.id_app = " . _session('id_app', 0) . ")
			WHERE id_app IS NULL
			ORDER BY c.name_first, c.name_last, o.name";
		
		$result = lcm_query($q);

		echo '<select name="client">' . "\n";
		echo '<option selected="selected" value="0"> ... </option>' . "\n";

		while ($row = lcm_fetch_array($result)) {
			echo '<option value="' . $row['id_client'] . ':' . $row['id_org'] . '">'
				. get_person_name($row)
				. ($row['name'] ? ' of ' . $row['name'] : '') // TRAD
				. "</option>\n";
		}

		echo "</select>\n";
		echo "<button name=\"submit\" type=\"submit\" value=\"add_client\" class=\"simple_form_btn\">" . 'Add' . "</button>\n"; // TRAD
		echo "</td></tr>\n";

		echo "</table>\n";

		// Delete appointment
		if (_session('id_app', 0)) {
			// $checked = ($this->getDataString('hidden') == 'Y' ? ' checked="checked" ' : '');
			$checked = ($_SESSION['form_data']['hidden'] == 'Y' ? ' checked="checked" ' : '');

			echo '<p class="normal_text">';
			echo '<input type="checkbox"' . $checked . ' name="hidden" id="box_delete" />';
			echo '<label for="box_delete">' . _T('app_info_delete') . '</label>';
			echo "</p>\n";
		}

		// Submit buttons
		echo '<button name="submit" type="submit" value="adddet" class="simple_form_btn">' . _T('button_validate') . "</button>\n";

		echo '<input type="hidden" name="id_app" value="' . _session('id_app', 0) . '" />' . "\n";
		echo '<input type="hidden" name="id_case" value="' . _session('id_case', 0) . '" />' . "\n";
		echo '<input type="hidden" name="id_followup" value="' . _session('id_followup', 0) . '" />' . "\n";

		// because of XHTML validation...
		if (_session('ref_edit_app')) {
			$ref_link = new Link(_session('ref_edit_app'));
			echo '<input type="hidden" name="ref_edit_app" value="' . $ref_link->getUrl() . '" />' . "\n";
		}

echo "</form>\n";

lcm_page_end();

// Clear the errors, in case user jumps to other 'edit' page
$_SESSION['errors'] = array();
$_SESSION['app_data'] = array(); // DEPRECATED since 0.7.0
$_SESSION['form_data'] = array();

?>
