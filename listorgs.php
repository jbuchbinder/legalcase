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

	$Id: listorgs.php,v 1.9 2005/02/15 08:32:13 mlutfy Exp $
*/

include('inc/inc.php');

lcm_page_start("List of organisations");

// List all organisations in the system + search criterion if any
$q = "SELECT id_org,name
		FROM lcm_org";

if (strlen($find_org_string) > 1) {
	// Add search criteria
	$q .= " WHERE (name LIKE '%$find_org_string%')";
}

// Do the query
$result = lcm_query($q);

echo '<form name="frm_find_org" class="search_form" action="listorgs.php" method="get">' . "\n";
echo _T('input_search_organisation') . "&nbsp;";
echo '<input type="text" name="find_org_string" size="10" class="search_form_txt" value="' .  $find_org_string . '" />';
echo '&nbsp;<input type="submit" name="submit" value="' . _T('button_search') . '" class="search_form_btn" />' . "\n";
echo "</form>\n";

// Output table tags
?>
<table class="tbl_usr_dtl" width="99%" border="0">
	<tr>
		<th class="heading">Organisation name</th>
	</tr>
<?php

for($cnt = 0; $row = lcm_fetch_array($result); $cnt++) {
	echo "<tr>\n";
	echo "<td class='tbl_cont_" . ($cnt % 2 ? "dark" : "light") . "'>";
	echo '<a href="org_det.php?org=' . $row['id_org'] . '" class="content_link">';
	echo highlight_matches(clean_output($row['name']),$find_org_string);
	echo "</td>\n";
	echo "</tr>\n";
}

?>
</table>
<br /><a href="edit_org.php" class="create_new_lnk">Add new organisation</a>

<?php
lcm_page_end();
?>
