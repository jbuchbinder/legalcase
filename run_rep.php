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

	$Id: run_rep.php,v 1.36 2007/03/26 15:34:31 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_obj_reportgen');

function panic_not_implemented($table1, $table2) {
	// [ML] Eventually we should print a more user-friendly message,
	// but for now, lcm_panic() is the easiest to debug.

	$GLOBALS['errors']['join'] = "Report not implemented: join of $table1 and $table2.
	Please write to legalcase-devel@lists.sf.net and explain the report you are
	trying to generate. If possible, please send a sample report with fictive
	values. It is possible that either it is possible to generate this report
	by another way, or that it may be necessary to write a custom report.";

	lcm_panic($GLOBALS['errors']['join']);
}

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
	elseif ($table == 'lcm_stage')
		return "s";

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

function join_tables($table1, $table2 = '', $id1 = 0, $id2 = 0, $report = null, $query = '') {
	$from  = ""; // select .. FROM [here] (for dependancies between tables)
	$from_glue = ""; // for joining the FROM
	$where = ""; // the usual stuff (LEFT JOIN ON .., WHERE ..)

	if ($report)
		$report->addComment("join_tables: " . $table1 . " - " . $table2 . " id1 = " . $id1 . " id2 = " . $id2);
		
	// lcm_debug("join_tables: " . $table1 . " - " . $table2 . " id1 = " . $id1 . " id2 = " . $id2);
	// lcm_debug(lcm_getbacktrace(false));

	$table_keys = array(
		"lcm_case" => "id_case",
		"lcm_author" => "id_author",
		"lcm_followup" => "id_followup",
		"lcm_client" => "id_client",
		"lcm_org" => "id_org",
		"lcm_keyword_case" => "id_keyword");

	if ($table1 == $table2)
		panic_not_implemented($table1, $table2);

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
					panic_not_implemented($table1, $table2);
					break;
				case 'lcm_org':
					panic_not_implemented($table1, $table2);
					break;
				case 'lcm_stage':
					/* TESTCASE: Count number of cases concluded by author
					 *  - row = lcm_author (name, family)
					 *  - col = lcm_stage (count)
					 *  - filter = lcm_stage.date_conclusion date_in (...)
					 */
					$from = " , lcm_case_author as ca ";
					$from_glue .= " a.id_author = ca.id_author AND s.id_case = ca.id_case ";
					$where .= " a.id_author = ca.id_author ";
					break;
				case '':
					break;
				default:
					panic_not_implemented($table1, $table2);
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
				case 'lcm_author':
					// [ML] This may not generate anything interested. Was implemented just for fun.
					$from   = " , lcm_case_author as ca ";
					$from_glue .= " c.id_case = ca.id_case AND ca.id_author = a.id_author ";
					$where .= " ca.id_author = a.id_author ";
					break;
				default:
					panic_not_implemented($table1, $table2);
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
					panic_not_implemented($table1, $table2);
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
					panic_not_implemented($table1, $table2);
			}
			break;

		case 'lcm_org':
			switch($table2) {
				default:
					panic_not_implemented($table1, $table2);
			}

			break;

		default:
			// Try to process as a keyword group
			$kwg = get_kwg_from_name($table1);

			switch($kwg['type']) {
				case 'case':
					$from   = " , lcm_keyword_case as kc ";
					$from_glue .= " kc.id_case = ca.id_case ";
					$table1 = 'lcm_keyword_case';
					// $where .= " cl.id_client = cco.id_client ";
					break;
			}
	}

	if ($id1) {
		if ($id2) { // [ML] TEST THIS
			$where .= " AND " . prefix_field($table1, $table_keys[$table1]) . " = $id1 ";

			if (isset($report))
				$report->addComment(" AND " .  prefix_field($table1, $table_keys[$table1]) . " = $id1 ");
		} elseif (($field = $report->getLineKeyField())) {
			// If joining some special query, check if $field in $query
			// [ML] this really needs more testing
			if ($query && preg_match('/^(.+)\./', $field, $regs)) {
				$table = $regs[1];

				if (preg_match('/as ' . $table . '/', $query)) {
					$where .= " AND $field = $id1 ";
				} elseif ($table == 'a' && preg_match('/as ca/', $query)) {
					$where .= " AND ca.id_author = $id1 ";
				} elseif ($table == 'a' && preg_match('/as fu/', $query)) {
					$where .= " AND fu.id_author = $id1 ";
				} else {
					$where .= " AND " . prefix_field($table1, $table_keys[$table1]) . " = $id1 ";
				}
			} else {
				$where .= " AND $field = $id1 ";
			}

			if (isset($report))
				$report->addComment($where);
		}
	}
	
	if ($id2) // [ML] TEST THIS
		$where .= " AND " . prefix_field($table2, $table_keys[$table2]) . " = $id2 ";

	return array($from, $from_glue, $where);
}

function get_filters_sql($report, $obj_type = '', $obj_name = '') {
	$ret = "";

	$is_missing_filters = false;
	$my_filters = get_filters($report->getId(), $obj_type, $obj_name);
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
		if (! ($report->getOption('headers_sent') == 'yes'))
			lcm_page_start(_T('title_rep_run') . " " . remove_number_prefix($rep_info['title']), '', '', 'report_intro');

		show_page_subtitle(_T('rep_subtitle_filters'), 'reports_edit', 'filters');
		echo '<p class="normal_text">';

		include_lcm('inc_conditions');
		show_report_filters($report->getId(), true);

		echo "</p>\n";
		lcm_page_end();
		exit;
	}

	$ret1 = implode(" AND ", $clauses);
	return $ret1;
}

function show_filters_info($report) {
	if (! $report->getOption('headers_sent'))
		return;

	$my_filters = get_filters($report->getId());

	if (count($my_filters))
		echo '<p class="normal_text">';

	foreach ($my_filters as $f) {
		if (! $f['value']) {
			// Value may be provided by $_REQUEST
			if (isset($_REQUEST['filter_val' . $f['id_filter']])) { 
				// text or number
				$f['value'] = $_REQUEST['filter_val' . $f['id_filter']];
			} elseif (isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'], 'year_only')) {
				// Date
				$f['value'] = get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'], 'year_only');
			} elseif (isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_start", 'year_only')
				&& isset_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_end", 'year_only'))
			{
				// Date interval
				$f['value'] = get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_start", 'start');
				$f['value'] .= ";";
				$f['value'] .= get_datetime_from_array($_REQUEST, 'filter_val' . $f['id_filter'] . "_end", 'end');
			} else {
				// Should never happen
				$f['value'] = _T('info_not_available');
			}
		}

		// TODO: If field.type == text and field.value is based on keyword or
		// list, use translation if known.
		if ($f['enum_type']) {
			$enum = explode(":", $f['enum_type']);

			if ($enum[0] == 'keyword') {
				if ($enum[2])
					$f['value'] = _Tkw($enum[2], $f['value']);
			} elseif ($enum[0] == 'list') {
				if ($enum[2])
					$f['value'] = _T($enum[2] . $f['value']);
			}
		}
	
		// Example: "Follow-up - Start: in 1 Apr 05, 00h00 - 31 Dec 05, 23h59"
		// or.....: Table - Field: type_filter value
		echo _T('rep_info_table_' . $f['table_name']) . " - " . _Ti($f['description'])
			. _T('rep_filter_' . $f['type']) . " ";

		switch($f['type']) {
			case 'date_in':
				$values = split(";", $f['value']);
				echo format_date($values[0], 'short') . " - " . format_date($values[1], 'short');
				break;
			case 'date_eq':
			case 'date_ge':
			case 'date_gt':
			case 'date_le':
			case 'date_lt':
				echo format_date($f['value'], 'short');
				break;
			default:
				echo $f['value'];
		}

		echo "<br />\n";
	}

	if (count($my_filters))
		echo "</p>\n";
}

function get_filters($id_report, $obj_type = '', $obj_name = '') {
	$my_filters = array();

	$q_fil = "SELECT v.id_filter, f.table_name, f.field_name, f.description, f.enum_type, v.type, v.value 
		FROM lcm_rep_filter as v, lcm_fields as f
		WHERE v.id_field = f.id_field
		AND v.id_report = " . $id_report;
	
	// XXX not sure how to deal with keywords
	if ($obj_type && $obj_name) {
		// special situation (apply a stage filter on a list of cases,
		// ex: show number of concluded case, by author, by case crime type)
		if ($obj_name == "'lcm_case'") 
			$obj_name = "'lcm_case', 'lcm_stage'";

		$q_fil .= " AND f.table_name IN ($obj_name) ";
	}

	$result = lcm_query($q_fil);

	while ($row = lcm_fetch_array($result))
		array_push($my_filters, $row);

	return $my_filters;
}

function apply_filter($f) {
	$ret = '';

	$filter_conv = array(
			"neq" => "!=",
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


global $author_session;

// Restrict page to administrators
if ($author_session['status'] != 'admin') {
	lcm_page_start(_T('title_rep_run'), '', '', 'report_intro');
	echo '<p class="normal_text">' . _T('warning_forbidden_not_admin') . "</p>\n";
	lcm_page_end();
	exit;
}


$_SESSION['errors'] = array();
$rep = intval(_request('rep', 0));

if (! $rep) {
	lcm_header('Location: listreps.php');
	exit;
}

//
// Show title and description of the report
//

$q = "SELECT *
		FROM lcm_report
		WHERE id_report = " . $rep;

$result = lcm_query($q);

if (! ($rep_info = lcm_fetch_array($result)))
	lcm_panic("Report # " . $rep . " doest not exist.");

if ((! $rep_info['line_src_name']) && (! $rep_info['filecustom'])) {
	$_SESSION['errors']['rep_line'] = _T('rep_warning_atleastlineinfo');
	lcm_header('Location: rep_det.php?rep=' . $rep);
	exit;
}

if ($rep_info['filecustom']) {
	include_custom_report($rep_info['filecustom']);
	$report = new CustomReportGen(intval(_request('rep')), _request('export', 'html'), _request('debug'));
} else {
	$report = new LcmReportGenUI(intval(_request('rep')), _request('export', 'html'), _request('debug'));
}

$report->printStartDoc($rep_info['title'], $rep_info['description'], 'report_intro');
	
if ($rep_info['line_src_type'] == 'table')
	$my_line_table = "lcm_" . $rep_info['line_src_name'];
else
	$my_line_table = $rep_info['line_src_name'];

//
// For report headers (used later)
//

// $do_grouping = false;
$report->setOption('do_grouping', 'no');

//
// Get report line fields, store into $report->lines for later
//

$report->setupReportLines();

//
// Get report columns fields, store into $report->columns for later
//

$do_special_join = false;

// if ($row['src_type' == 'table' && ! preg_match('/^lcm_/', $src_name))
//	$src_name = 'lcm_' . $src_name;

$q = "SELECT *
		FROM lcm_rep_col as c, lcm_fields as f
		WHERE c.id_report = " . $report->getId() . "
			AND c.id_field = f.id_field
		ORDER BY c.col_order, id_column ASC";

$result = lcm_query($q);

while ($row = lcm_fetch_array($result)) {
	$my_col_table = $row['table_name'];

	if ($row['field_name'] == "count(*)")
		$report->setOption('do_grouping', 'yes'); // $do_grouping = true;
	
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
				$tmp_my_filters = get_filters_sql($report, 'table', "'" . $row['table_name'] . "'");
				$sql_filter = ($tmp_my_filters ? " AND " . $tmp_my_filters : "");

				foreach ($kws as $k) {
					// This is for the various types of system_kwg.
					// Not very efficient, but works for now.
					if ($enum[2] == 'followups') {
						// Crossing lcm_followup with either lcm_author or lcm_case
						$sql = "SELECT sum(IF(UNIX_TIMESTAMP(fu.date_end) > UNIX_TIMESTAMP(fu.date_start), UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) FROM lcm_followup as fu WHERE type = '" . $k['name'] . "' AND fu.hidden = 'N' " . $sql_filter;
					
						// For report headers
						$k['filter_special'] = 'time_length'; // XXX
					} elseif ($enum[2] == 'conclusion' || $enum[2] == '_crimresults' || $enum[2] == 'sentence' || $enum[2] == 'stage') {
						$tmp_kw = ($enum[2] == '_crimresults' ? 'result' : $enum[2]);
						$tmp_kw = ($enum[2] == 'stage' ? 'case_stage' : $enum[2]);

						if ($my_line_table == 'lcm_author') {
							// Crossing lcm_stage with lcm_author (conclusions by author)
							$sql = "SELECT count(*) "
								. " FROM lcm_stage as s, lcm_case_author as ca, lcm_case as c "
								. " WHERE s.kw_" . $tmp_kw . " = '" . $k['name'] . "' "
								. "   AND s.id_case = ca.id_case "
								. "   AND c.id_case = ca.id_case "
								. $sql_filter;
						} elseif ($rep_info['line_src_type'] == 'keyword') {
							$sql = "SELECT count(*) "
								. " FROM lcm_stage as s, lcm_keyword_case as kc, lcm_case as c "
								. " WHERE s.kw_" . $tmp_kw . " = '" . $k['name'] . "' "
								. "   AND s.id_case = kc.id_case AND c.id_case = s.id_case "
								. $sql_filter;
						} else {
							lcm_panic("unknown enum[2] = " . $enum[2]);
						}
					} else {
						lcm_panic("unknown enum[2] = " . $enum[2]);
					}

					// For report headers
					$report->addHeader($k['title'], 'number', $k['enum_type'], $k['filter_special']);

					// Store special SQL command.
					$special_id = $report->addSpecial($sql);
					$report->addColumn("1 as \"LCM_SQL:special:$special_id\"");
				}

				//
				// TOTAL for this enum
				//
				$sql = "";
				if ($enum[2] == 'followups') {
					$sql = "SELECT sum(IF(UNIX_TIMESTAMP(fu.date_end) > UNIX_TIMESTAMP(fu.date_start), UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) FROM lcm_followup as fu WHERE fu.hidden = 'N' " . $sql_filter;

					// For report headers
					$k['filter_special'] = 'time_length'; // XXX
				} elseif ($enum[2] == 'conclusion' || $enum[2] == '_crimresults' || $enum[2] == 'sentence' || $enum[2] == 'stage') {
					if ($my_line_table == 'lcm_author') {
						$sql = "SELECT count(*) "
							. " FROM lcm_stage as s, lcm_case_author as ca, lcm_case as c "
							. " WHERE s.id_case = ca.id_case "
							. "   AND c.id_case = ca.id_case "
							. $sql_filter;
					} elseif ($rep_info['line_src_type'] == 'keyword') {
						$sql = "SELECT count(*) "
							. " FROM lcm_stage as s, lcm_keyword_case as kc "
							. " WHERE  "
							. "   s.id_case = kc.id_case "
							. $sql_filter;
					}
				}

				// For report headers
				$report->addHeader(_Th('generic_input_total'), $k['filter'], $k['enum_type'], $k['filter_special']);

				// Store special SQL command.
				$special_id = $report->addSpecial($sql);
				$report->addColumn("1 as \"LCM_SQL:special:$special_id\"");

				$report->setOption('do_grouping', 'yes');
				// $do_grouping = true;
			} else {
				echo "\n\n QUERY = " . $report->getSQL() . " \n\n";
				lcm_panic("Not yet implemented -" . $enum[1] . "-");
			}
		} elseif ($enum[0] == 'list') {
			$items = split(',', $enum[1]);

			foreach($items as $i) {
				// XXX should add 'where' clauses only (kwg above too..)
				$special_id = $report->addSpecial("cl.gender = '$i'");
				$report->addColumn("2 as \"LCM_SQL:special:$special_id\"");

				// $tmp = array('description' => _T($enum[2] . $i), 'filter' => 'number');
				$report->addHeader(_T($enum[2] . $i), 'number');
			}
		} else {
			echo "\n\n QUERY = " . $report->getSQL() . " \n\n";
			lcm_panic("Not yet implemented -" . $enum[0] . "-");
		}
	} elseif ($my_line_table == 'lcm_author' && $row['table_name'] == 'lcm_case' && $row['field_name'] == 'count(*)') {
		// TODO: ADD FILTERS?
		$kws = get_keywords_in_group_name('stage');

		// Get filters that might apply
		$tmp_my_filters = get_filters_sql($report, 'table', "'lcm_case'");
		$sql_filter = ($tmp_my_filters ? " AND " . $tmp_my_filters : "");

		foreach ($kws as $k) {
			$sql = "SELECT count(*) FROM lcm_case as c "
				. " LEFT JOIN lcm_case_author as ca ON (c.id_case = ca.id_case) "
				. " WHERE c.stage = '" . $k['name'] . "' "
				. $sql_filter;

			// For report headers
			// $k['filter'] = 'number';
			// $k['description'] = $k['title'];
			$report->addHeader(_Th($k['title']), 'number', $k['enum_type']);

			// Store special SQL command.
			$special_id = $report->addSpecial($sql);
			$report->addColumn("1 as \"LCM_SQL:special:$special_id\"");
		}

		// TOTAL for this enum
		$sql = "SELECT count(*) FROM lcm_case as c, lcm_case_author as ca "
			. " WHERE c.id_case = ca.id_case "
			. $sql_filter;

		// $k['filter'] = 'number';
		// $k['description'] = _Th('generic_input_total');
		$report->addHeader(_Th('generic_input_total'), 'number', $k['enum_type']);

		// Store special SQL command. 
		$special_id = $report->addSpecial($sql);
		$report->addColumn("1 as \"LCM_SQL:special:$special_id\"");

		// $do_grouping = true;
		$report->setOption('do_grouping', 'yes');
	} else {
		$report->addColumn(prefix_field($row['table_name'], $row['field_name']));
		$report->addHeader($row['description'], $row['filter'], $row['enum_type']);
		$do_special_join = true;
	}
}

if ($rep_info['col_src_type'] == 'keyword' && $rep_info['col_src_name'] && ! count($report->getColumns())) {
	$all_kw_names = array();
	$all_kw_ids = array();
	$kwg = get_kwg_from_name($rep_info['col_src_name']);
	$kws = get_keywords_in_group_name($rep_info['col_src_name']);

	$tmp_my_filters = get_filters_sql($report, 'table', "'lcm_case'");
	$sql_filter = ($tmp_my_filters ? " AND " . $tmp_my_filters : "");

	// Test whether there are any lcm_stage filters
	// lcm_stage filters are already caught with the above get_filters_sql(..., 'lcm_case')
	// [ML] Note: we don't want to systematically join with lcm_stage because
	// when a case has multiple stages, it will confuse the count().. well, at
	// least, that's how I prefer to leave it for compatibility between 0.6.x releases
	$tmp_my_filters_stage = get_filters_sql($report, 'table', "'lcm_stage'");
	$exists_stage_filter = false;
	if ($tmp_my_filters_stage)
		$exists_stage_filter = true;

	if ($kwg['type'] == $rep_info['line_src_name']) {
		$report->addHeader(_Th(remove_number_prefix($kwg['title'])), 'text');
		$report->addColumn("k.title as 'TRAD'");
	//	$report->addWhere("k.id_group = " . $kwg['id_group']);

	} else {

		// TODO: For the moment, this is limited to crossing the author table
		// with 'case' keywords. Ex: type-of-crimes, per author, where
		// line = author(name) and col = kw.type-of-crime
		foreach ($kws as $kw) {

			if ($kwg['type'] == 'system') 
				lcm_panic("not supported yet");

			if ($my_line_table == 'lcm_author') {
				// TODO: can't we use k.id_keyword instead? and drop lcm_keyword?
				$report->addHeader(_Th(remove_number_prefix($kw['title'])), 'number', $kw['enum_type']);

				$sql = "SELECT count(*) FROM lcm_keyword_" . $kwg['type'] . " as ka, "
					. " lcm_case_author as ca, lcm_keyword as k, lcm_case as c " . ($exists_stage_filter ? ", lcm_stage as s " : "")
					. " WHERE k.id_keyword = ka.id_keyword "
					. " AND c.id_case = ca.id_case "
					. ($exists_stage_filter ? " AND c.id_case = s.id_case " : "")
					. " AND ca.id_case = ka.id_case " // XXX
					. " AND k.name = '" . $kw['name'] . "'"
					. $sql_filter;
			} elseif ($my_line_table == 'lcm_followup') {
				$report->addHeader(_Th(remove_number_prefix($kw['title'])), 'time_input_length', '', 'time_length');

				$sql = "SELECT sum(IF(UNIX_TIMESTAMP(fu.date_end) > UNIX_TIMESTAMP(fu.date_start), UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) "
					. "FROM lcm_keyword_" . $kwg['type'] . " as ka, lcm_followup as fu, lcm_case as c "
					. "WHERE fu.id_case = ka.id_case "
					. " AND c.id_case = fu.id_case "
					. " AND ka.id_keyword = " . $kw['id_keyword']
					. $sql_filter;

				// $do_grouping = true;
				$report->setOption('do_grouping', 'yes');
			}

			// Store special SQL command. This was stored as "LCM_SQL: very long SQL",
			// but it caused problems on some installations (MySQL 4.1.x on W32) and
			// would cut the SQL string at the 256th character.
			$special_id = $report->addSpecial($sql);
			$report->addColumn("1 as \"LCM_SQL:special:$special_id\"");

			$all_kw_names[] = $kw['name'];
			$all_kw_ids[] = $kw['id_keyword'];
	}

	// Items WITHOUT keyword
	// [ML] HIGHLY EXPERIMENTAL, but hey, it works! :-)
	// well, it works for: show new cases by case-type (e.g. crime), for each author
	if (isset($_REQUEST['show_nokw']) && $_REQUEST['show_nokw']) {
		if ($my_line_table == 'lcm_author') {

		$sql = "SELECT count(*) FROM "
			. " lcm_case_author as ca," /* lcm_keyword as k, */ . " lcm_case as c " . ($exists_stage_filter ? ", lcm_stage as s " : "")
			. " LEFT JOIN lcm_keyword_" . $kwg['type'] . " as ka ON ka.id_case = c.id_case "
			. "  AND ka.id_keyword IN (" . implode(",", $all_kw_ids) . ")"
			. " WHERE c.id_case = ca.id_case "
			. ($exists_stage_filter ? " AND c.id_case = s.id_case " : "")
			// . " AND ca.id_case = ka.id_case " // XXX
			. " AND ka.id_case IS NULL "
			. $sql_filter;
		}

		// [ML] NOTE: if crossing lcm_followup with case-keyword,
		// I am not bothering with the "having no keyword" column
		// because I am fed up of this mess (well, the SELECT for the
		// lines of the reports needs to be fixed for this) XXX

		$report->addHeader("Test", 'number', $k['enum_type']);

		$special_id = $report->addSpecial($sql);
		$report->addColumn("1 as \"LCM_SQL:special:$special_id\"");
		$report->setOption('show_nokw', 'yes');
		$report->setOption('allow_show_nokw', 'yes');
	} else {
		$report->setOption('allow_show_nokw', 'yes');
	}

	// TOTAL for this enum
	// Note: the k.name IN (...) is because other keywords might be associated 
	// with this case/client/etc
	if ($my_line_table == 'lcm_author') {
		$report->addHeader(_Th('generic_input_total'), 'number', $k['enum_type']);

		$sql = "SELECT count(*) FROM lcm_keyword_" . $kwg['type'] . " as ka, "
			. " lcm_case_author as ca, lcm_keyword as k, lcm_case as c " . ($exists_stage_filter ? ", lcm_stage as s " : "")
			. " WHERE k.id_keyword = ka.id_keyword "
			. " AND c.id_case = ca.id_case "
			. ($exists_stage_filter ? " AND c.id_case = s.id_case " : "")
			. " AND ca.id_case = ka.id_case " // XXX
			. " AND k.id_keyword IN (" . implode(",", $all_kw_ids) . ")"
			. $sql_filter;
	} elseif ($my_line_table == 'lcm_followup') {
		$report->addHeader(_Th('generic_input_total'), 'time_input_length', $k['enum_type'], 'time_length');

		$sql = "SELECT sum(IF(UNIX_TIMESTAMP(fu.date_end) > UNIX_TIMESTAMP(fu.date_start), UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)) "
			. "FROM lcm_followup as fu, lcm_case as c "
			. "WHERE fu.id_case = c.id_case " . $sql_filter;
	}

	// Store special SQL command. 
	$special_id = $report->addSpecial($sql);
	$report->addColumn("1 as \"LCM_SQL:special:$special_id\"");

	}
}

//
// Add implicit fields if there will be a join table
// For example, if we select fields from lcm_author, we should include id_author
// even if we don't want to show it (for table joining, later).
// 

if ($rep_info['line_src_type'] == 'table'
	&& preg_match("/^lcm_(.*)$/", $my_line_table, $regs)
	&& count($report->getColumns()))
{

	// Check first if any id_foo was provided.
	// for example, crossing lcm_following and case-keyword using id_case explicitely
	// Note: it may be ex: fu.id_case, hence the strange regexp
	$tmp_lines = $report->getLines();

	foreach ($tmp_lines as $l) {
		if (preg_match("/^(.+\.)?id_/", $l)) {
			if (! $report->getLineKeyField())
				$report->setLineKeyField($l);
		}
	}

	if (! $report->getLineKeyField()) {
		$temp = get_table_suffix($my_line_table);
		if ($temp) {
			$temp .= ".id_" . $regs[1];
			$report->setLineKeyField($temp);
		}
	}
} elseif ($rep_info['line_src_type'] == 'keyword' && count($report->getColumns())) {
	$report->setLineKeyField('k.id_keyword');
}

//
// Start building the SQL query for the report lines
//

$my_line_fields = implode(", ", $report->getLines());
$my_col_fields  = implode(", ", $report->getColumns());

$report->addSQL("SELECT " . $my_line_fields);

// Hide implicit fields, but allow them to be in 'group by' if necessary
if ($my_line_fields && $report->getLineKeyField()) {
	$my_line_fields .= ", " . $report->getLineKeyField(); // [ML] only for backward compat
	$report->addSQL(", " . $report->getLineKeyField() . " as 'LCM_HIDE_ID' ");
	$report->addLine($report->getLineKeyField() . " as 'LCM_HIDE_ID'");
}

if ($my_col_fields)
	$report->addSQL(", " . $my_col_fields);

if ($rep_info['line_src_type'] == 'table') {
	$report->addSQL(" FROM " . $my_line_table . suffix_table($my_line_table));

	if ($rep_info['col_src_type'] == 'keyword' && $rep_info['col_src_name']) {
		$kwg = get_kwg_from_name($rep_info['col_src_name']);
		$kws = get_keywords_in_group_name($rep_info['col_src_name']);
		$kw_list_id = array();

		foreach($kws as $k)
			$kw_list_id[] = $k['id_keyword'];

		// FIXME lcm_case and lcm_followup specific!
		if ($my_line_table == 'lcm_case') {
			$report->addSQL(" LEFT JOIN lcm_keyword_case as kc "
					. " ON (kc.id_case = c.id_case AND kc.id_keyword IN (" . join(',', $kw_list_id) . ")) ");
			$report->addSQL(" LEFT JOIN lcm_keyword as k ON (k.id_keyword = kc.id_keyword) ");
		} elseif ($my_line_table == 'lcm_followup') {
			$report->addSQL(" LEFT JOIN lcm_keyword_case as kc "
					. " ON (kc.id_case = fu.id_case AND kc.id_keyword IN (" . join(',', $kw_list_id) . ")) ");
			$report->addSQL(" LEFT JOIN lcm_keyword as k ON (k.id_keyword = kc.id_keyword) ");
		}

	}

} elseif ($rep_info['line_src_type'] == 'keyword') {
	$report->addSQL(" FROM lcm_keyword as k "
		. " LEFT JOIN lcm_keyword_group as kwg "
		. " ON (k.id_group = kwg.id_group AND kwg.name = '" . $rep_info['line_src_name'] . "')");
		
	$report->addWhere("kwg.name IS NOT NULL");
}

// Join condition
if ($rep_info['line_src_type'] == 'table') {
	// join my_line_table with my_col_table
	if ($my_col_table && $do_special_join) {
		// from join (ex: dependancy on middle tables, such as Author-Case => lcm_case_author)
		$deps = join_tables($my_line_table, $my_col_table);
	
		if ($deps[0])
			$report->addSQL($deps[0]);

		if ($deps[1])
			$report->addWhere($deps[1]);

		$report->addSQL(" LEFT JOIN " . $my_col_table . suffix_table($my_col_table) . " ON (" . $deps[2] . " ) ");
	}
} elseif ($rep_info['line_src_type'] == 'keyword') {
	switch($rep_info['line_src_name']) {
		default:
			switch($my_col_table) {
				case '':
					break;
				case 'lcm_case':
					$report->addSQL(" LEFT JOIN lcm_keyword_case as kc ON (kc.id_keyword = k.id_keyword) ");
					break;
				case 'lcm_client':
					$report->addSQL(" LEFT JOIN lcm_keyword_client as kc ON (kc.id_keyword = k.id_keyword) ");

					if ($my_line_table != 'lcm_client')
						$report->addSQL(" LEFT JOIN lcm_client as cl ON (cl.id_client = kc.id_client) ");
					break;
				case 'lcm_stage':
					// Program goes here when crossing: case-keyword + lcm_stage
					/*
					lcm_panic("If you are trying to count the number of cases
						depending on their conclusion/result/sentence, try to use
						only 'case stage' as the report row, with the fields:
						conclusion (or result/sentence) + count");
					*/
					break;
				default:
					lcm_panic($report->getSQL() . " <br/> Not implemented: line_src = " . $rep_info['line_src_name'] 
							. ", col_table = " . $my_col_table);
			}
	}
}

//
// Fetch all filters for this report
//

$tmp_tables = "'$my_line_table'";
if ($my_col_table && $do_special_join)
	$tmp_tables .= ",'$my_col_table'";

$my_filters_sql = get_filters_sql($report, 'table', $tmp_tables);

if ($my_filters_sql)
	$report->addSQL("WHERE " . $my_filters_sql);

//
// Add the last "where" conditions
//

if (count($report->getWhere())) {
	if ($my_filters_sql)
		$report->addSQL(" AND ");
	else
		$report->addSQL(" WHERE ");
	
	$report->addSQL(implode(" AND ", $report->getWhere()));
}

if ($report->getOption('do_grouping') == 'yes') { // $do_grouping) {
	$group_fields = "";
	$tmp = array();
	$my_lines = $report->getLines();

	foreach($my_lines as $l)
		if (preg_match("/(.*) as .*/", $l, $regs))
			$tmp[] = $regs[1];
		elseif (! preg_match("/.*count\(\*\)/", $l))
			$tmp[] = $l;

	$group_fields = implode(',', $tmp);
	$report->addSQL(" GROUP BY " . $group_fields);
}

//
// Ready!
//

// [ML] The SQL dump is also shown at the end of the report, but when
// special queries fail, it is useful to have it before the SQL error.
if ($report->getOption('headers_sent') == 'yes' && $_REQUEST['debug'] == 2) {
	echo "\n\n<!-- QUERY = " . $report->getSQL() . " -->\n\n";
	for($cpt = 0; $cpt < $report->getSpecialCount(); $cpt++)
		echo "<!-- \t - $cpt: " . $report->getSpecial($cpt) . " -->\n";

	if (isset($_REQUEST['debug'])) {
		$dbg = $report->getJournal();

		foreach($dbg as $line)
			echo $line;
	}
}

if ($rep_info['filecustom']) {
	$result = null;
} else {
	$result = lcm_query($report->getSQL(), true);

	if (! $result) {
		if (isset($_REQUEST['debug'])) {
			echo "The report could not be generated. Please send the following
				information to the software developers if you think that this is a
				bug."; // TRAD

				echo "SQL = " . $report->getSQL();

			$dbg = $report->getJournal();

			foreach($dbg as $line)
				echo $line;
		} else {
			$tmp_link = new Link();
			$tmp_link->addVar('debug', '2');

			echo "The report could not be generated. Try running again using the "
				. '<a href="' . $tmp_link->getUrl() . '">debug mode</a>.'; // TRAD
		}

		exit;
	}
}

//
// Show filters applied
//

show_filters_info($report);

//
// Ready for report line
//

$report->printHeaderValueStart();

$my_headers = $report->getHeaders();

foreach ($my_headers as $h)
	$report->printHeaderValue($h['description']);

$report->printHeaderValueEnd();

if ($rep_info['filecustom']) 
	$report->run();

for ($cpt_lines = $cpt_col = 0; $result && ($row = lcm_fetch_array($result)); $cpt_lines++) {
	$report->printStartLine();

	foreach ($row as $key => $val) {
		if ((! is_numeric($key)) && ($key != 'LCM_HIDE_ID')) {
			$cpt_items = 0;

			$css = 'class="tbl_cont_' . ($cpt_lines % 2 ? "light" : "dark") . '"';
			$align = 'align="left"';

			//
			// Special cases
			//
			if ($my_headers[$cpt_col]['field_name'] == 'description')
				$val = get_fu_description($row);

			if ($val == "1" && preg_match("/^LCM_SQL:special:(.*)/", $key, $regs)) {
				$deps = join_tables($my_line_table, '', $row['LCM_HIDE_ID'], 0, $report, $report->getSpecial($regs[1]));
				$q_col = $report->getSpecial($regs[1]); // Fetch special rule
				$q_col .= $deps[2]; // WHERE [...]

				$allow_zoom = false;
				$zooming = false;

				// [ML] Limit zooming to queries involving cases, because we have no 
				// test cases for other uses
				if (preg_match("/lcm_case/", $q_col))
					$allow_zoom = true;

				if (isset($_REQUEST['zoom' . $cpt_lines . "-" . $cpt_col])) {
					$zooming = true;
					// FIXME (specific to reports involving cases (?))
					$q_col = preg_replace("/count\(\*\)/", "c.title, c.id_case", $q_col);
				}

				$report->addComment("[$cpt_lines:$cpt_col] $q_col");
				$result_tmp = lcm_query($q_col);
				$val = "";

				if ($zooming) 
					$val = '<div align="left"><ul style="padding: 0; padding-left: 1em; margin: 0;">';

				while($row_tmp = lcm_fetch_array($result_tmp)) {
					if ($zooming) {
						$tmp_link = new Link("case_det.php");
						$tmp_link->addVar('case', $row_tmp['id_case']); // XXX specific

						// This puts <td> in values ... 
						// $row_tmp[0] = get_ui_print_value($row_tmp[0], $my_headers[$cpt_col]);

						// FIXME [ML] $report should have a method, such as $r->supportsHtml()
						// or $r->addHtml() .. 
						if ($_REQUEST['export'] == 'csv' || $_REQUEST['export'] == 'ods') {
							$val .= $row_tmp[0];
						} else {
							$val .= '<li style="padding: 0; margin: 0;">';
							$val .= '<a class="content_link" href="' . $tmp_link->getUrl() . '">';
							$val .= $row_tmp[0];
							$val .= "</a></li>\n";
						}

						$cpt_items++;
					} else {
						if ($allow_zoom && $row_tmp[0] != 0) {
							$tmp_link = new Link();
							$tmp_link->addVar('zoom' . $cpt_lines . "-" . $cpt_col, 1);

							if ($_REQUEST['export'] == 'csv' || $_REQUEST['export'] == 'ods') { // FIXME
								$val .= $row_tmp[0];
							} else {
								$val .= '<a class="content_link" style="display: block;" href="' . $tmp_link->getUrl() . '">';
								$val .= $row_tmp[0];
								$val .= "</a>";
							}
						} else {
							$val .= $row_tmp[0];
						}

						$cpt_items += $row_tmp[0];
					}
				}

				if ($zooming) {
					$val .= "</ul></div>";

					$tmp_link = new Link();
					$tmp_link->delVar('zoom' . $cpt_lines . "-" . $cpt_col);
					$val .= '(<a class="content_link" href="' . $tmp_link->getUrl() . '">x</a>)';
				}

			} elseif ($val == "2" && preg_match("/^LCM_SQL:special:(.*)/", $key, $regs)) {
				$tmp = $report->getSpecial($regs[1]); // Fetch special rule

				$q_col = "SELECT count(*) ";
				$q_col .= strstr($report->getSQL(), "FROM");

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

					if (is_numeric($row_tmp[0]) && $row_tmp[0] > 0)
						$cpt_items += $row_tmp[0];
				}
			} elseif ($key == 'TRAD') {
				$val = remove_number_prefix(_Th($val));

				// [ML] I don't remember what $val might be, but it is probably 
				// numeric (if we are translating it), but just in case..
				if (is_numeric($val) && $val > 0)
					$cpt_items += $val;
			} else {
				if (is_numeric($val) && $val > 0)
					$cpt_items += $val;
			}

			// Translate values based on keywords (ex: fu.type)
			if ($my_headers[$cpt_col]['enum_type']) {
				$enum = split(":", $my_headers[$cpt_col]['enum_type']);

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
			$report->addTotal($cpt_col, $cpt_items);
			$report->printValue($val, $my_headers[$cpt_col], $css);
			$cpt_col = ($cpt_col + 1) % count($my_headers);
		}
	}

	$report->printEndLine();
	$report->incrementLine();
}

// 
// Footer
//
$css = 'class="tbl_cont_' . (($cpt_lines + 1) % 2 ? "light" : "dark") . '"';
$cpt_tmp = 0;

$report->printStartLine();

$my_headers = $report->getHeaders();

foreach ($my_headers as $h) {
	if ((! preg_match('/^(.+\.)?id_.+/', $h['field_name']))
		&& ($h['filter'] == 'number' || $h['filter'] == 'currency' || $h['filter_special'] == 'time_length'))
		$report->printValue($report->getTotal($cpt_tmp), $h, $css);
	elseif ($cpt_tmp == 0)
		$report->printValue(_Th('generic_input_total'), $h, $css);
	else
		$report->printValue('', $h, $css);
	
	$cpt_tmp++;
}

$report->printEndLine();
$report->printEndDoc();

if ($report->getOption('headers_sent') == 'yes') {
	echo "</table>\n";

	echo "<p>Number of rows: " . $report->getRowCount() . "</p>\n"; // TRAD

	// Report footnotes (ex: signed by manager, etc. -- allow HTML)
	echo $rep_info['notes'];

	if ($report->getOption('allow_show_nokw') == 'yes') {
		$tmp_link = new Link();
		$tmp_link->delVar('show_nokw');

		if ($report->getOption('show_nokw') == 'yes') {
			echo '<p><a href="' . $tmp_link->getUrl() . '" class="run_lnk">' . _T('rep_button_nokw_hide') . "</a></p>\n";
		} else {
			$tmp_link->addVar('show_nokw', "1");
			echo '<p><a href="' . $tmp_link->getUrl() . '" class="run_lnk">' . _T('rep_button_nokw_show') . "</a></p>\n";
		}
	}

	echo '<p><a href="rep_det.php?rep=' . $report->getId() . '" class="run_lnk">' . _T('rep_button_goback') . "</a></p>\n";

	//
	// Make a link to export the report
	//
	echo '<p>';

	$link_csv = new Link();
	$link_csv->delVar('export');
	$link_csv->addVar('export', 'csv');

	echo '<a href="' . $link_csv->getUrl() . '" class="exp_lnk">' . _T('rep_button_exportcsv') . '</a> ';

	$link_ods = new Link();
	$link_ods->delVar('export');
	$link_ods->addVar('export', 'ods');

	echo '<a href="' . $link_ods->getUrl() . '" class="exp_lnk">' .  _T('rep_button_exportcsv') . ' (ODS)' . '</a>'; // TRAD
	
	echo "</p>\n";

	//
	// Print debug information, if requested
	//
	if (isset($_REQUEST['debug'])) {
		$dbg = $report->getJournal();

		foreach($dbg as $line)
			echo $line;
	}
	
	lcm_page_end();
}


?>
