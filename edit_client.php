<?php

include('inc/inc.php');

// Error display function
function f_err($fn, $errors)
{
    if (isset($errors[$fn]))
		echo "<font color='red'>$errors[$fn]</font><br>";
}

// Create empty client data
$client_data = array();

// Initiate session
session_start();

if (empty($errors)) {
    // Clear form data
    $client_data=array('referer'=>$HTTP_REFERER);

	if (isset($client)) {
		// Register client as session variable
	    if (!session_is_registered("client"))
			session_register("client");

		// Prepare query
		$q = 'SELECT * FROM lcm_client WHERE id_client=' . $client;

		// Do the query
		$result = lcm_query($q);

		// Process the output of the query
		if ($row = lcm_fetch_array($result)) {
			// Get client details
			foreach($row as $key=>$value) {
				$client_data[$key] = $value;
			}
		}
	} else {
		// Setup default values
		$client_data['date_creation'] = date('Y-m-d H:i:s'); // now
		$client_data['date_update'] = date('Y-m-d H:i:s'); // now
	}
}

lcm_page_start("Edit client details");
?>

<h1>Edit client information:</h1>
<form action="upd_client.php" method="POST">
	<table><caption>Client details</caption>
		<tr><th>Parameter</th><th>Value</th></tr>
		<tr><td>Client ID:</td>
			<td><?php echo $client_data['id_client']; ?>
			<input type="hidden" name="id_client" value="<?php echo $client_data['id_client']; ?>"></td></tr>
		<tr><td>First name:</td>
			<td><input name="name_first" value="<?php echo $client_data['name_first']; ?>"></td></tr>
		<tr><td>Middle name:</td>
			<td><input name="name_middle" value="<?php echo $client_data['name_middle']; ?>"></td></tr>
		<tr><td>Last name:</td>
			<td><input name="name_last" value="<?php echo $client_data['name_last']; ?>"></td></tr>
		<tr><td>Created on:</td>
			<td><input name="date_creation" value="<?php echo $client_data['date_creation']; ?>">
			<?php echo f_err('date_creation',$errors); ?></td></tr>
		<tr><td>Updated on:</td>
			<td><input name="date_update" value="<?php echo $client_data['date_update']; ?>">
			<?php echo f_err('date_update',$errors); ?></td></tr>
		<tr><td>Citizen number:</td>
			<td><input name="citizen_number" value="<?php echo $client_data['citizen_number']; ?>"></td></tr>
		<tr><td>Address:</td>
			<td><textarea name="address" rows=3><?php echo htmlspecialchars($client_data['address']); ?></textarea></td></tr>
		<tr><td>Civil status:</td>
			<td><input name="civil_status" value="<?php echo $client_data['civil_status']; ?>"></td></tr>
		<tr><td>Income:</td>
			<td><input name="income" value="<?php echo $client_data['income']; ?>"></td></tr>
	</table>
	<button name="submit" type="submit" value="submit">Save</button>
	<button name="reset" type="reset">Reset</button>
	<input type="hidden" name="ref_edit_client" value="<?php echo $HTTP_REFERER ?>">
</form>

<?php
	lcm_page_end();
?>
