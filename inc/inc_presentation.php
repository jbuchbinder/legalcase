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

	$Id: inc_presentation.php,v 1.176 2005/03/22 11:55:57 mlutfy Exp $
*/

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

	// The 'antifocus' is used to erase default titles such as "New appointment"
	// other functions are used in calendar functions (taken from Spip's presentation.js)
	echo "<script type='text/javascript'><!--
		var title_antifocus = false;

		var memo_obj = new Array();

		function findObj(n) { 
			var p, i, x;

			// Check if we have not already memorised this elements
			if (memo_obj[n]) {
				return memo_obj[n];
			}       

			d = document; 
			if((p = n.indexOf(\"?\"))>0 && parent.frames.length) {
				d = parent.frames[n.substring(p+1)].document; 
				n = n.substring(0,p);
			}       
			if(!(x = d[n]) && d.all) {
				x = d.all[n]; 
			}       
			for (i = 0; !x && i<d.forms.length; i++) {
				x = d.forms[i][n];
			}       
			for(i=0; !x && d.layers && i<d.layers.length; i++) x =
				findObj(n,d.layers[i].document);
			if(!x && document.getElementById) x = document.getElementById(n); 

			// Memorise the element
			memo_obj[n] = x;

			return x;
		}

		function setvisibility (objet, status) {
			element = findObj(objet);
			if (element.style.visibility != status)
				element.style.visibility = status; 
		}

		function lcm_show(objet) {
			setvisibility(objet, 'visible');
		}

		function lcm_hide(objet) {
			setvisibility(objet, 'hidden');
		}

		//--></script>\n";
	
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
	
	echo "<link rel=\"shortcut icon\" type=\"image/ico\" href=\"images/lcm/favicon.ico\" />\n";
	
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
		<a href='summary.php' class='balance_link'>&nbsp;</a>
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
						<p class=\"nav_column_text\">";
//	echo format_date();
	// Show calendar
	include_lcm('inc_calendar');
/*	$q = "SELECT lcm_app.*
			FROM lcm_app, lcm_author_app as a
			WHERE (a.id_author=" . $GLOBALS['author_session']['id_author'] . "
				AND lcm_app.id_app=a.id_app)";

	$result = lcm_query($q);

	if (lcm_num_rows($result) > 0) {
		$events = array();
		while ($row=lcm_fetch_array($result)) {
			$events[] = $row;
		}
		echo lcm_http_calendrier_ics($events,'20050222');
	} */
	$now = date('Y-m-d');
	echo http_calendrier_agenda(mois($now), annee($now), jour($now), mois($now), annee($now), false, 'calendar.php');
	echo "</p>";
	echo "
					</div>\n";
	
	// Start agenda box
	echo '<div class="nav_menu_box">' . "\n";
		echo '<div class="nav_column_menu_head">';
			echo '<div class="mm_agenda">';
			echo '<a href="listapps.php">'. _T('menu_agenda') . '</a>
				</div>
			</div>';
	$events = false;

	// Show appointments for today
	$q = "SELECT lcm_app.id_app,start_time,type,title
			FROM lcm_app, lcm_author_app as a
			WHERE (a.id_author=" . $GLOBALS['author_session']['id_author'] . "
				AND lcm_app.id_app=a.id_app
				AND start_time LIKE '" . date('Y-m-d') ."%')
			ORDER BY reminder ASC";

	$result = lcm_query($q);

	if (lcm_num_rows($result) > 0) {
		$events = true;
		echo "<p class=\"nav_column_text\">
					<strong>Today</strong><br />\n";
		echo "</p>\n";
		echo "<ul class=\"small_agenda\">\n";
		while ($row=lcm_fetch_array($result)) {
			echo "<li><a href=\"app_det.php?app=" . $row['id_app'] . "\">"
				. heures($row['start_time']) . ':' . minutes($row['start_time']) . " - " . $row['title'] . "</a></li>\n";
		}
		//					9:30 - Meeting with Mr. Smith<br /><br />
		//					11:00 - At the court
		//echo "</p>\n";
		echo "</ul>\n";
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
		$events = true;
		echo "<p class=\"nav_column_text\">
				<strong>Next appointments</strong><br />\n"; // TRAD
		echo "						</p>\n";
		
		echo "<ul class=\"small_agenda\">\n";
		while ($row=lcm_fetch_array($result)) {
			echo "							<li><a href=\"app_det.php?app=" . $row['id_app'] . "\">"
				. format_date($row['start_time'],'short') . " - " . $row['title'] . "</a></li>\n";
		}
		echo "</ul>\n";
		//					8:30 - Meeting with Mr. Johnson<br /><br />
		//					10:00 - At the court
		//echo "						</p>\n";
	}

	if (!$events) {
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
			" . _T('input_search_org') . "<br />
			<input type=\"text\" name=\"find_org_string\" size=\"10\" class=\"search_form_txt\"";

	if (isset($find_org_string))
		echo " value='$find_org_string'";

	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"" . _T('button_search') . "\" class=\"search_form_btn\" />
			</p>
			</form><br />
			<!-- the font size experiment -->
			<div class=\"prefs_column_menu_head\"><div class=\"sm_font_size\">" . _T('menu_fontsize') . "</div>
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
	echo "<div id=\"footer\">". _T('title_software') ." (". $lcm_version_shown .")<br/> ";
	echo _T('info_free_software', 
			array(
				'distributed' => '<a href="http://www.lcm.ngo-bg.org/" class="prefs_normal_lnk">' . _T('info_free_software1') . '</a>',
				'license' => lcm_help_string('about#license', _T('info_free_software2'))))
		. "</div>\n";

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
	echo "\t<div align='center' id='install_screen'>\n";
	echo "\t\t<h1><b>$title</b></h1>\n";

	echo "\n<!-- END install_html_start() -->\n\n";
}

/*
 * Footer function for the installation
 * They are used by install.php and lcm_test_dirs.php
 */
function install_html_end() {
		echo " </div>
	</body>
	</html>
";
}

//
// Help
//

function lcm_help($code, $anchor = '') {
	global $lcm_lang;

	$topic = _T('help_title_' . $code);
	if ($anchor) $anchor = '#' . $anchor;

	return '<a href="lcm_help.php?code=' . $code . $anchor .'" target="lcm_help" ' 
		. 'onclick="javascript:window.open(this.href, \'lcm_help\', \'scrollbars=yes, resizable=yes, width=740, height=580\'); return false;">'
		. '<img src="images/lcm/help.png" alt="help: ' . $topic . '" '
		. 'title="help: ' . $topic . '" width="12" height="12" border="0" align="middle" /> '
		. "</a>\n";
}

// shows help link for a string rather than for icon (see GPL notice in install + footer)
function lcm_help_string($code, $string, $anchor = '') {
	global $lcm_lang;

	$topic = _T('help_title_' . $code);
	if ($anchor) $anchor = '#' . $anchor;

	return '<a class="prefs_normal_lnk" href="lcm_help.php?code=' . $code . $anchor . '" target="lcm_help" ' 
		. 'onclick="javascript:window.open(this.href, \'lcm_help\', \'scrollbars=yes, resizable=yes, width=740, height=580\'); return false;">'
		. $string
		. "</a>";
}


//
// Help pages HTML header & footer
//

function help_page_start($page_title) {

	if (!$charset = read_meta('charset'))
		$charset = 'utf-8';

	$toc = array(
		'installation' => array('install_permissions', 'install_database', 'install_personal'),
		'cases' => array('cases_intro', 'cases_participants', 'cases_followups'),
		'clients' => array('clients_intro', 'clients_org'),
		'authors' => array('authors_intro', 'authors_admin'),
		'siteconfig' => array('siteconfig_general', 'siteconfig_collab', 'siteconfig_policy', 'siteconfig_regional'),
		'archives' => array('archives_intro', 'archives_export', 'archives_import'),
		'reports' => array('reports_intro'), 
		'keywords' => array('keywords_intro', 'keywords_new'),
		'about' => array('about_contrib', 'about_license')); 

	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . _T('help_title_help') . '</title>
<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '" />
<link rel="stylesheet" href="styles/lcm_help.css" type="text/css" />
<script type="text/javascript" language="JavaScript" src="inc/help_menu.js"></script>
</head>' . "\n";

	echo "<body>\n";
	echo '<h1>' . _T('help_title_help') . "</h1>\n";
	echo '<div id="hlp_big_box">
	<div id="hlp_menu">
		<ul id="nav">';

	foreach ($toc as $topic => $subtopics) {
		echo "\n\t\t\t". '<li><a href="lcm_help.php?code=' . $topic .'">' . _T('help_title_' . $topic) . '</a>' . "\n";
		echo "\t\t\t\t". '<ul class="subnav">' ."\n";
		foreach ($subtopics as $st) {
			echo "\t\t\t\t\t". '<li><a href="lcm_help.php?code=' . $st .'">' . _T('help_title_' . $st) . '</a></li>' . "\n";
		}
		echo "\t\t\t\t</ul>\n\t\t\t</li>";
	}
	
	echo "\n\t\t". '</ul>
	</div>
	<div id="hlp_cont"><h2>' . $page_title . "</h2>
		<div class=\"hlp_data\">";

}

function help_page_end() {
	echo "</div>
	</div>
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

// Returns a "select" with choice of yes/no
function get_yes_no($name, $value = '') {
	$ret = '';

	// [ML] sorry for this stupid patch, practical for keywords.php
	$val_yes = 'yes';
	$val_no = 'no';

	if ($value == 'Y' || $value == 'N') {
		$val_yes = 'Y';
		$val_no  = 'N';
	}

	$yes = ($value == $val_yes ? ' selected="selected"' : '');
	$no = ($value == $val_no ? ' selected="selected"' : '');
	$other = ($yes || $no ? '' : ' selected="selected"');

	// until we format with tables, better to keep the starting space
	$ret .= ' <select name="' . $name . '" class="sel_frm">' . "\n";
	$ret .= '<option value="' . $val_yes . '"' . $yes . '>' . _T('info_yes') . '</option>';
	$ret .= '<option value="' . $val_no  . '"' . $no .  '>' . _T('info_no') . '</option>';

	if ($other)
		$ret .= '<option value=""' . $other . '> </option>';

	$ret .= '</select>' . "\n";

	return $ret;
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

function get_list_pos($result) {
	$list_pos = 0;
	
	if (isset($_REQUEST['list_pos']))
		$list_pos = $_REQUEST['list_pos'];
	
	if ($list_pos >= $number_of_rows)
		$list_pos = 0;
	
	// Position to the page info start
	if ($list_pos > 0)
		if (!lcm_data_seek($result, $list_pos))
			lcm_panic("Error seeking position $list_pos in the result");
	
	return $list_pos;
}

function show_list_start($headers = array()) {
	echo '<table border="0" align="center" class="tbl_usr_dtl" width="99%">' . "\n";
	echo "<tr>\n";

	foreach($headers as $h) {
		echo "<th class=\"heading\">";

		if ($h['order'] && $h['order'] != 'no_order') {
			$ovar = $h['order'];
			$cur_sort_order = $h['default'];
			if (isset($_REQUEST[$ovar]) && ($_REQUEST[$ovar] == 'ASC' || $_REQUEST[$ovar] == 'DESC'))
				$cur_sort_order = $_REQUEST[$ovar];
		
			$new_sort_order = ($cur_sort_order == 'DESC' ? 'ASC' : 'DESC');
			$sort_link = new Link();
			$sort_link->addVar($ovar, $new_sort_order);
		
			echo '<a href="' . $sort_link->getUrl() . '" class="content_link">';
			echo $h['title'];
			echo "</a>";
		
			if ($cur_sort_order == 'ASC')
				echo '<img src="images/lcm/asc_desc_arrow.gif" alt="" />';
			else
				echo '<img src="images/lcm/desc_asc_arrow.gif" alt="" />';
		
		} else {
			echo $h['title'];
		}
		
		echo "</th>";
	}

	echo "</tr>\n";
}

function show_list_end($current_pos = 0, $number_of_rows = 0) {
	global $prefs;

	echo "</table>\n";

	//
	// Navigation for previous/next screens
	//
	$list_pages = ceil($number_of_rows / $prefs['page_rows']);

	if (! $list_pages) {
		echo "<!-- list_pages == 0 -->\n";
		return;
	}

	echo "<table border='0' align='center' width='99%' class='page_numbers'>\n";
	echo '<tr><td align="left" width="15%">';

	// Previous page
	if ($current_pos > 0) {
		$link = new Link();
		$link->delVar('list_pos');

		if ($current_pos > $prefs['page_rows'])
			$link->addVar('list_pos', $current_pos - $prefs['page_rows']);

		echo '<a href="' . $link->getUrl() . '" class="content_link">'
			. "&lt; " . _T('listnav_link_previous')
			. '</a> ';
	}

	echo "</td>\n";
	echo '<td align="center" width="70%">';

	// Page numbers with direct links
	if ($list_pages > 1) {
		echo _T('listnav_link_gotopage') . ' ';

		for ($i = 0; $i < $list_pages; $i++) {
			if ($i == floor($current_pos / $prefs['page_rows'])) {
				echo '[' . ($i+1) . '] ';
			} else {
				$current_pos_val = ($i * $prefs['page_rows']);
				$link = new Link();
				$link->delVar('list_pos');

				if ($current_pos_val > 0)
					$link->addVar('list_pos', $current_pos_val);
				
				echo '<a href="' . $link->getUrl() . '" class="content_link">' . ($i+1) . '</a> ';
			}
		}
	}

	echo "</td>\n";
	echo "<td align='right' width='15%'>";

	// Next page
	$next_pos = $current_pos + $prefs['page_rows'];
	if ($next_pos < $number_of_rows) {
		$current_pos_val = $next_pos;
		$link = new Link();
		$link->addVar('list_pos', $current_pos_val);

		echo '<a href="' . $link->getUrl() . '" class="content_link">'
			. _T('listnav_link_next') . " &gt;"
			. '</a>';
	}

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

// see listclients.php for example
function show_listclient_start() {
	$headers = array();
	$headers[0]['title'] = _Th('person_input_name');
	$headers[0]['order']  = 'order_name_first';
	$headers[0]['default'] = 'ASC';

	show_list_start($headers);
}

function show_listclient_end($current_pos = 0, $number_of_rows = 0) {
	show_list_end($current_pos, $number_of_rows);
}

// see listcases.php for example
function show_listcase_start() {
	$case_court_archive = read_meta('case_court_archive');

	$cpt = 0;
	$headers = array();

	$headers[$cpt]['title'] = '#'; // TRAD
	$headers[$cpt]['order'] = 'no_order';
	$cpt++;

	$headers[$cpt]['title'] = _Th('time_input_date_creation');
	$headers[$cpt]['order'] = 'case_order';
	$headers[$cpt]['default'] = 'DESC';
	$cpt++;

	$headers[$cpt]['title'] = _Th('case_input_title');
	$headers[$cpt]['order'] = 'no_order';
	$cpt++;

	if ($case_court_archive == 'yes') {
		$headers[$cpt]['title'] = _Th('case_input_court_archive');
		$headers[$cpt]['order'] = 'no_order';
		$cpt++;
	}

	// XXX actually, it would be nice to be able to order..
	// but this would require us to put numbers in status names
	$headers[$cpt]['title'] = _Th('case_input_status');
	$headers[$cpt]['order'] = 'no_order';

	show_list_start($headers);
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

	// Date creation
	echo "<td class='tbl_cont_" . $css . "'>";
	if ($ac_read) echo '<a href="case_det.php?case=' . $item['id_case'] . '" class="content_link">';
	echo format_date($item['date_creation'], 'short');
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

function show_listcase_end($current_pos = 0, $number_of_rows = 0) {
	show_list_end($current_pos, $number_of_rows);
}

function show_find_box($type, $string) {
	// the joy of patching around
	switch ($type) {
		case 'case':
		case 'client':
		case 'author':
			$action = 'list' . $type . 's.php';
			break;
		case 'org':
			$action = 'listorgs.php';
			break;
		default:
			lcm_panic("invalid type: $type");
	}

	echo '<form name="frm_find_' . $type . '" class="search_form" action="' . $action . '" method="get">' . "\n";
	echo _T('input_search_' . $type) . "&nbsp;";
	echo '<input type="text" name="find_' . $type . '_string" size="10" class="search_form_txt" value="' .  $string . '" />';
	echo '&nbsp;<input type="submit" name="submit" value="' . _T('button_search') . '" class="search_form_btn" />' . "\n";
	echo "</form>\n";
}

function show_context_start() {
	echo "<ul style=\"padding-left: 0.5em; padding-top: 0.2; padding-bottom: 0.2; font-size: 12px;\">\n";
}

function show_context_case_title($id_case) {
	if (! (is_numeric($id_case) && $id_case > 0)) {
		lcm_log("Warning: show_context_casename, id_case not a number > 0: $id_case");
		return;
	}

	$query = "SELECT title FROM lcm_case WHERE id_case = $id_case";
	$result = lcm_query($query);

	while ($row = lcm_fetch_array($result))  // should be only once
		echo '<li style="list-style-type: none;">' 
			. _T('fu_input_for_case')
			. " <a href='case_det.php?case=$id_case' class='content_link'>" . $row['title'] . "</a>"
			. "</li>\n";
}

function show_context_case_involving($id_case) {
	if (! (is_numeric($id_case) && $id_case > 0)) {
		lcm_log("Warning: show_context_casename, id_case not a number > 0: $id_case");
		return;
	}

	$query = "SELECT cl.id_client, name_first, name_middle, name_last
				FROM lcm_case_client_org as cco, lcm_client as cl
				WHERE cco.id_case = $id_case
				  AND cco.id_client = cl.id_client";
	
	$result = lcm_query($query);
	$numrows = lcm_num_rows($result);

	$current = 0;
	$all_clients = array();
	
	while ($all_clients[] = lcm_fetch_array($result));
	
	$query = "SELECT org.name, cco.id_client, org.id_org
				FROM lcm_case_client_org as cco, lcm_org as org
				WHERE cco.id_case = $id_case
				  AND cco.id_org = org.id_org";
	
	$result = lcm_query($query);
	$numrows += lcm_num_rows($result);
	
	// TODO: It would be nice to have the name of the contact for that
	// organisation, if any, but then again, not the end of the world.
	// (altough I we make a library of common functions, it will defenitely
	// be a good thing to have)
	while ($all_clients[] = lcm_fetch_array($result));
	
	if ($numrows > 0)
		echo '<li style="list-style-type: none;">' . _T('fu_input_involving_clients') . " ";
	
	foreach ($all_clients as $client) {
		if ($client['id_client']) {
			echo '<a href="client_det.php?client=' . $client['id_client'] . '" class="content_link">'
				. njoin(array($client['name_first'],$client['name_middle'],$client['name_last']))
				. '</a>';
	
			if (++$current < $numrows)
				echo ", ";
		} else if ($client['id_org']) {
			echo '<a href="org_det.php?org=' . $client['id_org'] . '" class="content_link">'
				. $client['name']
				. '</a>';
	
			if (++$current < $numrows)
				echo ", ";
		}
	
	}
	
	if ($numrows > 0)
		echo "</li>\n";
}

function show_context_end() {
	echo "</ul>\n";
}

function show_attachments_list($type, $id_type) {
	if (! ($type == 'case' || $type == 'client' || $type == 'org')) 
		lcm_panic("unknown type -" . $type . "-");

	$q = "SELECT * 
			FROM lcm_" . $type . "_attachment 
			WHERE id_" . $type . " = " . intval($id_type);

	$result = lcm_query($q);
	$i = lcm_num_rows($result);

	if ($i > 0) {
		echo '<table border="0" align="center" class="tbl_usr_dtl" width="99%">' . "\n";
		echo "<tr>\n";
		echo '<th class="heading">' . _Th('file_input_name') . "</th>\n";
		echo '<th class="heading">' . _Th('file_input_type') . "</th>\n";
		echo '<th class="heading">' . _Th('file_input_size') . "</th>\n";
		echo '<th class="heading">' . _Th('file_input_description') . "</th>\n";
		echo "</tr>\n";

		for ($i=0 ; $row = lcm_fetch_array($result) ; $i++) {
			echo "<tr>\n";
			echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">'
				. '<a href="view_file.php?type=' . $type . '&amp;file_id=' . $row['id_attachment']
				. '" class="content_link">' . $row['filename'] . '</a></td>';
			echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . $row['type'] . '</td>';
			echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . $row['size'] . '</td>';
			echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . clean_output($row['description']) . '</td>';
			echo "</tr>\n";
		}

		echo "</table>\n";
		echo "<br />\n";
	}
}

function show_attachments_upload($type, $id_type) {
	if (! ($type == 'case' || $type == 'client' || $type == 'org')) 
		lcm_panic("unknown type -" . $type . "-");

	echo '<div class="prefs_column_menu_head">' . 'Add new document' . "</div>\n"; // TRAD

	echo '<form enctype="multipart/form-data" action="attach_file.php" method="post">' . "\n";
	echo '<input type="hidden" name="' . $type . '" value="' . $id_type . '" />' . "\n";
	echo '<input type="hidden" name="MAX_FILE_SIZE" value="300000" />' . "\n";

	echo '<strong>' . _Ti('file_input_name') . "</strong><br />";
	echo '<input type="file" name="filename" size="40" />' . "<br />\n";

	echo '<strong>' . _Ti('file_input_description') . "</strong><br />\n";
	echo '<input type="text" name="description" class="search_form_txt" />&nbsp;' . "\n";
	echo '<input type="submit" name="submit" value="' . _T('button_validate') . '" class="search_form_btn" />' . "\n";
	echo "</form>\n";
}

?>
