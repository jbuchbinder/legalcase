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

// Clean input data
$rep = intval($_GET['rep']);

// Get report info
$q = "SELECT *
		FROM lcm_report
		WHERE id_report=$rep";
$result = lcm_query($q);
$row = lcm_fetch_array($result);
lcm_page_start($row['title']);

// Get report columns
$q = "SELECT lcm_rep_cols.*,lcm_fields.*
		FROM lcm_rep_cols,lcm_fields
		WHERE (id_report=$rep
			AND lcm_rep_cols.id_field=lcm_fields.id_field)
		ORDER BY lcm_rep_cols.order";
$result = lcm_query($q);

// Process report column data to prepare SQL query
$fl = '';		// fields list
$ta = array();	// tables array
$sl = '';		// sort list
$sl_text = '';	// Sorting explaination

while ($row = lcm_fetch_array($result)) {
	if ($fl) $fl .= ',';
	$fl .= $row['table_name'] . '.' . $row['field_name'] . " AS '" . $row['header'] . "'";
	if (!in_array($row['table_name'],$ta)) $ta[] = $row['table_name'];
	if ($row['sort']) {
		if ($sl) $sl .= ',';
		$sl .= $row['table_name'] . '.' . $row['field_name'] . " " . $row['sort'];
		$sl_text .= ( $sl_text ? ', "' : '"' ) . $row['description'] . '"';
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
			ORDER BY lcm_filter_conds.order";
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
	$wl .= ' AND lcm_case.id_case=lcm_case_author.id_case AND lcm_author.id_author=lcm_case_author.id_author';
}

$tl = implode(',',$ta);

$q = "SELECT $fl\n\tFROM $tl\n\tWHERE ($wl)\n\tORDER BY $sl";
$result = lcm_query($q);

echo "<!-- query is: '$q' -->\n";

if (lcm_num_rows($result)>0) {
	if ($sl_text) echo "Sorted by: $sl_text<br>\n";
	if ($filter_text) echo "Filters applied: $filter_text<br>\n";
	echo "<table border='0' class='tbl_usr_dtl'>\n";
	echo "\t<tr>\n";
	for ($i=0; $i<mysql_num_fields($result); $i++) {
		echo "\t\t<th class='heading'>" . mysql_field_name($result,$i) . "</th>\n";
	}
	echo "\t</tr>\n";
	while ($row = lcm_fetch_array($result)) {
		echo "\t<tr>";
		for ($j=0; $j<$i; $j++) {
			echo "\t\t<td>" . $row[$j] . "</td>\n";
		}
		echo "\t</tr>\n";
	}
	echo "</table>";
}

lcm_page_end();

?>
