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

	$Id: export.php,v 1.4 2005/04/28 01:06:32 antzi Exp $
*/

include('inc/inc_version.php');
include_lcm('inc_auth');
include_lcm('inc_filters');
include_lcm('inc_impex');
include_lcm('inc_xml');

if ($GLOBALS['author_session']['status'] != 'admin')
	lcm_panic("You don't have permission to export!");

$item = clean_input($_REQUEST['item']);
if (!empty($_REQUEST['id']))
	$id = intval($_REQUEST['id']);

$data = array();
switch ($item) {
	case 'case' :
		load_case($id, $data, _LOAD_ALL);
		break;
	case 'followup' :
		$data = load_followup($id, $data, _LOAD_ALL);
		break;
	case 'client' :
		$data = load_client($id, $data, _LOAD_ALL);
		break;
	case 'org' :
		$data = load_org($id, $data, _LOAD_ALL);
		break;
	default :
		lcm_panic("Incorrect export item type!");
		exit;
}

// Send proper headers to browser
header("Content-Type: text/xml");
header("Content-Disposition: filename={$item}_{$id}.xml");
header("Content-Description: " . "Export of {$item} ID{$id}");

echo '<?xml version="1.0"?>' . "\n";
echo xml_encode("{$item}_{$id}",$data);

?>