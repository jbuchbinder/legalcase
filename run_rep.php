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

	$Id: run_rep.php,v 1.11 2005/02/09 09:59:05 mlutfy Exp $
*/

include('inc/inc.php');

// Report ID
$rep = intval($_GET['rep']);

//
// Show title and description of the report
//

$q = "SELECT *
		FROM lcm_report
		WHERE id_report=$rep";

$result = lcm_query($q);

if ($row = lcm_fetch_array($result))
	lcm_page_start("Report: " . $row['title']);
else
	die("There is no such report!");

echo "<p>" . $row['description'] . "</p>\n";

//
// Get report columns fields, store into $my_columns for later
//

$my_columns = array();
$q = "SELECT f.id_field, f.field_name, f.table_name, f.enum_type
		FROM lcm_rep_col as c, lcm_fields as f
		WHERE c.id_report = $rep
			AND c.id_field = f.id_field
		ORDER BY c.col_order";

$result = lcm_query($q);

while ($row = lcm_fetch_array($result))
	array_push($my_columns, $row);

//
// Get report line fields, store into $my_lines for later
//

$my_lines = array();
$q = "SELECT field_name, table_name
		FROM lcm_rep_line as l, lcm_fields as f
		WHERE id_report = " . $rep . "
		AND l.id_field = f.id_field
		ORDER BY col_order ASC";

$result = lcm_query($q);

while ($row = lcm_fetch_array($result)) {
	$my_line_table = $row['table_name'];
	array_push($my_lines, $row['field_name']);
}

if (! empty($my_lines) && ! $my_line_table)
	lcm_panic("Internal error: line fields are present, but no source table was specified");

//
// Add implicit fields, if necesary
// For example, if we select fields from lcm_author, we should include id_author
// even if we don't want to show it (for table joining, later).
// 

$my_line_fields_implicit = "";

if (preg_match("/^lcm_(.*)$/", $my_line_table, $regs)) {
	$temp = "id_" . $regs[1];
	$temp_exists = false;

	// Add it only if it was not already selected
	foreach($my_lines as $l)
		if ($l == $temp)
			$temp_exists = true;
	
	if (! $temp_exists) {
		$my_line_fields_implicit = $temp;
		echo "<!-- Implicit param: " . $my_line_fields_implicit . " -->\n";
	}
}


//
// Start building the SQL query for the report lines
//

$my_line_fields = implode(", ", $my_lines);

if ($my_line_fields && $my_line_fields_implicit) {
	$my_line_fields .= ", " . $my_line_fields_implicit;
}

$q = "SELECT " . $my_line_fields . "
		FROM " . $my_line_table;
// TODO: WHERE .... (each line type can propose ready filters?)

$result = lcm_query($q);

echo "<table width='99%' border='1'>";

while ($row = lcm_fetch_array($result)) {
	echo "<tr>\n";

	// Line information
	echo "<td>\n";

	// Show only the explicitely requested fields, not implicit
	foreach ($my_lines as $l)
		echo $row[$l] . " ";

	echo "</td>\n";

	// Column information
	foreach ($my_columns as $col) {
		// Table-join condition
		$from = $my_line_table;
		if ($col['table_name'] != $my_line_table)
			$from .= ", " . $col['table_name'];

		$where = '';

		switch ($my_line_table) {
			case 'lcm_case':
				$where = " lcm_case.id_case = ";
				break;
			case 'lcm_author': 
				$where = " lcm_author.id_author = ";
				break;
			case 'lcm_client':
				$where = " lcm_client.id_client = ";
				break;
			case 'lcm_followup':
				$where = " lcm_followup.id_followup = " . $row['id_followup'];
				// $where .= " lcm_followup.id_author ";
				break;
			default:
				lcm_panic("internal error: table = " . $my_line_table);
		}

		switch ($col['table_name']) {
			case 'lcm_case':
				if ($my_line_table == 'lcm_author') {
					$from  .= ", lcm_case_author";
					$where .= "lcm_case_author.id_author AND lcm_case_author.id_case = lcm_case.id_case";

					// This can be determined automatically
					$where .= " AND lcm_author.id_author = " . $row['id_author'];
				}
				break;
			case 'lcm_author': 
				$where .= " lcm_author.id_author ";
				break;
			case 'lcm_client':
				$where .= " lcm_client.id_client ";
				break;
			case 'lcm_followup':
				if ($my_line_table != 'lcm_followup')
					$where .= " lcm_followup.id_author ";

				if ($my_line_table == 'lcm_author') {
					$where .= " AND lcm_followup.id_author = " . $row['id_author'];
				}

				break;
			default:
				lcm_panic("internal error: table = " . $col['table_name']);

		}

		if ($col['enum_type']) {
			$enum_info = explode(":", $col['enum_type']);
			$enum_src = $enum_info[0]; // keyword
			$enum_type = $enum_info[1]; // system_kwg
			$enum_group = $enum_info[2]; // ex: followups

			if ($enum_src == 'keyword') {
				global $system_kwg;

				foreach ($system_kwg[$enum_group]['keywords'] as $kw) {
					lcm_log("TYPE = " . $kw['name']);

					// FIXME: COUNT(*), AVG(date_end - date_start), SUM(date_end - date_start)
					$q1 = "SELECT COUNT(*)
							FROM " . $from . "
							WHERE " . $col['field_name'] . " = '" . $kw['name'] . "'
								AND " . $where;

					$result1 = lcm_query($q1);

					$val = lcm_fetch_array($result1);
					echo "<td>" . $val[0] . "</td>\n";

				}
			} else {
				lcm_panic("unknown enum_src = " . $enum_src);
			}

		} else {
			$q1 = "SELECT " . $col['field_name'] . "
					FROM " . $from . "
					WHERE " . $where;
	
			$result1 = lcm_query($q1);
			$val = lcm_fetch_array($result1);
	
			echo "<td>" . $val[0] . "</td>\n";
		}
	}
	
	echo "</tr>\n";
}

echo "</table>\n";

/*

//
// Get report columns
//
$q = "SELECT *
		FROM lcm_rep_col as c, lcm_fields as f
		WHERE c.id_report = $rep
			AND c.id_field = f.id_field
		ORDER BY c.col_order";

$result = lcm_query($q);

// Process report column data to prepare SQL query
$fl = '';		// fields list
$ta = array();	// tables array
$sl = '';		// sort list
$sl_text = '';	// Sorting explaination

while ($row = lcm_fetch_array($result)) {
	if ($fl) $fl .= ',';

	$fl .= $row['table_name'] . '.' . $row['field_name'] . " AS '" . $row['header'] . "'";

	if (!in_array($row['table_name'],$ta))
		$ta[] = $row['table_name'];

	if ($row['sort']) {
		if ($sl) $sl .= ',';
		$sl .= $row['table_name'] . '.' . $row['field_name'] . " " . $row['sort'];
		$sl_text .= ( $sl_text ? ', "' : '"' ) . $row['description'] . '"';
	}

	if ($row['total']) {
		$totals[$row['col_order']] = 0;
	}
}

// Get report filters
$q = "SELECT lcm_filter.*
		FROM lcm_rep_filters,lcm_filter
		WHERE (id_report=$rep
			AND lcm_rep_filters.id_filter = lcm_filter.id_filter)";

$result = lcm_query($q);

// Process each filter
$filter_text = '';
while ($filter = lcm_fetch_array($result)) {
	// Add filter name to the list
	$filter_text .= ( $filter_text ? ', "' : '"' ) . $filter['title'] . '"';
	// Get filter conditions
	$q = "SELECT *
			FROM lcm_filter_conds,lcm_fields
			WHERE (lcm_filter_conds.id_filter=" . $filter['id_filter'] . "
				AND lcm_filter_conds.id_field=lcm_fields.id_field)
			ORDER BY lcm_filter_conds.cond_order";
	$res_cond = lcm_query($q);

	// Process conditions
	$cl = '';	// Conditions list
	while ($condition = lcm_fetch_array($res_cond)) {
		// Add logical operand, if necessary
		$cl .= ( $cl ? ' ' . $filter['type'] . ' (' : '(');
		// Add field and table if not added yet
		$cl .= $condition['table_name'] . '.' . $condition['field_name'];
		if (!in_array($condition['table_name'],$ta)) $ta[] = $condition['table_name'];
		// Add condition operand and value
		switch ($condition['type']) {
			case 1:
				$cl .= '=' . $condition['value'];
				break;
			case 2:
				$cl .= '<' . $condition['value'];
				break;
			case 3:
				$cl .= '>' . $condition['value'];
				break;
			case 4:
				$cl .= " LIKE '%" . $condition['value'] . "%'";
				break;
			case 5:
				$cl .= " LIKE '" . $condition['value'] . "%'";
				break;
			case 6:
				$cl .= " LIKE '%" . $condition['value'] . "%'";
				break;

		}
		$cl .= ')';
	}
}

$wl = ( ($cl) ? "($cl)" : '');	// WHERE clause list

// Add implied relations between tables included in the report
if (in_array('lcm_case',$ta) && in_array('lcm_author',$ta)) {
	$ta[] = 'lcm_case_author';

	if ($wl)
		$wl .= ' AND ';

	$wl .= ' lcm_case.id_case = lcm_case_author.id_case AND lcm_author.id_author = lcm_case_author.id_author';
}

// Convert array of table names into string list
$tl = implode(',',$ta);

echo "\n<!-- ";
echo "\t * FL = $fl\n";
echo "\t * TL = $tl\n";
echo "\t * WL = $wl\n";
echo "-->\n";


if ($fl && $tl) { //  && $wl) {
	// Get report data
	$q = "SELECT $fl
			FROM $tl\n";
	
	if ($wl)
		$q .= "\tWHERE ($wl)\n";

	if ($sl)
		$q .= "\tORDER BY $sl";

	$result = lcm_query($q);

	// Diagnostic: show query into HTML
	echo "<!-- query is: '$q' -->\n";

	// Show report data
	if (lcm_num_rows($result)>0) {
		if ($sl_text) echo "Sorted by: $sl_text<br>\n";
		if ($filter_text) echo "Filters applied: $filter_text<br>\n";
		echo "<table border='0' class='tbl_usr_dtl'>\n";
		echo "\t<tr>\n";
		// Show column headers
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo "\t\t<th class='heading'>" . mysql_field_name($result,$i) . "</th>\n";
		}
		echo "\t</tr>\n";
		// Show report data rows
		while ($row = lcm_fetch_array($result)) {
			echo "\t<tr>\n";
			for ($j=0; $j<$i; $j++) {
				echo "\t\t<td>" . $row[$j] . "</td>\n";
				if (isset($totals[$j+1])) $totals[$j+1] += $row[$j];
			}
			echo "\t</tr>\n";
		}
		// Show totals (if any)
		echo "\t<tr>\n";
		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo "\t\t<td>" . ($totals[$i+1] ? '<strong>' . $totals[$i+1] . '</strong>' : '') . "</td>\n";
		}
		echo "\t</tr>\n";

		echo "</table>";
	}
}
*/

echo '<p><a href="' . ($GLOBALS['HTTP_REFERER'] ? $GLOBALS['HTTP_REFERER'] : "rep_det.php?rep=$rep") . '" class="run_lnk">Back</a></p>';

lcm_page_end();

?>
