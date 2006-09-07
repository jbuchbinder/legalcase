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

	$Id: view_file.php,v 1.5 2006/09/07 19:53:49 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');

// Get request parameters
$file_id = intval($_REQUEST['file_id']);
$type = clean_input($_REQUEST['type']);

switch ($type) {
	//
	// View case attachment
	//
	case 'case' :
		$q = "SELECT ca.*, c.public
			FROM lcm_case_attachment as ca, lcm_case as c
			WHERE ca.id_case = c.id_case
				AND id_attachment = $file_id";
		$result = lcm_query($q);
		
		if (lcm_num_rows($result) == 0) die("There is no such file");
		
		$row = lcm_fetch_array($result);
		
		if (!(($GLOBALS['author_session']['status'] == 'admin') || $row['public'] || allowed($row['id_case'],'r'))) {
			die(_T('error_no_read_permission'));
		}
		break;
	//
	// View client attachment
	//
	case 'client' :
		$q = "SELECT *
			FROM lcm_client_attachment
			WHERE id_attachment=$file_id";
		$result = lcm_query($q);

		if (lcm_num_rows($result) == 0) die("There is no such file!");

		$row = lcm_fetch_array($result);

		break;
	//
	// View organisation attachment
	//
	case 'org' :
		$q = "SELECT *
			FROM lcm_org_attachment
			WHERE id_attachment=$file_id";
		$result = lcm_query($q);

		if (lcm_num_rows($result) == 0) die("There is no such file!");

		$row = lcm_fetch_array($result);

		break;
	default :
		die("What type of attachment?");
}

header("Content-Type: " . ($row['type'] ? $row['type'] : "application/octet-stream") );
header("Content-Disposition: filename=" . $row['filename']);
header("Content-Description: " . $row['description']);
header("Content-Transfer-Encoding: binary");
echo ( get_magic_quotes_runtime() ? stripslashes($row['content']) : $row['content'] );

?>
