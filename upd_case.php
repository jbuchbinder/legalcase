<?php

include('inc/inc.php');

//$cl='(id_case,title,id_court_archive,date_creation,date_assignment,legal_reason,alledged_crime,status)';
//$vl="($id_case,'$title','$id_court_archive','$date_creation','$date_assignment','$legal_reason','$alledged_crime','$status')";
$fl="title='$title',id_court_archive='$id_court_archive',date_creation='$date_creation'";
$fl.=",date_assignment='$date_assignment',legal_reason='$legal_reason',alledged_crime='$alledged_crime'";
$fl.=",status='$status'";

if ($id_case>0) {
   // Prepare query
   $q="UPDATE lcm_case SET $fl WHERE id_case=$id_case";
} else {
   $q="INSERT INTO lcm_case SET id_case=0,$fl";
   //$q="INSERT INTO lcm_case SET id_case=$id_case,$fl ON DUPLICATE KEY UPDATE $fl";
   //$q="INSERT INTO lcm_case $cl VALUES $vl ON DUPLICATE KEY UPDATE $fl";
}

// Do the query
$result=mysql_query($q);

// Close connection
// mysql_close($db);

header("Location: $referer");

?>
