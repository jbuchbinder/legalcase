<?php

include('inc/inc.php');

//$cl='(id_case,title,id_court_archive,date_creation,date_assignment,legal_reason,alledged_crime,status)';
//$vl="($id_case,'$title','$id_court_archive','$date_creation','$date_assignment','$legal_reason','$alledged_crime','$status')";
$fl = "title='$title',
		id_court_archive='$id_court_archive',
		date_creation='$date_creation',
		date_assignment='$date_assignment',
		legal_reason='$legal_reason',
		alledged_crime='$alledged_crime',
		status='$status',
		public='$public'";

if ($id_case>0) {
	// Update the existing case
	$q = "UPDATE lcm_case SET $fl WHERE id_case=$id_case";
} else {
	// Insert new case row
	$q = "INSERT INTO lcm_case SET id_case=0,$fl";
	$result = mysql_query($q);
	$id_case = mysql_insert_id();

	// Insert new case_author relation
	$q = "INSERT INTO lcm_case_author SET
			id_case=$id_case,
			id_author=$id_author,
			read=1,
			write=1";
}

// Some advanced ideas for future use
//$q="INSERT INTO lcm_case SET id_case=$id_case,$fl ON DUPLICATE KEY UPDATE $fl";
//$q="INSERT INTO lcm_case $cl VALUES $vl ON DUPLICATE KEY UPDATE $fl";


// Do the query
$result=mysql_query($q);

// Close connection
// mysql_close($db);

header("Location: $ref_edit_case");

?>
