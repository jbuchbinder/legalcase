<?php

include('inc/inc.php');
include('inc/inc_acc.php');

if ($case>0) {
	if (!empty($auth)) {
		if (allowed($case,'a')) {
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
		} else die("You don't have permission to edit this case's access rights!");
	}
} else die("Which case?");

// Send user back to add/edit page's referer
header('Location: ' . $ref_edit_auth);
?>
