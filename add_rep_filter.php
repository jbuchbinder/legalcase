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
include_lcm('inc_lang');

// Clean the POST values
$rep = intval($_POST['rep']);
$filter = intval($_POST['filter']);

if (($rep>0) && ($filter)) {
	// Add new filter
	$q = "INSERT INTO lcm_rep_filters
			SET id_report=$rep,id_filter=$filter";
	$result = lcm_query($q);
}

header("Location: " . $GLOBALS['HTTP_REFERER']);

?>
