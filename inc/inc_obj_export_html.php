<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

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

	$Id: inc_obj_export_html.php,v 1.3 2006/04/06 21:27:54 mlutfy Exp $
*/

// Not needed for now, but maybe later?
// include_lcm('inc_obj_export_generic');

class LcmExportHtml /* extends LcmExportObject */ {

	function LcmExportHtml() {
		// $this->LcmExportObject();
	}

	function printStartDoc($title, $description, $helpref) {
		$title = trim($title);
		$description = trim($description);

		lcm_page_start($title, '', '', $helpref);

		if ($description)
			echo '<p class="normal_text">' . $description . "</p>\n";
	}

	function printHeaderValueStart() {
		echo "<table class='tbl_usr_dtl' width='98%' align='center' border='1'>";
		echo "<tr>\n";
	}

	function printHeaderValue($val) {
		echo '<th class="heading">' . $val . "</th>\n";
	}

	function printHeaderValueEnd() {
		$this->printEndLine();
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
				if ($val)
					$val = format_date($val, 'short');
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

		echo '<td ' . $align . ' ' . $css . '>' . $val . "</td>\n";
	}

	function printStartLine() {
		echo "<tr>\n";
	}

	function printEndLine() {
		echo "</tr>\n";
	}

	function printEndDoc() {
		// nothing
	}
}

?>
