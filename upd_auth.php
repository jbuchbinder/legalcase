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

	$Id: upd_auth.php,v 1.4 2005/05/12 13:40:33 mlutfy Exp $
*/

include('inc/inc.php');
include('inc/inc_acc.php');

// TODO: Replace by $_SESSION['errors']
if (! ($case > 0)) {
	lcm_page_start(_T('title_error'));
	echo "<p>" . _T('error_no_case_specified') . "</p>\n";
	lcm_page_end();
	exit;
}

if (isset($_REQUEST['ref_edit_auth']) && $_REQUEST['ref_edit_auth'])
	$referer = $_REQUEST['ref_edit_auth'];
else
	$referer = "case_det.php?case=" . $case;

// Check if any work to do
if (empty($auth)) {
	header('Location: ' . $ref_edit_auth);
	exit;
}

if (! allowed($case,'a'))
	die("You don't have permission to edit this case's access rights.");

foreach ($auth as $id => $access) {
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
					description = '" . $author_data['id_author'] . "'";

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
