<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

  // ce script peut etre recopie a la racine pour obtenir le calendrier
  // a partir de l'espace public. 
  // Evidemment les messages internes a la redaction seront absents.
/*
if (!defined("_ECRIRE_INC_VERSION")) {
	include((@is_dir("inc") ? 'inc/' : '') . "inc_version.php");
}

if (!_DIR_RESTREINT)
	include ("inc/inc.php");
 else {
	include_lcm("inc_presentation");
	include_lcm("inc_calendar");
//	include_ecrire("inc_texte.php3");
//	include_ecrire("inc_layer.php3");
 }
*/

include('inc/inc.php');
include_lcm('inc_calendar');

lcm_page_start('Calendar');

$afficher_bandeau_calendrier = true;
echo http_calendrier_init('', $type);
//if (!_DIR_RESTREINT) fin_page();

lcm_page_end();

?>
