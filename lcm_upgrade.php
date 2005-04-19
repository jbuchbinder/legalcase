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

	$Id: lcm_upgrade.php,v 1.9 2005/04/19 06:25:13 mlutfy Exp $
*/

include('inc/inc_version.php');
include_lcm('inc_presentation');
include_lcm('inc_meta');
include_lcm('inc_auth');

$current_version = read_meta('lcm_db_version');
if (!$current_version) $current_version = 0;

// test if upgraded necessary
if ($lcm_db_version <> $current_version) {
	include_lcm('inc_db_upgrade');
	lcm_page_start(_T('title_upgrade_database'));
	
	echo "\n<!-- Upgrading from $current_version to $lcm_db_version -->\n";
	$log = upgrade_database($current_version);

	// Create new meta information, if necessary
	include_lcm('inc_meta_defaults');
	init_default_config();

	// To be honest, in most cases, it will cause a lcm_panic() and this will
	// not show, altough we could (in the future) catch/interpret errors.
	if ($log) {
		echo "<div class='box_error'>\n";
		echo "<p>An error occured while upgrading the database: <br/>$log</p>\n"; // TRAD
		echo "</div>\n";
		lcm_panic("upgrade error ($log)");
	} else {
		echo "<div class='box_success'>\n";
		echo '<p class="normal_text">' . _T('info_upgrade_database3') 
			. ' <a class="content_link" href="index.php">' . _T('info_upgrade_database5') . "</a></p>\n";
		echo "</div>\n";
	}
	
	lcm_page_end();
} else {
	lcm_page_start("No database upgrade needed"); // TRAD

	echo '<p class="normal_text"><a class="content_link" href="index.php">' . _T('info_upgrade_database5') . "</a></p>\n";

	lcm_page_end();
}

?>
