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

	$Id: inc_obj_export_csv.php,v 1.1 2006/03/15 15:10:46 mlutfy Exp $
*/

// Not needed for now, but maybe later?
// include_lcm('inc_obj_export_generic');

class LcmExportCSV /* extends LcmExportObject */ {

	function LcmExportCSV() {
		// $this->LcmExportObject();
	}

	function printValue($val, $h, $css) {
		$align = '';

		// Maybe formalise 'time_length' filter, but check SQL pre-filter also
		if ($h['filter_special'] == 'time_length') {
			// $val = format_time_interval_prefs($val);
			$val = format_time_interval($val, true, '%.2f');
			if (! $val)
				$val = 0;
		} elseif ($h['description'] == 'time_input_length') {
			$val = format_time_interval($val, true, '%.2f');
			if (! $val)
				$val = 0;
		}

		switch ($h['filter']) {
			case 'date':
				// we leave the date in 0000-000-00 00:00:00 format
				break;
			case 'currency':
				if ($val)
					$val = format_money($val);
				else
					$val = 0;
				break;
			case 'number':
				$align = 'align="right"';
				if (! $val)
					$val = 0;
				break;
		}

		if (is_numeric($val)) {
			echo $val . ", ";
		} else {
			// escape " character (csv)
			$val = str_replace('"', '""', $val); 
			echo '"' . $val . '" , ';
		}
	}

	function printStartLine() {
		// nothing
	}

	function printEndLine() {
		echo "\n";
	}
}

?>
