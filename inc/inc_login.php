<?php

if (defined('_INC_LOGIN')) return;
define('_INC_LOGIN', '1');

include('inc/inc_version.php');
include_lcm('inc_meta');
include_lcm('inc_session');
include_lcm('inc_filters');
include_lcm('inc_text');
// include_local ('inc-formulaires');

// Management of HTTP authentication
function auth_http($cible, $essai_auth_http) {
	if ($essai_auth_http == 'oui') {
		include_lcm('inc_session');
		if (!verifier_php_auth()) {
			$url = quote_amp(urlencode($cible->getUrl()));
			$page_erreur = "<b>"._T('login_access_denied')."</b><p />"._T('login_password_incorrect')."<p />[<a href='./'>"._T('login_retour_site')."</a>] [<a href='./lcm_cookie.php?essai_auth_http=oui&amp;url=$url'>"._T('login_nouvelle_tentative')."</a>]";
			ask_php_auth($page_erreur);
		}
		else
			@header("Location: " . $cible->getUrl() );
		exit;
	} else if ($essai_auth_http == 'logout') {
		// request for logout auth_http
		include_lcm('inc_session');
		ask_php_auth("<b>"._T('login_deconnexion_ok')."</b><p />"._T('login_verifiez_navigateur')."<p />[<a href='./'>"._T('login_retour_public')."</a>] [<a href='./lcm_cookie.php?essai_auth_http=oui&amp;redirect=ecrire'>"._T('login_test_navigateur')."</a>] [<a href='ecrire/'>"._T('login_espace_prive')."</a>]");
		exit;
	}
}

function open_login($title='') {
	$text = "<div>";

	if ($title)
		$text .= "<h3>$title</h3>";

	$text .= '<div style="font-family: Verdana,arial,helvetica,sans-serif; font-size: 12px;">';
	return $text;
}

function close_login() {
	$text =  "</div>";
	$text .= "</div>";
	return $text;
}

function login($cible, $prive = 'prive', $message_login='') {
	$pass_popup ='href="lcm_pass.php" target="lcm_pass" onclick="' .  "javascript:window.open('lcm_pass.php', 'lcm_pass', 'scrollbars=yes, resizable=yes, width=480, height=450'); return false;\"";

	$login = $GLOBALS['var_login'];
	$erreur = '';
	$essai_auth_http = $GLOBALS['var_essai_auth_http'];
	$logout = $GLOBALS['var_logout'];

	// If the cookie fails, inc_auth tried to redirect to lcm_cookie who
	// then tried to put a cookie. If it is not there, it is "cookie failed"
	// who is there, and it's probably a bookmark on bonjour=oui and not
	// a cookie failure.
	if ($GLOBALS['var_echec_cookie'])
		$echec_cookie = ($GLOBALS['lcm_session'] != 'test_echec_cookie');

	global $author_session;
	global $lcm_session, $PHP_AUTH_USER, $ignore_auth_http;
	global $lcm_admin;
	global $php_module;
	global $clean_link;

	if (!$cible) {
		if ($GLOBALS['var_url']) $cible = new Link($GLOBALS['var_url']);
		else if ($prive) $cible = new Link('index.php');
		else $cible = $clean_link;
	}

	$cible->delVar('var_erreur');
	$cible->delVar('var_url');
	$clean_link->delVar('var_erreur');
	$clean_link->delVar('var_login');
	$url = $cible->getUrl();

	include_lcm('inc_session');
	verifier_visiteur();

	if ($author_session AND !$logout 
		AND ($author_session['status']=='admin' OR $author_session['status']=='normal'))
	{
		if ($url != $GLOBALS['clean_link']->getUrl())
			@Header("Location: $url");
		echo "<a href='$url'>"._T('login_par_ici')."</a>\n";
		return;
	}

	// Initialisations
	$nom_site = lire_meta('site_name');
	$url_site = lire_meta('adresse_site');

	if (!$nom_site)
		$nom_site = _T('info_mon_site_spip');

	if (!$url_site) 
		$url_site = "./";

	if ($GLOBALS['var_erreur'] == 'pass')
		$erreur = _T('login_password_incorrect');

	// The login is memorized in the cookie for a possible future admin login
	if (!$login) {
		if (ereg("^@(.*)$", $lcm_admin, $regs))
			$login = $regs[1];
	} else if ($login == '-1')
		$login = '';

	$flag_autres_sources = $GLOBALS['ldap_present'];

	// What informations to pass?
	if ($login) {
		$status_login = 0; // unknown status
		$login = addslashes($login);
		$query = "SELECT id_author, status, password, prefs FROM lcm_author WHERE username='$login'";
		$result = lcm_query($query);
		if ($row = lcm_fetch_array($result)) {
			if ($row['status'] == 'trash' OR $row['password'] == '') {
				$status_login = -1; // refuse
			} else {
				$status_login = 1; // known login

				// Which infos to pass for the javascript ?
				$id_author = $row['id_author'];
				$alea_actuel = $row['alea_actuel'];
				$alea_futur = $row['alea_futur'];

				// Button for lenght of connection
				if ($row['prefs']) {
					$prefs = unserialize($row['prefs']);
					$rester_checked = ($prefs['cnx'] == 'perma' ? ' checked=\'checked\'':'');
				}
			}
		}

		// unknown login (except LDAP) or refused
		if ($status_login == -1 OR ($status_login == 0 AND !$flag_autres_sources)) {
			$erreur = _T('login_identifier_unknown', array('login' => htmlspecialchars($login)));
			$login = '';
			@lcm_setcookie('lcm_admin', '', time() - 3600);
		}
	}
	
	// Javascript for the focus
	if ($login)
		$js_focus = 'document.form_login.session_password.focus();';
	else
		$js_focus = 'document.form_login.var_login.focus();';

	if ($echec_cookie == "oui") {
		echo open_login (_T('erreur_probleme_cookie'));
		echo "<p /><b>"._T('login_cookie_oblige')."</b> ";
		echo _T('login_cookie_accepte')."\n";
	} else {
		echo open_login ();
		if ($message_login) {
			echo "<br />" .  _T("forum_vous_enregistrer") . " <a $pass_popup>"
				.  _T("forum_vous_inscrire") .  "</a><p/>\n";
		}
	}

	$action = $clean_link->getUrl();

	if ($login) {
		// Shows the login form, including the MD5 javascript
		$flag_challenge_md5 = true;

		if ($flag_challenge_md5)
			echo "<script type=\"text/javascript\" src=\"md5.js\"></script>";

		echo "<form name='form_login' action='lcm_cookie.php' method='post'";

		if ($flag_challenge_md5)
			echo " onSubmit='if (this.session_password.value) {
				this.session_password_md5.value = calcMD5(\"$alea_actuel\" + this.session_password.value);
				this.next_session_password_md5.value = calcMD5(\"$alea_futur\" + this.session_password.value);
				this.session_password.value = \"\"; }'";

		echo ">\n";
		echo "<div style='border: 1px solid #000000; padding: 10px; text-align:".$GLOBALS["spip_lang_left"].";'>";

		if ($erreur) 
			echo "<div style='color:red;'><b>" .  _T('login_access_denied') . _T('typo_column') . " $erreur</b></div><p>";

		if ($flag_challenge_md5) {
			// If javascript is active, we pass the login in the hidden field
			echo "<script type=\"text/javascript\"><!--\n" . 
				"document.write('" . addslashes(_T('login_login')) . " <b>$login</b><br/>" .
				"<font size=\\'2\\'>[<a href=\\'lcm_cookie.php?cookie_admin=non&amp;url=".rawurlencode($action)."\\'>" . _T('login_other_identifier') . "</a>]</font>');\n" .
				"//--></script>\n";
		 	echo "<input type='hidden' name='session_login_hidden' value='$login' />";

			// If javascript is not active, the login is still modifiable
			// (since the challenge is not used)
			echo "<noscript>";
			echo "<div class='box_warning'>";
			echo _T('login_not_secure') . " <a href=\"" .
			quote_amp($clean_link->getUrl()) . "\">" .
			_T('login_link_reload_page') . "</a>.<p /></div>\n";
		}

		echo "\t<label for='session_login'><b>" . _T('login_login') . _T('typo_column') . "</b> (" . _T('login_info_login').")<br /></label>";
		echo "\t<input type='text' name='session_login' id='session_login' class='forml' value=\"$login\" size='40' />\n";
		if ($flag_challenge_md5) echo "</noscript>\n";

		echo "\t<p />\n";
		echo "\t<label for='session_password'><b>" . _T('login_password') . _T('typo_column') . "</b><br /></label>";
		echo "\t<input type='password' name='session_password' id='session_password' class='forml' value=\"\" size='40' />\n";
		echo "\t<input type='hidden' name='essai_login' value='oui' />\n";

		echo "\t<br />&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='session_remember' value='yes' id='session_remember'$rester_checked /> ";
		echo "\t<label for='session_remember'>" . _T('login_remain_logged_on') . "</label>";

		echo "\t<input type='hidden' name='url' value='$url' />\n";
		echo "\t<input type='hidden' name='session_password_md5' value='' />\n";
		echo "\t<input type='hidden' name='next_session_password_md5' value='' />\n";
		echo "<div align='right'><input class='button_login' type='submit' value='"._T('button_validate')."' /></div>\n";
		echo "</div>";
		echo "</form>";
	} else {
		// Ask only for the login/username
		$action = quote_amp($action);
		echo "<form name='form_login' action='$action' method='post'>\n";
		echo "<div style='border: 1px solid #000000; padding: 10px; text-align:" . $GLOBALS["spip_lang_left"] . ";'>";

		if ($erreur)
			echo "<span style='color:red;'><b>" . _T('login_access_denied') .  _T('typo_column') . " $erreur</b></span><p />";
			
		echo "<label><b>" . _T('login_login') . '</b> (' . _T('login_info_login') . ')' . _T('typo_column') . "<br /></label>";
		echo "<input type='text' name='var_login' class='forml' value=\"\" size='40' />\n";

		echo "<input type='hidden' name='var_url' value='$url' />\n";
		echo "<div align='right'><input class='button_login' type='submit' value='"._T('button_validate')."'/></div>\n";
		echo "</div>";
		echo "</form>";
	}

	// Focus management
	echo "<script type=\"text/javascript\"><!--\n" . $js_focus . "\n//--></script>\n";

	if ($echec_cookie == "oui" AND $php_module AND !$ignore_auth_http) {
		echo "<form action='lcm_cookie.php' method='get'>";
		echo "<fieldset>\n<p>";
		echo _T('login_prefer_no_cookie') . " \n";
		echo "<input type='hidden' name='essai_auth_http' value='oui'/> ";
		echo "<input type='hidden' name='url' value='$url'/>\n";
		echo "<div align='right'><input type='submit' value='"._T('login_without_cookie')."'/></div>\n";
		echo "</fieldset></form>\n";
	}

	echo "\n<div align='center' style='font-size: 12px;' >"; // start of the login footer

	$open_subscription = read_meta("site_open_subscription");
	if ($open_subscription == 'open' || $open_subscription == 'moderated')
		echo " [<a $pass_popup>" . _T('login_register').'</a>]';

	// button for "forgotten password"
	include_lcm('inc_mail');
	if (tester_mail()) {
		echo ' [<a href="lcm_pass.php?pass_forgotten=yes" target="lcm_pass" onclick="' ."javascript:window.open(this.href, 'lcm_pass', 'scrollbars=yes, resizable=yes, width=480, height=280'); return false;\">" ._T('login_password_forgotten').'</a>]'; 
	}

	echo "</div>\n";

	echo close_login();
}

?>
