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

	$Id: keywords.php,v 1.9 2005/02/17 07:40:12 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_keywords');

//
// Show all kwg for a given type (system, user, case, followup,
// client, org, author).
//
function show_all_keywords($type = '') {
/*	if (! $type)
		$type = 'system';
	
	$html_system = ($type == 'system' ? '&nbsp;&lt;--' : '');
	$html_user   = ($type == 'user' ? '&nbsp;&lt;--' : '');

	// Mini-menu: system or user keyword groups
	echo "<ul>";
	echo '<li><a href="?type=system" class="content_link">System keywords</a>' . $html_system . "</li>\n";
	echo '<li><a href="?type=user" class="content_link">User keywords</a>' . $html_user . "</li>\n";
	echo "</ul>\n\n";
	
*/
	$kwg_all = get_kwg_all($type);

	foreach ($kwg_all as $kwg) {
		// tester ac-admin?
		
		echo "<fieldset class='info_box'>\n";
		echo "<div class='prefs_column_menu_head'><a href='?action=edit&amp;id_group=" . $kwg['id_group'] . "' class='content_link'>" . _T($kwg['title']) . "</a></div>\n";

		$kw_all = get_keywords_in_group_id($kwg['id_group']);

		if (count($kw_all)) {
			echo "<ul class='wo_blt'>\n";

			foreach ($kw_all as $kw) {
				echo "\t<li>";
				echo "<a href='?action=edit&amp;id_keyword=" . $kw['id_keyword'] . "' class='content_link'>". _T($kw['title']) . "</a>";
				echo "</li>\n";
			}

			echo "</ul>\n";
		}
		
		echo "</fieldset>\n";
		
	}
} 

//
// View the details on a keyword group
//
function show_keyword_group_id($action = 'edit', $id_group) {
	global $system_kwg;

	$kwg = get_kwg_from_id($id_group);
	lcm_page_start("Keyword group: " . $kwg['name']);
	
	echo '<form action="keywords.php" method="post">' . "\n";
	
	echo '<input type="hidden" name="action" value="update_group" />' . "\n";
	echo '<input type="hidden" name="id_group" value="' . $id_group . '" />' . "\n";
	
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
	
	foreach ($system_kwg[$kwg['name']]['keywords'] as $kw) {
		$sel = ($kw['name'] == $kwg['suggest'] ? ' selected="selected"' : '');
		echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>' . "\n";
	}

	echo '</select>';
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>" . _T('keywords_input_title') . "<br />\n";
	echo "<input type='text' readonly='readonly' style='width:99%;' id='kwg_title' name='kwg_title' value='" .  $kwg['title'] . "' class='search_form_txt' />\n";
	echo "</td>\n";
	echo "<tr></tr>\n";
	echo "<td colspan='2'>" . _T('keywords_input_description') . "<br />\n";
	echo "<textarea readonly='readonly' id='kwg_desc' name='kwg_desc' style='width:99%' rows='2' cols='45' wrap='soft' class='frm_tarea'>";
	echo $kwg['description'];
	echo "</textarea>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n\n";

	echo '<button name="submit" type="submit" value="submit" class="simple_form_btn">' . _T('button_validate') . "</button>\n";
	echo "</form>\n";

	lcm_page_end();
}

//
// View the details on a keyword 
//
function show_keyword_id($action = 'view', $id_keyword) {
	$kw = get_kw_from_id($id_keyword);

	lcm_page_start("Keyword: " . $kw['name']);

	echo "<p>Title = " . $kw['title'] . "</p>\n";
	echo "<p>TODO</p>\n";

	lcm_page_end();
}

//
// Main
//

// Do any requested actions
if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'edit' :
			if (isset($_REQUEST['id_group']) && intval($_REQUEST['id_group']) > 0) {
				show_keyword_group_id($_REQUEST['action'], intval($_REQUEST['id_group']));
			} else if (isset($_REQUEST['id_keyword']) && intval($_REQUEST['id_keyword']) > 0) {
				show_keyword_id($_REQUEST['action'], intval($_REQUEST['id_keyword']));
			}
			exit;
			break;
		case 'refresh' :
			// Do not remove, or variables won't be declared
			global $system_keyword_groups;
			$system_keyword_groups = array();
		
			include_lcm('inc_meta');
			include_lcm('inc_keywords_default');
			create_groups($system_keyword_groups);

			break;
		default :
			//echo "<p>Not ready yet</p>\n";
			die("No such action!");
	}
	//exit;
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
show_tabs($groups,$tab,$_SERVER['SCRIPT_NAME']);

// Show tab contents
switch ($tab) {
	case 'system' :
	case 'user' :
		show_all_keywords($tab);
		break;
	case 'maint' :
		echo '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . "\">\n";
		echo "\t<button type=\"submit\" name=\"action\" value=\"refresh\">Refresh default keywords</button>\n";
		echo "</form>\n";
}

lcm_page_end();

?>
