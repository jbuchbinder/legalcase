<?php

// Start session
session_start();

// Register $errors array - just in case
if (!session_is_registered("errors"))
    session_register("errors");

// Clear all previous errors
$errors=array();

// Register form data in the session
if(!session_is_registered("fu_data"))
    session_register("fu_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $fu_data[$key]=$value;

// Check submitted information
if (strtotime($fu_data['date_start'])<0) { $errors['date_start']='Invalid start date!'; }

if (count($errors)) {
    header("Location: $HTTP_REFERER");
    exit;
} else {
    // Connect to the database
    $db=mysql_connect('localhost','lcm','lcmpass');

    // Select lcm database
    mysql_select_db('lcm',$db);

    $fl="date_start=\"$date_start\",date_end='$date_end',type='$type',description='$description'";
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

    // Clear the session
    session_destroy();

    // Send user back to add/edit page's referer
    header("Location: $fu_data['referer']");
}
?>
