<?php

include('inc/inc.php');

// Clean the POST values
$org = intval($_POST['org']);
foreach ($_POST['clients'] as $key=>$value) $clients[$key] = intval($value);

if (($org>0) && ($clients)) {
	foreach($clients as $client) {
		// Prepare query
		$q="INSERT INTO lcm_client_org
			SET id_org=$org,id_client=$client";

		// Do the query
		if (!($result = lcm_query($q))) die("$q<br>\n" . _T('title_error') . " " . lcm_errno() . ": " . lcm_error());
	}
}

//header("Location: $ref_sel_cli_org");
header("Location: org_det.php?org=$org");

?>
