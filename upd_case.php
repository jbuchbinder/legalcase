<?php

include('inc/inc.php');
include('inc/inc_acc.php');

//$cl='(id_case,title,id_court_archive,date_creation,date_assignment,legal_reason,alledged_crime,status)';
//$vl="($id_case,'$title','$id_court_archive','$date_creation','$date_assignment','$legal_reason','$alledged_crime','$status')";
$fl = "title='$title',
		id_court_archive='$id_court_archive',
		date_creation='$date_creation',
		date_assignment='$date_assignment',
		legal_reason='$legal_reason',
		alledged_crime='$alledged_crime',
		status='$status',";
if ($public) $fl .= "public=1";
else $fl .= "public=0";

if ($id_case>0) {
	// Check access rights
	if (!allowed($id_case,'e')) die("You don't have permission to change this case's information!");

	// Update the existing case
	$q = "UPDATE lcm_case SET $fl WHERE id_case=$id_case";
} else {
	// Insert new case row
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


// Do the query
$result=lcm_query($q);

// Close connection
// mysql_close($db);

header("Location: $ref_edit_case");

?>
