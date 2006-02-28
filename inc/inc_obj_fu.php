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

	$Id: inc_obj_fu.php,v 1.1 2006/02/28 17:11:53 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_FU')) return;
define('_INC_OBJ_FU', '1');

include_lcm('inc_db');

class LcmFollowup {
	// Note: Since PHP5 we should use "private", and generates a warning,
	// but we must support PHP >= 4.0.
	var $data; 

	function LcmFollowup($id_fu = 0) {
		$id_fu = intval($id_fu);
		$this->data = array();

		if (! ($id_fu > 0))
			return;

		$query = "SELECT * FROM lcm_followup WHERE id_followup = $id_fu";
		$result = lcm_query($query);

		if (($row = lcm_fetch_array($result))) 
			foreach ($row as $key => $val) 
				$this->data[$key] = $val;
	}

}

class LcmFollowupInfoUI extends LcmFollowup {
	function LcmFollowupInfoUI($id_fu = 0) {
		$this->LcmFollowup($id_fu);
	}

	function printGeneral($show_subtitle = true, $allow_edit = true) {
		if ($show_subtitle)
			show_page_subtitle(_T('generic_subtitle_general'), 'cases_intro');

	}

	// XXX error checking! ($_SESSION['errors'])
	function printEdit() {

	}
}

?>
