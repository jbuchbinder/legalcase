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

	$Id: keywords.php,v 1.6 2005/01/28 23:37:05 mlutfy Exp $
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
	
	lcm_page_start(_T('menu_admin_keywords') . _T('typo_column') . " " . $type);

	echo "<p>Warning: This feature is still in early development. For more
	information, please consult the <a href='http://www.lcm.ngo-bg.org/article43.html'>analysis
	documentation for keywords</a>.</p>\n";

	$html_system = ($type == 'system' ? '&nbsp;&lt;--' : '');
	$html_user   = ($type == 'user' ? '&nbsp;&lt;--' : '');

	// Mini-menu: system or user keyword groups
	echo "<ul>";
	echo '<li><a href="?type=system">System keywords</a>' . $html_system . "</li>\n";
	echo '<li><a href="?type=user">User keywords</a>' . $html_user . "</li>\n";
	echo "</ul>\n\n";

	$kwg_all = get_kwg_all($type);

	foreach ($kwg_all as $kwg) {
		// tester ac-admin?
		echo "<ul style='padding: 0.5em; border: 1px solid #cccccc; -moz-border-radius: 10px;'>\n";
		echo "<li style='list-style-type: none;'><a href='?action=edit&amp;id_group=" . $kwg['id_group'] . "'>" . _T($kwg['title']) . "</a></li>\n";

		$kw_all = get_keywords_in_group_id($kwg['id_group']);

		if (count($kw_all)) {
			echo "<ul style='padding: 1em;>";

			foreach ($kw_all as $kw) {
				echo "<li style='list-style-type: none;'>";
				echo "<a href='?action=edit&amp;id_keyword=" . $kw['id_keyword'] . "'>". _T($kw['title']) . "</a>";
				echo "</li>\n";
			}

			echo "</ul>\n";
		}

		echo "</ul>\n";
	}

	lcm_page_end();
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
	echo '<select name="kwg_suggest">';
	
	foreach ($system_kwg[$kwg['name']]['keywords'] as $kw) {
		$sel = ($kw['name'] == $kwg['suggest'] ? ' selected="selected"' : '');
		echo '<option value="' . $kw['name'] . '"' . $sel . '>' . _T($kw['title']) . '</option>' . "\n";
	}

	echo '</select>';
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>" . _T('keywords_input_title') . "<br />\n";
	echo "<input type='text' readonly='readonly' style='width:99%;' id='kwg_title' name='kwg_title' value='" .  $kwg['title'] . "' />\n";
	echo "</td>\n";
	echo "<tr></tr>\n";
	echo "<td colspan='2'>" . _T('keywords_input_description') . "<br />\n";
	echo "<textarea readonly='readonly' id='kwg_desc' name='kwg_desc' style='width:99%' rows='2' cols='45' wrap='soft'>";
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

if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'edit') {
		$action = $_REQUEST['action'];
		
		if (isset($_REQUEST['id_group']) && intval($_REQUEST['id_group']) > 0) {
			show_keyword_group_id($action, intval($_REQUEST['id_group']));
		} else if (isset($_REQUEST['id_keyword']) && intval($_REQUEST['id_keyword']) > 0) {
			show_keyword_id($action, intval($_REQUEST['id_keyword']));
		}
	} else if ($_REQUEST['action'] == 'edit') {
		echo "<p>Not ready yet</p>\n";
	}

	exit;
}

// Default action
$type = (isset($_REQUEST['type']) ? $_REQUEST['type'] : '');
show_all_keywords($type);

?>
