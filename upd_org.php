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
if(!session_is_registered("org_data"))
    session_register("org_data");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $org_data[$key]=$value;

// Check submitted information
if (strtotime($org_data['date_creation'])<0) { $errors['date_creation']='Invalid creation date!'; }
if (strtotime($org_data['date_update'])<0) { $errors['date_update']='Invalid update date!'; }

if (count($errors)) {
    header("Location: $HTTP_REFERER");
    exit;
} else {
	$ol="name='" . addslashes($name) . "',date_creation='$date_creation',date_update='$date_update',";
    $ol.="address='" . addslashes($address) . "'";

    if ($id_org>0) {
		// Prepare query
		$q="UPDATE lcm_org SET $ol WHERE id_org=$id_org";
    } else {
		$q="INSERT INTO lcm_org SET id_org=0,$ol";
    }

    // Do the query
    if (!($result = lcm_query($q))) die("$q<br>\nError ".lcm_errno().": ".lcm_error());
    //echo $q;

    // Clear the session
    session_destroy();

    // Send user back to add/edit page's referer
    header('Location: ' . $org_data['referer']);
}

?>
