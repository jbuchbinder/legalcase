<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2007 Free Software Foundation, Inc.

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

	$Id: sel_client.php,v 1.19 2007/03/20 18:33:30 mlutfy Exp $
*/

include('inc/inc.php');

$case = intval(_request('case'));

if (! $case > 0)
	die("ERROR: There is no such case.");

// Get case data
$q = "SELECT id_case, title
		FROM lcm_case
		WHERE id_case=$case";
$result = lcm_query($q);

$case_data = lcm_fetch_array($result);

//
// Show only clients who are not already in the case
// Extract the clients on the case, then put them in a "not in" list
//
$q = "SELECT *
		FROM lcm_case_client_org
		WHERE (id_case=$case AND id_client>0)";

$result = lcm_query($q);

$q2 = "SELECT id_client,name_first,name_middle,name_last
		FROM lcm_client
		WHERE (id_client NOT IN (0";

// Build "not in" list
while ($row = lcm_fetch_array($result)) {
	$q2 .= ',' . $row['id_client'];
}

$q2 .= ')';

// Add search criteria if any
$find_client_string = _request('find_client_string');

if (strlen($find_client_string)>1) {
	$q2 .= " AND ((name_first LIKE '%$find_client_string%')"
		. " OR (name_middle LIKE '%$find_client_string%')"
		. " OR (name_last LIKE '%$find_client_string%'))";
}


$q2 .= ")";

// Sort client by name_first
$order_name = 'ASC';
if (_request('order_name') == 'ASC' || _request('order_name') == 'DESC')
	$order_name = _request('order_name');

$q2 .= " ORDER BY name_first " . $order_name;

$result = lcm_query($q2);

lcm_page_start(_T('title_case_add_client'));

show_context_start();
show_context_case_title($case);
show_context_case_involving($case);
show_context_end();

// Get the number of rows in the result
$number_of_rows = lcm_num_rows($result);

// Check for correct start position of the list
$list_pos = intval(_request('list_pos', 0));
if ($list_pos >= $number_of_rows) $list_pos = 0;

// Position to the page info start
if ($list_pos>0)
	if (!lcm_data_seek($result,$list_pos))
		die("Error seeking position $list_pos in the result");

show_find_box('client', $find_client_string, '__self__');
echo '<form action="add_client.php" method="post">' . "\n";

$headers[0]['title'] = "";
$headers[0]['order'] = 'no_order';
$headers[1]['title'] = _Th('person_input_name');
$headers[1]['order'] = 'order_name';
$headers[1]['default'] = 'ASC';

show_list_start($headers);

// Process the output of the query
for ($i = 0 ; (($i < $prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
	echo "<tr>";

	// Show checkbox
	echo "<td width='1%' class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	echo "<input type='checkbox' name='clients[]' value='" . $row['id_client'] . "'>";
	echo "</td>\n";

	// Show client name
	echo "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
	echo '<a href="client_det.php?client=' . $row['id_client'] . '" class="content_link">';
	echo highlight_matches(clean_output($row['name_first'] . ' ' . $row['name_middle'] . ' '
		. $row['name_last']), $find_client_string);
	echo "</a>";
	echo "</td>\n";

	echo "</tr>\n";
}

echo "<tr>\n";
echo '<td colspan="2"><p><a href="edit_client.php?attach_case=' . $case . '" class="create_new_lnk">' 
	. _T('client_button_new_for_case')
	.  '</a></p></td>' . "\n";
echo "</tr>\n";

show_list_end($list_pos, $number_of_rows);

?>

	<input type="hidden" name="case" value="<?php echo $case; ?>">
	<input type="hidden" name="ref_sel_client" value="<?php echo $_SERVER['HTTP_REFERER']; ?>">
	<p><button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate'); ?></button></p>
</form>

<?php

lcm_page_end();

?>
