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

	$Id: keywords.php,v 1.37 2006/05/26 07:57:37 mlutfy Exp $
*/

include('inc/inc.php');

//
// Show all kwg for a given type (system, user, case, followup,
// client, org, author).
//
function show_all_keywords_type($type = '') {
	if (! $type)
		$type = 'system';
	
	$kwg_all = get_kwg_all($type);

	foreach ($kwg_all as $kwg) {
		// test ac-admin?
		$suggest = $kwg['suggest'];
		
		echo '<a name="' . $kwg['name'] . '"></a>' . "\n";
		echo "<fieldset class='info_box'>\n";
		echo "<div class='prefs_column_menu_head'>";
		echo "<a href='?action=edit_group&amp;id_group=" . $kwg['id_group'] . "' class='content_link'>"
			. _T($kwg['title']) 
			. "</a>";

		if ($kwg['type'] != 'system')
			echo " - " . _Ti('keywords_input_type') .  _T('keywords_input_type_' . $kwg['type']);

		if ($kwg['ac_author'] != 'Y')
			echo ' ' . _T('keywords_info_kwg_hidden');

		echo "</div>\n";

		$kw_all = get_keywords_in_group_id($kwg['id_group'], false);

		if (count($kw_all)) {
			$cpt_kw = 0;
			echo "<ul class='wo_blt'>\n";
			echo '<table border="0" align="center" class="tbl_usr_dtl" width="100%">' . "\n";

			foreach ($kw_all as $key => $kw) {
				$css = ' class="tbl_cont_' . ($cpt_kw %2 ? "dark" : "light") . '" ';
				// echo '<li>';
				echo "<tr>\n";

				// Keyword name
				echo "<td width='80%' $css>";
				if ($suggest == $kw['name']) echo "<b>";
				echo "<a href='?action=edit_keyword&amp;id_keyword=" . $kw['id_keyword'] . "' class='content_link'>". _T(remove_number_prefix($kw['title'])) . "</a>";
				if ($suggest == $kw['name']) echo "</b>";
				echo "</td>";

				// Hidden kw?
				echo "<td $css>";
				if ($kw['ac_author'] != 'Y')
					echo _T('keywords_info_kw_hidden');
				echo "</td>";

				// TODO: Keyword occurences?
				// [ML] really messy, because we don't know to what applies
				// a system keyword (except by making a big switch) and poking
				// everywhere..

				echo "</tr>\n";
				// echo "</li>\n";
				$cpt_kw++;
			}

			echo "</table>\n";
			echo "</ul>\n";
		}

		echo '<p><a class="edit_lnk" href="keywords.php?action=edit_keyword&amp;id_keyword=0&amp;'
			. 'id_group=' . $kwg['id_group'] . '">'
			. _T('keywords_button_kw_new') . "</a></p>\n";
	
		echo "</fieldset>\n";
	}

	if ($type == 'user')
		echo '<p><a href="keywords.php?action=edit_group&amp;id_group=0" class="create_new_lnk">'
			. _T('keywords_button_kwg_new') . '</a></p>' . "\n";

}

//
// View the details on a keyword group
//
function show_keyword_group_id($id_group) {
	global $system_kwg;

	if (! $id_group) {
		$kwg['name'] = '';
		$kwg['type'] = 'user';
		lcm_page_start(_T('title_kwg_new'));
	} else {
		$kwg = get_kwg_from_id($id_group);
		lcm_page_start(_T('title_kwg_edit'));
	}

	echo show_all_errors($_SESSION['errors']);
	
	echo '<form action="keywords.php" method="post">' . "\n";
	
	echo '<input type="hidden" name="action" value="update_group" />' . "\n";
	echo '<input type="hidden" name="id_group" value="' . $id_group . '" />' . "\n";
	
	echo "<table border='0' width='99%' align='left' class='tbl_usr_dtl'>\n";
	echo "<tr>\n";
	echo '<td width="30%"><label for="kwg_type">' . _T('keywords_input_type') . "</label></td>\n";
	echo "<td>";
	
	if ($kwg['type'] == 'system') {
		echo _T('keywords_input_type_system');
	} else {
		$all_types = array("case", "stage", "client", "org", "client_org");  // "author", "followup"
		
		echo '<select name="kwg_type" id="kwg_type">';

		foreach ($all_types as $t)
			echo '<option value="' . $t . '">' . _T('keywords_input_type_' . $t) . '</option>';

		echo "</select>\n";
	}
	
	echo "</td>\n";
	echo "</tr><tr>\n";
	echo '<td><label for="kwg_policy">' . _T('keywords_input_policy') . "</label></td>\n";
	echo "<td>";

	if ($kwg['type'] == 'system') {
		echo _T('keywords_input_policy_' . $kwg['policy']);
	} else {
		$all_policy = array('mandatory', 'optional', 'recommended');
		echo '<select name="kwg_policy" id="kwg_policy">';

		foreach ($all_policy as $pol) {
			$sel = ($kwg['policy'] == $pol ? ' selected="selected"' : '');
			echo '<option value="' . $pol . '"' . $sel . '>' 
				. _T('keywords_input_policy_' . $pol)
				. "</option>\n";
		}

		echo "</select>\n";
	}

	echo "</td>\n";
	echo "</tr><tr>\n";
	echo "<td>" . _T('keywords_input_suggest') . "</td>\n";
	echo "<td>";
	echo '<select name="kwg_suggest" class="sel_frm">';
	echo '<option value=""' . $sel . '>' . "none" . '</option>' . "\n"; // TRAD
	
	if ($id_group) {
		$all_kw = get_keywords_in_group_name($kwg['name']);
		foreach ($all_kw as $kw) {
			$sel = ($kw['name'] == $kwg['suggest'] ? ' selected="selected"' : '');
			echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>' . "\n";
		}
	}

	echo '</select>';
	echo "</td>\n";
	echo "</tr><tr>\n";

	// Name (only for new keywords, must be unique and cannot be changed)
	$disabled = ($id_group ? ' disabled="disabled" ' : '');
	echo "<td colspan='2'>";
	echo "<p class='normal_text'>";
	echo "<strong>" . f_err_star('name') . _T('keywords_input_name') . "</strong> " 
		. "(short identifier, unique to this keyword group)" . "<br />\n"; // TRAD
	echo '<input ' . $disabled . ' type="text" style="width:99%;" id="kwg_name" name="kwg_name" value="' . $kwg['name'] . '" class="search_form_txt" />' . "\n";
	echo "</p>\n";

	echo "<p class='normal_text'>";
	echo "<strong>" . f_err_star('title') . _T('keywords_input_title') . "</strong><br />\n";
	echo "<input type='text' style='width:99%;' id='kwg_title' name='kwg_title' value='" .  $kwg['title'] . "' class='search_form_txt' />\n";
	echo "</p>\n";

	echo "<p class='normal_text'>";
	echo "<strong>" . _T('keywords_input_description') . "</strong><br />\n";
	echo "<textarea id='kwg_desc' name='kwg_desc' style='width:99%' rows='2' cols='45' wrap='soft' class='frm_tarea'>";
	echo $kwg['description'];
	echo "</textarea>\n";
	echo "</p>\n";

	echo "</td>\n";
	echo "</tr><tr>\n";
	echo '<td colspan="2">';
	echo '<ul class="info">';

	// Quantity: relevevant only for user keywords (ex: 'thematics' for cases)
	if ($kwg['type'] != 'system') {
		// [ML] Yes, strange UI, but imho it works great (otherwise confusing, I hate checkboxes)
		$html_quantity = '<select name="kwg_quantity" id="kwg_quantity">'
			. '<option value="one"' . ($kwg['quantity'] == 'one' ? ' selected="selected"' : '') . '>' . _T('keywords_option_quantity_one') . '</option>'
			. '<option value="many"' . ($kwg['quantity'] == 'many' ? ' selected="selected"' : '') . '>' . _T('keywords_option_quantity_many') . '</option>'
			. '</select>';
	} else {
		$html_quantity = _T('keywords_option_quantity_' . $kwg['quantity'])
			. '<input type="hidden" name="kwg_quantity" value="' . $kwg['quantity'] . '" />';
	}
	
	echo '<li>' . _T('keywords_info_quantity', array('quantity' => $html_quantity)) . "</li>\n";

	if ($kwg['type'] != 'system') {
		if (! $id_group)
			$kwg['ac_author'] = 'Y';
	
		echo '<li>'
			. _T('keywords_info_kwg_ac_author') . " " . get_yes_no('kwg_ac_author', $kwg['ac_author'])
			. "</li>\n";
	}

	echo "</ul>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n\n";

	echo '<p><button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button></p>\n";
	echo "</form>\n";

	// destroy error messages
	$_SESSION['errors'] = array();

	lcm_page_end();
	exit;
}

//
// View the details on a keyword 
//
function show_keyword_id($id_keyword = 0) {
	if (! $id_keyword) {

		if (! intval($_REQUEST['id_group']) > 0)
			lcm_panic("missing valid id_group for new keyword");

		$kwg = get_kwg_from_id($_REQUEST['id_group']);

		// Suggest a keyword name: kwgnameNN, where NN = numeric sequence
		$all_kws = get_keywords_in_group_name($kwg['name'], false);
		$cpt = sprintf("%02d", count($all_kws) + 1);
		
		while (get_kw_from_name($kwg['name'], $kwg['name'] . $cpt))
			$cpt = sprintf("%02d", ++$cpt);

		$kw['name'] = $kwg['name'] . $cpt;
		$kw['title'] = '';
		$kw['description'] = '';
		$kw['id_group'] = $kwg['id_group'];
		$kw['ac_author'] = 'Y';
		$kw['hasvalue'] = 'N';
		$kw['type'] = $kwg['type'];
		lcm_page_start(_T('title_keyword_new'));
	} else {
		$kw = get_kw_from_id($id_keyword);
		lcm_page_start(_T('title_keyword_edit'));
	}

	echo show_all_errors($_SESSION['errors']);

	if (! $id_keyword) {
		echo "<ul style=\"padding-left: 0.5em; padding-top: 0.2; padding-bottom: 0.2; font-size: 12px;\">\n";
		echo '<li style="list-style-type: none;">' . _T('keywords_input_for_group') . " " . _T($kwg['title']) . "</li>\n";
		echo "</ul>\n";
	}
	
	echo '<fieldset class="info_box">';
	
	echo '<form action="keywords.php" method="post">' . "\n";
	echo '<input type="hidden" name="action" value="update_keyword" />' . "\n";
	echo '<input type="hidden" name="id_keyword" value="' . $id_keyword . '" />' . "\n";
	echo '<input type="hidden" name="id_group" value="' . $kw['id_group'] . '" />' . "\n"; // for new keyword only

	// Name (only for new keywords, must be unique and cannot be changed)
	echo "<strong>" . f_err_star('name', $_SESSION['errors']) . _T('keywords_input_name') . "</strong> " 
		. "(short identifier, unique to this keyword group)" . "<br />\n"; // TRAD

	$disabled = ($id_keyword ? ' disabled="disabled" ' : '');
	echo '<input ' . $disabled . ' type="text" id="kw_name" name="kw_name" value="' . $kw['name'] . '" class="search_form_txt" />' . "\n";
	echo "<br /><br />\n";
	
	// Title
	echo "<strong>" . f_err_star('title', $_SESSION['errors']) . _T('keywords_input_title') . "</strong><br />\n";
	echo "<input type='text' id='kw_title' name='kw_title' value='" .  $kw['title'] . "' class='search_form_txt' />\n";
	echo "<br /><br />\n";

	// Description
	echo "<strong>" . _T('keywords_input_description') . "</strong><br />\n";
	echo "<textarea id='kw_desc' name='kw_desc' rows='2' cols='45' wrap='soft' class='frm_tarea'>";
	echo $kw['description'];
	echo "</textarea>\n";
	
	// Ac_author
	echo '<ul class="info">';
	echo '<li>'
		. _T('keywords_info_kw_ac_author') . ' ' 
		. get_yes_no('kw_ac_author', $kw['ac_author'])
		. "</li>\n";
	
	echo '<li>'
		. "Does the keyword have a specific value?" . ' '  // TRAD
		. get_yes_no('kw_hasvalue', $kw['hasvalue'])
		. "</li>\n";

	echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
	echo "</form>\n";
	
	echo '</fieldset>';
	
	// destroy error messages
	$_SESSION['errors'] = array();

	lcm_page_end();
	exit;
}

//
// Update the information on a keyword group
//
function update_keyword_group($id_group) {
	$kwg_suggest = _request('kwg_suggest'); // sys + user
	$kwg_name    = _request('kwg_name');    // user only
	$kwg_title   = _request('kwg_title');   // sys + user
	$kwg_desc    = _request('kwg_desc');    // sys + user
	$kwg_type    = _request('kwg_type');    // user only
	$kwg_policy  = _request('kwg_policy');  // user only
	$kwg_quantity = _request('kwg_quantity'); // user only
	$kwg_hasvalue = _request('kwg_hasvalue'); // user only
	$kwg_ac_author = _request('kwg_ac_author'); // user only

	//
	// Check for errors
	//

	if (! $id_group) {
		if (! $kwg_name)
			$_SESSION['errors']['name'] = _Ti('keywords_input_name') . _T('warning_field_mandatory');

		if (! check_if_kwg_name_unique($kwg_name))
			$_SESSION['errors']['name'] = _T('keywords_warning_kwg_code_exists');
	}

	if (! $kwg_title)
		$_SESSION['errors']['title'] = _Ti('keywords_input_title') . _T('warning_field_mandatory');

	if (count($_SESSION['errors'])) {
		header("Location: " . $_SERVER['HTTP_REFERER']);
		exit;
	}

	//
	// Apply to database
	//

	if (! $id_group) { // new
		if ($kwg_type == 'system')
			lcm_panic("Operation not allowed (type = $kwg_type)");
	
		$query = "INSERT INTO lcm_keyword_group
					SET type = '" . $kwg_type . "',
						name = '" . $kwg_name . "',
						title = '" . $kwg_title . "',
						description = '" . $kwg_desc . "',
						suggest = '',
						policy = '" . $kwg_policy . "',
						quantity = '" . $kwg_quantity . "',
						ac_author = '" . $kwg_ac_author . "',
						ac_admin = 'Y'";

		lcm_query($query);
		$id_group = lcm_insert_id('lcm_keyword_group', 'id_group');
		$kwg_info = get_kwg_from_id($id_group);
	} else {
		// Get current kwg information (kwg_type & name cannot be changed)
		$kwg_info = get_kwg_from_id($id_group);

		$fl = " suggest = '$kwg_suggest', "
			. "title = '$kwg_title', "
			. "description = '$kwg_desc' ";
	
		if ($kwg_info['type'] != 'system') {
			$fl .= ", quantity = '$kwg_quantity' ";
			$fl .= ", policy = '$kwg_policy' ";

			if ($kwg_ac_author == 'Y' || $kwg_ac_author == 'N')
				$fl .= ", ac_author = '$kwg_ac_author' ";
		}
		
		$query = "UPDATE lcm_keyword_group
					SET $fl
					WHERE id_group = $id_group";
		
		lcm_query($query);
	}
	
	write_metas(); // update inc_meta_cache.php

	$tab = ($kwg_info['type'] == 'system' ? 'system' : 'user');
	header("Location: keywords.php?tab=" . $tab . "#" . $kwg_info['name']);
	exit;
}

//
// Update the information on a keyword
//
function update_keyword($id_keyword) {
	$kw_title     = _request('kw_title');
	$kw_name      = _request('kw_name'); // only for new keyword
	$kw_desc      = _request('kw_desc');
	$kw_ac_author = _request('kw_ac_author'); // show/hide keyword
	$kw_hasvalue  = _request('kw_hasvalue');  // show field to enter text value
	$kw_idgroup   = intval(_request('id_group'));

	//
	// Check for errors
	//

	if (! $id_keyword) { // new keyword
		global $system_kwg;

		if (! $kw_idgroup)
			lcm_panic("update_keyword: missing or badly formatted id_keyword or id_group");

		$kwg_info = get_kwg_from_id($kw_idgroup);

		if (! $kw_name)
			$_SESSION['errors']['name'] = _Ti('keywords_input_name') . _T('warning_field_mandatory');

		if (isset($system_kwg[$kwg_info['name']]['keywords'][$kw_name])) // XXX [ML] what about user keywords?
			$_SESSION['errors']['name'] = _Ti('keywords_input_name') . _T('keywords_warning_kw_code_exists');
	}

	if (! $kw_title)
		$_SESSION['errors']['title'] = _Ti('keywords_input_name') . _T('warning_field_mandatory');

	if (count($_SESSION['errors'])) {
		header("Location: " . $_SERVER['HTTP_REFERER']);
		exit;
	}

	//
	// Apply to database
	//

	if (! $id_keyword) { // new
		$query = "INSERT INTO lcm_keyword
				SET id_group = $kw_idgroup, 
					name = '$kw_name',
					title = '$kw_title',
					description = '$kw_desc',
					ac_author = '$kw_ac_author',
					hasvalue = '$kw_hasvalue'";

		lcm_query($query);
		$id_keyword = lcm_insert_id('lcm_keyword', 'id_keyword');
		$kw_info = get_kw_from_id($id_keyword); // for redirection later
	} else {
		// Get current info about keyword (don't trust the user)
		$kw_info = get_kw_from_id($id_keyword);
	
		$fl = "description = '$kw_desc',
				title = '$kw_title',
			 	hasvalue = '$kw_hasvalue'";
		
		if ($kw_ac_author == 'Y' || $kw_ac_author == 'N')
			$fl .= ", ac_author = '$kw_ac_author'";
	
		$query = "UPDATE lcm_keyword
					SET $fl
					WHERE id_keyword = " . $id_keyword;
		
		lcm_query($query);
	}

	write_metas(); // update inc_meta_cache.php

	$tab = ($kw_info['type'] == 'system' ? 'system' : 'user');
	header("Location: keywords.php?tab=" . $tab . "#" . $kw_info['kwg_name']);
	exit;
}


//
// Main
//

// Do any requested actions
if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'edit_group' :
			// Show form to edit a keyword group and exit
			show_keyword_group_id(intval($_REQUEST['id_group']));

			break;
		case 'edit_keyword':
			// Show form to edit a keyword and exit
			show_keyword_id(intval($_REQUEST['id_keyword']));

			break;
		case 'update_group':
			// Update the information on a keyword group then goes to edit group
			update_keyword_group(intval($_REQUEST['id_group']));

			break;
		case 'update_keyword':
			// Update the information on a keyword group then goes to edit group
			update_keyword(intval($_REQUEST['id_keyword']));

			break;
		case 'refresh':
			include_lcm('inc_meta');
			include_lcm('inc_keywords_default');

			$system_keyword_groups = get_default_keywords();
			create_groups($system_keyword_groups);
			write_metas(); // regenerate inc/data/inc_meta_cache.php
			
			break;
		default:
			die("No such action! (" . $_REQUEST['action'] . ")");
	}
}

lcm_page_start(_T('menu_admin_keywords'), '', '', 'keywords_intro');
lcm_bubble('keyword_list');

//
// Tabs
//
$groups = array('system' => _T('keywords_tab_system'),
				'user'   => _T('keywords_tab_user'),
				'maint'  => _T('keywords_tab_maintenance'));
$tab = (isset($_GET['tab']) ? $_GET['tab'] : 'system');

show_tabs($groups, $tab, $_SERVER['SCRIPT_NAME']);

switch ($tab) {
	case 'system' :
	case 'user' :
		show_all_keywords_type($tab);
		break;
	case 'maint' :
		echo '<fieldset class="info_box">' . "\n";
		echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '">' . "\n";
		echo '<p>' . _T('keywords_info_maintenance') . "</p>\n";

		echo '<button type="submit" name="action" value="refresh" class="simple_form_btn">'
			. _T('button_validate')
			. "</button>\n";
		echo "</form>\n";
		echo "</fieldset>\n";
}

lcm_page_end();

?>
