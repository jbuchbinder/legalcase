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
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_conditions');

$filter = intval($_GET['filter']);

if ($filter > 0) {
	$q="SELECT *
		FROM lcm_filter
		WHERE id_filter=$filter";

	$result = lcm_query($q);

	// Process the output of the query
	if ($row = lcm_fetch_array($result)) {

		// Show filter details
		lcm_page_start("Filter details: " . $row['title']);

		echo "<p class='normal_text'>";

		if (true)
			echo '[<a href="edit_filter.php?filter=' . $row['id_filter'] . '" class="content_link"><strong>Edit this filter</strong></a>]<br /><br />';
		echo "\nFilter ID: " . $row['id_filter'] . "<br>\n";
		echo "Created on: " . $row['date_creation'] . "<br>\n";
		echo "Last update: " . $row['date_update'] . "<br>\n";

//
//	List the conditions in the filter
//
		echo '<h3>Filter conditions:</h3>';
		echo "\n\t\t<table border='0' class='tbl_usr_dtl'>\n";
		echo "<tr><th class='heading'>#</th>
	<th class='heading'>Field</th>
	<th class='heading'>Condition</th>
	<th class='heading'>Value</th>
</tr>";

		// Show fields included in this filter
		$q = "SELECT lcm_filter_conds.*,lcm_fields.description
			FROM lcm_filter_conds,lcm_fields
			WHERE (id_filter=$filter
				AND lcm_filter_conds.id_field=lcm_fields.id_field)
			ORDER BY 'order'";
		// Do the query
		$conds = lcm_query($q);
		// Show the results
		while ($condition = lcm_fetch_array($conds)) {
			// Order
			echo '<tr><td>' . $condition['order'] . '</td>';
			// Field description
			echo '<td>';
			if (true) echo '<a href="edit_filter_cond.php?filter=' . $filter . '&amp;cond=' . $condition['id_condition'] . '" class="content_link">';
			echo clean_output($condition['description']);
			if (true) echo '</a>';
			echo '</td>';
			// Condition description
			echo '<td>' . $GLOBALS['condition_types'][$condition['type']] . '</td>';
			echo '<td>' . $condition['value'] . '</td>';
			echo "</tr>\n";
			$last_order = $condition['order']+1;
		}
		echo "\t\t</table><br>\n";

//
//	Display add new condition form
//
		if (true) {
			echo "<form action='add_filter_cond.php' method='POST'>\n";
			echo "\t<input type='hidden' name='filter' value='$filter' />\n";
			echo "\t<table border='0' class='tbl_usr_dtl'>\n";

			// Get condition order
			echo "\t\t<tr><th class='heading'>Position</th><td>\n";
			echo "\t\t\t<select name='order'>\n";
			$i = 1;
			while ($i<$last_order) {
				echo "\t\t\t\t<option label='Insert before condition $i' value='$i'>Insert before condition $i</option>\n";
				$i++;
			}
			echo "\t\t\t\t<option selected label='Add at the end' value='$i'>Add at the end</option>\n";
			echo "\t\t\t</select>\n";
//			echo "<input type='text' name='order' value='$last_order' size='2' />";
			echo "\t\t</td></tr>\n";

			// Get field from list
			echo "\t\t<tr><th class='heading'>Field</th>\n";
			echo "\t\t\t<td><select name='field'>\n";
			echo "\t\t\t\t<option selected disabled label='' value=''>-- Select field to check from the list --</option>";
			$q = "SELECT * FROM lcm_fields ORDER BY table_name,description";
			$fields = lcm_query($q);
			$table = '';
			while ($field = lcm_fetch_array($fields)) {
				if ($field['table_name']!=$table) {
					if (!$table) echo "\t\t\t\t</optgroup>\n";
					$table = $field['table_name'];
					echo "\t\t\t\t<optgroup label='$table'>\n";
				}
//				echo "<option label='" . $field['description'] . "' value='" . $field['id_field'] . "'>" . $field['description'] . "</option>\n";
				echo "\t\t\t\t\t<option value='" . $field['id_field'] . "'>" . $field['description'] . "</option>\n";

			}
			if ($table) echo "\t\t\t\t</optgroup>\n";
			echo "\t\t\t</select></td>\n";
			echo "\t\t</tr>\n";

			// Get condition type
			echo "\t\t<tr><th class='heading'>Condition</th>\n";
			echo "\t\t\t<td>\n";
			echo select_condition('cond');
			echo "\t\t\t</td>\n";

			// Get condition value
			echo "\t\t<tr><th class='heading'>Value</th>\n";
			echo "\t\t\t<td><input type='text' name='value' /></td></tr>\n";

			echo "\t</table>\n";
			echo "\t<button type='submit' class='simple_form_btn'>Add condition</button>\n";
			echo "</form>\n";
		}
		echo "<br>\n";

	} // End of check if such filter exists in the database

	lcm_page_end();
} else {
	lcm_page_start(_T('title_error'));
	echo "<p>" . 'Error: no filter specified' . "</p>\n";
	lcm_page_end();
}

?>
