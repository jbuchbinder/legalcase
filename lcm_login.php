<?php

// Test if LCM is installed
if (!@file_exists('inc/config/inc_connect.php')) {
	header('Location: install.php');
	exit;
}

include('inc/inc_version.php');
include_lcm('inc_presentation');
include_lcm('inc_login');

lcm_html_start(_T('login_acces_prive'));

global $spip_lang_right;
$lcm_site_name = read_meta('site_name');

echo "<p>&nbsp;</p>\n";
echo "<center><table width='400'><tr><td width='400'>\n";
echo "<div align='center'>\n";
echo "<h3>" . $lcm_site_name . "</h3>\n";
echo "<div align='" . $spip_lang_right . "'>" . menu_languages() . "</div>\n";
echo "</div>\n";

login('');

echo "</td></tr></table></center>\n";

lcm_html_end();

?>
