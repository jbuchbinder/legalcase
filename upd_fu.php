<?php
// Connect to the database
$db=mysql_connect('localhost','lcm','lcmpass');

// Select lcm database
mysql_select_db('lcm',$db);

$fl="date_start='$date_start',date_end='$date_end',type='$type',description='$description'";
$fl.=",sumbilled='$sumbilled'";

if ($id_followup>0) {
   // Prepare query
   $q="UPDATE lcm_followup SET $fl WHERE id_followup=$id_followup";
} else {
   $q="INSERT INTO lcm_followup SET id_followup=0,id_case=$id_case,$fl";
}

// Do the query
if (!($result=mysql_query($q,$db))) die("$q<br>\nError ".mysql_errno().": ".mysql_error());
//echo $q;

// Close connection
mysql_close($db);

header("Location: $referer");
?>
