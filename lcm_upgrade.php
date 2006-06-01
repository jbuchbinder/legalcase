<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

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

	$Id: lcm_upgrade.php,v 1.16 2006/06/01 13:19:27 mlutfy Exp $
*/

include('inc/inc_version.php');
include_lcm('inc_presentation');
include_lcm('inc_meta');
include_lcm('inc_auth');

global $lcm_db_version;

// Check dir permissions: if we upgrade the LCM files, the permissions
// of the directories may have changed.
// c.f. Mateusz Hołysz (1148727224.9101.18.camel@localhost.localdomain)
if (_request('dirs_ok') != 1) {
	lcm_header('Location: lcm_test_dirs.php?url=' . urlencode("lcm_upgrade.php?dirs_ok=1"));
	exit;
}

// Usually done in inc.php, but we cannot include it otherwise
// it will loop on "please upgrade your database".
if (! include_data_exists('inc_meta_cache'))
	write_metas();

$current_version = read_meta('lcm_db_version');

// Quite unlikely to happen, because it would cause warnings
// But let's be paranoid, nothing to loose..
if (! $current_version) {
	lcm_log("lcm_upgrade: meta is misbehaving, searching in DB");
	$query = "SELECT value FROM lcm_meta WHERE name = 'lcm_db_version'";
	$result = lcm_query($query);

	if (($row = lcm_fetch_array($result)))
		$current_version = $row['value'];
	else
		lcm_panic("Could not find lcm_db_version");
}

lcm_log("lcm_upgrade test: current = $current_version, should be = $lcm_db_version");

// test if upgraded necessary
if ($current_version < $lcm_db_version) {
	include_lcm('inc_db_upgrade');
	lcm_page_start(_T('title_upgrade_database'));
	
	echo "\n<!-- Upgrading from $current_version to $lcm_db_version -->\n";

	$log = upgrade_database($current_version);

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
	global $author_session;
	lcm_page_start("No database upgrade needed"); // TRAD

	// Small practical trick to refresh the report fields/filters
	if ($author_session['status'] == 'admin') {
		include_lcm('inc_db_upgrade');
		include_lcm('inc_repfields_defaults');
		$fields = get_default_repfields();
		create_repfields($fields);
	}

	echo '<p class="normal_text"><a class="content_link" href="index.php">' . _T('info_upgrade_database5') . "</a></p>\n";

	lcm_page_end();
}

?>
