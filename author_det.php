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

	$Id: author_det.php,v 1.5 2005/03/11 17:11:45 antzi Exp $
*/

include('inc/inc.php');
include_lcm('inc_contacts');

// Initialise variables
$author = intval($_REQUEST['author']);

if ($author > 0) {
	// Get author data
	$q = "SELECT *
			FROM lcm_author
			WHERE id_author = $author";
	$result = lcm_query($q);

	if ($author_data = lcm_fetch_array($result)) {
		// Start the page
		$fullname = $author_data['name_first'];
		$fullname .= ($author_data['name_middle'] ? ' ' . $author_data['name_middle'] : '');
		$fullname .= ($author_data['name_last'] ? ' ' . $author_data['name_last'] : '');

		lcm_page_start("Author details: $fullname");

		//
		// Show author contacts (if any)
		//
		$hide_emails = read_meta('hide_emails');
		$contacts = get_contacts('author', $author);

		$html = '';
		$html .= '<table border="0" align="center" class="tbl_usr_dtl" width="99%">' . "\n";
		$html .= '<tr><th class="heading" colspan="2">' . "Contacts:" . '</th></tr>' . "\n";

		$i = 0;
		foreach($contacts as $c) {
			// Check if the contact is an e-mail
			if (strpos($c['name'],'email') === 0) {
				if (! ($hide_emails == 'yes' && $author_session['status'] != 'admin')) {
					$html .= "\t<tr>";
					$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . _T($c['title']) . ":</td>";
					$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>";
					$html .= '<a href="mailto:' . $c['value'] . '">' . $c['value'] . '</a></td>';
					$html .= "</tr>\n";
					$i++;
				}
			} else {
				$html .= "\t<tr>";
				$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . _T($c['title']) . ":</td>";
				$html .= "<td class='tbl_cont_" . ($i % 2 ? "dark" : "light") . "'>" . $c['value'] . "</td>";
				$html .= "</tr>\n";
				$i++;
			}
		}

		$html .= "</table><br />\n";

		if ($i > 0)
			echo $html;

		/* [ML] 2005-01-17: Redundant with edit-author
		if (($GLOBALS['author_session']['status'] == 'admin') ||
			($author == $GLOBALS['author_session']['id_author'])) {
			// Show "add contact" form
			echo '<form method="POST" action="add_contact.php">' . "\n";
			echo "\t<input type='hidden' name='author' value='$author' />\n";
			// Show author keywords
			$q = "SELECT lcm_keyword.*
					FROM lcm_keyword,lcm_keyword_group
					WHERE ((lcm_keyword.id_group=lcm_keyword_group.id_group)
						AND (lcm_keyword_group.name='contacts'))";
			$result = lcm_query($q);
			echo "\t<select class=\"sel_frm\">\n";
			while ($row = lcm_fetch_array($result)) {
				echo "\t\t<option>" . _T($row['title']) . "</option>\n";
			}
			echo "\t</select>\n";
			echo "\t<input type='text' size='40' style='style: 99%' name='value' class='search_form_txt' />";
			echo "\t<input name='submit' type='submit' class='search_form_btn' id='submit' value='Add contact' />\n";
			echo "</form>\n";
		} */

		//
		// Show 'edit author' button, if allowed
		//
		if (($GLOBALS['author_session']['status'] == 'admin') ||
			($author == $GLOBALS['author_session']['id_author']))
				echo '<p class="normal_text"><a href="edit_author.php?author=' . $author . "\" class=\"edit_lnk\">Edit author data</a></p>\n";

		lcm_page_end();
	} else {
		die("There's no such author!");
	}
} else {
	die("Which author?");
}

?>
