<?php

include('inc/inc_version.php');

include_lcm('inc_meta');
include_lcm('inc_presentation');
include_lcm('inc_session');
include_lcm('inc_filters');
// include_lcm('inc_texte');
include_lcm('inc_meta');
include_lcm('inc_mail');
include_lcm('inc_access');

// include_local('inc-formulaires.php3');

use_language_of_site();
use_language_of_visitor();

$open_subscribtion = (lire_meta("site_open_subscribtion") == "yes") ;
unset($error);

// Using the cookie (which was sent by e-mail), reset the password
if ($p = addslashes($p)) {
	$pass_forgotten = 'yes';

	$res = lcm_query("SELECT * 
				FROM lcm_author
				WHERE cookie_recall='$p' AND status<>'trash' AND password<>''");

	if ($row = lcm_fetch_array($res)) {
		if ($pass) {
			$mdpass = md5($pass);
			$htpass = generer_htpass($pass);

			lcm_query("UPDATE lcm_author
				SET htpass='$htpass', password='$mdpass', alea_actuel='', cookie_recall = ''
				WHERE cookie_recall = '$p'");

			$login = $row['login'];
			$error = "<b>"._T('pass_nouveau_enregistre')."</b>".
			"<p>"._T('pass_rappel_login', array('login' => $login));
		} else {
			install_html_start(_T('pass_nouveau_pass'));
			echo "<p><br>";
			echo "<form action='spip_pass.php3' method='post'>";
			echo "<input type='hidden' name='p' value='".htmlspecialchars($p)."'>";
			echo _T('pass_choix_pass')."<br>\n";
			echo "<input type='password' name='pass' value=''>";
			echo '  <input type=submit class="fondl" name="oubli" value="'._T('pass_ok').'"></div></form>';
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
				lcm_query("UPDATE spip_auteurs SET cookie_oubli = '$cookie' WHERE email ='$email'");

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
	install_html_start(_T('pass_title_forgotten_password'));

	if ($error)
		echo "<p>" . $error . "</p>\n";
	else {
		echo _T('pass_info_enter_email');

		echo "<p>";
		echo '<form action="lcm_pass.php" method="post">';
		echo '<div align="right">';
		echo '<input type="text" class="fondo" name="user_email" value="">';
		echo '<input type="hidden" name="pass_forgotten" value="yes">';
		echo '<input type=submit class="fondl" name="oubli" value="'._T('button_validate').'"></div></form>';
	}
} else if ($open_subscription) {
	// debut presentation
	install_html_start(_T('pass_vousinscrire'));
	echo "<p>";

	if ($open_subscription)
		echo _T('pass_espace_prive_bla');
	else
		echo _T('pass_forum_bla');
	echo "\n<p>";

	echo formulaire_inscription(($open_subscription)? 'redac' : 'forum');
}
else {
	install_html_start(_T('pass_erreur'));
	echo "<p>" . _T('pass_rien_a_faire_ici') . "</p>";
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
