<?php

include('inc/inc_version.php');
include_lcm('inc_presentation');
include_lcm('inc_login');

lcm_html_start(_T('login_acces_prive'));

global $spip_lang_right;
$lcm_site_name = lire_meta('nom_site');

echo "<p>&nbsp;</p>\n";
echo "<center><table width='400'><tr><td width='400'>\n";
echo "<div align='center'>\n";
echo "<h3 class='spip'>" . $lcm_site_name . "<br>\n";
echo "<small>" . _T('login_acces_prive') . "</small></h3>\n";
echo "<div align='" . $spip_lang_right . "'>" . menu_languages() . "</div>\n";
echo "</div>\n";

login('');

echo "</td></tr></table></center>\n";

lcm_html_end();

?>
