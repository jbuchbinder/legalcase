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

	$Id: upd_rep_field.php,v 1.6 2005/02/10 13:09:41 mlutfy Exp $
*/

include('inc/inc.php');

// Clean the POST values
$rep = intval($_REQUEST['rep']);
// $order = intval($_REQUEST['order']);

// After returning to the page referer, jump to a specific place
// Ex: #line, #column, #filter, etc.
$ref_tag = "";

if (isset($_REQUEST['remove'])) {
	$remove = $_REQUEST['remove']; // = { 'column', 'line' }

	if ($remove == 'column') {
		$id_column = intval($_REQUEST['id_column']);
	
		if (! $id_column)
			die ("remove column: missing valid 'id_column'");
	
		$query = "DELETE FROM lcm_rep_col
					WHERE id_report = " . $rep . "
					AND id_column = " . $id_column;
	
		lcm_query($query);
		$ref_tag = "#column";
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

if (isset($_REQUEST['add'])) {
	$add = $_REQUEST['add']; // = { 'column', 'line', 'filter' }
	$id_field = intval($_REQUEST['id_field']);

	if (! $id_field)
		die ("add column: missing valid 'id_field'");

	if ($add == 'column') {
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
		$ref_tag = "#column";
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

if (isset($_REQUEST['update'])) {
	$update = $_REQUEST['update']; // = { 'filter' }
	$id_filter = intval($_REQUEST['id_filter']);

	if (! $id_filter)
		die ("update field: missing valid 'id_filter'");

	if ($update == 'filter') {
		$type = clean_input($_REQUEST['filter_type']);
		$value = clean_input($_REQUEST['filter_value']);

		$fields = array();
		$flist = "";

		if ($type)
			array_push($fields, "type = '" . $type . "'");

		if ($value)
			array_push($fields, "value = '" . $value . "'");

		if (count($fields))
			$flist = implode(", ", $fields);

		$query = "UPDATE lcm_rep_filter
						SET " . $flist . "
						WHERE id_filter = " . $id_filter;

		lcm_query($query);
		$ref_tag = "#filter";
	}
}

if (isset($_REQUEST['select_col_type']) && isset($_REQUEST['select_col_name'])) {
	// Update only if not already set, or it will create mess
	// if (! ($rep_info['col_src_type'] && $rep_info['col_src_name'])) {
		$query = "UPDATE lcm_report
					SET col_src_type = '" . clean_input($_REQUEST['select_col_type']) . "',
						col_src_name = '" . clean_input($_REQUEST['select_col_name']) .  "'";

		lcm_query($query);
	// }

	$ref_tag = "#column";
}

if (isset($_REQUEST['select_line_type']) && isset($_REQUEST['select_line_name'])) {
	// Update only if not already set, or it will create mess
	// if (! ($rep_info['line_src_type'] && $rep_info['line_src_name'])) {
		$query = "UPDATE lcm_report
					SET line_src_type = '" . clean_input($_REQUEST['select_line_type']) . "',
						line_src_name = '" . clean_input($_REQUEST['select_line_name']) . "'";

		lcm_query($query);
	// }

	$ref_tag = "#line";
}

if (isset($_REQUEST['unselect_col'])) {
	$query = "UPDATE lcm_report
			SET col_src_type = '',
				col_src_name = ''
			WHERE id_report = " . $rep;
	
	lcm_query($query);
	$ref_tag = "#column";
}

if (isset($_REQUEST['unselect_line'])) {
	$query = "UPDATE lcm_report
			SET line_src_type = '',
				line_src_name = ''
			WHERE id_report = " . $rep;
	
	lcm_query($query);
	$ref_tag = "#line";
}

/*
if (($rep>0) && ($order)) {
	// Remove the column
	$q = "DELETE FROM lcm_rep_col
			WHERE id_report=$rep
			AND col_order=$order";
	$result = lcm_query($q);

	// Change order of the rest of the columns
	$q = "UPDATE lcm_rep_col
			SET col_order=col_order-1
			WHERE (id_report=$rep
				AND col_order>$order)";
	$result = lcm_query($q);

} */

header("Location: " . $GLOBALS['HTTP_REFERER'] . $ref_tag);

?>
