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

	$Id: run_rep.php,v 1.17 2005/05/10 10:41:03 mlutfy Exp $
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
	lcm_page_start(_T('title_rep_run'), '', '', 'report_intro');
	echo '<p class="normal_text">' . _T('warning_forbidden_not_admin') . "</p>\n";
	lcm_page_end();
	exit;
}

$rep = intval($_GET['rep']); // Report ID
$headers_sent = false;
$_SESSION['errors'] = array();

//
// Show title and description of the report
//

$q = "SELECT *
		FROM lcm_report
		WHERE id_report=$rep";

$result = lcm_query($q);

if (! ($rep_info = lcm_fetch_array($result)))
	die("Report # " . $rep . " doest not exist.");

if (! $rep_info['line_src_name']) {
	$_SESSION['errors']['rep_line'] = _T('rep_warning_atleastlineinfo');
	header('Location: rep_det.php?rep=' . $rep);
	exit;
}
	
if ($_REQUEST['export'] == 'csv') {
	header("Content-Type: text/comma-separated-values");
	header('Content-Disposition: filename="' . $rep_info['title'] . '.csv"');
	header("Content-Description: " . $rep_info['title']);
	header("Content-Transfer-Encoding: binary");
} else {
	lcm_page_start(_T('title_rep_run') . " " . $rep_info['title'], '', '', 'report_intro');
	$headers_sent = true;

	if ($rep_info['description'])
		echo "<p>" . $rep_info['description'] . "</p>\n";
}

$my_line_table = "lcm_" . $rep_info['line_src_name'];

//
// For report headers (used later)
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
	}
}

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


//
// Fetch all filters for this report
//

$my_filters = array();

$q_fil = "SELECT v.id_filter, f.table_name, f.field_name, f.description, v.type, v.value 
		FROM lcm_rep_filter as v, lcm_fields as f
		WHERE v.id_field = f.id_field
		AND v.id_report = " . $rep;

$result = lcm_query($q_fil);

while ($row = lcm_fetch_array($result))
	array_push($my_filters, $row);

// Apply filters
$line_filters = array();

function apply_filter($f) {
	$ret = '';

	$filter_conv = array(
			"eq" => "=",
			"lt" => "<",
			"le" => "<=",
			"gt" => ">",
			"ge" => ">="
			);

	if (! $f['type'])
		return '';

	if ($f['type'] == 'date_in') {
		$dates = array();

		if ($f['value']) {
			$dates = explode(";", $f['value']);
		} else {
			$dates[0] = get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . '_start');
			$dates[1] = get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . '_end');
		}
		$ret .= "(DATE_FORMAT(" . $f['field_name'] . ", '%Y-%m-%d') >= DATE_FORMAT('" . $dates[0] . "', '%Y-%m-%d')" 
			. " AND DATE_FORMAT(" . $f['field_name'] . ", '%Y-%m-%d') <= DATE_FORMAT('" . $dates[1] . "', '%Y-%m-%d')) ";
	} else {
		$foo = explode("_", $f['type']); // ex: date_eq
		$filter_type = $foo[0]; // date
		$filter_op = $foo[1]; // eq

		if (! $f['value'])
			if (isset($_REQUEST['filter_val' . $f['id_filter']]))
				$f['value'] = $_REQUEST['filter_val' . $f['id_filter']];

		// FIELD OPERATOR 'VALUE'
		if ($filter_conv[$filter_op]) {
			switch($filter_type) {
				case 'date':
					$ret .= "DATE_FORMAT(" . $f['field_name'] . ", '%Y-%m-%d')"
						. " " . $filter_conv[$filter_op] . " " 
						. "DATE_FORMAT('" . $f['value'] . "', '%Y-%m-%d') ";
					break;
				case 'text':
					$ret .= $f['field_name'] 
						. " " . $filter_conv[$filter_op] . " "
						. "'" . $f['value'] . "' ";
					break;
				default: // number
					if ($f['description'] == 'time_input_length')
						$f['value'] = " " . $f['value'] . " * 3600 ";

					$ret .= $f['field_name']
						. " " . $filter_conv[$filter_op] . " "
						. $f['value'] . " ";
			}
		} else {
			lcm_log("no filter_conv for $filter_op ?");
			return '';
		}
	}

	return $ret;
}

foreach ($my_filters as $f) {
	if ($f['table_name'] == $my_line_table) { // FIXME .. and col filters?
		if ($f['value'] || isset($_REQUEST['filter_val' . $f['id_filter']]) 
			|| isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_start", 'year_only')
			|| isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_end", 'year_only'))
		{
			$fil_sql = apply_filter($f);

			if ($fil_sql)
				array_push($line_filters, $fil_sql);
		} else {
			// For now, we ignore filters without type (eq/lt/gt/..) 
			// because it's a bit messy to allow input at runtime
			// (because of fields for filter value)
			if ($f['type'])
				$is_missing_filters = true;
		}
	}
}

if ($is_missing_filters) {
	if (! $headers_sent)
		lcm_page_start("Report: " . $rep_info['title'], '', '', 'report_intro'); // TRAD

	echo '<p class="normal_text">' . "Please enter the values for the report:" . "</p>\n"; // TRAD
	include_lcm('inc_conditions');
	show_report_filters($rep, true);
	exit;
}

if (count($line_filters))
	$q .= "\n WHERE " . implode(" AND ", $line_filters);

//
// Add the last "where" conditions
//

if (count($q_where)) {
	if (! count($line_filters))
		$q .= " WHERE ";

	$q = implode(" AND ", $q_where);
}

if ($do_grouping) {
	$q .= " GROUP BY " . $my_line_fields;
}

//
// Ready!
//

if ($headers_sent)
	echo "\n\n<!-- QUERY = " . $q . " -->\n\n";

$result = lcm_query($q);

//
// Ready for report line
//

if ($headers_sent) {
	echo "<table class='tbl_usr_dtl' width='98%' align='center' border='1'>";
	echo "<tr>\n";

	$h_before = '<th class="heading">';
	$h_between = '';
	$h_after  = "</th>\n";
} else {
	$h_before = '"';
	$h_between = ', ';
	$h_after = "\"";
}

foreach ($headers as $h) {
	echo $h_before . _Th(remove_number_prefix($h['description'])) . $h_after . $h_between;
}

if ($headers_sent)
	echo "</tr>\n";
else
	echo "\n"; // CSV export

$cpt_lines = 0;
$cpt_col = 0;

while ($row = lcm_fetch_array($result)) {
	$cpt_lines++;

	if ($headers_sent)
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

			// Translate values based on keywords (ex: fu.type)
			if ($headers[$cpt_col]['enum_type']) {
				$enum = split(":", $headers[$cpt_col]['enum_type']);

				if ($enum[0] == 'keyword') {
					if ($enum[1] == 'system_kwg') {
						if ($val) // XXX lcm_panic if kw does not exist
							$val = _Tkw($enum[2], $val);
					}
				}
			}

			if ($headers[$cpt_col]['description'] == 'time_input_length')
				$val = format_time_interval_prefs($val);

			switch ($headers[$cpt_col]['filter']) {
				case 'date':
					$val = format_date($val, 'short');
					break;
				case 'number':
					$align = 'align="right"';
					break;
			}

			if ($headers_sent)
				echo '<td ' . $align . ' ' . $css . '>' . $val . "</td>\n";
			else { // if ($_REQUEST['export'] == 'csv')
				$val = str_replace('"', '""', $val); // escape " character (csv)
				echo '"' . $val . '" , ';
			}

			$cpt_col = ($cpt_col + 1) % count($headers);
		}
	}

	if ($headers_sent)
		echo "</tr>\n";
	else // // if ($_REQUEST['export'] == 'csv')
		echo "\n";
}

if ($headers_sent) {
	echo "</table>\n";

	echo '<p><a href="rep_det.php?rep=' . $rep . '" class="run_lnk">' . _T('rep_button_goback') . "</a></p>\n";
	lcm_page_end();
}


?>
