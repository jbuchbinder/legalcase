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
function lcm_html_start($title = "AUTO", $css_files = "") {
	global $couleur_foncee, $couleur_claire, $couleur_lien, $couleur_lien_off;
	global $flag_ecrire;
	global $spip_lang_rtl, $spip_lang_left;
	global $mode;
	global $connect_status, $connect_toutes_rubriques;
	
	$lcm_site_name = entites_html(read_meta("nom_site"));
	$title = textebrut(typo($title));

	// Don't show site name (if none) while installation
	if (!$lcm_site_name && $title == "AUTO")
		$lcm_site_name = "LCM";

	if (!$charset = read_meta('charset'))
		$charset = 'utf-8';

	@Header("Expires: 0");
	@Header("Cache-Control: no-cache,no-store");
	@Header("Pragma: no-cache");
	@Header("Content-Type: text/html; charset=$charset");
	
	//[KM] Here begins XHTML code
	//My opinion is to use XHTML 1.0 Transitional because it supports simultaneously
	//HTML code style and XHTML code style. Some kind of universal version.
	
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
	<title>". ($lcm_site_name ? $lcm_site_name ." | " : '') . $title ."</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=". $charset ."\" />\n";

	// [ML] We might need something similar 
	// (to calculate some colors in the CSS depending on variables)
	// [ML] couleur_claire == light color, couleur_foncee == dark color
	
	//[KM]
	//$link = new Link('lcm_css_basic.php');
	//$link->addVar('couleur_claire', $couleur_claire);
	//$link->addVar('couleur_foncee', $couleur_foncee);
	//$link->addVar('left', $GLOBALS['spip_lang_left']);
	//$link->addVar('right', $GLOBALS['spip_lang_right']);
	//echo $link->getUrl(). '">' . "\n";
	
	//echo "\t<link rel='stylesheet' href='styles/lcm_styles.css' type='text/css' />\n";
	
	//[KM]
	//This is the basic and the alternative style sheets
	//Some colors maybe are confusing but I will fix that later
	
	echo "<link rel=\"stylesheet\" href=\"styles/lcm_ui_default.css\" type=\"text/css\" />\n";
	echo "<link rel=\"alternate stylesheet\" type=\"text/css\" media=\"screen\" href=\"styles/lcm_ui_blue.css\" title=\"blue\" />\n";
	echo "<link rel=\"alternate stylesheet\" type=\"text/css\" media=\"screen\" href=\"styles/lcm_ui_orange.css\" title=\"orange\" />\n";
	echo "<link rel=\"alternate stylesheet\" type=\"text/css\" media=\"screen\" href=\"styles/lcm_ui_monochrome.css\" title=\"mono\" />\n";

	// It is the responsability of the function caller to make sure that
	// the filename does not cause problems...
	$css_files_array = explode(",", $css_files);
	foreach ($css_files_array as $f)
		echo "\t<link rel='stylesheet' href='styles/lcm_$f.css' type='text/css' />\n";
	
	echo "</head>\n";

	// We do not support arabic/hebrew/farsi at the moment
	/*
	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo ">";
	*/
	
	echo "<body>\n";
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

	lcm_html_start($title); // , $onLoad);
	
	// XXX TODO
	// most of the header html after <head></head> should go here
	//
	
	echo "
	<div id='header'>&nbsp;</div>
	<div id='wrapper'>
		<div id=\"container\">
			<div id=\"content\">
			<!-- This is the navigation column, usually used for menus and brief information -->
				<div id=\"navigation_menu_column\">
				<!-- Start of \"navigation_menu_column\" content -->
					<div id=\"nav_menu_box\">
						<div class=\"nav_column_menu_head\">Main menu</div>
						<ul id=\"nav_menu_list\">
							<li><a href=\"listcases.php\" class=\"main_nav_btn\">My cases</a></li>
							<li><a href=\"edit_client.php\" class=\"main_nav_btn\">Add Client</a></li>
							<li><a href=\"edit_case.php\" class=\"main_nav_btn\">New Case</a></li>
							<li>Edit <abbr title=\"User Interface\">UI</abbr> preferences</li>\n";
	
	if ($connect_status == 'admin') {
		// TODO: Provide better name
		echo "\t\t\t\t\t\t\t<li><a href=\"config_site.php\">Site conf</a></li>\n";
		echo "\t\t\t\t\t\t\t<li><abbr title=\"All cases, categorised by date, keyword, etc. (admin only)\">Archives</abbr></li>\n";
		echo "\t\t\t\t\t\t\t<li><abbr title=\"Generate reports on all cases (admin only)\">Reports</abbr></li>\n";
	}
							
	echo "
						</ul>
					</div>
					<div id=\"nav_menu_box\">
						<div class=\"nav_column_menu_head\">Calendar</div>
						<p class=\"nav_column_text\">". date("l, dS F Y") ."</p>
					</div>
					<div id=\"nav_menu_box\">
						<div class=\"nav_column_menu_head\">Next 7 meetings</div>
						<p class=\"nav_column_text\">
						<strong>Today</strong><br />
						9:30 - Meeting with Mr. Smith<br /><br />
						11:00 - At the court
						</p>
						<hr class=\"hair_line\" />
						<p class=\"nav_column_text\">
						<strong>Tomorrow(28.09.2004)</strong><br />
						8:30 - Meeting with Mr. Johnson<br /><br />
						10:00 - At the court
						</p>
					</div>
					<br /><br />
				<!-- End of \"navigation_menu_column\" content -->
				</div>

				<!-- The main content will be here - all the data, html forms, search results etc. -->
				<div id=\"main_column\">
				
					<!-- Start of 'main_column' content -->
					<h3 class=\"content_head\">". $title ."</h3>

					<!-- [ML] Added by matt, who likes to mess up clean CSS -->
					<div align='left'>
	";
}

// Footer of the interface
// XXX You may want to use lcm_page_end() instead
function lcm_html_end() {
	
	//[KM] Font tags are generally not recommended since we have CSS which defines the font family,
	//different sizes, colors, letter spacing and some other font properties
	
	//echo "</font>";

	// Create a new session cookie if the IP changed
	// [ML] FIXME update paths and names
	if ($GLOBALS['lcm_session'] && $GLOBALS['author_session']['ip_change']) {
		echo "<img name='img_session' src='img_pack/rien.gif' width='0' height='0'>\n";
		echo "<script type='text/javascript'><!-- \n";
		echo "document.img_session.src='lcm_cookie.php?change_session=oui';\n";
		echo "// --></script>\n";
	}
	
	
	flush();
}


function lcm_page_end($credits = '') {
	global $lcm_version_shown;
	global $connect_id_auteur;

	global $author_session;
	global $find_org_string;
	global $find_case_string;
	global $find_client_string;

	///
	// Insert FOOTER stuff here
	// Ignore the rest after this for now.
	//

	//[KM] The bottom of a single page
	//
	echo "
				</div> <!-- align left -->

				<!-- End of 'main_column' content -->
				</div>
			</div>
		</div>
<!-- The initial intention was that here can be placed some UI preferences -->
<!-- but I think it will be much better to put the search boxes -->
<!-- The right and the left column can be very long, so, we can put here a lot of additional information, some tiny help hints and so -->
		<div id=\"prefs_column\">
<!-- Start of \"prefs_column\" content -->
			<div class=\"prefs_column_menu_head\">Profile</div>
			<p class=\"prefs_column_text\"><strong>Name: </strong>"
				. $author_session['name_first'] . ' '
				. $author_session['name_middle'] . ' '
				. $author_session['name_last']
				. "<br /><br />
			<a href=\"#\" class=\"prefs_bold_lnk\">[ update profile ]</a>&nbsp;&nbsp;&nbsp;<a href=\"lcm_cookie.php?logout=".  $author_session['username'] ."\" class=\"prefs_bold_lnk\">[ logout ]</a>
			</p>
			<div class=\"prefs_column_menu_head\">Search</div>
			<p class=\"prefs_column_text\">
			<form name=\"frm_find_case\" class=\"search_form\" action=\"listcases.php\" method=\"POST\">
			Find case<br />
			<input type=\"text\" name=\"find_case_string\" size=\"10\" class=\"search_form_txt\"";
	if (isset($find_case_string)) echo " value='$find_case_string'";
	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"Search\" class=\"search_form_btn\" />
			</form>
			<form name=\"frm_find_client\" class=\"search_form\" action=\"listclients.php\" method=\"POST\">
			Find client<br />
			<input type=\"text\" name=\"find_client_string\" size=\"10\" class=\"search_form_txt\"";
	if (isset($find_client_string)) echo " value='$find_client_string'";
	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"Search\" class=\"search_form_btn\" />
			</form>
			<form name=\"frm_find_client\" class=\"search_form\" action=\"listorgs.php\" method=\"POST\">
			Find organisation<br />
			<input type=\"text\" name=\"find_org_string\" size=\"10\" class=\"search_form_txt\"";
	if (isset($find_org_string)) echo " value='$find_org_string'";
	echo " />&nbsp;<input type=\"submit\" name=\"submit\" value=\"Search\" class=\"search_form_btn\" />
			</form>
			</p>
<!-- End of \"prefs_column\" content -->
		</div>
		<div class=\"clearing\">&nbsp;</div>
	</div>
<div id=\"footer\">". _T('title_software') ." (". $lcm_version_shown .")</div>\n";

	//
	// Language choice (temporarely put here by [ML])
	//
	if ($GLOBALS['all_langs']) {
		echo "<br/><div align=\"right\">" . menu_languages('var_lang_lcm') .  "</div>\n";
	}

	echo "
</body>
</html>";

	// [ML] Off-topic note, seen while removing code:
	// http://www.dynamicdrive.com/dynamicindex11/abox.htm

	//debut_grand_cadre(); // start_big_frame, needs cleaning
	
	//[KM] This is some copyright info and its place is not here I think
	//But I'm not sure
	/*
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
	*/

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


	if ($title == 'AUTO')
		$title = _T('install_title_installation_start');

	lcm_html_start($title, "install");

/*
*/

	echo "\t<br/>\n";
	echo "\t<div align='center'>\n";
	echo "\t\t<div align='left' style='width: 450px;'>\n";
	echo "\t\t<h1><b>$title</b></h1>\n";

	echo "\n<!-- END install_html_start() -->\n\n";
}

/*
 * Footer function for the installation
 * They are used by install.php and lcm_test_dirs.php
 */
function install_html_end() {
	echo "
			</div>
		</div>
	</body>
	</html>
";
}



?>
