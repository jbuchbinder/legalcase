<?php

if (defined('_INC_REPFIELDS_DEFAULT')) return;
define('_INC_REPFIELDS_DEFAULT', '1');

function get_default_repfields() {
	$fields = array(
		/* LCM_CASE */
		array(
			"table_name" => "lcm_case",
			"field_name" => "id_case",
			"description" => "id_case",
			"enum_type" => "",
			"filter" => "number"),
		array(
			"table_name" => "lcm_case",
			"field_name" => "title",
			"description" => "title",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_case",
			"field_name" => "date_creation",
			"description" => "time_input_date_creation",
			"enum_type" => "",
			"filter" => "date"),
		array(
			"table_name" => "lcm_case",
			"field_name" => "date_assignment",
			"description" => "case_input_date_assigned",
			"enum_type" => "",
			"filter" => "date"),
		array(
			"table_name" => "lcm_case",
			"field_name" => "legal_reason",
			"description" => "case_input_legal_reason",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_case",
			"field_name" => "alledged_crime",
			"description" => "case_input_alledged_crime",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_case",
			"field_name" => "count(*)",
			"description" => "count",
			"enum_type" => "",
			"filter" => "number"),
		/* LCM_AUTHOR (user) */
		array(
			"table_name" => "lcm_author",
			"field_name" => "id_author",
			"description" => "authoredit_input_id",
			"enum_type" => "",
			"filter" => "number"),
		array(
			"table_name" => "lcm_author",
			"field_name" => "id_office",
			"description" => "id_office",
			"enum_type" => "",
			"filter" => "number"),
		array(
			"table_name" => "lcm_author",
			"field_name" => "name_first",
			"description" => "person_input_name_first",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_author",
			"field_name" => "name_middle",
			"description" => "person_input_name_middle",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_author",
			"field_name" => "name_last",
			"description" => "person_input_name_last",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_author",
			"field_name" => "date_creation",
			"description" => "time_input_date_creation",
			"enum_type" => "",
			"filter" => "date"),
		array(
			"table_name" => "lcm_author",
			"field_name" => "status",
			"description" => "authoredit_input_status",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_author",
			"field_name" => "count(*)",
			"description" => "count",
			"enum_type" => "",
			"filter" => "number"),
		/* LCM_CLIENT */
		array(
			"table_name" => "lcm_client",
			"field_name" => "id_client",
			"description" => "client_input_id",
			"enum_type" => "",
			"filter" => "number"),
		array(
			"table_name" => "lcm_client",
			"field_name" => "name_first",
			"description" => "person_input_name_first",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_client",
			"field_name" => "name_middle",
			"description" => "person_input_name_middle",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_client",
			"field_name" => "name_last",
			"description" => "person_input_name_last",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_client",
			"field_name" => "date_creation",
			"description" => "time_input_date_creation",
			"enum_type" => "",
			"filter" => "date"),
		array(
			"table_name" => "lcm_client",
			"field_name" => "citizen_number",
			"description" => "person_input_citizen_number",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_client",
			"field_name" => "civil_status",
			"description" => "person_input_civil_status",
			"enum_type" => "keyword:system_kwg:civilstatus",
			"filter" => "text"),
		array(
			"table_name" => "lcm_client",
			"field_name" => "income",
			"description" => "person_input_income",
			"enum_type" => "keyword:system_kwg:income",
			"filter" => "number"),
		array(
			"table_name" => "lcm_client",
			"field_name" => "gender",
			"description" => "person_input_gender",
			"enum_type" => "list:female,male,unknown",
			"filter" => "text"),
		/* LCM_FOLLOWUP */
		array(
			"table_name" => "lcm_followup",
			"field_name" => "id_followup",
			"description" => "fu_input_id",
			"enum_type" => "",
			"filter" => "number"),
		array(
			"table_name" => "lcm_followup",
			"field_name" => "id_case",
			"description" => "case_input_id",
			"enum_type" => "",
			"filter" => "number"),
		array(
			"table_name" => "lcm_followup",
			"field_name" => "id_author",
			"description" => "author_input_id",
			"enum_type" => "",
			"filter" => "number"),
		array(
			"table_name" => "lcm_followup",
			"field_name" => "type",
			"description" => "fu_input_type",
			"enum_type" => "keyword:system_kwg:followups",
			"filter" => "number"),
		array(
			"table_name" => "lcm_followup",
			"field_name" => "description",
			"description" => "fu_input_description",
			"enum_type" => "",
			"filter" => "text"),
		array(
			"table_name" => "lcm_followup",
			"field_name" => "sumbilled",
			"description" => "fu_input_sum_billed",
			"enum_type" => "",
			"filter" => "number"),
		array(
			"table_name" => "lcm_followup",
			"field_name" => "date_start",
			"description" => "time_input_date_start",
			"enum_type" => "",
			"filter" => "date"),
		array(
			"table_name" => "lcm_followup",
			"field_name" => "date_end",
			"description" => "time_input_date_end",
			"enum_type" => "",
			"filter" => "date"),
		array(
			"table_name" => "lcm_followup",
			"field_name" => "IF(UNIX_TIMESTAMP(fu.date_end) > UNIX_TIMESTAMP(fu.date_start), UNIX_TIMESTAMP(fu.date_end)-UNIX_TIMESTAMP(fu.date_start), 0)",
			"description" => "time_input_length",
			"enum_type" => "",
			"filter" => "date"),
		array(
			"table_name" => "lcm_followup",
			"field_name" => "count(*)",
			"description" => "count",
			"enum_type" => "",
			"filter" => "number")
	);

	return $fields;
}

// Creates or updates lcm_fields (fields available in a report)
// Triggered at every upgrade from inc/inc_db_upgrade.php
function create_repfields($rep_fields) {
	foreach ($rep_fields as $f) {
		$q = "SELECT * 
				FROM lcm_fields 
				WHERE table_name = '" . $f['table_name'] . "'
				  AND field_name = '" . $f['field_name'] . "'";

		$result = lcm_query($q);

		if (($row = lcm_fetch_array($result))) {
			// check if update necessary
			$needs_update = false;

			foreach($f as $key => $val) {
				if ($row[$key] != $val)
					$needs_update = true;
			}

			if ($needs_update) {
				$all_fields_tmp = array();
				$all_fields = "";

				foreach ($f as $key => $val)
					$all_fields_tmp[] = "$key = '$val'";

				$all_fields = implode(", ", $all_fields_tmp);

				$q2 = "UPDATE lcm_fields
						SET " . $all_fields . "
						WHERE table_name = '" . $f['table_name'] . "'
						  AND field_name = '" . $f['field_name'] . "'";

				lcm_query($q2);
			}
		} else {
			// insert new field
			$all_fields_tmp = array();
			$all_fields = "";

			foreach ($f as $key => $val)
				$all_fields_tmp[] = "$key = '$val'";

			$all_fields = implode(", ", $all_fields_tmp);

			$q2 = "INSERT INTO lcm_fields
						SET " . $all_fields;

			lcm_query($q2);
		}
	}
}
