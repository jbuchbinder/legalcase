<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>Case details</title>
</head>
<body>

<?php

// Set connection parameters

// Connect to the database
$db=mysql_connect('localhost','lcm','lcmpass');

// Select lcm database
mysql_select_db('lcm',$db);

$fu_data=array();

if (isset($followup)) {
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
} else {
    // Link to the case
    $fu_data['id_case']=$case;
    //$fu_data['date_start']='2004-09-16 16:32:37';
}

$types=array("assignment","suspension","delay","conclusion","consultation","correspondance","travel","other");

// Edit followup details form
?>
<h1>Edit follow-up information:</h1>

<form action="upd_fu.php" method="POST">
<table><caption>Details of follow-up:</caption>
<tr><th>Parameter</th><th>Value</th></tr>
<INPUT type="hidden" name="id_followup" value="<?php echo $fu_data['id_followup']; ?>">
<INPUT type="hidden" name="id_case" value="<?php echo $fu_data['id_case']; ?>">
<tr><td>Start date:</td><td><INPUT name="date_start" value="<?php echo $fu_data['date_start']; ?>"></td></tr>
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
<INPUT type="hidden" name="referer" value="<?php echo $HTTP_REFERER ?>">
</form>

<?php
//} else die("There's no such followup!");

// Close connection
mysql_close($db);
?>
</body>
</html>
