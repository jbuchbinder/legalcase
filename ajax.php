<?php

if (! $_COOKIE['lcm_session']) {
	echo "nologin";
	return;
}

include('inc/inc.php');

header('Content-Type: text/xml');
echo '<?xml version="1.0"?>';

echo "<body>";

if (isset($_REQUEST['find_name_client']) && $_REQUEST['find_name_client']) {
	include_lcm('inc_obj_client');
	echo "<div id=\"autocomplete-client-popup\">";

	$cpt = 0;
	$search = clean_input($_REQUEST['find_name_client']);

	// $search = 

	$query = "SELECT *
				FROM lcm_client
				WHERE name_last LIKE '%$search%'
					OR name_first LIKE '%$search%'
					OR CONCAT(name_first, ' ', IF (name_middle != '', CONCAT(name_middle, ' '), ''), name_last) LIKE '%$search%'";

	$result = lcm_query($query);

	echo "<ul>";

	while (($row = lcm_fetch_array($result))) {
		echo "<li>" . $row['id_client'] . ": " . get_person_name($row) . "</li>\n";
		$cpt++;
	}

	if (! $cpt)
		echo "<li>0: No results</li>"; // TRAD

	echo "</ul>\n";
	echo "</div>\n";
} elseif (isset($_REQUEST['find_name_case']) && $_REQUEST['find_name_case']) {
	include_lcm('inc_obj_case');
	echo "<div id=\"autocomplete-case-popup\">";

	$cpt = 0;
	$search = clean_input($_REQUEST['find_name_case']);

	// $search = 

	// TODO: also search keywords
	$query = "SELECT *
				FROM lcm_case
				WHERE title LIKE '%$search%'";

	$result = lcm_query($query);

	echo "<ul>";

	while (($row = lcm_fetch_array($result))) {
		echo "<li>" . $row['id_case'] . ": " . $row['title'] . "</li>\n";
		$cpt++;
	}

	if (! $cpt)
		echo "<li>0: No results</li>"; // TRAD

	echo "</ul>\n";
	echo "</div>\n";
} elseif (isset($_REQUEST['id_client']) && (intval($_REQUEST['id_client']) > 0)) {
	include_lcm('inc_obj_client');
	$client = new LcmClientInfoUI(intval($_REQUEST['id_client']));
	$client->printGeneral(false);
	$client->printCases();
	$client->printAttach();
} elseif (isset($_REQUEST['id_case']) && (intval($_REQUEST['id_case']) > 0)) {
	include_lcm('inc_obj_case');
	echo '<div id="case_data">';

	// Must remove &nbsp; otherwise requestXML cannot parse (?!)
	ob_start();

	$case = new LcmCaseInfoUI(intval($_REQUEST['id_case']));
	$case->printGeneral(false, false);
	$case->printFollowups();

	$foo = ob_get_contents();
	ob_end_clean();

	echo preg_replace("/\&nbsp;/", " ", $foo);

	echo "</div>\n";
} else {
	echo "Unknown action.";
}

echo "</body>\n";

?>
