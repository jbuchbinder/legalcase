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
	$site_name = read_meta('site_name');
	$site_desc = read_meta('site_description');
	$default_language = read_meta('default_language');
	$email_sysadmin = read_meta('email_sysadmin');
	$case_default_read = read_meta('case_default_read');
	$case_default_write = read_meta('case_default_write');
	$case_read_always = read_meta('case_read_always');
	$case_write_always = read_meta('case_write_always');
	$site_open_subscription = read_meta('site_open_subscription');

	if (empty($site_name))
		$site_name = _T('title_software');
	
	# if (empty($site_desc))
	#	$site_desc = _T('title_software_description');

	echo "<form name='upd_site_profile' method='post' action='config_site.php'>\n";
	echo "\t<input type='hidden' name='conf_modified' value='yes'/>\n";

	// *** INFO SITE
	echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";
	echo "<tr>\n";
	echo '<td colspan="2" align="center" valign="middle" class="heading"><h4>';
	echo "Information about the site";
	echo "</h4></td>\n";
	echo "<tr>\n";
	echo "<td>";

	echo "<p><b>Site name:</b></p>\n";
	echo "<p><small>This will be shown when the user logs-in, in generated reports, etc.</small></p>\n";
	echo "<p><input type='text' id='site_name' name='site_name' value='$site_name' size='40'/></p>\n";

	echo "<p><b>Site description:</b></p>\n";
	echo "<p><small>Often shown under the site name, except on reports.</small></p>\n";
	echo "<p><input type='text' id='site_desc' name='site_desc' value='$site_desc' size='40'/></p>\n";

	echo "<p><b>Default language:</b></p>\n";
	echo "<p><small>Language to use if a language could not be detected or chosen (such as for new users).</small></p>\n";
	echo "<p>" . menu_languages('default_language', $default_language) . "\n";

	echo "<p><b>E-mail of site administrator:</b></p>\n";
	echo "<p><small>E-mail of the contact for administrative requests or problems. This e-mail can be a mailing-list.</small></p>\n";
	echo "<p><input type='text' id='email_sysadmin' name='email_sysadmin' value='$email_sysadmin' size='40'/></p>\n";
	echo "</td>\n</tr>\n</table>\n";

	// *** COLLAB WORD
	echo '<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">' . "\n";
	echo "<tr>\n";
	echo '<td colspan="2" align="center" valign="middle" class="heading"><h4>';
	echo "Collaborative work";
	echo "</h4></td>\n";
	echo "<tr>\n";
	echo "<td>";

	# echo "<h3>Collaborative work on cases</h3>\n";
	# echo "<div style='border: 1px solid #999999; padding: 5px; margin-bottom: 1em;'>\n";

	echo "<p><small>This only applies to new cases. Wording of this page needs fixing.</small></p>\n";

	// READ ACCESS
	echo "<p><b>Read access to cases</b></p>\n";

	echo "<p>Who can view case information?<br>
<small>(Cases usually have one or many authors specifically assigned to them. It is assumed that assigned authors can consult the case and it's follow-ups, but what about authors who are not assigned to the case?)</small></p>\n";

	echo "<ul>";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read' id='case_default_read_1' value='1'";
	if ($case_default_read) echo " checked";
	echo "><label for='case_default_read_1'>Any author can view the case information of other authors, even if they are not on the case (better cooperation).</label></input></li>\n";

	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read' id='case_default_read_2' value=''";
	if (!$case_default_read) echo " checked";
	echo "><label for='case_default_read_2'>Only authors assigned to a case can view its information and follow-ups (better privacy).</label></input></li>\n";
	echo "</ul>\n";

	echo "<p><b>Who choses read access</b></p>\n";

	echo "<p>Can authors, assigned to a case, decide to change its privacy setting?<br>
<small>(This is used to avoid mistakes or to enforce a site policy.)</small></p>\n";

	echo "<ul>";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_read_always' id='case_read_always_1' value=''";
	if (!$case_read_always) echo " checked";
	echo "><label for='case_read_always_1'>Yes</label></input></li>\n";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_read_always' id='case_read_always_2' value='1'";
	if ($case_read_always) echo " checked";
	echo "><label for='case_read_always_2'>No, except if they have administrative rights.</label></input></li>\n";
	echo "</ul>\n";

	echo "<hr>\n";

	// WRITE ACCESS
	echo "<p><b>Write access to cases</b></p>\n";

	echo "<p>Who can write information in the cases?<br>
<small>(Cases usually have one or many authors specifically assigned to them. It is assumed that only assigned authors can add follow-up information to the case, but what about authors who are not assigned to the case?)</small></p>\n";

	echo "<ul>";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write' id='case_default_write_1' value='1'";
	if ($case_default_write) echo " checked";
	echo "><label for='case_default_write_1'>Any author can write the case information of other authors, even if they are not on the case (better cooperation).</label></input></li>\n";

	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write' id='case_default_write_2' value=''";
	if (!$case_default_write) echo " checked";
	echo "><label for='case_default_write_2'>Only authors assigned to a case can write its information and follow-ups (better privacy).</label></input></li>\n";
	echo "</ul>\n";

	echo "<p><b>Who choses write access</b></p>\n";

	echo "<p>Can authors of the case change write access rights?<br>
<small>(This is used to avoid mistakes or to enforce a site policy.)</small></p>\n";

	echo "<ul>";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_write_always' id='case_write_always_1' value=''";
	if (!$case_write_always) echo " checked";
	echo "><label for='case_write_always_1'>Yes</label></input></li>\n";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_write_always' id='case_write_always_2' value='1'";
	if ($case_write_always) echo " checked";
	echo "><label for='case_write_always_2'>No, except if they have administrative rights.</label></input></li>\n";
	echo "</ul>\n";
	echo "</td>\n</tr>\n</table>\n";

	echo "<p><input type='submit' name='Validate' id='Validate' value='Validate'/></p>\n";

	echo "</form>\n";
}

function apply_conf_changes() {
	$log = array();

	global $site_name;
	global $site_desc;
	global $default_language;
	global $case_default_read;
	global $case_default_write;
	global $case_read_always;
	global $case_write_always;

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

	// TODO: admin email

	// TODO: Collab word

	// Default read policy
	if ($case_default_read != read_meta('case_default_read')) {
		write_meta('case_default_read',$case_default_read);
		$entry = "Read access to cases set to '<tt>";
		if ($case_default_read) $entry .= "public";
		else $entry .= "restricted";
		$entry .= "</tt>'";
		array_push($log, $entry);
	}

	// Default write policy
	if ($case_default_write != read_meta('case_default_write')) {
		write_meta('case_default_write',$case_default_write);
		$entry = "Write access to cases set to '<tt>";
		if ($case_default_write) $entry .= "public";
		else $entry .= "restricted";
		$entry .= "</tt>'";
		array_push($log, $entry);
	}

	// Read policy access
	if ($case_read_always != read_meta('case_read_always')) {
		write_meta('case_read_always',$case_read_always);
		$entry = "Read access policy can by changed by <tt>";
		if ($case_read_always) $entry .= "admin only";
		else $entry .= "everybody";
		$entry .= "</tt>";
		array_push($log, $entry);
	}

	// Write policy access
	if ($case_write_always != read_meta('case_write_always')) {
		write_meta('case_write_always',$case_write_always);
		$entry = "Write access policy can be changed by <tt>";
		if ($case_write_always) $entry .= "admin only";
		else $entry .= "everybody";
		$entry .= "</tt>";
		array_push($log, $entry);
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
	lcm_page_start("Site configuration");

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
