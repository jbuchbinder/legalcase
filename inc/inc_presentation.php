<?php

//
// Execute this file only once
if (defined('_INC_PRESENTATION')) return;
define('_INC_PRESENTATION', '1');

include_lcm('inc_filters');
include_lcm('inc_text');
include_lcm('inc_lang');

use_language_of_visitor();

//
// Header / Footer functions
//


// Presentation of the interface, headers and "<head></head>".
// XXX You may want to use lcm_page_start() instead.
function lcm_html_start($title = "AUTO", $css_files = "", $meta = '') {
	global $lcm_lang_rtl, $lcm_lang_left;
	global $mode;
	global $connect_status;
	global $prefs;
		
	$lcm_site_name = clean_input(_T(read_meta('site_name')));
	$title = textebrut($title);

	// Don't show site name (if none) while installation
	if (!$lcm_site_name && $title == "AUTO")
		$lcm_site_name = _T('title_software');

	if (!$charset = read_meta('charset'))
		$charset = 'utf-8';

	@Header("Expires: 0");
	@Header("Cache-Control: no-cache,no-store");
	@Header("Pragma: no-cache");
	@Header("Content-Type: text/html; charset=$charset");
	
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
	<title>". ($lcm_site_name ? $lcm_site_name . " | " : '') . $title ."</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=". $charset ."\" />\n";
	echo "$meta\n";
	echo "	<link rel=\"stylesheet\" type=\"text/css\" href=\"styles/lcm_basic_layout.css\" media=\"screen\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"styles/lcm_print.css\" media=\"print\" />\n";

	//
	// Style sheets
	//

	if (@file_exists("styles/lcm_ui_" . $prefs['theme'] . ".css")) {
		echo "\t" . '<link rel="stylesheet" type="text/css" media="screen" href="styles/lcm_ui_' . $prefs['theme'] . '.css" />' . "\n";
	} else {
		echo "\t" . '<link rel="stylesheet" type="text/css" media="screen" href="styles/lcm_ui_default.css" />' . "\n";
	}
	
	// It is the responsability of the function caller to make sure that
	// the filename does not cause problems...
	$css_files_array = explode(",", $css_files);
	foreach ($css_files_array as $f)
		if ($f)
			echo "\t" . '<link rel="stylesheet" type="text/css" href="styles/lcm_' . $f . '.css" />' . "\n";
	
	// linking the alternate CSS files with smaller and larger font size
	
	//There must be one active font size CSS file
	switch($prefs['font_size'])
	{
		case "small_font":
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"styles/lcm_opt_smallfonts.css\" />\n";
		break;
		
		case "large_font":
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"styles/lcm_opt_largefonts.css\" />\n";
		break;
		
		case "medium_font":
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"styles/lcm_opt_mediumfonts.css\" />\n";
		break;
		
		default:
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"styles/lcm_opt_mediumfonts.css\" />\n";
		break;
	}
	
	echo "\t<link rel=\"alternate stylesheet\" type=\"text/css\" href=\"styles/lcm_opt_smallfonts.css\" title=\"small_font\" />\n";
	echo "\t<link rel=\"alternate stylesheet\" type=\"text/css\" href=\"styles/lcm_opt_mediumfonts.css\" title=\"medium_font\" />\n";
	echo "\t<link rel=\"alternate stylesheet\" type=\"text/css\" href=\"styles/lcm_opt_largefonts.css\" title=\"large_font\" />\n";
	
	echo "\t<script type=\"text/javascript\" language=\"JavaScript\" src=\"inc/ss_switcher.js\"></script>\n";
				
	echo "</head>\n";

	// right-to-left (Arabic, Hebrew, Farsi, etc. -- even if not supported at the moment)
	echo '<body' . ($lcm_lang_rtl ? ' dir="rtl"' : '') . ">\n";
}

function lcm_page_start($title = "", $css_files = "", $meta = '') {
	global $connect_id_auteur;
	global $connect_status;
	global $auth_can_disconnect, $connect_login;
	global $options;
	global $lcm_lang, $lcm_lang_rtl, $lcm_lang_left, $lcm_lang_right;
	global $clean_link;
	
	global $prefs;

	// Clean the global link (i.e. remove actions passed in the URL)
	$clean_link->delVar('var_lang');
	$clean_link->delVar('set_options');
	$clean_link->delVar('set_couleur');
	$clean_link->delVar('set_disp');
	$clean_link->delVar('set_ecran');

	lcm_html_start($title, $css_files, $meta);

	//
	// Title (mandatory) and description (may be empty) of the site
	//

	$site_name = _T(read_meta('site_name'));
	if (!$site_name)
		$site_name = _T('title_software');

	$site_desc = _T(read_meta('site_description'));
	
	//
	// Most of the header/navigation html
	//
	
	echo "<div id='header'>
		<h1 class='lcm_main_head'><a href='summary.php' class='head_ttl_link'>" . $site_name . "</a></h1>
		<div class='lcm_slogan'><a href='summary.php' class='head_subttl_link'>" . $site_desc . "</a></div>
	</div>";
	
	/*
	if($prefs['screen'] == "narrow")
	{
		//data from the refs_column - user name, links [My preferences] & [Logout]
		echo "<div id=\"user_info_box_large_screen\">";
		echo "<p class=\"prefs_column_text\"><strong>Name: </strong>"
				. "<a href=\"edit_author.php?author=" .  $author_session['id_author'] . "\" class=\"prefs_normal_lnk\">"
				. $author_session['name_first'] . ' '
				. $author_session['name_middle'] . ' '
				. $author_session['name_last']
				. "</a><br /><br />
			<a href=\"config_author.php\" class=\"prefs_bold_lnk\">[ My preferences ]</a>&nbsp;&nbsp;&nbsp;<a href=\"lcm_cookie.php?logout=".  $author_session['username'] ."\" class=\"prefs_bold_lnk\">[ Logout ]</a>
			</p>";
		echo "</div>";
	}
	*/
	
	echo "<div id='wrapper_". $prefs['screen'] ."'>
		<div id=\"container_". $prefs['screen'] ."\">
			<div id=\"content_". $prefs['screen'] ."\">
			<!-- This is the navigation column, usually used for menus and brief information -->
				<div id=\"navigation_menu_column\">
				<!-- Start of navigation_menu_column content -->
					<div class=\"nav_menu_box\">
						<div class=\"nav_column_menu_head\"><div class=\"mm_main_menu\">"
							. _T('menu_main') . "</div>
							</div>
						<ul class=\"nav_menu_list\">
							<li><a href=\"listcases.php\" class=\"main_nav_btn\">" . _T('menu_main_cases') . "</a></li>
							<li><a href=\"listclients.php\" class=\"main_nav_btn\">" . _T('menu_main_clients') . "</a></li>
							<li><a href=\"listorgs.php\" class=\"main_nav_btn\">" . _T('menu_main_org') . "</a></li>
							<li><a href=\"listauthors.php\" class=\"main_nav_btn\">" . _T('menu_main_authors') . "</a></li>";
							/*
							if($prefs['screen'] == "narrow") {
								echo "<li><a href=\"config_author.php\" class=\"main_nav_btn\">" . _T('menu_profile_preferences') . "</a></li>\n";
							}
							*/
	echo "
						</ul>
					</div>\n";

	if ($connect_status == 'admin') {
		echo "		
					<div class=\"nav_menu_box\">
						<div class=\"nav_column_menu_head\"><div class=\"mm_admin\">" . _T('menu_admin') . "</div></div>
						<ul class=\"nav_menu_list\">
							<li><a href=\"config_site.php\" class=\"main_nav_btn\">" . _T('menu_admin_siteconf') . "</a></li>
							<li><a href=\"archive.php\" class=\"main_nav_btn\">" .  _T('menu_admin_archives') . " <abbr title=\"All cases, categorised by date, keyword, etc. (admin only)\"></abbr></a></li>
							<li><a href=\"listreps.php\" class=\"main_nav_btn\">" . _T('menu_admin_reports') . " <abbr title=\"Manage reports (admin only)\"></abbr></a></li>
							<!-- [ML] li><a href=\"listfilters.php\" class=\"main_nav_btn\">" . _T('menu_admin_filters') . " <abbr title=\"Manage filters (admin only)\"></abbr></a></li -->
							<li><a href=\"keywords.php\" class=\"main_nav_btn\">" .  _T('menu_admin_keywords') . "</a></li>
							<!-- [ML] li><a href=\"export_db.php\" class=\"main_nav_btn\">" .  _T('menu_admin_export_db') . "</a></li -->
							<!-- [ML] li><a href=\"import_db.php\" class=\"main_nav_btn\">" .  _T('menu_admin_import_db') . "</a></li -->
						</ul>
					</div>\n";
	}

	// Show today's date
	echo "\n";
	echo "<div class=\"nav_menu_box\">
						<div class=\"nav_column_menu_head\">
							<div class=\"mm_calendar\">" . _T('menu_calendar') . "</div>
						</div>
						<p class=\"nav_column_text\">". format_date() ."</p>
					</div>\n";
	
	// Start agenda box
	echo '<div class="nav_menu_box">' . "\n";
		echo '<div class="nav_column_menu_head">';
			echo '<div class="mm_agenda">';
			echo '<a href="listapps.php">'. _T('menu_agenda') . '</a>
				</div>
			</div>';

	// Show appointments for today
	$q = "SELECT lcm_app.id_app,start_time,type,title
			FROM lcm_app, lcm_author_app as a
			WHERE (a.id_author=" . $GLOBALS['author_session']['id_author'] . "
				AND lcm_app.id_app=a.id_app
				AND start_time LIKE '" . date('Y-m-d') ."%')
			ORDER BY reminder ASC";

	$result = lcm_query($q);

	if (lcm_num_rows($result) > 0) {
		echo "<p class=\"nav_column_text\">
					<strong>Today</strong><br />\n";
		while ($row=lcm_fetch_array($result)) {
			echo "<a href=\"app_det.php?app=" . $row['id_app'] . "\">"
				. heures($row['start_time']) . ':' . minutes($row['start_time']) . " - " . $row['title'] . "</a><br />\n";
		}
		//					9:30 - Meeting with Mr. Smith<br /><br />
		//					11:00 - At the court
		echo "</p>\n";
		echo "<hr class=\"hair_line\" />\n";
	}

	// Show next appointments
	$q = "SELECT lcm_app.id_app,start_time,type,title
		FROM lcm_app, lcm_author_app as a
		WHERE (a.id_author=" . $GLOBALS['author_session']['id_author'] . "
			AND lcm_app.id_app=a.id_app
			AND start_time>='" . date('Y-m-d H:i:s',((int) ceil(time()/86400)) * 86400) ."')
		ORDER BY reminder ASC
		LIMIT 5";

	$result = lcm_query($q);

	if (lcm_num_rows($result)>0) {
		echo "<p class=\"nav_column_text\">
				<strong>Next appointments</strong><br />\n";
		while ($row=lcm_fetch_array($result)) {
			echo "							<a href=\"app_det.php?app=" . $row['id_app'] . "\">"
				. format_date($row['start_time'],'short') . " - " . $row['title'] . "</a><br />\n";
		}
		//					8:30 - Meeting with Mr. Johnson<br /><br />
		//					10:00 - At the court
		echo "						</p>\n";
	} else {
		echo '<p class="nav_column_text">' . "No events" . "</p>\n";
	}

	// End of nav_menu_box for Agenda
	echo "</div>\n";

	// End of "navigation_menu_column" content
	echo "</div>

				<!-- The main content will be here - all the data, html forms, search results etc. -->
				<div id=\"main_column\">
				
					<!-- Start of 'main_column' content -->
					<h3 class=\"content_head\">". $title ."</h3>
					<!-- [KM] Just a small experiment how the future breadcrumb will look like -->
					<!-- div id=\"breadcrumb\"><a href=\"#\" title=\"Test link\">Home</a> &gt; <a href=\"#\" title=\"Test link\">Page1</a> &gt; <a href=\"#\" title=\"Test link\">Subpage1</a> &gt; Subsubpage1</div -->
	";
}

// Footer of the interface
// XXX You may want to use lcm_page_end() instead
function lcm_html_end() {
	// Create a new session cookie if the IP changed
	// An image is sent, which then calls lcm_cookie.php with Javascript
	if ($GLOBALS['lcm_session'] && $GLOBALS['author_session']['ip_change']) {
		echo "<img name='img_session' src='images/lcm/nothing.gif' width='0' height='0' />\n";
		echo "<script type='text/javascript'><!-- \n";
		echo "document.img_session.src='lcm_cookie.php?change_session=oui';\n";
		echo "// --></script>\n";
	}
	
	flush();
}


function lcm_page_end($credits = '') {
	global $lcm_version_shown;
	global $connect_id_auteur;

	global $author_session;
	global $find_org_string;
	global $find_case_string;
	global $find_client_string;
	
	global $prefs;
	
	//[KM] The bottom of a single page
	//
	echo "
				<!-- End of 'main_column' content -->
				</div>
			</div>
		</div>

<!-- The initial intention was that here can be placed some UI preferences -->
<!-- but I think it will be much better to put the search boxes -->
<!-- The right and the left column can be very long, so, we can put here a lot of additional information, some tiny help hints and so -->";

// Checking for "wide/narrow" user screen
if($prefs['screen'] == "wide") {
		echo "<div id=\"prefs_column\">
<!-- Start of \"prefs_column\" content -->
			<div class=\"prefs_column_menu_head\"><div class=\"sm_profile\">" . _T('menu_profile') . "</div>
			</div>
			<p class=\"prefs_column_text\">"
				. "<a href=\"edit_author.php?author=" .  $author_session['id_author'] . "\" class=\"prefs_normal_lnk\">"
				. $author_session['name_first'] . ' '
				. $author_session['name_middle'] . ' '
				. $author_session['name_last']
				. "</a><br /><br />
			<a href=\"config_author.php\" class=\"prefs_myprefs\">" .  _T('menu_profile_preferences') . "</a><br /><br /><a href=\"lcm_cookie.php?logout=".  $author_session['username'] ."\" class=\"prefs_logout\">" . _T('menu_profile_logout') . "</a>
			</p><br />
			<div class=\"prefs_column_menu_head\"><div class=\"sm_search\">" . _T('menu_search') . "</div>
			</div>
			<form name=\"frm_find_case\" class=\"search_form\" action=\"listcases.php\" method=\"post\">
			<p class=\"prefs_column_text\">
			" . _T('input_search_case') . "<br />
			<input type=\"text\" name=\"find_case_string\" size=\"10\" class=\"search_form_txt\"";

	if (isset($find_case_string))
		echo " value='$find_case_string'";

	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"" . _T('button_search') . "\" class=\"search_form_btn\" />
			</p>
			</form>
			<form name=\"frm_find_client\" class=\"search_form\" action=\"listclients.php\" method=\"post\">
			<p class=\"prefs_column_text\">
			" . _T('input_search_client') . "<br />
			<input type=\"text\" name=\"find_client_string\" size=\"10\" class=\"search_form_txt\"";

	if (isset($find_client_string)) 
		echo " value='$find_client_string'";

	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"" . _T('button_search') . "\" class=\"search_form_btn\" />
			</p>
			</form>
			<form name=\"frm_find_client\" class=\"search_form\" action=\"listorgs.php\" method=\"post\">
			<p class=\"prefs_column_text\">
			" . _T('input_search_organisation') . "<br />
			<input type=\"text\" name=\"find_org_string\" size=\"10\" class=\"search_form_txt\"";

	if (isset($find_org_string))
		echo " value='$find_org_string'";

	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"" . _T('button_search') . "\" class=\"search_form_btn\" />
			</p>
			</form><br />
			<!-- the font size experiment -->
			<div class=\"prefs_column_menu_head\"><div class=\"sm_font_size\">Font size</div>
			</div>
				<ul class=\"font_size_buttons\">
					<li><a href=\"javascript:;\" title=\"Small Text\" onclick=\"setActiveStyleSheet('small_font')\">A-</a></li>
					<li><a href=\"javascript:;\" title=\"Normal Text\" onclick=\"setActiveStyleSheet('medium_font')\">A</a></li>
					<li><a href=\"javascript:;\" title=\"Large Text\" onclick=\"setActiveStyleSheet('large_font')\">A+</a></li>
				</ul>
		<!-- End of \"prefs_column\" content -->
		</div>";
//end of user screen IF

} else {

	//data from the refs_column - user name, links [My preferences] & [Logout]
		echo "<div id=\"user_info_box_large_screen\">";
		echo "<p class=\"prefs_column_text\">"
				. "<a href=\"edit_author.php?author=" .  $author_session['id_author'] . "\" class=\"prefs_normal_lnk\">"
				. $author_session['name_first'] . ' '
				. $author_session['name_middle'] . ' '
				. $author_session['name_last']
				. "</a><br /><br />
			<a href=\"config_author.php\" class=\"prefs_myprefs\">" .  _T('menu_profile_preferences') . "</a><br /><br /><a href=\"javascript:;\" title=\"Small Text\" onclick=\"setActiveStyleSheet('small_font')\" class=\"set_fnt_sz\">&nbsp;A-&nbsp;</a>&nbsp;
				<a href=\"javascript:;\" title=\"Normal Text\" onclick=\"setActiveStyleSheet('medium_font')\" class=\"set_fnt_sz\">&nbsp;A&nbsp;&nbsp;</a>&nbsp;
				<a href=\"javascript:;\" title=\"Large Text\" onclick=\"setActiveStyleSheet('large_font')\" class=\"set_fnt_sz\">&nbsp;A+&nbsp;</a>&nbsp;&nbsp;<a href=\"lcm_cookie.php?logout=".  $author_session['username'] ."\" class=\"prefs_logout\">" . _T('menu_profile_logout') . "</a>
			</p>";
		echo "</div>";
}

		//just test...
		echo "<div class=\"clearing\">&nbsp;</div>
	</div>";

if($prefs['screen'] == "narrow")
{
	echo '<div id="footer_narrow">
	<div class="prefs_column_menu_head"><div class="sm_search">' .  _T('menu_search') . "</div></div>
	<table border=\"0\" align=\"center\" width=\"100%\">
		<tr>
			<td align=\"left\" valign=\"top\">

			<form name=\"frm_find_case\" class=\"search_form\" action=\"listcases.php\" method=\"post\">
			" . _T('input_search_case') . '<br />
			<input type="text" name="find_case_string" size="10" class="search_form_txt"';
	if (isset($find_case_string)) echo ' value="' . $find_case_string . '"';
	echo ' />&nbsp;<input type="submit" name="submit" value="' . _T('button_search') . "\" class=\"search_form_btn\" />
			</form>

			</td>
			<td align=\"left\" valign=\"top\">

			<form name=\"frm_find_client\" class=\"search_form\" action=\"listclients.php\" method=\"post\">
			" . _T('input_search_client') . "<br />
			<input type=\"text\" name=\"find_client_string\" size=\"10\" class=\"search_form_txt\"";
	if (isset($find_client_string)) echo " value='$find_client_string'";
	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"" . _T('button_search') . "\" class=\"search_form_btn\" />
			</form>

			</td>
			<td align=\"left\" valign=\"top\">
			<form name=\"frm_find_client\" class=\"search_form\" action=\"listorgs.php\" method=\"post\">
			" . _T('input_search_organisation') . "<br />
			<input type=\"text\" name=\"find_org_string\" size=\"10\" class=\"search_form_txt\"";
	if (isset($find_org_string)) echo " value='$find_org_string'";
	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"" . _T('button_search') . "\" class=\"search_form_btn\" />
			</form>
			</td>
		</tr>
	</table>
	</div><br />";
}
	echo "<div id=\"footer\">". _T('title_software') ." (". $lcm_version_shown .")<br/> ". _T('info_free_software') ."</div>\n";

	//
	// Language choice (temporarely put here by [ML])
	//
	/* [ML] No longuer necessary
	if ($GLOBALS['all_langs']) {
		echo "<br/><div align=\"right\">" . menu_languages('var_lang_lcm') .  "</div>\n";
	}
	*/

	echo "
</body>
</html>";

	// [ML] Off-topic note, seen while removing code:
	// http://www.dynamicdrive.com/dynamicindex11/abox.htm

	lcm_html_end();
}

/*
 * Header function for the installation
 * They are used by install.php and lcm_test_dirs.php
 */
function install_html_start($title = 'AUTO', $css_files = "") {
	global $lcm_lang_rtl;

	if ($title == 'AUTO')
		$title = _T('install_title_installation_start');

	$css_files = ($css_files ? $css_files . ",install" : "install");

	lcm_html_start($title, $css_files);

	echo "\t<br/>\n";
	echo "\t<div align='center'>\n";
	echo "\t\t<div align='left' style='width: 450px;'>\n";
	echo "\t\t<h1><b>$title</b></h1>\n";

	echo "\n<!-- END install_html_start() -->\n\n";
}

/*
 * Footer function for the installation
 * They are used by install.php and lcm_test_dirs.php
 */
function install_html_end() {
	echo "
			</div>
		</div>
	</body>
	</html>
";
}

//
// Help
//

function lcm_help($code) {
	global $lcm_lang;

	$topic = _T('help_title_' . $code);

	return '<div align="right"><a href="lcm_help.php?code=' . $code . '" target="lcm_help" ' 
		. 'onclick="javascript:window.open(this.href, \'lcm_help\', \'scrollbars=yes, resizable=yes, width=740, height=580\'); return false;">'
		. '<img src="images/lcm/help.png" alt="help: ' . $topic . '" '
		. 'title="help: ' . $topic . '" width="12" height="12" border="0" align="middle" /> '
		. "</a></div>\n"; 
}


//
// Help pages HTML header & footer
//

function help_page_start($page_title) {

	if (!$charset = read_meta('charset'))
		$charset = 'utf-8';

	$toc = array('installation', 'cases', 'clients', 'authors', 'siteconfig', 'archives', 'reports', 'keywords', 'about'); 

	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>LCM | Help</title>
<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '" />
<link rel="stylesheet" href="styles/lcm_basic_layout.css" type="text/css" />
<link rel="stylesheet" href="styles/lcm_opt_mediumfonts.css" type="text/css" />
</head>';

	echo "
<body>
<h1 class=\"hlp_h1\">Legal Case Management Help</h1>
<div id=\"hlp_big_box\">
	<div id=\"hlp_menu\">
		<ul>";

	foreach ($toc as $topic) {
		echo '<li><a href="lcm_help?code=' . $topic .'">' . _T('help_title_' . $topic) . '</a></li>' . "\n";
	}
	
	echo "
		</ul>
	</div>
	<div id=\"hlp_cont\">
		<h2 class=\"hlp_h2\">" . $page_title . "</h2>";

}

function help_page_end() {

	echo "</div>
</div>
</body>
</html>";

}


//
// Commonly used visual functions
//

function get_date_inputs($name = 'select', $date = '', $blank = true, $table = false) {
	// TODO: Add global variables (optional) in my_options.php to
	// modify the date range.

	// Date and month have no default selection, Year does
	$split_date = recup_date($date);
	$default_month = $split_date[1];
	$default_day = $split_date[2];
	$default_year = $split_date[0];

	// If name is empty, disable fields
	$dis = (($name) ? '' : 'disabled');

	$ret = '';
	if ($table)
		$ret .= "<table cellpadding=\"3\" cellspacing=\"3\">\n"
			. "<tr>\n"
			. "<td><!-- " . _T('select_date_day') . "<br/ -->\n";
	$ret .= "<select $dis name=\"" . $name . "_day\" id=\"" . $name . "_day\">\n";

	// Day of month
	for ($i = 1; $i <= 31; $i++) {
		$default = ($i == $default_day ? ' selected="selected"' : '');
		$ret .= "<option" . $default . " value=\"" . $i . "\">" . $i . "</option>\n";
	}

	if ($blank) {
		$default = ($default_day == 0 ? ' selected="selected"' : '');
		$ret .= "<option" . $default . " value=\"\"></option>\n";
	}

	// Month of year
	$ret .= "</select>\n";
	if ($table)
		$ret .= "</td>\n"
			. "<td><!-- " . _T('select_date_month') . "<br/ -->\n";
	$ret .= "<select $dis name=\"" . $name . "_month\" id=\"" . $name . "_month\">\n";

	for ($i = 1; $i <= 12; $i++) {
		$default = ($i == $default_month ? ' selected="selected"' : '');
		$ret .= "<option" . $default . " value=\"" . $i . "\">" . _T('date_month_' . $i) . "</option>\n";
	}

	if ($blank) {
		$default = ($default_month == 0 ? ' selected="selected"' : '');
		$ret .= "<option" . $default . " value=\"\"></option>\n";
	}

	// Year
	$ret .= "</select>\n";
	if ($table)
		$ret .= "</td>\n"
			. "<td><!-- " . _T('select_date_year') . "<br/ -->\n";
	$ret .= "<select $dis name=\"" . $name . "_year\" id=\"" . $name . "_year\">\n";

	for ($i = 1999; $i <= 2006; $i++) {
		$default = ($i == $default_year ? ' selected="selected"' : '');
		$ret .= "<option" . $default . " value=\"" . $i . "\">" . $i . "</option>\n";
	}

	if ($blank) {
		$default = ($default_year == 0 ? ' selected="selected"' : '');
		$ret .= "<option" . $default . " value=\"\"></option>\n";
	}

	$ret .= "</select>\n";
	if ($table)
		$ret .= "</td>\n"
			. "</tr>\n"
			. "</table>\n";

	return $ret;
}

function get_time_inputs($name = 'select', $time = '', $hours24 = true, $show_seconds = false, $table = false) {

	$split_time = recup_time($time);
	$default_hour = $split_time[0];
	$default_minutes = $split_time[1] - ($split_time[1] % 5); // make it round
	$default_seconds = $split_time[2];

	// If name is empty, disable fields
	$dis = (($name) ? '' : 'disabled');

	$ret = '';

	// Hour
	if ($table)
		$ret .= "<table cellpadding=\"3\" cellspacing=\"3\">\n"
			. "<tr>\n"
			. "<td><!-- " . _T('select_time_hour') . "<br/ -->\n";

	$ret .= "<select $dis name=\"" . $name . "_hour\" id=\"" . $name . "_hour\" align=\"right\">\n";

	for ($i = 0; $i < 24; $i++) {
		$default = ($i == $default_hour ? ' selected="selected"' : '');
		$ret .= "<option" . $default . " value=\"" . sprintf('%02u',$i) . "\">";
		if ($hours24) {
			$ret .= $i;
		} else {
			$ret .= gmdate('g a',($i * 3600));
		}
		$ret .= "</option>\n";
	}

	$ret .= "</select>";

	if ($table)
		$ret .= "</td>\n";

	// Minutes
	if ($table)
		$ret .= "<td><!-- " . _T('select_time_minutes') . "<br/ -->\n";
	$ret .= ":<select $dis name=\"" . $name . "_minutes\" id=\"" . $name . "_minutes\" align=\"right\">\n";

	for ($i = 0; $i < 60; $i += 5) {
		$default = ($i == $default_minutes ? ' selected="selected"' : '');
		$ret .= "<option" . $default . " value=\"" . sprintf('%02u',$i) . "\">" . sprintf('%02u',$i) . "</option>\n";
	}

	$ret .= "</select>";

	if ($table)
		$ret .= "</td>\n";

	// Seconds
	if ($show_seconds) {
		if ($table)
			$ret .= "<td><!-- " . _T('select_time_seconds') . "<br/ -->\n";
		$ret .= ":<select $dis name=\"" . $name . "_seconds\" id=\"" . $name . "_seconds\" align=\"right\">\n";

		for ($i = 0; $i < 60; $i++) {
			$default = ($i == $default_seconds ? ' selected="selected"' : '');
			$ret .= "<option" . $default . " value=\"" . sprintf('%02u',$i) . "\">" . sprintf('%02u',$i) . "</option>\n";
		}

		$ret .= "</select>\n";

		if ($table)
			$ret .= "</td>\n";
	}

	if ($table)
		$ret .= "</tr>\n"
			. "</table>\n";

	return $ret;
}

function get_time_interval_inputs($name = 'select', $time, $hours_only = true, $select_hours = true, $table = false) {

	if ($hours_only) {
		$days = 0;
		$hours = $time / 3600;
		$minutes = 0;
	} else {
		$days = (int) ($time / 86400);
		$hours = (int) ( ($time % 86400) / 3600);
		$minutes = (int) round( ($time % 3600) / 300) * 5;
	}

	// If name is empty, disable fields
	$dis = (($name) ? '' : 'disabled');

	$ret = '';

	if ($table && !$hours_only)
		$ret .= "<table cellpadding=\"3\" cellspacing=\"3\">\n<tr>\n";
		
	// Days
	if ($hours_only) {
		$ret .= "<input type=\"hidden\" name=\"" . $name . "_days\" id=\"" . $name . "_days\" value=\"$days\" />\n";
	} else {
		if ($table)
			$ret .= "<td>\n"
			. "<!-- " . _T('select_time_days') . "<br/ -->\n";
		
		$ret .= "<input $dis size=\"2\" name=\"" . $name . "_days\" id=\"" . $name . "_days\" align=\"right\" value=\"$days\" />";
		$ret .= " d, ";
				
		if ($table)
			$ret .= "</td>\n";
	}

	// Hour
	if ($hours_only || !$select_hours) {
		$ret .= "<input $dis size=\"4\" name=\"" . $name . "_hours\" id=\"" . $name . "_hours\" align=\"right\" value=\"$hours\" />";
		$ret .= ($hours_only ? " hours\n" : " h, ");
	} else {
		if ($table)
			$ret .= "<td>\n"
			. "<!-- " . _T('select_time_hour') . "<br/ -->\n";

		$ret .= "<select $dis name=\"" . $name . "_hours\" id=\"" . $name . "_hours\" align=\"right\">\n";
	
		for ($i = 0; $i < 24; $i++) {
			$default = ($i == $hours ? ' selected="selected"' : '');
			$ret .= "<option" . $default . " value=\"" . sprintf('%02u',$i) . "\">$i</option>\n";
		}
	
		$ret .= "</select>";
		
		$ret .= " h, ";
	
		if ($table)
			$ret .= "</td>\n";
	}
	
	// Minutes
	if ($hours_only) {
		$ret .= "<input type=\"hidden\" name=\"" . $name . "_minutes\" id=\"" . $name . "_minutes\" value=\"$minutes\" />\n";
	} else {
		if ($table)
			$ret .= "<td>\n"
			. "<!-- " . _T('select_time_minutes') . "<br/ -->\n";
		
		$ret .= "<select $dis name=\"" . $name . "_minutes\" id=\"" . $name . "_minutes\" align=\"right\">\n";
	
		for ($i = 0; $i < 60; $i += 5) {
			$default = ($i == $minutes ? ' selected="selected"' : '');
			$ret .= "<option" . $default . " value=\"" . sprintf('%02u',$i) . "\">" . sprintf('%02u',$i) . "</option>\n";
		}
	
		$ret .= "</select>";
		$ret .= " m";
	
		if ($table)
			$ret .= "</td>\n";
	}

	if ($table && !$hours_only) $ret .= "</tr></table>\n";

	return $ret;
}

// Returns an array with valid CSS files for themes (lcm_ui_*.css)
function get_theme_list() {
	$list = array();

	$handle = opendir("styles");
	while (($f = readdir($handle)) != '') {
		if (is_file("styles/" . $f)) {
			// matches: styles/lcm_ui_foo.css
			if (preg_match("/lcm_ui_([_a-zA-Z0-9]+)\.css/", $f, $regs)) {
				// push_array($list, $regs[1]);
				$list[$regs[1]] = $regs[1];
			}
		}
	}

	ksort($list);
	reset($list);

	return $list;
}

// Show tabs
function show_tabs($tab_list, $selected, $url_base) {
// $tab_list = array( tab1_key => tab1_name, ... )
// $selected = tabX_key
// $url_base = url to  link tabs to as 'url'?tab=X

	// Get current $url_base parameters
	$params = array();
	$pos = strpos($url_base,'?');
	if ($pos === false) {
		$query = '';
	} else {
		$query = substr($url_base,$pos+1);
		$url_base = substr($url_base,0,$pos);
		parse_str($query,$params);
		unset($params['tab']);
		foreach ($params as $k => $v) {
			$params[$k] = $k . '=' . urlencode($v);
		}
	}
	
	echo "<!-- Page tabs generated by show_tabs() -->\n";

	// [KM]
	echo "<div class=\"tabs\">\n";
	echo "<ul class=\"tabs_list\">\n";
	
	// Display tabs
	foreach($tab_list as $key => $tab) {
		if ($key != $selected) {
			echo "\t<li><a href=\"$url_base?";
			if (count($params)>0) echo join('&amp;',$params) . '&amp;';
			echo 'tab=' . $key . "\">";
		} else {
			echo "\t<li class=\"active\">";
		}

		echo $tab;
		if ($key != $selected) echo "</a>";
		echo "</li>\n";
	}
	
	echo "</ul>";
	echo "</div>";
	echo "\n\n";
}

// Show tabs & links
function show_tabs_links($tab_list, $selected='', $sel_link=false) {
// $tab_list = array( tab1_key => array('name' => tab1_name, 'url' => tab1_link), ... )
// $selected = tabX_key;
// $sel_link - link of the selected tab active (true/false)

	echo "<!-- Page tabs generated by show_tabs_links() -->\n";

	// [KM]
	echo "<div class=\"tabs\">\n";
	echo "<ul class=\"tabs_list\">\n";
	
	// Display tabs
	foreach($tab_list as $key => $tab) {
		if ($key === $selected) {
			echo "\t<li class=\"active\">";
			if ($sel_link) echo "<a href=\"" . $tab['url'] . "\">";
		} else echo "\t<li><a href=\"" . $tab['url'] . "\">";
		echo $tab['name'];
		if ($sel_link || !($key === $selected) ) echo "</a>";
		echo "</li>";
		echo "\n";
	}

	echo "</ul>";
	echo "</div>";
	echo "\n\n";
}

function show_listcase_start() {
	$case_court_archive = read_meta('case_court_archive');

	echo '<table border="0" align="center" class="tbl_usr_dtl" width="99%">' . "\n";
	echo "<tr>\n";
	echo '<th class="heading">#</th>';
	echo '<th class="heading">Title</th>';

	if ($case_court_archive == 'yes') {
		echo '<th class="heading">Court archive</th>';
	}
	
	echo '<th colspan="2" class="heading">Status</th>';
	echo "</tr>\n";
}

function show_listcase_item($item, $cpt, $custom = '') {
	$case_court_archive = read_meta('case_court_archive');

	$ac_read = allowed($item['id_case'],'r');
	$ac_edit = allowed($item['id_case'], 'e');
	$css = ($cpt %2 ? "dark" : "light");

	echo "<tr>\n";

	// Case ID
	echo "<td class='tbl_cont_" . $css . "'>";
	if ($ac_read) echo '<a href="case_det.php?case=' . $item['id_case'] . '" class="content_link">';
	echo highlight_matches($item['id_case'],$find_case_string);
	if ($ac_read) echo '</a>';
	echo "</td>\n";

	// Title
	echo "<td class='tbl_cont_" . $css . "'>";
	if ($ac_read) echo '<a href="case_det.php?case=' . $item['id_case'] . '" class="content_link">';
	echo highlight_matches(clean_output($item['title']),$find_case_string);
	if (allowed($item['id_case'],'r')) echo '</a>';
	echo "</td>\n";
	
	// Court archive ID
	if ($case_court_archive == 'yes') {
		echo "<td class='tbl_cont_" . $css . "'>";
		echo highlight_matches(clean_output($item['id_court_archive']),$find_case_string);
		echo "</td>\n";
	}
	
	// Status
	echo "<td class='tbl_cont_" . $css . "'>" . $item['status'] . "</td>\n";
	
	// Actions / custom html
	echo "<td class='tbl_cont_" . $css . "'>";
	echo $custom;
	echo "</td>\n";

	echo "</tr>\n";
}

function show_listcase_end() {
	echo "</table>\n";
}

?>
