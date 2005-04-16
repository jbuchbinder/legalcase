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

	$Id: inc_auth_db.php,v 1.21 2005/04/16 06:47:54 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_AUTH_DB')) return;
define('_INC_AUTH_DB', '1');

include_lcm('inc_filters');
include_lcm('inc_session');

class Auth_db {
	var $nom, $username, $md5pass, $md5next, $alea_futur, $statut;

	function init() {
		$this->error = "";
		return true;
	}

	// Check the encrypted password (javascript)
	function validate_md5_challenge($username, $current_mdpass, $future_mdpass) {
		$this->error = "";

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
		$this->error = "";

		// Do not allow empty passwords
		if ($pass == '') return false;

		$query = "SELECT alea_actuel, alea_futur
					FROM lcm_author 
					WHERE username='".addslashes($username)."'";

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
		$this->error = "";
		return true;
	}

	// [ML] not used afaik
	function activate() {
		$this->error = "";

		if ($this->statut == 'nouveau') { // new author
			lcm_query("UPDATE lcm_author 
						SET status='normal' 
						WHERE username='".addslashes($this->username)."'");
		}

		if ($this->md5next) {
			include_lcm('inc_session');
			// creates a new salt for password encoding in the database
			$nouvel_alea_futur = create_uniq_id();
			$query = "UPDATE lcm_author SET alea_actuel = alea_futur, ".
				"password = '".addslashes($this->md5next)."', alea_futur = '$nouvel_alea_futur' ".
				"WHERE username='".$this->username."'";
			@lcm_query($query);
		}
	}

	function is_newpass_allowed($id_author, $username, $author_session = 0) {
		$this->error = "";

		if (! $author_session)
			return true;

		if ($author_session['username'] == $username)
			return true;
		else if ($author_session['status'] == 'admin')
			return true;
		else {
			$this->error = "You are not allowed to change the password.";
			return false;
		}
	}

	function newpass($id_author, $username, $pass, $author_session = 0) {
		$this->error = "";

		if ($this->is_newpass_allowed($id_author, $username, $author_session) == false)
			return false;

		// Check for password size
		if (strlen(lcm_utf8_decode($pass)) <= 5) {
			$this->error = _T('pass_warning_too_short');
			return false;
		}

		$alea_current = create_uniq_id();
		$alea_future  = create_uniq_id();
		$pass = md5($alea_current . $pass);
	
		$query = "UPDATE lcm_author
					SET password = '" . $pass . "',
						alea_actuel = '" . $alea_current . "',
						alea_futur = '" . $alea_future . "'
					WHERE id_author = '" . $id_author . "'";

		lcm_query($query);
		return true;
	}

	function is_newusername_allowed($id_author, $username, $author_session = 0) {
		$this->error = "";
		
		if (! $author_session)
			return true;

		if ($author_session['status'] == 'admin')
			return true;
		else {
			$this->error = "You are not allowed to change the username.";
			return false;
		}
	}

	function newusername($id_author, $old_username, $new_username, $author_session = 0) {
		$this->error = "";

		if ($this->is_newusername_allowed($id_author, $old_username, $author_session) == false)
			return false;

		// Check for username size
		if (strlen(lcm_utf8_decode($new_username)) < 3) {
			$this->error = _T('login_warning_too_short');
			return false;
		}

		// Check if username is not already taken
		$query = "SELECT username
					FROM lcm_author
					WHERE username = '" . addslashes($new_username) . "'";
		$result = lcm_query($query);

		if ($row = lcm_fetch_array($result)) {
			$this->error = _T('login_warning_already_exists ');
			return false;
		}
	
		$query = "UPDATE lcm_author
					SET username = '" . addslashes($new_username) . "'
					WHERE id_author = $id_author";
		lcm_query($query);

		// Check for errors (duplicates, format, etc.)
		if (lcm_sql_errno()) {
			$this->error = lcm_sql_error();
			lcm_log("newusername: " . $this->error);
			return false;
		}

		return true;
	}
}


?>
