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

	$Id: listapps.php,v 1.12 2005/03/25 11:23:21 mlutfy Exp $
*/

include('inc/inc.php');

lcm_page_start('Agenda'); // TRAD

$q = "SELECT lcm_app.*
	FROM lcm_author_app,lcm_app
	WHERE lcm_author_app.id_app=lcm_app.id_app
		AND lcm_author_app.id_author=" . $GLOBALS['author_session']['id_author'];

// Sort agenda by date/time of the appointments
$order = 'DESC';
if (isset($_REQUEST['order']))
	if ($_REQUEST['order'] == 'ASC' || $_REQUEST['order'] == 'DESC')
		$order = $_REQUEST['order'];

$q .= " ORDER BY start_time " . $order;

$result = lcm_query($q);

// Get the number of rows in the result
$number_of_rows = lcm_num_rows($result);
if ($number_of_rows) {
	$headers = array( array( 'title' => _Th('time_input_date_start'), 'order' => 'order', 'default' => 'DESC'),
			array( 'title' => ( ($prefs['time_intervals'] == 'absolute') ? _Th('time_input_date_end') : _Th('time_input_duration') ), 'order' => 'no_order'),
			array( 'title' => _Th('app_input_type'), 'order' => 'no_order'),
			array( 'title' => _Th('app_input_title'), 'order' => 'no_order'),
			array( 'title' => 'Reminder', 'order' => 'no_order'));	// TRAD
	show_list_start($headers);

/*	echo "<table border='0' align='center' class='tbl_usr_dtl' width='99%'>\n";
	echo "\t<tr>";
	echo '<th class="heading">' . _Th('time_input_date_start') . '</th>';
	echo '<th class="heading">' . ( ($prefs['time_intervals'] == 'absolute') ? _Th('time_input_date_end') : _Th('time_input_duration') ) . '</th>';
	echo '<th class="heading">' . _Th('app_input_type') . '</th>';
	echo '<th class="heading">' . _Th('app_input_title'). '</th>';
	echo '<th class="heading">Reminder</th>'; // TRAD 
	echo '<th class="heading">Action</th>'; // TRAD
	echo "</tr>\n";
*/
	// Check for correct start position of the list
	$list_pos = 0;
	
	if (isset($_REQUEST['list_pos']))
		$list_pos = $_REQUEST['list_pos'];
	
	if ($list_pos>=$number_of_rows) $list_pos = 0;
	
	// Position to the page info start
	if ($list_pos>0)
		if (!lcm_data_seek($result,$list_pos))
			lcm_panic("Error seeking position $list_pos in the result");	// TRAD
	
	// Show page of the list
	for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
		echo "\t<tr>";
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
			. format_date($row['start_time'], 'short') . '</td>';

		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
			. ( ($prefs['time_intervals'] == 'absolute') ?
				date('d.m.y H:i',strtotime($row['end_time'])) :
				format_time_interval(strtotime($row['end_time']) - strtotime($row['start_time']),
							($prefs['time_intervals_notation'] == 'hours_only') )
			) . '</td>';
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">' . $row['type'] . '</td>';
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
			. '<a href="app_det.php?app=' . $row['id_app'] . '" class="content_link">' . $row['title'] . '</a></td>';
		echo '<td class="tbl_cont_' . ($i % 2 ? 'dark' : 'light') . '">'
			. format_date($row['reminder'], 'short') . '</td>';
		echo "</tr>\n";
	}

	show_list_end($list_pos, $number_of_rows);
}

echo '<p><a href="edit_app.php?app=0" class="create_new_lnk">New appointment</a></p>'; // TRAD

lcm_page_end();

?>
