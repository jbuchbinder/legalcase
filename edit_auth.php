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

	$Id: edit_auth.php,v 1.18 2006/07/27 15:31:22 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Initialise variables
$case = intval($_GET['case']);

if (! ($case > 0)) {
	lcm_page_start(_T('title_error'));
	echo "<p>" . _T('error_no_case_specified') . "</p>\n";
	lcm_page_end();
	exit;
}

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

lcm_page_start(_T('title_case_edit_ac'));
lcm_bubble('case_ac');

show_context_start();
show_context_case_title($case);
show_context_case_involving($case);
show_context_end();

?>

<form action="upd_auth.php" method="post">
	<table border="0" class="tbl_usr_dtl" width="99%">
	<tr>
		<th align="center" class="heading"><?php echo _Th('case_input_author'); ?></th>
		<th align="center" class="heading"><?php echo _Th('case_ac_input_rights'); ?></th>
	</tr>

<?php

		while ($row = lcm_fetch_array($result)) {
			echo "<tr>\n";

			// User name
			echo '<td align="left">';
			echo '<a href="author_det.php?author=' . $row['id_author'] . '" class="content_link"'
				. ' title="' . _T('case_tooltip_view_author_details', array('author' => get_person_name($row))) . '">'
				. get_person_name($row) 
				. '</a>';
			echo "</td>\n";

			// Access rights in case
			echo '<td align="center">';
			echo '<select name="auth[' . $row['id_author'] . ']">' . "\n";

			$all_rights = array('read', 'write', /* 'edit', */ 'admin', '', 'remove');

			foreach($all_rights as $ac) {
				$sel = ($row['ac_' . $ac] ? ' selected="selected" ' : '');
				$dis = (! $ac ? ' disabled="disabled" ' : '');
				$title = ($ac ? _T('case_input_option_ac_' . $ac) : '');
				echo '<option value="' . $ac . '"' . $sel . $dis . '>' . $title . "</option>\n";
			}

			echo "</select>\n";
			echo "</td>\n";

			echo "</tr>\n";
		}

	echo "</table>\n";
	echo '<p><button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button></p>\n";
	echo '<input type="hidden" name="case" value="' . $case . '" />' . "\n";

	$link = new Link($_SERVER['HTTP_REFERER']);
	echo '<input type="hidden" name="ref_edit_auth" value="' . $link->getUrl() . '"/>' . "\n";
	echo "</form>\n";

	lcm_page_end();
?>
