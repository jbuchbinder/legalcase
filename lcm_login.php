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

	$Id: lcm_login.php,v 1.17 2006/03/10 18:55:48 mlutfy Exp $
*/

include('inc/inc_version.php');

// Test if LCM is installed
if (! include_config_exists('inc_connect')) {
	header('Location: install.php');
	exit;
}

include_lcm('inc_presentation');
include_lcm('inc_login');

global $lcm_lang_right;

lcm_html_start(_T('login_title_login'), 'login');

echo get_optional_html_login();

// Site name: mandatory
$site_name = _T(read_meta('site_name'));
if (! $site_name)
	$site_name = _T('title_software');

// Site description: may be empty
$site_desc = _T(read_meta('site_description'));

echo "\n";
echo "<div align='center'>\n";
echo "<div align='center' id='login_screen'>\n\n";
echo "<h3>" . $site_name;

if ($site_desc)
	echo "<br /><span style='font-size: 80%; font-weight: normal;'>" . $site_desc .  "</span>";

echo "</h3>\n\n";

show_login('');

echo "\n\n";
echo "</div>\n";
echo "</div>\n\n";

echo "</body>\n</html>\n\n";

lcm_html_end();

?>
