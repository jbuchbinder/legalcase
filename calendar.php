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

include('inc/inc.php');
include_lcm('inc_calendar');

lcm_page_start(_T('title_calendar_view'));

$afficher_bandeau_calendrier = true;
echo http_calendrier_init('', $_REQUEST['type']);

lcm_page_end();

?>
