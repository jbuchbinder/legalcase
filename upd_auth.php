<?php

include('inc/inc.php');

if (!empty($auth)) {
	foreach ($auth as $id=>$author) {
		// Prepare query
		$q = "UPDATE lcm_case_author
				SET ac_read=";
		if (isset($author['ac_read'])) $q .= "1";
		else $q .= "0";
		$q .= ", ac_write=";
		if (isset($author['ac_write'])) $q .= "1";
		else $q .= "0";
		$q .= ", ac_edit=";
		if (isset($author['ac_edit'])) $q .= "1";
		else $q .= "0";
		$q .= ", ac_admin=";
		if (isset($author['ac_admin'])) $q .= "1";
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
