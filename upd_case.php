<?php

include('inc/inc.php');
include('inc/inc_acc.php');

// Start session
session_start();

// Register $errors array - just in case
if (!session_is_registered("errors"))
    session_register("errors");

// Clear all previous errors
$errors=array();

// Register form data in the session
if(!session_is_registered("case_data"))
    session_register("case_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $case_data[$key]=$value;

// Check case data for validity
if (!$case_data['title']) $errors['title'] = _T('error_no_case_name');

if (count($errors)) {
    header("Location: $HTTP_REFERER");
    exit;
} else {
	//$cl='(id_case,title,id_court_archive,date_creation,date_assignment,legal_reason,alledged_crime,status)';
	//$vl="($id_case,'$title','$id_court_archive','$date_creation','$date_assignment','$legal_reason','$alledged_crime','$status')";
	$fl = "title='" . $case_data['title'] . "',
			id_court_archive='" . $case_data['id_court_archive'] . "',
			date_creation='" . $case_data['date_creation'] . "',
			date_assignment='" . $case_data['date_assignment'] . "',
			legal_reason='" . $case_data['legal_reason'] . "',
			alledged_crime='" . $case_data['alledged_crime'] . "',
			status='" . $case_data['status'] . "',";
	if ($public) $fl .= "public=1";
	else $fl .= "public=0";

	if ($id_case > 0) {
		// Check access rights
		if (!allowed($id_case,'e')) die("You don't have permission to change this case's information!");

		$q = "UPDATE lcm_case SET $fl WHERE id_case=$id_case";
	} else {
		$q = "INSERT INTO lcm_case SET id_case=0,$fl";
		$result = lcm_query($q);
		$id_case = lcm_insert_id();

		// Insert new case_author relation
		$q = "INSERT INTO lcm_case_author SET
				id_case=$id_case,
				id_author=$id_author,
				ac_read=1,
				ac_write=1,
				ac_admin=1";
	}

	// Some advanced ideas for future use
	//$q="INSERT INTO lcm_case SET id_case=$id_case,$fl ON DUPLICATE KEY UPDATE $fl";
	//$q="INSERT INTO lcm_case $cl VALUES $vl ON DUPLICATE KEY UPDATE $fl";

	$result = lcm_query($q);

    // Clear the session
    session_destroy();

	// [ML] I don't understand why: header("Location: $ref_edit_case");
	// [AG] Because "edit_case" could be invoked from diferent places i.e. edit existing case or add new or other.
	// [AG] User could come to edit from listcases.php or case_det.php. Also, other references could be added later.
	// [AG] In each case the return page will be different.

	//header("Location: case_det.php?case=$id_case");

	// Proceed accoring to the button type
	switch ($submit) {
		case 'addnew':
			header("Location: edit_case.php?case=0&ref=$ref_edit_case");
			break;
		case 'adddet':
			header("Location: case_det.php?case=$id_case");
			break;
		default :
			header("Location: $ref_edit_case");
	}
}
?>
