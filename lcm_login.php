<?php

// Test if LCM is installed
if (!@file_exists('inc/config/inc_connect.php')) {
	header('Location: install.php');
	exit;
}

include('inc/inc_version.php');
include_lcm('inc_presentation');
include_lcm('inc_login');

lcm_html_start(_T('login_title_login'), 'login');

global $lcm_lang_right;

// Site name: mandatory
$site_name = read_meta('site_name');
if (! $site_name)
	$site_name = _T('title_software');

// Site description: may be empty
$site_desc = read_meta('site_description');

echo "\n";
echo "<div align='center'>\n";
echo "<div align='center' id='login_screen'>\n\n";
echo "<h3>" . $site_name;

if ($site_desc)
	echo "<br /><span style='font-size: 80%; font-weight: normal;'>" . $site_desc .  "</span>";

echo "</h3>\n\n";

login('');

echo "\n\n";
echo "</div>\n";
echo "</div>\n\n";

echo "</body>\n</html>\n\n";

lcm_html_end();

?>
