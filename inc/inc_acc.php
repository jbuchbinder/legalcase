<?php

//
// Execute this file only once
if (defined('_INC_ACC')) return;
define('_INC_ACC', '1');

function allowed($case,$access) {
	// By default, do not allow access
	$allow = false;

	// Check if the case number is present
	if ($case>0) {

		// Prepare query
		$q = "SELECT *
				FROM lcm_case_author
				WHERE (id_case=$case
					AND id_author=" . $GLOBALS['author_session']['id_author'] . ")";

		// Do the query
		$result = lcm_query($q);

		// Process the result, if any
		if ($row = lcm_fetch_array($result)) {

			// Set initial value to true, if $access parameter is set
			$allow = (bool) $access;

			// Walk each character in the required access rights list
			for($i=0 ; $i<strlen($access) ; $i++) {
				switch ($access{$i}) {
					case "r":
						$allow &= ($row['ac_read']);
						break;
					case "w":
						$allow &= ($row['ac_write']);
						break;
					case "e":
						$allow &= ($row['ac_edit']);
						break;
					case "a":
						$allow &= ($row['ac_admin']);
						break;
					default:
						// At any unknown character, disallow access
						$allow = 0;
				}
			}
		}
	}
	return $allow;
}

?>
