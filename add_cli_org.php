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

	$Id: add_cli_org.php,v 1.4 2005/03/29 17:56:27 antzi Exp $
*/

include('inc/inc.php');

// Clean the POST values
$org = intval($_POST['org']);

if ($org > 0) {
	if ( isset($_POST['clients']) && (count($_POST['clients']) > 0) ) {
		//
		// Add organization representatives
		//
		$values = array();
		foreach($_POST['clients'] as $client) {
			$client = intval($client);
			if ($client > 0) $values[] = "($org,$client)";
		}

		if (count($values) > 0) {
			// Prepare and do the query
			$q="INSERT INTO lcm_client_org (id_org,id_client) VALUES " . join(',',$values);
			if (!($result = lcm_query($q))) die("$q<br>\n" . _T('title_error') . " " . lcm_errno() . ": " . lcm_error());
		}
	} else if ( isset($_POST['rem_clients']) && (count($_POST['rem_clients']) > 0) ) {
		//
		// Remove organization representatives
		//
		$values = array();
		foreach($_POST['rem_clients'] as $client) {
			$client = intval($client);
			if ($client > 0) $values[] = $client;
		}

		if (count($values) > 0) {
			// Prepare and do the query
			$q="DELETE FROM lcm_client_org WHERE id_org=$org AND id_client IN (" . join(',',$values) . ")";
			if (!($result = lcm_query($q))) die("$q<br>\n" . _T('title_error') . " " . lcm_errno() . ": " . lcm_error());
		}
	}
}

//header("Location: $ref_sel_cli_org");
header("Location: org_det.php?org=$org&tab=representatives");

?>
