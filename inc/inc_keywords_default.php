<?php

if (defined('_INC_KEYWORDS_DEFAULT')) return;
define('_INC_KEYWORDS_DEFAULT', '1');

include_lcm('inc_keywords');

global $system_keyword_groups;

$system_keyword_groups = array (
	"followups" => array(
		"name" => "followups",
		"title" => "kwg_followups_title",
		"description" => "kwg_followups_description",
		"type" => "system",
		"policy" => "mandatory",
		"quantity" => "one",
		"suggest" => "consultation",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array(
			array (
				"name" => "opening",
				"title" => "kw_followups_opening_title",
				"description" => "kw_followups_opening_description",
				"ac_author" => "Y"),
			array (
				"name" => "assignment",
				"title" => "kw_followups_assignment_title",
				"description" => "kw_followups_assignment_description",
				"ac_author" => "Y"),
			array (
				"name" => "suspension",
				"title" => "kw_followups_suspension_title",
				"description" => "kw_followups_suspension_description",
				"ac_author" => "Y"),
			array (
				"name" => "resumption",
				"title" => "kw_followups_resumption_title",
				"description" => "kw_followups_resumption_description",
				"ac_author" => "Y"),
			array (
				"name" => "delay",
				"title" => "kw_followups_delay_title",
				"description" => "kw_followups_delay_description",
				"ac_author" => "Y"),
			array (
				"name" => "conclusion",
				"title" => "kw_followups_conclusion_title",
				"description" => "kw_followups_conclusion_description",
				"ac_author" => "Y"),
			array (
				"name" => "reopening",
				"title" => "kw_followups_reopening_title",
				"description" => "kw_followups_reopening_description",
				"ac_author" => "Y"),
			array (
				"name" => "merge",
				"title" => "kw_followups_merge_title",
				"description" => "kw_followups_merge_description",
				"ac_author" => "Y"),
			array (
				"name" => "consultation",
				"title" => "kw_followups_consultation_title",
				"description" => "kw_followups_consultation_description",
				"ac_author" => "Y"),
			array (
				"name" => "correspondance",
				"title" => "kw_followups_correspondance_title",
				"description" => "kw_followups_correspondance_description",
				"ac_author" => "Y"),
			array (
				"name" => "travel",
				"title" => "kw_followups_travel_title",
				"description" => "kw_followups_travel_description",
				"ac_author" => "Y"),
			array (
				"name" => "other",
				"title" => "kw_followups_other_title",
				"description" => "kw_followups_other_description",
				"ac_author" => "Y")
		)
	),
	
	"contacts" => array(
		"name" => "contacts",
		"title" => "kwg_contacts_title",
		"description" => "kwg_contacts_description",
		"type" => "system",
		"policy" => "optional",
		"quantity" => "many",
		"suggest" => "",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array(
			array (
				"name" => "email_main",
				"title" => "kw_contacts_emailmain_title",
				"description" => "kw_contacts_emailmain_description",
				"ac_author" => "Y"),
			array (
				"name" => "email_alternate",
				"title" => "kw_contacts_emailalternate_title",
				"description" => "kw_contacts_emailalternate_description",
				"ac_author" => "Y"),
			array (
				"name" => "phone_home",
				"title" => "kw_contacts_phonehome_title",
				"description" => "kw_contacts_phonehome_description",
				"ac_author" => "Y"),
			array (
				"name" => "phone_office",
				"title" => "kw_contacts_phoneoffice_title",
				"description" => "kw_contacts_phoneoffice_description",
				"ac_author" => "Y"),
			array (
				"name" => "phone_mobile",
				"title" => "kw_contacts_phonemobile_title",
				"description" => "kw_contacts_phonemobile_description",
				"ac_author" => "Y"),
			array (
				"name" => "address_main",
				"title" => "kw_contacts_addressmain_title",
				"description" => "kw_contacts_addressmain_description",
				"ac_author" => "Y")
		)
	),
	
	"appointments" => array(
		"name" => "appointments",
		"title" => "kwg_appointments_title",
		"description" => "kwg_appointments_description",
		"type" => "system",
		"policy" => "optional",
		"quantity" => "many",
		"suggest" => "meeting",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array(
			array (
				"name" => "meeting",
				"title" => "kw_appointments_meeting_title",
				"description" => "kw_appointments_meeting_description",
				"ac_author" => "Y"),
			array (
				"name" => "phone_conversation",
				"title" => "kw_appointments_phone_conversation_title",
				"description" => "kw_appointments_phone_conversation_description",
				"ac_author" => "Y"),
			array (
				"name" => "court_session",
				"title" => "kw_appointments_court_session_title",
				"description" => "kw_appointments_court_session_description",
				"ac_author" => "Y")
		)
	),

	"civilstatus" => array(
		"name" => "civilstatus",
		"title" => "kwg_civilstatus_title",
		"description" => "kwg_civilstatus_description",
		"type" => "system",
		"policy" => "optional",
		"quantity" => "one",
		"suggest" => "unknown",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array(
			array (
				"name" => "unknown",
				"title" => "kw_civilstatus_unknown_title",
				"description" => "kw_civilstatus_unknown_description",
				"ac_author" => "Y"),
			array (
				"name" => "single",
				"title" => "kw_civilstatus_single_title",
				"description" => "kw_civilstatus_single_description",
				"ac_author" => "Y"),
			array (
				"name" => "married", // or civil union
				"title" => "kw_civilstatus_married_title",
				"description" => "kw_civilstatus_married_description",
				"ac_author" => "Y"),
			array (
				"name" => "divorced", // or seperated
				"title" => "kw_civilstatus_divorced_title",
				"description" => "kw_civilstatus_divorced_description",
				"ac_author" => "Y"),
			array (
				"name" => "widowed",
				"title" => "kw_civilstatus_widowed_title",
				"description" => "kw_civilstatus_widowed_description",
				"ac_author" => "Y"),
		)
	),

	"income" => array(
		"name" => "income",
		"title" => "kwg_income_title",
		"description" => "kwg_income_description",
		"type" => "system",
		"policy" => "optional",
		"quantity" => "one",
		"suggest" => "unknown",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array(
			array (
				"name" => "unknown",
				"title" => "kw_income_unknown_title",
				"description" => "kw_income_unknown_description",
				"ac_author" => "Y"),
			array (
				"name" => "low",
				"title" => "kw_income_low_title",
				"description" => "kw_income_low_description",
				"ac_author" => "Y"),
			array (
				"name" => "average",
				"title" => "kw_income_average_title",
				"description" => "kw_income_average_description",
				"ac_author" => "Y"),
			array (
				"name" => "high",
				"title" => "kw_income_high_title",
				"description" => "kw_income_high_description",
				"ac_author" => "Y"),
		)
	),

	"stage" => array(
		"name" => "stage",
		"title" => "kwg_stage_title",
		"description" => "kwg_stage_description",
		"type" => "system",
		"policy" => "optional",
		"quantity" => "one",
		"suggest" => "investigation",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array(
			array (
				"name" => "investigation",
				"title" => "kw_stage_investigation_title",
				"description" => "kw_stage_investigation_description",
				"ac_author" => "Y"),
			array (
				"name" => "pre-trial",
				"title" => "kw_stage_pre-trial_title",
				"description" => "kw_stage_pre-trial_description",
				"ac_author" => "Y"),
			array (
				"name" => "trial",
				"title" => "kw_stage_trial_title",
				"description" => "kw_stage_trial_description",
				"ac_author" => "Y"),
			array (
				"name" => "appeal",
				"title" => "kw_stage_appeal_title",
				"description" => "kw_stage_appeal_description",
				"ac_author" => "Y"),
			array (
				"name" => "second_appeal",
				"title" => "kw_stage_second_appeal_title",
				"description" => "kw_stage_second_appeal_description",
				"ac_author" => "Y"),
		)
	)
);

function create_groups($keyword_groups) {
	foreach ($keyword_groups as $skwg) {
		// Insert keyword group data into database table
		$q = "INSERT IGNORE INTO lcm_keyword_group 
				(name, title, description, type, policy, quantity, suggest, ac_admin, ac_author) 
			VALUES (" 
				. "'" . addslashes($skwg['name']) . "', "
				. "'" . addslashes($skwg['title']) . "', "
				. "'" . addslashes($skwg['description']) . "', "
				. "'" . addslashes($skwg['type']) . "', "
				. "'" . addslashes($skwg['policy']) . "', "
				. "'" . addslashes($skwg['quantity']) . "', "
				. "'" . addslashes($skwg['suggest']) . "', "
				. "'" . addslashes($skwg['ac_admin']) . "', "
				. "'" . addslashes($skwg['ac_author']) . "')";

		$result = lcm_query($q);

		// Findout under what ID is this group stored
		$q = "SELECT id_group,name FROM lcm_keyword_group WHERE name='" . addslashes($skwg['name']) . "'";
		$result = lcm_query($q);
		$row = lcm_fetch_array($result);
		$kwg_id = $row['id_group'];

		// If group is not successfully created or its ID is not found, report error
		if ($kwg_id < 1) {
			lcm_log("create_groups: creation of keyword group seems to have failed. Aborting.");
			lcm_log("-> Query was: " . $q);
			return;
		}

		// Insert keywords data into database table
		foreach ($skwg['keywords'] as $k) {
			$q = "INSERT IGNORE INTO lcm_keyword
					(id_group, name, title, description, ac_author)
				VALUES ("
					. $kwg_id . ", "
					. "'" . addslashes($k['name']) . "', "
					. "'" . addslashes($k['title']) . "', "
					. "'" . addslashes($k['description']) . "', "
					. "'" . addslashes($k['ac_author']) . "')";

			$result = lcm_query($q);
		}
	}
}

//echo "KWG = $system_keyword_groups";
//print_r($system_keyword_groups);

?>

