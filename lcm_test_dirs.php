<?php

include('inc/inc_version.php');
include_lcm('inc_presentation');

use_language_of_visitor();

function bad_dirs($bad_dirs, $test_dir, $install) {
	install_html_start();

	if ($install) {
		$titre = _T('directories_setup_start');
	} else
		$titre = _T('directories_setup_problem');

	$bad_url = "lcm_test_dirs.php";
	if ($test_dir) 
		$bad_url .= '?test_dir=' . $test_dir;

	echo "<h3>". $titre ."</h3>\n";
	echo "<div align='right'>". menu_languages('var_lang_lcm') ."</div>\n";

	echo "<div class='box_error'>\n";
	echo "<p>". _T('directories_bad_rights') . _T('typo_column') ."</p>\n";
	echo "<ul>". $bad_dirs ."</ul>\n";
	echo "<p>". _T('directories_bad_rights_cause') ."</p>\n";
	echo "</div>\n";

	// if ($install)
	//	echo help ("install0");

	echo "<p>". _T('directories_continue') ."</p>\n";

	echo "<form action='$bad_urls' method='get'>\n";
	echo "<div align='right'><input type='submit' class='fondl' name='Valider' value='". _T('button_reload_page')."'></div>";
	echo "</form>";

	install_html_end();
}


function absent_dirs($bad_dirs, $test_dir) {
	install_html_start();

	$titre = _T('directories_setup_start');

	$bad_url = "lcm_test_dirs.php";
	if ($test_dir)
		$bad_url .= '?test_dir='. $test_dir;

	echo "<h3>". $titre ."</h3>\n";
	echo "<div align='right'>". menu_languages('var_lang_lcm') ."</div>\n";

	echo "<div class='box_error'>\n";
	echo "<p>". _T('directories_missing') . _T('typo_column') ."</p>\n";
	echo "<ul>". $bad_dirs ."</ul>\n";
	echo "<p>". _T('directories_missing_possible_cause') ."</p>\n";
	echo "</div>\n";

	// if ($install)
	//	echo aide ("install0");

	echo "<p>". _T('directories_continue') ."</p>\n";

	echo "<form action='$bad_urls' method='get'>\n";
	echo "<div align='right'><input type='submit' class='fondl' name='Valider' value='". _T('button_reload_page')."'></div>";
	echo "</form>";

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

$bad_dirs = array();
$absent_dirs = array();

foreach ($test_dirs as $my_dir) {
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
				array_push($bad_dirs, "<li>". $my_dir ."</li>\n");
		} else
			array_push($absent_dirs, "<li>". $my_dir ."</li>\n");
	}
}

if (!empty($bad_dirs)) {
	$bad_dirs = join(" ", $bad_dirs);
	bad_dirs($bad_dirs, $test_dir, $install);
} else if (!empty($absent_dirs)) {
	$absent_dirs = join(" ", $absent_dirs);
	absent_dirs($absent_dirs, $test_dir);
} else {
	if ($install)
		header("Location: ./install.php?step=1");
	else
		header("Location: ./index.php");
}

?>
