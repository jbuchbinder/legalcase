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

if ($author>0) {

	lcm_page_start("Edit author");

	$q = "SELECT * FROM lcm_author WHERE id_author=" . $author;
	$result = lcm_query($q);
	if (!($usr = lcm_fetch_array($result))) die(_T('error_no_such_user'));
} else {
	lcm_page_start("New author");
	$usr = array();
}

global $author_session;

$prefs = ($usr['prefs']) ? unserialize($usr['prefs']) : array();
$statuses = array('admin', 'normal', 'external', 'trash', 'waiting', 'suspended');

?>
<form name="edit_author" method="post" action="upd_author.php">
	<!-- input type="hidden" name="author_modified" value="yes"/ -->

	<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">
		<tr><td align="right" valign="top">First name:</td>
			<td align="left" valign="top"><input name="usr_fname" type="text" class="search_form_txt" id="usr_fname" size="35" value="<?php echo clean_output($usr['name_first']); ?>"/></td>
		</tr>
		<tr><td align="right" valign="top">Middle name:</td>
			<td align="left" valign="top"><input name="usr_mname" type="text" class="search_form_txt" id="usr_mname" size="35"  value="<?php echo clean_output($usr['name_middle']); ?>"/></td>
		</tr>
		<tr><td align="right" valign="top">Last name:</td>
			<td align="left" valign="top"><input name="usr_lname" type="text" class="search_form_txt" id="usr_lname" size="35"  value="<?php echo clean_output($usr['name_last']); ?>"/></td>
		</tr>
		<tr><td align="right" valign="top">E-mail:</td>
			<td align="left" valign="top"><input name="usr_email" type="text" class="search_form_txt" id="usr_email" size="35" /></td>
		</tr>
		<tr><td align="right" valign="top">Status:</td>
			<td align="left" valign="top"><select name="usr_status" class="sel_frm" id="usr_status">
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
