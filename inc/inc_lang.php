<?php

// 
// Execute this file only once
if (defined('_INC_LANG')) return;
define('_INC_LANG', '1');

//
// Load a language file
// [ML] "force" parameter might be useless
//
function load_language_file($lang, $module = 'lcm', $force = false) {
	if (@file_exists('inc/lang/'.$module.'_'.$lang.'.php')) {
		$GLOBALS['idx_lang'] = 'i18n_'.$module.'_'.$lang;
		include_lcm('lang/'.$module.'_'.$lang);
		lcm_debug($module . "_" . $lang . ": Language file loaded");
	} else {
		// If the language file of the module does not exist, we fallback
		// on English, which *by definition* must exist. We then recopy the
		// 'en' table in the variable related to the requested language
		if (@file_exists('inc/lang/'.$module.'_en.php')) {
			$GLOBALS['idx_lang'] = 'i18n_'.$module.'_en';
			include_lcm('lang/'.$module.'_en');
		}
		$GLOBALS['i18n_'.$module.'_'.$lang] = $GLOBALS['i18n_'.$module.'_en'];
		lcm_debug("Fellback on English");
	}

	// The local system administrator can overload official strings
	if (@file_exists('lang/perso.php')) {
		include_lcm('lang/perso');
	}
}

//
// Change the current language
//
function lcm_set_language($lang) {
	global $all_langs, $spip_lang_rtl, $spip_lang_right, $spip_lang_left, $spip_lang_dir, $spip_dir_lang;

	$liste_langues = $all_langs.','.read_meta('langues_multilingue');

 	if ($lang && ereg(",$lang,", ",$liste_langues,")) {
		$GLOBALS['lcm_lang'] = $lang;

		$spip_lang_rtl =   lang_dir($lang, '', '_rtl');
		$spip_lang_left =  lang_dir($lang, 'left', 'right');
		$spip_lang_right = lang_dir($lang, 'right', 'left');
		$spip_lang_dir =   lang_dir($lang);
		$spip_dir_lang = " dir='$spip_lang_dir'";

		return true;
	}
	else
		return false;
}

function changer_langue($lang) {
	lcm_log("Use of deprecated function changer_langue(), use lcm_set_language instead");
	return lcm_set_language($lang);
}

//
// Set the current language based on the information sent by the browser
//
function lcm_set_language_from_browser() {
	global $HTTP_SERVER_VARS, $HTTP_COOKIE_VARS;

	$accept_langs = explode(',', $HTTP_SERVER_VARS['HTTP_ACCEPT_LANGUAGE']);
	if (is_array($accept_langs)) {
		while(list(, $s) = each($accept_langs)) {
			if (eregi('^([a-z]{2,3})(-[a-z]{2,3})?(;q=[0-9.]+)?$', trim($s), $r)) {
				$lang = strtolower($r[1]);
				if (lcm_set_language($lang)) return $lang;
			}
		}
	}
	return false;
}

//
// Translate a string
//
function traduire_chaine($code, $args) {
	global $lcm_lang;

	// list of modules to process (ex: "module:my_string")
	$modules = array('lcm');
	if (strpos($code, ':')) {
		if (ereg("^([a-z/]+):(.*)$", $code, $regs)) {
			$modules = explode("/",$regs[1]);
			$code = $regs[2];
		}
	}

	// go thgough all the modules until we find our string
	while (!$text AND (list(,$module) = each ($modules))) {
		$var = "i18n_".$module."_".$lcm_lang;
		if (!$GLOBALS[$var])
			load_language_file($lcm_lang, $module);

		if (!$flag_ecrire) {
			if (!isset($GLOBALS[$var][$code]))
				load_language_file($lcm_lang, $module, $code);

			if (isset($GLOBALS[$var][$code]))
				$cache_lang[$lcm_lang][$code] = 1;
		}
		$text = $GLOBALS[$var][$code];
	}

	// Languages which are not finished or late  (...)
	if ($lcm_lang<>'en') {
		$text = ereg_replace("^<(NEW|MODIF)>","",$text);
		if (!$text) {
			$lcm_lang_temp = $lcm_lang;
			$lcm_lang = 'en';
			$text = traduire_chaine($code, $args);
			$lcm_lang = $lcm_lang_temp;
		}
	}

	// Insert the variables into the strings
	if (!$args) return $text;
	while (list($name, $value) = each($args))
		$text = str_replace ("@$name@", $value, $text);
	
	if (empty($text))
		lcm_log("Warning: translation string -" . $code . "- has no text");

	return $text;
}


function translate_language_name($lang) {
	$r = $GLOBALS['codes_langues'][$lang];
	if (!$r) $r = $lang;
	return $r;
}

function traduire_nom_langue($lang) {
	lcm_debug("Use of deprecated function traduire_nom_langue(), use translate_language_name() instead");
	return translate_language_name($lang);
}

function init_codes_langues() {
	$GLOBALS['codes_langues'] = array(
	'aa' => "Afar",
	'ab' => "Abkhazian",
	'af' => "Afrikaans",
	'am' => "Amharic",
	'ar' => "&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;",
	'as' => "Assamese",
	'ast' => "asturiano",
	'ay' => "Aymara",
	'az' => "&#1040;&#1079;&#1241;&#1088;&#1073;&#1072;&#1112;&#1209;&#1072;&#1085;",
	'ba' => "Bashkir",
	'be' => "&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1110;",
	'bg' => "&#1073;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;",
	'bh' => "Bihari",
	'bi' => "Bislama",
	'bn' => "Bengali; Bangla",
	'bo' => "Tibetan",
	'br' => "breton",
	'ca' => "catal&#224;",
	'co' => "corsu",
	'cpf' => "Kr&eacute;ol r&eacute;yon&eacute;",
	'cpf_dom' => "Krey&ograve;l",
	'cs' => "&#269;e&#353;tina",
	'cy' => "Welsh",
	'da' => "dansk",
	'de' => "Deutsch",
	'dz' => "Bhutani",
	'el' => "&#949;&#955;&#955;&#951;&#957;&#953;&#954;&#940;",
	'en' => "English",
	'eo' => "Esperanto",
	'es' => "Espa&#241;ol",
	'es_co' => "Colombiano",
	'et' => "eesti",
	'eu' => "euskara",
	'fa' => "&#1601;&#1575;&#1585;&#1587;&#1609;",
	'fi' => "suomi",
	'fj' => "Fiji",
	'fo' => "f&#248;royskt",
	'fr' => "fran&#231;ais",
	'fr_tu' => "fran&#231;ais copain",
	'fy' => "Frisian",
	'ga' => "Irish",
	'gd' => "Scots Gaelic",
	'gl' => "galego",
	'gn' => "Guarani",
	'gu' => "Gujarati",
	'ha' => "Hausa",
	'he' => "&#1506;&#1489;&#1512;&#1497;&#1514;",
	'hi' => "&#2361;&#2367;&#2306;&#2342;&#2368;",
	'hr' => "hrvatski",
	'hu' => "magyar",
	'hy' => "Armenian",
	'ia' => "Interlingua",
	'id' => "Bahasa Indonesia",
	'ie' => "Interlingue",
	'ik' => "Inupiak",
	'is' => "&#237;slenska",
	'it' => "italiano",
	'iu' => "Inuktitut",
	'ja' => "&#26085;&#26412;&#35486;",
	'jw' => "Javanese",
	'ka' => "&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;",
	'kk' => "&#1178;&#1072;&#1079;&#1072;&#1097;b",
	'kl' => "Greenlandic",
	'km' => "Cambodian",
	'kn' => "Kannada",
	'ko' => "&#54620;&#44397;&#50612;",
	'ks' => "Kashmiri",
	'ku' => "Kurdish",
	'ky' => "Kirghiz",
	'la' => "Latin",
	'ln' => "Lingala",
	'lo' => "Laothian",
	'lt' => "lietuvi&#371;",
	'lv' => "latvie&#353;u",
	'mg' => "Malagasy",
	'mi' => "Maori",
	'mk' => "&#1084;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080; &#1112;&#1072;&#1079;&#1080;&#1082;",
	'ml' => "Malayalam",
	'mn' => "Mongolian",
	'mo' => "Moldavian",
	'mr' => "&#2350;&#2352;&#2366;&#2336;&#2368;",
	'ms' => "Bahasa Malaysia",
	'mt' => "Maltese",
	'my' => "Burmese",
	'na' => "Nauru",
	'ne' => "Nepali",
	'nl' => "Nederlands",
	'no' => "norsk",
	'nb' => "norsk bokm&aring;l",
	'nn' => "norsk nynorsk",
	'oc_lnc' => "&ograve;c lengadocian",
	'oc_ni' => "&ograve;c ni&ccedil;ard",
	'oc_prv' => "&ograve;c proven&ccedil;au",
	'oc_gsc' => "&ograve;c gascon",
	'oc_lms' => "&ograve;c lemosin",
	'oc_auv' => "&ograve;c auvernhat",
	'oc_va' => "&ograve;c vivaroaupenc",
	'om' => "(Afan) Oromo",
	'or' => "Oriya",
	'pa' => "Punjabi",
	'pl' => "polski",
	'ps' => "Pashto, Pushto",
	'pt' => "Portugu&#234;s",
	'qu' => "Quechua",
	'rm' => "Rhaeto-Romance",
	'rn' => "Kirundi",
	'ro' => "rom&#226;n&#259;",
	'ru' => "&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;",
	'rw' => "Kinyarwanda",
	'sa' => "&#2360;&#2306;&#2360;&#2381;&#2325;&#2371;&#2340;",
	'sd' => "Sindhi",
	'sg' => "Sangho",
	'sh' => "srpskohrvastski",
	'sh_lat' => 'srpskohrvastski',
	'sh_cyr' => '&#1057;&#1088;&#1087;&#1089;&#1082;&#1086;&#1093;&#1088;&#1074;&#1072;&#1090;&#1089;&#1082;&#1080;',
	'si' => "Sinhalese",
	'sk' => "sloven&#269;ina",	// (Slovakia)
	'sl' => "sloven&#353;&#269;ina",	// (Slovenia)
	'sm' => "Samoan",
	'sn' => "Shona",
	'so' => "Somali",
	'sq' => "shqipe",
	'sr' => "&#1089;&#1088;&#1087;&#1089;&#1082;&#1080;",
	'ss' => "Siswati",
	'st' => "Sesotho",
	'su' => "Sundanese",
	'sv' => "svenska",
	'sw' => "Kiswahili",
	'ta' => "&#2980;&#2990;&#3007;&#2996;&#3021; - tamil",
	'te' => "Telugu",
	'tg' => "Tajik",
	'th' => "&#3652;&#3607;&#3618;",
	'ti' => "Tigrinya",
	'tk' => "Turkmen",
	'tl' => "Tagalog",
	'tn' => "Setswana",
	'to' => "Tonga",
	'tr' => "T&#252;rk&#231;e",
	'ts' => "Tsonga",
	'tt' => "&#1058;&#1072;&#1090;&#1072;&#1088;",
	'tw' => "Twi",
	'ug' => "Uighur",
	'uk' => "&#1091;&#1082;&#1088;&#1072;&#1111;&#1085;&#1100;&#1089;&#1082;&#1072;",
	'ur' => "&#1649;&#1585;&#1583;&#1608;",
	'uz' => "U'zbek",
	'vi' => "Ti&#7871;ng Vi&#7879;t",
	'vo' => "Volapuk",
	'wo' => "Wolof",
	'xh' => "Xhosa",
	'yi' => "Yiddish",
	'yor' => "Yoruba",
	'za' => "Zhuang",
	'zh' => "&#20013;&#25991;",
	'zu' => "Zulu");
}

//
// Filtres de langue
//

// afficher 'gaucher' si la langue est arabe, hebreu, persan, 'droitier' sinon
// utilise par #LANG_DIR, #LANG_LEFT, #LANG_RIGHT
function lang_dir($lang, $droitier='ltr', $gaucher='rtl') {
	if ($lang=='fa' OR $lang=='ar' OR $lang == 'he')
		return $gaucher;
	else
		return $droitier;
}

function lang_typo($lang) {
	if ($lang == 'eo' OR $lang == 'fr' OR substr($lang, 0, 3) == 'fr_' OR $lang == 'cpf')
		return 'fr';
	else if ($lang)
		return 'en';
	else
		return false;
}

// service pour que l'espace prive reflete la typo et la direction des objets affiches
function changer_typo($lang = '', $source = '') {
	global $lang_typo, $lang_dir, $dir_lang;

	if (ereg("^(article|rubrique|breve|auteur)([0-9]+)", $source, $regs)) {
		$r = lcm_fetch_array(lcm_query("SELECT lang FROM spip_".$regs[1]."s WHERE id_".$regs[1]."=".$regs[2]));
		$lang = $r['lang'];
	}

	if (!$lang)
		$lang = read_meta('langue_site');

	$lang_typo = lang_typo($lang);
	$lang_dir = lang_dir($lang);
	$dir_lang = " dir='$lang_dir'";
}

// selectionner une langue
function lang_select ($lang='') {
	global $pile_langues, $lcm_lang;
	array_push($pile_langues, $lcm_lang);
	lcm_set_language($lang);
}

// revenir a la langue precedente
function lang_dselect ($rien='') {
	global $pile_langues;
	lcm_set_language(array_pop($pile_langues));
}


//
// Show a selection menu for the language
// - 'var_lang_lcm' = language of the interface
// - 'var_lang' = [NOT USED] langue de l'article, espace public
// 
function menu_languages($select_name = 'var_lang_lcm', $default = '', $text = '', $herit = '') {
	global $connect_id_auteur;

	$ret = '';

	if ($default == '')
		$default = $GLOBALS['lcm_lang'];

	if ($select_name == 'var_lang_lcm_all') {
		$langues = explode(',', $GLOBALS['all_langs']);
		// [ML] XXX because I need a normal var_lang_lcm, but with all 
		// the languages, instead, the function parameters should be changed.
		$select_name = 'var_lang_lcm';
	} else {
		$langues = explode(',', read_meta('available_languages'));
	}

	// We do not offer a choice if there is only one language installed
	if (count($langues) <= 1)
		return;

	$lien = $GLOBALS['clean_link'];

	if ($select_name == 'var_lang_lcm') {
		include_lcm('inc_admin');
		$target = $lien->getUrl();
		$post = "lcm_cookie.php?id_author=$connect_id_auteur&valeur=".calculer_action_auteur('var_lang_lcm', $connect_id_auteur);

		$ret = "<form action='$post' method='post' style='margin:0px; padding:0px;'>";

		if ($target)
			$ret .= "<input type='hidden' name='url' value='$target'>";

		if ($text)
			$ret .= $text;

		$style = "class='forml' style='vertical-align: top; max-height: 24px; margin-bottom: 5px; width: 120px;'";
		// $style = "class='verdana1' style='background-color: $couleur_foncee; max-height: 24px; border: 1px solid white; color: white; width: 100px;'";

		$postcomplet = new Link($post);
		if ($target) $postcomplet->addvar('url', $target);

		$ret .= "\n<select name='$select_name' $style onChange=\"document.location.href='".$postcomplet->geturl()."&$select_name='+this.options[this.selectedIndex].value\">\n";
	} else {
		// XXX TODO FIXME
		// rename class 'forml' to 'form_lang' and adjust CSS
		$ret .= "\n<select class='forml' name='$select_name'>\n";
	}

	sort($langues);
	while (list(, $l) = each ($langues)) {
		if ($l == $default) {
			$selected = ' selected';
		} else {
			$selected = '';
		}
		if ($l == $herit) {
			$ret .= "<option class='maj-debut' style='font-weight: bold;' value='herit'$selected>"
				.translate_language_name($herit)." ("._T('info_multi_herit').")</option>\n";
		}
		else $ret .= "<option class='maj-debut' value='$l'$selected>".translate_language_name($l)."</option>\n";
	}
	$ret .= "</select>\n";

	if ($select_name == 'var_lang_lcm') {
		$ret .= "<noscript><INPUT TYPE='submit' NAME='Valider' VALUE='&gt;&gt;' class='spip_bouton' $style></noscript>";
		$ret .= "</form>";
	} 

	return $ret;
}


//
// High-level language selection function
//
function use_language_of_site() {
	lcm_set_language($GLOBALS['langue_site']);
}

function use_language_of_visitor() {
	global $HTTP_COOKIE_VARS, $flag_ecrire;

	if (!lcm_set_language_from_browser())
		use_language_of_site();

	if ($GLOBALS['author_session']['lang'])
		lcm_set_language($GLOBALS['author_session']['lang']);

	if ($cookie_lang = $HTTP_COOKIE_VARS['lcm_lang'])
		lcm_set_language($cookie_lang);

}

//
// Initialisation
//
function init_languages() {
	global $all_langs, $langue_site, $cache_lang, $cache_lang_modifs;
	global $pile_langues, $lang_typo, $lang_dir;

	$all_langs = read_meta('langues_proposees');
	$langue_site = read_meta('langue_site');
	$cache_lang = array();
	$cache_lang_modifs = array();
	$pile_langues = array();
	$lang_typo = '';
	$lang_dir = '';

	$toutes_langs = Array();
	if (!$all_langs || !$langue_site) {
		if (!$d = @opendir('inc/lang')) return;
		while ($f = readdir($d)) {
			if (ereg('^lcm_([a-z_]+)\.php?$', $f, $regs))
				$toutes_langs[] = $regs[1];
		}
		closedir($d);
		sort($toutes_langs);
		$all_langs2 = join(',', $toutes_langs);

		// Si les langues n'ont pas change, ne rien faire
		if ($all_langs2 != $all_langs) {
			$all_langs = $all_langs2;
			if (!$langue_site) {
				// Initialisation: English by default, else the first language found
				if (ereg(',en,', ",$all_langs,")) $langue_site = 'en';
				else list(, $langue_site) = each($toutes_langs);
				if (defined('_INC_META'))
					write_meta('langue_site', $langue_site);
			}
			if (defined('_INC_META')) {
				write_meta('langues_proposees', $all_langs);
				write_metas();
			}
		}
	}
	init_codes_langues();
}

init_languages();
use_language_of_site();

?>
