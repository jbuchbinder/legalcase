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

	$Id: listapps.php,v 1.7 2005/03/05 00:00:39 antzi Exp $
*/

include('inc/inc.php');

lcm_page_start('Agenda');

$q = "SELECT lcm_app.*
	FROM lcm_author_app,lcm_app
	WHERE lcm_author_app.id_app=lcm_app.id_app
		AND lcm_author_app.id_author=" . $GLOBALS['author_session']['id_author'];
$result = lcm_query($q);

// Get the number of rows in the result
$number_of_rows = lcm_num_rows($result);
if ($number_of_rows) {
	echo "<table border='0' align='center' class='tbl_usr_dtl' width='99%'>\n";
	echo "\t<tr>";
	echo '<th class="heading">Start time</th>';
	echo '<th class="heading">' . ( ($prefs['time_intervals'] == 'absolute') ? 'End time' : 'Duration' ) . '</th>';
	echo '<th class="heading">Type</th>';
	echo '<th class="heading">Title</th>';
	echo '<th class="heading">Reminder</th>';
	echo '<th class="heading">Action</th>';
	echo "</tr>\n";

	// Check for correct start position of the list
	$list_pos = 0;
	
	if (isset($_REQUEST['list_pos']))
		$list_pos = $_REQUEST['list_pos'];
	
	if ($list_pos>=$number_of_rows) $list_pos = 0;
	
	// Position to the page info start
	if ($list_pos>0)
		if (!lcm_data_seek($result,$list_pos))
			die("Error seeking position $list_pos in the result");
	
	// Show page of the list
	for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
		echo "\t<tr>";
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
			. date('d.m.y H:i',strtotime($row['start_time'])) . '</td>';
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
			. ( ($prefs['time_intervals'] == 'absolute') ?
				date('d.m.y H:i',strtotime($row['end_time'])) :
				format_time_interval(strtotime($row['end_time']) - strtotime($row['start_time']),
							($prefs['time_intervals_notation'] == 'hours_only') )
			) . '</td>';
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">' . $row['type'] . '</td>';
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
			. '<a href="app_det.php?app=' . $row['id_app'] . '">' . $row['title'] . '</a></td>';
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
			. date('d.m.y H:i',strtotime($row['reminder'])) . '</td>';
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
			. '<a href="edit_app.php?app=' . $row['id_app'] . '">' . _T('edit') . '</a></td>';
		echo "</tr>\n";
	}
	
	echo "</table>\n\n";

	if ($number_of_rows>$prefs['page_rows']) {
		echo '<table border="0" align="center" width="99%" class="page_numbers">
	<tr><td align="left" width="15%">';

		// Show link to previous page
		if ($list_pos>0) {
			echo '<a href="listapps.php?list_pos=';
			echo ( ($list_pos>$prefs['page_rows']) ? ($list_pos - $prefs['page_rows']) : 0);
			if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
			echo '" class="content_link">< Prev</a> ';
		}

		echo "</td>\n\t\t<td align='center' width='70%'>";

		// Show page numbers with direct links
		$list_pages = ceil($number_of_rows / $prefs['page_rows']);
		if ($list_pages>1) {
			echo 'Go to page: ';
			for ($i=0 ; $i<$list_pages ; $i++) {
				if ($i==floor($list_pos / $prefs['page_rows'])) echo '[' . ($i+1) . '] ';
				else {
					echo '<a href="listapps.php?list_pos=' . ($i*$prefs['page_rows']);
					if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
					echo '" class="content_link">' . ($i+1) . '</a> ';
				}
			}
		}
		
		echo "</td>\n\t\t<td align='right' width='15%'>";
		
		// Show link to next page
		$next_pos = $list_pos + $prefs['page_rows'];
		if ($next_pos<$number_of_rows) {
			echo "<a href=\"listapps.php?list_pos=$next_pos";
			if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
			echo '" class="content_link">Next ></a>';
		}
		
		echo "</td>\n\t</tr>\n</table>\n";
	}

}

echo '<br /><a href="edit_app.php?app=0" class="create_new_lnk">New appointment</a><br /><br />';

lcm_page_end();

?>