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

	$Id: upd_author.php,v 1.7 2005/01/10 15:39:07 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

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

// Check author data for validty
// [ML:temporary] if (!$usr['email']) $errors['email'] = 'You MUST specify an e-mail!';

// There were errors, send user back to form
if (count($errors)) {
    header("Location: $HTTP_REFERER");
    exit;
}

//
// Change password (if requested)
//

global $author_session;

if ($usr['usr_new_passwd']) {
	// FIXME: include auth type according to 'auth_type' field in DB
	// default on 'db' if field not present/set.
	$class_auth = 'Auth_db';
	include_lcm('inc_auth_db');

	$auth = new $class_auth;

	if (! $auth->init()) {
		// TODO: make error
		lcm_log("pass change: failed auth init, signal 'internal error'.");
	}

	if ($author_session['status'] == 'admin') {
		// If admin, do not check existing password (reset pass for users)
		if ($usr['usr_new_passwd'] == $usr['usr_retype_passwd']) {
			$result = $auth->newpass($usr['username'], $usr['usr_new_passwd'], $author_session);
			lcm_log("pass change: result = " . $result);
		} else {
			// TODO: Make error: passwords do not match
		}
	} else {
		// If user, confirm existing password before changing it
		$valid_oldpass = false;

		// Try to validate with the MD5s
		if (isset($_REQUEST['session_password_md5']) &&
			isset($_REQUEST['next_session_password_md5'])) 
		{
			$valid_oldpass = $auth->validate_md5_challenge($usr['session_password_md5'], $usr['next_session_password_md5']);
		}

		// If it didn't work, fallback on cleartext
		if (! $valid_oldpass) {
			$valid_oldpass = $auth->validate_pass_cleartext($usr['username'], $usr['usr_old_passwd']);
		}

		if ($valid_oldpass) {
			$valid_newpass = $auth->newpass($usr['username'], $usr['usr_new_passwd'], $author_session);

			if (! $valid_newpass) {
				// TODO return error: $this->get_error() ? generic error?
				lcm_log("new pass failed 001");
			}
		} else {
			// TODO: return error: could not validate current password
			lcm_log("pass change: could not validate current password");
		}
	}
}

//
// No errors, update database
//

$fl = "id_author=" . $usr['id_author'] . ",username='" . clean_input($usr['username']) . "'"
. ",name_first='" . clean_input($usr['name_first']) . "'"
. ",name_middle='" . clean_input($usr['name_middle']) . "'"
. ",name_last='" . clean_input($usr['name_last']) . "'"
. ",status='" . clean_input($usr['status']) . "'"
. ",date_update=NOW()";


if ($usr['id_author'] > 0) {
	// Check access rights
	if (($author_session['status'] != 'admin') && ($author_session['id_author'] != $usr['id_author']))
		die("You don't have permission to change author's information!");
	else {
		$q = "UPDATE lcm_author SET $fl WHERE id_author=" . $usr['id_author'];
		$result = lcm_query($q);
	}
} else {
	$q = "INSERT INTO lcm_author SET date_creation=NOW(),$fl";
	$result = lcm_query($q);
	$usr['id_author'] = lcm_insert_id();
}

//
// Insert/update author contacts
//

include_lcm('inc_contacts');

if (isset($_REQUEST['contact_value'])) {
	$cpt = 0;
	$contacts = $_REQUEST['contact_value'];
	$c_types  = $_REQUEST['contact_type'];

	// TODO: update existing information
	// check for doubles, etc.
	// complain if no email_main

	while (isset($contacts[$cpt])) {
		if ($c_types[$cpt] == 'email_main') {
			// We have to check more cases for contacts:
			// - does the author already have this contact type
			// - update or insert?
			// - for e-mail: is address unique?
			if (! is_existing_contact('author', $usr['id_author'], 'email_main', $contacts[$cpt])) { // [AG] Could we use $usr['email_exists'] instead?
				// Not existing as author email_main yet
//				if (is_existing_contact('author', 0, 'email_main', $usr['email'])) // $usr['email'] is set only if author has email_main, which conflicts with the previous condition
				if (is_existing_contact('author', 0, 'email_main', $contacts[$cpt])) {
					// email exists, and is associated to someone else
					// [AG] as it's email_main, so we add nothing to preserve its uniqueness
				} else {
					// Add e-mail as author contact
					add_contact('author', $usr['id_author'], 'email', $contacts[$cpt]);
				}
			} else {
				// update
				lcm_log("update");
			}
		} else {
			// Contact is not primary e-mail address
			lcm_debug("contact type = " . $c_types[$cpt]);
			if ($contacts[$cpt]) {
				// Contact is set to some value
				if (! is_existing_contact('author', $usr['id_author'], $c_types[$cpt], $contacts[$cpt])) {
					// New contact, add
					add_contact('author', $usr['id_author'], $c_types[$cpt], $contacts[$cpt]);
				} else {
					// Existing contact, update
					lcm_log("update " . $c_types[$cpt] . ' to ' . $contacts[$cpt]);
				}
			} else {
				// Empty contact value. Maybe delete the contact?
			}
		}

		$cpt++;
	}
}

session_destroy();

// [ML] Added this because 1- people can bookmark a page 2- easier for testing
if (isset($usr['ref_edit_author']) && $usr['ref_edit_author'])
	header('Location: ' . $usr['ref_edit_author']);
else
	header('Location: edit_author.php?author=' . $usr['id_author']);

?>
