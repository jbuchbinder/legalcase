<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2005 Free Software Foundation, Inc.

	This program is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published by the 
	Free Software Foundation; either version 2 of the License, or (at your 
	option) any later version.

	This program is distributed in the hope that it will be useful, but 
	WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
	or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
	for more details.

	You should have received a copy of the GNU General Public License along 
	with this program; if not, write to the Free Software Foundation, Inc.,
	59 Temple Place, Suite 330, Boston, MA  02111-1307, USA

	$Id: inc_filters.php,v 1.35 2005/03/01 10:38:25 antzi Exp $
*/

// Execute this file only once
if (defined('_INC_FILTERS')) return;
define('_INC_FILTERS', '1');


// Makes $match substring of $source in bold
function highlight_matches($source, $match) {
	// Initialize variables
	$model = strtolower($source);
	$match = strtolower($match);
	$p = 0;
	$result = '';
	$ml = strlen($match);

	if ($ml > 0) {
		$i = strpos($model, $match);

		// Cycle each match
		while (!($i === false)) {
			$result .= (substr($source, $p, $i - $p) . '<b>' . substr($source, $i, $ml) . '</b>');
			$p = $i + $ml;
			$i = strpos($model, $match, $p);
		}
	}

	$result .= substr($source, $p, strlen($source) - $p);
	return $result;
}


// Format the date according to the user's preferences or
// the localised format
function format_date($timestamp = '', $format = 'full') {
	// XXX [ML] this is an absurd waste and redundant, but
	// it works well and accepts many formats.. and I am tired.
	// The most common case anyway, will be to have a date in
	// format 0000-00-00 HH:MM:DD

	if (! $timestamp)
		$timestamp = strftime("%Y-%m-%d %H:%M:%S", mktime());
	
	if (is_numeric($timestamp))
		$timestamp = strftime("%Y-%m-%d %H:%M:%S", $timestamp);

	// Reacts strangely when date is 00:00:00
	if (preg_match('/0000-.*/', $timestamp))
		return '';

	$dd = recup_date($timestamp);
	$tt = recup_time($timestamp);

	// [AG] strftime() always returns 0 for me (Windows 2K server?).
	// The following works, but Sunday is day 0 instead of 7
	// $day_of_w = strftime("%u", mktime(0, 0, 0, $dd[1], $dd[2], $dd[0]));
	$day_of_w = date("w", mktime(0, 0, 0, $dd[1], $dd[2], $dd[0]));

	$my_date = _T('date_format_' . $format, array(
				'day_name' => _T('date_wday_' . ($day_of_w + 0)),
				'month_name' => _T('date_month_' . ($dd[1] + 0)),
				'month_short' => _T('date_month_short_' .($dd[1] + 0)),
				'month' => ($dd[1] + 0),
				'day_order' => _T('date_day_' . $dd[2]),
				'day' => ($dd[2] + 0),
				'year' => $dd[0],
				'hours' => $tt[0],
				'mins' => $tt[1]));

	return $my_date;
}

// Formats time interval
function format_time_interval($time, $hours_only=false, $hours_only_format='%.2f') {
	if (is_numeric($time) && ($time>0)) {
		if ($hours_only) {
			$days = 0;
			$hours = $time / 3600;
			$minutes = 0;
		} else {
			$days = (int) ($time / 86400);
			$hours = (int) ( ($time % 86400) / 3600);
			$minutes = (int) ( ($time % 3600) / 60);
		}

		$ret = array();
		if ($days) $ret[] = $days . 'd';
		if ($hours) {
			if ($hours_only)
				$ret[] = sprintf($hours_only_format,$hours) . ( ($hours == 1.0) ? ' hr' : ' hrs');
			else
				$ret[] = $hours . 'h';
		}
		if ($minutes) $ret[] = $minutes . 'm';

		return join(', ',$ret);
	} else return '';
}

// Error display function
// Highlights (outlines) errors in the form data
function f_err($fn, $errors) {
	return (isset($errors[$fn]) ? '<span style="color: #ff0000">' . $errors[$fn] . '</span>' : '');
}

function f_err_star($fn, $errors) {
	return (isset($errors[$fn]) ? '<span style="color: #ff0000">*</span>' : '');
}

function show_all_errors($all_errors) {
	$ret = "<ul>";

	if (! count($all_errors))
		return "";

	foreach ($all_errors as $error)
		$ret .= "<li>" . $error . "</li>\n";
	
	$ret .= "</ul>\n";
	return $ret;
}

// Cleans user input string from 'dangerous' characters
function clean_input($string) {
	if (get_magic_quotes_gpc()) {
		return $string;
	} else {
		return addslashes($string);
	}
}

// Cleans text to be send out
function clean_output($string) {
	if (get_magic_quotes_runtime()) {
		return htmlspecialchars(stripslashes($string));
	} else {
		return htmlspecialchars($string);
	}
}

// Joins non-empty elements of the array
function njoin($parts,$separator=' ') {
	if (!empty($parts) && is_array($parts)) {
		foreach ($parts as $key => $value) {
			if (empty($value)) unset($parts[$key]);
		}
		return join($separator,$parts);
	} else return false;
}

// Dirty hack: utf8_decode is mainly used for strlen(),
// so if it is not installed, it's not such a big problem.
// Use with care!
function lcm_utf8_decode($string) {
	if (function_exists("utf8_decode"))
		return utf8_decode($string);
	else
		return $string;
}

function recup_date($numdate) {
	if (! $numdate) return '';

	if (ereg('([0-9]{1,2})/([0-9]{1,2})/([0-9]{1,2})', $numdate, $regs)) {
		$day = $regs[1];
		$month = $regs[2];
		$year = $regs[3];

		if ($year < 90){
			$year = 2000 + $year;
		} else {
			$year = 1900 + $year ;
		}
	} elseif (ereg('([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})',$numdate, $regs)) {
		$year = $regs[1];
		$month = $regs[2];
		$day = $regs[3];
	} elseif (ereg('([0-9]{4})-([0-9]{2})', $numdate, $regs)){
		$year = $regs[1];
		$month = $regs[2];
	}

	if ($year > 4000) $year -= 9000;
	if (substr($day, 0, 1) == '0') $day = substr($day, 1);

	return array($year, $month, $day);
}

function recup_time($numdate) {
	if (!$numdate) return '';

	if (ereg('([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})', $numdate, $regs)) {
		$hours = $regs[1];
		$minutes = $regs[2];
		$seconds = $regs[3];
	}
	return array($hours, $minutes, $seconds);
}



/* ********************************************************
 * DEPRECATED: The following functions will be removed soon
 * ******************************************************** */

// Echappement des entites HTML avec correction des entites "brutes"
// (generees par les butineurs lorsqu'on rentre des caracteres n'appartenant
// pas au charset de la page [iso-8859-1 par defaut])
function corriger_entites_html($texte) {
	return ereg_replace('&amp;(#[0-9]+;)', '&\1', $texte);
}
// idem mais corriger aussi les &amp;eacute; en &eacute; (etait pour backends, mais n'est plus utilisee)
function corriger_toutes_entites_html($texte) {
	return eregi_replace('&amp;(#?[a-z0-9]+;)', '&\1', $texte);
}

function entites_html($texte) {
	return corriger_entites_html(htmlspecialchars($texte));
}

// Transformer les &eacute; dans le charset local
function filtrer_entites($texte) {
	include_lcm('inc_charsets');
	// filtrer
	$texte = html2unicode($texte);
	// remettre le tout dans le charset cible
	return unicode2charset($texte);
}

// Tout mettre en entites pour l'export backend (sauf iso-8859-1)
function entites_unicode($texte) {
	include_lcm('inc_charsets');
	return charset2unicode($texte);
}

// Nettoyer les backend
function texte_backend($texte) {

	// " -> &quot; et tout ce genre de choses
	$texte = str_replace("&nbsp;", " ", $texte);
	$texte = entites_html($texte);

	// verifier le charset
	$texte = entites_unicode($texte);

	// Caracteres problematiques en iso-latin 1
	if (read_meta('charset') == 'iso-8859-1') {
		$texte = str_replace(chr(156), '&#156;', $texte);
		$texte = str_replace(chr(140), '&#140;', $texte);
		$texte = str_replace(chr(159), '&#159;', $texte);
	}

	// nettoyer l'apostrophe curly qui semble poser probleme a certains rss-readers
	$texte = str_replace("&#8217;","'",$texte);

	return $texte;
}

// Suppression basique et brutale de tous les <...>
function supprimer_tags($texte, $rempl = "") {
	// super gavant : la regexp ci-dessous plante sous php3, genre boucle infinie !
	// $texte = ereg_replace("<([^>\"']*|\"[^\"]*\"|'[^']*')*>", $rempl, $texte);
	$texte = ereg_replace("<[^>]*>", $rempl, $texte);
	return $texte;
}

// Convertit les <...> en la version lisible en HTML
function echapper_tags($texte, $rempl = "") {
	$texte = ereg_replace("<([^>]*)>", "&lt;\\1&gt;", $texte);
	return $texte;
}

// Convertit un texte HTML en texte brut
function textebrut($texte) {
	$texte = ereg_replace("[\n\r]+", " ", $texte);
	$texte = eregi_replace("<(p|br)([[:space:]][^>]*)?".">", "\n\n", $texte);
	$texte = ereg_replace("^\n+", "", $texte);
	$texte = ereg_replace("\n+$", "", $texte);
	$texte = ereg_replace("\n +", "\n", $texte);
	$texte = supprimer_tags($texte);
	$texte = ereg_replace("(&nbsp;| )+", " ", $texte);
	// nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
	$texte = str_replace("&#8217;","'",$texte);
	return $texte;
}

// Corrige les caracteres degoutants utilises par les Windozeries
function corriger_caracteres($texte) {
	static $trans;
	if (!$trans) {
		// 145,146,180 = simple quote ; 147,148 = double quote ; 150,151 = tiret long
		$trans['iso-8859-1'] = array(
			chr(146) => "'",
			chr(180) => "'",
			chr(147) => '&#8220;',
			chr(148) => '&#8221;',
			chr(150) => '-',
			chr(151) => '-',
			chr(133) => '...'
		);
		$trans['utf-8'] = array(
			chr(194).chr(146) => "'",
			chr(194).chr(180) => "'",
			chr(194).chr(147) => '&#8220;',
			chr(194).chr(148) => '&#8221;',
			chr(194).chr(150) => '-',
			chr(194).chr(151) => '-',
			chr(194).chr(133) => '...'
		);
	}
	$charset = read_meta('charset');
	if (!$trans[$charset]) return $texte;
	if ($GLOBALS['flag_strtr2']) return strtr($texte, $trans[$charset]);
	reset($trans[$charset]);
	while (list($from, $to) = each($trans[$charset])) 
		$texte = str_replace($from, $to, $texte);
	return $texte;
}


// "127.4 kb" or "3.1 Mb"
function size_in_bytes ($mysize) {
	if ($mysize < 1024) {$mysize = _T('mysize_octets', array('mysize' => $mysize));}
	else if ($mysize < 1024*1024) {
		$mysize = _T('mysize_kb', array('mysize' => ((floor($mysize / 102.4))/10)));
	} else {
		$mysize = _T('mysize_mb', array('mysize' => ((floor(($mysize / 1024) / 102.4))/10)));
	}
	return $mysize;
}


// Transforme n'importe quel champ en une chaine utilisable
// en PHP ou Javascript en toute securite
// < ? php $x = '[(#TEXTE|texte_script)]'; ? >
function texte_script($texte) {
	$texte = str_replace('\\', '\\\\', $texte);
	$texte = str_replace('\'', '\\\'', $texte);
	return $texte;
}


// Rend une chaine utilisable sans dommage comme attribut HTML
function attribut_html($texte) {
	$texte = ereg_replace('"', '&quot;', supprimer_tags($texte));
	return $texte;
}

// Vider les url nulles comme 'http://' ou 'mailto:'
function vider_url($url) {
	if (eregi("^(http:?/?/?|mailto:?)$", trim($url)))
		return false;
	else
		return $url;
}

// Maquiller une adresse e-mail
function antispam($texte) {
	include_ecrire ("inc_acces.php3");
	$masque = creer_pass_aleatoire(3);
	return ereg_replace("@", " $masque ", $texte);
}


//
// Date, heure, saisons
//

function normaliser_date($date) {
	if ($date) {
		$date = vider_date($date);
		if (ereg("^[0-9]{8,10}$", $date))
			$date = date("Y-m-d H:i:s", $date);
		if (ereg("^([12][0-9]{3})([-/]00)?( [-0-9:]+)?$", $date, $regs))
			$date = $regs[1]."-01-01".$regs[3];
		else if (ereg("^([12][0-9]{3}[-/][01]?[0-9])([-/]00)?( [-0-9:]+)?$", $date, $regs))
			$date = ereg_replace("/","-",$regs[1])."-01".$regs[3];
		else if ($GLOBALS['flag_strtotime']) {
			$date = date("Y-m-d H:i:s", strtotime($date));
		}
		else $date = ereg_replace('[^-0-9/: ]', '', $date);
	}
	return $date;
}

function vider_date($letexte) {
	if (ereg("^0000-00-00", $letexte)) return;
	if (ereg("^1970-01-01", $letexte)) return;	// eviter le bug GMT-1
	return $letexte;
}

function heures($numdate) {
	$date_array = recup_time($numdate);
	if ($date_array)
		list($heures, $minutes, $secondes) = $date_array;
	return $heures;
}

function minutes($numdate) {
	$date_array = recup_time($numdate);
	if ($date_array)
		list($heures, $minutes, $secondes) = $date_array;
	return $minutes;
}

function secondes($numdate) {
	$date_array = recup_time($numdate);
	if ($date_array)
		list($heures,$minutes,$secondes) = $date_array;
	return $secondes;
}

function heures_minutes($numdate) {
	return _T('date_fmt_heures_minutes', array('h'=> heures($numdate), 'm'=> minutes($numdate)));
}


function affdate_base($numdate, $vue) {
	global $lcm_lang;
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee, $mois, $jour) = $date_array;
	else
		return '';

	// 1er, 21st, etc.
	$journum = $jour;

	if ($jour == 0)
		$jour = '';
	else if ($jourth = _T('date_jnum'.$jour))
			$jour = $jourth;

	$mois = intval($mois);
	if ($mois > 0 AND $mois < 13) {
		$nommois = _T('date_mois_'.$mois);
		if ($jour)
			$jourmois = _T('date_de_mois_'.$mois, array('j'=>$jour, 'nommois'=>$nommois));
	}

	if ($annee < 0) {
		$annee = -$annee." "._T('date_avant_jc');
		$avjc = true;
	}
	else $avjc = false;

	switch ($vue) {
	case 'saison':
		if ($mois > 0){
			$saison = 1;
			if (($mois == 3 AND $jour >= 21) OR $mois > 3) $saison = 2;
			if (($mois == 6 AND $jour >= 21) OR $mois > 6) $saison = 3;
			if (($mois == 9 AND $jour >= 21) OR $mois > 9) $saison = 4;
			if (($mois == 12 AND $jour >= 21) OR $mois > 12) $saison = 1;
		}
		return _T('date_saison_'.$saison);

	case 'court':
		if ($avjc) return $annee;
		$a = date('Y');
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
		if ($annee != $a) return _T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>ucfirst($nommois), 'annee'=>$annee));
		return _T('date_fmt_jour_mois', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));

	case 'jourcourt':
		if ($avjc) return $annee;
		$a = date('Y');
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
		if ($annee != $a) return _T('date_fmt_jour_mois_annee', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
		return _T('date_fmt_jour_mois', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));

	case 'entier':
		if ($avjc) return $annee;
		if ($jour)
			return _T('date_fmt_jour_mois_annee', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
		else
			return _T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));

	case 'nom_mois':
		return $nommois;

	case 'mois':
		return sprintf("%02s",$mois);

	case 'jour':
		return $jour;

	case 'journum':
		return $journum;

	case 'nom_jour':
		if (!$mois OR !$jour) return '';
		$nom = mktime(1,1,1,$mois,$jour,$annee);
		$nom = 1+date('w',$nom);
		return _T('date_jour_'.$nom);

	case 'mois_annee':
		if ($avjc) return $annee;
		return trim(_T('date_fmt_mois_annee', array('mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee)));

	case 'annee':
		return $annee;
	}

	return "<blink>"._T('info_format_non_defini')."</blink>";
}

function nom_jour($numdate) {
	return affdate_base($numdate, 'nom_jour');
}

function jour($numdate) {
	return affdate_base($numdate, 'jour');
}

function journum($numdate) {
	return affdate_base($numdate, 'journum');
}

function mois($numdate) {
	return affdate_base($numdate, 'mois');
}

function nom_mois($numdate) {
	return affdate_base($numdate, 'nom_mois');
}

function annee($numdate) {
	return affdate_base($numdate, 'annee');
}

function saison($numdate) {
	return affdate_base($numdate, 'saison');
}

function affdate($numdate) {
	return affdate_base($numdate, 'entier');
}

function affdate_court($numdate) {
	return affdate_base($numdate, 'court');
}

function affdate_jourcourt($numdate) {
	return affdate_base($numdate, 'jourcourt');
}

function affdate_mois_annee($numdate) {
	return affdate_base($numdate, 'mois_annee');
}

function affdate_heure($numdate) {
	return _T('date_fmt_jour_heure', array('jour' => affdate($numdate), 'heure' => heures_minutes($numdate)));
}


//
// Alignements en HTML
//

function aligner($letexte,$justif) {
	$letexte = eregi_replace("<p([^>]*)", "<p\\1 align='$justif'", trim($letexte));
	if ($letexte AND !ereg("^[[:space:]]*<p", $letexte)) {
		$letexte = "<p class='spip' align='$justif'>" . $letexte . "</p>";
	}
	return $letexte;
}

//
// Export iCal
//

function filtrer_ical($texte) {
	include_lcm('inc_charsets');
	$texte = html2unicode($texte);
	$texte = unicode2charset(charset2unicode($texte, read_meta('charset'), 1), 'utf-8');
	$texte = ereg_replace("\n", " ", $texte);
	$texte = ereg_replace(",", "\,", $texte);

	return $texte;
}

function date_ical($date_heure, $minutes = 0) {
	return date("Ymd\THis", mktime(heures($date_heure),minutes($date_heure)+$minutes,0,mois($date_heure),jour($date_heure),annee($date_heure)));
}

function date_iso($date_heure) {
	list($annee, $mois, $jour) = recup_date($date_heure);
	list($heures, $minutes, $secondes) = recup_time($date_heure);
	$time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
	return gmdate("Y-m-d\TH:i:s\Z", $time);
}

//
// Reduire la taille d'un logo
// [(#LOGO_ARTICLE||reduire_image{100,60})]
//

function reduire_image($img, $taille = 120, $taille_y=0) {
	include_ecrire('inc_logos.php3');
	include_local('inc-cache.php3');

	if (!$taille_y)
		$taille_y = $taille;

	if (!$img) return;

	// recuperer le nom du fichier
	if (eregi("src=\'([^']+)\'", $img, $regs)) $logo = $regs[1];
	if (eregi("align=\'([^']+)\'", $img, $regs)) $align = $regs[1];
	if (eregi("name=\'([^']+)\'", $img, $regs)) $name = $regs[1];
	if (eregi("hspace=\'([^']+)\'", $img, $regs)) $espace = $regs[1];

	if (!$logo)
		$logo = $img; // [(#LOGO_ARTICLE|fichier|reduire_image{100})]

	$logo = 'IMG/'.ereg_replace('(../|IMG/)', '', $logo);

	if (@file_exists($logo) AND eregi("IMG/(.*)\.(jpg|gif|png)$", $logo, $regs)) {
		$nom = $regs[1];
		$format = $regs[2];
		$cache_folder= 'IMG/'.creer_repertoire('IMG', 'cache-'.$taille.'x'.$taille_y);
		$destination = $cache_folder.$nom.'-'.$taille.'x'.$taille_y;

		if ($preview = creer_vignette($logo, $taille, $taille_y, $format, $destination)) {
			$vignette = $preview['fichier'];
			$width = $preview['width'];
			$height = $preview['height'];
			return "<img src='$vignette' name='$name' border='0' align='$align' alt='' hspace='$espace' vspace='$espace' width='$width' height='$height' class='spip_logos' />";
		}
		else if ($taille_origine = @getimagesize("IMG/$logo")) {
			list ($destWidth,$destHeight) = image_ratio($taille_origine[0], $taille_origine[1], $taille, $taille_y);
			return "<img src='$logo' name='$name' width='$destWidth' height='$destHeight' border='0' align='$align' alt='' hspace='$espace' vspace='$espace' class='spip_logos' />";
		}
	}
}


//
// Recuperation de donnees dans le champ extra
// Ce filtre n'a de sens qu'avec la balise #EXTRA
//
function extra($letexte, $champ) {
	$champs = unserialize($letexte);
	return $champs[$champ];
}

// postautobr : transforme les sauts de ligne en _
function post_autobr($texte, $delim="\n_ ") {
	$texte = str_replace("\r\n", "\r", $texte);
	$texte = str_replace("\r", "\n", $texte);
	list($texte, $les_echap) = echappe_html($texte, "POSTAUTOBR", true);

	$debut = '';
	$suite = $texte;
	while ($t = strpos('-'.$suite, "\n", 1)) {
		$debut .= substr($suite, 0, $t-1);
		$suite = substr($suite, $t);
		$car = substr($suite, 0, 1);
		if (($car<>'-') AND ($car<>'_') AND ($car<>"\n") AND ($car<>"|"))
			$debut .= $delim;
		else
			$debut .= "\n";
		if (ereg("^\n+", $suite, $regs)) {
			$debut.=$regs[0];
			$suite = substr($suite, strlen($regs[0]));
		}
	}
	$texte = $debut.$suite;

	$texte = echappe_retour($texte, $les_echap, "POSTAUTOBR");
	return $texte;
}


//
// Gestion des blocs multilingues
//

// renvoie la traduction d'un bloc multi dans la langue demandee
function multi_trad ($lang, $trads) {
	// si la traduction existe, genial
	if (isset($trads[$lang])) {
		$retour = $trads[$lang];

	}	// cas des langues xx_yy
	else if (ereg('^([a-z]+)_', $lang, $regs) AND isset($trads[$regs[1]])) {
		$retour = $trads[$regs[1]];

	}	// sinon, renvoyer la premiere du tableau
		// remarque : on pourrait aussi appeler un service de traduction externe
		// ou permettre de choisir une langue "plus proche",
		// par exemple le francais pour l'espagnol, l'anglais pour l'allemand, etc.
	else {
		list (,$trad) = each($trads);
		$retour = $trad;
	}


	// dans l'espace prive, mettre un popup multi
	if ($GLOBALS['flag_ecrire']) {
		$retour = ajoute_popup_multi($lang, $trads, $retour);
	}

	return $retour;
}

// analyse un bloc multi
function extraire_trad ($langue_demandee, $bloc) {
	$lang = '';

	while (preg_match("/^(.*?)\[([a-z_]+)\]/si", $bloc, $regs)) {
		$texte = trim($regs[1]);
		if ($texte OR $lang)
			$trads[$lang] = $texte;
		$bloc = substr($bloc, strlen($regs[0]));
		$lang = $regs[2];
	}
	$trads[$lang] = $bloc;

	// faire la traduction avec ces donnees
	return multi_trad($langue_demandee, $trads);
}

// repere les blocs multi dans un texte et extrait le bon
function extraire_multi ($letexte) {
	global $flag_pcre;

	if (!strpos('-'.$letexte, '<multi>')) return $letexte; // perf
	if ($flag_pcre AND preg_match_all("@<multi>(.*?)</multi>@s", $letexte, $regs, PREG_SET_ORDER)) {
		while (list(,$reg) = each ($regs)) {
			$letexte = str_replace($reg[0], extraire_trad($GLOBALS['lcm_lang'], $reg[1]), $letexte);
		}
	}
	return $letexte;
}

// popup des blocs multi dans l'espace prive (a ameliorer)
function ajoute_popup_multi($langue_demandee, $trads, $texte) {
	static $num_multi=0;
	global $multi_popup;
	while (list($lang,$bloc) = each($trads)) {
		if ($lang != $langue_demandee)
			$survol .= "[$lang] ".supprimer_tags(couper($bloc,20))."\n";
		$texte_popup .= "<br /><b>".translate_language_name($lang)."</b> ".ereg_replace("\n+","<br />", supprimer_tags(couper(propre($bloc),200)));
	}

	if ($survol) {
		$num_multi ++;
		$texte .= " <img src=\"img_pack/langues-modif-12.gif\" alt=\"(multi)\" title=\"$survol\" height=\"12\" width=\"12\" border=\"0\" onclick=\"return openmulti($num_multi)\" />";
		$multi_popup .= "textes_multi[$num_multi] = '".addslashes($texte_popup)."';\n";
	}

	return $texte;
}

function quote_amp ($text) {
	// avoids cases "&amp; & me"
	$text = str_replace("&amp;", "&", $text);
	$text = str_replace("&", "&amp;", $text);
	return $text;
}

?>
