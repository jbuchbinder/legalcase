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

	$Id: listorgs.php,v 1.5 2004/11/16 08:38:03 mlutfy Exp $
*/

include('inc/inc.php');

// Prepare query
$q = "SELECT id_org,name
		FROM lcm_org";

if (strlen($find_org_string)>1) {
	// Add search criteria
	$q .= " WHERE (name LIKE '%$find_org_string%')";
	lcm_page_start("Organisation(s), containing '$find_org_string':");
} else {
	lcm_page_start("List of organisation(s)");
}

// Do the query
$result = lcm_query($q);

// Output table tags
?>
<table class="tbl_usr_dtl">
	<tr>
		<th class="heading">Organisation name</th>
		<th class="heading">&nbsp;</th>
	</tr>
<?php
while ($row = lcm_fetch_array($result)) {
?>
	<tr>
		<td><a href="org_det.php?org=<?php echo $row['id_org'] . '" class="content_link">';
		echo highlight_matches(clean_output($row['name']),$find_org_string);
?></td>
		<td><a href="edit_org.php?org=<?php echo $row['id_org']; ?>" class="content_link">Edit</a></td>
	</tr>
<?php
}
?>
	<tr>
		<td><a href="edit_org.php" class="content_link"><strong>Add new organisation</strong></a></td>
		<td>&nbsp;</td>
	</tr>
</table>
<?php

lcm_page_end();
?>
