<?php

include('inc/inc.php');
include_lcm('inc_lang');

// Clean the POST values
$case = intval($_POST['case']);
foreach ($_POST['clients'] as $key=>$value) $clients[$key] = intval($value);

if (($case>0) && ($clients)) {
	foreach($clients as $client) {
		// Prepare query
		$q="INSERT INTO lcm_case_client_org
			SET id_case=$case,id_client=$client";

		// Do the query
		if (!($result = lcm_query($q))) die("$q<br>\n" . _T('title_error') . " " . lcm_errno() . ": " . lcm_error());
	}
}

//header("Location: $ref_sel_client");
header("Location: case_det.php?case=$case");

?>
