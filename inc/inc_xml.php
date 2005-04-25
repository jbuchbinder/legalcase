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

	$Id: inc_xml.php,v 1.1 2005/04/25 15:58:18 antzi Exp $
*/

// Execute this file only once
if (defined('_INC_XML')) return;
define('_INC_XML', '1');

function xml_encode($name, $content = '', $indent = '') {
	// Check for entity name validity

	if (empty($content)) {
		return $indent . "<$name/>\n";
	} else {
		if (is_array($content)) {
			$ent_block = $indent . "<$name>\n";
			foreach($content as $k => $v) {
				$ent_block .= xml_encode($k,$v,$indent . "\t");
			}
			$ent_block .= $indent . "</$name>\n";
			return $ent_block;
		} else {
			return $indent . "<$name>" . htmlspecialchars($content, ENT_QUOTES) . "</$name>\n";
		}
	}
}


?>