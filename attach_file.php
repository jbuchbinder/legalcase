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

	$Id: attach_file.php,v 1.2 2005/02/21 14:16:58 antzi Exp $
*/

include("inc/inc.php");

// Get POST values
$case = intval($_POST['case']);
$description = clean_input($_POST['description']);

if (isset($_FILES['filename'])) {
//	print_r($_FILES['filename']);
	$user_file = $_FILES['filename'];
	$filename = $user_file['tmp_name'];
//	echo $filename;
	if (is_uploaded_file($filename)) {
		$file = fopen($filename,"r");
		$file_contents = fread($file, filesize($filename));
		$file_contents = addslashes($file_contents);
	
		$q = "INSERT INTO lcm_case_attachment
			SET	id_case=$case,
				filename='" . $user_file['name'] . "',
				type='" . $user_file['type'] . "',
				size=" . $user_file['size'] . ",
				description='$description',
				content='$file_contents',
				date_attached=NOW()
			";
		$result = lcm_query($q);

	} else die('Error #' . $user_file['error'] . ' uploading file ' . $user_file['name'] . '!');
}

header("Location: " . $_SERVER['HTTP_REFERER']);

?>