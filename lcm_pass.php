<?php

include('inc/inc_version.php');

include_lcm('inc_meta');
include_lcm('inc_presentation');
include_lcm('inc_session');
include_lcm('inc_filters');
include_lcm('inc_text'); // what for?
include_lcm('inc_mail');
include_lcm('inc_access'); // may be limited to only a few parts

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

// Send a cookie by e-mail
if ($user_email) {
	if (is_valid_email($user_email)) {
		$email = addslashes($user_email);
		$res = lcm_query("SELECT * FROM lcm_author WHERE email ='$email'");
		if ($row = spip_fetch_array($res)) {
			if ($row['status'] == 'trash' OR $row['pass'] == '')
				$error = _T('pass_erreur_acces_refuse');
			else {
				$cookie = creer_uniqid();
				lcm_query("UPDATE lcm_author SET cookie_recall = '$cookie' WHERE email ='$email'");

				$nom_site_spip = lire_meta("nom_site");
				$adresse_site = lire_meta("adresse_site");

				$message = _T('pass_mail_passcookie', array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site, 'cookie' => $cookie));
				if (send_email($email, "[$nom_site_spip] "._T('pass_oubli_mot'), $message))
					$error = _T('pass_recevoir_mail');
				else
					$error = _T('pass_erreur_probleme_technique');
			}
		}
		else
			$error = _T('pass_erreur_non_enregistre', array('user_email' => htmlspecialchars($user_email)));
	}
	else
		$error = _T('pass_erreur_non_valide', array('user_email' => htmlspecialchars($user_email)));
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

	echo "<p>" . _T('pass_info_why_register') . "</p>\n";

	if ($email && ($name_first || $name_last)) {
		$query = "SELECT * FROM lcm_author WHERE email='".addslashes($email)."'"; // XXX
		$result = lcm_query($query);

		$res = "<div class='reponse_formulaire'>";

		// Test if the user already exists
	 	if ($row = lcm_fetch_array($result)) {
			$id_auteur = $row['id_author'];
			$statut = $row['status'];

			unset ($continue);
			if ($statut == 'trash')
				$res .= "<b>"._T('form_forum_access_refuse')."</b>";
			else if ($statut == 'nouveau') {
				lcm_query("DELETE FROM spip_auteurs WHERE id_auteur=$id_auteur");
				$continue = true;
			} else
				$res .= "<b>"._T('form_forum_email_deja_enregistre')."</b>";
		} else
			$continue = true;

		// Send identifiers by e-mail
		if ($continue) {
			include_lcm('inc_access');
			$pass = creer_pass_aleatoire(8, $mail_inscription);
			$login = test_login($mail_inscription);
			$mdpass = md5($pass);
			lcm_query("INSERT INTO lcm_author (name_first, name_middle, name_last, username, password, status) ". "VALUES ('".addslashes($name_first)."', '".addslashes($name_middle)."', '".addslashes($name_last)."', '$username', '$mdpass', '$status')");
			// TODO: e-mail
			ecrire_acces();

			$site_name = read_meta("site_name");
			$adresse_site = read_meta("adresse_site"); // XXX

			$message = _T('form_forum_message_auto')."\n\n"._T('form_forum_bonjour')."\n\n";
			if ($type == 'forum') {
				$message .= _T('form_forum_voici1', array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site)) . "\n\n";
			}
			else {
				$message .= _T('form_forum_voici2', array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site)) . "\n\n";
			}
			$message .= "- "._T('form_forum_login')." $login\n";
			$message .= "- "._T('form_forum_pass')." $pass\n\n";

			if (send_email($mail_inscription, "[$nom_site_spip] "._T('form_forum_identifiants'), $message)) {
			  $res .=  _T('form_forum_identifiant_mail');
			}
			else {
				$res .= _T('form_forum_probleme_mail');
			}
		}
		$res .= "</div>";
	} else {
		// Show form to enter mail
		$link = new Link;
		$url = $link->getUrl();
		$url = quote_amp($url);

	  	echo "<form method='get' action='$url' style='border: 0px; margin: 0px;'>\n";

		echo "<fieldset><label><b>". _T('info_your_contact_information') . "</b><br></label>\n";
		echo "<b>". _T('info_name_of_person') . "</b><br>\n";

		echo "<table border='0'>\n";
		echo "<tr>\n";
		echo "<td><small><label for='name_first'>" . _T('enter_name_first') .  "</label></small></td>\n";
		echo "<td><small><label for='name_middle'>" . _T('enter_name_middle') . "</label></small></td>\n";
		echo "<td><small><label for='name_last'>" . _T('enter_name_last') . "</label></small></td>\n";
		echo "</tr><tr>\n";
		echo "<td><input type='text' id='name_first' name='name_first' class='formo' value='$name_first' size='20'></td>\n";
		echo "<td><input type='text' id='name_middle' name='name_middle' class='formo' value='$name_middle' size='20'></td>\n";
		echo "<td><input type='text' id='name_last' name='name_last' class='formo' value='$name_last' size='20'></td>\n";
		echo "<tr>\n";
		echo "</table>\n";

		echo "<b><label for='email'>" . _T('input_email') . "</label></b><br>";
		echo "<input type='text' id='email' name='email' class='formo' value=\"$email\" size='40'></fieldset><p>\n";

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
