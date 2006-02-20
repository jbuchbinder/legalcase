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

	$Id: add_auth.php,v 1.15 2006/02/20 03:01:36 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_lang');

// Clear all previous errors
$_SESSION['errors'] = array();

// Clean input variables
$case = intval($_POST['case']);
$ref_sel_auth = ($_POST['ref_sel_auth']);
$authors = array();

if (isset($_POST['authors']) && is_array($_POST['authors'])) 
	foreach ($_POST['authors'] as $key => $value)
		$authors[$key] = $value;

if (! ($case > 0)) {
	header("Location: $ref_sel_auth");
	exit;
}

if (! $authors) {
	header("Location: $ref_sel_auth");
	exit;
}

// Check for admin rights on case
if (! allowed($case, 'a')) {
	$_SESSION['errors']['generic'] = _T('error_add_auth_no_rights');
	header("Location: $ref_sel_auth");
	exit;
}

// Get the current case stage for the FU entry
$case_stage = '';
$q = "SELECT stage FROM lcm_case where id_case = " . $case;
$result = lcm_query($q);

if (($row = lcm_fetch_array($result))) {
	$case_stage = $row['stage'];
} else {
	$_SESSION['errors']['generic'] = _T('error_add_auth_no_rights');
	header("Location: $ref_sel_auth");
	exit;
}

			foreach($authors as $author) {
				$q="INSERT INTO lcm_case_author
					SET id_case=$case,id_author=$author";

				$result = lcm_query($q);

				// Get author information
				$q = "SELECT *
						FROM lcm_author
						WHERE id_author=$author";
				$result = lcm_query($q);
				$author_data = lcm_fetch_array($result);

				// Add 'assigned' followup to the case
				$q = "INSERT INTO lcm_followup
						SET date_start = NOW(), date_end = NOW(),
							id_followup = 0, id_case = $case, 
							id_author = " . $GLOBALS['author_session']['id_author'] . ",
							type = 'assignment', 
							description = '" . $author_data['id_author'] . "',
							case_stage = '$case_stage'";

				$result = lcm_query($q);

				// Set case date_assigned to NOW()
				$q = "UPDATE lcm_case
						SET date_assignment = NOW()
						WHERE id_case = $case";
				$result = lcm_query($q);
			}

header("Location: $ref_sel_auth");

?>
