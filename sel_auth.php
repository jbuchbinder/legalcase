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

	$Id: sel_auth.php,v 1.8 2005/03/19 15:42:12 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');

global $prefs;

if ($case > 0) {
	$destination = "case_det.php?id_case=" . $case;

	if (isset($GLOBALS['HTTP_REFERER']))
		$destination = $GLOBALS['HTTP_REFERER'];
	
	$dest_link = new Link($destination);

	if (allowed($case,'a')) {
		// Prepare query
		$q = "SELECT *
			FROM lcm_case_author
			WHERE id_case=$case";

		$result = lcm_query($q);

		// Prepare list query
		$q = "SELECT id_author,name_first,name_middle,name_last
			FROM lcm_author
			WHERE id_author NOT IN (0";

		// Add clients to NOT IN list
		while ($row = lcm_fetch_array($result)) {
			$q .= ',' . $row['id_author'];
		}
		$q .= ')';

		// Sort by name_first
		$order_name = 'ASC';
		if (isset($_REQUEST['order_name']))
			if ($_REQUEST['order_name'] == 'ASC' || $_REQUEST['order_name'] == 'DESC')
				$order_name = $_REQUEST['order_name'];
		
		$q .= " ORDER BY name_first " . $order_name;

		$result = lcm_query($q);
		$number_of_rows = lcm_num_rows($result);

		// Check if any author(s) available for selection
		if (lcm_num_rows($result) > 0)
			lcm_page_start("Select users(s)"); // TRAD
		else {
			header('Location: ' . $dest_link->getUrlForHeader());
			exit;
		}

		show_context_start();
		show_context_case_title($case);
		show_context_case_involving($case);
		show_context_end();

		echo '<form action="add_auth.php" method="post">' . "\n";

		$headers = array();
		$headers[0]['title'] = '';
		$headers[0]['order'] = 'no_order';
		$headers[1]['title'] = 'Name'; // TRAD
		$headers[1]['order'] = 'order_name';

		$list_pos = get_list_pos($result);
		show_list_start($headers);

		for ($i = 0 ; (($i < $prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
			echo "<tr>\n";
			echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">';
			echo '<input type="checkbox" name="authors[]" value="' . $row['id_author'] . '" />';
			echo "</td>\n";
			echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . get_person_name($row) . "</td>\n";
			echo "</tr>\n";
		}

		show_list_end($list_pos, $number_of_rows);
	?>

		<input type="hidden" name="case" value="<?php echo $case; ?>" />
		<input type="hidden" name="ref_sel_auth" value="<?php echo $dest_link->getUrl(); ?>" />
		<button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate'); ?></button>
	</form>
	<?php
	} else die("You don't have permission to add users to this case!");
} else die("Which case?");

lcm_page_end();
?>
