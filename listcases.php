<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>List of cases</title>
</head>
<body>

<?php

// Set connection parameters

// Connect to the database
$db=mysql_connect('localhost','lcm','lcmpass');

// Select lcm database
mysql_select_db('lcm',$db);

// Prepare query
$q='SELECT id_case,title FROM lcm_case';

// TODO - add case filter based on user/case status to query

// Do the query
$result=mysql_query($q,$db);

?>
<h1>List of cases</h1>
<table>
<tr><th>Case description</th></tr>
<tr><td>
<?php
// Process the output of the query
while ($row = mysql_fetch_assoc($result)) {
	// Show case title
	echo '<tr><td><a href="case_det.php?case=' . $row['id_case'] . '">'. $row['title'] . '</a><td><tr>';
	echo "\n";
}

// Close connection
mysql_close($db);
?>
</table>
</body>
</html>
