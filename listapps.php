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

	$Id: listapps.php,v 1.1 2005/02/22 09:46:32 antzi Exp $
*/

include('inc/inc.php');

lcm_page_start('Agenda');

$q = "SELECT lcm_app.*
	FROM lcm_author_app,lcm_app
	WHERE lcm_author_app.id_app=lcm_app.id_app
		AND lcm_author_app.id_author=" . $GLOBALS['author_session']['id_author'];
$result = lcm_query($q);
if (lcm_num_rows($result)) {
	echo "<table>\n";
	echo "\t<tr>";
	echo '<th>Start time</th>';
	echo '<th>End time</th>';
	echo '<th>Type</th>';
	echo '<th>Title</th>';
	echo '<th>Reminder</th>';
	echo "</tr>\n";
	while ($row = lcm_fetch_array($result)) {
		echo "\t<tr>";
		echo '<td>' . $row['start_time'] . '</td>';
		echo '<td>' . $row['end_time'] . '</td>';
		echo '<td>' . $row['type'] . '</td>';
		echo '<td>' . $row['title'] . '</td>';
		echo '<td>' . $row['reminder'] . '</td>';
		echo "</tr>\n";
	}
	echo '</table>';
}

lcm_page_end();

?>