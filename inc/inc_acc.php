<?php

//
// Execute this file only once
if (defined('_INC_ACC')) return;
define('_INC_ACC', '1');

function allowed($case,$access) {
	$q = "SELECT *
			FROM lcm_case_author
			WHERE (id_case=$case
				AND id_author=" . $GLOBALS['connect_id_auteur'] . ")";

	$result = lcm_query($q);

	if ($row = lcm_fetch_array($result)) {
		$allow = (bool) $access;
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
					$allow = 0;
			}
		}
	}
	return $allow;
}

?>
