<?php

//
// Execute this file only once
if (defined('_INC_PRESENTATION')) return;
define('_INC_PRESENTATION', '1');

include_lcm('inc_filters');
include_lcm('inc_text');
include_lcm('inc_lang');

use_language_of_visitor();

/*
 * [ML] Ok, this file really is a mess.
 * Please be careful where you step.
 */

//
// The following functions are currently in used.
//


// Presentation of the interface, headers and "<head></head>".
// XXX You may want to use lcm_page_start() instead.
function lcm_html_start($title = "", $onLoad = "") {
	global $couleur_foncee, $couleur_claire, $couleur_lien, $couleur_lien_off;
	global $flag_ecrire;
	global $spip_lang_rtl, $spip_lang_left;
	global $mode;
	global $connect_status, $connect_toutes_rubriques;
	
	$nom_site_spip = entites_html(lire_meta("nom_site"));
	$title = textebrut(typo($title));

	if (!$nom_site_spip)
		$nom_site_spip="LCM";

	if (!$charset = lire_meta('charset'))
		$charset = 'utf-8';

	@Header("Expires: 0");
	@Header("Cache-Control: no-cache,no-store");
	@Header("Pragma: no-cache");
	@Header("Content-Type: text/html; charset=$charset");

	echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n"
		. "<html>\n"
		. "<head>\n"
		. "\t<title>[$nom_site_spip] $title</title>\n"
		. "\t" . '<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">'
		. "\t" . '<link rel="stylesheet" type="text/css" href="';

	// [ML] We might need something similar 
	// (to calculate some colors in the CSS depending on variables)
	// [ML] couleur_claire == light color, couleur_foncee == dark color
	$link = new Link('lcm_css_basic.php');
	$link->addVar('couleur_claire', $couleur_claire);
	$link->addVar('couleur_foncee', $couleur_foncee);
	$link->addVar('left', $GLOBALS['spip_lang_left']);
	$link->addVar('right', $GLOBALS['spip_lang_right']);
	echo $link->getUrl(). '">' . "\n";

 	// echo "\t" . '<link rel="stylesheet" href="styles/spip_style_visible.css" type="text/css" title="visible" />' . "\n";

	echo '</head>' . "\n";

	// [ML] couleur_lien = link color
	// We should clean this I guess. It could simply be in the CSS files
	echo "<body text='#000000' bgcolor='#f8f7f3' link='$couleur_lien' vlink='$couleur_lien_off' alink='$couleur_lien_off' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' frameborder='0'";

	// [ML] This also, even dough we do not support arabic/hebrew/farsi at the moment
	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo ">";
}

function lcm_page_start($title = "") {
	// [ML] Yes, too many globals. I will clean this later.
	global $couleur_foncee; // dark color
	global $couleur_claire; // light
	global $adresse_site;
	global $connect_id_auteur;
	global $connect_status;
	global $auth_can_disconnect, $connect_login;
	global $options, $spip_display, $spip_ecran;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;
	global $clean_link;

	// Clean the global link (i.e. remove actions passed in the URL)
	$clean_link->delVar('var_lang');
	$clean_link->delVar('set_options');
	$clean_link->delVar('set_couleur');
	$clean_link->delVar('set_disp');
	$clean_link->delVar('set_ecran');

	lcm_html_start($title, $onLoad);

	
	// XXX TODO
	// most of the header html after <head></head> should go here
	//

	// Opening of the "main" part of the page
	// [ML] This is temporary untill we cleanup the HTML
	echo "<center>";
	echo "<table><tr><td>\n";
}

// Footer of the interface
// XXX You may want to use lcm_page_end() instead
function lcm_html_end() {

	echo "</font>";

	// Create a new session cookie if the IP changed
	// [ML] FIXME update paths and names
	if ($GLOBALS['lcm_session'] && $GLOBALS['author_session']['ip_change']) {
		echo "<img name='img_session' src='img_pack/rien.gif' width='0' height='0'>\n";
		echo "<script type='text/javascript'><!-- \n";
		echo "document.img_session.src='lcm_cookie.php?change_session=oui';\n";
		echo "// --></script>\n";
	}

	echo "</body></html>\n";
	flush();
}


function lcm_page_end($credits = '') {
	global $lcm_version_shown;
	global $connect_id_auteur;
	global $auth_can_disconnect, $connect_login;

	echo "</td></tr></table>";

	///
	// Insert FOOTER stuff here
	// Ignore the rest after this for now.
	// 



	// [ML] Off-topic note, seen while removing code:
	// http://www.dynamicdrive.com/dynamicindex11/abox.htm

	debut_grand_cadre(); // start_big_frame, needs cleaning

	echo "<div align='right' class='verdana2'>";
	echo "<b>LCM $lcm_version_shown</b> ";
	echo _T('info_copyright');

	echo "<br>"._T('info_copyright_doc');

	if (ereg("jimmac", $credits))
		echo "<br>"._T('lien_icones_interface');

	echo "</div><p>";

	fin_grand_cadre();

	echo "</center>";
	echo "</td></tr></center>\n";

	//
	// Language choice (temporarely put here by [ML])
	//
	if ($GLOBALS['all_langs']) {
		echo menu_languages('var_lang_lcm');
	}

	if ($auth_can_disconnect) {	
		echo "<a href='lcm_cookie.php?logout=$connect_login' class='icone26' onMouseOver=\"changestyle('bandeaudeconnecter','visibility', 'visible');\"><img src='img_pack/deconnecter-24$spip_lang_rtl.gif' border='0'></a>";
	}

	lcm_html_end();
}




// *************
// The following functions may not be in use. Still double-check 
// before deleting!
// *************

//
// Help
//
function aide($aide='') {
	global $couleur_foncee, $lcm_lang, $spip_lang_rtl;

	if (!$aide) return;

	return "&nbsp;&nbsp;<a class='aide' href=\"help_index.php?aide=$aide&var_lang=$lcm_lang\" target=\"spip_aide\" ".
		"onclick=\"javascript:window.open(this.href, 'spip_aide', 'scrollbars=yes, ".
		"resizable=yes, width=740, height=580'); return false;\"><img ".
		"src=\"images/lcm/help.png\" alt=\""._T('info_image_aide')."\" ".
		"title=\""._T('titre_image_aide')."\" width=\"12\" height=\"12\" border=\"0\" ".
		"align=\"middle\"></a>";
}


// Fake HR, with color control
function hr($color, $retour = false) {
	$ret = "<div style='height: 1px; margin-top: 5px; padding-top: 5px; border-top: 1px solid $color;'></div>";
	
	if ($retour) return $ret;
	else echo $ret;
}


// Frames (start)
function debut_cadre($style, $icone = "", $fonction = "", $titre = "") {
	global $spip_display, $spip_lang_left;
	static $accesskey = 97; // a

	// accesskey pour accessibilite espace prive
	$accesskey_c = chr($accesskey++);
	$ret = "<a name='access-$accesskey_c' href='#access-$accesskey_c' accesskey='$accesskey_c'></a>";


	$ret .= "<div style='position: relative; z-index: 1;'>";
	if ($spip_display != 1 AND strlen($icone) > 1) {
		$style_gauche = " style='padding-$spip_lang_left: 38px;'";
		$ret .= "<div style='position: absolute; top: 0px; $spip_lang_left: 10px; z-index: 2;'>";
		if ($fonction) {
			$ret .= "<div style='$bgright"."background: url(img_pack/$icone) no-repeat; padding: 0px; margin: 0px;'>";
			$ret .= "<img src='img_pack/$fonction'>";
			$ret .= "</div>";
		}
		else $ret .= "<img src='img_pack/$icone'>";
		$ret .= "</div>";

		$style_cadre = " style='position: relative; top: 15px; margin-bottom: 15px; z-index: 1;'";
	}

	if ($style == "e") {
		$ret .= "<div class='cadre-e-noir'$style_cadre><div class='cadre-$style'>";
	}
	else {
		$ret .= "<div class='cadre-$style'$style_cadre>";
	}
	
	if (strlen($titre) > 0) {
		$ret .= "<div class='cadre-titre'$style_gauche>$titre</div>";
	}
	
	
	$ret .= "<div class='cadre-padding'>";
	
	
	return $ret;
}


// Frames (end)
function fin_cadre($style="") {
	if ($style == "e") $ret = "</div>";
	$ret .= "</div></div></div>\n";
	$ret .= "<div style='height: 5px;'></div>";
	
	return $ret;
}




function debut_cadre_relief($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('r', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_relief($return = false){
	$retour_aff = fin_cadre('r');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


function debut_cadre_enfonce($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('e', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_enfonce($return = false){

	$retour_aff = fin_cadre('e');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


function debut_cadre_gris_clair($icone='', $return = false, $fonction='', $titre = ''){
	$retour_aff = debut_cadre('gris-clair', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_gris_clair($return = false){
	$retour_aff = fin_cadre('gris-clair');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


function debut_cadre_couleur($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('couleur', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_couleur($return = false){
	$retour_aff = fin_cadre('couleur');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function debut_cadre_trait_couleur($icone='', $return = false, $fonction='', $titre=''){
	$retour_aff = debut_cadre('trait-couleur', $icone, $fonction, $titre);

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_trait_couleur($return = false){
	$retour_aff = fin_cadre('trait-couleur');

	if ($return) return $retour_aff;
	else echo $retour_aff;
}



// an alert box
function debut_boite_alerte() {
	echo "<p><table cellpadding='6' border='0'><tr><td width='100%' bgcolor='red'>";
	echo "<table width='100%' cellpadding='12' border='0'><tr><td width='100%' bgcolor='white'>";
}

function fin_boite_alerte() {
	echo "</td></tr></table>";
	echo "</td></tr></table>";
}


// an information box
function debut_boite_info() {
	echo "<div class='cadre-info'>";
}

function fin_boite_info() {
	echo "</div>";
}

// yet another box
function bandeau_titre_boite($titre, $afficher_auteurs, $boite_importante = true) {
	global $couleur_foncee;
	if ($boite_importante) {
		$couleur_fond = $couleur_foncee;
		$couleur_texte = '#FFFFFF';
	}
	else {
		$couleur_fond = '#EEEECC';
		$couleur_texte = '#000000';
	}
	echo "<tr bgcolor='$couleur_fond'><td width=\"100%\"><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='$couleur_texte'>";
	echo "<B>$titre</B></FONT></TD>";
	if ($afficher_auteurs){
		echo "<TD WIDTH='100'>";
		echo "<img src='img_pack/rien.gif' alt='' width='100' height='12' border='0'>";
		echo "</TD>";
	}
	echo "<TD WIDTH='90'>";
	echo "<img src='img_pack/rien.gif' alt='' width='90' height='12' border='0'>";
	echo "</TD>";
	echo "</TR>";
}

// .. and another box
function bandeau_titre_boite2($titre, $logo="", $fond="white", $texte="black") {
	global $spip_lang_left, $spip_display;
	
	if (strlen($logo) > 0 AND $spip_display != 1) {
		echo "<div style='position: relative;'>";
		echo "<div style='position: absolute; top: -12px; $spip_lang_left: 3px;'><img src='img_pack/$logo'></div>";
		echo "<div style='background-color: $fond; color: $texte; padding: 3px; padding-$spip_lang_left: 30px; border-bottom: 1px solid #444444;' class='verdana2'><b>$titre</b></div>";
	
		echo "</div>";
	} else {
		echo "<div style='background-color: $fond; color: $texte; padding: 3px; border-bottom: 1px solid #444444;' class='verdana2'><b>$titre</b></div>";
	}

}


// Shows the author's status from the SQL query result
// [ML] not used, may never be
function author_status($row) {
	global $connect_status;

	switch($row['statut']) {
		case "admin":
			$image = "<img src='img_pack/admin-12.gif' alt='' title='"._T('titre_image_administrateur')."' border='0'>";
			break;
		case "1comite":
			if ($connect_status == 'admin' AND ($row['source'] == 'spip' AND !($row['pass'] AND $row['login'])))
				$image = "<img src='img_pack/visit-12.gif' alt='' title='"._T('titre_image_redacteur')."' border='0'>";
			else
				$image = "<img src='img_pack/redac-12.gif' alt='' title='"._T('titre_image_redacteur_02')."' border='0'>";
			break;
		case "5poubelle":
			$image = "<img src='img_pack/poubelle.gif' alt='' title='"._T('titre_image_auteur_supprime')."' border='0'>";
			break;
		case "6forum":
			$image = "<img src='img_pack/visit-12.gif' alt='' title='"._T('titre_image_visiteur')."' border='0'>";
			break;
		case "nouveau":
		default:
			$image = '';
			break;
	}

	return $image;
}

// La couleur du statut
function puce_statut($statut, $type='article') {
	switch ($statut) {
		case 'publie':
			return 'verte';
		case 'prepa':
			return 'blanche';
		case 'prop':
			return 'orange';
		case 'refuse':
			return 'rouge';
		case 'poubelle':
			return 'poubelle';
	}
}



//
// un bouton (en POST) a partir d'un URL en format GET
//
function bouton($titre,$lien) {
	$lapage=substr($lien,0,strpos($lien,"?"));
	$lesvars=substr($lien,strpos($lien,"?")+1,strlen($lien));

	echo "\n<form action='$lapage' method='get'>\n";
	$lesvars=explode("&",$lesvars);
	
	for($i=0;$i<count($lesvars);$i++){
		$var_loc=explode("=",$lesvars[$i]);
		echo "<input type='Hidden' name='$var_loc[0]' value=\"$var_loc[1]\">\n";
	}
	echo "<input type='submit' name='Submit' class='fondo' value=\"$titre\">\n";
	echo "</form>";
}



// Link to change the color
function lien_change_var($lien, $set, $couleur, $coords, $titre, $mouseOver="") {
	$lien->addVar($set, $couleur);
	return "\n<area shape='rect' href='". $lien->getUrl() ."' coords='$coords' title=\"$titre\" $mouseOver>";
}


//
// Cadre centre (haut de page)
//

function debut_grand_cadre(){
	global $spip_ecran;
	
	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;
	echo "\n<br><br><table width='$largeur' cellpadding='0' cellspacing='0' border='0'>";
	echo "\n<tr>";
	echo "<td width='$largeur' class='serif'>";

}

function fin_grand_cadre(){
	echo "\n</td></tr></table>";
}


//
// ******************************************************************
//


/*
 * Header function for the installation
 * They are used by install.php and lcm_test_dirs.php
 */
function install_html_start($title = 'AUTO') {
	global $spip_lang_rtl;

	if ($title=='AUTO')
		$title=_T('info_installation_legal_case_management');

	if (!$charset = read_meta('charset'))
		$charset = 'utf-8';

	@Header("Content-Type: text/html; charset=$charset");

	echo "<html><head>
	<title>$title</title>
	<meta http-equiv='Expires' content='0'>
	<meta http-equiv='cache-control' content='no-cache,no-store'>
	<meta http-equiv='pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=$charset'>
	<style>
	<!--
	a {text-decoration: none; }
	A:Hover {color:#FF9900; text-decoration: underline;}
	.forml {width: 100%; background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.formo {width: 100%; background-color: #FFF0E0; background-position: center bottom; weight: bold; float: none; color: #000000}
	.fondl {background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.fondo {background-color: #FFF0E0; background-position: center bottom; float: none; color: #000000}
	.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
	.serif { font-family: Georgia, Garamond, Times New Roman, serif; }
	-->
	</style>
	</head>
	<body bgcolor='#FFFFFF' text='#000000' link='#E86519' vlink='#6E003A' alink='#FF9900' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'";

	if ($spip_lang_rtl) echo " dir='rtl'";

	echo "><br><br><br>
	<center>
	<table width='450'>
	<tr><td width='450' class='serif'>
	<font face='Verdana,Arial,Sans,sans-serif' size='4' color='#970038'><B>$title</b></font>\n<p>";
}

/*
 * Footer function for the installation
 * They are used by install.php and lcm_test_dirs.php
 */
function install_html_end() {
	echo '
	</font>
	</td></tr></table>
	</center>
	</body>
	</html>
	';
}



?>
