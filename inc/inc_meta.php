<?php

//
// Execute this file only once
if (defined('_INC_META')) return;
define('_INC_META', '1');

// ********
// [ML] WARNING: Don't include inc_meta unless you cannot
// do without. Bad usage of inc_meta can cause strange bugs
// in the installation and in inc_lang.php
// ********


function read_metas() {
	global $meta, $meta_upd;

	$meta = '';
	$meta_upd = '';
	$query = 'SELECT name, value, upd FROM lcm_meta';
	$result = lcm_query($query);
	while ($row = spip_fetch_array($result)) {
		$nom = $row['name'];
		$meta[$nom] = $row['value'];
		$meta_upd[$nom] = $row['upd'];
	}
}

// old function for read_metas
function lire_metas() {
	lcm_log("use of deprecated function: lire_metas, use read_metas instead");
	return read_metas();
}

function write_meta($name, $value) {
	$value = addslashes($value);
	lcm_query("REPLACE lcm_meta (name, value) VALUES ('$name', '$value')");
}

// old function for write_meta
function ecrire_meta($name, $value) {
	lcm_log("use of deprecated function: ecrire_meta, use write_meta instead");
	return write_meta($name, $value);
}

function erase_meta($name) {
	lcm_query("DELETE FROM lcm_meta WHERE name='$name'");
}

// old function for delete_meta
function effacer_meta($name) {
	lcm_log("use of deprecated function: effacer_meta, use erase_meta instead");
	erase_meta($name);
}

//
// Update the cache file for the meta informations
// Don't forget to call this function after write_meta() and erase_meta()!
//
function write_metas() {
	global $meta, $meta_upd;

	read_metas();

	$s = '<'.'?php

if (defined("_INC_META_CACHE")) return;
define("_INC_META_CACHE", "1");

function read_meta($name) {
	global $meta;
	return $meta[$name];
}

// old read_meta function (to remove eventually)
function lire_meta($name) {
	return read_meta($name);
}

function read_meta_upd($name) {
	global $meta_upd;
	return $meta_upd[$name];
}

// old read_meta_upd function (to remove eventually)
function lire_meta_maj($name) {
	return read_meta_upd($name);
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
	if ($meta_upd) {
		reset($meta_upd);
		while (list($key, $val) = each($meta_upd)) {
			$key = addslashes($key);
			$s .= "\$GLOBALS['meta_upd']['$key'] = '$val';\n";
		}
		$s .= "\n";
	}
	$s .= '?'.'>';

	$file_meta_cache = 'inc/data/inc_meta_cache.php';
	@unlink($file_meta_cache);
	$file_meta_cache_w = $file_meta_cache.'-'.@getmypid();
	$f = @fopen($file_meta_cache_w, "wb");
	if ($f) {
		$r = @fputs($f, $s);
		@fclose($f);
		if ($r == strlen($s))
			@rename($file_meta_cache_w, $file_meta_cache);
		else
			@unlink($file_meta_cache_w);
	} else {
		global $connect_status;
		if ($connect_status == 'admin')
			echo "<h4 font color='red'>"._T('texte_inc_meta_1')." <a href='lcm_test_dirs.php'>"._T('texte_inc_meta_2')."</a> "._T('texte_inc_meta_3')."&nbsp;</h4>\n";
	}
}

// old deprecated function, to remove soon
function ecrire_metas() {
	return write_metas();
}

read_metas();

?>
