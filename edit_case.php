<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>Edit case details</title>
</head>
<body>

<?php

// Create empty case data
$case_data=array();

if ($case>0) {
   // Connect to the database
   $db=mysql_connect('localhost','lcm','lcmpass');

   // Select lcm database
   mysql_select_db('lcm',$db);

   // Prepare query
   $q='SELECT * FROM lcm_case WHERE id_case=' . $case;

   // Do the query
   $result=mysql_query($q,$db);

   // Process the output of the query
   if ($row = mysql_fetch_assoc($result)) {
	// Get case details
	foreach ($row as $key => $value) {
	   $case_data[$key] = $value;
	}
   }

   // Close connection
   mysql_close($db);
}

?><h1>Edit case information:</h1>
<form action="upd_case.php" method="POST">
<table>
<caption>Case details</caption>
<tr><th>Parameter</th><th>Value</th></tr>
<tr><td>Case ID:</td><td><?php echo $case_data['id_case']; ?>
<INPUT type="hidden" name="id_case" value="<?php echo $case_data['id_case']; ?>"></td></tr>
<tr><td>Case title:</td><td><input name="title" value="<?php echo $case_data['title']; ?>"></td></tr>
<tr><td>Court archive:</td><td><input name="id_court_archive" value="<?php echo $case_data['id_court_archive']; ?>"></td></tr>
<tr><td>Date created:</td><td><input name="date_creation" value="<?php echo $case_data['date_creation']; ?>"></td></tr>
<tr><td>Date assigned:</td><td><input name="date_assignment" value="<?php echo $case_data['date_assignment']; ?>"></td></tr>
<tr><td>Legal reason:</td><td><input name="legal_reason" value="<?php echo $case_data['legal_reason']; ?>"></td></tr>
<tr><td>Alledged crime:</td><td><input name="alledged_crime" value="<?php echo $case_data['alledged_crime']; ?>"></td></tr>
<tr><td>Case status:</td><td><input name="status" value="<?php echo $case_data['status']; ?>"></td></tr>
</table>
<BUTTON name="submit" type="submit" value="submit">Save</BUTTON>
<BUTTON name="reset" type="reset">Reset</BUTTON>
<INPUT type="hidden" name="referer" value="<?php echo $HTTP_REFERER ?>">
</form>
</body>
</html>
