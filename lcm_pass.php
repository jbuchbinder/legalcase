<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

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

	$Id: lcm_pass.php,v 1.27 2006/09/18 15:38:45 mlutfy Exp $
*/

session_start();

include('inc/inc_version.php');

include_lcm('inc_meta');
include_lcm('inc_presentation');
include_lcm('inc_session');
include_lcm('inc_keywords');

// Returns a unique username based on what the user proposed
// For example, if username "joe" exists, it will return "joe1".
function get_unique_username($username) {
	$username = strtolower($username);
	$username = ereg_replace("[^a-zA-Z0-9]", "", $username);

	if (!$username) $username = "user";
	$unique_name = $username;

	for ($i = 0; ; $i++) {
		if ($i)
			$unique_name = $username.$i;
		else
			$unique_name = $username;

		$query = "SELECT id_author FROM lcm_author WHERE username='$unique_name'";
		$result = lcm_query($query);

		if (!lcm_num_rows($result)) break;
	}

	return $unique_name;
}

// If only a cookie (p) was provided, show the form to reset the password
// If a cookie + password were provided, reset the password.
function reset_pass($my_p, $my_password = 0) {
	$my_p = clean_input($my_p);
	$my_pass_forgotten = 'yes';
	$errors = array();

	if ($my_password) {
		$res = lcm_query("SELECT id_author, username 
				FROM lcm_author
				WHERE cookie_recall='$my_p' AND status <> 'trash' AND password <> ''");

		if ($row = lcm_fetch_array($res)) {
			$usr['id_author'] = $row['id_author'];
			$usr['username'] = $row['username'];
		} else {
			install_html_start(_T('pass_title_new_pass'), 'login');
			echo "<div class='box_error'><p>" . _T('pass_warning_unknown_cookie') . "</p></div>\n";
			return;
		}

		// FIXME: include auth type according to 'auth_type' field in DB
		// default on 'db' if field not present/set.
		// NOTE: author_session is not available here, since not logged in.
		$class_auth = 'Auth_db';
		include_lcm('inc_auth_db');

		$auth = new $class_auth;

		if ($auth->init()) {
			$ok = $auth->newpass($usr['id_author'], $usr['username'], $my_password);
			if (! $ok) $errors['password_generic'] = $auth->error;
		} else {
			lcm_log("pass change: failed auth init, signal 'internal error'.");
			$errors['password_generic'] = $auth->error;
		}
	}

	if (count($errors) || ! $my_password) {
		install_html_start(_T('pass_title_new_pass'), 'login');

		include_lcm('inc_filters');
		echo show_all_errors($errors);
		
		echo "<form name='form_newpass' id='form_newpass' action='lcm_pass.php' method='post'>\n";
		echo "<input type='hidden' name='p' value='" . htmlspecialchars($my_p) . "' />\n";
		echo '<fieldset><p><label for="password">' . _T('pass_enter_new_pass') .  _T('typo_column') . "</label><br/>\n";
		echo "<input type='password' class='fondo' name='password' id='password' size='30' value='' /> ";
		echo '<input type="submit" class="fondl" name="validate" value="' . _T('button_validate') . "\" />\n";
		echo "</fieldset>\n";
		echo "</p></form>\n";

		echo "<script type=\"text/javascript\"><!--
				document.form_newpass.password.focus();
				//--></script>\n";
	} else {
		install_html_start(_T('pass_info_pass_updated'), 'login');
	}
}

// Send a cookie by e-mail which will later allow the user to
// reset his/her password.
function send_cookie_by_email($my_email) {
	global $system_kwg;
	include_lcm('inc_mail');

	install_html_start(_T('pass_title_forgotten_password'), 'login');

	if (! is_valid_email($my_email)) {
		echo "<p>" . _T('pass_warning_invalid_email', array('user_email' => htmlspecialchars($my_email))) . "</p>\n";
		return;
	}

	$my_email = clean_input($my_email);
	$kwg_email = get_kwg_from_name('+email_main');

	// Find the ID + info of the author
	$res = lcm_query("SELECT id_of_person, username, status, password
			FROM lcm_contact as c, lcm_author as a
			WHERE c.id_of_person = a.id_author
			and type_person = 'author' 
			and value ='$my_email' 
			and type_contact = " . $kwg_email['id_group']);
	
	$row = lcm_fetch_array($res);

	if (! $row) {
		echo "<p>" . _T('pass_warning_not_registered', array('user_email' => htmlspecialchars($my_email))) . "</p>\n";
		return;
	}

	if ($row['status'] == 'trash' OR $row['password'] == '') {
		// TODO TRAD
		echo "<p>" . _T('pass_error_acces_refuse') . "</p>";
	} else if ($row['status'] == 'waiting') {
		// TODO TRAD
		echo "<p>" . _T('pass_error_waiting_moderator') . "</p>";
	} else if ($row['id_of_person']) {
		$cookie = create_uniq_id();
		lcm_query("UPDATE lcm_author 
				SET cookie_recall = '$cookie'
				WHERE id_author = " . $row['id_of_person']);

		$site_name = _T(read_meta('site_name'));
		$site_address = read_meta('site_address');

		$message  = _T('pass_mail_cookie1') . "\n";
		$message .= $site_name . " (" . $site_address . ")\n\n";
		$message .= _T('pass_mail_cookie2') . "\n";
		$message .= "    " . $site_address . "/lcm_pass.php?p=" .  $cookie .  "\n\n";
		$message .= _T('pass_mail_cookie3') . "\n";
		$message .= _T('pass_info_remind_username', array('login' => $row['username'])) . "\n";

		if (send_email($my_email, "[$site_name] "._T('pass_title_forgotten_password'), $message)) {
			echo "<p>" . _T('pass_info_receive_mail') . "</p>";
		} else {
			$email_admin = meta_read('email_sysadmin');
			echo "<div class=\"box_error\"><p>" 
				.  _T('pass_warning_mail_failure', array('email_admin' => $email_admin))
				. "</p></div>\n";
		}
	} else {
		lcm_panic("Missing id_of_person for " .  $my_email);
	}
}

function print_pass_forgotten_form() {
	install_html_start(_T('pass_title_forgotten_password'), 'login');

	echo "<p>" . _T('pass_info_enter_email') . "</p>\n";

	echo "<form action='lcm_pass.php' name='form_forgotten' id='form_forgotten' method='post'>\n";
	echo "<input type='hidden' name='pass_forgotten' value='yes' />\n";
	echo "<input type='text' class='txt_lmnt' size='40' name='user_email' value='' />\n";
	echo "<button type='submit' class='simple_form_btn' name='validate'>" . _T('button_validate') . "</button>\n";

	echo "</form>\n";

	echo "<script type=\"text/javascript\"><!--
			document.form_forgotten.user_email.focus();
			//--></script>\n";
}

function send_registration_by_email() {
	global $lcm_lang_left;

	$_SESSION['form_data'] = array();
	$_SESSION['errors'] = array();

	$kwg_email = get_kwg_from_name('+email_main');

	$form_items = array (
		'name_first' => 'person_input_name_first',
		'name_last'  => 'person_input_name_last',
		'email'      => 'input_email',
		'username'   => 'authoredit_input_username'
	);

	foreach ($form_items as $field => $trad) {
		$_SESSION['form_data'][$field] = _request($field);

		if (! _session($field))
			$_SESSION['errors'][$field] = _Ti($trad) . _T('warning_field_mandatory');
	}

	if (count($_SESSION['errors'])) {
		lcm_header("Location: lcm_pass.php?register=yes");
		exit;
	}

	install_html_start(_T('pass_title_register'), 'login');

	// There is a risk that an author changes his e-mail after his account
	// is created, to the e-mail of another person, and therefore block the
	// other person from registering. But then.. this would allow the other
	// person to hijack the account, so it would be a stupid DoS.
	$query = "SELECT id_of_person, status FROM lcm_contact as c, lcm_author as a
		WHERE c.id_of_person = a.id_author
		AND value = '" . _session('email') . "'
		AND type_person = 'author'
		AND type_contact = " . $kwg_email['id_group'];

	$result = lcm_query($query);

	// Test if the user already exists
	if ($row = lcm_fetch_array($result)) {
		$id_author = $row['id_of_person'];
		$status = $row['status'];

		// TODO: if status = 'pending for validation by admin', show message
		if ($status == 'trash') {
			echo "<br />\n";
			echo "<div class='box_error'>" . _T('pass_registration_denied') . "</div>\n";
		} else {
			echo "<br />\n";
			echo "<div class=\"box_error\" align=\"$lcm_lang_left\">" . _T('pass_warning_already_registered') . "</div>\n";
			return;
		}
	}

	//
	// Send identifiers by e-mail
	//
	include_lcm('inc_access');
	include_lcm('inc_mail');

	$username = get_unique_username(_session('username'));
	$pass = create_random_password(8, $username);
	$mdpass = md5($pass);

	$open_subscription = read_meta("site_open_subscription");

	if (! ($open_subscription == 'yes' || $open_subscription == 'moderated'))
		lcm_panic("Subscriptions not permitted.");

	$status = 'waiting';

	if ($open_subscription == 'yes')
		$status = 'normal';

	lcm_query("INSERT INTO lcm_author (name_first, name_last, username, password, status, date_creation, date_update) "
			. "VALUES ('" . _session('name_first') . "', '" . _session('name_last') . "', '$username', '$mdpass', 'normal', NOW(), NOW())");

	$id_author = lcm_insert_id('lcm_author', 'id_author');

	// Add e-mail to lcm_contact
	lcm_query("INSERT INTO lcm_contact (type_person, type_contact, id_of_person, value)
			VALUES ('author', " . $kwg_email['id_group'] . ", $id_author, '" .  _session('email') . "')");

	// Prepare the e-mail to send to the user
	$site_name = _T(read_meta('site_name'));
	$site_address = read_meta('site_address');

	$message = _T('info_greetings') . ",\n\n";
	$message .= _T('pass_info_here_info', array('site_name' => $site_name, 'site_address' => $site_address)) . "\n\n";
	$message .= "- ". _Ti('login_login') . " $username\n";
	$message .= "- ". _Ti('login_password') . " $pass\n\n";

	if ($open_subscription == 'moderated')
		$message .= _T('pass_info_moderated') . "\n\n";

	$message .= _T('pass_info_automated_msg') . "\n\n";

	if (send_email(_session('email'), "[$site_name] " . _T('pass_title_personal_identifier'), $message)) {
		echo "<p>" . _T('pass_info_identifier_mail') . "</p>\n";
	} else {
		$email_admin = read_meta('email_sysadmin');
		echo "<div class=\"box_error\"><p>" 
			.  _T('pass_warning_mail_failure', array('email_admin' => $email_admin))
			. "</p></div>\n";
	}

	// If moderated, send copy to site admin
	if ($open_subscription == 'moderated') {
		$email_admin = read_meta('email_sysadmin');

		send_email($email_admin, "[$site_name] " . _T('pass_title_personal_identifier'), $message);
	}
}

// Show form to enter mail
function print_registration_form() {
	install_html_start(_T('pass_title_register'), 'login');

	$link = new Link;
	$url = $link->getUrl();

	echo '<p align="left" class="normal_text">' . _T('pass_info_why_register') . "</p>\n";

	echo show_all_errors();
	
	echo "<form method='post' action='$url' style='border: 0px; margin: 0px;'>\n";
	echo '<input type="hidden" name="register" value="data" />' . "\n";

	echo "<fieldset><label><b>". _T('info_your_contact_information') . "</b><br></label>\n";

	// [ML] Altough not most problematic, could be better. But if someone
	// fixes here, please fix install.php also (step 4)
	echo "<table border='0'>\n";
	echo "<tr>\n";
	echo "<td>
			<label for='name_first'>" . f_err_star('name_first') . _Ti('person_input_name_first') . "</label><br />
			<input type='text' style='width: 100%;' id='name_first' name='name_first' class='formo' value='" . _session('name_first') . "' size='20'>
		</td>\n";
	echo "<td>
			<label for='name_last'>" . f_err_star('name_last') . _Ti('person_input_name_last') . "</label><br />
			<input type='text' style='width: 100%;' id='name_last' name='name_last' class='formo' value='" . _session('name_last') . "' size='20'>
		</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>";

	echo "<p><label for='email'>" . f_err_star('email') . _Ti('input_email') . "</label><br />";
	echo "<input type='text' id='email' name='email' class='formo' value='" . _session('email') . "' size='40'></p>\n";

	echo "<p><label for='username'>" . f_err_star('username') . _Ti('authoredit_input_username') . "</label> ";
	echo "<small>" . _T('info_more_than_three') . "</small><br />";
	echo "<input type='text' id='username' name='username' class='formo' value='" . _session('username') . "' size='40'></p>\n";

	echo "<small>" . _T('pass_info_password_by_mail') . "</small>\n";
	echo "</fieldset>\n";

	echo "<p align=\"right\">";
	echo '<button type="submit" name="Validate">' . _T('button_validate') . "</button>";
	echo "</p>";

	echo "</form>\n";

	$_SESSION['form_data'] = array();
	$_SESSION['errors'] = array();
}

use_language_of_site();
use_language_of_visitor();

$open_subscription = read_meta("site_open_subscription");


if (_request('p')) {
	reset_pass(_request('p'), _request('password'));
}

else if (_request('user_email')) {
	send_cookie_by_email(_request('user_email'));
}

else if (($reg = _request('register'))) {
	if ($open_subscription == 'yes' || $open_subscription == 'moderated') {
		if ($reg == 'data')
			send_registration_by_email();
		else 
			print_registration_form();
	} else {
		install_html_start(_T('pass_title_register'), 'login');
		// FIXME ? show error ?
	}
}

else {
	print_pass_forgotten_form();
}

echo "<p align='right'>
	<script type='text/javascript'><!--
		if (window.opener) 
			document.write(\"<a href='javascript:close();'>\");
		else 
			document.write(\"<a href='./'>\");

		document.write(\""._T('pass_close_this_window')."</a>\");
	//--></script>
	<noscript>[<a href='./'>"._T('pass_back_to_site')."</a>]</noscript>
</p>\n";

install_html_end();

?>
