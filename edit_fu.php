<?php

// Error display function
function f_err($fn, $errors)
{
    if (isset($errors[$fn]))
		echo "<font color=RED>$errors[$fn]</font><br>";
}

// Initiate session
session_start();

if (empty($errors)) {
    // Clear form data
    $fu_data=array('referer'=>$HTTP_REFERER);

	if (isset($followup)) {
		// Register followup as session variable
	    if (!session_is_registered("followup"))
			session_register("followup");

		// Connect to the database
		$db=mysql_connect('localhost','lcm','lcmpass');

		// Select lcm database
		mysql_select_db('lcm',$db);

		// Prepare query
		$q='SELECT * FROM lcm_followup WHERE id_followup=' . $followup;

		// Do the query
		$result=mysql_query($q,$db);

		// Process the output of the query
		if ($row = mysql_fetch_assoc($result)) {
			// Get followup details
			foreach($row as $key=>$value) {
				$fu_data[$key]=$value;
			}
		}

		// Close connection
		mysql_close($db);
	} else {
		// Setup default values
		$fu_data['id_case']=$case; // Link to the case
		$fu_data['date_start']=date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
	}
}

$types=array("assignment","suspension","delay","conclusion","consultation","correspondance","travel","other");

// Edit followup details form
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>Follow-up details</title>
</head>
<body>

<h1>Edit follow-up information:</h1>

<form action="upd_fu.php" method="POST">
<table><caption>Details of follow-up:</caption>
<tr><th>Parameter</th><th>Value</th></tr>
<INPUT type="hidden" name="id_followup" value="<?php echo $fu_data['id_followup']; ?>">
<INPUT type="hidden" name="id_case" value="<?php echo $fu_data['id_case']; ?>">
<tr><td>Start date:</td><td><INPUT name="date_start" value="<?php echo $fu_data['date_start']; ?>">
<?php echo f_err('date_start',$errors); ?></td></tr>
<tr><td>End date:</td><td><INPUT name="date_end" value="<?php echo $fu_data['date_end']; ?>"></td></tr>
<tr><td>Type:</td><td><SELECT name="type" size="1"><OPTION selected><?php echo $fu_data['type']; ?></OPTION>
<?php
foreach($types as $item) {
    if ($item != $fu_data['type']) {
	echo "<OPTION>$item</OPTION>\n";
    }
}
?>
</SELECT></td></tr>
<tr><td>Description:</td><td><textarea name="description" rows="5" cols="30">
<?php echo $fu_data['description']; ?></textarea></td></tr>
<tr><td>Sum billed:</td><td><input name="sumbilled" value="<?php echo $fu_data['sumbilled']; ?>"></td></tr>
</table>
<BUTTON name="submit" type="submit" value="submit">Save</BUTTON>
<BUTTON name="reset" type="reset">Reset</BUTTON>
<INPUT type="hidden" name="referer" value="<?php echo $fu_data['referer']; ?>">
</form>

</body>
</html>
