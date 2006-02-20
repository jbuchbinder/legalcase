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

	$Id: inc_impex.php,v 1.8 2006/02/20 03:44:09 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_IMPEX')) return;
define('_INC_IMPEX', '1');

include('inc/inc_db.php');

function export($type, $format, $search = '') {
	switch ($type) {
		case 'case' :
			// List cases in the system + search criterion if any
			$q = "SELECT id_case,title,legal_reason,alledged_crime,notes,status,stage
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

//---------------------------------------------------------------------------------------
// Load/Put item functions
//---------------------------------------------------------------------------------------
// The following functions read from/write to database various items

// Load scope constants
define("_LOAD_ALL",65535);	// Temporary, allows 16 flags
define("_LOAD_CASE",1);		// Load case(s) data
define("_LOAD_FU",2);		// Load followup(s) data
define("_LOAD_CLIENT",4);	// Load client(s) data
define("_LOAD_ORG",8);		// Load organization(s) data
define("_LOAD_ATTACHMENT",16);	// Load attachment(s)
define("_LOAD_CONTACTS",32);	// Load contacts information

// Loads case from database; $id - case ID; $scope - what information to load
function load_case($id, &$case_data, $scope = 0) {
	// Load case data
	$result = lcm_query("SELECT * FROM lcm_case WHERE id_case=$id");
	$case_data['case']["ID$id"] = lcm_fetch_assoc($result);

	// Load the associated items - followups, clients, orgs, attachnments
	if ($scope & _LOAD_FU) {
		$result = lcm_query("SELECT * FROM lcm_followup WHERE id_case=$id");
		while ($row = lcm_fetch_assoc($result)) {
			load_followup($row['id_followup'], $case_data);
		}
	}
	if ($scope & _LOAD_CLIENT) {
		$result = lcm_query("SELECT * FROM lcm_case_client_org WHERE id_case=$id AND id_client>0");
		while ($row = lcm_fetch_assoc($result)) {
			$case_data['relation']['case-client-org']['ID' . join('-',$row)] = $row;
			load_client($row['id_client'], $case_data, $scope & (_LOAD_ATTACHMENT | _LOAD_CONTACTS));
		}
	}
	if ($scope & _LOAD_ORG) {
		$result = lcm_query("SELECT * FROM lcm_case_client_org WHERE id_case=$id AND id_org>0");
		while ($row = lcm_fetch_assoc($result)) {
			$case_data['relation']['case-client-org']['ID' . join('-',$row)] = $row;
			load_org($row['id_org'], $case_data, $scope & (_LOAD_ATTACHMENT | _LOAD_CONTACTS));
		}
	}
	if ($scope & _LOAD_ATTACHMENT) {
		$result = lcm_query("SELECT * FROM lcm_case_attachment WHERE id_case=$id");
		while ($row = lcm_fetch_assoc($result)) {
			$row['content'] = base64_encode($row['content']);
			$case_data['case']["ID$id"]['attachment']['ID' . $row['id_attachment']] = $row;
		}
	}
}

// Loads followup from database; $id - followup ID; $scope - what information to load
function load_followup($id, &$fu_data, $scope = 0) {
	// Load followup data
	$result = lcm_query("SELECT * FROM lcm_followup WHERE id_followup=$id");
	$fu_data['followup']["ID$id"] = lcm_fetch_assoc($result);

	// Load the associated items - cases
	if ($scope & _LOAD_CASE) {
		load_case($fu_data['followup']["ID$id"]['id_case'], $fu_data);
	}
}

// Loads keyword from database; $id - keyword ID
function load_kw($id, &$kw_data, $scope = 0) {
	// Load keyword data
	$result = lcm_query("SELECT * FROM lcm_keyword WHERE id_keyword=$id");
	$kw_data['keyword']["ID$id"] = lcm_fetch_assoc($result);

	// Load the associated keyword group
	if ($kw_data['keyword']["ID$id"]['id_group']>0 && $scope>0)
		load_kwg($kw_data['keyword']["ID$id"]['id_group'], $kw_data);
}

// Loads keyword group from database; $id - keyword ID; $scope - what information to load
function load_kwg($id, &$kwg_data, $scope = 0) {
	// Load keyword group data
	$result = lcm_query("SELECT * FROM lcm_keyword_group WHERE id_group=$id");
	$kwg_data['keyword_group']["ID$id"] = lcm_fetch_assoc($result);

	// Load the group member keyword(s)
	if ($scope>0) {
		$res_kw = lcm_query("SELECT * FROM lcm_keyword WHERE id_group=" . $kwg_data['keyword_group']["ID$id"]['id_group']);
		while ($row = lcm_fetch_assoc($res_kw)) {
			$kwg_data['keyword']["ID" . $row['id_keyword']] = $row;
		}
	}
}

// Loads client from database; $id - client ID; $scope - what information to load
function load_client($id, &$client_data, $scope = 0) {
	// Load client data
	$result = lcm_query("SELECT * FROM lcm_client WHERE id_client=$id");
	$client_data['client']["ID$id"] = lcm_fetch_assoc($result);

	// Load the associated items - cases, orgs, attachnments
	if ($scope & _LOAD_CASE) {
		$result = lcm_query("SELECT * FROM lcm_case_client_org WHERE id_client=$id");
		while ($row = lcm_fetch_assoc($result)) {
			$client_data['relation']['case-client-org']['ID' . join('-',$row)] = $row;
			load_case($row['id_case'], $client_data, $scope & (_LOAD_ATTACHMENT | _LOAD_CONTACTS));
		}
	}
	if ($scope & _LOAD_ORG) {
		$result = lcm_query("SELECT * FROM lcm_client_org WHERE id_client=$id AND id_org>0");
		while ($row = lcm_fetch_assoc($result)) {
			$client_data['relation']['client-org']['ID' . join('-',$row)] = $row;
			load_org($row['id_org'], $client_data, $scope & (_LOAD_ATTACHMENT | _LOAD_CONTACTS));
		}
	}
	if ($scope & _LOAD_ATTACHMENT) {
		$result = lcm_query("SELECT * FROM lcm_client_attachment WHERE id_client=$id");
		while ($row = lcm_fetch_assoc($result)) {
			$row['content'] = base64_encode($row['content']);
			$client_data['client']["ID$id"]['attachment']['ID' . $row['id_attachment']] = $row;
		}
	}

	if ($scope & _LOAD_CONTACTS) {
		$result = lcm_query("	SELECT * FROM lcm_contact WHERE type_person='client' AND id_of_person=$id");
		while ($row = lcm_fetch_assoc($result)) {
			$client_data['client']["ID$id"]['contact']['ID' . $row['id_contact']] = $row;
			load_kw($row['type_contact'], $client_data, _LOAD_ALL);
		}
	}
}

// Loads organization from database; $id - org ID; $scope - what information to load
function load_org($id, &$org_data, $scope = 0) {
	// Load organization data
	$result = lcm_query("SELECT * FROM lcm_org WHERE id_org=$id");
	$org_data['organization']["ID$id"] = lcm_fetch_assoc($result);

	// Load the associated items - cases, clients, attachnments
	if ($scope & _LOAD_CASE) {
		$result = lcm_query("SELECT * FROM lcm_case_client_org WHERE id_org=$id");
		while ($row = lcm_fetch_assoc($result)) {
			$org_data['relation']['case-client-org']['ID' . join('-',$row)] = $row;
			load_case($row['id_case'], $org_data, $scope & (_LOAD_ATTACHMENT | _LOAD_CONTACTS));
		}
	}
	if ($scope & _LOAD_CLIENT) {
		$result = lcm_query("SELECT * FROM lcm_client_org WHERE id_org=$id AND id_client>0");
		while ($row = lcm_fetch_assoc($result)) {
			$org_data['relation']['client-org']['ID' . join('-',$row)] = $row;
			load_client($row['id_client'], $org_data, $scope & (_LOAD_ATTACHMENT | _LOAD_CONTACTS));
		}
	}
	if ($scope & _LOAD_ATTACHMENT) {
		$result = lcm_query("SELECT * FROM lcm_org_attachment WHERE id_org=$id");
		while ($row = lcm_fetch_assoc($result)) {
			$row['content'] = base64_encode($row['content']);
			$org_data['organization']["ID$id"]['attachment']['ID' . $row['id_attachment']] = $row;
		}
	}

	if ($scope & _LOAD_CONTACTS) {
		$result = lcm_query("	SELECT * FROM lcm_contact WHERE type_person='org' AND id_of_person=$id");
		while ($row = lcm_fetch_assoc($result)) {
			$org_data['organization']["ID$id"]['contact']['ID' . $row['id_contact']] = $row;
			load_kw($row['type_contact'], $org_data, _LOAD_ALL);
		}
	}
}

?>
