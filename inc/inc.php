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

	$Id: inc.php,v 1.60 2006/09/14 23:25:51 mlutfy Exp $
*/

include ('inc/inc_version.php');

// Test if LCM is installed
if (! include_config_exists('inc_connect')) {
	header('Location: install.php');
	exit;
}

// For profiling: count the number of SQL queries
$GLOBALS['db_query_count'] = 0;

include_lcm('inc_auth');
include_lcm('inc_acc');
include_lcm('inc_presentation');
include_lcm('inc_text');
include_lcm('inc_filters');
include_lcm('inc_keywords');
// include_lcm('inc_calendar'); [ML] not used


if (! include_data_exists('inc_meta_cache'))
	write_metas();

// Just precaution, avoids PHP warnings sometimes
if (! isset($_SESSION['form_data']))
	$_SESSION['form_data'] = array();

// [AG] Adding to validate later references to it
global $author_session;

//
// Preferences for presentation
// Can be done from any screen, but for now most is in config_author.php
// The presence of author_ui_modified assumed that all other form variables are set.
// [AG] added author_advanced_settings_modified to split settings into tabs
//
// Clear preferences modified flag
$prefs_mod = false;

if (_request('author_ui_modified')) {
	// Set UI theme
	if (_request('sel_theme') != _request('old_theme')) {
		// XSS risk: Theme names can only be alpha-numeric, "-" and "_"
		$sel_theme = preg_replace("/[^-_a-zA-Z0-9]/", '', _request('sel_theme'));

		if (file_exists("styles/lcm_ui_" . $sel_theme . ".css")) {
			$prefs['theme'] = ($sel_theme);
			$prefs_mod = true;
		}
	}

	// Set wide/narrow screen mode preference
	if (_request('sel_screen') != _request('old_screen')) {
		if (_request('sel_screen') == 'narrow' || _request('sel_screen') == 'wide') {
			$prefs['screen'] = _request('sel_screen');
			$prefs_mod = true;
		}
	}

	// Set rows per page preference
	if (intval(_request('page_rows', 0)) > 0) {
		$prefs['page_rows'] = intval(_request('page_rows', 0));
		$prefs_mod = true;
	}

	// Set font size
	if (_request('font_size') != _request('old_font_size')) {
		$font_size = _request('font_size');
		if ($font_size == 'small_font' || $font_size == 'medium_font' || $font_size == 'large_font') {
			$prefs['font_size'] = $font_size;
			$prefs_mod = true;
		}
	}

	// [ML] This is very important (but dirty hack) to change the language
	// from config_author.php but passing by lcm_cookie.php
	// It must be called last, because FORM values will be lost in the redirect
	$lang = _request('sel_language', $_COOKIE['lcm_lang']);
	
}

if (isset($_REQUEST['author_advanced_settings_modified'])) {
	// Set absolute/relative time intervals
	if ($_REQUEST['sel_time_intervals'] != $_REQUEST['old_time_intervals']) {
		if ($_REQUEST['sel_time_intervals'] == 'absolute' || $_REQUEST['sel_time_intervals'] == 'relative') {
			$prefs['time_intervals'] = $_REQUEST['sel_time_intervals'];
			$prefs_mod = true;
		}
	}

	// Set intervals notation
	if ($_REQUEST['sel_time_intervals_notation'] != $_REQUEST['old_time_intervals_notation']) {
		if (in_array($_REQUEST['sel_time_intervals_notation'],array("hours_only", "floatdays_hours_minutes", "floatdays_floathours_minutes"))) {
			$prefs['time_intervals_notation'] = $_REQUEST['sel_time_intervals_notation'];
			$prefs_mod = true;
		}
	}
}

// Update user preferences if modified
if ($prefs_mod) {
	lcm_query("UPDATE lcm_author
				SET   prefs = '" . addslashes(serialize($prefs)) . "'
				WHERE id_author = " . $author_session['id_author']);
}

if (isset($lang) AND $lang <> $lcm_lang) {
	// Boomerang via lcm_cookie to set a cookie and do all the dirty work
	// The REQUEST_URI should always be set, and point to the current page
	// we are being sent to (Ex: from config_author.php to listcases.php).
	// [ML] I used $lcm_lang because there are rare cases where the cookie
	// can disagree with $author_session['lang'] (e.g. login one user, set
	// cookie, logout, login other user, conflict).
	// [ML] Added $ref because some forms such as config_author.php expect it
	$ref = (isset($_REQUEST['referer']) ? '&referer=' . urlencode($_REQUEST['referer']) : '');
	header("Location: lcm_cookie.php?var_lang_lcm=" . $lang . "&url=" . urlencode($_SERVER['REQUEST_URI']) . $ref);
	exit;
}

//
// Database version management
//

$installed_db_version = read_meta('lcm_db_version');

if ($installed_db_version < $lcm_db_version) {
	lcm_page_start(_T('title_upgrade_database'));

	echo "<div class='box_warning'>\n";
	echo '<p class="normal_text"><b>' . _T('title_technical_message') . _T('typo_column') . "</b> "
		. _T('info_upgrade_database1') . ' '
		. '<a class="content_link" href="lcm_upgrade.php">' . _T('info_upgrade_database2') . "</a>"
		. "</p>";
	echo "</div>\n";

	echo "<!-- VERSION installed = $installed_db_version ; should be = $lcm_db_version -->\n";
	lcm_log("Upgrade required: installed = $installed_db_version, should be = $lcm_db_version");

	lcm_page_end();
	exit;
}


// Fetch the administration cookie
// [ML] Where is this used and why? :-)
if (isset($_COOKIE['lcm_admin']))
	$cookie_admin = $_COOKIE['lcm_admin'];
else
	$cookie_admin = "";


?>
