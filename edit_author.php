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

	$Id: edit_author.php,v 1.30 2005/03/18 16:28:42 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_contacts');

global $author_session;
$usr = array(); // form data
$statuses = array('admin', 'normal', 'external', 'trash', 'waiting', 'suspended');
$author = intval($_GET['author']);

// Set the returning page
if (isset($ref)) $usr['ref_edit_author'] = $ref;
else $usr['ref_edit_author'] = $HTTP_REFERER;

// Find out if this is existing or new case
$existing = ($author > 0);

if ($existing) {
	// Check if user is permitted to edit this author's data
	if (($author_session['status'] != 'admin') &&
			($author != $author_session['id_author'])) {
		die("You don't have the right to edit this author's details");
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
if ($existing) lcm_page_start("Edit author");
else lcm_page_start("New author");

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

	$cpt = 0;
	$cpt_new = 0;

	$emailmain_exists = false;
	$addrmain_exists = false;

	$contacts_emailmain = get_contacts('author', $usr['id_author'], 'email_main');
	$contacts_addrmain = get_contacts('author', $usr['id_author'], 'address_main');
	$contacts_other = get_contacts('author', $usr['id_author'], 'email_main,address_main', 'not');

/*
	// For new specific type of contact, such as 'email_main', 'address_main'
	function print_new_contact($type_kw, $type_name, $num_new) {
		echo '<tr><td align="right" valign="top">' . _T("kw_contacts_" . $type_kw . "_title") . "\n";
		echo '<td align="left" valign="top">';
		echo '<input name="new_contact_type_name[]" id="new_contact_type_name_' . $num_new . '" '
			. 'type="hidden" value="' . $type_name . '" />' . "\n";

		echo '<input name="new_contact_value[]" id="new_contact_value_' . $num_new . '" type="text" '
			. 'class="search_form_txt" size="35" value=""/>&nbsp;';
		
		echo "</td>\n</tr>\n\n";
	}
*/
	
	// First show the main address
	foreach ($contacts_addrmain as $contact) {
		print_existing_contact($contact, $cpt); 
		$cpt++;
		$addrmain_exists = true;
	}

	if (! $addrmain_exists) {
		print_new_contact('addressmain', 'address_main', $cpt_new);
		$cpt_new++;
	}

	// Second show the email_main
	foreach ($contacts_emailmain as $contact) {
		print_existing_contact($contact, $cpt);
		$cpt++;
		$emailmain_exists = true;
	}

	if (! $emailmain_exists) {
		print_new_contact('emailmain', 'email_main', $cpt_new);
		$cpt_new++;
	}

	// Show all the rest
	foreach ($contacts_other as $contact) {
		print_existing_contact($contact, $cpt);
		$cpt++;
	}

	// Show "new contact"
?>
		<tr>
			<td align="right" valign="top">
			
			<?php
				echo f_err_star('new_contact_' . $cpt_new, $_SESSION['errors']);
				echo "Other contact";
			?>
			
			</td>
			<td align="left" valign="top">
				<div>
				<?php
					global $system_kwg;

					echo '<select name="new_contact_type_name[]" id="new_contact_type_' . $cpt_new . '" class="sel_frm">' . "\n";
					echo "<option value=''>" . "- select contact type -" . "</option>\n";

					foreach ($system_kwg['contacts']['keywords'] as $contact) {
						if ($contact['name'] != 'email_main' && $contact['name'] != 'address_main') {
							echo "<option value='" . $contact['name'] . "'>" . _T($contact['title']) . "</option>\n";
						}
					}
					echo "</select>\n";

				?>
				</div>
				<div>
					<input type='text' size='40' style='style: 99%' name='new_contact_value[]' id='new_contact_value_<?php echo $cpt_new; ?>' 
					
					<?php 
						echo ' value="' . $_SESSION['usr']['new_contact_' . $cpt_new] . '" ';
						$cpt_new++;
					?>
						
					class='search_form_txt' />
				</div>
			</td>
		</tr>
	<?php
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
				echo "<p><b>ERROR: failed to initialize auth method: " . $auth->error . "</b></p>\n";
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

		<tr><td align="right" valign="top"><?php echo "Status:"; /* TRAD */ ?></td>
			<td align="left" valign="top">
			
<?php
			echo '<input type="hidden" name="status_old" value="' . $usr['status'] . '"/>' . "\n";

			if ($author_session['status'] == 'admin' && $usr['id_author'] != $author_session['id_author']) {
				echo '<select name="status" class="sel_frm" id="status">' . "\n";

				foreach ($statuses as $s) {
					echo "\t\t\t\t<option value=\"$s\""
						. (($s == $usr['status']) ? ' selected="selected"' : '') . ">$s</option>\n";
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
