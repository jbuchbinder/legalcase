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

	$Id: upd_rep_field.php,v 1.14 2006/04/17 20:01:14 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

$rep = intval(_request('rep', 0));

if (! $rep) {
	lcm_log("upd_rep_field.php: missing rep (id)");
	lcm_header("Location: listreps.php");
	exit;
}

// After returning to the page referer, jump to a specific place
// Ex: #line, #col, #filter, etc.
$ref_tag = "";

if (_request('remove')) {
	$remove = $_REQUEST['remove']; // = { 'col', 'line' }

	if ($remove == 'col') {
		$id_column = intval($_REQUEST['id_column']);
	
		if (! $id_column)
			die ("remove column: missing valid 'id_column'");
	
		$query = "DELETE FROM lcm_rep_col
					WHERE id_report = " . $rep . "
					AND id_column = " . $id_column;
	
		lcm_query($query);
		$ref_tag = "#col";
	} else if ($remove == 'line') {
		$id_line = intval($_REQUEST['id_line']);
	
		if (! $id_line)
			die ("remove line: missing valid 'id_line'");
		
		$query = "DELETE FROM lcm_rep_line
					WHERE id_report = " . $rep . "
					AND id_line = " . $id_line;
	
		lcm_query($query);
		$ref_tag = "#line";
	} else if ($remove == 'filter') {
		$id_filter = intval($_REQUEST['id_filter']);

		if (! $id_filter)
			die ("remove filter: missing valid 'id_filter'");

		// id_report is not mandatory, but it helps avoid errors
		$query = "DELETE FROM lcm_rep_filter
					WHERE id_filter = " . $id_filter . "
					AND id_report = " . $rep;

		lcm_query($query);
		$ref_tag = "#filter";
	}
}

if (_request('add')) {
	$add = $_REQUEST['add']; // = { 'col', 'line', 'filter' }
	$id_field = intval($_REQUEST['id_field']);

	if (! $id_field) {
		// This is normal to happen, but log in case we have weird bugs
		lcm_log("upd_rep_field: add column: no id_field provided");
	} elseif ($add == 'col') {
		$order = intval($_REQUEST['order']);
		$header = clean_input($_REQUEST['header']);
		$sort = clean_input($_REQUEST['sort']);
	
		// TODO: Add "position"

		$query = "INSERT INTO lcm_rep_col
				SET id_report = $rep,
					id_field = $id_field,
					col_order = $order,
					header = '$header',
					sort = '$sort'";
	
		lcm_query($query);
		$ref_tag = "#col";
	} else if ($add == 'line') {
		// TODO: Add "position"
		// $order = intval($_REQUEST['order']);
		// $header = clean_input($_REQUEST['header']);
		// $sort = clean_input($_REQUEST['sort']);

		$query = "INSERT INTO lcm_rep_line
				SET id_report = $rep,
					id_field = $id_field";
		// TODO: Add sort_type, col_order, total, ...
	
		lcm_query($query);
		$ref_tag = "#line";
	} else if ($add == 'filter') {
		$query = "INSERT INTO lcm_rep_filter
				SET id_report = $rep,
					id_field = $id_field,
					type = '',
					value = ''";

		lcm_query($query);
		$ref_tag = "#filter";
	}
}

if (_request('update')) {
	$update = $_REQUEST['update']; // = { 'filter' }
	$id_filter = intval($_REQUEST['id_filter']);

	if (! $id_filter)
		die ("update field: missing valid 'id_filter'");

	if ($update == 'filter') {
		$type = clean_input($_REQUEST['filter_type']);

		$fields = array();
		$flist = "";

		if ($type)
			array_push($fields, "type = '" . $type . "'");

		switch($type) {
			// For dates, it is important to fallback on null date so that the
			// user can clear out/delete a date previously entered.
			case 'date_eq': // not very important whether start/end, will be sql IN_YEAR()
			case 'date_le': // ex: date <= 2005 becomes date <= 2005-01-01 00:00:00
			case 'date_ge': // ex: date >= 2005 becomes date >= 2005-01-01 00:00:00
			case 'date_lt': // ex: date < 2005 becomes date < 2005-01-01 00:00:00
				$date = get_datetime_from_array($_REQUEST, 'date', 'start', '0000-00-00 00:00:00');
				array_push($fields, "value = IF(TO_DAYS('$date') > 0, '" . $date . "', '')");
				break;
			case 'date_gt': // ex: date > 2005 becomes date > 2005-12-31 23:59:59
				$date = get_datetime_from_array($_REQUEST, 'date', 'end', '0000-00-00 00:00:00');
				array_push($fields, "value = IF(TO_DAYS('$date') > 0, '" . $date . "', '')");
				break;
			case 'date_in':
				$date_start = get_datetime_from_array($_REQUEST, 'date_start', 'start', '0000-00-00 00:00:00');
				$date_end   = get_datetime_from_array($_REQUEST, 'date_end', 'end', '0000-00-00 00:00:00');

				if (isset_datetime_from_array($_REQUEST, 'date_start', 'year_only')
					|| isset_datetime_from_array($_REQUEST, 'date_end', 'year_only'))
				{
					array_push($fields, "value = CONCAT("
							. "IF(TO_DAYS('$date_start') > 0, '$date_start', ''),"
							. "';',"
							. "IF(TO_DAYS('$date_end') > 0, '$date_end', '')"
							. ")");
				}
				
				break;
			default:
				$value = clean_input($_REQUEST['filter_value']);
				array_push($fields, "value = '" . $value . "'");
		}

		if (count($fields))
			$flist = implode(", ", $fields);

		$query = "UPDATE lcm_rep_filter
						SET " . $flist . "
						WHERE id_filter = " . $id_filter;

		lcm_query($query);
		$ref_tag = "#filter";
	}
}

if (_request('select_col_type') && _request('select_col_name')) {
	// Update only if not already set, or it will create mess
	// if (! ($rep_info['col_src_type'] && $rep_info['col_src_name'])) {
		$query = "UPDATE lcm_report
					SET col_src_type = '" . clean_input($_REQUEST['select_col_type']) . "',
						col_src_name = '" . clean_input($_REQUEST['select_col_name']) .  "'
					WHERE id_report = " . $rep;

		lcm_query($query);
	// }

	$ref_tag = "#col";
}

if (_request('select_line_type') && _request('select_line_name')) {
	// Update only if not already set, or it will create mess
	// if (! ($rep_info['line_src_type'] && $rep_info['line_src_name'])) {
		$query = "UPDATE lcm_report
					SET line_src_type = '" . clean_input($_REQUEST['select_line_type']) . "',
						line_src_name = '" . clean_input($_REQUEST['select_line_name']) . "'
					WHERE id_report = " . $rep;

		lcm_query($query);
	// }

	$ref_tag = "#line";
}

if (_request('unselect_col')) {
	$query = "UPDATE lcm_report
			SET col_src_type = '',
				col_src_name = ''
			WHERE id_report = " . $rep;
	
	lcm_query($query);
	$ref_tag = "#col";
}

if (_request('unselect_line')) {
	$query = "UPDATE lcm_report
			SET line_src_type = '',
				line_src_name = ''
			WHERE id_report = " . $rep;
	
	lcm_query($query);
	$ref_tag = "#line";
}

if (_request('filecustom')) {
	if (include_custom_report_exists(_request('filecustom'))) {
		include_custom_report(_request('filecustom'));
		$obj = new CustomReportSpecs();

		$do_update = false;
		$query = "UPDATE lcm_report SET ";

		if (($info = $obj->getReportLine())) {
			$query .= "line_src_type = '" . $info['type'] . "',
						line_src_name = '" . $info['name'] . "'";

			$do_update = true;
		}

		if (($info = $obj->getReportCol())) {
			if ($do_update)
				$query .= ", ";

			$query .= " col_src_type = '" . $info['type'] . "'";
			
			// Ignore if name not set, or name restricts the choice (ex:  keyword that applies to 'case')
			if (! $info['name'] || substr($info['name'], 0, 4) == 'FOR:')
				$query .= ", col_src_name = '' ";
			else
				$query .= ", col_src_name = '" . $info['name'] . "' ";

			$do_update = true;
		}

		if ($do_update)
			lcm_query($query);
	} else {
		$_SESSION['errors']['filecustom'] = "Custom report file does not exist: "
				. htmlspecialchars(_request('filecustom'));
	}
}

lcm_header("Location: rep_det.php?rep=" . $rep . $ref_tag);

?>
