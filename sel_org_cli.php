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

	$Id: sel_org_cli.php,v 1.6 2005/03/31 15:08:19 mlutfy Exp $
*/

include('inc/inc.php');
lcm_page_start("Select organisation(s)"); // TRAD

$client = intval($_GET['client']);

if (! ($client > 0))
	die("There's no such client!");

$q = "SELECT *
	  FROM lcm_client_org
	  WHERE id_client = $client";

$result = lcm_query($q);

// Add org in NOT IN list
$q = "SELECT id_org,name
	  FROM lcm_org
	  WHERE id_org NOT IN (0";

while ($row = lcm_fetch_array($result)) {
	$q .= ',' . $row['id_org'];
}
$q .= ')';

// Add search criteria if any
if (strlen($_REQUEST['find_org_string']) > 1) {
	$find_org_string = $_REQUEST['find_org_string'];
	$q .= " AND (name LIKE '%$find_org_string%')";
}

// Sort by name
$order_name = 'ASC';
if (isset($_REQUEST['order_name']))
	if ($_REQUEST['order_name'] == 'ASC' || $_REQUEST['order_name'] == 'DESC')
		$order_name = $_REQUEST['order_name'];
		
$q .= " ORDER BY name " . $order_name;

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

show_find_box('org', $find_org_string, '__self__');
echo '<form action="add_org_cli.php" method="post">' . "\n";

$headers = array();
$headers[0]['title'] = '';
$headers[0]['order'] = 'no_order';
$headers[1]['title'] = _Th('org_input_name');
$headers[1]['order'] = 'order_name';

show_list_start($headers);

for ($i = 0; (($i < $prefs['page_rows']) && ($row = lcm_fetch_array($result))) ; $i++) {
?>
		<tr>
			<td><input type="checkbox" name="orgs[]" value="<?php echo $row['id_org']; ?>"></td>
			<td><?php echo $row['name']; ?></td>
		</tr>
<?php
	}
?>
		<tr>
			<td>&nbsp;</td>
			<td><a href="edit_org.php" class="content_link"><strong><?php echo _T('org_button_new');  ?></strong></a></td>
		</tr>

<?php

show_list_end($list_pos, $number_of_rows);

?>

	<input type="hidden" name="client" value="<?php echo $client; ?>">
	<input type="hidden" name="ref_sel_org_cli" value="<?php echo $HTTP_REFERER ?>">
	<p><button name="submit" type="submit" value="submit" class="simple_form_btn"><?php echo _T('button_validate'); ?></button></p>
</form>
<?php

lcm_page_end();

?>
