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

	$Id: app_det.php,v 1.10 2005/03/18 23:07:20 antzi Exp $
*/

include('inc/inc.php');

$app = intval($_GET['app']);

$q = "SELECT lcm_app.*,lcm_author.name_first,lcm_author.name_middle,lcm_author.name_last,lcm_case.title AS case_title
	FROM lcm_app, lcm_author_app, lcm_author
	LEFT JOIN lcm_case ON (lcm_case.id_case = lcm_app.id_case)
	WHERE (lcm_app.id_app=$app
		AND lcm_author_app.id_app=$app
		AND lcm_author_app.id_author=" . $GLOBALS['author_session']['id_author'] . "
		AND lcm_app.id_author=lcm_author.id_author)";
$result = lcm_query($q);

if ($row = lcm_fetch_array($result)) {
	lcm_page_start('Appointment details:' . ' ' . $row['title']);

	echo '<fieldset class="info_box">';
//	echo '<div class="prefs_column_menu_head">' . _T('app_subtitle_general') . '</div>';
	echo "<p class=\"normal_text\">\n";
	
	echo "Start time: " . format_date($row['start_time'],'short') . "<br />\n";
	$end_time = vider_date($row['end_time']);
	$reminder = vider_date($row['reminder']);
	if ($prefs['time_intervals'] == 'absolute') {
		echo "End time: " . $row['end_time'] . "<br />\n";
		echo "Reminder: " . $row['reminder'] . "<br />\n";
	} else {
		$duration = ($end_time ? strtotime($row['end_time']) - strtotime($row['start_time']) : 0);
		echo "Duration: " . format_time_interval($duration,($prefs['time_intervals_notation'] == 'hours_only')) . "<br />\n";
		$reminder_offset = ($reminder ? strtotime($row['start_time']) - strtotime($row['reminder']) : 0);
		echo "Reminder: " . format_time_interval($reminder_offset,($prefs['time_intervals_notation'] == 'hours_only')) . " before start time<br />\n";
	}
	echo "Type: " . $row['type'] . "<br />\n";
	echo "Title: " . $row['title'] . "<br />\n";
	echo "Description: " . $row['description'] . "<br />\n";
	echo "Created by: " . njoin(array($row['name_first'],$row['name_middle'],$row['name_last'])) . "<br />\n";
	if ($row['case_title'])
		echo 'In connection with case: <a href="case_det.php?case=' . $row['id_case'] . '" class="content_link">' . $row['case_title'] , "</a><br />\n";

	// Show appointment participants
	$q = "SELECT lcm_author_app.*,lcm_author.name_first,lcm_author.name_middle,lcm_author.name_last
		FROM lcm_author_app, lcm_author
		WHERE (id_app=" . $row['id_app'] . "
			AND lcm_author_app.id_author=lcm_author.id_author)";
	$res_author = lcm_query($q);
	if (lcm_num_rows($res_author)>0) {
		echo "Participants: ";
		$participants = array();
		while ($author = lcm_fetch_array($res_author)) {
			$participants[] = njoin(array($author['name_first'],$author['name_middle'],$author['name_last']));
		}
		echo join(', ',$participants);
		echo "<br />\n";
	}
	
	// Show appointment clients
	$q = "SELECT lcm_app_client_org.*,lcm_client.name_first,lcm_client.name_middle,lcm_client.name_last,lcm_org.name
		FROM lcm_app_client_org, lcm_client
		LEFT JOIN  lcm_org ON lcm_app_client_org.id_org=lcm_org.id_org
		WHERE (id_app=" . $row['id_app'] . "
			AND lcm_app_client_org.id_client=lcm_client.id_client)";
	$res_client = lcm_query($q);

	if (lcm_num_rows($res_client)>0) {
		echo "Clients: ";
		$clients = array();
		while ($client = lcm_fetch_array($res_client))
			$clients[] = njoin(array($client['name_first'],$client['name_middle'],$client['name_last']))
				. ( ($client['id_org'] > 0) ? " of " . $client['name'] : '');
		echo join(', ',$clients);
		echo "<br />\n";
	}

	// Show edit appointment button
	if ($row['id_author'] == $GLOBALS['author_session']['id_author'])
		echo '<br /><a href="edit_app.php?app=' . $row['id_app'] . '" class="create_new_lnk">' . 'Edit this appointment' . "</a><br />\n"; // TRAD

	if ($row['id_case'] > 0) {
//		echo '<br />';
		// Show child followup
		$q = "SELECT lcm_app_fu.id_followup,lcm_followup.description FROM lcm_app_fu,lcm_followup
			WHERE lcm_app_fu.id_app=" . $row['id_app'] . "
				AND lcm_app_fu.id_followup=lcm_followup.id_followup
				AND lcm_app_fu.relation='parent'";
		$res_fu = lcm_query($q);
		if (lcm_num_rows($res_fu) > 0) {
			// Show child followup
			$fu = lcm_fetch_array($res_fu);
			$title_length = (($prefs['screen'] == "wide") ? 48 : 115);
			if (strlen(lcm_utf8_decode($fu['description'])) < $title_length)
				$short_description = $fu['description'];
			else
				$short_description = substr($fu['description'],0,$title_length) . '...';
			echo '<br />Followup:' . ' <a href="fu_det.php?followup=' . $fu['id_followup'] . '">' . $short_description;
		} else {
			// Show create followup from appointment
			echo '<br /><a href="edit_fu.php?case=' . $row['id_case'] . '&amp;app=' . $row['id_app']
				. '" class="create_new_lnk">Create new followup from this appointment';
		}
		echo "</a><br />\n";

		// Show link back to the case details
		echo '<br /><a href="case_det.php?case=' . $row['id_case'] . '&amp;tab=appointments" class="back_lnk">' . 'To case appointments' . "</a><br />\n";
	}

	echo "<br /></p>";
	echo "</fieldset>\n";

	lcm_page_end();
} else die("There is no such appointment!");

?>