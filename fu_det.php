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

	$Id: fu_det.php,v 1.5 2005/01/21 09:21:14 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

if (isset($_GET['followup'])) {
	$followup=intval($_GET['followup']);

	// Fetch the details on the specified follow-up
	$q="SELECT *
		FROM lcm_followup
		WHERE id_followup=$followup";

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
	echo '<li style="list-style-type: none;">' . _T('info_followup_case')
		. " <a href='case_det.php?case=$case'>" . $row['title'] . "</a></li>\n";

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
	echo '<li style="list-style-type: none;">' . _T('info_followup_involving') . " ";

foreach ($all_clients as $client) {
	if ($client['id_client']) {
		echo '<a href="client_det.php?client=' . $client['id_client'] . '" class="content_link">'
			. $client['name_first'] . ' ' . $client['name_middle'] . ' ' . $client['name_last']
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

echo "</ul>\n";

?>

	<table class="tbl_usr_dtl" width="99%">
		<tr><td>Start:</td>
			<td><?php echo format_date($fu_data['date_start']); ?></td></tr>
		<tr><td>End:</td>
			<td><?php echo format_date($fu_data['date_end']); ?></td></tr>
		<tr><td>Type:</td>
			<td><?php echo $fu_data['type']; ?></td></tr>
		<tr><td valign="top">Description:</td>
			<td><?php echo clean_output($fu_data['description']);
				echo "</td></tr>\n";

				// Read the policy settings
				$fu_sum_billed = read_meta('fu_sum_billed');
				if ($fu_sum_billed=='yes') {
?>		<tr><td>Sum billed:</td>
			<td><?php echo clean_output($fu_data['sumbilled']);
					// [ML] If we do this we may as well make a function
					// out of it, but not sure where to place it :-)
					// This code is also in config_site.php
					$currency = read_meta('currency');
					if (empty($currency)) {
						$current_lang = $GLOBALS['lang'];
						$GLOBALS['lang'] = read_meta('default_language');
						$currency = _T('currency_default_format');
						$GLOBALS['lang'] = $current_lang;
					}

					echo htmlspecialchars($currency);
					echo "\n			</td></tr>\n";
				}; echo "	</table>\n";

	lcm_page_end();
?>
