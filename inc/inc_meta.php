<?php

//
// Execute this file only once
if (defined('_INC_META')) return;
define('_INC_META', '1');

// read metas
function lire_metas() {
	global $meta, $meta_maj;

	$meta = '';
	$meta_maj = '';
	$query = 'SELECT name, value, upd FROM lcm_meta';
	$result = lcm_query($query);
	while ($row = spip_fetch_array($result)) {
		$nom = $row['name'];
		$meta[$nom] = $row['value'];
		$meta_maj[$nom] = $row['upd'];
	}
}

// write metas
function ecrire_meta($nom, $valeur) {
	$valeur = addslashes($valeur);
	lcm_query("REPLACE lcm_meta (name, value) VALUES ('$nom', '$valeur')");
}

// delete metas
function effacer_meta($nom) {
	lcm_query("DELETE FROM lcm_meta WHERE name='$nom'");
}

//
// Mettre a jour le fichier cache des metas
//
// Ne pas oublier d'appeler cette fonction apres ecrire_meta() et effacer_meta() !
//
function ecrire_metas() {
	global $meta, $meta_maj, $flag_ecrire;

	lire_metas();

	$s = '<'.'?php

if (defined("_INC_META_CACHE")) return;
define("_INC_META_CACHE", "1");

function lire_meta($nom) {
	global $meta;
	return $meta[$nom];
}

function lire_meta_maj($nom) {
	global $meta_maj;
	return $meta_maj[$nom];
}

';
	if ($meta) {
		reset($meta);
		while (list($key, $val) = each($meta)) {
			$key = addslashes($key);
			$val = ereg_replace("([\\\\'])", "\\\\1", $val);
			$s .= "\$GLOBALS['meta']['$key'] = '$val';\n";
		}
		$s .= "\n";
	}
	if ($meta_maj) {
		reset($meta_maj);
		while (list($key, $val) = each($meta_maj)) {
			$key = addslashes($key);
			$s .= "\$GLOBALS['meta_maj']['$key'] = '$val';\n";
		}
		$s .= "\n";
	}
	$s .= '?'.'>';

	$fichier_meta_cache = 'data/inc_meta_cache.php';
	@unlink($fichier_meta_cache);
	$fichier_meta_cache_w = $fichier_meta_cache.'-'.@getmypid();
	$f = @fopen($fichier_meta_cache_w, "wb");
	if ($f) {
		$r = @fputs($f, $s);
		@fclose($f);
		if ($r == strlen($s))
			@rename($fichier_meta_cache_w, $fichier_meta_cache);
		else
			@unlink($fichier_meta_cache_w);
	} else {
		global $connect_statut;
		if ($connect_statut == 'admin')
			echo "<h4 font color='red'>"._T('texte_inc_meta_1')." <a href='lcm_test_dirs.php'>"._T('texte_inc_meta_2')."</a> "._T('texte_inc_meta_3')."&nbsp;</h4>\n";
	}
}


lire_metas();

?>
