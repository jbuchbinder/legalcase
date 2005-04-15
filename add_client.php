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

	$Id: add_client.php,v 1.7 2005/04/15 12:19:41 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');

$case = intval($_REQUEST['case']);
$_SESSION['errors'] = array();

$destination = "case_det.php?case=$case";

/* [ML] Not useful for now (my prefs redirection bug)
if (isset($_REQUEST['ref_sel_client']) && $_REQUEST['ref_sel_client'])
	$destination = $ref_sel_client;
*/

// Test access rights (unlikely to happen, unless hack attempt)
if (! ($case && allowed($case, 'a'))) {
	$_SESSION['errors']['generic'] = "Access denied"; // TRAD
	header("Location: " . $destination);
	exit;
}

// Add client to case
if (isset($_REQUEST['clients'])) {
	foreach ($_REQUEST['clients'] as $key=>$value) 
		$clients[$key] = intval($value);

	if ($clients) {
		foreach($clients as $client) {
			$q="INSERT INTO lcm_case_client_org
				SET id_case=$case,id_client=$client";

			$result = lcm_query($q);
		}
	}
}

// Remove client from case
if (isset($_REQUEST['id_del_client'])) {
	foreach ($_REQUEST['id_del_client'] as $id_client) {
		$q="DELETE FROM lcm_case_client_org
			WHERE id_case = $case
			AND id_client = $id_client";

		$result = lcm_query($q);
	}
}

// Remove organisation from case
if (isset($_REQUEST['id_del_org'])) {
	foreach ($_REQUEST['id_del_org'] as $id_org) {
		$q="DELETE FROM lcm_case_client_org
			WHERE id_case = $case
			AND id_org = $id_org";

		$result = lcm_query($q);
	}
}

header("Location: " . $destination . "#clients");

?>
