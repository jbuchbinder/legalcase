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

// Prepare query
$q='SELECT * FROM lcm_followup WHERE id_followup=' . $followup;

// Do the query
$result=mysql_query($q,$db);

$types=array("assignment","suspension","delay","conclusion","consultation","correspondance","travel","other");
// Process the output of the query
if ($row = mysql_fetch_assoc($result)) {
	// Edit followup details
	?><form><table><caption>Details of follow-up:</caption><?php
	echo '<tr><td>Start date:' . '</td><td><INPUT value="' . $row['date_start'] . "\"></td><tr>\n";
	echo '<tr><td>End date:' . '</td><td><INPUT value="' . $row['date_end'] . "\"></td><tr>\n";
	echo '<tr><td>Type:' . '</td><td><SELECT size="1"><OPTION selected>' . $row['type'] . "</OPTION>\n";
	foreach($types as $item) {
	   if ($item != $row['type'])
	      { echo '<OPTION>' . $item . "</OPTION>\n"; }
	}
	echo "</SELECT></td><tr>\n";
	echo '<tr><td>Description:' . '</td><td><textarea rows="5" cols="30">';
	echo $row['description'] . "</textarea></td><tr>\n";
	echo '<tr><td>Sum billed:' . '</td><td><input value="' . $row['sumbilled'] . "\"></td><tr>\n";
	?></table></form><?php
} else die("There's no such followup!");

// Close connection
mysql_close($db);
?>
</body>
</html>
