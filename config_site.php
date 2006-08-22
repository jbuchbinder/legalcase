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

	$Id: config_site.php,v 1.53 2006/08/22 18:02:23 mlutfy Exp $
*/

include ("inc/inc.php");

function now_and_before($now, $before) {
	return _T('siteconf_info_now_and_before', array('now' => $now, 'before' => $before));
}

function show_config_form() {
	echo "<p class='normal_text'><img src='images/jimmac/icon_warning.gif' alt='' align='right'
		height='48' width='48' />" . _T('siteconf_warning') . "</p>\n";

	// Show tabs
	$groups = array('general' => _T('siteconf_tab_general'),
			'collab' => _T('siteconf_tab_collab_work'),
			'policy' => _T('siteconf_tab_policy'),
			'regional' => _T('siteconf_tab_regional'));
	$tab = ( isset($_GET['tab']) ? $_GET['tab'] : 'general' );
	//show_tabs($groups,$tab,$_SERVER['REQUEST_URI']);
	show_tabs($groups,$tab,$_SERVER['SCRIPT_NAME']);

	echo "<form name='upd_site_profile' method='post' action='" . $_SERVER['REQUEST_URI'] . "'>\n";
	// echo "<form name='upd_site_profile' method='post' action='config_site.php'>\n";

	switch ($tab) {
		case 'collab' :
			show_config_form_collab();
			break;
		case 'policy' :
			show_config_form_policy();
			break;
		case 'regional' :
			show_config_form_regional();
			break;
		default:
			// case: 'general'
			show_config_form_general();
	}
	
	echo "</form>\n";
}

function show_config_form_general() {
	global $lcm_lang_right;

	$site_name = read_meta('site_name');
	$site_desc = read_meta('site_description');
	$site_address = read_meta('site_address');
	$email_sysadmin = read_meta('email_sysadmin');

	if (empty($site_name))
		$site_name = _T('title_software');

	echo '<input type="hidden" name="conf_modified_general" value="yes" />' . "\n";
	echo '<input type="hidden" name="panel" value="general" />' . "\n";

	echo '<fieldset class="conf_info_box">' . "\n";
	show_page_subtitle(_T('siteconf_subtitle_site_identification'), 'siteconfig_general', 'identification');
	
	echo '<p><b><label for="site_name">' . _T('siteconf_input_site_name') . "</label></b></p>\n";
	echo "<p><small class='sm_11'>" . _T('siteconf_info_site_name') . "</small></p>\n";
	echo "<p><input type='text' id='site_name' name='site_name' value='$site_name' size='40' class='search_form_txt' /></p>\n";

	echo "<p><b><label for='site_desc'>" . _T('siteconf_input_site_desc') .  "</label></b></p>\n";
	echo "<p><small class='sm_11'>" . _T('siteconf_info_site_desc') . "</small></p>\n";
	echo "<p><input type='text' id='site_desc' name='site_desc' value='$site_desc' size='40' class='search_form_txt' /></p>\n";
	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate1' class='simple_form_btn'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

	echo "<fieldset class='conf_info_box'>\n";
	show_page_subtitle(_T('siteconf_subtitle_site_contacts'), 'siteconfig_general', 'contacts');

	echo "<p><b><label for='site_address'>" . _T('siteconf_input_site_address') . "</label></b></p>\n";
	echo "<p><input type='text' id='site_address' name='site_address' value='$site_address' size='40' class='search_form_txt' /></p>\n";

	echo "<p><b><label for='email_sysadmin'>" . _T('siteconf_input_admin_email') . "</label></b></p>\n";
	echo "<p><small class='sm_11'>" . _T('siteconf_info_admin_email') . "</small></p>\n";
	echo "<p><input type='text' id='email_sysadmin' name='email_sysadmin' value='$email_sysadmin' size='40' class='search_form_txt' /></p>\n";
	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate2' class='simple_form_btn'>" .  _T('button_validate') . "</button></p>\n";
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
	
	echo '<fieldset class="conf_info_box">' . "\n";
	show_page_subtitle(_T('siteconf_subtitle_collab_work'), 'siteconfig_collab', 'collab');

	// READ ACCESS
	echo "<p><b>" . _T('siteconf_input_access_read_choice') . "</b></p>\n";

	echo "<ul>";
	// If case_default_read == 'yes' (public)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read' id='case_default_read_1' value='yes'";
	if ($case_default_read == 'yes') echo ' checked="checked"';
	echo "><label for='case_default_read_1'>" .  _T('siteconf_input_access_read_choice_public') . "</label></input></li>\n";

	// If case_default_read != 'yes' (private)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read' id='case_default_read_2' value=''";
	if ($case_default_read == 'no') echo ' checked="checked"';
	echo "><label for='case_default_read_2'>" . _T('siteconf_input_access_read_choice_private') . "</label></input></li>\n";
	echo "</ul>\n";

	// READ ACCESS POLICY
	echo "<p><b>" . _T('siteconf_input_access_read_global') . "</b></p>\n";

	echo "<ul>";
	// Anyone can change the setting (case_read_always != yes)
	echo '<li style="list-style-type: none;"><input type="radio" name="case_read_always" id="case_read_always_2" value=""';
	if ($case_read_always == 'no') echo ' checked="checked"';
	echo '><label for="case_read_always_2">' . _T('siteconf_input_access_read_global_no') . "</label></input></li>\n";

	// Only the admin can change the setting (case_read_always == yes)
	echo '<li style="list-style-type: none;"><input type="radio" name="case_read_always" id="case_read_always_1" value="yes"';
	if ($case_read_always == 'yes') echo ' checked="checked"';
	echo '><label for="case_read_always_1">' . _T('siteconf_input_access_read_global_yes') . "</label></input></li>\n";
	echo "</ul>\n";

	echo "<hr>\n";

	// WRITE ACCESS
	echo "<p><b>" . _T('siteconf_input_access_write_choice') . "</b></p>\n";

	echo "<ul>";
	// If by default write set to public (case_default_write == 'yes')
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write' id='case_default_write_1' value='yes'";
	if ($case_default_write == 'yes') echo ' checked="checked"';
	echo '><label for="case_default_write_1">' . _T('siteconf_input_access_write_choice_public') . "</label></input></li>\n";

	// If by default write not set to public (case_default_write != 'yes')
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write' id='case_default_write_2' value=''";
	if ($case_default_write == 'no') echo ' checked="checked"';
	echo '><label for="case_default_write_2">' . _T('siteconf_input_access_write_choice_private') . "</label></input></li>\n";
	echo "</ul>\n";

	// WRITE ACCESS POLICY
	echo "<p><b>" . _T('siteconf_input_access_write_global') . "</b></p>\n";

	echo "<ul>";
	// Anyone can change the setting (case_write_always != yes)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_write_always' id='case_write_always_2' value=''";
	if ($case_write_always == 'no') echo ' checked="checked"';
	echo '><label for="case_write_always_2">' . _T('siteconf_input_access_write_global_no') . "</label></input></li>\n";

	// Only the admin can change the setting (case_write_always == yes)
	echo "<li style='list-style-type: none;'><input type='radio' name='case_write_always' id='case_write_always_1' value='yes'";
	if ($case_write_always == 'yes') echo ' checked="checked"';
	echo '><label for="case_write_always_1">' . _T('siteconf_input_access_write_global_yes') . "</label></input></li>\n";
	echo "</ul>\n";
	echo "</fieldset>";

	//
	// *** SELF-REGISTRATION
	//
	$site_open_subscription = read_meta('site_open_subscription');
	
	echo "<fieldset class='conf_info_box'>\n";
	show_page_subtitle(_T('siteconf_subtitle_self_registration'), 'siteconfig_collab', 'selfreg');

	echo "<p>" . _T('siteconf_info_self_registration') . "</p>\n";
	echo "<ul>";

	// moderated
	echo "<li style='list-style-type: none;'><input type='radio' name='site_open_subscription' id='site_open_subscription_2' value='moderated'";
	if ($site_open_subscription == 'moderated') echo ' checked="checked"';
	echo " /><label for='site_open_subscription_2'>" . _T('siteconf_input_selfreg_moderated') . "</label></input></li>\n";

	// un-moderated (yes)
	echo "<li style='list-style-type: none;'>";
	echo "<input type='radio' name='site_open_subscription' id='site_open_subscription_1' value='yes'";
	if ($site_open_subscription == 'yes') echo ' checked="checked"';
	echo " /><label for='site_open_subscription_1'>" . _T('siteconf_input_selfreg_yes') . "</label></input></li>\n";

	// no
	echo "<li style='list-style-type: none;'><input type='radio' name='site_open_subscription' id='site_open_subscription_3' value='no'";
	if ($site_open_subscription == 'no') echo ' checked="checked"';
	echo "><label for='site_open_subscription_3'>" . _T('siteconf_input_selfreg_no') . "</label></input></li>\n";

	echo "</ul>\n";
	echo "</fieldset>";
	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate3' class='simple_form_btn'>" .  _T('button_validate') . "</button></p>\n";

}

function show_config_form_regional() {
	global $lcm_lang_right;

	$default_language = read_meta('default_language');
	$currency = read_meta('currency');

	// If no currency format set, get default format from the language translation
	if (empty($currency)) {
		$current_lang = $GLOBALS['lcm_lang'];
		$GLOBALS['lcm_lang'] = $default_language;
		$currency = _T('currency_default_format');
		$GLOBALS['lcm_lang'] = $current_lang;
	}

	echo "<input type='hidden' name='conf_modified_regional' value='yes' />\n";
	echo "<input type='hidden' name='panel' value='regional' />\n";

	echo "<fieldset class='conf_info_box'>\n";
	show_page_subtitle(_T('siteconf_subtitle_regional'), 'siteconfig_regional');

	echo "<p><b>" . _T('siteconf_input_default_lang') . "</b></p>\n";
	echo "<p><small class='sm_11'>" . _T('siteconf_info_default_lang') . "</small></p>\n";
	echo "<p align='center'>" . menu_languages('default_language', $default_language) . "</p>\n";

	echo "<p><b><label for='currency'>" . _T('siteconf_input_currency') . "</label></b></p>\n";
	echo "<p><small class='sm_11'>" . _T('siteconf_info_currency') . ' ' .  _T('siteconf_warning_currency') . "</small></p>\n";
	echo "<p align='center'><input type='text' id='currency' name='currency' value='$currency' size='5' class='search_form_txt' /></p>\n";
	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate4' class='simple_form_btn'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

	echo "<fieldset class='conf_info_box'>\n";
	show_page_subtitle(_T('siteconf_subtitle_refresh_lang'));

	echo "<p>" . _T('siteconf_info_available_languages') . "</p>\n";

	echo "<p align='$lcm_lang_right'><button type='submit' name='validate_refresh' id='Validate5' class='simple_form_btn'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";
}

function show_config_form_policy() {
	global $lcm_lang_right;

	$meta = array (
		'client_name_middle' => '',		'person_name_format' => '',
		'client_citizen_number' => '',	'client_civil_status' => '',
		'client_income' => '', 			'client_date_birth' => '',
		'case_assignment_date' => '', 	'case_alledged_crime' => '',
		'case_legal_reason' => '', 		'case_allow_modif' => '',
		'fu_sum_billed' => '', 			'fu_allow_modif' => '',
		'hide_emails' => '',			'case_new_showorg' => '',
	);

	foreach ($meta as $m => $val)
		$meta[$m] = read_meta($m);

	echo "<input type='hidden' name='conf_modified_policy' value='yes' />\n";
	echo "<input type='hidden' name='panel' value='policy' />\n";

	// ** CLIENTS
	echo "<fieldset class='conf_info_box'>\n";
	show_page_subtitle(_T('siteconf_subtitle_client_fields'), 'siteconfig_policy', 'clients');

	echo '<p><small class="sm_11">' . _T('siteconf_info_client_fields') . "</small></p>\n";

	echo '<table width="99%" class="tbl_usr_dtl">' . "\n";
	echo '<tr><td width="300">' . _T('siteconf_input_name_middle') ."</td>\n"
		. "<td>" . get_yes_no_mand('client_name_middle', $meta['client_name_middle']) .  "</td>\n"
		. "</tr>\n";

	echo "<tr><td>" . _Ti('siteconf_input_citizen_number') ."</td>"
		. "<td>" . get_yes_no_mand('client_citizen_number', $meta['client_citizen_number']) . "</td>"
		. "</tr>\n";

	echo "<tr><td>" . _Ti('person_input_date_birth') ."</td>"
		. "<td>" . get_yes_no_mand('client_date_birth', $meta['client_date_birth']) . "</td>"
		. "</tr>\n";

	echo "<tr><td>" . _Ti('person_input_civil_status') ."</td>"
		. "<td>" . get_yes_no_mand('client_civil_status', $meta['client_civil_status']) . "</td>"
		. "</tr>\n";

	echo "<tr><td>" . _Ti('siteconf_input_client_income') ."</td>"
		. "<td>" . get_yes_no_mand('client_income', $meta['client_income']) . "</td>"
		. "</tr>\n";

	echo "<tr><td>" . _Ti('siteconf_info_hide_emails') . "</td>\n"
		. "<td>" . get_yes_no('hide_emails', $meta['hide_emails']) . "</td>"
		. "</tr>\n";

	echo '<tr><td>' . _T('siteconf_input_name_format') ."</td>\n"
		. "<td>" 
		. '<select name="person_name_format" class="sel_frm">'
		. (! $meta['person_name_format'] ? '<option value=""></option>' : '')
		. '<option value="1"' . isSelected($meta['person_name_format'] == '1') . '>' . _T('siteconf_info_name_format_1') . '</option>'
		. '<option value="10"' . isSelected($meta['person_name_format'] == '10') . '>' . _T('siteconf_info_name_format_10') . '</option>'
		. "</select>\n"
		. "</td>\n"
		. "</tr>\n";

	echo "</table>\n";

	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate6' class='simple_form_btn'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

	// ** CASES
	echo "<fieldset class='conf_info_box'>\n";
	show_page_subtitle(_T('siteconf_subtitle_case_fields'), 'siteconfig_policy', 'cases');

	echo "<p><small class='sm_11'>" . _T('siteconf_info_case_fields') . "</small></p>\n";

	echo "<table width=\"99%\" class=\"tbl_usr_dtl\">";
	echo "<tr><td>" . _Ti('case_input_date_assigned') ."</td>"
		. "<td>" . get_yes_no('case_assignment_date', $meta['case_assignment_date']) . "</td></tr>\n";
	echo "<tr><td> " . _Ti('case_input_alledged_crime') ."</td>"
		. "<td>" . get_yes_no_mand('case_alledged_crime', $meta['case_alledged_crime']) . "</td></tr>\n";
	echo "<tr><td> " . _Ti('case_input_legal_reason') ."</td>"
		. "<td>" . get_yes_no_mand('case_legal_reason', $meta['case_legal_reason']) . "</td></tr>\n";
	echo "<tr><td> " . _T('siteconf_input_case_new_showorg') ."</td>"
		. "<td>" . get_yes_no('case_new_showorg', $meta['case_new_showorg']) . "</td></tr>\n";
	echo "<tr><td>" . _T('siteconf_input_case_allow_modif') ."</td>"
		. "<td>" . get_yes_no('case_allow_modif', $meta['case_allow_modif']) . "</td></tr>\n";
	echo "</table>\n";

	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate6' class='simple_form_btn'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

	// ** FOLLOW-UPS
	echo "<fieldset class='conf_info_box'>\n";
	show_page_subtitle(_T('siteconf_subtitle_followup_fields'), 'siteconfig_policy', 'followups');

	echo "<p><small class='sm_11'>" . _T('siteconf_info_followups_fields') . "</small></p>\n";

	echo "<table width=\"99%\" class=\"tbl_usr_dtl\">";
	echo "<tr><td width=\"300\">" . _T('fu_input_sum_billed') ."</td>"
		. "<td>" . get_yes_no('fu_sum_billed', $meta['fu_sum_billed']) . "</td></tr>\n";
	echo "<tr><td>" . _T('siteconf_input_fu_allow_modif') ."</td>"
		. "<td>" . get_yes_no('fu_allow_modif', $meta['fu_allow_modif']) . "</td></tr>\n";
	echo "</table>\n";

	echo "<p align='$lcm_lang_right'><button type='submit' name='Validate' id='Validate7' class='simple_form_btn'>" .  _T('button_validate') . "</button></p>\n";
	echo "</fieldset>\n";

}

function apply_conf_changes_general() {
	$log = array();

	$site_name = _request('site_name');
	$site_desc = _request('site_desc');
	$site_address = _request('site_address');
	$email_sysadmin = _request('email_sysadmin');

	// Site name
	if (! empty($site_name)) {
		$old_name = read_meta('site_name');
		if (! $old_name) $old_name = _T('title_software');

		if ($old_name != $site_name) {
			write_meta('site_name', $site_name);
			array_push($log, _Ti('siteconf_input_site_name') . now_and_before($site_name, $old_name));
		}
	}

	// Site description (may be empty)
	$old_desc = read_meta('site_description');

	if ($old_desc != $site_desc) {
		write_meta('site_description', $site_desc);
		array_push($log, _Ti('siteconf_input_site_desc') . now_and_before($site_desc, $old_desc));
	}

	// Site address (Internet or LAN)
	$old_address = read_meta('site_address');

	if ($old_address != $site_address) {
		write_meta('site_address', $site_address);
		array_push($log, _Ti('siteconf_input_site_address') . now_and_before($site_address, $old_address));
	}

	// Administrator e-mail
	if (! empty($email_sysadmin)) {
		$old_email = read_meta('email_sysadmin');
		if ($email_sysadmin != $old_email) {
			if (is_valid_email($email_sysadmin)) {
				write_meta('email_sysadmin', $email_sysadmin);
				array_push($log, _Ti('siteconf_input_admin_email')
					. now_and_before(clean_input($email_sysadmin), $old_email));
			} else {
				// FIXME not the best way of showing errors... 
				array_push($log, "Sysadmin e-mail address <tt>"
					. addslashes($email_sysadmin) . "</tt> is <b>not</b> a "
					. "valid address. Modification not applied."); // TRAD
			}
		}
	}

	if (! empty($log))
		write_metas();
	
	return $log;
}

function apply_conf_changes_collab() {
	$log = array();

	$case_default_read  = ($_REQUEST['case_default_read'] == 'yes' ? 'yes' : 'no');
	$case_default_write = ($_REQUEST['case_default_write'] == 'yes' ? 'yes' : 'no');
	$case_read_always   = ($_REQUEST['case_read_always'] == 'yes' ? 'yes' : 'no');
	$case_write_always  = ($_REQUEST['case_write_always'] == 'yes' ? 'yes' : 'no');
	$site_open_subscription = $_REQUEST['site_open_subscription']; // validate later

	// Default read policy
	if ($case_default_read != read_meta('case_default_read')) {
		write_meta('case_default_read', $case_default_read);

		$entry = "Read access to cases set to '<tt>"; // TRAD
		if ($case_default_read == 'yes') $entry .= "public";
		else $entry .= "restricted";
		$entry .= "</tt>'";
		array_push($log, $entry);
	}

	// Default write policy
	if ($case_default_write != read_meta('case_default_write')) {
		write_meta('case_default_write', $case_default_write);

		$entry = "Write access to cases set to '<tt>"; // TRAD
		if ($case_default_write == 'yes') $entry .= "public";
		else $entry .= "restricted";
		$entry .= "</tt>'";
		array_push($log, $entry);
	}

	// Read policy access
	if ($case_read_always != read_meta('case_read_always')) {
		write_meta('case_read_always', $case_read_always);

		$entry = "Read access policy can by changed by <tt>"; // TRAD
		if ($case_read_always == 'yes') $entry .= "admin only";
		else $entry .= "everybody";
		$entry .= "</tt>";
		array_push($log, $entry);
	}

	// Write policy access
	if ($case_write_always != read_meta('case_write_always')) {
		write_meta('case_write_always', $case_write_always);

		$entry = "Write access policy can be changed by <tt>"; // TRAD
		if ($case_write_always == 'yes') $entry .= "admin only";
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
			array_push($log, "New author self-registration changed to " // TRAD
				. "'$site_open_subscription', was '$old_site_open_subscription'.");
		}
	}

	if (! empty($log))
		write_metas();

	return $log;
}

function apply_conf_changes_policy() {
	$log = array();

	$items = array('client_name_middle' => 'person_input_name_middle',
				'client_citizen_number' => 'person_input_citizen_number',
				'client_date_birth'		=> 'person_input_date_birth',
				'client_civil_status'   => 'person_input_civil_status',
				'client_income'         => 'person_input_income',
				'hide_emails'           => 'siteconf_info_hide_emails',
				'case_assignment_date'  => 'case_input_date_assigned',
				'case_alledged_crime'   => 'case_input_alledged_crime',
				'case_legal_reason'		=> 'case_input_legal_reason',
				'case_new_showorg'		=> 'siteconf_input_case_new_showorg',
				'case_allow_modif'      => 'siteconf_input_case_allow_modif', 
				'fu_sum_billed'         => 'fu_input_sum_billed',
				'fu_allow_modif'        => 'siteconf_input_fu_allow_modif');

	$allowed_values = array('yes' => 1, 'yes_mandatory' => 1, 'yes_optional' => 1, 'no' => 1);

	foreach ($items as $it => $trad) {
		if ($allowed_values[_request($it)]) {
			$old_value = read_meta($it);
			if (_request($it) != $old_value) {
				write_meta($it, _request($it));
				array_push($log, _Ti($trad) . now_and_before( _T('info_' . _request($it)), _T('info_' . $old_value) ));
			}
		}
	}

	// Exception
	// Person name format (FML = 1; L,FM = 10)
	$val_pnf = intval(_request('person_name_format'));
	$old_val_pnf = read_meta('person_name_format');

	if ($val_pnf != $old_val_pnf) {
		write_meta('person_name_format', $val_pnf);
		array_push($log, _Ti('siteconf_input_name_format') 
			. now_and_before(_T('siteconf_info_name_format_' . $val_pnf), _T('siteconf_info_name_format_' . $old_val_pnf)));
	}
	
	if (! empty($log))
		write_metas();

	return $log;
}

function apply_conf_changes_regional() {
	$log = array();

	$default_language = $_REQUEST['default_language'];
	$currency = $_REQUEST['currency'];
	$refresh_list_lang = isset($_REQUEST['validate_refresh']);

	// Default language
	if (! empty($default_language)) {
		$old_lang = read_meta('default_language');

		if ($old_lang != $default_language) {
			write_meta('default_language', $default_language);
			array_push($log, _Ti('siteconf_input_default_lang') 
				. now_and_before(translate_language_name($default_language), translate_language_name($old_lang)));
		}
	}

	// Currency
	if (! empty($currency)) {
		$old_currency = read_meta('currency');

		if ($currency != $old_currency) {
			write_meta('currency', $currency);
			array_push($log, _Ti('siteconf_input_currency') . now_and_before($currency, $old_currency));
		}
	}

	// Force refresh of lcm_meta->available_languages
	if ($refresh_list_lang) {
		init_languages(true);
		array_push($log, _T('siteconf_info_languages_refreshed'));
	}

	if (! empty($log))
		write_metas();

	return $log;
}

global $author_session;

// Restrict page to administrators
if ($author_session['status'] != 'admin') {
	lcm_page_start(_T('title_site_configuration'), '', '', 'siteconfig');
	echo '<p class="normal_text">' . _T('warning_forbidden_not_admin') . "</p>\n";
	lcm_page_end();
	exit;
}

if ($_REQUEST['conf_modified_general'])
	$log = apply_conf_changes_general();
else if ($_REQUEST['conf_modified_collab'])
	$log = apply_conf_changes_collab();
else if ($_REQUEST['conf_modified_policy'])
	$log = apply_conf_changes_policy();
else if ($_REQUEST['conf_modified_regional'])
	$log = apply_conf_changes_regional();

// Once ready, show the form (must be done after changes are
// applied so that they can be used in the header).
lcm_page_start(_T('title_site_configuration'), '', '', 'siteconfig');

// Show changes on screen
if (! empty($log)) {
	echo "<div class=\"sys_msg_box\">\n";
	echo "<div>" . _T('siteconf_info_changes_made') . "</div>\n";
	echo "<ul>";

	foreach ($log as $line) {
		echo "<li>" . $line . "</li>\n";
		lcm_log('Author ' . $author_session['id_author'] . ': ' . $line,'config');
	}

	echo "</ul>\n";
	echo "</div>\n";
}

show_config_form();
lcm_page_end();


?>
