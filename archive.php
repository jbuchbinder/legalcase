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

	$Id: archive.php,v 1.4 2005/02/28 14:09:31 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');

// Check access rights
if ($GLOBALS['author_session']['status'] != 'admin') 
	die("You don't have the right to list all cases!");

// Show page start
lcm_page_start(_T('title_archives'));

// Show tabs
$tabs = array(	array('name' => _T('archives_tab_all_cases'), 'url' => 'archive.php'),
		array('name' => _T('archives_tab_export'), 'url' => 'export_db.php'),
		array('name' => _T('archives_tab_import'), 'url' => 'import_db.php')
	);
show_tabs_links($tabs,0);

$q = "SELECT DISTINCT lcm_case.id_case,title,status,public,pub_write
		FROM lcm_case,lcm_case_author
		WHERE (lcm_case.id_case=lcm_case_author.id_case";

$find_case_string = $_REQUEST['find_case_string'];

// Add search criteria if any
if (strlen($find_case_string) > 1) {
	$q .= " AND ((lcm_case.title LIKE '%$find_case_string%')
				OR (lcm_case.status LIKE '%$find_case_string%'))";
}

$q .= ")";

$result = lcm_query($q);
$number_of_rows = lcm_num_rows($result);

// Check for correct start position of the list
$list_pos = (isset($_REQUEST['list_pos']) ? $_REQUEST['list_pos'] : 0);

if ($list_pos >= $number_of_rows)
	$list_pos = 0;

// Position to the page info start
if ($list_pos > 0)
	if (!lcm_data_seek($result,$list_pos))
		die("Error seeking position $list_pos in the result");

echo '<form name="frm_find_case" class="search_form" action="all_cases.php" method="post">' . "\n";
echo _T('input_search_case') . "&nbsp;";
echo '<input type="text" name="find_case_string" size="10" class="search_form_txt" value="' .  $find_case_string . '" />';
echo '&nbsp;<input type="submit" name="submit" value="' . _T('button_search') . '" class="search_form_btn" />' . "\n";
echo "</form>\n";

// Process the output of the query
show_listcase_start();

for ($i = 0 ; (($i<$prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	$action = '<a href="edit_fu.php?case=' . $row['id_case'] . '" class="content_link">Add followup</a>';
	show_listcase_item($row, $i, $action);
}

show_listcase_end();

?>

<table border='0' align='center' width='99%' class='page_numbers'>
	<tr><td align="left" width="15%"><?php

// Show link to previous page
if ($list_pos>0) {
	echo '<a href="all_cases.php?list_pos=';
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
			echo '<a href="all_cases.php?list_pos=' . ($i*$prefs['page_rows']);
			if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
			echo '" class="content_link">' . ($i+1) . '</a> ';
		}
	}
}

echo "</td>\n\t\t<td align='right' width='15%'>";

// Show link to next page
$next_pos = $list_pos + $prefs['page_rows'];
if ($next_pos<$number_of_rows) {
	echo "<a href=\"all_cases.php?list_pos=$next_pos";
	if (strlen($find_case_string)>1) echo "&amp;find_case_string=" . rawurlencode($find_case_string);
	echo '" class="content_link">Next ></a>';
}

echo "</td>\n\t</tr>\n</table>\n";

lcm_page_end();
?>
