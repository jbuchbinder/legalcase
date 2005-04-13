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

	$Id: inc_impex.php,v 1.1 2005/04/13 20:16:25 antzi Exp $
*/

// Execute this file only once
if (defined('_INC_IMPEX')) return;
define('_INC_IMPEX', '1');

function export($type, $format, $search = '') {
	switch ($type) {
		case 'case' :
			// List cases in the system + search criterion if any
			$q = "SELECT id_case,title,id_court_archive,legal_reason,alledged_crime,notes,status,stage
					FROM lcm_case";

			if (strlen($search)>1) {
				// Add search criteria
				$q .= " WHERE ((title LIKE '%$search%')
						OR (status LIKE '%$search%')
						OR (stage LIKE '%$search%'))";
			}

			break;

		case 'client' :
			// List clients in the system + search criterion if any
			$q = "SELECT id_client,name_first,name_middle,name_last,citizen_number,civil_status,income,gender,notes
					FROM lcm_client";

			if (strlen($search)>1) {
				// Add search criteria
				$q .= " WHERE ((name_first LIKE '%$search%')
						OR (name_middle LIKE '%$search%')
						OR (name_last LIKE '%$search%'))";
			}

			break;

		case 'org' :
			// List organizations in the system + search criterion if any
			$q = "SELECT id_org,name,notes,court_reg,tax_number,stat_number
					FROM lcm_org";

			if (strlen($search)>1) {
				// Add search criteria
				$q .= " WHERE (name LIKE '%$search%')";
			}

			break;

		default:
			lcm_panic("invalid type: $type");
			return 0;

	}

	$mime_types = array(	'csv' => 'text/comma-separated-values',
				'xml' => 'text/xml');
	if (!($mime_type = $mime_types[$format])) {
		lcm_panic("invalid type: $type");
		return 0;
	}

	$result = lcm_query($q);
	if (lcm_num_rows($result) > 0) {
		// Send proper headers to browser
		header("Content-Type: " . $mime_type);
		header("Content-Disposition: filename=$type.$format");
		header("Content-Description: " . "Export of {$type}s");
		header("Content-Transfer-Encoding: binary");
//		echo ( get_magic_quotes_runtime() ? stripslashes($row['content']) : $row['content'] );

		// Document start
		switch ($format) {
			case 'csv' :
				// Export columns headers
				break;
			case 'xml' :
				echo "<document>\r\n";
				break;
		}

		// Document contents
		while ($row = lcm_fetch_assoc($result)) {
			// Export row start
			switch ($format) {
				case 'csv' :
					break;
				case 'xml' :
					echo "\t<row>\r\n";
					break;
			}
			// Prepare row fields
			$fields = array();
			foreach($row as $key => $value) {
				// Remove escaping if any
				$value = ( get_magic_quotes_runtime() ? stripslashes($value) : $value );
				switch ($format) {
					case 'csv' :
						if (is_string($value)) {
							// Escape double quote in CVS style
							$value = str_replace('"', '""', $value);
							// Add double quotes
							$value = "\"$value\"";
						}
						
						break;
					case 'xml' :
						$value = (is_string($value) ? htmlspecialchars($value) : $value);
						$value = "\t\t<$key>$value</$key>\r\n";
						break;
				}
				$fields[] = $value;
			}
			// Export row end
			switch ($format) {
				case 'csv' :
					echo join(',',$fields) . "\r\n";
					break;
				case 'xml' :
					echo join('',$fields);
					echo "\t</row>\r\n";
					break;
			}
		}

		// Document end
		switch ($format) {
			case 'csv' :
				break;
			case 'xml' :
				echo "</document>\r\n";
				break;
		}
	}
}

?>