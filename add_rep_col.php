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
$order = intval($_POST['order']);
$header = clean_input($_POST['header']);
$field = intval($_POST['field']);
$sort = clean_input($_POST['sort']);

if (($rep>0) && ($field)) {
	// Change order of the columns to be left behind the new one
	$q = "UPDATE lcm_rep_cols
			SET lcm_rep_cols.order=lcm_rep_cols.order+1
			WHERE (id_report=$rep
				AND lcm_rep_cols.order>=$order)";
	$result = lcm_query($q);

	// Insert new column info
	$q = "INSERT INTO lcm_rep_cols
			SET id_report=$rep,id_field=$field,lcm_rep_cols.order=$order,header='$header',sort='$sort'";
	$result = lcm_query($q);
}

header("Location: " . $GLOBALS['HTTP_REFERER']);

?>
