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

	$Id: inc.php,v 1.37 2005/01/19 09:42:25 mlutfy Exp $
*/

// Test if LCM is installed
if (!@file_exists('inc/config/inc_connect.php')) {
	header('Location: install.php');
	exit;
}

include ('inc/inc_version.php');

include_lcm('inc_auth');
include_lcm('inc_presentation');
include_lcm('inc_text');
include_lcm('inc_filters');
// include_lcm('inc_calendar'); [ML] not used

if (! @file_exists('inc/data/inc_meta_cache.php'))
	write_metas();

//
// Preferences for presentation
// Can be done from any screen, but for now most is in config_author.php
// The presence of author_ui_modified assumed that all other form variables are set.
//
if (isset($_REQUEST['author_ui_modified'])) {
	// Set prefs_mod to true if prefs need to be saved in DB
	$prefs_mod = false;

	// [ML] This is very important (but dirty hack) to change the language
	// from config_author.php but passing by lcm_cookie.php
	if (isset($_REQUEST['sel_language']))
		$lang = $_REQUEST['sel_language'];
	else
		$lang = $GLOBALS['HTTP_COOKIE_VARS']['lcm_lang'];

	if (isset($lang) AND $lang <> $author_session['lang']) {
		// Boomerang via lcm_cookie to set a cookie and do all the dirty work
		// The REQUEST_URI should always be set, and point to the current page
		// we are being sent to (Ex: from config_author.php to listcases.php).
		header("Location: lcm_cookie.php?var_lang_lcm=" . $lang . "&url=" .  $_SERVER['REQUEST_URI']);
	}

	// Set UI theme
	if ($_REQUEST['sel_theme'] != $_REQUEST['old_theme']) {	// Value is changed
		// XSS risk: Theme names can only be alpha-numeric, "-" and "_"
		$sel_theme = preg_replace("/[^-_a-zA-Z0-9]/", '', $_REQUEST['sel_theme']);

		if (file_exists("styles/lcm_ui_" . $sel_theme . ".css")) {
			$prefs['theme'] = ($sel_theme);
			$prefs_mod = true;
		}
	}

	// Set wide/narrow screen mode preference
	if ($_REQUEST['sel_screen'] != $_REQUEST['old_screen']) {	// Value is changed
		if ($_REQUEST['sel_screen'] == 'narrow' || $_REQUEST['sel_screen'] == 'wide') {
			$prefs['screen'] = $_REQUEST['sel_screen'];
			$prefs_mod = true;
		}
	}

	// Set normal/advanced UI mode preference
	if ($_REQUEST['sel_mode'] != $_REQUEST['old_mode']) {
		if ($_REQUEST['sel_mode'] == 'simple' || $_REQUEST['sel_mode'] == 'extended') {
			$prefs['mode'] = $_REQUEST['sel_mode'];
			$prefs_mod = true;
		}
	}

	// Set rows per page preference
	if (intval($_REQUEST['page_rows']) > 0) {
		$prefs['page_rows'] = intval($_REQUEST['page_rows']);
		$prefs_mod = true;
	}

	// Set font size
	if ($_REQUEST['font_size'] != $_REQUEST['old_font_size']) {
		if ($font_size == 'small_font' || $font_size == 'medium_font' || $font_size == 'large_font') {
			$prefs['font_size'] = $font_size;
			$prefs_mod = true;
		}
	}

	// Update user preferences if modified
	if ($prefs_mod) {
		lcm_query("UPDATE lcm_author
					SET   prefs = '".addslashes(serialize($prefs))."'
					WHERE id_author = " . $author_session['id_author']);
	}
}

//
// Database version management
//

// [ML] Not required, there was a bug in line 25 of inc.php
// write_metas();

$installed_db_version = read_meta('lcm_db_version');

if ($installed_db_version <> $lcm_db_version) {
	lcm_page_start("Database upgrade", "install");
	if (! isset($installed_version))
		$installed_version = "old version";

	echo "<div class='box_warning'>\n";
	echo "<p><b>" . _T('title_technical_message') . _T('typo_column') . "</b> The
		format of the database has changed. <a href='lcm_upgrade.php'>To
		proceed with the automatic upgrade, click here</a>. You are also
		encouraged to make a backup before proceeding.</p>\n";
	echo "</div>\n";

	lcm_page_end();
	exit;
}


//
// Fetch the administration cookie
$cookie_admin = $HTTP_COOKIE_VARS['lcm_admin'];


?>
