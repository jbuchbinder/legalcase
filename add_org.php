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

	$Id: add_org.php,v 1.5 2005/03/18 09:46:44 mlutfy Exp $
*/

include('inc/inc.php');

$case = intval($_REQUEST['case']);

$destination = "case_det.php?case=$case";
if (isset($_REQUEST['ref_sel_client']) && $_REQUEST['ref_sel_client'])
	$destination = $ref_sel_client;

// Test whether organisations were selected
if (! isset($_REQUEST['orgs'])) {
	header("Location: " . $destination);
	exit;
}

foreach ($_REQUEST['orgs'] as $key=>$value)
	$orgs[$key] = intval($value);

if (($case>0) && ($orgs)) {
	foreach($orgs as $org) {
		$q="INSERT INTO lcm_case_client_org
			SET id_case=$case,id_org=$org";

		$result = lcm_query($q);
	}
}

header("Location: " . $destination);

?>
