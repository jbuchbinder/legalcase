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

	$Id: attach_file.php,v 1.8 2005/03/23 20:55:04 antzi Exp $
*/

include("inc/inc.php");
include_lcm('inc_filters');

// Get POST values
$case = intval($_POST['case']);
$client = intval($_POST['client']);
$org = intval($_POST['org']);

if (isset($_FILES['filename'])) {
//	print_r($_FILES['filename']);
	$_SESSION['user_file'] = $_FILES['filename'];
	$_SESSION['user_file']['description'] = clean_input($_POST['description']);
	$filename = $_SESSION['user_file']['tmp_name'];
//	echo $filename;
	if (is_uploaded_file($filename) && $_SESSION['user_file']['size'] > 0) {
		$file = fopen($filename,"r");
		$file_contents = fread($file, filesize($filename));
		$file_contents = addslashes($file_contents);

		if ($case > 0) {
			$q = "INSERT INTO lcm_case_attachment
				SET	id_case=$case,
					filename='" . $_SESSION['user_file']['name'] . "',
					type='" . $_SESSION['user_file']['type'] . "',
					size=" . $_SESSION['user_file']['size'] . ",
					description='" . $_SESSION['user_file']['description'] . "',
					content='$file_contents',
					date_attached=NOW()
				";
		} else if ($client > 0) {
			$q = "INSERT INTO lcm_client_attachment
				SET	id_client=$client,
					filename='" . $_SESSION['user_file']['name'] . "',
					type='" . $_SESSION['user_file']['type'] . "',
					size=" . $_SESSION['user_file']['size'] . ",
					description='" . $_SESSION['user_file']['description'] . "',
					content='$file_contents',
					date_attached=NOW()
				";
		} else if ($org > 0) {
			$q = "INSERT INTO lcm_org_attachment
				SET	id_org=$org,
					filename='" . $_SESSION['user_file']['name'] . "',
					type='" . $_SESSION['user_file']['type'] . "',
					size=" . $_SESSION['user_file']['size'] . ",
					description='" . $_SESSION['user_file']['description'] . "',
					content='$file_contents',
					date_attached=NOW()
				";
		} else die("Attach file to what?");

		$result = lcm_query($q);

		$user_file = array();

	} else {
		// Handle errors
		if ($_SESSION['user_file']['error'] > 0) {
			$cause = array(	UPLOAD_ERR_OK		=> 'The file was uploaded successfully!',
					UPLOAD_ERR_INI_SIZE	=> 'The file size exceeds the "upload_max_filesize" directive in php.ini.',
					UPLOAD_ERR_FORM_SIZE	=> 'The file size exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
					UPLOAD_ERR_PARTIAL	=> 'The file was uploaded only partially.',
					UPLOAD_ERR_NO_FILE	=> 'No file was uploaded!',
					UPLOAD_ERR_NO_TMP_DIR	=> 'Missing a temporary folder.');
			$_SESSION['errors'] = array('file' => $cause[$_SESSION['user_file']['error']]);
		} else {
			$_SESSION['errors'] = array('file' => 'Empty file or access denied!');
		}
		// . ' uploading file ' . $_SESSION['user_file']['name'] . '!');
	}
}

header("Location: " . $_SERVER['HTTP_REFERER']);

?>