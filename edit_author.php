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

	$Id: edit_author.php,v 1.34 2005/03/24 10:16:56 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_contacts');

global $author_session;
$usr = array(); // form data
$author = intval($_GET['author']);

$statuses = array('admin', 'normal', 'external', 'trash'); // , 'suspended'

$meta_subscription = read_meta('site_open_subscription');
if ($meta_subscription == 'moderated' || $meta_subscription == 'yes')
	array_push($statuses, 'waiting');

// Set the returning page
if (isset($_REQUEST['ref']))
	$usr['ref_edit_author'] = $ref;
else
	$usr['ref_edit_author'] = $GLOBALS['HTTP_REFERER'];

// Find out if this is existing or new case
$existing = ($author > 0);

if ($existing) {
	// Check if user is permitted to edit this author's data
	if (($author_session['status'] != 'admin') &&
			($author != $author_session['id_author'])) {
		die("You don't have the right to edit this author's details");	// TRAD
	}

	// Get author data
	$q = "SELECT * FROM lcm_author WHERE id_author = $author";
	$result = lcm_query($q);
	if ($row = lcm_fetch_array($result)) {
		foreach ($row as $key => $value) {
			$usr[$key] = $value;
		}
	} else
		die(_T('error_no_such_user'));

	$type_email = get_contact_type_id('email_main');

	$q = "SELECT value
		FROM lcm_contact
		WHERE id_of_person = $author
			AND type_person = 'author'
			AND type_contact = " . $type_email;
	$result = lcm_query($q);
	if ($contact = lcm_fetch_array($result)) {
		$usr['email'] = $contact['value'];
		$usr['email_exists'] = 'yes';
	}
} else {
	$usr['id_author'] = 0;
	$usr['email'] = '';
	$usr['status'] = 'normal';
}

// Fetch values that caused errors to show them with the error message
if (isset($_SESSION['usr']))
	foreach($_SESSION['usr'] as $key => $value)
		$usr[$key] = $value;

// Start the page with the proper title
if ($existing) lcm_page_start("Edit author"); // TRAD
else lcm_page_start("New author"); // TRAD

echo show_all_errors($_SESSION['errors']);

?>
<form name="edit_author" method="post" action="upd_author.php">
	<input name="id_author" type="hidden" id="id_author" value="<?php echo $usr['id_author']; ?>"/>
	<input name="email_exists" type="hidden" id="email_exists" value="<?php echo $usr['email_exists']; ?>"/>
	<input name="ref_edit_author" type="hidden" id="ref_edit_author" value="<?php echo $usr['ref_edit_author']; ?>"/>

	<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">
		<?php
			//
			// PERSONAL INFO
			//
		?>
		<tr>
			<td colspan="2" align="center" valign="middle" class="heading"><h4><?php echo _T('authoredit_subtitle_personalinfo'); ?></h4></td>
		</tr>

		<tr><td align="right" valign="top"><?php echo f_err_star('name_first', $_SESSION['errors']) . _T('person_input_name_first'); ?></td>
			<td align="left" valign="top"><input name="name_first" type="text" class="search_form_txt" id="name_first" size="35" value="<?php echo clean_output($usr['name_first']); ?>"/></td>
		</tr>

		<?php
			// Middle name can be desactivated, but show anyway if there is one
			if ($usr['name_middle'] || read_meta('client_name_middle') == 'yes') {
		?>

		<tr><td align="right" valign="top"><?php echo _T('person_input_name_middle'); ?></td>
			<td align="left" valign="top"><input name="name_middle" type="text" class="search_form_txt" id="name_middle" size="35" value="<?php echo clean_output($usr['name_middle']); ?>"/></td>
		</tr>

		<?php
			}
		?>
		<tr><td align="right" valign="top"><?php echo f_err_star('name_last', $_SESSION['errors']) . _T('person_input_name_last'); ?></td>
			<td align="left" valign="top"><input name="name_last" type="text" class="search_form_txt" id="name_last" size="35"  value="<?php echo clean_output($usr['name_last']); ?>"/></td>
		</tr>
<?php

	//
	// Contacts (e-mail, phones, etc.)
	//

	echo "<tr>\n";
	echo '<td colspan="2" align="center" valign="middle" class="heading">';
	echo '<h4>' . _T('client_subtitle_contacts') . '</h4>';
	echo '</td>';
	echo "</tr>\n";

	show_edit_contacts_form('author', $usr['id_author']);

	//
	// LOGIN INFO
	//

	?>
		<tr>
			<td colspan="2" align="center" valign="middle" class="heading"><h4><?php echo _T('authoredit_subtitle_connectionidentifiers'); ?></h4></td>
		</tr>
		<tr><td align="right" valign="top"><?php echo f_err_star('username', $_SESSION['errors']) . _T('authoredit_input_username'); ?></td>
			<td align="left" valign="top">

		<?php
			global $author_session;

			$class_auth = 'Auth_db';
			include_lcm('inc_auth_db');

			$auth = new $class_auth;

			if (! $auth->init()) {
				echo "<p><b>ERROR: failed to initialize auth method: " . $auth->error . "</b></p>\n";	// TRAD
				lcm_log("ERROR: failed to initialize auth method: " . $auth->error);
			}

			// Some authentication methods might not allow the username to be 
			// changed. Also, it is generally better not to allow users to
			// change their username. Show the fields only if it is possible.
			echo '<input name="username_old" type="hidden" id="username_old" value="' . clean_output($usr['username']) .'"/>';
			echo "\n";

			if ($auth->is_newusername_allowed($usr['id_author'], $usr['username'], $author_session)) {
				echo '<input name="username" type="text" class="search_form_txt" id="username" size="35" value="'
					. ($usr['username'] == '0' ? '' : clean_output($usr['username'])) .'"/>';
			} else {
				echo '<input type="hidden" name="username" value="' . clean_output($usr['username']) . '"/>';
				echo $usr['username'];
			}
		?>

			</td>
		</tr>

		<?php
			// Some authentication methods might not allow the password
			// to be changed. Show the fields only if it is possible.
			if ($auth->is_newpass_allowed($usr['id_author'], $usr['username'], $author_session)) {
				// Do not request 'current password' if new author or admin
				if ($usr['id_author'] && $author_session['status'] != 'admin') {
					echo '
		<tr>
			<td align="right" valign="top">' . f_err_star('password_current', $_SESSION['errors']) . _T('authorconf_input_password_current') . '</td>
			<td align="left" valign="top"><input name="usr_old_passwd" type="password" class="search_form_txt" id="usr_old_passwd" size="35" /></td>
		</tr>' . "\n";
				}
		?>
		
		<tr>
			<td align="right" valign="top"><?php echo f_err_star('password_confirm', $_SESSION['errors']) . _T('authorconf_input_password_new'); ?></td>
			<td align="left" valign="top"><input name="usr_new_passwd" type="password" class="search_form_txt" id="usr_new_passwd" size="35" /></td>
		</tr>
		<tr>
			<td align="right" valign="top"><?php echo f_err_star('password_confirm', $_SESSION['errors']) . _T('authorconf_input_password_confirm'); ?></td>
			<td align="left" valign="top"><input name="usr_retype_passwd" type="password" class="search_form_txt" id="usr_retype_passwd" size="35" /></td>
		</tr>

		<?php
			} /* is_newpass_allowed() */
		?>

		<tr><td align="right" valign="top"><?php echo _Ti('authoredit_input_status'); ?></td>
			<td align="left" valign="top">
			
<?php
			echo '<input type="hidden" name="status_old" value="' . $usr['status'] . '"/>' . "\n";

			if ($author_session['status'] == 'admin' && $usr['id_author'] != $author_session['id_author']) {
				echo '<select name="status" class="sel_frm" id="status">' . "\n";

				foreach ($statuses as $s) {
					echo "\t\t\t\t<option value=\"$s\""
						. (($s == $usr['status']) ? ' selected="selected"' : '') . ">" . _T('authoredit_input_status_' . $s) . "</option>\n";
				}

				echo "</select>\n";
			} else {
				echo '<input type="hidden" name="status" value="' . $usr['status'] . '"/>' . "\n";
				echo $usr['status'];
			}
?>
			</td>
		</tr>
		<tr><td colspan="2" align="center" valign="middle">
			<input name="submit" type="submit" class="search_form_btn" id="submit" value="<?php echo _T('button_validate') ?>" /></td>
		</tr>
	</table>
</form>

<?php

lcm_page_end();

// Reset error messages
$_SESSION['errors'] = array();
$_SESSION['usr'] = array();

?>
