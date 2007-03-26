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

	$Id: fu_det.php,v 1.39 2007/03/26 16:14:32 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_obj_fu');

if (isset($_GET['followup'])) {
	$followup = intval($_GET['followup']);

	// Fetch the details on the specified follow-up
	$q="SELECT fu.*, a.name_first, a.name_middle, a.name_last, " . 
		lcm_query_subst_time('fu.date_start', 'fu.date_end') . " as length
		FROM lcm_followup as fu, lcm_author as a
		WHERE id_followup = $followup
			AND fu.id_author = a.id_author";

	$result = lcm_query($q);

	if ($row = lcm_fetch_array($result)) {
		foreach($row as $key=>$value) {
			$fu_data[$key] = $value;
		}
	} else die("There's no such follow-up!");
} else {
	die("Which follow-up?");
}

// For 'edit case' button + 'undelete' message
$case_allow_modif = read_meta('case_allow_modif');
$edit  = allowed($fu_data['id_case'], 'e');
$admin = allowed($fu_data['id_case'], 'a');

lcm_page_start(_T('title_fu_view'), '', '', 'cases_followups');

echo '<fieldset class="info_box">';

// Show a bit of background on the case
$case = $fu_data['id_case'];
show_context_start();
show_context_case_title($fu_data['id_case']);
show_context_case_stage($fu_data['id_case'], $fu_data['id_followup']);
show_context_case_involving($fu_data['id_case']);

// Show parent appointment, if any
// [ML] todo put in inc_presentation
$q = "SELECT app.*
		FROM lcm_app_fu as af, lcm_app as app
		WHERE af.id_followup = $followup 
		  AND af.id_app = app.id_app 
		  AND af.relation = 'child'";
$res_app = lcm_query($q);

if ($app = lcm_fetch_array($res_app)) {
	echo '<li style="list-style-type: none;">' . _T('fu_input_parent_appointment') . ' ';
	echo '<a class="content_link" href="app_det.php?app=' . $app['id_app'] . '">' . _Tkw('appointments', $app['type'])
		. ' (' . $app['title'] . ') from ' . format_date($app['start_time']) . "</a></li>\n"; // TRAD
}

// Show child appointment, if any
$q = "SELECT app.* 
		FROM lcm_app_fu as af, lcm_app as app
		WHERE af.id_followup = $followup 
		  AND af.id_app = app.id_app 
		  AND af.relation = 'parent'";

$res_app = lcm_query($q);

if ($app = lcm_fetch_array($res_app)) {
	echo '<li style="list-style-type: none;">' . _T('fu_input_child_appointment') . ' ';
	echo '<a class="content_link" href="app_det.php?app=' . $app['id_app'] . '">' . _Tkw('appointments', $app['type'])
		. ' (' . $app['title'] . ') from ' . format_date($app['start_time']) . "</a></li>\n"; // TRAD
}

// Show stage information
if ($fu_data['case_stage']) {
	// if editing an existing followup..
	if ($fu_data['case_stage'])
		$stage_info = get_kw_from_name('stage', $fu_data['case_stage']);
	$id_stage = $stage_info['id_keyword'];
	show_context_stage($fu_data['id_case'], $id_stage);
}

show_context_end();

if ($fu_data['hidden'] == 'Y') {
	echo '<p class="normal_text"><strong>' . _T('fu_info_is_deleted') . "</strong>";

	if ($admin)
		echo " " . _T('fu_info_is_deleted2');
	
	echo "</p>\n";
}

$obj_fu = new LcmFollowupInfoUI($fu_data['id_followup']);
$obj_fu->printGeneral();

echo "<br />";

// Edit button
if ($case_allow_modif == 'yes' && $edit) {
	echo '<a href="edit_fu.php?followup=' . $fu_data['id_followup'] . '" class="edit_lnk">'
		. _T('fu_button_edit')
		. '</a>';
}

// [ML] Not useful
//if ($GLOBALS['author_session']['status'] == 'admin')
//	echo '<a href="export.php?item=followup&amp;id=' . $fu_data['id_followup'] . '" class="exp_lnk">' . _T('export_button_followup') . "</a>\n";

echo "<br /><br /></fieldset>";

if (! $app) {
	// Show create appointment from followup
	echo '<p><a href="edit_app.php?case=' . $fu_data['id_case'] . '&amp;followup=' . $followup . '" class="create_new_lnk">Create new appointment related to this followup' . "</a></p>\n";  // TRAD
}

lcm_page_end();

?>
