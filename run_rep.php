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

$rep = intval($_GET['rep']);

$q = "SELECT *
		FROM lcm_report
		WHERE id_report=$rep";
$result = lcm_query($q);
$row = lcm_fetch_array($result);
lcm_page_start($row['title']);

$q = "SELECT lcm_rep_cols.*,lcm_fields.*
		FROM lcm_rep_cols,lcm_fields
		WHERE (id_report=$rep
			AND lcm_rep_cols.id_field=lcm_fields.id_field)
		ORDER BY lcm_rep_cols.order";
$result = lcm_query($q);

$fl = '';
$ta = array();
$sl = '';
while ($row = lcm_fetch_array($result)) {
	if ($fl) $fl .= ',';
	$fl .= $row['table_name'] . '.' . $row['field_name'] . " AS '" . $row['header'] . "'";
	if (!in_array($row['table_name'],$ta)) $ta[] = $row['table_name'];
	if ($row['sort']) {
		if ($sl) $sl .= ',';
		$sl .= $row['table_name'] . '.' . $row['field_name'] . " " . $row['sort'];
	}
}

$wl = '1';
if (in_array('lcm_case',$ta) && in_array('lcm_author',$ta)) {
	$ta[] = 'lcm_case_author';
	$wl .= ' AND lcm_case.id_case=lcm_case_author.id_case AND lcm_author.id_author=lcm_case_author.id_author';
}

$tl = implode(',',$ta);

$q = "SELECT $fl FROM $tl WHERE ($wl) ORDER BY $sl";
$result = lcm_query($q);

echo "<!-- query is: '$q' -->\n";

if (lcm_num_rows($result)>0) {
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
