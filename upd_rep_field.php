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

	$Id: upd_rep_field.php,v 1.2 2005/02/07 15:34:42 mlutfy Exp $
*/

include('inc/inc.php');

// Clean the POST values
$rep = intval($_REQUEST['rep']);
// $order = intval($_REQUEST['order']);

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
	} else if ($remove == 'line') {
		$id_line = intval($_REQUEST['id_line']);
	
		if (! $id_line)
			die ("remove line: missing valid 'id_line'");
		
		$query = "DELETE FROM lcm_rep_line
					WHERE id_report = " . $rep . "
					AND id_line = " . $id_line;
	
		lcm_query($query);
	}
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

header("Location: " . $GLOBALS['HTTP_REFERER']);

?>
