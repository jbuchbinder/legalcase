<?php

include('inc/inc_version.php');

include_lcm('inc_meta');
include_lcm('inc_presentation');
include_lcm('inc_session');
// include_lcm('inc_filters');
// include_lcm('inc_text'); // what for?
// include_lcm('inc_mail');
// include_lcm('inc_access'); // may be limited to only a few parts


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

use_language_of_site();
use_language_of_visitor();

$open_subscription = read_meta("site_open_subscription");
unset($error);

// Using the cookie (which was sent by e-mail) to reset the password
if ($p = addslashes($p)) {
	$pass_forgotten = 'yes';

	$res = lcm_query("SELECT * 
				FROM lcm_author
				WHERE cookie_recall='$p' AND status <> 'trash' AND password <> ''");

	if ($row = lcm_fetch_array($res)) {
		if ($pass) {
			$mdpass = md5($pass);

			lcm_query("UPDATE lcm_author
				SET cookie_recall = '', password='$mdpass', alea_actuel=''
				WHERE cookie_recall = '$p'");

			$username = $row['username'];
			$error = "<b>"._T('pass_nouveau_enregistre')."</b>".
			"<p>"._T('pass_rappel_login', array('username' => $username));
		} else {
			install_html_start(_T('pass_nouveau_pass'), 'login');
			echo "<p><br>";
			echo "<form action='spip_pass.php3' method='post'>";
			echo "<input type='hidden' name='p' value='" . htmlspecialchars($p) . "'>";
			echo _T('pass_choix_pass') . "<br>\n";
			echo "<input type='password' name='pass' value=''>";
			echo '  <input type=submit class="fondl" name="validate" value="'._T('pass_ok').'"></div></form>';
			install_fin_html();
			exit;
		}
	}
	else
		$error = _T('pass_erreur_code_inconnu');
}

// Send a cookie by e-mail which will later allow the user to
// reset his/her password.
if ($user_email) {
	include_lcm('inc_mail');

	if (is_valid_email($user_email)) {
		$user_email = addslashes($user_email);

		// Find the ID + info of the author
		$res = lcm_query("SELECT id_of_person, status, password
					FROM lcm_contact as c, lcm_author as a
					WHERE c.id_of_person = a.id_author
						and type_person = 'author' 
						and value ='$user_email' 
						and type_contact = 1");
		if ($row = lcm_fetch_array($res)) {
			if ($row['status'] == 'trash' OR $row['password'] == '')
				// TODO
				$error = _T('pass_error_acces_refuse');
			else if ($row['status'] == 'waiting') {
				// TODO
				$error = _T('pass_error_waiting_moderator');
			} else {
				$cookie = create_uniq_id();
				lcm_query("UPDATE lcm_author 
					SET cookie_recall = '$cookie'
					WHERE id_author ='" . $row['id_of_contact'] . "'");

				$site_name = read_meta('site_name');
				$site_address = read_meta('site_address');

				$message = _T('pass_mail_cookie1') . "\n";
				$message .= $site_name . " (" . $site_address . ")\n\n";
				$message .= _T('pass_mail_cookie2') . "\n";
				$message .= "    " . $site_address . "/lcm_pass.php?p=" .  $cookie .  "\n\n";
				$message .= _T('pass_mail_cookie3') . "\n";

				if (send_email($user_email, "[$site_name] "._T('pass_title_forgotten_password'), $message)) {
					$error = _T('pass_info_receive_mail');
				} else {
					$email_admin = meta_read('email_sysadmin');
					$error = "<div class=\"box_error\"><p>" 
						.  _T('pass_warning_mail_failure', array('email_admin' => $email_admin))
						. "</p></div>\n";
				}
			}
		}
		else {
			$error = _T('pass_erreur_non_enregistre', array('user_email' => htmlspecialchars($user_email)));
		}
	} else {
		$error = _T('pass_erreur_non_valide', array('user_email' => htmlspecialchars($user_email)));
	}
}

if ($pass_forgotten == 'yes') {
	install_html_start(_T('pass_title_forgotten_password'), 'login');

	if ($error)
		echo "<p>" . $error . "</p>\n";
	else {
		echo "<p>" . _T('pass_info_enter_email') . "</p>\n";

		echo "<form action='lcm_pass.php' method='post'>\n";
		echo "<div align='right'>\n";
		echo "<input type='text' class='fondo' name='user_email' value=''>\n";
		echo "<input type='hidden' name='pass_forgotten' value='yes'>\n";
		echo "<input type=submit class='fondl' name='validate' "
			. "value='" . _T('button_validate') . "'>\n";
		echo "</div>\n</form>\n";
	}
} else if ($open_subscription == 'yes' || $open_subscription == 'moderated') {
	install_html_start(_T('pass_title_register'), 'login');

	if ($email) {
		// There is a risk that an author changes his e-mail
		// after his account is created, to the e-mail of another
		// person, and therefore block the other person from 
		// registering. But then.. this would allow the other person
		// to hijack the account, so it would be a stupid DoS.
		$query = "SELECT id_of_person, status FROM lcm_contact as c, lcm_author as a
					WHERE c.id_of_person = a.id_author
					AND value=\"".addslashes($email)."\"
					AND type_person = 'author'
					AND type_contact = 1"; // XXX
		$result = lcm_query($query);

		echo "<div>\n";

		// Test if the user already exists
	 	if ($row = lcm_fetch_array($result)) {
			$id_author = $row['id_of_person'];
			$status = $row['status'];

			unset ($continue);
			if ($status == 'trash')
				echo "<b>" . _T('pass_registration_denied') . "</b>\n";
			else if ($status == 'nouveau') {
				lcm_query("DELETE FROM lcm_author WHERE id_author=$id_author");
				$continue = true;
			} else
				echo "<b>" . _T('pass_already_registered') . "</b>";
		} else {
			$continue = true;
		}

		// Send identifiers by e-mail
		if ($continue) {
			include_lcm('inc_access');
			include_lcm('inc_mail');

			$username = get_unique_username($username);
			$pass = create_random_password(8, $username);
			$mdpass = md5($pass);

			// TODO: If subscriptions moderated, send cookie + email to sysadmin
			lcm_query("INSERT INTO lcm_author (name_first, name_last, username, password, status) "
				. "VALUES ('".addslashes($name_first)."', '".addslashes($name_last)."', '$username', '$mdpass', 'normal')");

			$id_author = lcm_insert_id();

			// Add e-mail to lcm_contact
			lcm_query("INSERT INTO lcm_contact (type_person, type_contact, id_of_person, value)
						VALUES ('author', 1, $id_author, '" .  addslashes($email) . "')");

			// Prepare the e-mail to send to the user
			$site_name = read_meta('site_name');
			$site_address = read_meta('site_address');

			// This is only a last resort solution. It is not info
			// which can be trusted, and could be abused.
			if (! $site_address)
				$site_address = $GLOBALS['fallback_site_address']; // TODO

			$message = _T('pass_info_automated_msg') . "\n\n";
			$message .= _T('info_greetings') . ",\n\n";
			$message .= _T('pass_info_here_info', array('site_name' => $site_name, 'site_address' => $site_address)) . "\n\n";
			$message .= "- "._T('login_login') . _T('typo_column') . " $username\n";
			$message .= "- "._T('login_password') . _T('typo_column') . " $pass\n\n";

			if (send_email($email, "[$site_name] " . _T('pass_title_personal_identifier'), $message)) {
				echo "<p>" . _T('pass_info_identifier_mail') . "</p>\n";
			} else {
				$email_admin = meta_read('email_sysadmin');
				echo "<div class=\"box_error\"><p>" 
					.  _T('pass_warning_mail_failure', array('email_admin' => $email_admin))
					. "</p></div>\n";
			}
		}
		echo "</div>";
	} else {
		// Show form to enter mail
		$link = new Link;
		$url = $link->getUrl();
		$url = quote_amp($url);

		echo "<p>" . _T('pass_info_why_register') . "</p>\n";

	  	echo "<form method='get' action='$url' style='border: 0px; margin: 0px;'>\n";

		echo "<fieldset><label><b>". _T('info_your_contact_information') . "</b><br></label>\n";
		echo "<b>". _T('info_name_of_person') . "</b><br>\n";

		echo "<table border='0'>\n";
		echo "<tr>\n";
		echo "<td><small><label for='name_first'>" . _T('enter_name_first') .  "</label></small></td>\n";
		// echo "<td><small><label for='name_middle'>" . _T('enter_name_middle') . "</label></small></td>\n";
		echo "<td><small><label for='name_last'>" . _T('enter_name_last') . "</label></small></td>\n";
		echo "</tr><tr>\n";
		echo "<td><input type='text' id='name_first' name='name_first' class='formo' value='$name_first' size='20'></td>\n";
		// echo "<td><input type='text' id='name_middle' name='name_middle' class='formo' value='$name_middle' size='20'></td>\n";
		echo "<td><input type='text' id='name_last' name='name_last' class='formo' value='$name_last' size='20'></td>\n";
		echo "<tr>\n";
		echo "</table>\n";

		echo "<b><label for='email'>" . _T('input_email') . "</label></b><br>";
		echo "<input type='text' id='email' name='email' class='formo' value=\"$email\" size='40'></fieldset><p>\n";

		echo "<fieldset><b>" . _T('input_connection_identifiers') . "</b><br/>";
		echo "<b><label for='username'>" . _T('login_login') . "</label></b><br>";
		echo "<small>" . _T('info_more_than_three') . "</small><br>";
		echo "<input type='text' id='username' name='username' class='formo' value=\"$username\" size='40'><p>\n";
	
		/*
		echo "<b><label for='pass'>" . _T('login_password') . "</label></b><br>";
		echo "<small>" . _T('pass_more_than_5_or_random')."</small><br>";
		echo "<input type='password' id='pass' name='pass' class='formo' value=\"$pass\" size='40'><p>\n";

		echo "<b><label for='pass2'>" . _T('login_password_confirm') . "</label></b><br>";
		echo "<small>" . _T('info_password_confirm')."</small><br>";
		echo "<input type='password' id='pass2' name='pass2' class='formo' value=\"$pass\" size='40'><p>\n";
		*/

		echo "<small>" . "Your password will be sent to you by e-mail." .  "</small>\n";
		echo "</fieldset>\n";

		echo "<div align=\"right\"><input type=\"submit\" name='Validate' class='fondl' value=\""._T('button_validate')."\" /></div>";
		echo "</form>\n";
	}
} else {
	install_html_start(_T('title_error'), 'login');
	echo "<div class='box_error'>\n";
	echo "<p>" . _T('pass_warning_no_action') . "</p>";
	echo "</div>\n";
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
