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

	$Id: edit_auth.php,v 1.12 2005/04/07 16:53:15 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Initialise variables
$case = intval($_GET['case']);

if (! ($case > 0))
	die("Which case?");

if (! allowed($case,'a'))
	die("You don't have permission to edit this case's access rights.");

$q = "SELECT *
	FROM lcm_case_author,lcm_author
	WHERE (id_case=$case
	  AND lcm_case_author.id_author=lcm_author.id_author";

if ($author > 0)
	$q .= " AND lcm_author.id_author=$author";

$q .= ')';

$result = lcm_query($q);

lcm_page_start("Edit author's rights on case $case"); // TRAD

echo '<form action="upd_auth.php" method="post">' . "\n";

// TRAD TRAD TRAD ...
?>
		<table border="0" class="tbl_usr_dtl" width="99%">
			<tr><th align="center" class="heading">User</th>
				<th align="center" class="heading">Read</th>
				<th align="center" class="heading">Write</th>
				<th align="center" class="heading">Edit</th>
				<th align="center" class="heading">Admin</th>
			</tr>
<?php

		while ($row = lcm_fetch_array($result)) {
			echo "<tr>\n";

			echo '<td align="left">';
			echo get_person_name($row) . "</td>\n";

			echo '<td align="center">';
			echo '<input type="checkbox" name="auth[' . $row['id_author'] . "][ac_read]" . '" value="1"';
			if ($row['ac_read']) echo ' checked';
			echo "></td>\n";

			echo '<td align="center">';
			echo '<input type="checkbox" name="auth[' . $row['id_author'] . "][ac_write]" . '" value="1"';
			if ($row['ac_write']) echo ' checked';
			echo "></td>\n";

			echo '<td align="center">';
			echo '<input type="checkbox" name="auth[' . $row['id_author'] . "][ac_edit]" . '" value="1"';
			if ($row['ac_edit']) echo ' checked';
			echo "></td>\n";

			echo '<td align="center">';
			echo '<input type="checkbox" name="auth[' . $row['id_author'] . "][ac_admin]" . '" value="1"';
			if ($row['ac_admin']) echo ' checked';
			echo "></td>\n";
		}
	?>

			</tr>
		</table>

		<p><button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate'); ?></button></p>
		<input type="hidden" name="case" value="<?php echo $case; ?>">
		<input type="hidden" name="ref_edit_auth" value="<?php echo $HTTP_REFERER; ?>">

	</form>

<?php
	lcm_page_end();
?>
