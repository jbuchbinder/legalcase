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

	$Id: run_rep.php,v 1.13 2005/05/09 07:48:02 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_keywords');

function get_table_suffix($table) {
	if ($table == 'lcm_author')
		return "a";
	elseif ($table == 'lcm_followup')
		return "fu";
	elseif ($table == 'lcm_case')
		return "c";
	elseif ($table == 'lcm_client')
		return "cl";

	return "";
}

function suffix_table($table) {
	$suffix = get_table_suffix($table);

	if ($suffix)
		return " as " . get_table_suffix($table) . " ";
	else
		return "";
}

function suffix_field($table, $field) {
	$suffix = get_table_suffix($table);

	if (preg_match("/^IF/", $field))
		return $field;

	if ($suffix)
		return $suffix . "." . $field;
	else
		return $table . "." . $field;
}

function join_tables($table1, $table2 = '', $id1 = 0, $id2 = 0) {
	$sql = "";

	lcm_log("join_tables: " . $table1 . " - " . $table2 . " id1 = " . $id1 . " id2 = " . $id2);

	switch($table1) {
		case 'lcm_author':
			switch($table2) {
				case 'lcm_author':
					lcm_panic("linking author with author: not recommended");
					break;
				case 'lcm_case':
					$sql .= " a.id_author = c.id_author ";
					if ($id1)
						$sql .= " AND a.id_author = $id1 ";
					if ($id2)
						$sql .= " AND c.id_author = $id2 ";
					break;
				case 'lcm_followup':
					$sql .= " a.id_author = fu.id_author ";
					if ($id1)
						$sql .= " AND a.id_author = $id1 ";
					if ($id2)
						$sql .= " AND fu.id_author = $id2 ";
					break;
				case 'lcm_client':
					lcm_panic("not implemented");
					break;
				case 'lcm_org':
					lcm_panic("not implemented");
					break;
				case '':
					if ($id1)
						$sql  .= " AND id_author = $id1 ";
					break;
				default:
					lcm_panic("case not implemented ($table2)");
					break;
			}

			break;

		case 'lcm_case':
			switch($table2) {
				case '':
					if ($id1)
						$sql .= " AND id_case = $id1 ";
			}

			break;

		case 'lcm_followup':

			break;

		case 'lcm_client':

			break;

		case 'lcm_org':

			break;
	}

	return $sql;
}

// Restrict page to administrators
if ($author_session['status'] != 'admin') {
	lcm_page_start('Run report', '', '', 'report_intro'); // TRAD
	echo "<p>Warning: Access denied, not admin.\n"; // TRAD
	lcm_page_end();
	exit;
}

// Report ID
$rep = intval($_GET['rep']);

//
// Show title and description of the report
//

$q = "SELECT *
		FROM lcm_report
		WHERE id_report=$rep";

$result = lcm_query($q);

if ($rep_info = lcm_fetch_array($result))
	lcm_page_start("Report: " . $rep_info['title'], '', '', 'report_intro'); // TRAD
else
	die("There is no such report!");

if ($rep_info['description'])
	echo "<p>" . $rep_info['description'] . "</p>\n";

if (! $rep_info['line_src_name']) {
	$errors = array("You must select at least a source for the report line information."); // TRAD
	echo show_all_errors($errors);
	echo '<p><a href="rep_det.php?rep=' . $rep . '" class="run_lnk">Back</a></p>'; // TRAD
	lcm_page_end();
	exit;
}

$my_line_table = "lcm_" . $rep_info['line_src_name'];

//
// For eventual report headers
//

// for each array item will be a hash with 'description', 'filter' and 'enum_type'
$headers = array();

//
// Get report line fields, store into $my_lines for later
//

$my_lines = array();
$q = "SELECT f.id_field, f.field_name, f.table_name, f.enum_type, f.description
		FROM lcm_rep_line as l, lcm_fields as f
		WHERE id_report = " . $rep . "
		AND l.id_field = f.id_field
		ORDER BY col_order ASC";

$result = lcm_query($q);

while ($row = lcm_fetch_array($result)) {
	$my_line_table = $row['table_name'];
	array_push($my_lines, suffix_field($row['table_name'], $row['field_name']));
	array_push($headers, $row);
}

// No fields were specified: show them all (avoids errors)
if (! count($my_lines)) {
	if ($rep_info['line_src_type'] == 'table') {
		$q = "SELECT * 
			FROM lcm_fields 
			WHERE table_name = '$my_line_table'
			AND field_name != 'count(*)'";
		$result = lcm_query($q);

		while ($row = lcm_fetch_array($result)) {
			$my_line_table = $row['table_name'];
			array_push($my_lines, suffix_field($row['table_name'], $row['field_name']));
			array_push($headers, $row);
		}
	} elseif ($rep_info['line_src_type'] == 'keyword') {
		$kwg = get_kwg_from_name($rep_info['line_src_name']);
		// XXX dirty hack, headers print function refers directly to 'description'
		$kwg['description'] = _T(remove_number_prefix($kwg['title']));
		array_push($my_lines, "k.name");
		array_push($headers, $kwg);
	}
}

//
// Get report columns fields, store into $my_columns for later
//

$do_grouping = false;
$my_columns = array();
$q = "SELECT f.id_field, f.field_name, f.table_name, f.enum_type, f.description
		FROM lcm_rep_col as c, lcm_fields as f
		WHERE c.id_report = $rep
			AND c.id_field = f.id_field
		ORDER BY c.col_order";

$result = lcm_query($q);

while ($row = lcm_fetch_array($result)) {
	$my_col_table = $row['table_name'];
	if ($row['field_name'] == "count(*)")
		$do_grouping = true;
	
	if ($row['enum_type']) {
		$enum = split(":", $row['enum_type']);

		if ($enum[0] == 'keyword') {
			if ($enum[1] == 'system_kwg') {
				include_lcm('inc_keywords');
				$kws = get_keywords_in_group_name($enum[2]);
				$i = 1;
				$left_joins = "";

				foreach ($kws as $k) {
					$name = $row['field_name'];
					$prefix = "";

					$sql = "LCM_SQL: SELECT count(*) FROM lcm_followup WHERE type = '" . $k['name'] . "'";

					$k['description'] = $k['title'];
					array_push($headers, $k);
					array_push($my_columns, "1 as \"" . $sql ."\"");

					$i++;
				}

				$do_grouping = true;
			} else {
				echo "\n\n QUERY = " . $q . " \n\n";
				lcm_panic("Not yet implemented -" . $enum[1] . "-");
			}
		} elseif ($enum[0] == 'list') {

		} else {
			echo "\n\n QUERY = " . $q . " \n\n";
			lcm_panic("Not yet implemented -" . $enum[0] . "-");
		}
	} else {
		array_push($my_columns, $row['field_name']);
		array_push($headers, $row);
	}
}

//
// Add implicit fields.
// For example, if we select fields from lcm_author, we should include id_author
// even if we don't want to show it (for table joining, later).
// 

$my_line_fields_implicit = "";

if ($rep_info['line_src_type'] == 'table' && preg_match("/^lcm_(.*)$/", $my_line_table, $regs)) {
	$temp = get_table_suffix($my_line_table);

	if ($temp) {
		$temp .= ".id_" . $regs[1];
		$my_line_fields_implicit = $temp;
		echo "<!-- Implicit param: " . $my_line_fields_implicit . " -->\n";
	}
}

//
// Fetch all filters for this report
//

$my_filters = array();

$q = "SELECT f.table_name, f.field_name, v.type, v.value 
		FROM lcm_rep_filter as v, lcm_fields as f
		WHERE v.id_field = f.id_field
		AND v.id_report = " . $rep;

$result = lcm_query($q);

while ($row = lcm_fetch_array($result))
	array_push($my_filters, $row);


//
// Start building the SQL query for the report lines
//

$my_line_fields = implode(", ", $my_lines);
$my_col_fields  = implode(", ", $my_columns);

if ($my_line_fields && $my_line_fields_implicit)
	$my_line_fields .= ", " . $my_line_fields_implicit;

$q = "SELECT " . $my_line_fields;
$q_where = array();

// Hide implicit fields, but allow them to be in 'group by' if necessary
if ($my_line_fields_implicit)
	$q .= " as 'LCM_HIDE_ID' ";

if ($my_col_fields)
	$q .= ", " . $my_col_fields;

if ($rep_info['line_src_type'] == 'table') {
	$q .= " FROM " . $my_line_table . suffix_table($my_line_table);
} elseif ($rep_info['line_src_type'] == 'keyword') {
	$q .= " FROM lcm_keyword as k 
			LEFT JOIN lcm_keyword_group as kwg on (k.id_group = kwg.id_group AND kwg.name = '" . $rep_info['line_src_name'] . "')";

	$q_where[] = "kwg.name IS NOT NULL";
}

// Join condition
if ($rep_info['line_src_type'] == 'table') {
	switch ($my_line_table) {
		case 'lcm_author':
			switch($my_col_table) {
				case 'lcm_followup':
					$q .= " LEFT JOIN lcm_followup as fu ON (fu.id_author = a.id_author) ";
					break;
			}
			break;

		case 'lcm_case':
			switch($my_col_table) {
				case 'lcm_followup':
					$q .= " LEFT JOIN lcm_followup as fu ON (fu.id_case = c.id_case) ";
					break;

			}
			break;

		case 'lcm_followup':
			switch($my_col_table) {
				case 'lcm_author':
					$q .= " LEFT JOIN lcm_author as a ON (a.id_author = fu.id_author) ";
			}
			break;

		default:
			echo "\n\n QUERY = " . $q . " \n\n";
			lcm_panic("unknown join on my_line_table: $my_line_table");
	}
} elseif ($rep_info['line_src_type'] == 'keyword') {
	switch($rep_info['line_src_name']) {
		default:
			switch($my_col_table) {
				case 'lcm_case':
					$q .= " LEFT JOIN lcm_keyword_case as kc ON (kc.id_keyword = k.id_keyword) ";
					break;
			}
	}
}

if (isset($left_joins))
	$q .= $left_joins;

if (count($q_where)) {
	$tmp = implode(" AND ", $q_where);
	$q .= "WHERE " . $tmp;
}

if ($do_grouping) {
	$q .= " GROUP BY " . $my_line_fields;
}

echo "\n\n<!-- QUERY = " . $q . " -->\n\n";

// Apply filters to this table
$line_filters = array();

foreach ($my_filters as $f) {
	if ($f['table_name'] == $my_line_table) {
		$temp = '';

		if ($f['type']) 
			$temp .= $f['field_name'];

		switch($f['type']) {
			case '':
				// do nothing
				break;
			case 'num_eq':
				$temp .= " = " . $f['value'];
				break;
			case 'num_lt':
				$temp .= " < " . $f['value'];
				break;

			default:
				lcm_panic("Internal error in run_report: unknown filter type (" . $f['type'] . ")");
		}

		if ($temp)
			array_push($line_filters, $temp);
	}
}

if (count($line_filters))
	$q .= "\n WHERE " . implode(" AND ", $line_filters);

$result = lcm_query($q);

//
// Ready for report line
//

echo "<table class='tbl_usr_dtl' width='98%' align='center' border='1'>";
echo "<tr>\n";
foreach ($headers as $h) {
	echo "<th class='heading'>" . _Th(remove_number_prefix($h['description'])) . "</th>\n";
}
echo "</tr>\n";

$cpt_lines = 0;
$cpt_col = 0;

while ($row = lcm_fetch_array($result)) {
	$cpt_lines++;
	echo "<tr>\n";

	foreach ($row as $key => $val) {
		if ((! is_numeric($key)) && ($key != 'LCM_HIDE_ID')) {
			$css = 'class="tbl_cont_' . ($cpt_lines % 2 ? "light" : "dark") . '"';
			$align = 'align="left"';

			//
			// Special cases
			//
			if ($headers[$cpt_col]['field_name'] == 'description')
				$val = get_fu_description($row);

			if ($val == "1" && preg_match("/^LCM_SQL: (.*)/", $key, $regs)) {
				$q_col = $regs[1];
				$q_col .= join_tables($my_line_table, '', $row['LCM_HIDE_ID'], 0);
				
				$result_tmp = lcm_query($q_col);

				$val = "";

				while($row_tmp = lcm_fetch_array($result_tmp)) {
					$val .= $row_tmp[0];
				}
			}

			switch ($headers[$cpt_col]['filter']) {
				case 'date_length':
					$val = format_time_interval_prefs($val);
					break;
				case 'date':
					$val = format_date($val, 'short');
					break;
				case 'number':
					$align = 'align="right"';
			}
		
			echo '<td ' . $align . ' ' . $css . '>' . $val . "</td>\n";
			$cpt_col = ($cpt_col + 1) % count($headers);
		}
	}

	echo "</tr>\n";
}

echo "</table>\n";

echo '<p><a href="rep_det.php?rep=' . $rep . '" class="run_lnk">Back</a></p>'; // TRAD

lcm_page_end();

?>
