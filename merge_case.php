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

	$Id: merge_case.php,v 1.8 2006/03/16 23:07:21 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Get input values
$type = clean_input($_POST['type']);
$destination = intval($_POST['destination']);
$case = intval($_POST['id_case']);
$sumbilled = ($_POST['sumbilled'] ? $_POST['sumbilled'] : 0);
$ref_edit_fu = clean_input($_POST['ref_edit_fu']);
$id_author = $GLOBALS['author_session']['id_author'];

// Check incoming data
if ($type != 'merge') die("This module is used for case merging only!");
if (!($case>0)) die("Which case?");

// Check access rights
if (!allowed($case,'w')) die("You don't have permission to add information to this case!");

// Create new case if $destination is 0
if ($destination==0) {
	// Create new case
	$q = "INSERT INTO lcm_case SET
			id_case=0,
			date_creation=NOW(),
			status='open'";
	$result = lcm_query($q);
	$destination = lcm_insert_id('lcm_case', 'id_case');

	// Insert new case_author relation
	$q = "INSERT INTO lcm_case_author SET
			id_case=$destination,
			id_author=$id_author,
			ac_read=1,
			ac_write=1,
			ac_admin=1";
	$result = lcm_query($q);
}

// Add "merged to" follow-up to the old case
$q = "INSERT INTO lcm_followup SET id_followup=0,
		id_case=$case,
		id_author=$id_author,
		date_start=NOW(),
		date_end=NOW(),
		type='$type',
		sumbilled=$sumbilled,
		description='Merged to case ID:$destination\\n$description'";
$result = lcm_query($q);

// Add "merged from" follow-up to the new case
$q = "INSERT INTO lcm_followup SET
		id_followup=0,
		id_case=$destination,
		id_author=$id_author,
		date_start=NOW(),
		date_end=NOW(),
		type='$type',
		description='Case ID:$case merged in. \\n$description'";
// That would cause double counting the sumbilled
//		sumbilled=$sumbilled";
$result = lcm_query($q);

//
// Copy authors from the old to the new case
//

// 1. Get the authors of old case, which are NOT authors of the new
$q = "SELECT a1.*
		 FROM lcm_case_author a1
		 LEFT JOIN lcm_case_author a2
		 ON (a1.id_case=$case) AND (a2.id_case=$destination) AND (a1.id_author=a2.id_author)
		 WHERE (a1.id_case=$case) AND (a2.id_case IS NULL)";
$result = lcm_query($q);

// 2. Associate authors with the new case
$q = '';
while ($row = lcm_fetch_array($result)) {
	$row['id_case'] = $destination;
	$q .= ($q ? ',' : '') . '(' . implode(',',$row) . ')';
}
$q = "INSERT INTO lcm_case_author VALUES $q";
$result = lcm_query($q);

//
// Copy clients from the old to the new case
//

// 1. Get the clients of old case, which are NOT in the new
$q = "SELECT c1.id_case,c1.id_client
		 FROM lcm_case_client_org c1
		 LEFT JOIN lcm_case_client_org c2
		 ON (c1.id_case=$case) AND (c2.id_case=$destination) AND (c1.id_client=c2.id_client)
		 WHERE (c1.id_case=$case) AND (c2.id_case IS NULL) AND (c1.id_client>0)";
$result = lcm_query($q);

// 2. Associate clients with the new case
$q = '';
while ($row = lcm_fetch_array($result)) {
	$q .= ($q ? ',' : '') . "($destination," . $row['id_client'] . ',DEFAULT)';
}
$q = "INSERT INTO lcm_case_client_org VALUES $q";
$result = lcm_query($q);

//
// Copy organisations from the old to the new case
//

// 1. Get the organisations of old case, which are NOT in the new
$q = "SELECT o1.id_case,o1.id_org
		 FROM lcm_case_client_org o1
		 LEFT JOIN lcm_case_client_org o2
		 ON (o1.id_case=$case) AND (o2.id_case=$destination) AND (o1.id_org=o2.id_org)
		 WHERE (o1.id_case=$case) AND (o2.id_case IS NULL) AND (o1.id_org>0)";
$result = lcm_query($q);

// 2. Associate organisations with the new case
$q = '';
while ($row = lcm_fetch_array($result)) {
	$q .= ($q ? ',' : '') . "($destination,DEFAULT," . $row['id_org'] . ')';
}
$q = "INSERT INTO lcm_case_client_org VALUES $q";
$result = lcm_query($q);

//
// Update old case status to 'merged'
//
$q = "UPDATE lcm_case SET status='merged' WHERE id_case=$case";
$result = lcm_query($q);

// Clear the session
// [ML] why? session_destroy();


// Send user back to add/edit page's referer
header("Location: $ref_edit_fu");

?>
