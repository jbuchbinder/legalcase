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

	$Id: run_rep.php,v 1.26 2005/06/01 21:05:23 mlutfy Exp $
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
	elseif ($table == 'lcm_case_client_org')
		return "cco";
	elseif ($table == 'lcm_case_author')
		return "ca";

	return "";
}

function suffix_table($table) {
	$suffix = get_table_suffix($table);

	if ($suffix)
		return " as " . get_table_suffix($table) . " ";
	else
		return "";
}

function prefix_field($table, $field) {
	$suffix = get_table_suffix($table);

	if (preg_match("/^IF/", $field))
		return $field;

	if (preg_match("/^count\(\*\)/", $field))
		return $field;

	if ($suffix)
		return $suffix . "." . $field;
	else
		return $table . "." . $field;
}

function join_tables($table1, $table2 = '', $id1 = 0, $id2 = 0) {
	$from  = ""; // select .. FROM [here] (for dependancies between tables)
	$from_glue = ""; // for joining the FROM
	$where = ""; // the usual stuff (LEFT JOIN ON .., WHERE ..)

	lcm_debug("join_tables: " . $table1 . " - " . $table2 . " id1 = " . $id1 . " id2 = " . $id2);

	$table_keys = array(
		"lcm_case" => "id_case",
		"lcm_author" => "id_author",
		"lcm_followup" => "id_followup",
		"lcm_client" => "id_client",
		"lcm_org" => "id_org" );

	if ($table1 == $table2)
		lcm_panic("Linking with self: not yet supported");

	switch($table1) {
		case 'lcm_author':
			switch($table2) {
				case 'lcm_case':
					// $from = " LEFT JOIN lcm_case_author as ca ON (a.id_author = ca.id_author AND c.id_case = ca.id_case) ";
					$from   = " , lcm_case_author as ca ";
					$from_glue .= " a.id_author = ca.id_author AND c.id_case = ca.id_case ";
					$where .= " a.id_author = ca.id_author ";
					break;
				case 'lcm_followup':
					$where .= " a.id_author = fu.id_author ";
					break;
				case 'lcm_client':
					lcm_panic("not implemented");
					break;
				case 'lcm_org':
					lcm_panic("not implemented");
					break;
				case '':
					break;
				default:
					lcm_panic("case not implemented ($table2)");
					break;
			}

			break;

		case 'lcm_case':
			switch($table2) {
				case '':
					break;
				case 'lcm_followup':
					$where .= " c.id_case = fu.id_case ";
					break;
				default:
					lcm_panic("not coded");
			}

			break;

		case 'lcm_followup':
			switch($table2) {
				case '':
					break;
				case 'lcm_author':
					$where .= " fu.id_author = a.id_author ";
					break;
				default:
					lcm_panic("not coded");
			}

			break;

		case 'lcm_client':
			switch($table2) {
				case '':
					break;
				case 'lcm_case':
					$from   = " , lcm_case_client_org as cco ";
					$from_glue .= " cl.id_client = cco.id_client AND c.id_case = cco.id_case ";
					$where .= " cl.id_client = cco.id_client ";
					break;
				default:
					lcm_panic("not coded");
			}
			break;

		case 'lcm_org':
			switch($table2) {
				default:
					lcm_panic("not coded");
			}

			break;

		default:
			// lcm_panic("not coded - $table1");
	}

	if ($id1) {
		if ($id2)
			$where .= " AND " . prefix_field($table1, $table_keys[$table1]) . " = $id1 ";
		else
			$where .= " AND " . $table_keys[$table1] . " = $id1 ";
	}
	
	if ($id2)
		$where .= " AND " . prefix_field($table2, $table_keys[$table2]) . " = $id2 ";

	return array($from, $from_glue, $where);
}

function get_filters_sql($id_report, $obj_type = '', $obj_name = '') {
	$ret = "";

	$is_missing_filters = false;
	$my_filters = get_filters($id_report, $obj_type, $obj_name);
	$clauses = array();

	// Apply the filter values and check for missing values
	foreach ($my_filters as $f) {
		if ($f['value']
			|| isset($_REQUEST['filter_val' . $f['id_filter']]) // text or number
			|| isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'], 'year_only') // date
			|| (isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_start", 'year_only') // interval
			   && isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_end", 'year_only')))
		{
			$filter_sql = apply_filter($f);

			if ($filter_sql)
				array_push($clauses, $filter_sql);
		} else {
			// For now, we ignore filters without type (eq/lt/gt/..) 
			// because it's a bit messy to allow input at runtime
			// (because of fields for filter value)
			if ($f['type'])
				$is_missing_filters = true;
		}
	}

	if ($is_missing_filters) {
		global $rep;
		global $headers_sent; // XXX hmm, not clean

		if (! $headers_sent)
			lcm_page_start("Report: " . remove_number_prefix($rep_info['title']), '', '', 'report_intro'); // TRAD

		echo '<p class="normal_text">' . "Please enter the values for the report:" . "</p>\n"; // TRAD
		include_lcm('inc_conditions');
		show_report_filters($rep, true);
		exit;
	}

	$ret = implode(" AND ", $clauses);
	return $ret;
}

function show_filters_info($id_report) {
	$my_filters = get_filters($id_report);

	foreach ($my_filters as $f) {
		if (! $f['value']) {
			// Value may be provided by $_REQUEST
			if (isset($_REQUEST['filter_val' . $f['id_filter']])) { 
				// text or number
				$f['value'] = $_REQUEST['filter_val' . $f['id_filter']];
			} elseif (isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'], 'year_only')) {
				// Date
				$f['value'] = format_date(get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'], 'year_only'), 'short');
			} elseif (isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_start", 'year_only')
				&& isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_end", 'year_only'))
			{
				// Date interval
				$f['value'] = format_date(get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_start", 'start'), 'short');
				$f['value'] .= " - ";
				$f['value'] .= format_date(get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_end", 'end'), 'short');
			} else {
				// Should never happen
				$f['value'] = _T('info_not_available');
			}
		}
	
		// Example: "Follow-up - Start: in 1 Apr 05, 00h00 - 31 Dec 05, 23h59"
		// or.....: Table - Field: type_filter value
		echo '<p class="normal_text">' 
			. _T('rep_info_table_' . $f['table_name']) . " - " . _Ti($f['description'])
			. _T('rep_filter_' . $f['type']) . " " . $f['value']
			. "</p>\n";
	}
}

function get_filters($id_report, $obj_type = '', $obj_name = '') {
	$my_filters = array();

	$q_fil = "SELECT v.id_filter, f.table_name, f.field_name, f.description, v.type, v.value 
		FROM lcm_rep_filter as v, lcm_fields as f
		WHERE v.id_field = f.id_field
		AND v.id_report = " . $id_report;
	
	// XXX not sure how to deal with keywords
	if ($obj_type && $obj_name)
		$q_fil .= " AND f.table_name IN ($obj_name) ";

	$result = lcm_query($q_fil);

	while ($row = lcm_fetch_array($result))
		array_push($my_filters, $row);

	return $my_filters;
}

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
			$dates[0] = get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . '_start', 'start');
			$dates[1] = get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . '_end', 'end');
		}

		$ret .= "(DATE_FORMAT(" . prefix_field($f['table_name'], $f['field_name']) . ", '%Y-%m-%d') "
			.   " >= DATE_FORMAT('" . $dates[0] . "', '%Y-%m-%d')" 
			. " AND DATE_FORMAT(" . prefix_field($f['table_name'], $f['field_name']) . ", '%Y-%m-%d') "
			.   " <= DATE_FORMAT('" . $dates[1] . "', '%Y-%m-%d')) ";
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
					$ret .= "DATE_FORMAT(" . prefix_field($f['table_name'], $f['field_name']) . ", '%Y-%m-%d')"
						. " " . $filter_conv[$filter_op] . " " 
						. "DATE_FORMAT('" . get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter']) . "', '%Y-%m-%d') ";
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

function get_ui_print_value($val, $h, $css) {
	$ret = "";

	// Maybe formalise 'time_length' filter, but check SQL pre-filter also
	if ($h['filter_special'] == 'time_length') {
		$val = format_time_interval_prefs($val);
		if (! $val)
			$val = 0;
	} elseif ($h['description'] == 'time_input_length') {
		$val = format_time_interval_prefs($val);
		if (! $val)
			$val = 0;
	}
	
	switch ($h['filter']) {
		case 'date':
			if ($val)
				$val = format_date($val, 'short');
			break;
		case 'currency':
			if ($val)
				$val = format_money($val);
			else
				$val = 0;
			break;
		case 'number':
			$align = 'align="right"';
			if (! $val)
				$val = 0;
			break;
	}

	if ($_REQUEST['export'] == 'csv') {
		$val = str_replace('"', '""', $val); // escape " character (csv)
		$ret = '"' . $val . '" , ';
	} else {
		$ret = '<td ' . $align . ' ' . $css . '>' . $val . "</td>\n";
	}

	return $ret;
}

function get_ui_start_line() {
	if ($_REQUEST['export'] == 'csv')
		return "";
	else
		return "<tr>\n";
}

function get_ui_end_line() {
	if ($_REQUEST['export'] == 'csv')
		return "\n";
	else
		return "</tr>\n";
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
	header('Content-Disposition: filename="' . remove_number_prefix($rep_info['title']) . '.csv"');
	header("Content-Description: " . remove_number_prefix($rep_info['title']));
	header("Content-Transfer-Encoding: binary");
} else {
	lcm_page_start(_T('title_rep_run') . " " . remove_number_prefix($rep_info['title']), '', '', 'report_intro');
	$headers_sent = true;

	if ($rep_info['description'])
		echo '<p class="normal_text">' . $rep_info['description'] . "</p>\n";
}

$my_line_table = "lcm_" . $rep_info['line_src_name'];

//
// For report headers (used later)
//

// for each array item will be a hash with 'description', 'filter' and 'enum_type'
$headers = array();
$do_grouping = false;

//
// Get report line fields, store into $my_lines for later
//

$my_lines = array();
$q = "SELECT *
		FROM lcm_rep_line as l, lcm_fields as f
		WHERE id_report = " . $rep . "
		AND l.id_field = f.id_field
		ORDER BY col_order, id_line ASC";

$result = lcm_query($q);

while ($row = lcm_fetch_array($result)) {
	$my_line_table = $row['table_name'];
	array_push($my_lines, prefix_field($row['table_name'], $row['field_name']));
	array_push($headers, $row);

	if ($row['field_name'] == 'count(*)')
		$do_grouping = true;
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
			array_push($my_lines, prefix_field($row['table_name'], $row['field_name']));
			array_push($headers, $row);
		}
	} elseif ($rep_info['line_src_type'] == 'keyword') {
		$kwg = get_kwg_from_name($rep_info['line_src_name']);
		// XXX dirty hack, headers print function refers directly to 'description'
		$kwg['description'] = _T(remove_number_prefix($kwg['title']));
		array_push($my_lines, "k.title as 'TRAD'");
		array_push($headers, $kwg);
	}
}

//
// Get report columns fields, store into $my_columns for later
//

$do_special_join = false;
$my_columns = array();

// if ($row['src_type' == 'table' && ! preg_match('/^lcm_/', $src_name))
//	$src_name = 'lcm_' . $src_name;

$q = "SELECT *
		FROM lcm_rep_col as c, lcm_fields as f
		WHERE c.id_report = $rep
			AND c.id_field = f.id_field
		ORDER BY c.col_order, id_column ASC";

$result = lcm_query($q);

while ($row = lcm_fetch_array($result)) {
	$my_col_table = $row['table_name'];

	if ($row['field_name'] == "count(*)")
		$do_grouping = true;
	
	if ($row['enum_type']) {
		$enum = split(":", $row['enum_type']);

		if ($enum[0] == 'keyword') {
			if ($enum[1] == 'system_kwg') {
				// There is a 'bug' in this, because reporting sum(fu-type-time) for each case,
				// will not show hidden keywords, which include 'case_stage'. But it's odd to 
				// put a time in case_stage anyway, and showing 'hidden' keywords is not very
				// appropriate in this situation.
				$kws = get_keywords_in_group_name($enum[2]);

				// Get filters that might apply
				$my_filters = get_filters_sql($rep, 'table', "'" . $row['table_name'] . "'");
				$sql_filter = ($my_filters ? " AND " . $my_filters : "");

				foreach ($kws as $k) {
					// XXX this is too specific for a given case (sum(time) of activity type per author)
					$sql = "LCM_SQL: SELECT sum(IF(UNIX_TIMESTAMP(fu.date_end) > UNIX_TIMESTAMP(fu.date_start), UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) FROM lcm_followup as fu WHERE type = '" . $k['name'] . "' "
						. $sql_filter;

					// For report headers
					$k['filter_special'] = 'time_length'; // XXX
					$k['description'] = $k['title'];

					array_push($headers, $k);
					array_push($my_columns, "1 as \"" . $sql ."\"");
				}

				// TOTAL for this enum
				$sql = "LCM_SQL: SELECT sum(IF(UNIX_TIMESTAMP(fu.date_end) > UNIX_TIMESTAMP(fu.date_start), UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) FROM lcm_followup as fu WHERE 1 "
					. $sql_filter;

				$k['filter_special'] = 'time_length'; // XXX
				$k['description'] = "TOTAL"; // TRAD

				array_push($headers, $k);
				array_push($my_columns, "1 as \"" . $sql ."\"");

				$do_grouping = true;
			} else {
				echo "\n\n QUERY = " . $q . " \n\n";
				lcm_panic("Not yet implemented -" . $enum[1] . "-");
			}
		} elseif ($enum[0] == 'list') {
			$items = split(',', $enum[1]);

			foreach($items as $i) {
				// XXX should add 'where' clauses only (kwg above too..)
				$q_where_add = "LCM_SQL: cl.gender = '" . $i . "'";
				$tmp = array('description' => _T($enum[2] . $i), 'filter' => 'number');
				array_push($my_columns, "2 as \"" . $q_where_add . "\"");
				array_push($headers, $tmp);
			}
		} else {
			echo "\n\n QUERY = " . $q . " \n\n";
			lcm_panic("Not yet implemented -" . $enum[0] . "-");
		}
	} elseif ($my_line_table == 'lcm_author' && $row['table_name'] == 'lcm_case' && $row['field_name'] == 'count(*)') {
		// TODO: ADD FILTERS?
		$kws = get_keywords_in_group_name('stage');

		// Get filters that might apply
		$my_filters = get_filters_sql($rep, 'table', "'lcm_case'");
		$sql_filter = ($my_filters ? " AND " . $my_filters : "");

		foreach ($kws as $k) {
			$sql = "LCM_SQL: SELECT count(*) FROM lcm_case as c "
				. " LEFT JOIN lcm_case_author as ca ON (c.id_case = ca.id_case) "
				. " WHERE c.stage = '" . $k['name'] . "' "
				. $sql_filter;

			// For report headers
			$k['filter'] = 'number';
			$k['description'] = $k['title'];

			array_push($headers, $k);
			array_push($my_columns, "1 as \"" . $sql ."\"");
		}

		// TOTAL for this enum
		$sql = "LCM_SQL: SELECT count(*) FROM lcm_case as c, lcm_case_author as ca "
			. " WHERE c.id_case = ca.id_case "
			. $sql_filter;

		$k['filter'] = 'number';
		$k['description'] = "TOTAL"; // TRAD

		array_push($headers, $k);
		array_push($my_columns, "1 as \"" . $sql ."\"");

		$do_grouping = true;
	} else {
		array_push($my_columns, prefix_field($row['table_name'], $row['field_name']));
		array_push($headers, $row);
		$do_special_join = true;
	}
}

if ($rep_info['col_src_type'] == 'keyword' && ! count($my_columns)) {
	$kwg = get_kwg_from_name($rep_info['col_src_name']);
	$kws = get_keywords_in_group_name($rep_info['col_src_name']);

	$my_filters = get_filters_sql($rep, 'table', "'lcm_case'");
	$sql_filter = ($my_filters ? " AND " . $my_filters : "");
	$all_kw_names = array();

	// TODO: For the moment, this is limited to crossing the author table
	// with 'case' keywords. (ex: type-of-crimes, per author, where
	// line = author(name) and col = kw.type-of-crime

	foreach ($kws as $kw) {
		// XXX dirty hack, headers print function refers directly to 'description'
		$kw['description'] = _T(remove_number_prefix($kw['title']));
		$kw['filter'] = 'number';

		$sql = "LCM_SQL: SELECT count(*) FROM lcm_keyword_" . $kwg['type'] . " as ka, "
			. " lcm_case_author as ca, lcm_keyword as k " 
			. " WHERE k.id_keyword = ka.id_keyword "
			. " AND ca.id_case = ka.id_case " // XXX
			. " AND k.name = '" . $kw['name'] . "'"
			. $sql_filter;
		
		array_push($my_columns, "1 as \"" . $sql ."\"");
		array_push($headers, $kw);
		$all_kw_names[] = $kw['name'];
	}

	// TOTAL for this enum
	// Note: the k.name IN (...) is because other keywords might be associated 
	// with this case/client/etc
	$sql = "LCM_SQL: SELECT count(*) FROM lcm_keyword_" . $kwg['type'] . " as ka, "
		. " lcm_case_author as ca, lcm_keyword as k " 
		. " WHERE k.id_keyword = ka.id_keyword "
		. " AND ca.id_case = ka.id_case " // XXX
		. " AND k.name IN ('" . implode("','", $all_kw_names) . "')"
		. $sql_filter;

	$k['filter'] = 'number';
	$k['description'] = "TOTAL"; // TRAD

	array_push($headers, $k);
	array_push($my_columns, "1 as \"" . $sql ."\"");
}

//
// Add implicit fields if there will be a join table
// For example, if we select fields from lcm_author, we should include id_author
// even if we don't want to show it (for table joining, later).
// 

$my_line_fields_implicit = "";

if ($rep_info['line_src_type'] == 'table'
	&& preg_match("/^lcm_(.*)$/", $my_line_table, $regs)
	&& count($my_columns))
{
	$temp = get_table_suffix($my_line_table);

	if ($temp) {
		$temp .= ".id_" . $regs[1];
		$my_line_fields_implicit = $temp;
	}
} elseif ($rep_info['line_src_type'] == 'keyword' && count($my_columns)) {
	$my_line_fields_implicit = 'k.id_keyword';
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
	$q .= " FROM lcm_keyword as k "
		. " LEFT JOIN lcm_keyword_group as kwg on (k.id_group = kwg.id_group AND kwg.name = '" . $rep_info['line_src_name'] . "')";

	$q_where[] = "kwg.name IS NOT NULL";
}

// Join condition
if ($rep_info['line_src_type'] == 'table') {
	// join my_line_table with my_col_table
	if ($my_col_table && $do_special_join) {
		// from join (ex: dependancy on middle tables, such as Author-Case => lcm_case_author)
		$deps = join_tables($my_line_table, $my_col_table);
	
		if ($deps[0])
			$q .= $deps[0]; // FROM

		if ($deps[1])
			$q_where[] = $deps[1];

		$q .= " LEFT JOIN " . $my_col_table . suffix_table($my_col_table) . " ON (" . $deps[2] . " ) ";
	}
} elseif ($rep_info['line_src_type'] == 'keyword') {
	switch($rep_info['line_src_name']) {
		default:
			switch($my_col_table) {
				case '':
					break;
				case 'lcm_case':
					$q .= " LEFT JOIN lcm_keyword_case as kc ON (kc.id_keyword = k.id_keyword) ";
					break;
				case 'lcm_client':
					$q .= " LEFT JOIN lcm_keyword_client as kc ON (kc.id_keyword = k.id_keyword) ";
					if ($my_line_table != 'lcm_client')
						$q .= " LEFT JOIN lcm_client as cl ON (cl.id_client = kc.id_client) ";
					break;
				default:
					lcm_panic("not implemented");
			}
	}
}

//
// Fetch all filters for this report
//

$tmp_tables = "'$my_line_table'";
if ($my_col_table && $do_special_join)
	$tmp_tables .= ",'$my_col_table'";

$my_filters_sql = get_filters_sql($rep, 'table', $tmp_tables);

if ($my_filters_sql)
	$q .= "\n WHERE " . $my_filters_sql;

//
// Add the last "where" conditions
//

if (count($q_where)) {
	if (count($my_filters))
		$q .= " AND ";
	else
		$q .= " WHERE ";
	
	$q .= implode(" AND ", $q_where);
}

if ($do_grouping) {
	$group_fields = "";
	$tmp = array();

	foreach($my_lines as $l)
		if (preg_match("/(.*) as .*/", $l, $regs))
			$tmp[] = $regs[1];
		elseif (! preg_match("/.*count\(\*\)/", $l))
			$tmp[] = $l;

	$group_fields = implode(',', $tmp);
	$q .= " GROUP BY " . $group_fields;
}

//
// Ready!
//

if ($headers_sent)
	echo "\n\n<!-- QUERY = " . $q . " -->\n\n";

$result = lcm_query($q);

//
// Show filters applied
//

show_filters_info($rep);

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

echo get_ui_end_line();

$cpt_lines = 0;
$cpt_col = 0;

for ($cpt_lines = $cpt_col = 0; $row = lcm_fetch_array($result); $cpt_lines++) {
	echo get_ui_start_line();

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
				$deps = join_tables($my_line_table, '', $row['LCM_HIDE_ID'], 0);
				$q_col = $regs[1];
				$q_col .= $deps[2]; // WHERE [...]
				
				$result_tmp = lcm_query($q_col);

				$val = "";

				while($row_tmp = lcm_fetch_array($result_tmp)) {
					$val .= $row_tmp[0];
				}
			} elseif ($val == "2" && preg_match("/^LCM_SQL: (.*)/", $key, $regs)) {
				$tmp = $regs[1];

				$q_col = "SELECT count(*) ";
				$q_col .= strstr($q, "FROM");

				//
				// Experimental magic
				//
				$tmp .= " AND k.id_keyword = " . $row['LCM_HIDE_ID'];

				if (preg_match("/.*WHERE.*/", $q_col))
					$q_col = preg_replace("/WHERE/", "WHERE $tmp AND ", $q_col);
				else
					$q_col .= " WHERE $tmp ";

				$foo = split(" ", $tmp);
				if (preg_match("/.*GROUP BY.*/", $q_col))
					$q_col = preg_replace("/GROUP BY.*/", "GROUP BY " . $foo[0], $q_col);
				else
					$q_col .= " GROUP BY " . $foo[0];

				$result_tmp = lcm_query($q_col);

				$val = "";

				while($row_tmp = lcm_fetch_array($result_tmp)) {
					$val .= $row_tmp[0];
				}
			} elseif ($key == 'TRAD') {
				$val = _T($val);
			}

			// Translate values based on keywords (ex: fu.type)
			if ($headers[$cpt_col]['enum_type']) {
				$enum = split(":", $headers[$cpt_col]['enum_type']);

				if ($enum[0] == 'keyword') {
					if ($enum[1] == 'system_kwg') {
						if ($val) // XXX lcm_panic if kw does not exist
							$val = _Tkw($enum[2], $val);
					}
				} elseif ($enum[0] == 'list') {
					if ($enum[2])
						$val = _T($enum[2] . $val);
				}
			}

			// For end 'total' (works with datetime/number)
			$headers[$cpt_col]['total'] += $val;
			echo get_ui_print_value($val, $headers[$cpt_col], $css);
			$cpt_col = ($cpt_col + 1) % count($headers);
		}
	}

	echo get_ui_end_line();
}

// 
// Footer
//
$css = 'class="tbl_cont_' . (($cpt_lines + 1) % 2 ? "light" : "dark") . '"';
$cpt_tmp = 0;

echo get_ui_start_line();

foreach ($headers as $h) {
	if ((! preg_match('/^id_.*/', $h['field_name']))
		&& ($h['filter'] == 'number' || $h['filter'] == 'currency' || $h['filter_special'] == 'time_length'))
		echo get_ui_print_value($h['total'], $h, $css);
	elseif ($cpt_tmp == 0)
		echo get_ui_print_value('TOTAL', $h, $css); // TRAD
	else
		echo get_ui_print_value('', $h, $css);
	
	$cpt_tmp++;
}

echo get_ui_end_line();

if ($headers_sent) {
	echo "</table>\n";

	echo '<p><a href="rep_det.php?rep=' . $rep . '" class="run_lnk">' . _T('rep_button_goback') . "</a></p>\n";
	lcm_page_end();
}


?>
