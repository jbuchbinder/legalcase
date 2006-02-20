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

	$Id: sel_auth.php,v 1.12 2006/02/20 03:25:03 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');

global $prefs;

$case = intval($_REQUEST['case']);

if (! ($case > 0)) {
	lcm_page_start(_T('title_error'));
	echo "<p>" . _T('error_no_case_specified') . "</p>\n";
	lcm_page_end();
	exit;
}

$destination = "case_det.php?id_case=" . $case;

if (isset($_SERVER['HTTP_REFERER']))
	$destination = $_SERVER['HTTP_REFERER'];
	
$dest_link = new Link($destination);

if (! allowed($case,'a'))
	die("You don't have permission to add users to this case!");

$q = "SELECT *
		FROM lcm_case_author
		WHERE id_case=$case";

$result = lcm_query($q);

$q = "SELECT id_author, name_first, name_middle, name_last, status
		FROM lcm_author
		WHERE id_author NOT IN (0";

// Add clients to NOT IN list
while ($row = lcm_fetch_array($result)) {
	$q .= ',' . $row['id_author'];
}

$q .= ')';

// Add search criteria if any
if (isset($_REQUEST['find_author_string']) && strlen($_REQUEST['find_author_string']) > 1) {
	$find_author_string = $_REQUEST['find_author_string'];

	$q .= " AND ((name_first LIKE '%$find_author_string%')"
		. " OR (name_middle LIKE '%$find_author_string%')"
		. " OR (name_last LIKE '%$find_author_string%'))";
} else {
	$find_author_string = "";
}

// Sort authors by status
$order_set = false;
$order_status = '';
if (isset($_REQUEST['order_status']))
	if ($_REQUEST['order_status'] == 'ASC' || $_REQUEST['order_status'] == 'DESC') {
		$order_status = $_REQUEST['order_status'];
		$q .= " ORDER BY status " . $order_status;
		$order_set = true;
	}

// Sort by name_first
$order_name = 'ASC';
if (isset($_REQUEST['order_name']))
	if ($_REQUEST['order_name'] == 'ASC' || $_REQUEST['order_name'] == 'DESC')
		$order_name = $_REQUEST['order_name'];
		
$q .= ($order_set ? " , " : " ORDER BY ");
$q .= " name_first " . $order_name;

$result = lcm_query($q);
$number_of_rows = lcm_num_rows($result);

// Check for correct start position of the list
$list_pos = 0;
if (isset($_REQUEST['list_pos']))
	$list_pos = $_REQUEST['list_pos'];

if ($list_pos >= $number_of_rows)
	$list_pos = 0;

// Position to the page info start
if ($list_pos > 0)
	if (!lcm_data_seek($result,$list_pos))
		lcm_panic("Error seeking position $list_pos in the result");

// Check if any author(s) available for selection
if ($find_author_string || lcm_num_rows($result) > 0)
	lcm_page_start(_T('title_case_add_author'), '', '', 'cases_participants');
else {
	// TODO: add $_SESSION['errors']['generic'] message?
	header('Location: ' . $dest_link->getUrlForHeader());
	exit;
}

show_context_start();
show_context_case_title($case);
show_context_case_involving($case);
show_context_end();

show_find_box('author', $find_author_string, '__self__');
echo '<form action="add_auth.php" method="post">' . "\n";

$headers = array();
$headers[0]['title'] = '';
$headers[0]['order'] = 'no_order';
$headers[1]['title'] = _Th('person_input_name');
$headers[1]['order'] = 'order_name';
$headers[2]['title'] = _Th('authoredit_input_status');
$headers[2]['order'] = 'order_status';

show_list_start($headers);

for ($i = 0; (($i < $prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	echo "<tr>\n";
	echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">';
	echo '<input type="checkbox" name="authors[]" value="' . $row['id_author'] . '" />';
	echo "</td>\n";
	echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . get_person_name($row) . "</td>\n";
	echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">' . _T('authoredit_input_status_' . $row['status']) . "</td>\n";
	echo "</tr>\n";
}

show_list_end($list_pos, $number_of_rows);

?>

<input type="hidden" name="case" value="<?php echo $case; ?>" />
<input type="hidden" name="ref_sel_auth" value="<?php echo $dest_link->getUrl(); ?>" />
<button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate'); ?></button>
</form>

<?php

lcm_page_end();

?>
