<?php

//
// Execute this file only once
if (defined('_INC_AUTH_LDAP')) return;
define('_INC_AUTH_LDAP', '1');

class Auth_ldap {
	var $user_dn;
	var $nom, $login, $email, $pass, $statut, $bio;

	function init() {
		// Check for LDAP presence
		if (!$GLOBALS['ldap_present']) return false;
		return spip_connect_ldap();
	}

	function verifier_challenge_md5($login, $mdpass_actuel, $mdpass_futur) {
		return false;
	}

	function verifier($login, $pass) {
		global $ldap_link, $ldap_base;

		// security, in case the LDAP server is very easy going
		if (!$login || !$pass) return false;

		// Tested attributes for login
		$atts = array('uid', 'login', 'userid', 'cn', 'sn');
		$login_search = ereg_replace("[^-@._[:space:][:alnum:]]", "", $login); // security

		// First try to find the DN
		reset($atts);
		while (list(, $att) = each($atts)) {
			$filter = "$att=$login_search";
			$result = @ldap_search($ldap_link, $ldap_base, $filter, array("dn"));
			$info = @ldap_get_entries($ldap_link, $result);
			// Don't accept the results if there is more than one entry
			// (we want a unique attribute)
			if (is_array($info) AND $info['count'] == 1) {
				$dn = $info[0]['dn'];
				if (@ldap_bind($ldap_link, $dn, $pass)) {
					$this->user_dn = $dn;
					$this->login = $login;
					return true;
				}
			}
		}

		// If failed, try to guess the DN
		reset($atts);
		while (list(, $att) = each($atts)) {
			$dn = "$att=$login_search, $ldap_base";
			if (@ldap_bind($ldap_link, $dn, $pass)) {
				$this->user_dn = $dn;
				$this->login = $login;
				return true;
			}
		}
		return false;
	}

	function lire() { // read
		global $ldap_link, $ldap_base, $flag_utf8_decode;
		$this->nom = $this->email = $this->pass = $this->statut = '';

		if (!$this->login) return false;

		// If the author exists in the database, fetch his infos
		$query = "SELECT * FROM spip_auteurs WHERE login='".addslashes($this->login)."' AND source='ldap'";
		$result = lcm_query($query);

		if ($row = lcm_fetch_array($result)) {
			$this->nom = $row['nom'];
			$this->email = $row['email'];
			$this->statut = $row['statut'];
			$this->bio = $row['bio'];
			return true;
		}

		// Read the info on the author from LDAP
		$result = @ldap_read($ldap_link, $this->user_dn, "objectClass=*", array("uid", "cn", "mail", "description"));
		
		// If the user cannot read his informations, reconnect with the main account
		if (!$result) {
			if (spip_connect_ldap())
				$result = @ldap_read($ldap_link, $this->user_dn, "objectClass=*", array("uid", "cn", "mail", "description"));
			else
				return false;
		}
		if (!$result) return false;

		// Fetch the author's data
		$info = @ldap_get_entries($ldap_link, $result);
		if (!is_array($info)) return false;
		for ($i = 0; $i < $info["count"]; $i++) {
			$val = $info[$i];
			if (is_array($val)) {
				if (!$this->nom) $this->nom = $val['cn'][0];
				if (!$this->email) $this->email = $val['mail'][0];
				if (!$this->login) $this->login = $val['uid'][0];
				if (!$this->bio) $this->bio = $val['description'][0];
			}
		}

		// Convert from UTF-8 (default encoding)
		if ($flag_utf8_decode) {
			$this->nom = utf8_decode($this->nom);
			$this->email = utf8_decode($this->email);
			$this->login = utf8_decode($this->login);
			$this->bio = utf8_decode($this->bio);
		}
		return true;
	}

	function activate() {
		$nom = addslashes($this->nom);
		$login = addslashes($this->login);
		$email = addslashes($this->email);
		$bio = addslashes($this->bio);
		$statut = read_meta("ldap_statut_import");

		if (!$statut) return false;

		// If the author does not exist, insert with the default status (defined at installation)
		// [ML] lcm-ification not tested XXX
		$query = "SELECT id_author FROM lcm_author WHERE username='$login'";
		$result = lcm_query($query);
		if (lcm_num_rows($result)) return false;

		// XXX
		$query = "INSERT IGNORE INTO lcm_author (source, name, username, email, bio, status, pass) ".
			"VALUES ('ldap', '$nom', '$login', '$email', '$bio', '$statut', '')";
		return lcm_query($query);
	}

	function activer() {
		lcm_log("use of deprecated function: activer, use activate instead");
		return activate();
	}
}


?>
