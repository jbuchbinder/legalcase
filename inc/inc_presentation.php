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
function lcm_html_start($title = "AUTO", $css_files = "") {
	global $lcm_lang_rtl, $lcm_lang_left;
	global $mode;
	global $connect_status;
	global $prefs;
		
	$lcm_site_name = entites_html(read_meta("site_name"));
	$title = textebrut($title);

	// Don't show site name (if none) while installation
	if (!$lcm_site_name && $title == "AUTO")
		$lcm_site_name = "LCM";

	if (!$charset = read_meta('charset'))
		$charset = 'utf-8';

	@Header("Expires: 0");
	@Header("Cache-Control: no-cache,no-store");
	@Header("Pragma: no-cache");
	@Header("Content-Type: text/html; charset=$charset");
	
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
	<title>". ($lcm_site_name ? $lcm_site_name ." | " : '') . $title ."</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=". $charset ."\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"styles/lcm_basic_layout.css\" />\n";

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
	
	echo "\t<link rel=\"alternate stylesheet\" type=\"text/css\" href=\"styles/lcm_opt_smallfonts.css\" title=\"small_font\" />\n";
	echo "\t<link rel=\"alternate stylesheet\" type=\"text/css\" href=\"styles/lcm_opt_largefonts.css\" title=\"large_font\" />\n";
	
	echo "\t<script type=\"text/javascript\" language=\"JavaScript\" src=\"inc/ss_switcher.js\"></script>\n";
				
	echo "</head>\n";

	// right-to-left (Arabic, Hebrew, Farsi, etc. -- even if not supported at the moment)
	echo '<body' . ($lcm_lang_rtl ? ' dir="rtl"' : '') . ">\n";
}

function lcm_page_start($title = "", $css_files = "") {
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

	lcm_html_start($title, $css_files);

	//
	// Title and description of the site
	//

	$site_name = read_meta('site_name');
	if (!$site_name)
		$site_name = _T('title_software');

	$site_desc = read_meta('site_description');
	if (!$site_desc)
		$site_desc = _T('title_software_description');
	
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
						<div class=\"nav_column_menu_head\"><img src=\"images/jimmac/stock_home-16.png\" width=\"16\" height=\"16\" alt=\"\" />&nbsp;"
							. _T('menu_main') . "</div>
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
						<div class=\"nav_column_menu_head\"><img src=\"images/jimmac/stock_menu_properties.png\" width=\"16\" height=\"16\" alt=\"\" />&nbsp;" . _T('menu_admin') . "</div>
						<ul class=\"nav_menu_list\">
							<li><a href=\"config_site.php\" class=\"main_nav_btn\">" . _T('menu_admin_siteconf') . "</a></li>
							<li><a href=\"all_cases.php\" class=\"main_nav_btn\">" .  _T('menu_admin_archives') . " <abbr title=\"All cases, categorised by date, keyword, etc. (admin only)\"></abbr></a></li>
							<li><a href=\"listreps.php\" class=\"main_nav_btn\">" . _T('menu_admin_reports') . " <abbr title=\"Manage reports (admin only)\"></abbr></a></li>
							<li><a href=\"listfilters.php\" class=\"main_nav_btn\">" . _T('menu_admin_filters') . " <abbr title=\"Manage filters (admin only)\"></abbr></a></li>
							<li><a href=\"keywords.php\" class=\"main_nav_btn\">" .  _T('menu_admin_keywords') . "</a></li>
						</ul>
					</div>\n";
	}

	echo "
					<div class=\"nav_menu_box\">
						<div class=\"nav_column_menu_head\"><img src=\"images/jimmac/stock_insert_table-16.png\"  width=\"16\" height=\"16\" alt=\"\" />&nbsp;" . _T('menu_calendar') . "</div>
						<p class=\"nav_column_text\">". format_date() ."</p>
					</div>
					<!-- [ML] Keeping this so that testers see what features
					to expect, but I put the opacity low to insist that it is
					not ready. Who knows whether it works in MSIE. -->
					<div class=\"nav_menu_box\" style='-moz-opacity: 0.45; filter: alpha(opacity=45);'>
						<div class=\"nav_column_menu_head\">Next 7 meetings (demo)</div>
						<p style='font-size: 70%;'><b>This feature is not ready yet.</b></p>
						<p class=\"nav_column_text\">
						<strong>Today</strong><br />
						9:30 - Meeting with Mr. Smith<br /><br />
						11:00 - At the court
						</p>
						<hr class=\"hair_line\" />
						<p class=\"nav_column_text\">
						<strong>Tomorrow (28.09.2004)</strong><br />
						8:30 - Meeting with Mr. Johnson<br /><br />
						10:00 - At the court
						</p>
					</div>
					<br /><br />
				<!-- End of \"navigation_menu_column\" content -->
				</div>

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
			<div class=\"prefs_column_menu_head\"><img src=\"images/jimmac/stock_exec-16.png\"  width=\"16\" height=\"16\" alt=\"\" />&nbsp;" . _T('menu_profile') . "</div>
			<p class=\"prefs_column_text\">"
				. "<a href=\"edit_author.php?author=" .  $author_session['id_author'] . "\" class=\"prefs_normal_lnk\">"
				. $author_session['name_first'] . ' '
				. $author_session['name_middle'] . ' '
				. $author_session['name_last']
				. "</a><br /><br />
			<a href=\"config_author.php\" class=\"prefs_myprefs\">" .  _T('menu_profile_preferences') . "</a><br /><br /><a href=\"lcm_cookie.php?logout=".  $author_session['username'] ."\" class=\"prefs_logout\">" . _T('menu_profile_logout') . "</a>
			</p>
			<div class=\"prefs_column_menu_head\"><img src=\"images/jimmac/stock_search-16.png\"  width=\"16\" height=\"16\" alt=\"\" />&nbsp;" . _T('menu_search') . "</div>
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
			</form>
			<!-- the font size experiment -->
			<div class=\"prefs_column_menu_head\"><img src=\"images/jimmac/stock_font-16.png\"  width=\"16\" height=\"16\" alt=\"\" />&nbsp;Font size</div>
			<p class=\"prefs_column_text\">
				<ul class=\"font_size_buttons\">
					<li><a href=\"javascript:;\" title=\"Small Text\" onclick=\"setActiveStyleSheet('small_font')\">A-</a></li>
					<li><a href=\"javascript:;\" title=\"Normal Text\" onclick=\"setActiveStyleSheet('')\">A</a></li>
					<li><a href=\"javascript:;\" title=\"Large Text\" onclick=\"setActiveStyleSheet('large_font')\">A+</a></li>
				</ul>
			</p>
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
				<a href=\"javascript:;\" title=\"Normal Text\" onclick=\"setActiveStyleSheet('')\" class=\"set_fnt_sz\">&nbsp;A&nbsp;&nbsp;</a>&nbsp;
				<a href=\"javascript:;\" title=\"Large Text\" onclick=\"setActiveStyleSheet('large_font')\" class=\"set_fnt_sz\">&nbsp;A+&nbsp;</a>&nbsp;&nbsp;<a href=\"lcm_cookie.php?logout=".  $author_session['username'] ."\" class=\"prefs_logout\">" . _T('menu_profile_logout') . "</a>
			</p>";
		echo "</div>";
}

		//just test...
		echo "<div class=\"clearing\">&nbsp;</div>
	</div>";

if($prefs['screen'] == "narrow")
{
	echo "<div id=\"footer\">
	<div class=\"prefs_column_menu_head\">Search</div>
	<p class=\"prefs_column_text\">
	<table border=\"0\" align=\"center\" width=\"100%\">
		<tr>
			<td align=\"left\" valign=\"top\">
			<form name=\"frm_find_case\" class=\"search_form\" action=\"listcases.php\" method=\"post\">
			Find case<br />
			<input type=\"text\" name=\"find_case_string\" size=\"10\" class=\"search_form_txt\"";
	if (isset($find_case_string)) echo " value='$find_case_string'";
	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"Search\" class=\"search_form_btn\" />
			</form>
			</td>
			<td align=\"left\" valign=\"top\">
			<form name=\"frm_find_client\" class=\"search_form\" action=\"listclients.php\" method=\"post\">
			Find client<br />
			<input type=\"text\" name=\"find_client_string\" size=\"10\" class=\"search_form_txt\"";
	if (isset($find_client_string)) echo " value='$find_client_string'";
	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"Search\" class=\"search_form_btn\" />
			</form>
			</td>
			<td align=\"left\" valign=\"top\">
			<form name=\"frm_find_client\" class=\"search_form\" action=\"listorgs.php\" method=\"post\">
			Find organisation<br />
			<input type=\"text\" name=\"find_org_string\" size=\"10\" class=\"search_form_txt\"";
	if (isset($find_org_string)) echo " value='$find_org_string'";
	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"Search\" class=\"search_form_btn\" />
			</form>
			</td>
		</tr>
	</table>
			</p>
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

	$ret = '';
	if ($table)
		$ret .= "<table cellpadding=\"3\" cellspacing=\"3\">\n"
			. "<tr>\n"
			. "<td><!-- " . _T('select_date_day') . "<br/ -->\n";
	$ret .= "<select name=\"" . $name . "_day\" id=\"" . $name . "_day\">\n";

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
	$ret .= "<select name=\"" . $name . "_month\" id=\"" . $name . "_month\">\n";

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
	$ret .= "<select name=\"" . $name . "_year\" id=\"" . $name . "_year\">\n";

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


// ******************************************************************
// The following functions are not used. Some are kept only as reminders
// of features todo.
// ******************************************************************

//
// Help
//
/*
function help($aide='') {
 // ...
}
*/


// Fake HR, with color control -- advantages is to hide in Lynx (?)
/*
function hr($color, $retour = false) {
	$ret = "<div style='height: 1px; margin-top: 5px; padding-top: 5px; border-top: 1px solid $color;'></div>";
	
	if ($retour) return $ret;
	else echo $ret;
}
*/


?>
