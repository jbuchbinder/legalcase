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

include ("inc/inc.php");

function show_config_form() {
	echo "<p><img src='images/jimmac/icon_warning.gif' alt='' align='right'
		height='48' width='48' />" . _T('siteconf_warning') . "</p>\n";

	echo "<form name='upd_site_profile' method='post' action='config_site.php'>\n";
	echo "\t<input type='hidden' name='conf_modified' value='yes'/>\n";

	//
	// *** INFO SITE
	//
	$site_name = read_meta('site_name');
	$site_desc = read_meta('site_description');
	$site_address = read_meta('site_address');
	$default_language = read_meta('default_language');
	$email_sysadmin = read_meta('email_sysadmin');

	if (empty($site_name))
		$site_name = _T('title_software');

	echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";
	echo "<tr>\n";
	echo '<td colspan="2" align="center" valign="middle" class="heading"><h4>';
	echo _T('siteconf_subtitle_general_info');
	echo "</h4></td>\n";
	echo "<tr>\n";
	echo "<td>";

	echo "<p><b>" . _T('siteconf_input_site_name') . "</b></p>\n";
	echo "<p><small>" . _T('siteconf_info_site_name') . "</small></p>\n";
	echo "<p><input type='text' id='site_name' name='site_name' value='$site_name' size='40'/></p>\n";

	echo "<p><b>" . _T('siteconf_input_site_desc') . "</b></p>\n";
	echo "<p><small>" . _T('siteconf_info_site_desc') . "</small></p>\n";
	echo "<p><input type='text' id='site_desc' name='site_desc' value='$site_desc' size='40'/></p>\n";

	echo "<p><b>" . _T('siteconf_input_site_address') . "</b></p>\n";
	echo "<p><input type='text' id='site_address' name='site_address' value='$site_address' size='40'/></p>\n";

	echo "<p><b>" . _T('siteconf_input_default_lang') . "</b></p>\n";
	echo "<p><small>" . _T('siteconf_info_default_lang') . "</small></p>\n";
	echo "<p>" . menu_languages('default_language', $default_language) . "\n";

	echo "<p><b>" . _T('siteconf_input_admin_email') . "</b></p>\n";
	echo "<p><small>" . _T('siteconf_info_admin_email') . "</small></p>\n";
	echo "<p><input type='text' id='email_sysadmin' name='email_sysadmin' value='$email_sysadmin' size='40'/></p>\n";
	echo "</td>\n</tr>\n</table>\n";

	// 
	// *** COLLAB WORK
	//
	$case_default_read = read_meta('case_default_read');
	$case_default_write = read_meta('case_default_write');
	$case_read_always = read_meta('case_read_always');
	$case_write_always = read_meta('case_write_always');

	echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";
	echo "<tr>\n";
	echo '<td colspan="2" align="center" valign="middle" class="heading"><h4>';
	echo _T('siteconf_subtitle_collab_work');
	echo "</h4></td>\n";
	echo "<tr>\n";
	echo "<td>";

	echo "<p><small>This only applies to new cases. Wording of this page needs fixing.</small></p>\n";

	// READ ACCESS
	echo "<p><b>Read access to cases</b></p>\n";

	echo "<p>Who can view case information?<br>
<small>(Cases usually have one or many authors specifically assigned to them. It is assumed that assigned authors can consult the case and it's follow-ups, but what about authors who are not assigned to the case?)</small></p>\n";

	echo "<ul>";
	// If by default read set to public (case_default_read == yes)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read' id='case_default_read_1' value='yes'";
	if ($case_default_read) echo " checked";
	echo "><label for='case_default_read_1'>Any author can view the case information of other authors, even if they are not on the case (better cooperation).</label></input></li>\n";

	// If by default read not set to public (case_default_read != yes)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read' id='case_default_read_2' value=''";
	if (!$case_default_read) echo " checked";
	echo "><label for='case_default_read_2'>Only authors assigned to a case can view its information and follow-ups (better privacy).</label></input></li>\n";
	echo "</ul>\n";

	// READ ACCESS POLICY
	echo "<p><b>Read access global policy</b></p>\n";

	echo "<p>Who can change the read access to a case?<br>
<small>(This is used to avoid mistakes or to enforce a site policy.)</small></p>\n";

	echo "<ul>";
	// Anyone can change the setting (case_read_always != yes)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_read_always' id='case_read_always_2' value=''";
	if (!$case_read_always) echo " checked";
	echo "><label for='case_read_always_2'>Any author assigned to the case (and with the right to edit the case).</label></input></li>\n";

	// Only the admin can change the setting (case_read_always == yes)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_read_always' id='case_read_always_1' value='yes'";
	if ($case_read_always) echo " checked";
	echo "><label for='case_read_always_1'>Site administrators only.</label></input></li>\n";

	echo "</ul>\n";

	echo "<hr>\n";

	// WRITE ACCESS
	echo "<p><b>Write access to cases</b></p>\n";

	echo "<p>Who can write information in the cases?<br>
<small>(Cases usually have one or many authors specifically assigned to them. It is assumed that only assigned authors can add follow-up information to the case, but what about authors who are not assigned to the case?)</small></p>\n";

	echo "<ul>";
	// If by default write set to public (case_default_write == 'yes')
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write' id='case_default_write_1' value='yes'";
	if ($case_default_write) echo " checked";
	echo "><label for='case_default_write_1'>Any author can write the case information of other authors, even if they are not on the case (better cooperation).</label></input></li>\n";

	// If by default write not set to public (case_default_write != 'yes')
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write' id='case_default_write_2' value=''";
	if (!$case_default_write) echo " checked";
	echo "><label for='case_default_write_2'>Only authors assigned to a case can write its information and follow-ups (better privacy).</label></input></li>\n";
	echo "</ul>\n";

	// WRITE ACCESS POLICY
	echo "<p><b>Write access global policy</b></p>\n";

	echo "<p>Who can change the write access to a case?<br>
<small>(This is used to avoid mistakes or to enforce a site policy.)</small></p>\n";

	echo "<ul>";
	// Anyone can change the setting (case_write_always != yes)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_write_always' id='case_write_always_2' value=''";
	if (!$case_write_always) echo " checked";
	echo "><label for='case_write_always_2'>Any author assigned to the case (and with the right to edit the case).</label></input></li>\n";

	// Only the admin can change the setting (case_write_always == yes)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_write_always' id='case_write_always_1' value='yes'";
	if ($case_write_always) echo " checked";
	echo "><label for='case_write_always_1'>Site administrators only.</label></input></li>\n";
	echo "</ul>\n";
	echo "</td>\n</tr>\n</table>\n";

	//
	// *** SELF-REGISTRATION
	//
	$site_open_subscription = read_meta('site_open_subscription');

	echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";
	echo "<tr>\n";
	echo '<td colspan="2" align="center" valign="middle" class="heading"><h4>';
	echo _T('siteconf_subtitle_self_registration');
	echo "</h4></td>\n";
	echo "<tr>\n";
	echo "<td>";

	echo "<p>" . _T('siteconf_info_self_registration') . "</p>\n";
	echo "<ul>";

	// moderated
	echo "<li style='list-style-type: none;'><input type='radio' name='site_open_subscription' id='site_open_subscription_2' value='moderated'";
	if ($site_open_subscription == 'moderated') echo " checked='checked'";
	echo " /><label for='site_open_subscription_2'>" . _T('siteconf_input_selfreg_moderated') . "</label></input></li>\n";

	// un-moderated (yes)
	echo "<li style='list-style-type: none;'>";
	echo "<input type='radio' name='site_open_subscription' id='site_open_subscription_1' value='yes'";
	if ($site_open_subscription == 'yes') echo " checked='checked'";
	echo " /><label for='site_open_subscription_1'>" . _T('siteconf_input_selfreg_yes') . "</label></input></li>\n";

	// no
	echo "<li style='list-style-type: none;'><input type='radio' name='site_open_subscription' id='site_open_subscription_3' value='no'";
	if ($site_open_subscription == 'no') echo " checked='checked'";
	echo "><label for='site_open_subscription_3'>" . _T('siteconf_input_selfreg_no') . "</label></input></li>\n";

	echo "</ul>\n";
	echo "</td>\n</tr>\n</table>\n";

	echo "<p><button type='submit' name='Validate' id='Validate'>" .  _T('button_validate') . "</button></p>\n";

	echo "</form>\n";
}

function apply_conf_changes() {
	$log = array();

	global $site_name;
	global $site_desc;
	global $site_address;
	global $default_language;
	global $email_sysadmin;
	global $case_default_read;
	global $case_default_write;
	global $case_read_always;
	global $case_write_always;
	global $site_open_subscription;

	// Site name
	if (! empty($site_name)) {
		$old_name = read_meta('site_name');
		if (! $old_name) $old_name = _T('title_software');

		if ($old_name != $site_name) {
			write_meta('site_name', $site_name);
			array_push($log, "Name of site set to '<tt>$site_name</tt>', was '<tt>$old_name</tt>'.");
		}
	}

	// Site description (may be empty)
	$old_desc = read_meta('site_description');

	if ($old_desc != $site_desc) {
		write_meta('site_description', $site_desc);
		array_push($log, "Description of site set to '<tt>$site_desc</tt>', was '<tt>$old_desc</tt>'.");
	}

	// Site address (Internet or LAN)
	$old_address = read_meta('site_address');

	if ($old_address != $site_address) {
		write_meta('site_address', $site_address);
		array_push($log, "Site Internet or network address set to '<tt>$site_address</tt>', was '<tt>$old_address</tt>'.");
	}

	// Default language
	if (! empty($default_language)) {
		$old_lang = read_meta('default_language');

		if ($old_lang != $default_language) {
			write_meta('default_language', $default_language);
			array_push($log, "Default language set to <tt>"
				. translate_language_name($default_language)
				. "</tt>, previously was <tt>"
				. translate_language_name($old_lang) ."</tt>.");
		}
	}

	// Administrator e-mail
	if (! empty($email_sysadmin)) {
		if ($email_sysadmin != read_meta('email_sysadmin')) {
			if (is_valid_email($email_sysadmin)) {
				write_meta('email_sysadmin', $email_sysadmin);
				array_push($log, "Sysadmin e-mail address et to <tt>"
					. addslashes($email_sysadmin) . "</tt>.");
			} else {
				// FIXME not the best way of showing errors... 
				array_push($log, "Sysadmin e-mail address <tt>"
					. addslashes($email_sysadmin) . "</tt> is <b>not</b> a "
					. "valid address. Modification not applied.");
			}
		}
	}

	// Default read policy
	if ($case_default_read != read_meta('case_default_read')) {
		write_meta('case_default_read', ($case_default_read ? 'yes' : ''));

		$entry = "Read access to cases set to '<tt>";
		if ($case_default_read) $entry .= "public";
		else $entry .= "restricted";
		$entry .= "</tt>'";
		array_push($log, $entry);
	}

	// Default write policy
	if ($case_default_write != read_meta('case_default_write')) {
		write_meta('case_default_write', ($case_default_write ? 'yes' : ''));

		$entry = "Write access to cases set to '<tt>";
		if ($case_default_write) $entry .= "public";
		else $entry .= "restricted";
		$entry .= "</tt>'";
		array_push($log, $entry);
	}

	// Read policy access
	if ($case_read_always != read_meta('case_read_always')) {
		write_meta('case_read_always', ($case_read_always ? 'yes' : ''));

		$entry = "Read access policy can by changed by <tt>";
		if ($case_read_always) $entry .= "admin only";
		else $entry .= "everybody";
		$entry .= "</tt>";
		array_push($log, $entry);
	}

	// Write policy access
	if ($case_write_always != read_meta('case_write_always')) {
		write_meta('case_write_always', ($case_write_always ? 'yes' : ''));

		$entry = "Write access policy can be changed by <tt>";
		if ($case_write_always) $entry .= "admin only";
		else $entry .= "everybody";
		$entry .= "</tt>";
		array_push($log, $entry);
	}

	// Self-registration
	$old_site_open_subscription = read_meta('site_open_subscription');
	if ($site_open_subscription != $old_site_open_subscription) {
		if ($site_open_subscription == 'yes' || $site_open_subscription == 'moderated' || $site_open_subscription == 'no') {
			write_meta('site_open_subscription', $site_open_subscription);
			array_push($log, "New author self-registration changed to "
				. "'$site_open_subscription', was '$old_site_open_subscription'.");
		}
	}

	if (! empty($log))
		write_metas();
	
	return $log;
}

global $author_session;

if ($author_session['status'] != 'admin') {
	lcm_page_start("Site configuration");
	echo "<p>Warning: Access denied, not admin.\n";
	lcm_page_end();
} else {
	if ($conf_modified)
		$log = apply_conf_changes();

	// Once ready, show the form (must be done after changes are
	// applied so that they can be used in the header).
	lcm_page_start(_T('title_site_configuration'));

	// Show changes on screen
	if (! empty($log)) {
		echo "<div align='left' style='border: 1px solid #00ff00; padding: 5px;'>\n";
		echo "<div>Changes made:</div>\n";
		echo "<ul>";

		foreach ($log as $line) {
			echo "<li>" . $line . "</li>\n";
		}

		echo "</ul>\n";
		echo "</div>\n";
	}

	show_config_form();
	lcm_page_end();
}


?>
