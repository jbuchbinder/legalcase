<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2005 Free Software Foundation, Inc.

	This program is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published by the
	Free Software Foundation; either version 2 of the License, or (at your
	option) any later version.

	This program is distributed in the hope that it will be useful, but 
	WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
	or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
	for more details.

	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
    59 Temple Place, Suite 330, Boston, MA  02111-1307, USA

	$Id: inc_conditions.php,v 1.3 2005/02/10 08:40:20 makaveev Exp $
*/

// Execute this file only once
if (defined('_INC_CONDITIONS')) return;
define('_INC_CONDITIONS', '1');

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

	$html = "<select name='$name' class='sel_frm'>\n";

	foreach($condition_types as $key => $val) {
		$html .= "<option " . (($key == $sel) ? 'selected ' : '') . "value=$key>$val</option>\n";
	}
	$html .= "</select>\n";

	return $html;
}

?>
