<?php

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