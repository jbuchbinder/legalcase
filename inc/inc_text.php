<?php

//
// Execute this file only once
if (defined("_INC_TEXTE")) return;
define("_INC_TEXTE", "1");

include_lcm('inc_filters');


//
// Trouver une locale qui marche
//
$lang2 = strtoupper($GLOBALS['lcm_lang']);
setlocale(LC_CTYPE, $GLOBALS['lcm_lang']) ||
setlocale(LC_CTYPE, $lang2.'_'.$GLOBALS['lcm_lang']) ||
setlocale(LC_CTYPE, $GLOBALS['lcm_lang'].'_'.$lang2);


//
// Diverses fonctions essentielles
//

// ereg_ ou preg_ ?
function ereg_remplace($cherche_tableau, $remplace_tableau, $texte) {
	global $flag_pcre;

	if ($flag_pcre) return preg_replace($cherche_tableau, $remplace_tableau, $texte);

	$n = count($cherche_tableau);

	for ($i = 0; $i < $n; $i++) {
		$texte = ereg_replace(substr($cherche_tableau[$i], 1, -1), $remplace_tableau[$i], $texte);
	}
	return $texte;
}

// points d'entree de pre- et post-traitement pour propre() et typo()
function spip_avant_propre ($letexte) {
	$letexte = extraire_multi($letexte);

	if (@function_exists('avant_propre'))
		return avant_propre ($letexte);
	return $letexte;
}

function spip_apres_propre ($letexte) {
	if (@function_exists('apres_propre'))
		return apres_propre ($letexte);

	return $letexte;
}

function spip_avant_typo ($letexte) {
	$letexte = extraire_multi($letexte);

	if (@function_exists('avant_typo'))
		return avant_typo ($letexte);

	return $letexte;
}

function spip_apres_typo ($letexte) {

	// caracteres speciaux
	$letexte = corriger_caracteres($letexte);
	$letexte = str_replace("'", "&#8217;", $letexte);

	// relecture des &nbsp;
	if ($GLOBALS['flag_ecrire'] AND $GLOBALS['revision_nbsp'])
		$letexte = ereg_replace('&nbsp;', '<span class="spip-nbsp">&nbsp;</span>', $letexte);

	if (@function_exists('apres_typo'))
		return apres_typo ($letexte);

	return $letexte;
}



// Mise de cote des echappements
function echappe_html($letexte, $source, $no_transform=false) {
	global $flag_pcre;
	$les_echap = array();

	if ($flag_pcre) {	// beaucoup plus rapide si on a pcre
		$regexp_echap_html = "<html>((.*?))<\/html>";
		$regexp_echap_code = "<code>((.*?))<\/code>";
		$regexp_echap_cadre = "[\n\r]*<(cadre|frame)>((.*?))<\/(cadre|frame)>[\n\r]*";
		$regexp_echap_poesie = "[\n\r]*<(poesie|poetry)>((.*?))<\/(poesie|poetry)>[\n\r]*";
		$regexp_echap = "/($regexp_echap_html)|($regexp_echap_code)|($regexp_echap_cadre)|($regexp_echap_poesie)/si";
	} else {
		//echo creer_echappe_sans_pcre("cadre");
		$regexp_echap_html = "<html>(([^<]|<[^/]|</[^h]|</h[^t]|</ht[^m]|</htm[^l]|<\/html[^>])*)<\/html>";
		$regexp_echap_code = "<code>(([^<]|<[^/]|</[^c]|</c[^o]|</co[^d]|</cod[^e]|<\/code[^>])*)<\/code>";
		$regexp_echap_cadre = "(<[cf][ar][da][rm]e>(([^<]|<[^/]|</[^cf]|</[cf][^ar]|</[cf][ar][^da]|</[cf][ar][da][^rm]|</[cf][ar][da][rm][^e]|<\/[cf][ar][da][rm]e[^>])*)<\/[cf][ar][da][rm]e>)()"; // parentheses finales pour obtenir meme nombre de regs que pcre
		$regexp_echap_poesie = "(<poe[st][ir][ey]>(([^<]|<[^/]|</[^p]|</p[^o]|</po[^e]|</poe[^st]|</poe[st][^ir]|</poe[st][ir][^[ey]]|<\/poe[st][ir][ey][^>])*)<\/poe[st][ir][ey]>)()";
		$regexp_echap = "($regexp_echap_html)|($regexp_echap_code)|($regexp_echap_cadre)|($regexp_echap_poesie)";
	}

	while (($flag_pcre && preg_match($regexp_echap, $letexte, $regs))
		|| (!$flag_pcre && eregi($regexp_echap, $letexte, $regs))) {
		$num_echap++;

		if ($no_transform) {	// echappements bruts
			$les_echap[$num_echap] = $regs[0];
		}
		else
		if ($regs[1]) {
			// Echapper les <html>...</ html>
			$les_echap[$num_echap] = $regs[2];
		}
		else
		if ($regs[4]) {
			// Echapper les <code>...</ code>
			$lecode = entites_html($regs[5]);

			// supprimer les sauts de ligne debut/fin (mais pas les espaces => ascii art).
			$lecode = ereg_replace("^\n+|\n+$", "", $lecode);

			// ne pas mettre le <div...> s'il n'y a qu'une ligne
			if (is_int(strpos($lecode,"\n")))
				$lecode = nl2br("<div align='left' class='spip_code' dir='ltr'>".$lecode."</div>");
			else
				$lecode = "<span class='spip_code' dir='ltr'>".$lecode."</span>";

			$lecode = ereg_replace("\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $lecode);
			$lecode = ereg_replace("  ", " &nbsp;", $lecode);
			$les_echap[$num_echap] = "<tt>".$lecode."</tt>";
		}
		else
		if ($regs[7]) {
			// Echapper les <cadre>...</cadre>
			$lecode = trim(entites_html($regs[9]));
			$total_lignes = count(explode("\n", $lecode));

			$les_echap[$num_echap] = "</p><form action=\"/\" method=\"get\"><textarea readonly='readonly' cols='40' rows='$total_lignes' class='spip_cadre' dir='ltr'>".$lecode."</textarea></form><p class=\"spip\">";
		}
		else
		if ($regs[12]) {
			$lecode = $regs[14];
			$lecode = ereg_replace("\n[[:space:]]*\n", "\n&nbsp;\n",$lecode);
			$lecode = ereg_replace("\r", "\n", $lecode);
			$lecode = "<div class=\"spip_poesie\"><div>".ereg_replace("\n+", "</div>\n<div>", $lecode)."</div></div>";
			$les_echap[$num_echap] = propre($lecode);
		} 

		$pos = strpos($letexte, $regs[0]);
		$letexte = substr($letexte,0,$pos)."@@SPIP_$source$num_echap@@"
			.substr($letexte,$pos+strlen($regs[0]));
	}

	// Gestion du TeX
	// [ML] likely to be removed
	if (!(strpos($letexte, "<math>") === false)) {
		include_lcm('inc_math');
		$letexte = traiter_math($letexte, $les_echap, $num_echap, $source);
	}

	//
	// Insertion d'images et de documents utilisateur
	//
	while (eregi("<(IMG|DOC|EMB)([0-9]+)(\|([^\>]*))?".">", $letexte, $match)) {
		include_ecrire("inc_documents.php3");
		$num_echap++;

		$letout = quotemeta($match[0]);
		$letout = ereg_replace("\|", "\|", $letout);
		$id_document = $match[2];
		$align = $match[4];
		if (eregi("emb", $match[1]))
			$rempl = embed_document($id_document, $align);
		else
			$rempl = integre_image($id_document, $align, $match[1]);
		$letexte = ereg_replace($letout, "@@SPIP_$source$num_echap@@", $letexte);
		$les_echap[$num_echap] = $rempl;
	}

	//
	// Echapper les tags html contenant des caracteres sensibles a la typo
	//
	$regexp_echap = "<[a-zA-Z!][^<>!':;\?]*[!':;\?][^<>]*>";
	if ($flag_pcre) {
		if (preg_match_all("/$regexp_echap/", $letexte, $regs, PREG_SET_ORDER))
			while (list(,$reg) = each($regs)) {
				$num_echap++;
				$les_echap[$num_echap] = $reg[0];
				//echo htmlspecialchars($reg[0])."<p>";
				$pos = strpos($letexte, $les_echap[$num_echap]);
				$letexte = substr($letexte,0,$pos)."@@SPIP_$source$num_echap@@"
					.substr($letexte,$pos+strlen($les_echap[$num_echap]));
			}
	} else {
		while (ereg($regexp_echap, $letexte, $reg)) {
			$num_echap++;
			$les_echap[$num_echap] = $reg[0];
			$pos = strpos($letexte, $les_echap[$num_echap]);
			$letexte = substr($letexte,0,$pos)."@@SPIP_$source$num_echap@@"
				.substr($letexte,$pos+strlen($les_echap[$num_echap]));
		}
	}

	return array($letexte, $les_echap);
}

// Traitement final des echappements
function echappe_retour($letexte, $les_echap, $source) {
	while (ereg("@@SPIP_$source([0-9]+)@@", $letexte, $match)) {
		$lenum = $match[1];
		$cherche = $match[0];
		$pos = strpos($letexte, $cherche);
		$letexte = substr($letexte, 0, $pos). $les_echap[$lenum] . substr($letexte, $pos + strlen($cherche));
	}
	return $letexte;
}

// Cut a text to a given number of characters, but cleans useless characters
// and avoids cutting a word in two.
function couper($texte, $taille=50) {
	$texte = substr($texte, 0, 400 + 2*$taille); /* eviter de travailler sur 10ko pour extraire 150 caracteres */

	// on utilise les \r pour passer entre les gouttes
	$texte = str_replace("\r\n", "\n", $texte);
	$texte = str_replace("\r", "\n", $texte);

	// sauts de ligne et paragraphes
	$texte = ereg_replace("\n\n+", "\r", $texte);
	$texte = ereg_replace("<(p|br)( [^>]*)?".">", "\r", $texte);

	// supprimer les traits, lignes etc
	$texte = ereg_replace("(^|\r|\n)(-[-#\*]*|_ )", "\r", $texte);

	// supprimer les tags
	$texte = supprimer_tags($texte);
	$texte = trim(str_replace("\n"," ", $texte));
	$texte .= "\n";	// marquer la fin

	// travailler en accents charset
	$texte = filtrer_entites($texte);

	// supprimer les liens
	$texte = ereg_replace("\[->([^]]*)\]","\\1", $texte); // liens sans texte
	$texte = ereg_replace("\[([^\[]*)->([^]]*)\]","\\1", $texte);

	// supprimer les notes
	$texte = ereg_replace("\[\[([^]]|\][^]])*\]\]", "", $texte);

	// supprimer les codes typos
	$texte = ereg_replace("[{}]", "", $texte);

	// supprimer les tableaux
	$texte = ereg_replace("(^|\r)\|.*\|\r", "\r", $texte);

	// couper au mot precedent
	$long = substr($texte, 0, max($taille-4,1));
	$court = ereg_replace("([^[:space:]][[:space:]]+)[^[:space:]]*\n?$", "\\1", $long);
	$points = '&nbsp;(...)';

	// trop court ? ne pas faire de (...)
	if (strlen($court) < max(0.75 * $taille,2)) {
		$points = '';
		$long = ereg_replace("&#?[a-z0-9]*;?$", "", substr($texte, 0, $taille));
		$texte = ereg_replace("([^[:space:]][[:space:]]+)[^[:space:]]*$", "\\1", $long);
		// encore trop court ? couper au caractere
		if (strlen($texte) < 0.75 * $taille)
			$texte = $long;
	} else
		$texte = $court;

	if (strpos($texte, "\n"))	// la fin est encore la : c'est qu'on n'a pas de texte de suite
		$points = '';

	// remettre les paragraphes
	$texte = ereg_replace("\r+", "\n\n", $texte);

	return trim($texte).$points;
}

// prendre <intro>...</intro> sinon couper a la longueur demandee
function couper_intro($texte, $long) {
	$texte = eregi_replace("(</?)intro>", "\\1intro>", $texte); // minuscules
	while ($fin = strpos($texte, "</intro>")) {
		$zone = substr($texte, 0, $fin);
		$texte = substr($texte, $fin + strlen("</intro>"));
		if ($deb = strpos($zone, "<intro>") OR substr($zone, 0, 7) == "<intro>")
			$zone = substr($zone, $deb + 7);
		$intro .= $zone;
	}

	if ($intro)
		$intro = $intro.'&nbsp;(...)';
	else
		$intro = couper($texte, $long);

	// supprimer un eventuel chapo redirecteur =http:/.....
	$intro = ereg_replace("^=[^[:space:]]+","",$intro);

	return $intro;
}


//
// Les elements de propre()
//

// Securite : empecher l'execution de code PHP
function interdire_scripts($source) {
	$source = eregi_replace("<(\%|\?|([[:space:]]*)script)", "&lt;\\1", $source);
	return $source;
}


// Correction typographique francaise
function typo_fr($letexte) {
	global $flag_strtr2;
	static $trans;

	// Nettoyer 160 = nbsp ; 187 = raquo ; 171 = laquo ; 176 = deg ; 147 = ldquo; 148 = rdquo
	if (!$trans) {
		$trans = array(
			"&nbsp;" => "~",
			"&raquo;" => "&#187;",
			"&laquo;" => "&#171;",
			"&rdquo;" => "&#148;",
			"&ldquo;" => "&#147;",
			"&deg;" => "&#176;"
		);
		$chars = array(160 => '~', 187 => '&#187;', 171 => '&#171;', 148 => '&#148;', 147 => '&#147;', 176 => '&#176;');
		$charset = read_meta('charset');
		include_lcm('inc_charsets');

		while (list($c, $r) = each($chars)) {
			$c = unicode2charset(charset2unicode(chr($c), 'iso-8859-1', 'forcer'));
			$trans[$c] = $r;
		}
	}

	if ($flag_strtr2)
		$letexte = strtr($letexte, $trans);
	else {
		reset($trans);
		while (list($c, $r) = each($trans))
			$letexte = str_replace($c, $r, $letexte);
	}

	$cherche1 = array(
		/* 1		'/{([^}]+)}/',  */
		/* 2 */ 	'/((^|[^\#0-9a-zA-Z\&])[\#0-9a-zA-Z]*)\;/',
		/* 3 */		'/&#187;| --?,|:([^0-9]|$)/',
		/* 4 */		'/([^<!?])([!?])/',
		/* 5 */		'/&#171;|(M(M?\.|mes?|r\.?)|[MnN]&#176;) /'
	);
	$remplace1 = array(
		/* 1		'<i class="spip">\1</i>', */
		/* 2 */		'\1~;',
		/* 3 */		'~\0',
		/* 4 */		'\1~\2',
		/* 5 */		'\0~'
	);
	$letexte = ereg_remplace($cherche1, $remplace1, $letexte);
	$letexte = ereg_replace(" *~+ *", "~", $letexte);

	$cherche2 = array(
		'/([^-\n]|^)--([^-]|$)/',
		'/(http|https|ftp|mailto)~:/',
		'/~/'
	);
	$remplace2 = array(
		'\1&mdash;\2',
		'\1:',
		'&nbsp;'
	);
	$letexte = ereg_remplace($cherche2, $remplace2, $letexte);

	return $letexte;
}

// rien sauf les "~" et "-,"
function typo_en($letexte) {

	$cherche1 = array(
		'/ --?,/'
	);
	$remplace1 = array(
		'~\0'
	);
	$letexte = ereg_remplace($cherche1, $remplace1, $letexte);

	$letexte = str_replace("&nbsp;", "~", $letexte);
	$letexte = ereg_replace(" *~+ *", "~", $letexte);

	$cherche2 = array(
		'/([^-\n]|^)--([^-]|$)/',
		'/~/'
	);
	$remplace2 = array(
		'\1&mdash;\2',
		'&nbsp;'
	);

	$letexte = ereg_remplace($cherche2, $remplace2, $letexte);

	return $letexte;
}

// General typography: French is the language is 'cpf', 'fr' or 'eo', 
// else English (minimalist).
function typo($letexte) {
	global $lcm_lang, $lang_typo;

	// escape <html>...</html> code, etc.
	list($letexte, $les_echap) = echappe_html($letexte, "SOURCETYPO");

	// Call the function for pre-processing
	$letexte = spip_avant_typo ($letexte);

	if (!$lang = $lang_typo) {
		include_lcm('inc_lang');
		$lang = lang_typo($lcm_lang);
	}

	if ($lang == 'fr')
		$letexte = typo_fr($letexte);
	else
		$letexte = typo_en($letexte);

	// Call the post-processing function
	$letexte = spip_apres_typo ($letexte);

	// reintegrate the escaped text
	$letexte = echappe_retour($letexte, $les_echap, "SOURCETYPO");

	return $letexte;
}


// Filtre a appliquer aux champs du type #TEXTE*
function propre($letexte) {
	return interdire_scripts(trim($letexte));
}

?>
