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

// TODO: We should keep a log of modifications
// For example, if currency changed many times, it will allow
// to track when a currency had which value (silly, but can avoid crisis)

function show_config_form($panel) {
	echo "<p><img src='images/jimmac/icon_warning.gif' alt='' align='right'
		height='48' width='48' />" . _T('siteconf_warning') . "</p>\n";

	if ($panel == 'collab') {
		$html_collab = " &lt;--";
	} else if ($panel == 'policy') {
		$html_policy = " &lt;--";
	} else {
		$html_general = " &lt;--";
	}

	echo "<ul>\n";
	echo "<li><a href='config_site.php?panel=general'>" . _T('siteconf_subtitle_general_info') . "</a>" . $html_general . "</li>\n";
	echo "<li><a href='config_site.php?panel=collab'>" . _T('siteconf_subtitle_collab_work') . "</a>" . $html_collab . "</li>\n";
	echo "<li><a href='config_site.php?panel=policy'>" .  _T('siteconf_subtitle_policy') . "</a>" . $html_policy . "</li>\n";
	echo "</ul>\n";

	echo "<form name='upd_site_profile' method='post' action='config_site.php'>\n";

	if ($panel == 'collab') {
		show_config_form_collab();
	} else if ($panel == 'policy') {
		show_config_form_policy();
	} else {
		show_config_form_general();
	}

	echo "</form>\n";
}

function show_config_form_general() {
	global $lcm_lang_right;

	$site_name = read_meta('site_name');
	$site_desc = read_meta('site_description');
	$site_address = read_meta('site_address');
	$default_language = read_meta('default_language');
	$email_sysadmin = read_meta('email_sysadmin');
	$currency = read_meta('currency');

	if (empty($site_name))
		$site_name = _T('title_software');

	// If no currency format set, get default format from the 
	// global language translation files
	if (empty($currency)) {
		$current_lang = $GLOBALS['lcm_lang'];
		$GLOBALS['lcm_lang'] = $default_language;
		$currency = _T('currency_default_format');
		$GLOBALS['lcm_lang'] = $current_lang;
	}

	echo "\t<input type='hidden' name='conf_modified_general' value='yes'/>\n";
	echo "\t<input type='hidden' name='panel' value='general'/>\n";

	echo "<fieldset style='margin: 0; margin-bottom: 1em; padding: 0.5em; -moz-border-radius: 10px;'>\n";
	// XXX fix css, this was just a test
	echo "<p class='prefs_column_menu_head'><b><label for='site_name'>" . _T('siteconf_input_site_name') . "</label></b></p>\n";
	echo "<p><small>" . _T('siteconf_info_site_name') . "</small></p>\n";
	echo "<p><input type='text' id='site_name' name='site_name' value='$site_name' size='40'/></p>\n";

	echo "<p class='prefs_column_menu_head'><b><label for='site_desc'>" . _T('siteconf_input_site_desc') . "</label></b></p>\n";
	echo "<p><small>" . _T('siteconf_info_site_desc') . "</small></p>\n";
	echo "<p><input type='text' id='site_desc' name='site_desc' value='$site_desc' size='40'/></p>\n";
	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

	echo "<fieldset style='margin: 0; margin-bottom: 1em; padding: 0.5em; -moz-border-radius: 10px;'>\n";
	echo "<p><b><label for='site_address'>" . _T('siteconf_input_site_address') . "</label></b></p>\n";
	echo "<p><input type='text' id='site_address' name='site_address' value='$site_address' size='40'/></p>\n";

	echo "<p><b><label for='email_sysadmin'>" . _T('siteconf_input_admin_email') . "</label></b></p>\n";
	echo "<p><small>" . _T('siteconf_info_admin_email') . "</small></p>\n";
	echo "<p><input type='text' id='email_sysadmin' name='email_sysadmin' value='$email_sysadmin' size='40'/></p>\n";
	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

	echo "<fieldset style='margin: 0; margin-bottom: 1em; padding: 0.5em; -moz-border-radius: 10px;'>\n";
	echo "<p><b>" . _T('siteconf_input_default_lang') . "</b></p>\n";
	echo "<p><small>" . _T('siteconf_info_default_lang') . "</small></p>\n";
	echo "<p align='center'>" . menu_languages('default_language', $default_language) . "</p>\n";

	echo "<p><b><label for='currency'>" . _T('siteconf_input_currency') . "</label></b></p>\n";
	echo "<p><small>" . _T('siteconf_info_currency') . "</small></p>\n";
	echo "<p><small>" . _T('siteconf_warning_currency') . "</small></p>\n";
	echo "<p align='center'><input type='text' id='currency' name='currency' value='$currency' size='5'/></p>\n";
	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";
}

function show_config_form_collab() {
	global $lcm_lang_right;

	$case_default_read = read_meta('case_default_read');
	$case_default_write = read_meta('case_default_write');
	$case_read_always = read_meta('case_read_always');
	$case_write_always = read_meta('case_write_always');

	echo "\t<input type='hidden' name='conf_modified_collab' value='yes'/>\n";
	echo "\t<input type='hidden' name='panel' value='collab'/>\n";

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
	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate'>" .  _T('button_validate') . "</button></p>\n";
}

function get_yes_no($name, $value = '') {
	$ret = '';

	$yes = ($value == 'yes' ? ' selected="selected"' : '');
	$no = ($value == 'no' ? ' selected="selected"' : '');
	$other = ($yes || $no ? '' : ' selected="selected"');

	// until we format with tables, better to keep the starting space
	$ret .= ' <select name="' . $name . '">' . "\n";
	$ret .= '<option value="yes"' . $yes . '>' . _T('info_yes') . '</option>';
	$ret .= '<option value="no"' . $no . '>' . _T('info_no') . '</option>';

	if ($other)
		$ret .= '<option value=""' . $other . '> </option>';

	$ret .= '</select>' . "\n";

	return $ret;
}

function show_config_form_policy() {
	global $lcm_lang_right;

	$client_name_middle = read_meta('client_name_middle');
	$client_citizen_number = read_meta('client_citizen_number');
	$case_court_archive = read_meta('case_court_archive');
	$case_assignment_date = read_meta('case_assignment_date');
	$case_alledged_crime = read_meta('case_alledged_crime');
	$case_allow_modif = read_meta('case_allow_modif');
	$fu_sum_billed = read_meta('fu_sum_billed');
	$fu_allow_modif = read_meta('fu_allow_modif');

	echo "\t<input type='hidden' name='conf_modified_policy' value='yes'/>\n";
	echo "\t<input type='hidden' name='panel' value='policy'/>\n";

	// ** CLIENTS
	echo "<fieldset style='margin: 0; margin-bottom: 1em; padding: 0.5em; -moz-border-radius: 10px;'>\n";
	// XXX fix css, this was just a test
	echo "<p class='prefs_column_menu_head'><b>" . _T('siteconf_subtitle_client_fields') . "</b></p>\n";
	echo "<p><small>" . _T('siteconf_info_client_fields') . "</small></p>\n";

	echo "<ul>";
	echo "<li> " . _T('siteconf_input_name_middle')
		. get_yes_no('client_name_middle', $client_name_middle) 
		. "</li>\n";
	echo "<li> " . _T('siteconf_input_citizen_number')
		. get_yes_no('client_citizen_number', $client_citizen_number) 
		. "</li>\n";
	echo "</ul>\n";

	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

	// ** CASES
	echo "<fieldset style='margin: 0; margin-bottom: 1em; padding: 0.5em; -moz-border-radius: 10px;'>\n";
	echo "<p class='prefs_column_menu_head'><b>" . _T('siteconf_subtitle_case_fields') . "</b></p>\n";
	echo "<p><small>" . _T('siteconf_info_case_fields') . "</small></p>\n";

	echo "<ul>";
	echo "<li> " . _T('siteconf_input_court_archive') 
		. get_yes_no('case_court_archive', $case_court_archive)
		. "</li>\n";
	echo "<li> " . _T('siteconf_input_assignment_date') 
		. get_yes_no('case_assignment_date', $case_assignment_date)
		. "</li>\n";
	echo "<li> " . _T('siteconf_input_alledged_crime')
		. get_yes_no('case_alledged_crime', $case_alledged_crime)
		. "</li>\n";
	echo "<li> " . _T('siteconf_input_case_allow_modif')
		. get_yes_no('case_allow_modif', $case_allow_modif)
		. "</li>\n";
	echo "</ul>\n";

	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

	// ** FOLLOW-UPS
	echo "<fieldset style='margin: 0; margin-bottom: 1em; padding: 0.5em; -moz-border-radius: 10px;'>\n";
	echo "<p class='prefs_column_menu_head'><b>" . _T('siteconf_subtitle_followup_fields') . "</b></p>\n";
	echo "<p><small>" . _T('siteconf_info_followups_fields') . "</small></p>\n";

	echo "<ul>";
	echo "<li>" . _T('siteconf_input_sum_billed')
		. get_yes_no('fu_sum_billed', $fu_sum_billed)
		. "</li>\n";
	echo "<li>" . _T('siteconf_input_fu_allow_modif')
		. get_yes_no('fu_allow_modif', $fu_allow_modif)
		. "</li>\n";
	echo "</ul>\n";

	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

}

function apply_conf_changes_general() {
	$log = array();

	global $site_name;
	global $site_desc;
	global $site_address;
	global $default_language;
	global $email_sysadmin;
	global $currency;

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

	// Currency
	if (! empty($currency)) {
		$old_currency = read_meta('currency');

		if ($currency != $old_currency) {
			write_meta('currency', $currency);
			array_push($log, "Currency changed to <tt>$currency</tt>, "
				. "was <tt>$old_currency</tt>.");
		}
	}

	if (! empty($log))
		write_metas();
	
	return $log;
}

function apply_conf_changes_collab() {
	$log = array();

	global $case_default_read;
	global $case_default_write;
	global $case_read_always;
	global $case_write_always;
	global $site_open_subscription;

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
		if ($site_open_subscription == 'yes' || 
			$site_open_subscription == 'moderated' || 
			$site_open_subscription == 'no') 
		{
			write_meta('site_open_subscription', $site_open_subscription);
			array_push($log, "New author self-registration changed to "
				. "'$site_open_subscription', was '$old_site_open_subscription'.");
		}
	}

	if (! empty($log))
		write_metas();

	return $log;
}

function apply_conf_changes_policy() {
	$log = array();

	global $client_name_middle;
	global $client_citizen_number;
	global $case_court_archive;
	global $case_assignment_date;
	global $case_alledged_crime;
	global $case_allow_modif;
	global $fu_sum_billed;
	global $fu_allow_modif;

	// XXX [ML] I did alot of copy-pasting .. had I been a bit smarter,
	// I would have declared an array in the html form and just do a
	// simple loop over the variables.

	if (!empty($client_name_middle)
		AND ($client_name_middle == 'yes' OR $client_name_middle == 'no'))
	{
		$old_client_name_middle = read_meta('client_name_middle');
		if ($client_name_middle != $old_client_name_middle) {
			write_meta('client_name_middle', $client_name_middle);
			array_push($log, "client_name_middle set to "
				. $client_name_middle . ", was " . $old_client_name_middle . ".");
		}
	}

	if (!empty($client_citizen_number)
		AND ($client_citizen_number == 'yes' OR $client_citizen_number == 'no'))
	{
		$old_client_citizen_number = read_meta('client_citizen_number');
		if ($client_citizen_number != $old_client_citizen_number) {
			write_meta('client_citizen_number', $client_citizen_number);
			array_push($log, "client_citizen_number set to "
				. $client_citizen_number . ", was " . $old_client_citizen_number . ".");
		}
	}

	if (!empty($case_court_archive)
		AND ($case_court_archive == 'yes' OR $case_court_archive == 'no'))
	{
		$old_case_court_archive = read_meta('case_court_archive');
		if ($case_court_archive != $old_case_court_archive) {
			write_meta('case_court_archive', $case_court_archive);
			array_push($log, "case_court_archive set to "
				. $case_court_archive . ", was " . $old_case_court_archive . ".");
		}
	}

	if (!empty($case_assignment_date)
		AND ($case_assignment_date == 'yes' OR $case_assignment_date == 'no'))
	{
		$old_case_assignment_date = read_meta('case_assignment_date');
		if ($case_assignment_date != $old_case_assignment_date) {
			write_meta('case_assignment_date', $case_assignment_date);
			array_push($log, "case_assignment_date set to "
				. $case_assignment_date . ", was " . $old_case_assignment_date . ".");
		}
	}

	if (!empty($case_alledged_crime)
		AND ($case_alledged_crime == 'yes' OR $case_alledged_crime == 'no'))
	{
		$old_case_alledged_crime = read_meta('case_alledged_crime');
		if ($case_alledged_crime != $old_case_alledged_crime) {
			write_meta('case_alledged_crime', $case_alledged_crime);
			array_push($log, "case_alledged_crime set to "
				. $case_alledged_crime . ", was " . $old_case_alledged_crime . ".");
		}
	}

	if (!empty($case_allow_modif)
		AND ($case_allow_modif == 'yes' OR $case_allow_modif == 'no'))
	{
		$old_case_allow_modif = read_meta('case_allow_modif');
		if ($case_allow_modif != $old_case_allow_modif) {
			write_meta('case_allow_modif', $case_allow_modif);
			array_push($log, "case_allow_modif set to "
				. $case_allow_modif . ", was " . $old_case_allow_modif . ".");
		}
	}

	if (!empty($fu_sum_billed)
		AND ($fu_sum_billed == 'yes' OR $fu_sum_billed == 'no'))
	{
		$old_fu_sum_billed = read_meta('fu_sum_billed');
		if ($fu_sum_billed != $old_fu_sum_billed) {
			write_meta('fu_sum_billed', $fu_sum_billed);
			array_push($log, "fu_sum_billed set to "
				. $fu_sum_billed . ", was " . $old_fu_sum_billed . ".");
		}
	}

	if (!empty($fu_allow_modif)
		AND ($fu_allow_modif == 'yes' OR $fu_allow_modif == 'no'))
	{
		$old_fu_allow_modif = read_meta('fu_allow_modif');
		if ($fu_allow_modif != $old_fu_allow_modif) {
			write_meta('fu_allow_modif', $fu_allow_modif);
			array_push($log, "fu_allow_modif set to "
				. $fu_allow_modif . ", was " . $old_fu_allow_modif . ".");
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
	if ($conf_modified_general)
		$log = apply_conf_changes_general();
	else if ($conf_modified_collab)
		$log = apply_conf_changes_collab();
	else if ($conf_modified_policy)
		$log = apply_conf_changes_policy();

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

	show_config_form($panel);
	lcm_page_end();
}


?>
