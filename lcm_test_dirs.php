<?php

include('inc/inc_version.php');
include_lcm('inc_presentation');

use_language_of_visitor();

function bad_dirs($bad_dirs, $test_dir, $install) {
	install_html_start();

	if ($install) {
		$titre = _T('dirs_preliminaire');
		$continue = _T('dirs_commencer');
	} else
		$titre = _T('dirs_probleme_droits');

	$bad_url = "spip_test_dirs.php3";
	if ($test_dir) $bad_url .= '?test_dir='.$test_dir;

	echo "<font face=\"Verdana,Arial,Helvetica,sans-serif\" SIZE='3'>$titre</font>\n<p>";
	echo "<div align='right'>". menu_languages('var_lang_lcm')."</div><p>";

	echo _T('dirs_repertoires_suivants', array('bad_dirs' => $bad_dirs));
	echo "<b>". _T('login_recharger')." $continue.";

	if ($install)
		echo aide ("install0");
	echo "<p>";

	echo "<form action='$bad_urls' method='get'>\n";
	echo "<div align='right'><input type='submit' class='fondl' name='Valider' value='". _T('login_recharger')."'></div>";
	echo "</form>";

	install_html_end();
}


function absent_dirs($bad_dirs, $test_dir) {
	install_html_start();

	$titre = _T('dirs_preliminaire');
	$continue = _T('dirs_commencer');

	$bad_url = "spip_test_dirs.php3";
	if ($test_dir) $bad_url .= '?test_dir='.$test_dir;

	echo "<FONT FACE=\"Verdana,Arial,Helvetica,sans-serif\" SIZE=3>$titre</FONT>\n<p>";
	echo "<div align='right'>". menu_languages('var_lang_lcm')."</div><p>";

	echo _T('dirs_repertoires_absents', array('bad_dirs' => $bad_dirs));
	echo "<B>". _T('login_recharger')." $continue.";

	if ($install)
		echo aide ("install0");
	echo "<p>";

	echo "<FORM ACTION='$bad_urls' METHOD='GET'>\n";
	echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondl' NAME='Valider' VALUE='". _T('login_recharger')."'></DIV>";
	echo "</FORM>";

	install_html_end();
}

// Try to write in a directory
function test_write($my_dir) {
	$ok = true;
	$file_name = "$my_dir/test.txt";
	$f = @fopen($file_name, "w");

	if (!$f) $ok = false;
	else if (!@fclose($f)) $ok = false;
	else if (!@unlink($file_name)) $ok = false;

	return $ok;
}

//
// Test rights on directories
//

$install = !@file_exists("inc/config/inc_connect.php");

if ($test_dir)
	$test_dirs[] = $test_dir;
else {
	$test_dirs = array("log", "inc/config", "inc/data");
}

unset($bad_dirs);
unset($absent_dirs);

while (list(, $my_dir) = each($test_dirs)) {
	if (!test_write($my_dir)) {
		@umask(0);
		if (@file_exists($my_dir)) {
			@chmod($my_dir, 0777);
			// ???
			if (!test_write($my_dir))
				@chmod($my_dir, 0775);
			if (!test_write($my_dir))
				@chmod($my_dir, 0755);
			if (!test_write($my_dir))
				$bad_dirs[] = "<li>".$my_dir;
		} else
			$absent_dirs[] = "<li>".$my_dir;
	}
}

if ($bad_dirs) {
	$bad_dirs = join(" ", $bad_dirs);
	bad_dirs($bad_dirs, $test_dir, $install);
}
else if ($absent_dirs) {
	$absent_dirs = join(" ", $absent_dirs);
	absent_dirs($absent_dirs, $test_dir);
}
else {
	if ($install)
		header("Location: ./install.php?step=1");
	else
		header("Location: ./index.php");
}

?>
