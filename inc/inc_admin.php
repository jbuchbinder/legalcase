<?php

// This was taken from Spip (www.spip.net)
// [ML] verifier_action_auteur() is used by LCM, perhaps we should
// clean this out one day...

//
// Execute this file only once
if (defined('_INC_ADMIN')) return;
define('_INC_ADMIN', '1');

function _action_auteur($action, $id_auteur, $nom_alea) {
	if (!$id_auteur) {
		global $connect_id_auteur, $connect_pass;
		$id_auteur = $connect_id_auteur;
		$pass = $connect_pass;
	}
	else {
		$result = spip_query("SELECT password FROM lcm_author WHERE id_author=$id_auteur");
		if ($result)
			if ($row = spip_fetch_array($result))
				$pass = $row['password'];
	}
	$alea = read_meta($nom_alea);
	return md5($action.$id_auteur.$pass.$alea);
}


function calculer_action_auteur($action, $id_auteur = 0) {
	return _action_auteur($action, $id_auteur, 'alea_ephemere');
}

function verifier_action_auteur($action, $valeur, $id_auteur = 0) {
	if ($valeur == _action_auteur($action, $id_auteur, 'alea_ephemere')) return true;
	if ($valeur == _action_auteur($action, $id_auteur, 'alea_ephemere_ancien')) return true;
	return false;
}


?>
