<?php

// Execute this file only once
if (defined('_INC_AUTH_DB')) return;
define('_INC_AUTH_DB', '1');

class Auth_db {
	var $nom, $username, $md5pass, $md5next, $alea_futur, $statut;

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
			$this->username = $row['username'];
			$this->status = $row['status'];
			$this->md5pass = $current_mdpass;
			$this->md5next = $future_mdpass;
			return true;
		}

		return false;
	}

	// Check the non-encrypted password (no javascript support)
	function validate_pass_cleartext($username, $pass) {
		// Do not allow empty passwords
		if ($pass == '') return false;

		$query = "SELECT alea_actuel, alea_futur FROM lcm_author WHERE username='".addslashes($username)."'";
		$result = lcm_query($query);
		if ($row = lcm_fetch_array($result)) {
			$md5pass = md5($row['alea_actuel'] . $pass);
			$md5next = md5($row['alea_futur'] . $pass);
			return $this->validate_md5_challenge($username, $md5pass, $md5next);
		}
		return false;
	}

	// lire == read. See lcm_cookie.php. This function is important for LDAP auth.
	function lire() {
		return true;
	}

	// [ML] not used afaik
	function activate() {
		if ($this->statut == 'nouveau') { // new author
			lcm_query("UPDATE lcm_author SET status='normal' WHERE username='".addslashes($this->username)."'");
		}
		if ($this->md5next) {
			include_lcm('inc_session');
			// creates a new salt for password encoding in the database
			$nouvel_alea_futur = creer_uniqid();
			$query = "UPDATE lcm_author SET alea_actuel = alea_futur, ".
				"password = '".addslashes($this->md5next)."', alea_futur = '$nouvel_alea_futur' ".
				"WHERE username='".$this->username."'";
			@spip_query($query);
		}
	}

	function activer() {
		lcm_log("use of deprecated function: activer, use activate instead");
		return $this->activate();
	}

	function is_newpass_allowed($username, $author_session) {
		if ($author_session['username'] == $username)
			return true;
		else if ($author_session['status'] == 'admin')
			return true;
		else
			return false;
	}

	function newpass($username, $pass, $author_session) {
		if ($this->is_newpass_allowed($username, $author_session) == false)
			return false;

		$alea_current = create_uniq_id();
		$alea_future  = create_uniq_id();
		$pass = md5($alea_current . $pass);
	
		$query = "UPDATE lcm_author
					SET password = '" . $pass . "',
						alea_actuel = '" . $alea_current . "',
						alea_futur = '" . $alea_future . "'
					WHERE username = '" . $username . "'";

		lcm_query($query);
		return true;
	}

	function is_newlogin_allowed($username, $author_session) {
		if ($author_session['status'] == 'admin')
			return true;
		else
			return false;
	}

	function newlogin($username, $new_username, $author_session) {
		// TODO TODO
		lcm_log("newlogin: code me!");
		return false;
	}
}


?>
