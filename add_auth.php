<?php

include('inc/inc.php');
include('inc/inc_acc.php');
include_lcm('inc_lang');

if ($case>0) {
	if ($authors) {
		if (allowed($case,'a')) {
			foreach($authors as $author) {
				// Prepare query
				$q="INSERT INTO lcm_case_author
					SET id_case=$case,id_author=$author";

				// Do the query
				if (!($result = lcm_query($q))) die("$q<br>\n" .  _T('title_error') . " " . lcm_errno() . ": " . lcm_error());
			}
		} else die(_T('error_add_auth_no_rights'));
	}
} else die(_T('which_case'));

header("Location: $ref_sel_auth");

?>
