<?php

include('inc/inc.php');

// Error display function
function f_err($fn, $errors)
{
    if (isset($errors[$fn]))
		echo "<font color='red'>$errors[$fn]</font><br>";
}

// Create empty org data
$org_data=array();

// Initiate session
session_start();

if (empty($errors)) {
    // Clear form data
    $org_data=array('referer'=>$HTTP_REFERER);

	if (isset($org)) {
		// Register org as session variable
	    if (!session_is_registered("org"))
			session_register("org");

		// Prepare query
		$q='SELECT * FROM lcm_org WHERE id_org=' . $org;

		// Do the query
		$result = lcm_query($q);

		// Process the output of the query
		if ($row = mysql_fetch_array($result)) {
			// Get org details
			foreach($row as $key=>$value) {
				$org_data[$key]=$value;
			}
		}
	} else {
		// Setup default values
		$org_data['date_creation'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
	}
}

lcm_page_start("Edit organisation details");
?>

<h1>Edit organisation information:</h1>
<form action="upd_org.php" method="POST">
<table>
<caption><h3>Organisation details</h3></caption>
<tr><th>Parameter</th><th>Value</th></tr>
<tr><td>Organisation ID:</td><td><?php echo $org_data['id_org']; ?>
<input type="hidden" name="id_org" value="<?php echo $org_data['id_org']; ?>"></td></tr>
<tr><td>Name:</td><td><textarea name="name" rows="1"><?php echo $org_data['name']; ?></textarea></td></tr>
<tr><td>Created on:</td><td><input name="date_creation" value="<?php echo $org_data['date_creation']; ?>">
<?php echo f_err('date_creation',$errors); ?></td></tr>
<tr><td>Updated on:</td><td><input name="date_update" value="<?php echo $org_data['date_update']; ?>">
<?php echo f_err('date_update',$errors); ?></td></tr>
<tr><td>Address:</td><td><textarea name="address"><?php echo $org_data['address']; ?></textarea></td></tr>
</table>
<button name="submit" type="submit" value="submit">Save</button>
<button name="reset" type="reset">Reset</button>
<input type="hidden" name="referer" value="<?php echo $HTTP_REFERER ?>">
</form>

<?php
	lcm_page_end();
?>
