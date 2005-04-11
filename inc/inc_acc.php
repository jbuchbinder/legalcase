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

	$Id: inc_acc.php,v 1.7 2005/04/11 13:00:32 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_ACC')) return;
define('_INC_ACC', '1');

function allowed($case, $access) {
	// By default, do not allow access
	$allow = false;

	// Check if the case number is present
	if ($case > 0) {

		$q = "SELECT ca.*, c.status
				FROM lcm_case_author as ca, lcm_case as c
				WHERE (ca.id_case = " . intval($case) . "
					AND ca.id_author= " . $GLOBALS['author_session']['id_author'] . ")
					AND ca.id_case = c.id_case";

		$result = lcm_query($q);

		if ($row = lcm_fetch_array($result)) {

			// Set initial value to true, if $access parameter is set
			$allow = (bool) $access;
			$open = true;

			if ($row['status'] == 'deleted' || $row['status'] == 'closed')
				$open = false;

			// Give admin all rights, but check for write/edit/admin if closed/deleted
			if ($GLOBALS['author_session']['status'] == 'admin')
				$row['ac_read'] = $row['ac_write'] = $row['ac_edit'] = $row['ac_admin'] = 1;

			// Walk each character in the required access rights list
			for($i = 0; $i < strlen($access); $i++) {
				switch ($access{$i}) {
					case "r":
						$allow &= ($row['ac_read']);
						break;
					case "w":
						$allow &= ($row['ac_write'] && $open);
						break;
					case "e":
						$allow &= ($row['ac_edit'] && $open);
						break;
					case "a":
						$allow &= ($row['ac_admin'] && $open);
						break;
					case "A":
						// bypass 'closed' or 'deleted' (ex: case status for admin)
						$allow &= ($row['ac_admin']);
						break;
					default:
						// At any unknown character, disallow access
						$allow = 0;
				}
			}
		}
	}

	return $allow;
}

// Returns an array with the possible case statuses
// c.f. http://www.lcm.ngo-bg.org/article78.html
function get_possible_case_statuses($status) {
	$statuses = array();

	if ($status == 'draft') {
		$statuses = array('draft' => 'draft', 
				'open' => 'opening',
				// 'suspended' => 'suspension',
				'closed' => 'conclusion',
				'merged' => 'merge', 
				'deleted' => 'deletion');
	} elseif ($status == 'open') {
		$statuses = array( // 'draft' => 'draft', 
				'open' => 'opening',
				'suspended' => 'suspension',
				'closed' => 'conclusion',
				'merged' => 'merge', 
				'deleted' => 'deletion');
	} elseif ($status == 'suspended') {
		$statuses = array( // 'draft' => 'draft', 
				'open' => 'opening',
				'suspended' => 'suspension',
				'closed' => 'conclusion',
				'merged' => 'merge', 
				'deleted' => 'deletion');
	} elseif ($status == 'closed') {
		$statuses = array( // 'draft' => 'draft', 
				'open' => 'opening',
				// 'suspended' => 'suspension',
				'closed' => 'conclusion',
				// 'merged' => 'merge', 
				'deleted' => 'deletion');
	} elseif ($status == 'merged') {
		$statuses = array( // 'draft' => 'draft', 
				// 'open' => 'opening',
				// 'suspended' => 'suspension',
				// 'closed' => 'conclusion',
				'merged' => 'merge', 
				'deleted' => 'deletion');
	} elseif ($status == 'deleted') {
		$statuses = array( // 'draft' => 'draft', 
				'open' => 'opening',
				// 'suspended' => 'suspension',
				// 'closed' => 'conclusion',
				// 'merged' => 'merge', 
				'deleted' => 'deletion');
	} else {
		lcm_panic("unknown status: $status");
	}

	return $statuses;
}

?>
