<?php

//
// Execute this file only once
if (defined('_INC_AUTH_DB')) return;
define('_INC_AUTH_DB', '1');

class Auth_spip {
	var $nom, $login, $email, $md5pass, $md5next, $alea_futur, $statut;

	function init() {
		return true;
	}

	// Verification du mot passe crypte (javascript)
	function verifier_challenge_md5($login, $mdpass_actuel, $mdpass_futur) {
		// Interdire mot de passe vide
		if ($mdpass_actuel == '') return false;

		$query = "SELECT * FROM spip_auteurs WHERE login='".addslashes($login)."' AND pass='".addslashes($mdpass_actuel)."' AND statut<>'5poubelle'";
		$result = spip_query($query);

		if ($row = spip_fetch_array($result)) {
			$this->nom = $row['nom'];
			$this->login = $row['login'];
			$this->email = $row['email'];
			$this->statut = $row['statut'];
			$this->md5pass = $mdpass_actuel;
			$this->md5next = $mdpass_futur;
			return true;
		}
		return false;
	}

	// Verification du mot passe en clair (sans javascript)
	function verifier($login, $pass) {
		// Interdire mot de passe vide
		if ($pass == '') return false;

		$query = "SELECT alea_actuel, alea_futur FROM spip_auteurs WHERE login='".addslashes($login)."'";
		$result = spip_query($query);
		if ($row = spip_fetch_array($result)) {
			$md5pass = md5($row['alea_actuel'] . $pass);
			$md5next = md5($row['alea_futur'] . $pass);
			return $this->verifier_challenge_md5($login, $md5pass, $md5next);
		}
		return false;
	}

	function lire() {
		return true;
	}

	function activer() {
		if ($this->statut == 'nouveau') { // nouvel inscrit
			spip_query("UPDATE spip_auteurs SET statut='1comite' WHERE login='".addslashes($this->login)."'");
		}
		if ($this->md5next) {
			include_ecrire("inc_session.php3");
			// fait tourner le codage du pass dans la base
			$nouvel_alea_futur = creer_uniqid();
			$query = "UPDATE spip_auteurs SET alea_actuel = alea_futur, ".
				"pass = '".addslashes($this->md5next)."', alea_futur = '$nouvel_alea_futur' ".
				"WHERE login='".$this->login."'";
			@spip_query($query);
		}
	}
}


?>
