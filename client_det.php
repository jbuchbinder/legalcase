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

	$Id: client_det.php,v 1.56 2007/01/12 17:37:04 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_contacts');
include_lcm('inc_obj_client');

$client = intval(_request('client'));

if (! ($client > 0)) {
	lcm_header("Location: listclients.php");
	exit;
}

$q = "SELECT *
		FROM lcm_client as c
		WHERE c.id_client = $client";

$result = lcm_query($q);

if (! ($row = lcm_fetch_array($result)))
	die("ERROR: There is no such client in the database.");

lcm_page_start(_T('title_client_view') . ' ' . get_person_name($row), '', '', 'clients_intro');

		/* Saved for future use
			// Check for access rights
			if (!($row['public'] || allowed($client,'r'))) {
				die("You don't have permission to view this client details!");
			}
			$edit = allowed($client,'w');
		*/

		$edit = true;

		// Show tabs
		$groups = array(
			'general' => array('name' => _T('generic_tab_general'), 'tooltip' => _T('generic_subtitle_general')),
			// 'organisations' => _T('generic_tab_org'),
			'cases' => array('name' => _T('generic_tab_cases'), 'tooltip' => _T('client_subtitle_cases')),
			'attachments' => array('name' => _T('generic_tab_documents'), 'tooltip' => _T('client_subtitle_attachments')));

		$tab = ( isset($_GET['tab']) ? $_GET['tab'] : 'general' );
		show_tabs($groups,$tab,$_SERVER['REQUEST_URI']);

		if ($c = intval(_request('attach_case', 0))) {
			$q = "SELECT title
					FROM lcm_case
					WHERE id_case = " . $c;
			$result = lcm_query($q);

			while ($row1 = lcm_fetch_array($result)) {
				echo '<div class="sys_msg_box">';
				echo '<ul>';
				echo '<li>' . _Ti('client_info_created_attached')
					. '<a class="content_link" href="case_det.php?case=' . $c . '">' 
					. $row1['title'] 
					. "</a></li>\n";
				echo "</ul>\n";
				echo "</div>\n";
			}
		}

		switch ($tab) {
			case 'general':
				//
				// Show client general information
				//
				echo '<fieldset class="info_box">';
		
				$obj_client = new LcmClientInfoUI($row['id_client']);
				$obj_client->printGeneral();

				if ($edit)
					echo '<a href="edit_client.php?client=' .
					$row['id_client'] . '" class="edit_lnk">' .  _T('client_button_edit') . '</a>' . "\n";

				// [ML] Not useful
				// if ($GLOBALS['author_session']['status'] == 'admin')
				//	echo '<a href="export.php?item=client&amp;id=' . $row['id_client'] . '" class="exp_lnk">' . _T('export_button_client') . "</a>\n";

				echo '<br /><br />';
				echo "</fieldset>\n";
				break;
			case 'organisations':
				//
				// Show client associated organisations
				//
				echo '<fieldset class="info_box">';
				echo '<div class="prefs_column_menu_head">' . _T('client_subtitle_associated_org') . "</div>\n";

				echo '<form action="add_org_cli.php" method="post">' . "\n";
				echo '<input type="hidden" name="client" value="' . $client . '" />' . "\n";
				
				//
				// Show organisation(s)
				//
				$q = "SELECT lcm_org.id_org,name
						FROM lcm_client_org,lcm_org
						WHERE id_client=$client
							AND lcm_client_org.id_org=lcm_org.id_org";
		
				$result = lcm_query($q);
				$show_table = false;

				if (lcm_num_rows($result)) {
					$show_table = true;

					echo '<table border="0" class="tbl_usr_dtl" width="100%">' . "\n";
					echo "<tr>\n";
					echo '<th class="heading">&nbsp;</th>';
					echo '<th class="heading">' . _Th('org_input_name') . '</th>';
					echo '<th class="heading">&nbsp;</th>';
					echo "</tr>\n";
				} else {
					// TODO info message?
				}

				$i = 0;
				while ($row1 = lcm_fetch_array($result)) {
					echo "<tr>\n";

					// Image
					echo '<td width="25" align="center" class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '"><img src="images/jimmac/stock_people.png" alt="" height="16" width="16" /></td>' . "\n";

					// Name of org
					echo '<td class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '"><a style="display: block;" href="org_det.php?org=' . $row1['id_org'] .  '" class="content_link">' . $row1['name'] . "</a></td>\n";

					// Delete association
					echo '<td width="1%" nowrap="nowrap" class="tbl_cont_' . ($i % 2 ? "dark" : "light") . '">';

					echo '<label for="id_rem_org_' . $row1['id_org'] . '">';
					echo '<img src="images/jimmac/stock_trash-16.png" width="16" height="16" '
						. 'alt="' . _T('client_info_delete_org') . '" title="' . _T('client_info_delete_org') . '" />';
					echo '</label>&nbsp;';
					echo '<input type="checkbox" onclick="lcm_show(\'btn_delete\')" id="id_rem_org_' . $row1['id_org'] . '" name="rem_orgs[]" value="' . $row1['id_org'] . '" /></td>';

					echo "</tr>\n";
					$i++;
				}
				
				if ($show_table)
					echo "</table>";

				echo '<div align="right" style="visibility: hidden">';
				echo '<input type="submit" name="submit" id="btn_delete" value="' . _T('button_validate') . '" class="search_form_btn" />';
				echo "</div>\n";
		
				if ($edit)
					echo "<p><a href=\"sel_org_cli.php?client=$client\" class=\"add_lnk\">" . _T('client_button_add_org') . "</a></p>";

				echo "</form>\n";
				echo "</fieldset>";
				
				break;

			case 'cases':
				//
				// Show recent cases
				//

				$q = "SELECT clo.id_case, c.title, c.date_creation, c.status
						FROM lcm_case_client_org as clo, lcm_case as c
						WHERE id_client = " . $client . "
						AND clo.id_case = c.id_case ";

				// Sort cases by creation date
				$case_order = 'DESC';
				if (isset($_REQUEST['case_order']))
					if ($_REQUEST['case_order'] == 'ASC' || $_REQUEST['case_order'] == 'DESC')
						$case_order = $_REQUEST['case_order'];
				
				$q .= " ORDER BY c.date_creation " . $case_order;
		
				$result = lcm_query($q);
				$number_of_rows = lcm_num_rows($result);
				$list_pos = 0;
				
				if (isset($_REQUEST['list_pos']))
					$list_pos = $_REQUEST['list_pos'];
				
				if ($list_pos >= $number_of_rows)
					$list_pos = 0;
				
				// Position to the page info start
				if ($list_pos > 0)
					if (!lcm_data_seek($result,$list_pos))
						lcm_panic("Error seeking position $list_pos in the result");

				if (lcm_num_rows($result)) {
					echo '<fieldset class="info_box">' . "\n";
					show_page_subtitle(_T('client_subtitle_cases'), 'cases_participants');

					echo "<p class=\"normal_text\">\n";
					show_listcase_start();
		
					for ($cpt = 0; (($i<$prefs['page_rows']) && ($row1 = lcm_fetch_array($result))); $cpt++)
						show_listcase_item($row1, $cpt);

					show_listcase_end($list_pos, $number_of_rows);
					echo "</p>\n";
					echo "</fieldset>\n";
				}

				break;
			//
			// Client attachments
			//
			case 'attachments' :
				echo '<fieldset class="info_box">';
				show_page_subtitle(_T('client_subtitle_attachments'), 'tools_documents');
				echo "<p class=\"normal_text\">\n";

				echo '<form enctype="multipart/form-data" action="attach_file.php" method="post">' . "\n";
				echo '<input type="hidden" name="client" value="' . $client . '" />' . "\n";

				// List of attached files
				show_attachments_list('client', $client);

				// Attach new file form
				if ($edit)
					show_attachments_upload('client', $client);

				echo '<input type="submit" name="submit" value="' . _T('button_validate') . '" class="search_form_btn" />' . "\n";
				echo "</form>\n";

				echo "</p>\n";
				echo "</fieldset>\n";
				break;
		}

// Show this in all tabs
echo '<p>';
echo '<a href="edit_case.php?case=0&amp;attach_client=' . $row['id_client'] . '" class="create_new_lnk">';
echo _T('case_button_new');
echo "</a>";
echo "</p>\n";
				
// Clear session info
$_SESSION['client_data'] = array(); // DEPRECATED since 0.6.4
$_SESSION['form_data'] = array();
$_SESSION['errors'] = array();

lcm_page_end();
?>
