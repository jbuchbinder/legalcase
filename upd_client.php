<?php

include('inc/inc.php');
include_lcm('inc_filters');

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
    $client_data[$key] = $value;

// Check submitted information
if (strtotime($client_data['date_creation']) < 0) { $errors['date_creation'] = 'Invalid creation date!'; }
//if (strtotime($client_data['date_update']) < 0) { $errors['date_update'] = 'Invalid update date!'; }

// Add timestamp
$client_data['date_update'] = date('Y-m-d H:i:s'); // now

if (count($errors)) {
    header("Location: $HTTP_REFERER");
    exit;
} else {
	$cl = "name_first='" . clean_input($client_data['name_first']) . "',
		name_middle='" . clean_input($client_data['name_middle']) . "',
		name_last='" . clean_input($client_data['name_last']) . "',
		date_creation='" . clean_input($client_data['date_creation']) . "',
		date_update='" . clean_input($client_data['date_update']) . "',
		citizen_number='" . clean_input($client_data['citizen_number']) . "',
		address='" . clean_input($client_data['address']) . "',
		civil_status='" . clean_input($client_data['civil_status']) . "',
		income='" . clean_input($client_data['income']) . "'";

    if ($id_client>0) {
		// Prepare query
		$q = "UPDATE lcm_client SET $cl WHERE id_client=$id_client";
    } else {
		$q = "INSERT INTO lcm_client SET id_client=0,$cl";
    }

    // Do the query
    if (!($result = lcm_query($q))) die("$q<br>\nError ".lcm_errno().": ".lcm_error());
    //echo $q;

    // Clear the session
    session_destroy();

    // Send user back to add/edit page's referer
    header('Location: ' . $client_data['ref_edit_client']);
}

?>
