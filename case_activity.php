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

	$Id: case_activity.php,v 1.4 2006/02/20 03:10:28 mlutfy Exp $
*/

include("inc/inc.php");
include_lcm("inc_filters");

lcm_html_start('Case activities'); // TRAD

echo "<div align='left'>\n";

// Read parameters
$case = intval($_GET['case']);

if ($case > 0) {
	$q="SELECT id_case, title, date_creation, date_assignment,
			legal_reason, alledged_crime, status, stage, public, pub_write
		FROM lcm_case
		WHERE id_case=$case";

	$result = lcm_query($q);

	// Process the output of the query
	if ($row = lcm_fetch_array($result)) {
		echo "List of activities\n"; // TRAD
		echo "for case '" . $row['title'] . "'\n";  // TRAD

		// Some case information could be printed here

		// Print table with activities
		echo "<table border=\"1\" width=\"99%\">\n";
		echo "\t<tr><th>" . _Th('fu_input_date_start') . "</th>";
		echo "<th>" . _Th( (($prefs['time_intervals'] == 'absolute') ? 'date_end' : 'time_length') ) . "</th>"; // TRAD
		echo "<th>" . _Th('case_input_author') . "</th>";
		echo "<th>" . _Th('fu_input_type') . "</th>";
		echo "<th>" . _Th('fu_input_description') . "</th>";
		echo "</tr>\n";

		$q = "SELECT	lcm_followup.id_followup,
				lcm_followup.date_start,
				lcm_followup.date_end,
				lcm_followup.type,
				lcm_followup.description,
				lcm_author.name_first,
				lcm_author.name_middle,
				lcm_author.name_last
			FROM lcm_followup, lcm_author
			WHERE id_case=$case AND lcm_followup.id_author=lcm_author.id_author";

		// Add ordering
//		if ($fu_order) $q .= " ORDER BY date_start $fu_order, id_followup $fu_order";

		// Do the query
		$result = lcm_query($q);

		// Process the output of the query
		while ($row = lcm_fetch_array($result)) {
			echo "\t";

			// Start date
			echo '<tr><td>' . format_date($row['date_start'], 'short') . '</td>';

			// Time
			echo '<td>';
			$fu_date_end = vider_date($row['date_end']);
			if ($prefs['time_intervals'] == 'absolute') {
				if ($fu_date_end) echo format_date($row['date_end'],'short');
			} else {
				$fu_time = ($fu_date_end ? strtotime($row['date_end']) - strtotime($row['date_start']) : 0);
				echo format_time_interval($fu_time,($prefs['time_intervals_notation'] == 'hours_only'));
			}
			echo '</td>';

			// Author name
			echo '<td>';
			echo get_person_name($row);
			echo '</td>';

			// Type
			echo '<td>' . _T('kw_followups_' . $row['type'] . '_title') . '</td>';

			// Description
			echo '<td>' . nl2br(clean_output($row['description'])) . '</td>';

			echo "</tr>\n";
		}

		echo "</table>\n";
	} else die(_T('error_no_such_case'));

} else {
	echo "<p>" . _T('error_no_case_specified') . "</p>\n";
}

echo "</div>\n"; // align
lcm_html_end();

?>
