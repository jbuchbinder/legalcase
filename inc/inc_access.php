<?php

if (defined('_INC_ACCES')) return;
define('_INC_ACCES', '1');


function create_random_password($length = 8, $salt = "") {
	$seed = (double) (microtime() + 1) * time();
	mt_srand($seed);
	srand($seed);
	
	$s = $salt;
	$pass = '';

	for ($i = 0; $i < $length; $i++) {
		if (!$s) {
			$s = mt_rand();
			if (!$s) $s = rand();
			$s = substr(md5(uniqid($s).$salt), 0, 16);
		}

		$r = unpack("Cr", pack("H2", $s.$s));
		$x = $r['r'] & 63;

		if ($x < 10) $x = chr($x + 48);
		else if ($x < 36) $x = chr($x + 55);
		else if ($x < 62) $x = chr($x + 61);
		else if ($x == 63) $x = '/';
		else $x = '.';
		
		$pass .= $x;
		$s = substr($s, 2);
	}

	$pass = ereg_replace("[./]", "a", $pass);
	$pass = ereg_replace("[I1l]", "L", $pass);
	$pass = ereg_replace("[0O]", "o", $pass);

	return $pass;
}

function creer_pass_aleatoire($length = 8, $salt = "") {
	lcm_log("Use of deprecated function creer_pass_aleatoire, use create_random_password() instead.");
	return create_random_password($length, $salt);
}

function initialiser_sel() {
	global $htsalt;

	$htsalt = '$1$' . create_random_password();
}


// initialize salt
initialiser_sel();


?>
