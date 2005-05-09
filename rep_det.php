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

	$Id: rep_det.php,v 1.25 2005/05/09 15:41:41 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');
include_lcm('inc_conditions');
include_lcm('inc_keywords');

$rep = intval($_GET['rep']);

if (! $rep > 0) {
	lcm_page_start(_T('title_error'));
	echo "<p>" . 'Error: no report specified' . "</p>\n";
	lcm_page_end();
}

//
// Fetch general info on report
//

$q="SELECT *
	FROM lcm_report
	WHERE id_report = $rep";

$result = lcm_query($q);
$rep_info = lcm_fetch_array($result);

if (! $rep_info) {
	lcm_page_start(_T('title_error'));
	echo "<p>The report does not exist (ID = " . $rep . ").</p>";
	lcm_page_end();
	exit;
}

//
// TEMPORARY patch
// Since col_src_name/type are not currently stored, calculate them
//

$q = "SELECT f.table_name
		FROM lcm_fields as f, lcm_rep_col as c
		WHERE f.id_field = c.id_field
		AND c.id_report = " . $rep;

$result = lcm_query($q);
$tmp_info = lcm_fetch_array($result);

$rep_info['col_src_name'] = $tmp_info['table_name'];

//
// Show info on the report
//

lcm_page_start(_T('title_rep_view') . " " . $rep_info['title'], '', '', 'report_intro');
lcm_bubble("The report function is still in development."); // XXX

$edit = (($GLOBALS['author_session']['status'] == 'admin') ||
		($rep_info['id_author'] == $GLOBALS['author_session']['id_author']));

echo "<fieldset class='info_box'>";
show_page_subtitle(_T('generic_subtitle_general'), 'report_intro');

echo "<p class='normal_text'>";
echo _Ti('rep_title') . $rep_info['title'] . "<br />\n";
echo _Ti('time_input_date_creation') . format_date($rep_info['date_creation']) . "</p>\n";

if ($rep_info['description'])
	echo '<p class="normal_text">' . $rep_info['description'] . '</p>' . "\n";

if ($edit)
	echo '<p><a href="edit_rep.php?rep=' . $rep_info['id_report'] . '" class="edit_lnk">' . "Edit report" . '</a>&nbsp;'; // TRAD

echo '<a href="run_rep.php?rep=' . $rep_info['id_report'] . '" class="run_lnk">Generate report</a>&nbsp;'; // TRAD
echo '<a href="run_rep.php?export=csv&amp;rep=' . $rep_info['id_report'] . '" class="exp_lnk">Export report</a>'; // TRAD
echo "</p></fieldset>";

//
// Matrix line
//

echo '<a name="line"></a>' . "\n";
echo "<fieldset class='info_box'>";
show_page_subtitle("Report line information", 'report_edit', 'line'); // TRAD

// Extract source type, if any
if ($rep_info['line_src_type'] && $rep_info['line_src_name']) {
	if ($rep_info['line_src_type'] == 'keyword') {
		$kwg = get_kwg_from_name($rep_info['line_src_name']);
		echo "<p class='normal_text'>Source: " . $rep_info['line_src_type'] 
			. " (" . $kwg['type'] . ") -> " . $rep_info['line_src_name']; // TRAD
	} else {
		echo "<p class='normal_text'>Source: " . $rep_info['line_src_type'] . " -> " . $rep_info['line_src_name']; // TRAD
	}

	// Show list of fields for line, if any
	$my_fields = array();
	$query = "SELECT rl.id_line, f.id_field, f.description 
		FROM lcm_rep_line as rl, lcm_fields as f
		WHERE id_report = " . $rep_info['id_report'] . "
			AND rl.id_field = f.id_field
		ORDER BY col_order ASC";

	$result_lines = lcm_query($query);

	if (lcm_num_rows($result_lines)) {
		echo "</p>\n";
		echo "<table border='0' class='tbl_usr_dtl' width='99%'>\n";

		while ($line = lcm_fetch_array($result_lines)) {
			echo "<tr>\n";
			echo "<td>" . _Th($line['description']) . "</td>\n";
			echo "<td><a href='upd_rep_field.php?rep=" . $rep_info['id_report'] . "&amp;"
				. "remove=line" . "&amp;" . "id_line=" . $line['id_line'] . "' class='content_link'>" . "Remove" . "</a></td>\n";
			echo "</tr>\n";
			array_push($my_fields, $line['id_field']);
		}

		echo "</table>\n";
	} else {
		// Allow to change the source table
		echo ' <a href="upd_rep_field.php?rep=' . $rep_info['id_report'] 
				. '&amp;unselect_line=1" class="content_link">' . "Remove" . '</a>';
		echo "</p>\n";
	}

	// Add field (if line_src_type == table)
	// TODO: add 'not in (...existing fields..)
	$query = "SELECT *
				FROM lcm_fields
				WHERE table_name = 'lcm_" . $rep_info['line_src_name'] . "'";
	
	$result = lcm_query($query);

	if (lcm_num_rows($result)) {
		echo "<form action='upd_rep_field.php' name='frm_line_additem' method='get'>\n";
		echo "<input name='rep' value='" . $rep_info['id_report'] . "' type='hidden' />\n";
		echo "<input name='add' value='line' type='hidden' />\n";

		echo "<p class='normal_text'>Add an item: ";
		echo "<select name='id_field' class='sel_frm'>";

		while ($row = lcm_fetch_array($result)) {
			echo "<option value='" . $row['id_field'] . "'>" . _Th($row['description']) . "</option>\n";
		}
		
		echo "</select>\n";
		echo "<button class='simple_form_btn' name='validate_line_additem'>" . _T('button_validate') . "</button>\n";
		echo "</p>\n";
		echo "</form>\n";
	}
} else {
	echo "<form action='upd_rep_field.php' name='frm_line_source' method='post'>\n";
	echo "<input name='rep' value='" . $rep_info['id_report'] . "' type='hidden' />\n";
	echo "<p class='normal_text'>Select source table: "; // TRAD
	echo "<input name='select_line_type' value='table' type='hidden' />\n";
	echo "<select name='select_line_name' class='sel_frm'>
			<option value='author'>Author</option>
			<option value='case'>Case</option>
			<option value='client'>Client</option>
			<option value='followup'>Follow-up</option>
		</select>\n";

	echo "<button class='simple_form_btn' name='validate_line_source'>" . _T('button_validate') . "</button>\n";
	echo "</p>\n";
	echo "</form>\n";

	echo "<form action='upd_rep_field.php' name='frm_line_source' method='post'>\n";
	echo "<input name='rep' value='" . $rep_info['id_report'] . "' type='hidden' />\n";
	echo "<p class='normal_text'>or keyword: "; // TRAD
	echo "<input name='select_line_type' value='keyword' type='hidden' />\n";

	$all_kwgs = get_kwg_all('', true);

	echo "<select name='select_line_name' class='sel_frm'>\n";

	foreach ($all_kwgs as $kwg)
		echo "<option value='" . $kwg['name'] . "'>" . $kwg['type'] . " - " . _T(remove_number_prefix($kwg['title'])) . "</option>\n";

	echo "</select>\n";

	echo "<button class='simple_form_btn' name='validate_line_source_kw'>" . _T('button_validate') . "</button>\n";
	echo "</p>\n";
	echo "</form>\n";
}

echo "</fieldset>\n";

//
//	List the columns in the report
//

	echo '<a name="column"></a>' . "\n";
	echo "<fieldset class='info_box'>\n";
	show_page_subtitle("Report columns", 'report_edit', 'columns');
		
	echo "<p class='normal_text'>\n";
	echo "<table border='0' class='tbl_usr_dtl' width='99%'>\n";
	echo "<tr><th class='heading'>#</th>
		<th class='heading'>Header</th>
		<th class='heading'>Table</th>
		<th class='heading'>Contents</th>
		<th class='heading'>Group</th>
		<th class='heading'>Sort</th>
		<th class='heading'>Total</th>
		<th class='heading'>Action</th>
	</tr>";

		// Show fields included in this report
		$q = "SELECT lcm_rep_col.*,lcm_fields.description, lcm_fields.table_name
			FROM lcm_rep_col,lcm_fields
			WHERE (id_report=$rep
				AND lcm_rep_col.id_field=lcm_fields.id_field)
			ORDER BY 'col_order'";
		// Do the query
		$cols = lcm_query($q);
		$rows = lcm_num_rows($cols);
		// Show the results
		while ($column = lcm_fetch_array($cols)) {
			// Display column order
			echo '<tr><td>' . $column['col_order'] . "</td>\n";

			// Display column header
			echo '<td>';
			if ($edit) echo '<a href="edit_rep_col.php?rep=' . $rep . '&amp;col=' . $column['id_column'] . '" class="content_link">';
			echo clean_output($column['header']);
			if ($edit) echo '</a>';
			echo "</td>\n";

			// Display column table (temporary, [ML])
			echo '<td>';
			echo $column['table_name'];
			echo "</td>\n";

			// Display column description
			echo '<td>';
			if ($edit) echo '<a href="edit_rep_col.php?rep=' . $rep . '&amp;col=' . $column['id_column'] . '" class="content_link">';
			echo clean_output($column['description']);
			if ($edit) echo '</a>';
			echo "</td>\n";

			//Display column grouping
			echo '<td>';
			echo ($column['col_group'] ? $column['col_group'] : "None");
			echo "</td>\n";

			//Display sort setting
			echo '<td>';
			switch ($column['sort']) {
				case 'asc':
					echo "Asc";
					break;
				case 'desc':
					echo "Desc";
					break;
				default:
					echo "None";
			}
			echo "</td>\n";

			// Display total setting
			echo '<td>' . (($column['total']) ? 'Yes' : 'No') . "</td>\n";

			// Display allowed actions
			echo '<td>';
			if ($edit) {
				if ($column['col_order'] > 1)
					echo "<a class='content_link' href='move_rep_col.php?rep=$rep&amp;col=" . $column['id_column'] . "&amp;old=" . $column['col_order'] . "&amp;new=" . ($column['col_order']-1) . "'>^</a> ";
				if ($column['col_order'] < $rows)
					echo "<a class='content_link' href='move_rep_col.php?rep=$rep&amp;col=" . $column['id_column'] . "&amp;old=" . $column['col_order'] . "&amp;new=" . ($column['col_order']+1) . "'>v</a> ";
				echo "<a href='upd_rep_field.php?rep=$rep" . "&amp;" 
						. "remove=column" . "&amp;" . "id_column=" . $column['id_column'] . "' "
						. "class='content_link'>" . "X" . "</a>";
			}
			echo "</td>\n";
			echo "</tr>\n";
			$last_order = $column['col_order']+1;
		}
		echo "\t\t</table><br>\n";

//
//	Display add new column form
//

if ($edit) {
	echo "<form action='add_rep_col.php' method='post'>\n";
	echo "\t<input type='hidden' name='rep' value='$rep' />\n";
	echo "\t<table border='0' class='tbl_usr_dtl' width='99%'>\n";

	// Get field from list
	echo "\t\t<tr><th class='heading'>Contents</th>\n";
	echo "\t\t\t<td><select name='field' class='sel_frm'>\n";
	echo "\t\t\t\t<option selected disabled label='' value=''>-- Select column content from the list --</option>";

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


	// Get column order
	echo "\t\t<tr><th class='heading'>Position</th><td>\n"; // TRAD
	echo "\t\t\t<select name='order' class='sel_frm'>\n";

	$i = 1;
	while ($i<$last_order) {
		echo "\t\t\t\t<option label='Insert before column $i' value='$i'>Insert before column $i</option>\n";
		$i++;
	}

	echo "\t\t\t\t<option selected label='Add at the end' value='$i'>Add at the end</option>\n";
	echo "\t\t\t</select>\n";
	//			echo "<input type='text' name='order' value='$last_order' size='2' />";
	echo "\t\t</td></tr>\n";

	// Get column header
	echo "\t\t<tr><th class='heading'>Header</th>\n";
	echo "\t\t\t<td><input type='text' name='header' class='search_form_txt' /></td></tr>\n";

	// Get grouping setting
	echo "\t\t<tr><th class='heading'>Grouping</th>\n";
	echo "\t\t\t<td><select name='sort' class='sel_frm'>\n";
	echo "\t\t\t\t<option selected label='None' value=''>None</option>\n";
	echo "\t\t\t\t<option label='Count' value='COUNT'>COUNT</option>\n";
	echo "\t\t\t\t<option label='Sum' value='SUM'>SUM</option>\n";
	echo "\t\t\t</select></td>\n";
	echo "\t\t</tr>";

	// Get sort setting
	echo "\t\t<tr><th class='heading'>Sorting</th>\n";
	echo "\t\t\t<td><select name='sort' class='sel_frm'>\n";
	echo "\t\t\t\t<option selected label='None' value=''>None</option>\n";
	echo "\t\t\t\t<option label='Ascending' value='asc'>Ascending</option>\n";
	echo "\t\t\t\t<option label='Descending' value='desc'>Descending</option>\n";
	echo "\t\t\t</select></td>\n";
	echo "\t\t</tr>";

	echo "\t</table>\n";
	echo "<p><button type='submit' class='simple_form_btn'>" . _T('button_validate') . "</button></p>\n";
	echo "</form>\n";
}

echo "</fieldset>\n";

//
// Report filters
//

echo '<a name="filter"></a>' . "\n";
echo "<fieldset class='info_box'>";
show_page_subtitle("Report filters", 'report_edit', 'filters');

include_lcm('inc_conditions');
show_report_filters($rep, false);

echo "</fieldset>\n";

lcm_page_end();

?>
