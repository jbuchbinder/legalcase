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

	$Id: lcm_help.php,v 1.9 2006/02/20 03:26:18 mlutfy Exp $
*/

include('inc/inc_version.php');
include_lcm('inc_presentation');

$code = $_REQUEST['code'];
$error_section = false;

function include_help($code, $try_langs) {
	$ok = false;

	foreach ($try_langs as $lang) {
		$file = "inc/help/" . $lang . "/" . $code . ".html";

		if (@file_exists($file)) {
			$ok = true;
			include($file);
			return $ok;
		} else {
			echo "\n<!-- Failed to include '$file'. -->\n";
		}
	}

	return $ok;
}

if ($code) {
	// code should be short word, ex: installation, case_edit, ...
	$code = preg_replace("/[^_a-z]/", "", $code);
	$page_title = _T('help_title_' . $code);

	if ($page_title == 'help_title_' . $code)
		$error_section = true;
} else {
	$page_title = _T('title_software');
}

help_page_start($page_title, $code);

if ($code) {
	global $lcm_lang;
	$lang_site = read_meta('default_language');

	// Sometimes the help might not be translated in every
	// language. We will try first the language of the user,
	// then the default site language, then we fallback on English.
	$try_langs = array($lcm_lang, $lang_site, 'en');
	$ok = include_help($code, $try_langs);
	
	if (! $ok) {
		if ($error_section)
			echo "<p>" . $code . ": " . _T('help_warning_no_section') . "</p>\n";
		else {
			$toc = get_help_page_toc();

			if (isset($toc[$code])) {
				// [ML] TODO: Show chapter intro?
				echo "<ul>";

				foreach ($toc[$code] as $st)
					echo '<li><a href="lcm_help.php?code=' . $st . '">' . _T('help_title_' . $st) . "</a></li>\n";

				echo "</ul>\n";
			} else {
				echo "<p>" . _T('help_warning_no_files') . "</p>\n";
			}
		}
	}
} else {
	// Show LCM logo
	echo '<div align="center">';
	echo '<img src="images/lcm/lcm_logo_install.png" alt="" width="490" height="242" />' . "\n";
	echo '</div>';
}

help_page_end();

?>
