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

	$Id: keywords.php,v 1.12 2005/02/18 10:07:17 makaveev Exp $
*/

include('inc/inc.php');
include_lcm('inc_keywords');

//
// Show all kwg for a given type (system, user, case, followup,
// client, org, author).
//
function show_all_keywords($type = '') {
	if (! $type)
		$type = 'system';
	
	$kwg_all = get_kwg_all($type);

	foreach ($kwg_all as $kwg) {
		// test ac-admin?
		$suggest = $kwg['suggest'];
		
		echo "<fieldset class='info_box'>\n";
		echo "<div class='prefs_column_menu_head'><a href='?action=edit_group&amp;id_group=" . $kwg['id_group'] . "' class='content_link'>" . _T($kwg['title']) . "</a></div>\n";

		$kw_all = get_keywords_in_group_id($kwg['id_group']);

		if (count($kw_all)) {
			echo "<ul class='wo_blt'>\n";

			foreach ($kw_all as $kw) {
				echo "\t<li>";
				if ($suggest == $kw['name']) echo "<b>";
				echo "<a href='?action=edit_keyword&amp;id_keyword=" . $kw['id_keyword'] . "' class='content_link'>". _T($kw['title']) . "</a>";
				if ($suggest == $kw['name']) echo "</b>";
				echo "</li>\n";
			}

			echo "</ul>\n";
		}
		
		echo "</fieldset>\n";
	}

	if ($type == 'user')
		echo '<a href="keywords.php?action=edit_group&amp;id_group=0" class="create_new_lnk">Create a new keyword group</a>' . "\n";

}

//
// View the details on a keyword group
//
function show_keyword_group_id($id_group) {
	global $system_kwg;

	if (! $id_group) {
		echo "not coded yet";
		exit;
	}

	$kwg = get_kwg_from_id($id_group);
	lcm_page_start("Keyword group: " . $kwg['name']);

	echo show_all_errors($_SESSION['errors']);
	
	echo '<form action="keywords.php" method="post">' . "\n";
	
	echo '<input type="hidden" name="action" value="update_group" />' . "\n";
	echo '<input type="hidden" name="id_group" value="' . $id_group . '" />' . "\n";
	echo '<input type="hidden" name="kwg_type" value="' . $kwg['type'] . '" />' . "\n";
	
	echo "<table border='0' width='99%' align='left' class='tbl_usr_dtl'>\n";
	echo "<tr>\n";
	echo "<td>" . _T('keywords_input_type') . "</td>\n";
	echo "<td>" . $kwg['type'] . "</td>\n";
	echo "</tr><tr>\n";
	echo "<td>" . _T('keywords_input_policy') . "</td>\n";
	echo "<td>" . $kwg['policy'] . "</td>\n";
	echo "</tr><tr>\n";
	echo "<td>" . _T('keywords_input_quantity') . "</td>\n";
	echo "<td>" . $kwg['quantity'] . "</td>\n";
	echo "</tr><tr>\n";
	echo "<td>" . _T('keywords_input_suggest') . "</td>\n";
	echo "<td>";
	echo '<select name="kwg_suggest" class="sel_frm">';
	echo '<option value=""' . $sel . '>' . "none" . '</option>' . "\n";
	
	foreach ($system_kwg[$kwg['name']]['keywords'] as $kw) {
		$sel = ($kw['name'] == $kwg['suggest'] ? ' selected="selected"' : '');
		echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>' . "\n";
	}

	echo '</select>';
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>" . f_err_star('title', $_SESSION['errors']) . _T('keywords_input_title') . "<br />\n";
	echo "<input type='text' style='width:99%;' id='kwg_title' name='kwg_title' value='" .  $kwg['title'] . "' class='search_form_txt' />\n";
	echo "</td>\n";
	echo "<tr></tr>\n";
	echo "<td colspan='2'>" . _T('keywords_input_description') . "<br />\n";
	echo "<textarea id='kwg_desc' name='kwg_desc' style='width:99%' rows='2' cols='45' wrap='soft' class='frm_tarea'>";
	echo $kwg['description'];
	echo "</textarea>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n\n";

	echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
	echo "</form>\n";

	// destroy error messages
	$_SESSION['errors'] = array();

	lcm_page_end();
	exit;
}

//
// View the details on a keyword 
//
function show_keyword_id($id_keyword) {
	$kw = get_kw_from_id($id_keyword);

	lcm_page_start("Keyword: " . $kw['name']);
	echo show_all_errors($_SESSION['errors']);
	
	echo '<form action="keywords.php" method="post">' . "\n";
	
	echo '<input type="hidden" name="action" value="update_keyword" />' . "\n";
	echo '<input type="hidden" name="id_keyword" value="' . $id_keyword . '" />' . "\n";
	echo '<input type="hidden" name="kwg_type" value="' . $kw['type'] . '" />' . "\n";
	
	echo "<table border='0' width='99%' align='left' class='tbl_usr_dtl'>\n";
	echo "<tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>" . f_err_star('title', $_SESSION['errors']) . _T('keywords_input_title') . "<br />\n";
	echo "<input type='text' style='width:99%;' id='kw_title' name='kw_title' value='" .  $kw['title'] . "' class='search_form_txt' />\n";
	echo "</td>\n";
	echo "<tr></tr>\n";
	echo "<td colspan='2'>" . _T('keywords_input_description') . "<br />\n";
	echo "<textarea id='kw_desc' name='kw_desc' style='width:99%' rows='2' cols='45' wrap='soft' class='frm_tarea'>";
	echo $kw['description'];
	echo "</textarea>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n\n";

	echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
	echo "</form>\n";

	// destroy error messages
	$_SESSION['errors'] = array();

	lcm_page_end();
	exit;
}

//
// Update the information on a keyword group
//
function update_keyword_group($id_group) {
	$kwg_suggest = $_REQUEST['kwg_suggest'];
	$kwg_title   = $_REQUEST['kwg_title'];
	$kwg_desc    = $_REQUEST['kwg_desc'];
	$kwg_type    = $_REQUEST['kwg_type'];

	if (! intval($id_group) > 0)
		lcm_panic("update_keyword_group: missing or badly formatted id_group");
	
	$fl = " suggest = '" . clean_input($kwg_suggest) . "' ";
	
	if ($kwg_title) // cannot be empty
		$fl .= ", title = '" . clean_input($kwg_title) . "' ";
	else
		$_SESSION['errors']['title'] = "The title cannot be empty.";
	
	$fl .= ", description = '" . clean_input($kwg_desc) . "' ";

	$query = "UPDATE lcm_keyword_group
				SET $fl
				WHERE id_group = " . $id_group;
	
	lcm_query($query);
	write_metas(); // update inc_meta_cache.php

	header("Location: keywords.php?tab=" . $kwg_type);
	exit;
}

//
// Update the information on a keyword
//
function update_keyword($id_keyword) {
	$kw_title   = $_REQUEST['kw_title'];
	$kw_desc    = $_REQUEST['kw_desc'];
	$kwg_type   = $_REQUEST['kwg_type'];

	if (! intval($id_keyword) > 0)
		lcm_panic("update_keyword: missing or badly formatted id_keyword");
	
	$fl = "description = '" . clean_input($kw_desc) . "' ";
	
	if ($kw_title) // cannot be empty
		$fl .= ", title = '" . clean_input($kw_title) . "' ";
	else
		$_SESSION['errors']['title'] = "The title cannot be empty.";

	$query = "UPDATE lcm_keyword
				SET $fl
				WHERE id_keyword = " . $id_keyword;
	
	lcm_query($query);
	write_metas(); // update inc_meta_cache.php

	header("Location: keywords.php?tab=" . $kwg_type);
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
			// Do not remove, or variables won't be declared
			global $system_keyword_groups;
			$system_keyword_groups = array();
		
			include_lcm('inc_meta');
			include_lcm('inc_keywords_default');
			create_groups($system_keyword_groups);

			break;
		default:
			die("No such action! (" . $_REQUEST['action'] . ")");
	}
}

// Define tabs
$groups = array('system' => 'System keywords','user' => 'User keywords','maint' => 'Keyword maintenance');
$tab = ( isset($_GET['tab']) ? $_GET['tab'] : 'system' );

// Start page
//lcm_page_start(_T('menu_admin_keywords') . _T('typo_column') . " " . $groups[$tab]);
lcm_page_start(_T('menu_admin_keywords'));

// Show warning message
echo "<fieldset class='info_box'>\n";
echo "<p class='normal_text'><strong>Warning:</strong> This feature is still in early development. For more
information, please consult the <a href='http://www.lcm.ngo-bg.org/article43.html' class='content_link'>analysis
documentation for keywords</a>.</p>\n";
echo "</fieldset>\n";

// Show tabs
//show_tabs($groups,$tab,$_SERVER['REQUEST_URI']);
show_tabs($groups, $tab, $_SERVER['SCRIPT_NAME']);

// Show tab contents
switch ($tab) {
	case 'system' :
	case 'user' :
		show_all_keywords($tab);
		break;
	case 'maint' :
		echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . "\">\n";
		echo "\t<button type=\"submit\" name=\"action\" value=\"refresh\" class=\"simple_form_btn\">Refresh default keywords</button>\n";
		echo "</form>\n";
}

lcm_page_end();

?>
