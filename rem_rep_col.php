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

	$Id: rem_rep_col.php,v 1.3 2005/02/07 13:00:51 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_lang');

// Clean the POST values
$rep = intval($_GET['rep']);
$order = intval($_GET['order']);

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

}

header("Location: " . $GLOBALS['HTTP_REFERER']);

?>
