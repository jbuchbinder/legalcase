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

	$Id: listorgs.php,v 1.11 2005/03/14 08:09:51 mlutfy Exp $
*/

include('inc/inc.php');

lcm_page_start("List of organisations"); // TRAD
show_find_box('org', $find_org_string);

// List all organisations in the system + search criterion if any
$q = "SELECT id_org,name
		FROM lcm_org";

if (strlen($find_org_string) > 1)
	$q .= " WHERE (name LIKE '%$find_org_string%')";

$result = lcm_query($q);

// Output table tags
echo '<table class="tbl_usr_dtl" width="99%" border="0">' . "\n";
echo "<tr>\n";
echo "<th class='heading'>" . "Organisation name" . "</th>\n"; // TRAD
echo "</tr>\n";

for($cnt = 0; $row = lcm_fetch_array($result); $cnt++) {
	echo "<tr>\n";
	echo "<td class='tbl_cont_" . ($cnt % 2 ? "dark" : "light") . "'>";
	echo '<a href="org_det.php?org=' . $row['id_org'] . '" class="content_link">';
	echo highlight_matches(clean_output($row['name']), $find_org_string);
	echo "</td>\n";
	echo "</tr>\n";
}

echo "</table>\n";
echo '<p><a href="edit_org.php" class="create_new_lnk">' . "Register new organisation" . "</a></p>\n"; // TRAD
echo "<br />\n";

lcm_page_end();

?>
