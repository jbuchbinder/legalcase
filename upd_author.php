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
*/

include('inc/inc.php');
include_lcm('inc_filters');

// Start session
session_start();

// Register $errors array - just in case
if (!session_is_registered("errors"))
    session_register("errors");

// Clear all previous errors
$errors=array();

// Register form data in the session
if(!session_is_registered("usr"))
    session_register("usr");

// Get form data from POST fields
foreach($_POST as $key => $value)
    $usr[$key]=$value;

// Chech author data for validty
if (!$usr['email']) $errors['email'] = 'You MUST specify an e-mail!';

if (count($errors)) {
    header("Location: $HTTP_REFERER");
    exit;
} else {
	$fl = "id_author=" . $usr['id_author'] . ",username='" . clean_input($usr['username']) . "'"
		. ",name_first='" . clean_input($usr['name_first']) . "'"
		. ",name_middle='" . clean_input($usr['name_middle']) . "'"
		. ",name_last='" . clean_input($usr['name_last']) . "'"
		. ",status='" . clean_input($usr['status']) . "'"
		. ",date_update=NOW()";

	if ($usr['id_author'] > 0) {
		// Check access rights
		if ($GLOBALS['author_session']['status'] != 'admin') die("You don't have permission to change author's information!");
		else {
			$q = "UPDATE lcm_author SET $fl WHERE id_author=" . $usr['id_author'];
			$result = lcm_query($q);
		}
	} else {
		$q = "INSERT INTO lcm_author SET date_creation=NOW(),$fl";
		$result = lcm_query($q);
		$usr['id_author'] = lcm_insert_id();
	}

	// Insert/update e-mail into contacts
	if ($usr['email_exists']) {
		$q = "UPDATE lcm_contact SET value='" . $usr['email'] . "'"
			. " WHERE (id_of_person=" . $usr['id_author']
			. " AND type_person='author' AND type_contact=1)";
	} else {
		$q = "INSERT INTO lcm_contact
				SET id_of_person=" . $usr['id_author'] . ",
					value='" . $usr['email'] . "',
					type_person='author',
					type_contact=1";
	}
	$result = lcm_query($q);

    // Clear the session
    session_destroy();

	header('Location: ' . $usr['ref_edit_author']);
}
?>