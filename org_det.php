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

	$Id: org_det.php,v 1.10 2005/01/21 16:35:51 mlutfy Exp $
*/

include('inc/inc.php');
include('inc/inc_acc.php');

$org = (isset($_REQUEST['org']) ? intval($_REQUEST['org']) : 0);

if ($org <= 0)
	die("Which organisation?");

$q = "SELECT *
		FROM lcm_org
		WHERE lcm_org.id_org=$org";

$result = lcm_query($q);

if ($row = lcm_fetch_array($result)) {
	lcm_page_start("Organisation: " . $row['name']);

	/* Saved for future use
	// Check for access rights
	if (!($row['public'] || allowed($case,'r'))) {
		die("You don't have permission to view this case!");
	}
	$edit = allowed($case,'w');
	*/
	$edit = true;

	// Show organisation details
	echo '<fieldset class="info_box">';
	echo '<div class="prefs_column_menu_head">' . _T('org_subtitle_view_general') . "</div>\n";
	echo '<p class="normal_text">';

	//		echo "\n<br />Organisation ID: " . $row['id_org'] . "<br />\n";
	//		echo 'Organisation name: ' . $row['name'] . "<br />\n";
	echo 'Address: ' . $row['address'] . "<br />\n";
	echo 'Created on: ' . format_date($row['date_creation'], 'short') . "<br />\n";
	echo 'Last update: ' . format_date($row['date_update'], 'short') . "<br />\n";
	if ($edit)
		echo '<br /><a href="edit_org.php?org=' . $row['id_org'] . '" class="edit_lnk">Edit organisation information</a><br />';

	?>
	
	<br /></p>
	</fieldset>

	<fieldset class="info_box">
	<div class="prefs_column_menu_head"><?php echo _T('org_subtitle_representatives'); ?></div>

		<br />
		<table class="tbl_usr_dtl">
		<tr>
			<th class="heading">Representative(s):</th>
			<th class="heading">&nbsp;</th>
		</tr>
<?php

	// Show organisation representative(s)
	$q = "SELECT cl.id_client, name_first, name_middle, name_last
			FROM lcm_client_org as clo, lcm_client as cl
			WHERE id_org = $org 
				AND clo.id_client = cl.id_client";

	$result = lcm_query($q);

	while ($row = lcm_fetch_array($result)) {
		echo '<tr><td><a href="client_det.php?client=' . $row['id_client'] . '" class="content_link">';
		echo $row['name_first'] . ' ' . $row['name_middle'] . ' ' . $row['name_last'] . "</a></td>\n<td>";
		if ($edit)
			echo '<a href="edit_client.php?client=' . $row['id_client'] . '" class="content_link">Edit</a>';
		echo "</td></tr>\n";
	}

	echo "</table>";

	if ($edit)
		echo "<br /><a href=\"sel_cli_org.php?org=$org\" class=\"add_lnk\">Add representative(s)</a><br />";

	echo "<br /></fieldset>";

} else die("There's no such organisation!");

lcm_page_end();

?>
