<?php

include ("inc/inc.php");

function show_config_form() {
	echo "<div align='left'>\n";

	$site_name = read_meta('site_name');
	$default_language = read_meta('default_language');
	$email_sysadmin = read_meta('email_sysadmin');
	$case_default_read = read_meta('case_default_read');
	$case_default_write = read_meta('case_default_write');
	$site_open_subscription = read_meta('site_open_subscription');

	if (empty($site_name))
		$site_name = _T('title_software');

	echo "<p><small>We might want to put a seperate submit button for each block (or seperate the confs on many pages), it would reduce the risk of error.</small></p>\n";

	echo "<form action='config_site.php' method='post'>\n";

	// *** INFO SITE
	echo "<h3>Information about the site</h3>\n";

	echo "<div style='border: 1px solid #999999; padding: 5px; margin-bottom: 1em;'>\n";

	echo "<p><b>Site name:</b></p>\n";
	echo "<p><small>This will be shown when the user logs-in, in generated reports, etc.</small></p>\n";
	echo "<p><input type='text' id='site_name' name='site_name' value='$site_name' size='40'/></p>\n";

	echo "<p><b>Default language:</b></p>\n";
	echo "<p><small>Language to use if a language could not be detected or chosen (such as for new users).</small></p>\n";
	echo "<p>" . menu_languages('default_language', $default_language) . "\n";

	echo "<p><b>E-mail of site administrator:</b></p>\n";
	echo "<p><small>E-mail of the contact for administrative requests or problems. This e-mail can be a mailing-list.</small></p>\n";
	echo "<p><input type='text' id='email_sysadmin' name='email_sysadmin' value='$email_sysadmin' size='40'/></p>\n";

	echo "</div>\n";

	// *** COLLAB WORD
	echo "<h3>Collaborative work on cases</h3>\n";

	echo "<div style='border: 1px solid #999999; padding: 5px; margin-bottom: 1em;'>\n";

	echo "<p><small>This only applies to new cases. Wording of this page needs fixing.</small></p>\n";

	// READ ACCESS
	echo "<p><b>Read access to cases</b></p>\n";

	echo "<p><small>Cases usually have one or many authors specifically assigned to
		them. It is assumed that assigned authors can consult the case and it's
		follow-ups, but what about authors who are not assigned to the
		case:</small></p>\n";

	echo "<ul>";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read' id='case_default_read_1'><label for='case_default_read_1'>Any author can view the case information of other authors, even if they are not on the case (better cooperation).</label></input></li>\n";

	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read' id='case_default_read_2'><label for='case_default_read_2'>Only authors assigned to a case can view its information and follow-ups (better privacy).</label></input></li>\n";
	echo "</ul>\n";

	echo "<p><b>Who choses read access</b></p>\n";

	echo "<p><small>Authors assigned to a case can decide to change its privacy setting. This avoids mistakes or to enforce a site policy.</small></p>\n";

	echo "<ul>";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read_always' id='case_default_read_always_1'><label for='case_default_read_always_1'>Yes</label></input></li>\n";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_read_always' id='case_default_read_always_2'><label for='case_default_read_always_2'>No, only if they have administrative rights.</label></input></li>\n";
	echo "</ul>\n";

	echo "<hr>\n";
	
	// WRITE ACCESS
	echo "<p><b>Write access to cases</b></p>\n";

	echo "<p><small>Cases usually have one or many authors specifically assigned to
		them. It is assumed that only assigned authors can add follow-up
		information to the case, but what about authors who are not assigned to the
		case:</small></p>\n";

	echo "<ul>";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write' id='case_default_write_1'><label for='case_default_write_1'>Any author can view the case information of other authors, even if they are not on the case (better cooperation).</label></input></li>\n";

	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write' id='case_default_write_2'><label for='case_default_write_2'>Only authors assigned to a case can view its information and follow-ups (better privacy).</label></input></li>\n";
	echo "</ul>\n";

	echo "<p><b>Who choses write access</b></p>\n";

	echo "<p><small>This avoids mistakes or to enforce a site policy.</small></p>\n";

	echo "<ul>";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write_always' id='case_default_write_always_1'><label for='case_default_write_always_1'>Any author assigned to the case</label></input></li>\n";
	echo "<li style='list-style-type: none;'><input type='radio' name='case_default_write_always' id='case_default_write_always_2'><label for='case_default_write_always_2'> administrative rights.</label></input></li>\n";
	echo "</ul>\n";

	echo "</div>\n";

	echo "</form>\n";

	echo "</div>\n";
}

lcm_page_start("Site configuration");

global $author_session;

if ($author_session['status'] != 'admin') {
	echo "<p>Warning: Access denied, not admin\n";
} else {
	// XXX If any actions, do them here

	// Once ready, show the form
	show_config_form();
}

lcm_page_end();

?>
