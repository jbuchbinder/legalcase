<?php

//global $condition_types;
$GLOBALS['condition_types'] = array(1 => 'IS EQUAL TO',
				2 => 'IS LESS THAN',
				3 => 'IS GREATER THAN',
				4 => 'CONTAINS',
				5 => 'STARTS WITH',
				6 => 'ENDS WITH');

// Displays select condition form field
// $name - field name, $sel - selected option
function select_condition($name,$sel=0) {
	global $condition_types;

	$html = "<select name='$name'>\n";

	foreach($condition_types as $key => $val) {
		$html .= "<option " . (($key == $sel) ? 'selected ' : '') . "value=$key>$val</option>\n";
	}
	$html .= "</select>\n";

	return $html;
}

?>