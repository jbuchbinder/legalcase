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

	$Id: inc_obj_generic.php,v 1.3 2006/04/20 11:17:08 antzi Exp $
*/

// Execute this file only once
if (defined('_INC_OBJ_GENERIC')) return;
define('_INC_OBJ_GENERIC', '1');

class LcmObject {
	var $data; 

	function LcmObject() {
		$this->data = array();
	}

	function getDataInt($field, $default = 0) {
		if (isset($this->data[$field]) && $this->data[$field] > 0)
			return $this->data[$field];

		if (is_string($default) && $default == '__ASSERT__')
			lcm_panic("Value does not exist.");

		return $default;
	}

	function getDataFloat($field, $default = 0.00) {
		if (isset($this->data[$field]) && $this->data[$field] > 0.00)
			return $this->data[$field];

		if (is_string($default) && $default == '__ASSERT__')
			lcm_panic("Value does not exist.");

		return $default;
	}

	function getDataString($field, $default = '') {
		if (isset($this->data[$field]))
			return $this->data[$field];

		if (is_string($default) && $default == '__ASSERT__')
			lcm_panic("Value does not exist.");

		return $default;
	}
}

?>
