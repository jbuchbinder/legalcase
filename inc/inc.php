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
// [ML] include_lcm("inc_urls");
// [ML] include_lcm("inc_layer");
// [ML] include_lcm("inc_rubriques");
include_lcm('inc_calendar');

if (!@file_exists('data/inc_meta_cache.php'))
	ecrire_metas();


//
// Preferences for presentation
//

if ($lang = $GLOBALS['HTTP_COOKIE_VARS']['lcm_lang'] AND $lang <> $auteur_session['lang'] AND changer_langue($lang)) {
	spip_query ("UPDATE lcm_author SET lang = '".addslashes($lang)."' WHERE id_author  = $connect_id_auteur");
	$auteur_session['lang'] = $lang;
	ajouter_session($auteur_session, $lcm_session);
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
if ($prefs_mod) {
	// [ML TODO] spip_query ("UPDATE spip_auteurs SET prefs = '".addslashes(serialize($prefs))."' WHERE id_auteur = $connect_id_auteur");
}

if ($set_ecran) {
	// Set a cookie, since this features depends more on the navigator than on the user
	// [ML TODO] spip_setcookie('spip_ecran', $set_ecran, time() + 365 * 24 * 3600);
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

/*
switch ($prefs['couleur']) {
	case 6:
		/// Yellow
		$couleur_foncee="#9DBA00";
		$couleur_claire="#C5E41C";
		$couleur_lien="#657701";
		$couleur_lien_off="#A6C113";
		break;
	case 1:
		/// Some sort of violet
		$couleur_foncee="#eb68b3";
		$couleur_claire="#ffa9e6";
		$couleur_lien="#E95503";
		$couleur_lien_off="#8F004D";
		break;
	case 2:
		/// Orange
		$couleur_foncee="#fa9a00";
		$couleur_claire="#ffc000";
		$couleur_lien="#81A0C1";
		$couleur_lien_off="#FF5B00";
		break;
	case 3:
		/// Salmon
		$couleur_foncee="#CDA261";
		$couleur_claire="#FFDDAA";
		$couleur_lien="#5E0283";
		$couleur_lien_off="#472854";
		break;
	case 4:
		/// Light blue
		$couleur_foncee="#5da7c5";
		$couleur_claire="#97d2e1";
		$couleur_lien="#869100";
		$couleur_lien_off="#5B55A0";
		break;
	case 5:
		/// Grey
		$couleur_foncee="#727D87";
		$couleur_claire="#C0CAD4";
		$couleur_lien="#854270";
		$couleur_lien_off="#666666";
		break;
	default:
		/// Yellow
		$couleur_foncee="#9DBA00";
		$couleur_claire="#C5E41C";
		$couleur_lien="#657701";
		$couleur_lien_off="#A6C113";
}
*/


//
// Version management
//

write_metas();

$installed_version = (double) read_meta('version_lcm');
if ($installed_version <> $lcm_version) {
	lcm_page_start();
	if (!$installed_version)
		$installed_version = _T('info_anterieur');

	echo "<p>[ML] Installed version = $installed_version ; lcm_version = $lcm_version </p>"; // FIXME
	echo "<blockquote><blockquote><h4><font color='red'>"._T('info_message_technique')."</font><br> "._T('info_procedure_maj_version')."</h4>
	"._T('info_administrateur_site_01')." <a href='upgrade.php3'>"._T('info_administrateur_site_02')."</a></blockquote></blockquote><p>";

	lcm_page_end();
	exit;
}


//
// Management of the global configuration of the site
// [ML] Why is this done here?
//

if (!$adresse_site) {
	$nom_site_spip = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");
}
if (!$activer_breves){
	$activer_breves = lire_meta("activer_breves");
	$articles_mots = lire_meta("articles_mots");
}

if (!$activer_statistiques){
	$activer_statistiques = lire_meta("activer_statistiques");
}

if (!$nom_site_spip) {
	$nom_site_spip = _T('info_mon_site_spip');
	ecrire_meta("nom_site", $nom_site_spip);
	ecrire_metas();
}

if (!$adresse_site) {
	$adresse_site = "http://$HTTP_HOST".substr($REQUEST_URI, 0, strpos($REQUEST_URI, "/ecrire"));
	ecrire_meta("adresse_site", $adresse_site);
	ecrire_metas();
}


function tester_rubrique_vide($id_rubrique) {
	lcm_log("Call of deprecated function: tester_rubrique_vide");
	return true;
}


//
// Fetch the administration cookie
$cookie_admin = $HTTP_COOKIE_VARS['lcm_admin'];


?>
