<?php

include('inc/inc.php');

// Error display function
function f_err($fn, $errors)
{
    if (isset($errors[$fn]))
		echo "<font color='red'>$errors[$fn]</font><br>";
}

$fu_data=array();

// Initiate session
session_start();

if (empty($errors)) {
    // Clear form data
    $fu_data=array('referer'=>$HTTP_REFERER);

	if (isset($followup)) {
		// Register followup as session variable
	    if (!session_is_registered("followup"))
			session_register("followup");

		// Prepare query
		$q='SELECT * FROM lcm_followup WHERE id_followup=' . $followup;

		// Do the query
		$result = lcm_query($q);

		// Process the output of the query
		// [ML] XXX mysql_fetch_assoc is MySQL 4.x, can't we do without?
		// [AG] mysql_fetch_aray does the same and is available in PHP 3.
		// [AG] no requirement for MySQL version in PHP manual though.
		// [AG] TODO check if suplying MYSQL_ASSOC speeds the function up.
		if ($row = mysql_fetch_array($result)) {
			// Get followup details
			foreach($row as $key=>$value) {
				$fu_data[$key]=$value;
			}
		}
	} else {
		// Setup default values
		$fu_data['id_case'] = $case; // Link to the case
		$fu_data['date_start'] = date('Y-m-d H:i:s'); // '2004-09-16 16:32:37'
	}
}

$types=array("assignment","suspension","delay","conclusion","consultation","correspondance","travel","other");

// Edit followup details form

// lcm_page_start("Follow-up details");
lcm_page_start("Edit follow-up");

?>

<h1>Edit follow-up information:</h1>

<form action="upd_fu.php" method="POST">
<table><caption>Details of follow-up:</caption>
	<tr><th>Parameter</th><th>Value</th></tr>
	<tr><td>Start date:</td>
		<td><INPUT name="date_start" value="<?php echo $fu_data['date_start']; ?>">
			<?php echo f_err('date_start',$errors); ?></td></tr>
	<tr><td>End date:</td>
		<td><INPUT name="date_end" value="<?php echo $fu_data['date_end']; ?>">
		<?php echo f_err('date_end',$errors); ?></td></tr>
	<tr><td>Type:</td>
		<td><SELECT name="type" size="1"><OPTION selected><?php echo $fu_data['type']; ?></OPTION>
		<?php
		foreach($types as $item) {
			if ($item != $fu_data['type']) {
			echo "<OPTION>$item</OPTION>\n";
			}
		} ?>
		</SELECT></td></tr>
	<tr><td>Description:</td>
		<td><textarea name="description" rows="5" cols="30">
		<?php echo $fu_data['description']; ?></textarea></td></tr>
	<tr><td>Sum billed:</td>
		<td><input name="sumbilled" value="<?php echo $fu_data['sumbilled']; ?>"></td></tr>
</table>
<BUTTON name="submit" type="submit" value="submit">Save</BUTTON>
<BUTTON name="reset" type="reset">Reset</BUTTON>
<INPUT type="hidden" name="id_followup" value="<?php echo $fu_data['id_followup']; ?>">
<INPUT type="hidden" name="id_case" value="<?php echo $fu_data['id_case']; ?>">
<INPUT type="hidden" name="referer" value="<?php echo $fu_data['referer']; ?>">
</form>

<?php
	lcm_page_end();
?>
