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

	$Id: fu_det.php,v 1.14 2005/03/17 15:00:43 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_keywords');

if (isset($_GET['followup'])) {
	$followup=intval($_GET['followup']);

	// Fetch the details on the specified follow-up
	$q="SELECT lcm_followup.*,lcm_author.name_first,lcm_author.name_middle,lcm_author.name_last
		FROM lcm_followup, lcm_author
		WHERE id_followup=$followup
			AND lcm_followup.id_author=lcm_author.id_author";

	$result = lcm_query($q);

	if ($row = lcm_fetch_array($result)) {
		foreach($row as $key=>$value) {
			$fu_data[$key] = $value;
		}
	} else die("There's no such follow-up!");
} else {
	die("Which follow-up?");
}

lcm_page_start("Follow-up details");

// Show a bit of background on the case
$case = $fu_data['id_case'];
echo "<ul style=\"padding-left: 0.5em; padding-top: 0.2; padding-bottom: 0.2; font-size: 12px;\">\n";

// Name of case
$query = "SELECT title
		FROM lcm_case
		WHERE id_case=$case";

$result = lcm_query($query);
while ($row = lcm_fetch_array($result))  // should be only once
	echo '<li style="list-style-type: none;">' . _T('fu_input_for_case')
		. " <a href='case_det.php?case=$case' class='content_link'>" . $row['title'] . "</a></li>\n";

// We dump all the clients and org in the same array, then show
// them on screen in a more densed way
// Could be more esthetic or ergonomic, but works for now..
$query = "SELECT cl.id_client, name_first, name_middle, name_last
			FROM lcm_case_client_org as cco, lcm_client as cl
			WHERE cco.id_case=$case
			  AND cco.id_client = cl.id_client";

$result = lcm_query($query);
$numrows = lcm_num_rows($result);
$current = 0;

$all_clients = array();

while ($all_clients[] = lcm_fetch_array($result));

$query = "SELECT org.name, cco.id_client, org.id_org
			FROM lcm_case_client_org as cco, lcm_org as org
			WHERE cco.id_case=$case
			  AND cco.id_org = org.id_org";

$result = lcm_query($query);
$numrows += lcm_num_rows($result);

// TODO: It would be nice to have the name of the contact for that
// organisation, if any, but then again, not the end of the world.
// (altough I we make a library of common functions, it will defenitely
// be a good thing to have)
while ($all_clients[] = lcm_fetch_array($result));

if ($numrows > 0)
	echo '<li style="list-style-type: none;">' . _T('fu_input_involving_clients') . " ";

foreach ($all_clients as $client) {
	if ($client['id_client']) {
		echo '<a href="client_det.php?client=' . $client['id_client'] . '" class="content_link">'
			. njoin(array($client['name_first'],$client['name_middle'],$client['name_last']))
			. '</a>';

		if (++$current < $numrows)
			echo ", ";
	} else if ($client['id_org']) {
		echo '<a href="org_det.php?org=' . $client['id_org'] . '" class="content_link">'
			. $client['name']
			. '</a>';

		if (++$current < $numrows)
			echo ", ";
	}

}

if ($numrows > 0)
	echo "</li>\n";

// Show parent appointment, if any
$q = "SELECT lcm_app.* FROM lcm_app_fu,lcm_app WHERE lcm_app_fu.id_followup=$followup AND lcm_app_fu.id_app=lcm_app.id_app";
$res_app = lcm_query($q);
if ($app = lcm_fetch_array($res_app)) {
	echo '<li style="list-style-type: none;">' . _T('fu_input_parent_appointment') . ' ';
	echo '<a href="app_det.php?app=' . $app['id_app'] . '">' . _T(get_kw_title($app['type']))
		. ' (' . $app['title'] . ') from ' . format_date($app['start_time']) . "</a></li>\n";
}

echo "</ul>\n";

?>

	<table class="tbl_usr_dtl" width="99%">
		<tr><td><?php echo 'Author:'; /* TRAD */ ?></td>
			<td><?php echo get_person_name($fu_data); ?></td></tr>
		<tr><td><?php echo _T('fu_input_date_start'); ?></td>
			<td><?php echo format_date($fu_data['date_start']); ?></td></tr>
		<tr><td><?php echo _T('fu_input_date_end'); ?></td>
			<td><?php echo format_date($fu_data['date_end']); ?></td></tr>
		<tr><td><?php echo _T('fu_input_type'); ?></td>
			<td><?php echo _T('kw_followups_' . $fu_data['type'] . '_title'); ?></td></tr>
		<tr><td valign="top"><?php echo _T('fu_input_description'); ?></td>
			<td><?php
			
	echo nl2br(clean_output($fu_data['description']));
	echo "</td></tr>\n";

	// Sum billed (if activated from policy)
	$fu_sum_billed = read_meta('fu_sum_billed');

	if ($fu_sum_billed == 'yes') {
		echo "<tr><td>" . _T('fu_input_sum_billed') . "</td>\n";
		echo "<td>";
		echo format_money(clean_output($fu_data['sumbilled']));
		$currency = read_meta('currency');
		echo htmlspecialchars($currency);
		echo "</td></tr>\n";
	}
				
	echo "</table>\n";

	// Show 'edit case' button if allowed
	$case_allow_modif = read_meta('case_allow_modif');
	$edit = ($GLOBALS['author_session']['status'] == 'admin') || allowed($case,'e');

	if ($case_allow_modif == 'yes' && $edit) {
		echo '<p><a href="edit_fu.php?followup=' . $fu_data['id_followup'] . '" class="edit_lnk">'
				. "Edit follow-up" // TRAD
				. '</a></p>';
	}
	
	lcm_page_end();
?>
