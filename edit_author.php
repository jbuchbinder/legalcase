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
include_lcm('inc_filters');

// Initiate session
session_start();

if (empty($errors)) {

    // Clear form data
    $usr = array();

	// Set the returning page
	if (isset($ref)) $usr['ref_edit_author'] = $ref;
	else $usr['ref_edit_author'] = $HTTP_REFERER;

	// Register case type variable for the session
	if (!session_is_registered("existing"))
		session_register("existing");

	// Find out if this is existing or new case
	$existing = ($author > 0);

	if ($existing) {
		$q = "SELECT * FROM lcm_author WHERE id_author=$author";
		$result = lcm_query($q);
		if ($row = lcm_fetch_array($result)) {
			foreach ($row as $key => $value) {
				$usr[$key] = $value;
			}
		} else  die(_T('error_no_such_user'));


		$q = "SELECT value FROM lcm_contact WHERE (id_of_person=$author
					AND type_person='author'
					AND type_contact=1)";
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
}

$prefs = ($usr['prefs']) ? unserialize($usr['prefs']) : array();
$statuses = array('admin', 'normal', 'external', 'trash', 'waiting', 'suspended');

// Start the page with the proper title
if ($existing) lcm_page_start("Edit author");
else lcm_page_start("New author");

?>
<form name="edit_author" method="post" action="upd_author.php">
	<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">
		<input name="id_author" type="hidden" id="id_author" value="<?php echo $usr['id_author']; ?>"/>
		<input name="email_exists" type="hidden" id="email_exitst" value="<?php echo $usr['email_exists']; ?>"/>
		<input name="ref_edit_author" type="hidden" id="ref_edit_author" value="<?php echo $usr['ref_edit_author']; ?>"/>
		<tr><td align="right" valign="top">Username:</td>
			<td align="left" valign="top"><input name="username" type="text" class="search_form_txt" id="username" size="35" value="<?php echo clean_output($usr['username']); ?>"/></td>
		</tr>
		<tr><td align="right" valign="top">First name:</td>
			<td align="left" valign="top"><input name="name_first" type="text" class="search_form_txt" id="name_first" size="35" value="<?php echo clean_output($usr['name_first']); ?>"/></td>
		</tr>
		<tr><td align="right" valign="top">Middle name:</td>
			<td align="left" valign="top"><input name="name_middle" type="text" class="search_form_txt" id="name_middle" size="35" value="<?php echo clean_output($usr['name_middle']); ?>"/></td>
		</tr>
		<tr><td align="right" valign="top">Last name:</td>
			<td align="left" valign="top"><input name="name_last" type="text" class="search_form_txt" id="name_last" size="35"  value="<?php echo clean_output($usr['name_last']); ?>"/></td>
		</tr>
		<tr><td align="right" valign="top">E-mail:</td>
			<td align="left" valign="top"><input name="email" type="text" class="search_form_txt" id="email" size="35" value="<?php echo clean_output($usr['email']); ?>"/>&nbsp;<?php echo f_err('email',$errors); ?></td>
		</tr>
		<tr><td align="right" valign="top">Status:</td>
			<td align="left" valign="top"><select name="status" class="sel_frm" id="status">
<?php
			foreach ($statuses as $s) {
				echo "\t\t\t\t<option value=\"$s\""
					. (($s == $usr['status']) ? ' selected="selected"' : '') . ">$s</option>\n";
			}
?>			</select></td>
		</tr>
		<tr><td colspan="2" align="center" valign="middle">
			<input name="submit" type="submit" class="search_form_btn" id="submit" value="Save changes" /></td>
		</tr>
	</table>
</form>
<?php

lcm_page_end();

?>
