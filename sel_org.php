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

	$Id: sel_org.php,v 1.10 2006/08/22 20:16:29 mlutfy Exp $
*/

include('inc/inc.php');
lcm_page_start("Select organisation(s)");

$case = intval(_request('case'));

if (! $case > 0)
	die("There's no such case!");

$q = "SELECT *
	FROM lcm_case_client_org
	WHERE id_case=$case";

$result = lcm_query($q);

$q = "SELECT id_org,name
	  FROM lcm_org
	  WHERE id_org NOT IN (0";

// Add org in NOT IN list
while ($row = lcm_fetch_array($result)) 
	$q .= ',' . $row['id_org'];

$q .= ')';

$result = lcm_query($q);

show_context_start();
show_context_case_title($case);
show_context_case_involving($case);
show_context_end();

?>
<form action="add_client.php" method="post">

	<table border="0" class="tbl_usr_dtl">
		<tr>
			<th class="heading">&nbsp;</th>
			<th class="heading" width="350"><?php echo _Th('org_input_name'); ?></th>
		</tr>
<?php
	while ($row = lcm_fetch_array($result)) {
		echo "<tr>\n";
		echo '<td><input type="checkbox" name="orgs[]" id="org_' . $row['id_org'] . '" value="' . $row['id_org'] . '"></td>';
		echo '<td><label for="org_' . $row['id_org'] . '">' . $row['name'] . "</label></td>\n";
		echo "</tr>\n";
	}
?>
		<tr>
			<td>&nbsp;</td>
			<td><a href="edit_org.php" class="content_link"><?php echo _T('org_button_new'); ?></a></td>
		</tr>
	</table>

	<input type="hidden" name="case" value="<?php echo $case; ?>">
	<input type="hidden" name="ref_sel_org" value="<?php echo $_SERVER['HTTP_REFERER']; ?>">
	<p><button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate'); ?></button></p>

</form>
<?php

lcm_page_end();

?>
