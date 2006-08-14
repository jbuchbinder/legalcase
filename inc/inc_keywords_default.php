<?php

if (defined('_INC_KEYWORDS_DEFAULT')) return;
define('_INC_KEYWORDS_DEFAULT', '1');

include_lcm('inc_keywords');

function get_default_keywords() {
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
				"name" => "stage_change",
				"title" => "kw_followups_stage_change_title",
				"description" => "kw_followups_stage_change_description",
				"ac_author" => "N"),
			array (
				"name" => "opening",
				"title" => "kw_followups_opening_title",
				"description" => "kw_followups_opening_description",
				"ac_author" => "N"),
			array (
				"name" => "assignment",
				"title" => "kw_followups_assignment_title",
				"description" => "kw_followups_assignment_description",
				"ac_author" => "N"),
			array (
				"name" => "unassignment",
				"title" => "kw_followups_unassignment_title",
				"description" => "kw_followups_unassignment_description",
				"ac_author" => "N"),
			array (
				"name" => "suspension",
				"title" => "kw_followups_suspension_title",
				"description" => "kw_followups_suspension_description",
				"ac_author" => "N"),
			array (
				"name" => "resumption",
				"title" => "kw_followups_resumption_title",
				"description" => "kw_followups_resumption_description",
				"ac_author" => "N"),
			array (
				"name" => "delay",
				"title" => "kw_followups_delay_title",
				"description" => "kw_followups_delay_description",
				"ac_author" => "N"),
			array (
				"name" => "conclusion",
				"title" => "kw_followups_conclusion_title",
				"description" => "kw_followups_conclusion_description",
				"ac_author" => "N"),
			array (
				"name" => "deletion",
				"title" => "kw_followups_deletion_title",
				"description" => "kw_followups_deletion_description",
				"ac_author" => "N"),
			array (
				"name" => "reopening",
				"title" => "kw_followups_reopening_title",
				"description" => "kw_followups_reopening_description",
				"ac_author" => "N"),
			array (
				"name" => "merge",
				"title" => "kw_followups_merge_title",
				"description" => "kw_followups_merge_description",
				"ac_author" => "N"),
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

	//
	// Contacts
	//
	"+email_main" => array(
		"name" => "+email_main",
		"title" => "kw_contacts_emailmain_title",
		"description" => "kw_contacts_emailmain_description",
		"type" => "contact",
		"policy" => "optional",
		"quantity" => "one",
		"suggest" => "",
		"ac_admin" => "N", // required by system
		"ac_author" => "Y",
		"keywords" => array()
	),

	"+email_alternate" => array(
		"name" => "+email_alternate",
		"title" => "kw_contacts_emailalternate_title",
		"description" => "kw_contacts_emailalternate_description",
		"type" => "contact",
		"policy" => "optional",
		"quantity" => "many",
		"suggest" => "",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array()
	),

	"+phone_home" => array(
		"name" => "+phone_home",
		"title" => "kw_contacts_phonehome_title",
		"description" => "kw_contacts_phonehome_description",
		"type" => "contact",
		"policy" => "recommended",
		"quantity" => "many",
		"suggest" => "",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array()
	),

	"+phone_office" => array(
		"name" => "+phone_office",
		"title" => "kw_contacts_phoneoffice_title",
		"description" => "kw_contacts_phoneoffice_description",
		"type" => "contact",
		"policy" => "optional",
		"quantity" => "many",
		"suggest" => "",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array()
	),

	"+phone_mobile" => array(
		"name" => "+phone_mobile",
		"title" => "kw_contacts_phonemobile_title",
		"description" => "kw_contacts_phonemobile_description",
		"type" => "contact",
		"policy" => "optional",
		"quantity" => "many",
		"suggest" => "",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array()
	),

	"+address_main" => array(
		"name" => "+address_main",
		"title" => "kw_contacts_addressmain_title",
		"description" => "kw_contacts_addressmain_description",
		"type" => "contact",
		"policy" => "recommended",
		"quantity" => "one",
		"suggest" => "",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array()
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
	),

	"conclusion" => array(
		"name" => "conclusion",
		"title" => "kwg_conclusion_title",
		"description" => "kwg_conclusion_description",
		"type" => "system",
		"policy" => "optional",
		"quantity" => "one",
		"suggest" => "none",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array(
			array (
				"name" => "none",
				"title" => "10. kw_conclusion_none_title",
				"description" => "kw_conclusion_none_description",
				"ac_author" => "Y"),
			array (
				"name" => "guilty",
				"title" => "15. kw_conclusion_guilty_title",
				"description" => "kw_conclusion_guilty_description",
				"ac_author" => "Y"),
			array (
				"name" => "notguilty",
				"title" => "20. kw_conclusion_notguilty_title",
				"description" => "kw_conclusion_notguilty_description",
				"ac_author" => "Y"),
			array (
				"name" => "cessation",
				"title" => "25. kw_conclusion_cessation_title",
				"description" => "kw_conclusion_cessation_description",
				"ac_author" => "Y"),
			array (
				"name" => "reinvestigation",
				"title" => "30. kw_conclusion_reinvestigation_title",
				"description" => "kw_conclusion_reinvestigation_description",
				"ac_author" => "Y")
		)
	),

	"sentence" => array(
		"name" => "sentence",
		"title" => "kwg_sentence_title",
		"description" => "kwg_sentence_description",
		"type" => "system",
		"policy" => "optional",
		"quantity" => "one",
		"suggest" => "none",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array(
			array (
				"name" => "none",
				"title" => "10. kw_sentence_none_title",
				"description" => "kw_sentence_none_description",
				"ac_author" => "Y"),
			array (
				"name" => "fine",
				"title" => "15. kw_sentence_fine_title",
				"description" => "kw_sentence_fine_description",
				"ac_author" => "Y"),
			array (
				"name" => "prison",
				"title" => "20. kw_sentence_prison_title",
				"description" => "kw_sentence_prison_description",
				"ac_author" => "Y"),
			array (
				"name" => "probation",
				"title" => "25. kw_sentence_probation_title",
				"description" => "kw_sentence_probation_description",
				"ac_author" => "Y"),
			array (
				"name" => "community",
				"title" => "30. kw_sentence_community_title",
				"description" => "kw_sentence_community_description",
				"ac_author" => "Y")
		)
	),

	// [ML] I am prefixing with _, to show that it is system-created,
	// because at this point in the development process, we have no 
	// garanty that the user has not already created a kwg with this name.
	"_crimresults" => array(
		"name" => "_crimresults",
		"title" => "kwg__crimresults_title",
		"description" => "kwg__crimresults_title",
		"type" => "system",
		"policy" => "optional",
		"quantity" => "one",
		"suggest" => "none",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array(
			array (
				"name" => "none",
				"title" => "10. kw__crimresults_none_title",
				"description" => "kw__crimresults_none_description",
				"ac_author" => "Y"),
			array (
				"name" => "res001", // sentence
				"title" => "15. kw__crimresults_res001_title",
				"description" => "kw__crimresults_res001_description",
				"ac_author" => "Y"),
			array (
				"name" => "res002", // decision
				"title" => "20. kw__crimresults_res002_title",
				"description" => "kw__crimresults_res002_description",
				"ac_author" => "Y"),
			array (
				"name" => "res003", // agreement
				"title" => "25. kw__crimresults_res003_title",
				"description" => "kw__crimresults_res003_description",
				"ac_author" => "Y"),
			array (
				"name" => "res004", // liberation of criminal responsability
				"title" => "30. kw__crimresults_res004_title",
				"description" => "kw__crimresults_res004_description",
				"ac_author" => "Y"),
			array (
				"name" => "res005", // cessation
				"title" => "35. kw__crimresults_res005_title",
				"description" => "kw__crimresults_res005_description",
				"ac_author" => "Y"),
			array (
				"name" => "res006", // stopped
				"title" => "40. kw__crimresults_res006_title",
				"description" => "kw__crimresults_res006_description",
				"ac_author" => "Y"),
			array (
				"name" => "res007", // ruling
				"title" => "45. kw__crimresults_res007_title",
				"description" => "kw__crimresults_res007_description",
				"ac_author" => "Y"),
			array (
				"name" => "res008", // other
				"title" => "50. kw__crimresults_res008_title",
				"description" => "kw__crimresults_res008_description",
				"ac_author" => "Y"),
		)
	),

	"_refnumbers" => array (
		"name" => "_refnumbers",
		"title" => "kwg__refnumbers_title",
		"description" => "kwg__refnumbers_description",
		"type" => "stage",
		"policy" => "optional",
		"quantity" => "many",
		"suggest" => "",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array (
			array (
				"name" => "initialrefnum",
				"title" => "10. kw__refnumbers_initialrefnum_title",
				"description" => "kw__refnumbers_initialrefnum_description",
				"hasvalue" => "Y",
				"ac_author" => "N" ),
			array (
				"name" => "filenum",
				"title" => "12. kw__refnumbers_filenum_title",
				"description" => "kw__refnumbers_filenum_description",
				"hasvalue" => "Y",
				"ac_author" => "N" ),
			array (
				"name" => "courtarchive",
				"title" => "14. kw__refnumbers_courtarchive_title",
				"description" => "kw__refnumbers_courtarchive_description",
				"hasvalue" => "Y",
				"ac_author" => "Y" ),
		)
	),

	"_institutions" => array (
		"name" => "_institutions",
		"title" => "kwg__institutions_title",
		"description" => "kwg__institutions_description",
		"type" => "stage",
		"policy" => "optional",
		"quantity" => "one",
		"suggest" => "",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array (
			array (
				"name" => "institution001",
				"title" => "1. kw__institutions_001_title",
				"description" => "kw__institutions_001_description",
				"ac_author" => "Y" ),
			array (
				"name" => "institution002",
				"title" => "2. kw__institutions_002_title",
				"description" => "kw__institutions_002_description",
				"ac_author" => "Y" ),
			array (
				"name" => "institution003",
				"title" => "3. kw__institutions_003_title",
				"description" => "kw__institutions_003_description",
				"ac_author" => "Y" )
		)
	),

	"_exptypes" => array (
		"name" => "_exptypes",
		"title" => "kwg__exptypes_title",
		"description" => "kwg__exptypes_description",
		"type" => "system",
		"policy" => "mandatory",
		"quantity" => "one",
		"suggest" => "",
		"ac_admin" => "Y",
		"ac_author" => "Y",
		"keywords" => array (
			array (
				"name" => "_exptypes01",
				"title" => "10. kw__exptypes__exptypes01_title",
				"description" => "kw__exptypes__exptypes01_description",
				"hasvalue" => "N",
				"ac_author" => "Y"),
			array (
				"name" => "_exptypes02",
				"title" => "10. kw__exptypes__exptypes02_title",
				"description" => "kw__exptypes__exptypes02_description",
				"hasvalue" => "N",
				"ac_author" => "Y"),
			array (
				"name" => "_exptypes_other",
				"title" => "90. kw__exptypes__exptypes_other_title",
				"description" => "kw__exptypes__exptypes_other_description",
				"hasvalue" => "N",
				"ac_author" => "Y")
		)
	)
  );

  return $system_keyword_groups;
}

function create_groups($keyword_groups) {
	foreach ($keyword_groups as $skwg) {
		// Insert keyword group data into database table
		$q = "INSERT INTO lcm_keyword_group 
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

		$result = lcm_query($q, true); // Ignore if keyword exists (has unique key)
		
		// Findout under what ID is this group stored
		// Note: Do this instead of lcm_insert_id() because the keyword might not have been 
		// inserted, so using lcm_insert_id() would re-create ALL keywords using the latest kwg id...
		$q = "SELECT id_group,name FROM lcm_keyword_group WHERE name='" . addslashes($skwg['name']) . "'";
		$result = lcm_query($q);
		$row = lcm_fetch_array($result);
		$kwg_id = $row['id_group'];

		// If group is not successfully created or its ID is not found, report error
		// [ML] Failed SQL insert generates lcm_panic(), so this becomes useless.
		if ($kwg_id < 1) {
			lcm_log("create_groups: creation of keyword group seems to have failed. Aborting.");
			lcm_log("-> Query was: " . $q);
			return;
		}

		// Insert keywords data into database table
		foreach ($skwg['keywords'] as $k) {
			if (! isset($k['hasvalue']))
				$k['hasvalue'] = 'N';

			$q = "INSERT INTO lcm_keyword
					(id_group, name, title, description, hasvalue, ac_author)
				VALUES ("
					. $kwg_id . ", "
					. "'" . addslashes($k['name']) . "', "
					. "'" . addslashes($k['title']) . "', "
					. "'" . addslashes($k['description']) . "', "
					. "'" . addslashes($k['hasvalue']) . "', "
					. "'" . addslashes($k['ac_author']) . "')";

			$result = lcm_query($q, true); // Ignore if keyword exists (has unique key)
		}
	}
}

?>

