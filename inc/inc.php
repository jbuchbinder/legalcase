<?php

// Test if LCM is installed
if (!@file_exists('inc/config/inc_connect.php')) {
	header('Location: install.php');
	exit;
}

include ('inc/inc_version.php');

include_lcm('inc_auth');
include_lcm('inc_presentation');
include_lcm('inc_text');
include_lcm('inc_filters');
include_lcm('inc_calendar');

if (!@file_exists('data/inc_meta_cache.php'))
	write_metas();


//
// Preferences for presentation
//

lcm_log("1- lang = " . $GLOBALS['HTTP_COOKIE_VARS']['lcm_lang']);
lcm_log("2- session = " . $author_session['lang']);

// [ML] This is very important (but dirty hack) to change the language
// from config_author.php, without passing by lcm_cookie.php
if ($sel_language)
	$lang = $sel_language;
else
	$lang = $GLOBALS['HTTP_COOKIE_VARS']['lcm_lang'];

// if ($lang = $GLOBALS['HTTP_COOKIE_VARS']['lcm_lang'] AND $lang <> $author_session['lang'] AND lcm_set_language($lang)) {
if ($lang AND $lang <> $author_session['lang'] AND lcm_set_language($lang)) {
	lcm_query("UPDATE lcm_author SET lang = '".addslashes($lang)."' WHERE id_author  = $connect_id_auteur");
	$author_session['lang'] = $lang;
	lcm_add_session($author_session, $lcm_session);
	lcm_log("session re-saved");
} else {
	lcm_log("session NOT touched");
}

if ($set_couleur) {
	$prefs['couleur'] = floor($set_couleur);
	$prefs_mod = true;
}
if ($set_disp) {
	$prefs['display'] = floor($set_disp);
	$prefs_mod = true;
}
if ($set_options == 'avancees' OR $set_options == 'basiques') {
	$prefs['options'] = $set_options;
	$prefs_mod = true;
}

if ($set_ecran) {
	// Set a cookie, since this features depends more on the navigator than on the user
	// ecran == screen/monitor
	lcm_setcookie('spip_ecran', $set_ecran, time() + 365 * 24 * 3600);
	$spip_ecran = $set_ecran;
}
if (!$spip_ecran) $spip_ecran = "etroit";

// deux globales (compatibilite ascendante)
$options      = $prefs['options'];
$spip_display = $prefs['display'];


// Green
if (!$couleurs_spip[1]) $couleurs_spip[1] = array (
		"couleur_foncee" => "#9DBA00",
		"couleur_claire" => "#C5E41C",
		"couleur_lien" => "#657701",
		"couleur_lien_off" => "#A6C113"
);
// Light Violet
if (!$couleurs_spip[2]) $couleurs_spip[2] = array (
		"couleur_foncee" => "#eb68b3",
		"couleur_claire" => "#ffa9e6",
		"couleur_lien" => "#E95503",
		"couleur_lien_off" => "#8F004D"
);
// Orange
if (!$couleurs_spip[3]) $couleurs_spip[3] = array (
		"couleur_foncee" => "#fa9a00",
		"couleur_claire" => "#ffc000",
		"couleur_lien" => "#81A0C1",
		"couleur_lien_off" => "#FF5B00"
);
// Salmon
if (!$couleurs_spip[4]) $couleurs_spip[4] = array (
		"couleur_foncee" => "#CDA261",
		"couleur_claire" => "#FFDDAA",
		"couleur_lien" => "#5E0283",
		"couleur_lien_off" => "#472854"
);
//  Light blue
if (!$couleurs_spip[5]) $couleurs_spip[5] = array (
		"couleur_foncee" => "#5da7c5",
		"couleur_claire" => "#97d2e1",
		"couleur_lien" => "#869100",
		"couleur_lien_off" => "#5B55A0"
);
//  Grey
if (!$couleurs_spip[6]) $couleurs_spip[6] = array (
		"couleur_foncee" => "#727D87",
		"couleur_claire" => "#C0CAD4",
		"couleur_lien" => "#854270",
		"couleur_lien_off" => "#666666"
);


$choix_couleur = $prefs['couleur'];
if (strlen($couleurs_spip[$choix_couleur]['couleur_foncee']) < 7) $choix_couleur = 1;

$couleur_foncee = $couleurs_spip[$choix_couleur]['couleur_foncee'];
$couleur_claire = $couleurs_spip[$choix_couleur]['couleur_claire'];
$couleur_lien = $couleurs_spip[$choix_couleur]['couleur_lien'];
$couleur_lien_off = $couleurs_spip[$choix_couleur]['couleur_lien_off'];


//
// Version management
//

write_metas();

/* [ML] Not needed anymore, perhaps, since done with db_version */
/*
$installed_version = (double) read_meta('version_lcm');
if ($installed_version <> $lcm_version) {
	lcm_page_start();
	if (!$installed_version)
		$installed_version = _T('info_anterieur');

	echo "<p>[ML] Installed version = $installed_version ; lcm_version = $lcm_version </p>"; // FIXME
	echo "<blockquote><blockquote><h4><font color='red'>"._T('title_technical_message')."</font><br> "._T('info_procedure_maj_version')."</h4>
	"._T('info_administrateur_site_01')." <a href='upgrade.php3'>"._T('info_administrateur_site_02')."</a></blockquote></blockquote><p>";

	lcm_page_end();
	exit;
} */

//
// Database version management
// 

$installed_db_version = read_meta('lcm_db_version');

if ($installed_db_version <> $lcm_db_version) {
	lcm_page_start("Database upgrade", "install");
	if (!$installed_version)
		$installed_version = "old version";

	echo "<div class='box_warning'>\n";
	echo "<p><b>" . _T('title_technical_message') . _T('typo_column') . "</b> The
		format of the database has changed. <a href='lcm_upgrade.php'>To 
		proceed with the automatic upgrade, click here</a>. You are also 
		encouraged to make a backup before proceeding.</p>\n";
	echo "</div>\n";

	lcm_page_end();
	exit;
}

//
// Management of the global configuration of the site
// [ML] Why is this done here?
//

if (!$adresse_site) {
	$nom_site_spip = read_meta("nom_site");
	$adresse_site = read_meta("adresse_site");
}
if (!$activer_breves){
	$activer_breves = read_meta("activer_breves");
	$articles_mots = read_meta("articles_mots");
}

if (!$activer_statistiques){
	$activer_statistiques = read_meta("activer_statistiques");
}

function tester_rubrique_vide($id_rubrique) {
	lcm_log("Call of deprecated function: tester_rubrique_vide");
	return true;
}


//
// Fetch the administration cookie
$cookie_admin = $HTTP_COOKIE_VARS['lcm_admin'];


?>
