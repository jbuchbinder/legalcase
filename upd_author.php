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

	$Id: upd_author.php,v 1.21 2005/03/22 08:39:21 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_filters');

function force_session_restart($id_author) {
	include_lcm('inc_session');
	global $author_session, $lcm_session;

	zap_sessions($id_author, true);
	if ($author_session['id_author'] == $id_author) {
		lcm_debug("lcm_session = " . $lcm_session);
		delete_session($lcm_session);
	} else {
		lcm_debug("I am ID = " . $author_session['id_author']);
	}
}

function change_password($usr) {
	global $author_session;

	// FIXME: include auth type according to 'auth_type' field in DB
	// default on 'db' if field not present/set.
	$class_auth = 'Auth_db';
	include_lcm('inc_auth_db');

	$auth = new $class_auth;

	if (! $auth->init()) {
		lcm_log("pass change: failed auth init: " . $auth->error);
		$_SESSION['errors']['password_generic'] = $auth->error;
		return;
	}
	
	// Is user allowed to change the password?
	if (! $auth->is_newpass_allowed($usr['id_author'], $usr['username'], $author_session)) {
		$_SESSION['errors']['password_generic'] = $auth->error;
		return;
	}

	// Confirm current password only if user is not admin
	// (this also applies to the creation of new authors, only admins can do that)
	if ($author_session['status'] != 'admin') {
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

		if (! $valid_oldpass) {
			$_SESSION['errors']['password_current'] = _T('pass_warning_incorrect');
			return;
		}
	}

	// Confirm matching passwords
	if ($usr['usr_new_passwd'] != $usr['usr_retype_passwd']) {
		$_SESSION['errors']['password_confirm'] = _T('login_warning_password_dont_match');
		return;
	}

	// Change the password
	$ok = $auth->newpass($usr['id_author'], $usr['username'], $usr['usr_new_passwd'], $author_session);

	if (! $ok) {
		lcm_log("New pass failed: " . $auth->error);
		$_SESSION['errors']['password_confirm'] = $auth->error;
		return;
	}
}

function change_username($id_author, $old_username, $new_username) {
	global $author_session;

	include_lcm('inc_auth_db');
	$class_auth = 'Auth_db'; // FIXME, take from author_session
	$auth = new $class_auth;

	if (! $auth->init()) {
		lcm_log("username change: failed auth init, signal 'internal error'.");
		$_SESSION['errors']['password_generic'] = $auth->error;
		return;
	}

	// Change the username
	$ok = $auth->newusername($id_author, $old_username, $new_username, $author_session);

	if (! $ok) {
		lcm_log("New username failed: " . $auth->error);
		$_SESSION['errors']['username'] = _T('login_warning_password_change_failed') . ' ' . $auth->error;
		$_SESSION['usr']['username'] = $new_username;

		return;
	}

	force_session_restart($id_author);
}

// Clear all previous errors
$_SESSION['errors'] = array();

// Get form data from POST fields
foreach($_POST as $key => $value)
    $usr[$key] = $value;

//
// Basic security check: Non-admins can only edit themselves.
// Admins can edit any author.
//
if ($usr['id_author'] != $author_session['id_author'])
	if ($author_session['status'] != 'admin')
		die("Only administrators can edit other authors");

//
// Start SQL query
//
$fl = 'date_update = NOW()';

// First name must have at least one character
if (strlen(lcm_utf8_decode($usr['name_first'])) < 1) {
	$_SESSION['errors']['name_first'] = _T('person_input_name_first') . ' ' . _T('warning_field_mandatory');
	$_SESSION['usr']['name_first'] = $usr['name_first'];
} else {
	$fl .= ", name_first = '" . clean_input($usr['name_first'])  . "'";
}

// Middle name can be empty
$fl .= ", name_middle = '" . clean_input($usr['name_middle']) . "'";

// Last name must have at least one character
if (strlen(lcm_utf8_decode($usr['name_last'])) < 1) {
	$_SESSION['errors']['name_last'] = _T('person_input_name_last') . ' ' . _T('warning_field_mandatory');
	$_SESSION['usr']['name_last'] = $usr['name_last'];
} else {
	$fl .= ", name_last = '" . clean_input($usr['name_last'])  . "'";
}

// Author status can only be changed by admins
if ($author_session['status'] == 'admin')
	$fl .= ", status = '" . clean_input($usr['status'])      . "'";

if ($usr['id_author'] > 0) {
	$q = "UPDATE lcm_author 
			SET $fl 
			WHERE id_author = " . $usr['id_author'];
	$result = lcm_query($q);
} else {
	// Keep form information in session, just in case there is an error
	// now or later (username/pass).
	/* [ML] It is already saved
	foreach($usr as $key => $value)
		$_SESSION['usr'][$key] = $value;
	*/

	if (count($errors)) {
    	header("Location: edit_author.php?author=0");
		exit;
	}

	$q = "INSERT INTO lcm_author SET date_creation = NOW(), $fl";
	$result = lcm_query($q);
	$usr['id_author'] = lcm_insert_id();
	$_SESSION['usr']['id_author'] = $usr['id_author'];
}

//
// Change password (if requested)
//

if ($usr['usr_new_passwd'] || empty($usr['username_old']))
	change_password($usr);

//
// Change username
//

if ($usr['username'] != $usr['username_old'] || empty($usr['username_old']))
	change_username($usr['id_author'], $usr['username_old'], $usr['username']);

//
// Insert/update author contacts
//

include_lcm('inc_contacts');

//
// Update existing contacts
//
if (isset($_REQUEST['contact_value'])) {
	$contacts = $_REQUEST['contact_value'];
	$c_ids = $_REQUEST['contact_id'];
	$c_types = $_REQUEST['contact_type'];
	// $c_delete = $_REQUEST['del_contact'];

	//
	// Check if the contacts provided are really attached to the author
	// or else the author can provide a form with false contacts.
	//
	$all_contacts = get_contacts('author', $usr['id_author']);
	for ($cpt = 0; $c_ids[$cpt]; $cpt++) {
		$valid = false;

		foreach ($all_contacts as $c)
			if ($c['id_contact'] == $c_ids[$cpt])
				$valid = true;

		if (! $valid)
			die("Invalid modification of contacts detected.");
	}

	for ($cpt = 0; isset($c_ids[$cpt]); $cpt++) {
		if (isset($_REQUEST['del_contact_' . $c_ids[$cpt]]) && $_REQUEST['del_contact_' . $c_ids[$cpt]]) {
			delete_contact($c_ids[$cpt]);
		} else {
			// Check for doubles, etc. -> the hell with it! [ML] 2005-01-18
			update_contact($c_ids[$cpt], $contacts[$cpt]);
		}
	}
}

//
// New contacts
//
if (isset($_REQUEST['new_contact_value'])) {
	$cpt = 0;
	$new_contacts = $_REQUEST['new_contact_value'];
	$c_type_names = $_REQUEST['new_contact_type_name'];

	while (isset($new_contacts[$cpt])) {
		// Process only new contacts which have a value
		if ($new_contacts[$cpt]) {
			// And make sure that they have a "type of contact"
			if ($c_type_names[$cpt]) {
				add_contact('author', $usr['id_author'], $c_type_names[$cpt], $new_contacts[$cpt]);
			} else {
				$_SESSION['errors']['new_contact_' . $cpt] = "Please specify the type of contact.";
				$_SESSION['usr']['new_contact_' . $cpt] = $new_contacts[$cpt];
			}
		}

		$cpt++;
	}
}

// There were errors, send user back to form
// Note: Important to send back to edit_author, not HTTP_REFERER, because
// if we are creating a new author, we must send to 'edit', not 'new'.
if (count($_SESSION['errors'])) {
    header("Location: edit_author.php?author=" . $usr['id_author']);
    exit;
}

$dest_link = new Link('author_det.php');
$dest_link->addVar('author', $usr['id_author']);

// [ML] Not used at the moment, but could be useful eventually to send user
// back to where he was (but as a choice, not automatically, see author_det.php).
if (isset($usr['ref_edit_author']))
	$dest_link->addVar('ref', $usr['ref_edit_author']);

// Delete session (of form data will become ghosts)
$_SESSION['usr'] = array();

header('Location: ' . $dest_link->getUrlForHeader());

?>
