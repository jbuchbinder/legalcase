<?php

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
	global $db_ok;

	if (! isset($db_ok)) return; // no inc_connect.php
	if (! $db_ok) return; // database connection failed

	$meta = '';
	$meta_upd = '';
	$query = 'SELECT name, value, upd FROM lcm_meta';
	$result = lcm_query($query);
	while ($row = lcm_fetch_array($result)) {
		$nom = $row['name'];
		$meta[$nom] = $row['value'];
		$meta_upd[$nom] = $row['upd'];
	}
}

function write_meta($name, $value) {
	$value = addslashes($value);
	lcm_query("REPLACE lcm_meta (name, value) VALUES ('$name', '$value')");
}

function erase_meta($name) {
	lcm_query("DELETE FROM lcm_meta WHERE name='$name'");
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

	if (! array_key_exists($name, $meta)) {
		lcm_debug("read_meta: -$name- does not exist");
		return "";
	}

	return $meta[$name];
}

function read_meta_upd($name) {
	global $meta_upd;
	return $meta_upd[$name];
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

	// System keywords
	include_lcm('inc_keywords');
	$kwg_all = get_kwg_all('system');

	while (list($key0, $val0) = each($kwg_all)) {
		$name = $key0;

		// Dump every field of the keyword group
		while (list($key, $val) = each($val0)) {
			// We filter out numeric keys because lcm_fetch_array()
			// returns the two types of arrays
			if (! is_numeric($key)) {
				$key = addslashes($key);
				$val = ereg_replace("([\\\\'])", "\\\\1", $val);
				$s .= "\$GLOBALS['kwg']['$name']['$key'] = '$val';\n";
			}
		}
	}

	reset($kwg_all);
	foreach ($kwg_all as $kwg) {
		// Dump every keyword and field of the keyword group
		$kw_all = get_keywords_in_group_id($kwg['id_group']);

		foreach ($kw_all as $kw) {
			$kw_name = $kw['name'];

			// Dump every field of the keyword into the kwg
			while (list($key, $val) = each($kw)) {
				if (! is_numeric($key)) {
					$key = addslashes($key);
					$val = ereg_replace("([\\\\'])", "\\\\1", $val);
					$s .= "\$GLOBALS['kwg']['" . $kwg['name'] .  "']['keywords']['$kw_name']['$key'] = '$val';\n";
				}
			}
		}
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

read_metas();

?>
