<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2005 Free Software Foundation, Inc.

	This program is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published by the
	Free Software Foundation; either version 2 of the License, or (at your
	option) any later version.

	This program is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
	or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
	for more details.

	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
    59 Temple Place, Suite 330, Boston, MA  02111-1307, USA

	$Id:
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_lang');

// Clean input variables
$case = intval($_POST['case']);
$ref_sel_auth = ($_POST['ref_sel_auth']);
foreach ($_POST['authors'] as $key => $value)
	$authors[$key] = $value;

if ($case>0) {
	if ($authors) {
		if (allowed($case,'a')) {
			foreach($authors as $author) {
				// Prepare query
				$q="INSERT INTO lcm_case_author
					SET id_case=$case,id_author=$author";

				// Do the query
				if (!($result = lcm_query($q))) die("$q<br>\n" . _T('title_error') . " " . lcm_errno() . ": " . lcm_error());

				// Get author information
				$q = "SELECT *
						FROM lcm_author
						WHERE id_author=$author";
				$result = lcm_query($q);
				$author_data = lcm_fetch_array($result);

				// Add 'assigned' followup to the case
				$q = "INSERT INTO lcm_followup
						SET id_followup=0,id_case=$case,type='assigned',description='";
				$q .= $author_data['name_first'];
				$q .= (($author_data['name_middle']) ? ' ' . $author_data['name_middle'] : '');
				$q .= (($author_data['name_last']) ? ' ' . $author_data['name_last'] : '');
				$q .= " assigned to the case',date_start=NOW()";
				$result = lcm_query($q);

				// Set case date_assigned to NOW()
				$q = "UPDATE lcm_case
						SET date_assignment=NOW()
						WHERE id_case=$case";
				$result = lcm_query($q);
			}
		} else die(_T('error_add_auth_no_rights'));
	}
} else die(_T('which_case'));

header("Location: $ref_sel_auth");

?>
