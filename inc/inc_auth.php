<?php

//
// Execute this file only once
if (defined('_INC_AUTH')) return;
define('_INC_AUTH', '1');

include_lcm('inc_meta'); // initiates the database connection
include_lcm('inc_session');


// [ML] Alot of things to adapt... XXX/TODO
function auth() {
	global $INSECURE, $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_COOKIE_VARS, $REMOTE_USER, $PHP_AUTH_USER, $PHP_AUTH_PW;
	global $auth_can_disconnect, $ignore_auth_http, $ignore_remote_user;

	global $connect_id_auteur, $connect_nom, $connect_bio, $connect_email;
	global $connect_nom_site, $connect_url_site, $connect_login, $connect_pass;
	global $connect_activer_imessage, $connect_activer_messagerie;
	global $connect_status, $connect_toutes_rubriques, $connect_id_rubrique;

	global $author_session, $prefs;
	global $clean_link;

	// If there is not SQL connection, quit.
	if (!$GLOBALS['db_ok']) {
		echo "<P><H4>"._T('titre_probleme_technique')."</H4><P><P>\n".
		"<tt>".lcm_sql_errno()." ".lcm_sql_error()."</tt>";
		return false;
	}


	// Initialise variables (avoid URL hacks)
	$auth_login = "";
	$auth_pass = "";
	$auth_pass_ok = false;
	$auth_can_disconnect = false;
	$auth_htaccess = false;

	// Fetch identification data from authentication session
	if ($cookie_session = $HTTP_COOKIE_VARS['lcm_session']) {
		if (verifier_session($cookie_session)) {
			if ($author_session['status'] == 'admin' OR $author_session['status'] == '1comite') {
				$auth_login = $author_session['username'];
				$auth_pass_ok = true;
				$auth_can_disconnect = true;
			}
		}
	}

	// Authentification .htaccess
	// [ML] should be removed (TODO)
	else if ($REMOTE_USER && !$INSECURE['REMOTE_USER'] && !$ignore_remote_user) {
		$auth_login = $REMOTE_USER;
		$auth_pass_ok = true;
		$auth_htaccess = true;
	}

	// Failed login attempt
	// [ML] this is rather obscure to me, I will try to clean it later (TODO)
	else if ($GLOBALS['bonjour'] == 'oui') {
		$link = new Link("lcm_cookie.php?test_echec_cookie=oui");
		$clean_link->delVar('bonjour');
		$url = str_replace('/./', '/', 'ecrire/'.$clean_link->getUrl());
		$link->addVar('var_url', $url);
		@header("Location: ".$link->getUrl());
		exit;
	}

	// If not authenticated, ask for login / password
	if (!$auth_login) {
		$url = $clean_link->getUrl();
		@header("Location: lcm_login.php?var_url=".urlencode($url));
		exit;
	}

	//
	// Search for the login in the authors' table
	//

	$auth_login = addslashes($auth_login);
	$query = "SELECT * FROM lcm_author WHERE username='$auth_login' AND status !='external' AND status !='6forum'";
	$result = @lcm_query($query);

	if ($row = lcm_fetch_array($result)) {
		$connect_id_auteur = $row['id_author'];
		$connect_nom = $row['name_first'];
		$connect_bio = $row['bio'];
		$connect_email = $row['email'];
		$connect_nom_site = $row['nom_site'];
		$connect_url_site = $row['url_site'];
		$connect_login = $row['username'];
		$connect_pass = $row['password'];
		$connect_status = $row['status'];
		$connect_activer_messagerie = "non"; //$row["messagerie"];
		$connect_activer_imessage = "non "; //$row["imessage"];

		// Set the users' preferences
		$prefs = unserialize($row['prefs']);

		// XXX [ML] if some preferences are absent from $prefs,
		// we can set them here.
	}
	else {
		// This case is a strange possibility: the author is authentified
		// OK, but he does not exist in the authors table. Possible cause:
		// the database was restaured and the author does not exist (and
		// the user was authentified by another source, such as LDAP).
		include_lcm('inc_presentation');
		include_lcm('inc_text');

		install_html_start(_T('avis_erreur_connexion'));
		echo "<br><br><p>"._T('texte_inc_auth_1', array('auth_login' => $auth_login))." <A HREF='lcm_cookie.php?logout=$auth_login'>".  _T('texte_inc_auth_2')."</A>"._T('texte_inc_auth_3');
		install_html_end();
		exit;
	}

	if (!$auth_pass_ok) {
		@header("Location: lcm_login.php?var_erreur=pass");
		exit;
	}

	// [ML] Again, not sure how this is used, but we can ignore it for now
	// TODO (note: nouveau == new)
	if ($connect_status == 'nouveau') {
		$query = "UPDATE lcm_author SET status = '1comite' WHERE id_author = $connect_id_auteur";
		$result = spip_query($query);
		$connect_status = '1comite';
	}

	return true;
}


if (!auth()) exit;

?>
