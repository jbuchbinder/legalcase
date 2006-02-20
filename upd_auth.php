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

	$Id: upd_auth.php,v 1.7 2006/02/20 03:24:27 mlutfy Exp $
*/

include('inc/inc.php');

// Clear all previous errors
$_SESSION['errors'] = array();

// Clean input variables
$case = intval($_REQUEST['case']);

if (isset($_REQUEST['ref_edit_auth']) && $_REQUEST['ref_edit_auth'])
	$referer = $_REQUEST['ref_edit_auth'];
else
	$referer = "case_det.php?case=" . $case;

if (! ($case > 0)) {
	header("Location: $referer");
	exit;
}

// Check if any work to do
if (empty($_REQUEST['auth'])) {
	$_SESSION['errors']['generic'] = "no auth";
	header('Location: ' . $referer);
	exit;
}

// Check for admin rights on case
if (! allowed($case, 'a')) {
	$_SESSION['errors']['generic'] = _T('error_add_auth_no_rights');
	header("Location: $referer");
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

foreach ($_REQUEST['auth'] as $id => $access) {
	$admin = $write = $edit = $read = $remove = 0;

	switch($access) {
		// No break until 'read', it's normal, rights are cumulative.
		case 'admin':
			$admin = 1;
		case 'write':
			$edit  = 1;
			$write = 1;
		case 'read':
			$read = 1;
			break;
		case 'remove':
			$remove = 1;
			break;
	}

	if ($remove) {
		$q = "DELETE FROM lcm_case_author
				WHERE id_case = $case AND id_author = $id";

		$result = lcm_query($q);

		// Add 'un-assigned' followup to the case
		$q = "INSERT INTO lcm_followup
				SET date_start = NOW(), date_end = NOW(),
					id_followup = 0, id_case = $case, 
					id_author = " . $GLOBALS['author_session']['id_author'] . ",
					type = 'unassignment', 
					description = '" . $id . "',
					case_stage = '$case_stage'";

		$result = lcm_query($q);
	} else {
		$q = "UPDATE lcm_case_author
			SET ac_read   = $read,
			ac_write  = $write,
			ac_edit   = $edit,
			ac_admin  = $admin
			WHERE id_case = $case AND id_author = $id";

		$result = lcm_query($q);
	}
}

header('Location: ' . $referer);

?>
