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

	$Id: add_org_cli.php,v 1.5 2005/03/29 17:55:50 antzi Exp $
*/

include('inc/inc.php');

// Clean the POST values
$client = intval($_POST['client']);

if ($client > 0) {
	if ( isset($_POST['orgs']) && (count($_POST['orgs']) > 0) ) {
		$values = array();
		foreach($_POST['orgs'] as $org) {
			$org = intval($org);
			if ($org > 0) $values[] = "($client,$org)";
		}

		if (count($values) > 0) {
			// Prepare and do the query
			$q = "INSERT INTO lcm_client_org (id_client,id_org) VALUES " . join(',',$values);
			if (!($result = lcm_query($q))) die("$q<br>\n" . _T('title_error') . " " . lcm_errno() . ": " . lcm_error());
		}
	} else if ( isset($_POST['rem_orgs']) && (count($_POST['rem_orgs']) > 0) ) {
		$values = array();
		foreach($_POST['rem_orgs'] as $org) {
			$org = intval($org);
			if ($org > 0) $values[] = $org;
		}

		if (count($values) > 0) {
			// Remove relation client-organization from database
			$q = "DELETE FROM lcm_client_org WHERE id_client=$client AND id_org IN (" . join(',',$values) . ")";
			if (!($result = lcm_query($q))) die("$q<br>\n" . _T('title_error') . " " . lcm_errno() . ": " . lcm_error());
		}
	}
}

//header("Location: $ref_sel_org_cli");
header("Location: client_det.php?client=$client&tab=organisations");

?>
