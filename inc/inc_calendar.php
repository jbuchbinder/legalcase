<?php

// Execute only once
if (defined("_INC_CALENDAR")) return;
define("_INC_CALENDAR", "1");

define(DEFAULT_D_SCALE,120); # 1 pixel = 2 minutes

// Write cookies

if ($GLOBALS['set_echelle'] > 0) {
	lcm_setcookie('spip_calendrier_echelle', floor($GLOBALS['set_echelle']), time() + 365 * 24 * 3600);
	$GLOBALS['echelle'] = floor($GLOBALS['set_echelle']);
} else 
	$GLOBALS['echelle'] = $GLOBALS['HTTP_COOKIE_VARS']['spip_calendrier_echelle'];

if ($GLOBALS['set_partie_cal']) {
	lcm_setcookie('spip_partie_cal', $GLOBALS['set_partie_cal'], time() + 365 * 24 * 3600);
	$GLOBALS['partie_cal'] = $GLOBALS['set_partie_cal'];
} else 
	$GLOBALS['partie_cal'] = $GLOBALS['HTTP_COOKIE_VARS']['spip_partie_cal'];


// General typography of the 3 types of calendars: day/month/year
global $bleu, $vert, $jaune; // blue, green, yellow
$style = "style='width: 14px; height: 7px; border: 0px'";
$bleu = http_img_pack("m_envoi_bleu$spip_lang_rtl.gif", 'B', $style);
$vert = http_img_pack("m_envoi$spip_lang_rtl.gif", 'V', $style);
$jaune= http_img_pack("m_envoi_jaune$spip_lang_rtl.gif", 'J', $style);

function http_calendrier_init($date='', $ltype='', $lechelle='', $lpartie_cal='', $script='')
{
	// month, year, day, type, scale, part-of-cal
	// global $mois, $annee, $jour;
	global $type, $echelle, $partie_cal;

	$mois = $_REQUEST['mois']; // month
	$annee = $_REQUEST['annee']; // year
	$jour = $_REQUEST['jour']; // day

	// default values
	if (!$type AND !($type = $ltype))
		$type = 'mois'; // month

	if (!isset($echelle))
		$echelle = $lechelle;

	if (!isset($lpartie_cal)) 
		$partie_cal = $lpartie_cal;

	if (!$mois) {
		$today=getdate($date ? strtotime($date) : time());
		$jour = $today["mday"];
		$mois = $today["mon"];
		$annee = $today["year"];
	} else {
		if (!isset($jour)) {
			$jour = 1; 
			$type= 'mois';
		}
	}

	$date = date("Y-m-d", mktime(0, 0, 0, $mois, $jour, $annee));

	if (!$script) 
		$script = $GLOBALS['REQUEST_URI']; 

	$script = http_calendrier_retire_args($script);

	$f = 'http_calendrier_init_' . $type;
	return $f($date, $echelle, $partie_cal, $script);
}


// Conversion en HTML d'un tableau de champ ics
// Le champ URL devient une balise a href=URL entourant les champs SUMMARY et DESC
// Le champ CATEGORIES indique les couleurs pour le style CSS
function http_calendrier_ics($evenements, $amj = "") 
{
	$class_mois = '
		padding: 2px;
		margin-top: 2px;
		font-size: 10px; ';
	$res = '';

	if ($evenements) {
		foreach ($evenements as $evenement) {
			$url = $evenement['URL'];
			$afficher_ev = true;
						
			$jour_debut = substr($evenement['DTSTART'], 0,8);
			$jour_fin = substr($evenement['DTEND'], 0, 8);
			
			if ($jour_debut > 0) {
				if (!($jour_fin > 0)) $jour_fin = $jour_debut;
				if ($jour_debut > $amj OR $jour_fin < $amj) $afficher_ev = false;
			}
			
			if ($jour_debut < $amj) $afficher_suite = true;

			if ($afficher_ev) {
				$radius_top = " -moz-border-radius-topleft: 6px; -moz-border-radius-topright: 6px;";
				$radius_bottom = " -moz-border-radius-bottomleft: 6px; -moz-border-radius-bottomright: 6px;";

				$deb_h = substr($evenement['DTSTART'],-6,2);
				$deb_m = substr($evenement['DTSTART'],-4,2);
				$fin_h = substr($evenement['DTEND'],-6,2);
				$fin_m = substr($evenement['DTEND'],-4,2);

				$desc = propre($evenement['DESCRIPTION']);
				$sum = $evenement['SUMMARY'];
				if ($sum[0] != '<')
				{
				  if ($sum)
				    $sum = "<span style='color: black'>" .
						ereg_replace(' +','&nbsp;', typo($sum)) .
						"</span>";
				  else {
				    if ($desc) $sum .= " <span style='font-size: 10px'>$desc</span>"; 
				  }
				}
				if ($deb_h >0 OR $deb_m > 0) {
					if ((($deb_h > 0) OR ($deb_m > 0)) AND $amj == $jour_debut)
						{ $deb = '<b>' . $deb_h . ':' . $deb_m . '</b> ';}
					else { 
						$deb = '...'; 
						$radius_top = "";
					}
		
					if ((($fin_h > 0) OR ($fin_m > 0)) AND $amj == $jour_fin)
						{ $fin = '<b>' . $fin_h . ':' . $fin_m . '</b> ';}
					else { 
						$fin = '...'; 
						$radius_bottom = "";
					}
	
					if ($amj == $jour_debut OR $amj == $jour_fin) {
						$date_affichee = "<div>$deb-$fin</div>";
						$opacity = "";
					}
					else {
						$date_affichee = "";
						$opacity = " -moz-opacity: 0.5; filter: alpha(opacity=50);";
						$desc = "";
					}
				} else {
					$date_affichee = "";
					$opacity = "";
				}

				$c = calendrier_div_style($evenement);
				if (!$c)
					$c = "font-size: 10px; color: black";
				else 
				{
					list($b,$c) = $c;
					$c = "$class_mois$radius_top$radius_bottom background-color: $b; color: $c; border: 1px solid $c;$opacity";
				}
				$res .=
					"\n<div style='$c'>" .
					$date_affichee .
				  (!$url ? "$sum $desc" : http_href($url, $sum, $desc)) .
					"\n</div>\n"; 
			}
		}
	}
	return $res;
}

// Calendar for today + help
function http_calendrier_aujourdhui_et_aide($now, $texte, $href)
{
  return 
    (($now) ? '' :
     ("<a href='$href' class='cellule-h'>" .
      "<table cellpadding='0'><tr>\n" .
      "<td><img src='img_pack/calendrier-24.gif' alt='' /></td>\n" .
      "<td>" . _T("info_aujourdhui") . "<br />".
      $texte .
      "</td></tr></table></a>\n"))
//    . aide("messcalen")
	;
}
  
// Shows a month view, with tables which allow the user to click to other months
function http_calendrier_tout($mois, $annee, $premier_jour, $dernier_jour)
{
	global $largeur_table, $largeur_gauche, $prefs;

	if ($prefs['screen'] == "wide") {
		$largeur_gauche = 130;
		$largeur_table  = 954;
	} else {
		$largeur_gauche = 100;
		$largeur_table = 730;
	}

/*	$fclic = ((read_meta("activer_messagerie") == "oui") AND 
		($GLOBALS["connect_activer_messagerie"] != "non"))?
		'http_calendrier_clics' : 
		'http_calendrier_sans_clics';*/
	$fclic = 'http_calendrier_clics';
	while (!(checkdate($mois,$dernier_jour,$annee))) $dernier_jour--;
	$today=getdate(time());
	$m=$today["mon"];
	$a=$today["year"];

	list($articles, $breves, $messages) = 
		sql_calendrier_interval_mois($annee,$mois, $premier_jour);
	if ($articles)
		foreach($articles as $d => $v) 
			{ $messages[$d] = array_merge($messages[$d], http_calendrier_image_et_typo($v));}
	if ($breves)
		foreach($breves as $d => $v) 
			{ $messages[$d] = array_merge($messages[$d], http_calendrier_image_et_typo($v));}

	$total =
//		"<div>&nbsp;</div>" .
		"<table cellpadding=0 cellspacing=0 border=0 width='98%'>" .
		"<tr><td width='100%' valign='top'>" .
		http_calendrier_mois($mois, $annee, $premier_jour, $dernier_jour, 
			$GLOBALS['echelle'], $messages, $fclic);
		"</td></tr></table>";

	// messages without date?
	if ($messages["0"]){ 
		$total .=  "\n<table width='200'><tr><td><font size='1'><b>".
		_T('info_mois_courant').
		"</b>" .
		http_calendrier_ics($messages["0"]) .
		"</font></td></tr></table>";
	}

//	if ($fclic == 'http_calendrier_clics')
//		$total .= http_calendrier_aide_mess();

	return $total;
}

// Shows a month view, with tables which allow the user to click to other months
function http_calendrier_init_mois($date, $echelle, $partie_cal, $script)
{
	global $largeur_table, $largeur_gauche, $prefs;

	if ($prefs['screen'] == "wide") {
		$largeur_gauche = 130;
		$largeur_table  = 954;
	} else {
		$largeur_gauche = 100;
		$largeur_table = 730;
	}

	$premier_jour = '01';
	$mois = mois($date);
	$annee = annee($date);

	$dernier_jour = 31;
	while (!(checkdate($mois,$dernier_jour,$annee))) $dernier_jour--;
	$today=getdate(time());
	$m=$today["mon"];
	$a=$today["year"];

	list($articles, $breves, $messages) = 
		sql_calendrier_interval_mois($annee,$mois, $premier_jour);
	if ($articles)
		foreach($articles as $d => $v) 
			{ $r = http_calendrier_image_et_typo($v);
			  $messages[$d] = !$messages[$d] ? $r : 
			     array_merge($messages[$d], $r); }
	if ($breves)
		foreach($breves as $d => $v) 
			{ $r = http_calendrier_image_et_typo($v);
			  $messages[$d] = !$messages[$d] ?  
			    $r : array_merge($messages[$d], $r); }

	$total =
//		"<div>&nbsp;</div>" .
//		"<table cellpadding='0' cellspacing='0' border='0' width='$largeur_table'>" .
//		"\n<tr><td width='$largeur_table' valign='top'>" .
		"\n<table cellpadding='0' cellspacing='0' border='0' width='98%'>\n" .
		"<tr>\n" .
		"<td width='100%' valign='top'>" .
		http_calendrier_mois($mois, $annee, $premier_jour, $dernier_jour, $partie_cal, $echelle, $messages, $script, 'http_calendrier_clics') .
		"</td>\n</tr>\n</table>\n";

	// messages without date?
	if ($messages["0"]){ 
		$total .=  "\n<table width='200'>\n<tr><td><font size='1'><b>".
		_T('info_mois_courant').
		"</b>" .
		http_calendrier_ics($messages["0"]) .
		"</font></td></tr>\n</table>";
	}

//	$total .= http_calendrier_aide_mess();

	return $total;
}

// calendar help for 'mess' => message center, but we call it 'agenda'
function http_calendrier_aide_mess()
{
  global $bleu, $vert, $jaune;
 return
   "\n<br /><br /><br /><table width='700'><tr><td><font size='2'>" .
    "<b>"._T('info_aide')."</b>" .
    "<br />$bleu "._T('info_symbole_bleu')."\n" .
    "<br />$vert "._T('info_symbole_vert')."\n" .
    "<br />$jaune "._T('info_symbole_jaune')."\n" .
    "</font></td></tr></table>";
}

function http_calendrier_retire_args($script)
{
  foreach(array('echelle','jour','mois','annee', 'type', 'set_echelle', 'set_partie_cal') as $arg) {
		$script = preg_replace("/([?&])$arg=[^&]*&/",'\1', $script);
		$script = preg_replace("/([?&])$arg=[^#]*#/",'\1#', $script);
		$script = preg_replace("/([?&])$arg=[^&#]*$/",'\1', $script);
	}
	return $script;
}

// Banner at the top of the screen, according to the $type (day/month/year):
// 2 icons go to other types, for the same day/month/year ($jour/$mois/$annee)
// 2 icons zoom in/out for same type of cal
// and in the center, the name ($nom) of the calendar
function http_calendrier_navigation($jour, $mois, $annee, $partie_cal, $echelle, $nom,
			    $script, $args_pred, $args_suiv, $type, $ancre)
{
  global $couleur_foncee;

  if (!isset($couleur_foncee)) $couleur_foncee = '#aaaaaa';
	if (!$echelle) $echelle = DEFAULT_D_SCALE;
	$script = http_calendrier_retire_args($script);
	if (!ereg('[?&]$', $script))
		$script .= (strpos($script,'?') ? '&' : '?');
	$args = "jour=$jour&mois=$mois&annee=$annee$ancre";
	  
  	$retour = "<div class='navigation-calendrier' style='background-color: $couleur_foncee'>";

   	if ($type != "mois") {
		if ($partie_cal == "tout") $img_att = " style='-moz-opacity: 0.3; filter: alpha(opacity=30)'";
		else $img_att = "";
		$retour .= "<span$img_att>"
		  .http_href_img(($script . "type=$type&set_partie_cal=tout&$args"),
				 "heures-tout.png", "width='13' height='13' style='behavior: url(win_png.htc)'",  _T('cal_day_entier')) . "</span>";

		if ($partie_cal == "matin") $img_att = " style='-moz-opacity: 0.3; filter: alpha(opacity=30)'";
		else $img_att = "";
		$retour .= "<span$img_att>"
		  .http_href_img(($script . "type=$type&set_partie_cal=matin&$args"),
				 "heures-am.png",
				 "width='13' height='13' style='behavior: url(win_png.htc)'",
				 _T('cal_matin'))
		  . "</span>";

		if ($partie_cal == "soir") $img_att = " style='-moz-opacity: 0.3; filter: alpha(opacity=30)'";
		else $img_att = "";
		$retour .= "<span$img_att>"
		  .http_href_img(($script . "type=$type&set_partie_cal=soir&$args"),
				 "heures-pm.png", 
				 "width='13' height='13' style='behavior: url(win_png.htc)'",
				 _T('cal_apresmidi'))
		  . "</span>";
		$retour .= "&nbsp;";
		$retour .= http_href_img(($script . "type=$type&set_echelle=" .
					  floor($echelle * 1.5) . "&$args"),
					 "loupe-moins.gif",
					 '',
					 _T('zoom'). '-');
		$retour .= http_href_img(($script . "type=$type&set_echelle=" .
					  floor($echelle / 1.5) . "&$args"), 
					 "loupe-plus.gif",
					 '', 
					 _T('zoom'). '+');
 		$retour .= "&nbsp;";


 	}

        $img_att = ($type == 'jour') ? " style='-moz-opacity: 0.3; filter: alpha(opacity=30)'" : '';
	$retour .= http_href_img(($script . "type=jour&echelle=$echelle&$args"),"cal-jour.gif", $img_att, _T('cal_day')) . "&nbsp;";
	$img_att = ($type == 'semaine') ?  " style='-moz-opacity: 0.3; filter: alpha(opacity=30)'" : "" ;
	$retour .= http_href_img($script . "type=semaine&echelle=$echelle&$args", "cal-semaine.gif", $img_att, _T('cal_week'))  . "&nbsp;";;
	$img_att = ($type == 'mois') ? " style='-moz-opacity: 0.3; filter: alpha(opacity=30)'" : "" ;
	$retour .= http_href_img($script . "type=mois&echelle=$echelle&$args","cal-mois.gif", $img_att, _T('cal_month'));
	
  	$retour .= "&nbsp;&nbsp;";

	$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

	$arguments = "jour=$jour_today&mois=$mois_today&annee=$annee_today$ancre" ;
	if ($type == 'mois') $condition = ($annee == $annee_today && $mois == $mois_today);
	else $condition = ($annee == $annee_today && $mois == $mois_today && $jour == $jour_today);
	
	$id = 'nav-agenda' .ereg_replace('[^A-Za-z0-9]', '', $ancre);
	$retour .= "<span onmouseover=\"montrer('$id');\">";
	$retour .= http_href_img($script . "type=$type&echelle=$echelle&$arguments",
				 "cal-today.gif",
				 $condition ? " style='-moz-opacity: 0.3; filter: alpha(opacity=30)'" : "",
				 _T("info_now"));
	$retour .= "</span>&nbsp;";

	if ($args_pred)
		$retour .= http_href($script . "type=$type&echelle=$echelle&$args_pred$ancre",
				     http_img_pack("fleche-left.png", '&lt;&lt;&lt;', "style='behavior: url(win_png.htc)'  width='12' height='12'"),
				     _T('previous'));
	if ($args_suiv)
		$retour .= http_href(($script . "type=$type&echelle=$echelle&$args_suiv$ancre"),
				     http_img_pack("fleche-right.png",  '&gt;&gt;&gt;', "style='behavior: url(win_png.htc)' width='12' height='12'"),
				     _T('next'));
  	$retour .= "&nbsp;&nbsp;";
 	$retour .= "<span style='font-weight: bold'>$nom</span>";
	return $retour . "</div>"
		. http_agenda_invisible($id, $annee, $jour, $mois, $script, $ancre);
}


// Builds a mini-agenda accessible via mouse-over
function http_agenda_invisible($id, $annee, $jour, $mois, $script, $ancre)
{
	global $couleur_claire;
	if (!isset($couleur_claire)) $couleur_claire = 'white';
	$gadget = "<div
id='$id' style='position: relative; visibility: hidden;z-index: 1000; '
onmouseover=\"montrer('$id');\" onmouseout=\"cacher('$id');\"><div 
style='position: absolute; padding: 5px; background-color: $couleur_claire; margin-bottom: 5px; -moz-border-radius-bottomleft: 8px; -moz-border-radius-bottomright: 8px;'>";

	$gadget .= "<table cellpadding='0' cellspacing='5' border='0' width='98%'>";
	$gadget .= "\n<tr><td colspan='3' style='text-align:left;'>";

	$annee_avant = $annee - 1;
	$annee_apres = $annee + 1;

	for ($i=$mois; $i < 13; $i++) {
		$gadget .= http_href($script . "mois=$i&annee=$annee_avant$ancre",
				     nom_mois("$annee_avant-$i-1"),'','', 'calendrier-annee') ;
			}
	for ($i=1; $i < $mois - 1; $i++) {
		$gadget .= http_href($script . "mois=$i&annee=$annee$ancre",
					nom_mois("$annee-$i-1"),'','', 'calendrier-annee');
			}
	$gadget .= "</td></tr>"
		. "\n<tr><td valign='top' width='33%'>"
		. http_calendrier_agenda($mois-1, $annee, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
		. "</td>\n<td valign='top' width='33%'>"
		. http_calendrier_agenda($mois, $annee, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
		. "</td>\n<td valign='top' width='33%'>"
		. http_calendrier_agenda($mois+1, $annee, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
		. "</td>"
		. "</tr>"
		. "\n<tr><td colspan='3' style='text-align:right;'>";
	for ($i=$mois+2; $i <= 12; $i++) {
				$gadget .= http_href($script. "mois=$i&annee=$annee$ancre",
					nom_mois("$annee-$i-1"),'','', 'calendrier-annee');
			}
	for ($i=1; $i < $mois+1; $i++) {
		$gadget .= http_href($script . "mois=$i&annee=$annee_apres$ancre",
					nom_mois("$annee_apres-$i-1"),'','', 'calendrier-annee');
			}
	return $gadget . "</td></tr></table></div></div>";
}

// Shows the banner of a calendar for a given day
function http_calendrier_navigation_jour($jour,$mois,$annee, $partie_cal, $echelle, $script, $nav)
{
  $today=getdate(time());
  $jour_today = $today["mday"];
  $mois_today = $today["mon"];
  $annee_today = $today["year"];
//  return "<table width='100%'>" .
   return
     http_calendrier_navigation($jour, $mois, $annee, $partie_cal, $echelle,
				(nom_jour("$annee-$mois-$jour") . " " .
				 affdate_jourcourt("$annee-$mois-$jour")),
				$script,
				"jour=".($jour-1)."&mois=$mois&annee=$annee",
				"jour=".($jour+1)."&mois=$mois&annee=$annee",
				'jour',
				$nav);
// "</table>";
}

// Shows the banner for a calendar of a given week
function http_calendrier_navigation_semaine($jour_today,$mois_today,$annee_today, $partie_cal, $echelle, $jour_semaine, $script, $nav)
{
   $debut = date("Y-m-d",mktime (1,1,1,$mois_today, $jour_today-$jour_semaine+1, $annee_today));
  $fin = date("Y-m-d",mktime (1,1,1,$mois_today, $jour_today-$jour_semaine+7, $annee_today));

  return     "\n<tr><td colspan='7'>" .
    http_calendrier_navigation($jour_today,
			       $mois_today,
			       $annee_today,
			       $partie_cal, 
			       $echelle,
		     ((annee($debut) != annee($fin)) ?
		      (affdate($debut)." -<br />".affdate($fin)) :
		      ((mois($debut) == mois($fin)) ?
		       (journum($debut)." - ".affdate_jourcourt($fin)) :
		       (affdate_jourcourt($debut)." - ".affdate_jourcourt($fin)))),
			    $script,
		     "mois=$mois_today&annee=$annee_today&jour=".($jour_today-7),
		     "mois=$mois_today&annee=$annee_today&jour=".($jour_today+7),
		     'semaine',
		     $nav) .
    "</td></tr>\n" ;
}

# affichage du bandeau d'un calendrier de plusieurs semaines
# si la periode est inferieure a 31 jours, on considere que c'est un mois
# et on place les boutons de navigations vers les autres mois et connexe;
# sinon on considere que c'est un planning ferme et il n'y a pas de navigation

function http_calendrier_mois($mois, $annee, $premier_jour, $dernier_jour, $partie_cal, $echelle, $evenements, $script, $fclic)
{
	global $couleur_claire, $couleur_foncee;

	$today = getdate(time());
	$j = $today["mday"];

	if ($dernier_jour > 31) {
		$prec = $suiv = '';
		$periode = affdate_mois_annee(date("Y-m-d", mktime(1,1,1,$mois,$premier_jour,$annee))) . ' - '. affdate_mois_annee(date("Y-m-d", mktime(1,1,1,$mois,$dernier_jour,$annee)));
	} else {
		$mois_suiv = $mois + 1;
		$annee_suiv = $annee;
		$mois_prec = $mois - 1;
		$annee_prec = $annee;

		if ($mois == 1) {
			$mois_prec = 12;
			$annee_prec = $annee - 1;
		} else if ($mois == 12) {
			$mois_suiv = 1;
			$annee_suiv = $annee + 1;
		}

		$prec = "mois=$mois_prec&annee=$annee_prec";
		$suiv = "mois=$mois_suiv&annee=$annee_suiv";
		$periode = affdate_mois_annee("$annee-$mois-1");
	}

	if (ereg('^(.*)(#[^=&]*)$',$script, $m)) {
		$script = $m[1];
		$ancre = $m[2];
	} else {
		$ancre = '';
	}

	$nav = http_calendrier_navigation($j,
					$mois,
					$annee,
					$partie_cal,
					$echelle,
					$periode,
					$script,
					$prec,
					$suiv,
					'mois',
					$ancre);
	
	return "<table border='0' cellspacing='0' cellpadding='0' width='98%'>\n"
		. "<tr>\n<td colspan='7'>$nav</td>\n</tr>\n"
		. http_calendrier_les_jours(array(_T('date_wday_1'),
			    _T('date_wday_2'),
			    _T('date_wday_3'),
			    _T('date_wday_4'),
			    _T('date_wday_5'),
			    _T('date_wday_6'),
			    _T('date_wday_7')),
		      $couleur_claire,
		      $couleur_foncee)
		. http_calendrier_suitede7($mois,$annee, $premier_jour, $dernier_jour,$evenements, $fclic, $script) .
    "\n</table>\n";
}

# affiche le bandeau des jours

function http_calendrier_les_jours($intitul, $claire, $foncee)
{
  $nb = count($intitul);
  if (!$nb) return '';
  $r = '';
  $bo = "style='width: " .
    round(100/$nb) .
    "%; padding: 3px; color: black; text-align: center; background-color: $claire; font-size: 10px;'";
  foreach($intitul as $j) $r .= "\n\t<td $bo><b>$j</b></td>";
  return  "\n<tr>$r\n</tr>";
}

// Shows the lines of a calendar with 7 columns (the days)
// Each box is filled with the events of the day from $evenements
// and the results of the application of the parameter $fclic on values of day/month/year and script
function http_calendrier_suitede7($mois_today,$annee_today, $premier_jour, $dernier_jour,$evenements,$fclic, $script)
{
	global $couleur_claire;
	
	$class_dispose = "border-bottom: 1px solid $couleur_claire; border-right: 1px solid $couleur_claire;";
  
	// show start of week out of period (previous month)
	$jour_semaine = date("w",mktime(1,1,1,$mois_today,$premier_jour,$annee_today));
	if ($jour_semaine == 0)
		$jour_semaine=7;

	$total = '';
	$ligne = '';

	for ($i = 1; $i < $jour_semaine; $i++) {
		$ligne .= "\n\t<td style=\"border-bottom: 1px solid $couleur_claire;\">&nbsp;</td>";
	}

	$ce_jour=date("Ymd");
	$premier = true;

	for ($j = $premier_jour; $j <= $dernier_jour; $j++) {
		$nom = mktime(1,1,1,$mois_today,$j,$annee_today);
		$jour = date("d",$nom);
		$jour_semaine = date("w",$nom);
		$mois_en_cours = date("m",$nom);
		$annee_en_cours = date("y",$nom);
		$amj = date("Y",$nom) . $mois_en_cours . $jour;

		if ($jour_semaine == 0) {
			$couleur_lien = "black";
			$couleur_fond = $couleur_claire;
		} else {
			$couleur_lien = "black";
			$couleur_fond = "#eeeeee";
		}
		
		if ($amj == $ce_jour) {
			$couleur_lien = "red";
			$couleur_fond = "white";
		}

		$jour_mois = 
			("<span style='font-size: 16px; color: black'>" .
			(($dernier_jour <= 31) ? 	$jour : "$jour/$mois_en_cours") .
			"</span>");

		if ($premier) {
			$border_left = " border-left: 1px solid $couleur_claire;";
			$premier = false;
		} else {
			$border_left = "";
		}

		$ligne .= "\n\t<td style='$class_dispose background-color: $couleur_fond;$border_left height: 100px; width: 14%; vertical-align: top'>" .
		 //  $fclic($annee_en_cours, $mois_en_cours, $jour, $jour_mois, $script) .
		 // [ML] for some reason, $annee_en_cours (current year) returns '05' not '2005' (in Spip too)
			$fclic($annee_today, $mois_en_cours, $jour, $jour_mois, $script) .

			(!$evenements[$amj] ? '' : http_calendrier_ics($evenements[$amj], $amj) ).
			"\n\t</td>";
		if ($jour_semaine==0) 
		{ 
			$total .= "\n<tr>$ligne\n</tr>";
			$ligne = '';
			$premier = true;
		}
	}
	return  $total . ($ligne ? "\n<tr>$ligne\n</tr>" : '');
}

# 3 fonctions pour servir de parametre a la precedente

function http_calendrier_sans_clics($annee, $mois, $jour, $clic, $script)
{
    return $clic;
}

function http_calendrier_clics_jour_semaine($annee, $mois, $jour, $clic, $script)
{
  if (ereg('^(.*)(#[^=&]*)$',$script,$m)) {
    $script = $m[1];
    $ancre = $m[2];
  } else $ancre = '';
  $script .= (strpos($script,'?') ? '&' : '?');
  $d = mktime(0,0,0,$mois, $jour, $annee);
  $mois = date("m", $d);
  $annee = date("Y", $d);
  $jour = date("d", $d);
  $commun = $script . "jour=$jour&mois=$mois&annee=$annee";
  ereg('^(.*>)[^<>]+(<.*)$',$clic,$m);
  $semaine = $m[1] . "S" . date("W", $d) . $m[2];
  return 
    "<table width='98%'>\n<tr><td align='left'>".
    http_href("$commun&type=jour" . $ancre, $clic) .
    "</td><td align='right'>" .
    http_href("$commun&type=semaine" . $ancre,$semaine) .
    "</td></tr>\n</table>";
}

// Prints a list of options for each day, where appointments can be
// added. In spip, there were various actions possible depending on
// who/from where was accessing the calendar.
function http_calendrier_clics($year, $month, $day, $clic, $script)
{
	//  global $bleu, $jaune, $vert;

	// [ML] XXX TODO should be an icon
	// return '<a href="edit_app.php">Create new appointment</a>'; // TRAD
	return http_href("$script?type=jour&jour=$day&mois=$month&annee=$year", $clic)
		. '<a href="edit_app.php?time=' . rawurlencode("$year-$month-$day") . '">New</a>'; // TRAD
}

// Shows events of a week
function http_calendrier_suite_heures($jour_today,$mois_today,$annee_today,
				      $articles, $breves, $evenements, $partie_cal, $echelle,
	$script, $nav)
{
  global $couleur_claire, $couleur_foncee, $prefs;

	if ($partie_cal == "soir") {
		$debut = 12;
		$fin = 23;
	} else if ($partie_cal == "matin") {
		$debut = 4;
		$fin = 15;
	} else {
		$debut = 7;
		$fin =20;
	}
	
	if ($prefs['screen'] == "wide") $largeur = 90;
	else $largeur = 60;

	$jour_semaine = date("w",mktime(1,1,1,$mois_today,$jour_today,$annee_today));
	if ($jour_semaine==0) $jour_semaine=7;
	$intitul = array();
	$liens = array();
	for ($j=0; $j<7;$j++){
		$nom = mktime(0,0,0,$mois_today,$jour_today-$jour_semaine+$j+1,$annee_today);
		$date = date("Y-m-d", $nom);
		$v = array('date' => date("Ymd", $nom),
			'nom' => nom_jour($date),
			'jour' => journum($date),
			'mois' => mois($date),
			'annee' => annee($date),
			'index' => date("w", $nom));
		$intitul[$j] = $v;
		$liens[$j] = 
		http_href(($script .
			(strpos($script,'?') ? '&' : '?') .
			"type=jour&jour=" .
			$v['jour'] .
			"&mois=" .
			$v['mois'] .
			"&annee=" .
			$v['annee'] .
			$nav),
			($v['nom'] .
				" " .
//				$v['jour'] .
//				(($v['jour'] ==1) ? 'er' : '') .
				_T('date_day_' . $v['jour']) .
				($nav  ? ('/' . (0+$v['mois'])) : '')),
				'',
				'color:black;');
	}

	list($dimheure, $dimjour, $fontsize, $padding) =
	calendrier_echelle($debut, $fin, $echelle);

	$today=getdate(time());
	$jour_t = $today["mday"];
	$mois_t = $today["mon"];
	$annee_t = $today["year"];
	$total = '';
	$style = (!_DIR_RESTREINT ? 'padding: 5px;' :
		  ("position: absolute; z-index: 2; top: 10px; left: "
		   . round($largeur/2) . 'px'));
	foreach($intitul as $k => $v) {
		$d = $v['date'];
		$arbrev = (!($articles[$d] OR $breves[$d]) ? '' :
			   http_calendrier_articles_et_breves($articles[$d], $breves[$d], $style));
		$total .= "\n<td style='width: 14%; height: 100px;  vertical-align: top'>
			<div style='background-color: " . 
			(($v['index'] == 0) ? $couleur_claire :
			(($v['jour'] == $jour_t AND 
			$v['mois'] == $mois_t AND
			$v['annee'] == $annee_t) ? "white;" :
			"#eeeeee;")) .
			"'>" .
			"\n<div style='position: relative; color: #999999; width: 100%; " .
			"border-left: 1px solid $couleur_claire; " .
			"border-bottom: 1px solid $couleur_claire; " .
			"height: ${dimjour}px; " .
			"font-size: ${fontsize}px;'>" .
			http_calendrier_jour_ics($debut,$fin,$largeur, 'calendrier_div_style', $echelle, $evenements[$d], $d) . 
						 (!_DIR_RESTREINT ? "</div></div>$arbrev" : "$arbrev</div></div>") .
  			"\n</td>";
	}
	return 
	"<table border='0' cellspacing='0' cellpadding='0' width='98%'>" .
	  http_calendrier_navigation_semaine($jour_today,$mois_today,$annee_today,  $partie_cal,
		$echelle,
		$jour_semaine,
		$script,
		$nav) .
	http_calendrier_les_jours($liens, $couleur_claire, $couleur_foncee) .
	"\n<tr>$total</tr>" .
	"</table>";
}


// Calculate and show monthly agenda
// Used by navigation column of inc_presentation
function http_calendrier_agenda ($month, $year, $day_ved, $month_ved, $annee_ved, $week = false,  $script='', $target='') {

	if (!$script) 
		$script =  $GLOBALS['PHP_SELF'] ;

	if (!strpos($script, '?')) 
		$script .= '?';

	if (!$month) {
		$month = 12; 
		$year--;
	} elseif ($month == 13) {
		$month = 1; 
		$year++;
	}

	return "<div style='text-align: center; padding: 5px;'>"
		. http_href($script . "mois=$month&annee=$year$target",
		       "<b style='font-size: 10px;'>" . affdate_mois_annee("$year-$month-1") . "</b>",
		       '',
		       'color: black;')
		. "<table width='98%' cellspacing='0' cellpadding='0'>"
		. http_calendrier_agenda_rv ($year, $month, 
				sql_calendrier_agenda($month, $year),
			        'http_jour_clic', array($script, $target),
			        $day_ved, $month_ved, $annee_ved, 
				$week)
		. "</table>"
		. "</div>";
}

function http_jour_clic($annee, $mois, $jour, $type, $couleur, $perso)
{

  list($script, $ancre) = $perso;

  return http_href($script . "type=$type&jour=$jour&mois=$mois&annee=$annee$ancre", 
		   "<b>$jour</b>",
		   '',
		   "color: $couleur");
}

// Afficher un mois sous forme de petit tableau
// Show monthly calendar in small table

function http_calendrier_agenda_rv ($annee, $mois, $les_rv, $fclic, $perso='',
				    $jour_ved='', $mois_ved='', $annee_ved='',
				    $semaine='') {
	global $couleur_foncee;

	// Former une date correcte (par exemple: $mois=13; $annee=2003)
	$date_test = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date_test);
	$annee = annee($date_test);

	if ($semaine) 
	{
		$jour_semaine_valide = date("w",mktime(1,1,1,$mois_ved,$jour_ved,$annee_ved));
		if ($jour_semaine_valide==0) $jour_semaine_valide=7;
		$debut = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+1,$annee_ved);
		$fin = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+7,$annee_ved);
	} else { $debut = $fin = '';}
	
	$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

	$total = '';
	$ligne = '';
	$jour_semaine = date("w", mktime(1,1,1,$mois,1,$annee));
	if ($jour_semaine==0) $jour_semaine=7;
	for ($i=1;$i<$jour_semaine;$i++) $ligne .= "\n\t<td></td>";
	$style1 = "-moz-border-radius-topleft: 10px; -moz-border-radius-bottomleft: 10px;";
	$style7 = "-moz-border-radius-topright: 10px; -moz-border-radius-bottomright: 10px;";
	for ($j=1; $j<32; $j++) {
		$nom = mktime(1,1,1,$mois,$j,$annee);
		$jour_semaine = date("w",$nom);
		if ($jour_semaine==0) $jour_semaine=7;

		if (checkdate($mois,$j,$annee)){
		  if ($j == $jour_ved AND $mois == $mois_ved AND $annee == $annee_ved) {
		    $ligne .= "\n\t<td style='font-size: 11px; margin: 1px; padding: 2px; background-color: white; border: 1px solid $couleur_foncee; text-align: center; -moz-border-radius: 5px;'>" .
		      $fclic($annee,$mois, $j,"jour","black", $perso) .
		      "</td>";
		  } else if ($semaine AND $nom >= $debut AND $nom <= $fin) {
		    $ligne .= "\n\t<td style='font-size: 11px; margin: 0px; padding: 3px; background-color: white; text-align: center; " .
		      (($jour_semaine==1) ? 
		       $style1 :
		       (($jour_semaine==7) ?
			$style7 : '')) .
		      "'>" .
		      $fclic($annee,$mois, $j,($semaine ? 'semaine' : 'jour'),"black", $perso) .
		      "</td>";
		  } else {
		    if ($j == $jour_today AND $mois == $mois_today AND $annee == $annee_today) {
			$couleur_fond = $couleur_foncee;
			$couleur = "white";
		    } else {
			if ($jour_semaine == 7) {
				$couleur_fond = "#aaaaaa";
				$couleur = 'white';
			} else {
				$couleur_fond = "#ffffff";
				$couleur = "#aaaaaa";
			}
			if ($les_rv[$j] > 0) {
				$couleur = "black";
			}
		    }
		    $ligne .= "\n\t<td><div style='font-size: 11px; margin-left: 1px; margin-top: 1px; padding: 2px; background-color: $couleur_fond; text-align: center; -moz-border-radius: 5px;'>" .
		      $fclic($annee,$mois, $j,($semaine ? 'semaine' : 'jour'),$couleur, $perso) .
		      "</div></td>";
		  }
		  if ($jour_semaine==7) 
		    {
		      $total .= "\n<tr>$ligne\n</tr>";
		      $ligne = '';
		    }
		}
	}
	return $total . (!$ligne ? '' : "\n<tr>$ligne\n</tr>");

}

function http_calendrier_image_et_typo($evenements)
{
  $res = array();
  if ($evenements)
    foreach($evenements as $k => $v)
      {
	if (!(is_int($v['CATEGORIES'])))
	  {
	    $v['DESCRIPTION'] = typo($v['DESCRIPTION']);
	    if ($v['CATEGORIES'] == 'a')
	      $i = 'puce-verte-breve.gif';
	    else
	      $i = 'puce-blanche-breve.gif';
	    $v['SUMMARY'] = http_img_pack($i, ".",  $style = "style='width: 8px; height: 9px; border: 0px'") . '&nbsp;' . ($v['SUMMARY'] ? $v['SUMMARY'] : $v['DESCRIPTION']);
	  }
	$res[$k] = $v;
      }
  return $res;
}

# liste les articles & les breves

function http_calendrier_articles_et_breves($articles, $breves, $style)
{
  if ($articles)
    {
      $res1 = "<div><b style='font-size: 10px'>"._T('info_articles')."</b></div>" .
	http_calendrier_ics(http_calendrier_image_et_typo($articles));
	}
  if ($breves)
    {
      $res2 = "<div><b style='font-size: 10px'>"._T('info_breves_02')."</b></div>" .
	http_calendrier_ics(http_calendrier_image_et_typo($breves));
    }
  return "<div style='$style'>$res1$res2</div>";
}

# Affiche une grille horaire 
# Selon l'echelle demandee, on affiche heure, 1/2 heure 1/4 heure, 5minutes.

function http_calendrier_heures($debut, $fin, $dimheure, $dimjour, $fontsize)
{
	$slice = floor($dimheure/(2*$fontsize));
	if ($slice%2) $slice --;
	if (!$slice) $slice = 1;

	$total = '';
	for ($i = $debut; $i < $fin; $i++) {
		for ($j=0; $j < $slice; $j++) 
		{
			if ($j == 0) $gras = " font-weight: bold;";
			else $gras = "";
			
			$total .= "\n<div style='position: absolute; left: 0px; top: ".
				http_cal_top ("$i:".sprintf("%02d",floor(($j*60)/$slice)), $debut, $fin, $dimheure, $dimjour, $fontsize) .
				"px; border-top: 1px solid #cccccc;$gras'>
				<div style='margin-left: 2px'>$i:" .
				sprintf("%02d",floor(($j*60)/$slice)) . 
				"</div>\n</div>";
		}
	}
	
	$total .= "\n<div style='position: absolute; top: ".
		http_cal_top ("$fin:00", $debut, $fin, $dimheure, $dimjour, $fontsize).
		"px; border-top: 1px solid #cccccc; font-weight: bold;'>
		<div style='margin-left: 2px'>$fin:00" .
		"</div>\n</div>";
	
	
	return "\n<div style='position: absolute; left: 2px; top: 2px;'><b>0:00</b></div>" .
		$total .
		"\n<div style='position: absolute; left: 2px; top: ".
		($dimjour - $fontsize - 2) .
		"px;'><b>23:59</b></div>";
}

# Calcule le "top" d'une heure

function http_cal_top ($heure, $debut, $fin, $dimheure, $dimjour, $fontsize) {
	
	$h_heure = substr($heure, 0, strpos($heure, ":"));
	$m_heure = substr($heure, strpos($heure,":") + 1, strlen($heure));
	$heure100 = $h_heure + ($m_heure/60);

	if ($heure100 < $debut) $heure100 = ($heure100 / $debut) + $debut - 1;
	if ($heure100 > $fin) $heure100 = (($heure100-$fin) / (24 - $fin)) + $fin;

	$top = floor(($heure100 - $debut + 1) * $dimheure);

	return $top;	
}

# Calcule la hauteur entre deux heures
function http_cal_height ($heure, $heurefin, $debut, $fin, $dimheure, $dimjour, $fontsize) {

	$height = http_cal_top ($heurefin, $debut, $fin, $dimheure, $dimjour, $fontsize) 
				- http_cal_top ($heure, $debut, $fin, $dimheure, $dimjour, $fontsize);

	$padding = floor(($dimheure / 3600) * 240);
	$height = $height - (2* $padding + 2); // pour padding interieur
	
	if ($height < ($dimheure/4)) $height = floor($dimheure/4); // eviter paves totalement ecrases
	
	return $height;	
}

    
// Visualise les $evenements de la journee $date
// commencant a $debut heure et finissant a $fin heure avec
// des couleurs definies par la fonction $detcolor appliquee sur l'evenement
// une $echelle (nombre de secondes representees par 1 pixel)
// une dimension $large

function http_calendrier_jour_ics($debut, $fin, $largeur, $detcolor, $echelle, $evenements, $date) {

	if ($echelle==0) $echelle = DEFAULT_D_SCALE;

	list($dimheure, $dimjour, $fontsize, $padding) = calendrier_echelle($debut, $fin, $echelle);
	$modif_decalage = round($largeur/8);

	$total = '';

	if ($evenements)
    {
		$tous = 1 + count($evenements);
		$i = 0;
		foreach($evenements as $evenement) {

/*			// Debug info
			echo "<!-- Event: ";
			var_dump($evenement);
			echo " -->\n";
*/
			$d = $evenement['DTSTART'];
			$e = $evenement['DTEND'];
			$d_jour = substr($d,0,8);
			$e_jour = substr($e,0,8);
			$debut_avant = false;
			$fin_apres = false;
			
			
			$radius_top = " -moz-border-radius-topleft: 6px; -moz-border-radius-topright: 6px;";
			$radius_bottom = " -moz-border-radius-bottomleft: 6px; -moz-border-radius-bottomright: 6px;";
			
			if ($d_jour <= $date AND $e_jour >= $date) {

				$i++;
	
				// Verifier si debut est jour precedent
				if (substr($d,0,8) < $date)
				{
					$heure_debut = 0; $minutes_debut = 0;
					$debut_avant = true;
					$radius_top = "";
				}
				else
				{
					$heure_debut = substr($d,-6,2);
					$minutes_debut = substr($d,-4,2);
				}
	
				if (!$e)
				{ 
					$heure_fin = $heure_debut ;
					$minutes_fin = $minutes_debut ;
					$haut = 0;
					$bordure = "border-bottom: dashed 2px";
				}
				else
				{
					$bordure = "border: 1px solid";
					if (substr($e,0,8) > $date) 
					{
						$heure_fin = 23; $minutes_fin = 59;
						$fin_apres = true;
						$radius_bottom = "";
					}
					else
					{
						$heure_fin = substr($e,-6,2);
						$minutes_fin = substr($e,-4,2);
					}
				}
				
				if ($debut_avant && $fin_apres)  $opacity = "-moz-opacity: 0.6; filter: alpha(opacity=60);";
				else $opacity = "";
	
	
				$haut = http_cal_top ("$heure_debut:$minutes_debut", $debut, $fin, $dimheure, $dimjour, $fontsize);
				$bas = http_cal_top ("$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour, $fontsize);
				$hauteur = http_cal_height ("$heure_debut:$minutes_debut", "$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour, $fontsize);
				if ($bas_prec > $haut) $decale += $modif_decalage;
				else $decale = (2 * $fontsize);
				if ($bas > $bas_prec) $bas_prec = $bas;
				$url = $evenement['URL']; 
				$desc = propre($evenement['DESCRIPTION']);
				$perso = $evenement['ATTENDEE'];
				$lieu = $evenement['LOCATION'];
				$sum = ereg_replace(' +','&nbsp;', typo($evenement['SUMMARY']));
				if (!$sum) { $sum = $desc; $desc = '';}
				if (!$sum) { $sum = $lieu; $lieu = '';}
				if (!$sum) { $sum = $perso; $perso = '';}
				if ($sum)
				$sum = "<span style='font-size: 10px;'><b>$sum</b>$lieu $perso</span>";
				if (($largeur > 90) && $desc)
				$sum .=  "<br /><span style='color: black'>$desc</span>";
				$colors = $detcolor($evenement);
				if ($colors)
				{
					list($bcolor,$fcolor) = $colors;
				}
				else 
				{ 
					$bcolor = 'white';
					$fcolor = 'black';
				}
				$total .= "\n<div style='cursor: auto; position: absolute; overflow: hidden;$radius_top$radius_bottom$opacity z-index: " .
					$i .
					"; left: " .
					$decale .
					"px; top: " .
					$haut .
					"px; height: " .
					$hauteur .
					"px; width: ".
					($largeur - 2 * ($padding+1)) .
					"px; font-size: ".
					floor($fontsize * 1.3) .
					"px; padding: " .
					$padding . 
					"px; background-color: " .
					$bcolor .
					";color: " .
					$fcolor .
					"; $bordure $fcolor;'
					onmouseover=\"this.style.zIndex=" . $tous . "\"
					onmouseout=\"this.style.zIndex=" . $i . "\">" .
					((!$url) ? 
						$sum :
					http_href($url, $sum, $desc,"color: $fcolor")) .
					"</div>";
			}
		}
    }
	return
		http_calendrier_heures($debut, $fin, $dimheure, $dimjour, $fontsize) .
			$total ;
}


function http_calendrier_journee($jour_today,$mois_today,$annee_today, $date){
	global $largeur_table, $largeur_gauche, $prefs;
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);
	if ($prefs['screen'] == "wide") {
//		$largeur_table = 974;
//		$largeur_gauche = 200;
//		$largeur_centre = $largeur_table - 2 * ($largeur_gauche + 20);
		$largeur_table = '98%';
		$largeur_gauche = '25%';
		$largeur_centre = '50%';
	} else {
//		$largeur_table = 750;
//		$largeur_gauche = 100;
//		$largeur_centre = $largeur_table - ($largeur_gauche + 20);
		$largeur_table = '98%';
		$largeur_gauche = '30%';
		$largeur_centre = '70%';
	}
		
	$retour = "<table cellpadding=0 cellspacing=0 border=0 width='$largeur_table'><tr>";
//	$retour = "<table cellpadding=0 cellspacing=0 border=0 width='98%'><tr>";
	
	if ($prefs['screen'] == "wide") {
		$retour .= "<td width='$largeur_gauche' class='verdana1' valign='top'>" .
//		$retour .= "<td width='25%' class='verdana1' valign='top'>" .
			"<div style='height: 29px;'>&nbsp;</div>".
			http_calendrier_jour($jour-1,$mois,$annee, "col") .
			"</td>\n<td width='20'>&nbsp;</td>\n";
	}
	$retour .= "\n<td width='$largeur_centre' valign='top'>"  .
//	$retour .= "\n<td width='50%' valign='top'>"  .
		"<div>" .
		http_calendrier_navigation_jour($jour,$mois,$annee, $GLOBALS['echelle'], 'calendar.php', '') .
		"</div>".
		http_calendrier_jour($jour,$mois,$annee, "wide") .
		'</td>';
		
		# afficher en reduction le tableau du jour suivant
	$retour .= "\n<td width='20'>&nbsp;</td>" .
			"\n<td width='$largeur_gauche' class='verdana1' valign='top'>" .
//			"\n<td width='25%' class='verdana1' valign='top'>" .
			"<div style='height: 29px;'>&nbsp;</div>".
			http_calendrier_jour($jour+1,$mois,$annee, "col") .
			'</td>';
			
	$retour .= '</tr></table>';
		
	return $retour;
}

function http_calendrier_init_jour($date, $echelle,  $partie_cal, $script){
	global $largeur_table, $largeur_gauche, $prefs;
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);
	$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

	if ($prefs['screen'] == "wide") {
//		$largeur_table = 974;
//		$largeur_gauche = 200;
//		$largeur_centre = $largeur_table - 2 * ($largeur_gauche + 20);
		$largeur_table = '98%';
		$largeur_gauche = '25%';
		$largeur_centre = '50%';
	} else {
//		$largeur_table = 750;
//		$largeur_gauche = 100;
//		$largeur_centre = $largeur_table - ($largeur_gauche + 20);
		$largeur_table = '98%';
		$largeur_gauche = '30%';
		$largeur_centre = '70%';
	}
		
	$retour =
//		"<div>&nbsp;</div>" .
		"<table cellpadding='0' cellspacing='0' border='0' width='$largeur_table'><tr>";
//	$retour = "<div>&nbsp;</div><table cellpadding='0' cellspacing='0' border='0' width='98%'><tr>";
	
	if ($prefs['screen'] == "wide") {
		$retour .= "<td width='$largeur_gauche' style='font-size: 10px' valign='top'>" .
//		$retour .= "<td width='25%' style='font-family: Arial, Sans, sans-serif; font-size: 10px' valign='top'>" .
			"<div style='height: 29px;'>&nbsp;</div>".
		  http_calendrier_jour($jour-1,$mois,$annee, "col", $partie_cal, $echelle, 0, $script) .
			"</td>\n<td width='20'>&nbsp;</td>\n";
	}
	$retour .= "\n<td width='$largeur_centre' valign='top'>"  .
//	$retour .= "\n<td width='50%' valign='top'>"  .
		"<div>" .
	  http_calendrier_navigation_jour($jour,$mois,$annee, $partie_cal, $echelle, $script, '') .
		"</div>".
	  http_calendrier_jour($jour,$mois,$annee, "wide", $partie_cal, $echelle, 0, $script) .
		'</td>';
		
		# afficher en reduction le tableau du jour suivant
	$retour .= "\n<td width='20'>&nbsp;</td>" .
			"\n<td width='$largeur_gauche' style='font-size: 10px' valign='top'>" .
//			"\n<td width='25%' style='font-family: Arial, Sans, sans-serif; font-size: 10px' valign='top'>" .
			"<div style='height: 29px;'>&nbsp;</div>".
	  http_calendrier_jour($jour+1,$mois,$annee, "col", $partie_cal, $echelle, 0, $script) .
			'</td>';
			
	$retour .= '</tr></table>';
		
	return $retour;
}

function http_calendrier_init_semaine($date, $echelle, $partie_cal, $script)
{
  global $prefs, $couleur_claire;
	
	if ($prefs['screen'] == "wide") {
		$largeur_table = 974;
		$largeur_gauche = 170;
	} else {
		$largeur_table = 750;
		$largeur_gauche = 100;
	}
//	$largeur_table = $largeur_table - ($largeur_gauche+20);
  
	$jour_today = journum($date);
	$mois_today = mois($date);
	$annee_today = annee($date);
	$jour_semaine = date("w",$date);
	$debut = date("Y-m-d",mktime(1,1,1,$mois_today, $jour_today-$jour_semaine+1, $annee_today));

	$today=getdate(time());
	$jour = $today["mday"];
	$mois=$today["mon"];
	$annee=$today["year"];
	$now = date("w",mktime(1,1,1,$mois,$jour,$annee));

	list($articles, $breves, $messages) = 
		sql_calendrier_interval_semaine($annee_today,$mois_today,$jour_today);
	return 
//		"<div>&nbsp;</div>" .
//		"<table cellpadding='0' cellspacing='0' border='0' width='$largeur_table'><tr>" .
//		"<td width='$largeur_table' valign='top'>" .
		"<table cellpadding='0' cellspacing='0' border='0' width='98%'><tr>" .
		"<td width='100%' valign='top'>" .
	  http_calendrier_suite_heures($jour_today,$mois_today,$annee_today, $articles, $breves, $messages, $partie_cal, $echelle, $script, '') .
		"</td></tr></table>" .
		(!(strlen($breves["0"]) > 0 OR $articles["0"] > 0) ? '' :
			("<table width=400 background=''><tr width=400><td><font size='1'>" .
			"<b>"._T('info_mois_courant')."</b>" .
			$breves["0"] .
			$articles["0"] .
			"</font></td></tr></table>")) .
//			http_calendrier_aide_mess() .
			'';
}

function http_calendrier_jour($jour,$mois,$annee,$large = "wide", $partie_cal, $echelle, $le_message = 0, $script =  'calendar.php') {
  global $spip_lang_rtl, $bleu, $vert,$jaune;
	global $calendrier_message_fermeture;
	

	if ($partie_cal == "soir") {
		$debut_cal = 12;
		$fin_cal = 23;
	} else if ($partie_cal == "matin") {
		$debut_cal = 4;
		$fin_cal = 15;
	} else {
		$debut_cal = 7;
		$fin_cal =20;
	}

	$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);
	$bgcolor = "white";
	
	if ($large == "etroit") {
		$bgcolor = "#eeeeee";
		
		$today=getdate(time());
		$jour_today = $today["mday"];
		$mois_today = $today["mon"];
		$annee_today = $today["year"];
		
		if ($jour == $jour_today AND $mois == $mois_today AND $annee == $annee_today) $bgcolor = "white";
	}
	
	$nom = mktime(1,1,1,$mois,$jour,$annee);
	$jour_semaine = date("w",$nom);
	if ($jour_semaine == 0) $bgcolor = "#e0e0e0";

	if ($large == "col" ) {
	  $entete = "<div align='center' style='padding: 5px;'><b style='font-size: 10px'>" .
	    http_href("$script?type=jour&jour=$jour&mois=$mois&annee=$annee",
				 affdate_jourcourt("$annee-$mois-$jour"),
				 '',
				 'color:black;') .
	    "</b></div>";
	}
	else {
	  if (($large == "wide") && !_DIR_RESTREINT)
			$entete = "<div align='center' style='padding: 5px;'>" .
			'<a href="edit_app.php?time=' . rawurlencode("$annee-$mois-$jour") . '">Create new appointment</a>' . /* TRAD */
			"</div>\n";
		else
			$entete = '';
	}

	list($articles, $breves, $messages) =
	  sql_calendrier_interval_jour($annee,$mois,$jour);
	  
	$j = sprintf("%04d%02d%02d", $annee,$mois,$jour);
	
	if ($large == "wide") {
		$largeur = 300;
	} else if ($large == "col") {
		$largeur = 90;
	} else {
		$largeur = 50;
	}

	list($dimheure, $dimjour, $fontsize, $padding) =
	  calendrier_echelle($debut_cal, $fin_cal, $echelle);
	// faute de fermeture en PHP...
	$calendrier_message_fermeture = $le_message;

	return $entete .
		"\n<div style='position: relative; color: #666666; " .
		"height: ${dimjour}px; " .
		"font-size: ${fontsize}px;".
		' border-left: 1px solid #aaaaaa; border-right: 1px solid #aaaaaa; border-bottom: 1px solid #aaaaaa; border-top: 1px solid #aaaaaa;' .
		"'>" .
	  ((!($articles[$j] OR $breves[$j])) ? '' :
	   http_calendrier_articles_et_breves($articles[$j], $breves[$j],
				      "position: absolute; z-index: 2; left: "
				      . ($largeur - $padding + 35) .
				      "px; top: 0px;")) .
	  http_calendrier_jour_ics($debut_cal,$fin_cal,$largeur, 'http_calendrier_message',
				   $echelle,
				   $messages[$j],
				   $j) .
	   "\n</div>";
}

function http_calendrier_semaine($jour_today,$mois_today,$annee_today)
{
	global $prefs, $couleur_claire;
	global $partie_cal;
	
	if ($partie_cal == "soir") {
		$debut_cal = 12;
		$fin_cal = 23;
	} else if ($partie_cal == "matin") {
		$debut_cal = 4;
		$fin_cal = 15;
	} else {
		$debut_cal = 7;
		$fin_cal =20;
	}
	
	
	if ($prefs['screen'] == "wide") {
		$largeur_table = 974;
		$largeur_gauche = 170;
	} else {
		$largeur_table = 750;
		$largeur_gauche = 100;
	}
//	$largeur_table = $largeur_table - ($largeur_gauche+20);
  
	$nom = mktime(1,1,1,$mois_today,$jour_today,$annee_today);
	$jour_semaine = date("w",$nom);
	$debut = mktime(1,1,1,$mois_today, $jour_today-$jour_semaine+1, $annee_today);
	$debut = date("Y-m-d",$debut);

	$today=getdate(time());
	$jour = $today["mday"];
	$mois=$today["mon"];
	$annee=$today["year"];
	$now = date("w",mktime(1,1,1,$mois,$jour,$annee));

	list($articles, $breves, $messages) = 
		sql_calendrier_interval_semaine($annee_today,$mois_today,$jour_today);
	return 
//		"<div>&nbsp;</div>" .
//		"<table cellpadding=0 cellspacing=0 border=0 width='$largeur_table'><tr>" .
//		"<td width='$largeur_table' valign='top'>" .
		"<table cellpadding=0 cellspacing=0 border=0 width='98%'><tr>" .
		"<td width='100%' valign='top'>" .
		http_calendrier_suite_heures($jour_today,$mois_today,$annee_today, $debut_cal,$fin_cal,
			$GLOBALS['echelle'],
			$articles, $breves, $messages,
			'calendar.php',
			'') .
		"</td></tr></table>" .
		(!(strlen($breves["0"]) > 0 OR $articles["0"] > 0) ? '' :
			("<table width=400 background=''><tr width=400><td><font size='1'>" .
			"<b>"._T('info_mois_courant')."</b>" .
			$breves["0"] .
			$articles["0"] .
			"</font></td></tr></table>")) .
//			http_calendrier_aide_mess() .
			'';
}

function http_calendrier_message($evenement)
{
  global $calendrier_message_fermeture;
  if (ereg("=$calendrier_message_fermeture$", $evenement['URL']))
    {return array('white', 'black');}
  else
    {
      return calendrier_div_style($evenement);
    }
}

# Affiche l'encadre "lien iCal"
# avec une simulation de
# icone_horizontale (_T("icone_suivi_activite"), "synchro.php3", "synchro-24.gif")
# qui fait des echo...
function http_calendrier_ical($id) {
  $lien = 'synchro.php3';
  $fond = 'synchro-24.gif';
  $fonction = "rien.gif";
  $texte =_T("icone_suivi_activite") ;
  return 
    debut_cadre_enfonce('',true) .
    "<div class='verdana1'>"._T("calendrier_synchro") .
    "<a href='$lien' class='cellule-h'><table cellpadding='0' valign='middle'><tr>\n" .
    "<td><a href='$lien'><div class='cell-i'><img style='background: url(\"img_pack/$fond\"); background-repeat: no-repeat; background-position: center center;' src='img_pack/$fonction' alt='' /></div></a></td>\n" .
    "<td class='cellule-h-lien'><a href='$lien' class='cellule-h'>$texte</a></td>\n" .
    "</tr></table></a>\n" ."</div>" .
    fin_cadre_enfonce(true);
}

function http_calendrier_rv($messages, $type) {
	global $spip_lang_rtl;

	$total = '';
	if (!$messages) return $total;
	foreach ($messages as $row) {
		if (ereg("^=([^[:space:]]+)$",$row['texte'],$match))
			$url = $match[1];
		else
			$url = "message.php3?id_message=".$row['id_message'];

		$rv = ($row['rv'] == 'oui');
		$date = $row['date_heure'];
		$date_fin = $row['date_fin'];

		if ($row['type']=="pb") $bouton = "pense-bete";
		else if ($row['type']=="annonces") $bouton = "annonce";
		else $bouton = "message";

		if ($rv) {
			$date_jour = affdate_jourcourt($date);
			$total .= "<tr><td colspan='2'>" .
				(($date_jour == $date_rv) ? '' :
				"<div class='arial11'><b>$date_jour</b></div>") .
				"</td></tr>";
		}

		$total .= "<tr><td width='24' valign='middle'>" .
		http_href($url,
				     ($rv ?
				      http_img_pack("rv.gif", '',
						    http_style_background($bouton . '.gif', "no-repeat;' border='0'")) : 
				      http_img_pack("$bouton.gif", '', "border='0'")),
				     '', '') .
		"</td>" .
		"<td valign='middle'>" .
		((!$rv) ? '' :
		((affdate($date) == affdate($date_fin)) ?
		 ("<div style='font-size: 9px;'" . 
		  http_style_background('fond-agenda.gif', 
					"right center no-repeat; float: left; line-height: 12px; color: #666666; margin-right: 3px; padding-right: 4px;")
		  . heures($date).":".minutes($date)."<br />"
		  . heures($date_fin).":".minutes($date_fin)."</div>") :
		( "<div style='font-size: 9px;'" . 
		  http_style_background('fond-agenda.gif', 
					"right center no-repeat; float: left; line-height: 12px; color: #666666; margin-right: 3px; padding-right: 4px; text-align: center;")
		  . heures($date).":".minutes($date)."<br />...</div>" ))) .
		"<div><b>" .
		http_href($url, typo($row['titre']), '', 
'font-size: 10px;') .
		"</b></div>" .
		"</td>" .
		"</tr>\n";

		$date_rv = $date_jour;
	}

	if ($type == 'annonces') {
		$titre = _T('info_annonces_generales');
		$couleur_titre = "ccaa00";
		$couleur_texte = "black";
		$couleur_fond = "#ffffee";
	}
	else if ($type == 'pb') {
		$titre = _T('infos_vos_pense_bete');
		$couleur_titre = "#3874B0";
		$couleur_fond = "#EDF3FE";
		$couleur_texte = "white";
	}
	else if ($type == 'rv') {
		$titre = _T('info_vos_rendez_vous');
		$couleur_titre = "#666666";
		$couleur_fond = "#eeeeee";
		$couleur_texte = "white";
	}

	return
	  debut_cadre_enfonce("", true, "", $titre) .
	  "<table width='98%' border='0' cellpadding='0' cellspacing='2'>" .
	  $total .
	  "</table>" .
	  fin_cadre_enfonce(true);
}

// Fabrique une balise A, avec un href conforme au validateur W3C
// et une classe uniquement destinee a retirer l'URL lors d'une impression
// attention au cas ou la href est du Javascript avec des "'"

function http_calendrier_href($href, $clic, $title='', $style='', $class='') {
	return '<a href="' .
		str_replace('&', '&amp;', $href) .
		'"' . # class="forum-repondre-message"' .
		(!$style ? '' : (" style=\"" . $style . "\"")) .
		(!$title ? '' : (" title=\"" . $title . "\"")) .
		(!$class ? '' : (" class=\"" . $class . "\"")) .
		'>' .
		$clic .
		'</a>';
}


function sql_calendrier_interval_jour($annee,$mois,$jour) {
	$avant = "'$annee-$mois-$jour'";
	$apres = "'$annee-$mois-$jour 23:59:59'";

	return array(sql_calendrier_interval_articles($avant, $apres),
		sql_calendrier_interval_breves($avant, $apres),
		sql_calendrier_interval_rv($avant, $apres));
}

function sql_calendrier_interval_semaine($annee,$mois,$jour) {
	$w_day = date("w", mktime(0,0,0,$mois, $jour, $annee));
	if ($w_day == 0) $w_day = 7; // Gaffe: le dimanche est zero
	$debut = $jour-$w_day;
	$avant = "'" . date("Y-m-d", mktime(1,1,1,$mois,$debut,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$debut+7,$annee)) .
	" 23:59:59'";

	return array(sql_calendrier_interval_articles($avant, $apres),
		sql_calendrier_interval_breves($avant, $apres),
		sql_calendrier_interval_rv($avant, $apres));
}

function sql_calendrier_interval_mois($annee,$mois,$jour) {
	$periode = $annee . '-' . sprintf("%02d", $mois) . '-01';
	$avant = "'$periode'";
	// $apres = "DATE_ADD('$periode', INTERVAL 1 MONTH)";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois+1,$debut,$annee)) .
	" 23:59:59'";
	return array(sql_calendrier_interval_articles($avant, $apres),
		sql_calendrier_interval_breves($avant, $apres),
		sql_calendrier_interval_rv($avant, $apres));
}

# 3 fonctions retournant les evenements d'une periode
# le tableau retourne est indexe par les balises du format ics
# afin qu'il soit facile de produire de tels documents.
# Pour les articles post-dates vus de l'espace public,
# on regarde si c'est une redirection pour avoir une url interessante
# sinon on prend " ", c'est-a-dire la page d'appel du calendrier

function sql_calendrier_interval_articles($avant, $apres) {
	$evenements= array();
/*	$result=spip_query("
SELECT	id_article, titre, date, descriptif, chapo
FFROM	spip_articles
WHERE	statut='publie'
 AND	date >= $avant
 AND	date < $apres
ORDER BY date
");
	if (!_DIR_RESTREINT)
	  $script = 'articles' . _EXTENSION_PHP . "?id_article=";
	else
	  {
	    $now = date("Ymd");
	    $script = 'article' . _EXTENSION_PHP . "?id_article=";
	  }
	while($row=spip_fetch_array($result)){
		$amj = sql_calendrier_jour_ical($row['date']);
		if ((!_DIR_RESTREINT) || ($now >= $amj))
			$url = $script . $row['id_article'];
		else {
			if (substr($row['chapo'], 0, 1) != '=')
				$url = " ";
			else {
				list(,$url) = extraire_lien(array('','','',
					substr($row['chapo'], 1)));
				if ($url)
					$url = texte_script(str_replace('&amp;', '&', $url));
				else $url = " ";
			}
		}

		$evenements[$amj][]=
		    array(
			'CATEGORIES' => 'a',
			'DESCRIPTION' => $row['descriptif'],
			'SUMMARY' => $row['titre'],
			'URL' =>  $url);
	} */
	return $evenements;
}

function sql_calendrier_interval_breves($avant, $apres) {
	$evenements= array();
/*	$result=spip_query("
SELECT	id_breve, titre, date_heure
FROM	spip_breves
WHERE	statut='publie'
 AND	date_heure >= $avant
 AND	date_heure < $apres
ORDER BY date_heure
");
	while($row=spip_fetch_array($result)){
		$amj = sql_calendrier_jour_ical($row['date_heure']);
		$script = (_DIR_RESTREINT ? 'breve' : 'breves_voir');
		$evenements[$amj][]=
		array(
			'URL' => $script . _EXTENSION_PHP . "?id_breve=" . $row['id_breve'],
			'CATEGORIES' => 'b',
			'DESCRIPTION' => $row['titre']);
	} */
	return $evenements;
}

function sql_calendrier_interval_rv($avant, $apres) {
/*
	// Debug info
	echo "<!-- sql_calendrier_interval_rv($avant, $apres)";
*/
	global $connect_id_auteur;
	$evenements= array();
	if (!$connect_id_auteur) return $evenements;

	$q = "SELECT lcm_app.*
				FROM lcm_app, lcm_author_app
				WHERE (lcm_author_app.id_author='" . $GLOBALS['author_session']['id_author'] . "'
				AND lcm_app.id_app=lcm_author_app.id_app
				AND ((end_time >= $avant OR start_time >= $avant) AND start_time <= $apres))
				GROUP BY lcm_app.id_app
				ORDER BY start_time";
//	echo "($q)";
	$result = lcm_query($q);

/*	while($row=spip_fetch_array($result)){
		$date_heure=$row["date_heure"];
		$date_fin=$row["date_fin"];
		$type=$row["type"];
		$id_message=$row['id_message'];
		if ($type=="pb")
		  $cat = 2;
		else {
		  if ($type=="affich")
		  $cat = 4;
		  else {
		    if ($type!="normal")
		      $cat = 12;
		    else {
		      $cat = 9;
		      $auteurs = array();
		      $result_aut=spip_query("
SELECT	auteurs.nom 
FROM	spip_auteurs AS auteurs,
	spip_auteurs_messages AS lien 
WHERE	(lien.id_message='$id_message' 
  AND	(auteurs.id_auteur!='$connect_id_auteur'
  AND	lien.id_auteur=auteurs.id_auteur))");
			while($row_auteur=spip_fetch_array($result_aut)){
				$auteurs[] = $row_auteur['nom'];
			}
		    }
		  }
		}

		$jour_avant = substr($avant, 9,2);
		$mois_avant = substr($avant, 6,2);
		$annee_avant = substr($avant, 1,4);
		$jour_apres = substr($apres, 9,2);
		$mois_apres = substr($apres, 6,2);
		$annee_apres = substr($apres, 1,4);
		$ical_apres = sql_calendrier_jour_ical("$annee_apres-$mois_apres-".sprintf("%02d",$jour_apres));

		// Calcul pour les semaines a cheval sur deux mois 
 		$j = 0;
		$amj = sql_calendrier_jour_ical("$annee_avant-$mois_avant-".sprintf("%02d", $j+($jour_avant)));

		while ($amj <= $ical_apres) {
			if (!($amj == sql_calendrier_jour_ical($date_fin) AND ereg("00:00:00", $date_fin)))  // Ne pas prendre la fin a minuit sur jour precedent
			$evenements[$amj][$id_message]=
			  array(
				'URL' => "message.php3?id_message=$id_message",
				'DTSTART' => date_ical($date_heure),
				'DTEND' => date_ical($date_fin),
				'DESCRIPTION' => $row['texte'],
				'SUMMARY' => $row['titre'],
				'CATEGORIES' => $cat,
				'ATTENDEE' => (count($auteurs) == 0) ? '' : join($auteurs,", "));
			$j ++; 
			$ladate = date("Y-m-d",mktime (1,1,1,$mois_avant, ($j + $jour_avant), $annee_avant));
			
			$amj = sql_calendrier_jour_ical($ladate);

		}

	} */
	while($row=lcm_fetch_array($result)){
//		var_dump($row);
		$date_heure=$row["start_time"];
		$date_fin=$row["end_time"];
		$type=$row["type"];
		$id_message=$row['id_app'];
		$cat = 9;
		$auteurs = array();
		$result_aut=lcm_query("SELECT	name_first,name_last
					FROM	lcm_author, lcm_author_app
					WHERE	(lcm_author_app.id_app=$id_message
					AND	lcm_author.id_author!=" . $GLOBALS['author_session']['id_author'] . "
					AND	lcm_author_app.id_author=lcm_author.id_author)");
		while($row_auteur=lcm_fetch_array($result_aut)){
			$auteurs[] = get_person_name($row_auteur);
		}

		$jour_avant = substr($avant, 9,2);
		$mois_avant = substr($avant, 6,2);
		$annee_avant = substr($avant, 1,4);
		$jour_apres = substr($apres, 9,2);
		$mois_apres = substr($apres, 6,2);
		$annee_apres = substr($apres, 1,4);
		$ical_apres = sql_calendrier_jour_ical("$annee_apres-$mois_apres-".sprintf("%02d",$jour_apres));

		// Calcul pour les semaines a cheval sur deux mois
		$j = 0;
		$amj = sql_calendrier_jour_ical("$annee_avant-$mois_avant-".sprintf("%02d", $j+($jour_avant)));
	
		while ($amj <= $ical_apres) {
			if (!($amj == sql_calendrier_jour_ical($date_fin) AND ereg("00:00:00", $date_fin)))  // Ne pas prendre la fin a minuit sur jour precedent
				$evenements[$amj][$id_message]=
				array(
					'URL' => "app_det.php?app=$id_message",
					'DTSTART' => date_ical($date_heure),
					'DTEND' => date_ical($date_fin),
					'DESCRIPTION' => $row['description'],
					'SUMMARY' => $row['title'],
					'CATEGORIES' => $cat,
					'ATTENDEE' => (count($auteurs) == 0) ? '' : join($auteurs,", "));
			$j ++;
			$ladate = date("Y-m-d",mktime (1,1,1,$mois_avant, ($j + $jour_avant), $annee_avant));
				
			$amj = sql_calendrier_jour_ical($ladate);
	
		}

	}
/*
  // Debug info
  echo " = ";
  var_dump($evenements);
  echo " -->\n";
*/

  return $evenements;
}


function sql_calendrier_taches_annonces () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;
	$result = spip_query("
SELECT * FROM spip_messages 
WHERE type = 'affich' AND rv != 'oui' AND statut = 'publie' ORDER BY date_heure DESC");
	if (spip_num_rows($result) > 0)
		while ($x = spip_fetch_array($result)) $r[] = $x;
	return $r;
}

function sql_calendrier_taches_pb () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;
	$result = spip_query("
SELECT * FROM spip_messages AS messages 
WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'");
	if (spip_num_rows($result) > 0){
	  $r = array();
	  while ($x = spip_fetch_array($result)) $r[] = $x;
	}
	return $r;
}

function sql_calendrier_taches_rv () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;
	$result = spip_query("
SELECT messages.* 
FROM spip_messages AS messages, spip_auteurs_messages AS lien 
WHERE ((lien.id_auteur='$connect_id_auteur' 
	AND lien.id_message=messages.id_message) 
	OR messages.type='affich') 
AND messages.rv='oui'
AND ( (messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) 
	AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH))
	OR (messages.date_heure < NOW() AND messages.date_fin > NOW() ))
AND messages.statut='publie' 
GROUP BY messages.id_message 
ORDER BY messages.date_heure");
	if (spip_num_rows($result) > 0){
	  $r = array();
	  while ($x = spip_fetch_array($result)) $r[] = $x;
	}
	return  $r;
}

function sql_calendrier_agenda ($mois, $annee) {
	global $connect_id_auteur;

	$rv = array();
	if (!$connect_id_auteur) return $rv;
	$date = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date);
	$annee = annee($date);

	// rendez-vous personnels dans le mois
/*	$result_messages=spip_query("SELECT messages.date_heure FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) AND messages.statut='publie'");
	while($row=spip_fetch_array($result_messages)){
		$rv[journum($row['date_heure'])] = 1;
	} */
	$result_messages=lcm_query("SELECT lcm_app.reminder
					FROM lcm_app, lcm_author_app
					WHERE lcm_author_app.id_author='" . $GLOBALS['author_session']['id_author'] . "'
					AND lcm_app.id_app=lcm_author_app.id_app
					AND reminder >= '$annee-$mois-1'
					AND reminder < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH)");
	while($row=lcm_fetch_array($result_messages)){
		$rv[journum($row['reminder'])] = 1;
	}
	return $rv;
}

function sql_calendrier_jour_ical($d) {
	return substr($d, 0, 4) . substr($d, 5, 2) . substr($d, 8, 2);
}

# prend une heure de debut et de fin, ainsi qu'une echelle (seconde/pixel)
# et retourne un tableau compose
# - taille d'une heure
# - taille d'une journee
# - taille de la fonte
# - taille de la marge

function calendrier_echelle($debut, $fin, $echelle)
{
  if ($echelle==0) $echelle = DEFAULT_D_SCALE;
  if ($fin <= $debut) $fin = $debut +1;

  $duree = $fin - $debut;
  $dimheure = floor((3600 / $echelle));
  return array($dimheure,
	       (($duree+2) * $dimheure),
	       floor (14 / (1+($echelle/240))),
	       floor(240 / $echelle));
}


global $contrastes;
$contrastes = array(
		/// Brown
		array("#8C6635","#F5EEE5","#1A64DF","#955708"),
		/// Fushia
		array("#CD006F","#FDE5F2","#E95503","#8F004D"),
		/// Blue
		array("#5da7c5","#EDF3FE","#814E1B","#435E79"),
		/// Light blue
		array("#766CF6","#EBE9FF","#869100","#5B55A0"),
		/// Orange
		array("#fa9a00","#ffeecc","#396B25","#472854"),
		/// Red (Vermillon)
		array("#FF0000","#FFEDED","#D302CE","#D40202"),
		/// Orange
		array("#E95503","#FFF2EB","#81A0C1","#FF5B00"),
		/// Yellow
		array("#ccaa00", "#ffffee", "#65659C","#6A6A43"),
		/// Light green
		array("#009F3C","#E2FDEC","#EE0094","#02722C"),
		/// Green
		array("#9DBA00", "#e5fd63","#304C38","#854270"),
		/// Red (Bordeaux)
		array("#640707","#FFE0E0","#346868","#684747"),
		/// Grey
		array("#3F3F3F","#F2F2F2","#854270","#666666"),
		// Black
		array("black","#aaaaaa",  "#000000", "#ffffff"),
		/// Boza
		array("#666500","#FFFFE0","#65659C","#6A6A43")
		);

# Choisit dans le tableau ci-dessus les couleurs d'un evenement
# si l'indice fourni par CATEGORIES est negatif, inversion des plans

function calendrier_div_style($evenement)
{
  global $contrastes;

  $categ = $evenement['CATEGORIES'];

  if (!is_int($categ))
    return "";
  else 
    { 
      if ($categ >= 0) {$f=0;$b=1;$i=$categ;}else{$f=1;$b=0;$i=0-$categ;}
      $i %= count($contrastes);
      return array($contrastes[$i][$b], $contrastes[$i][$f]);
    }
}

/////////////////////////////////////////////////////////////////
//
// LCM additions
//
/////////////////////////////////////////////////////////////////

//
// Convert LCM appointments and show them via http_calendrier_ics() function
function lcm_http_calendrier_ics($appointments, $amj = "") {
	$events = array();
	foreach($appointments as $app) {
		$events[] = array(	'SUMMARY' 	=> $app['title'],
					'DESCRIPTION'	=> $app['description'],
					'DTSTART'	=> date('YmdHis',strtotime($app['start_time'])),
					'DTEND'		=> date('YmdHis',strtotime($app['end_time'])),
					'URL'		=> 'app_det.php?app=' . $app['id_app']
				);
	}
	//var_dump($events);
	return http_calendrier_ics($events, $amj);
}

?>
