<?php

//
// Execute this file only once
if (defined('_INC_AUTH_DB')) return;
define('_INC_AUTH_DB', '1');

class Auth_db {
	var $nom, $login, $email, $md5pass, $md5next, $alea_futur, $statut;

	function init() {
		return true;
	}

	// Check the encrypted password (javascript)
	function validate_md5_challenge($username, $current_mdpass, $future_mdpass) {
		// Do not allow empty passwords
		if ($current_mdpass == '') return false;

		$query = "SELECT *
			FROM lcm_author 
			WHERE username = '".addslashes($username)."' 
				AND password = '".addslashes($current_mdpass)."' 
				AND status <> 'trash'";
		$result = lcm_query($query);

		if ($row = lcm_fetch_array($result)) {
			$this->nom = $row['nom'];
			$this->login = $row['username'];
			$this->email = $row['email']; // FIXME
			$this->statut = $row['status'];
			$this->md5pass = $current_mdpass;
			$this->md5next = $future_mdpass;
			return true;
		}

		return false;
	}

	// Check the non-encrypted password (no javascript support)
	function verifier($login, $pass) {
		// Do not allow empty passwords
		if ($pass == '') return false;

		$query = "SELECT alea_actuel, alea_futur FROM lcm_author WHERE username='".addslashes($login)."'";
		$result = lcm_query($query);
		if ($row = lcm_fetch_array($result)) {
			$md5pass = md5($row['alea_actuel'] . $pass);
			$md5next = md5($row['alea_futur'] . $pass);
			return $this->validate_md5_challenge($login, $md5pass, $md5next);
		}
		return false;
	}

	// lire == read. See lcm_cookie.php. This function is important for LDAP auth.
	function lire() {
		return true;
	}

	function activate() {
		// not used afaik
		if ($this->statut == 'nouveau') { // new author
			lcm_query("UPDATE lcm_author SET status='normal' WHERE username='".addslashes($this->login)."'");
		}
		if ($this->md5next) {
			include_lcm('inc_session');
			// creates a new salt for password encoding in the database
			$nouvel_alea_futur = creer_uniqid();
			$query = "UPDATE lcm_author SET alea_actuel = alea_futur, ".
				"password = '".addslashes($this->md5next)."', alea_futur = '$nouvel_alea_futur' ".
				"WHERE username='".$this->login."'";
			@spip_query($query);
		}
	}

	function activer() {
		lcm_log("use of deprecated function: activer, use activate instead");
		return $this->activate();
	}
}


?>
