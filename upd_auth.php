<?php

include('inc/inc.php');

if (!empty($auth)) {
	foreach ($auth as $id=>$author) {
		// Prepare query
		$q = "UPDATE lcm_case_author
				SET lcm_case_author.read=";
		if (isset($author['read'])) $q .= "1";
		else $q .= "0";
		$q .= ", lcm_case_author.write=";
		if (isset($author['write'])) $q .= "1";
		else $q .= "0";
		$q .= ", lcm_case_author.admin=";
		if (isset($author['admin'])) $q .= "1";
		else $q .= "0";
		$q .= " WHERE (id_case=$case
				AND id_author=$id)";

		// Do the query
		$result = lcm_query($q);

	}
}

// Send user back to add/edit page's referer
header('Location: ' . $ref_edit_auth);
?>