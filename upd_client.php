<?php

include('inc/inc.php');

// Start session
session_start();

// Register $errors array - just in case
if (!session_is_registered("errors"))
    session_register("errors");

// Clear all previous errors
$errors=array();

// Register form data in the session
if(!session_is_registered("client_data"))
    session_register("client_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $client_data[$key]=$value;

// Check submitted information
if (strtotime($client_data['date_creation'])<0) { $errors['date_creation']='Invalid creation date!'; }
if (strtotime($client_data['date_update'])<0) { $errors['date_update']='Invalid update date!'; }

if (count($errors)) {
    header("Location: $HTTP_REFERER");
    exit;
} else {
	$cl="name_first='$name_first',name_middle='$name_middle',name_last='$name_last',";
    $cl.="date_creation='$date_creation',date_update='$date_update',citizen_number='$citizen_number',";
    $cl.="address='" . addslashes($address) . "',civil_status='$civil_status',income='$income'";

    if ($id_client>0) {
		// Prepare query
		$q="UPDATE lcm_client SET $cl WHERE id_client=$id_client";
    } else {
		$q="INSERT INTO lcm_client SET id_client=0,$cl";
    }

    // Do the query
    if (!($result = lcm_query($q))) die("$q<br>\nError ".lcm_errno().": ".lcm_error());
    //echo $q;

    // Clear the session
    session_destroy();

    // Send user back to add/edit page's referer
    header('Location: ' . $client_data['referer']);
}

?>
