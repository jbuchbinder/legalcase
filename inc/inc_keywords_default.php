<?php

if (defined('_INC_KEYWORDS_DEFAULT')) return;
define('_INC_KEYWORDS_DEFAULT', '1');

include_lcm('inc_keywords');

global $system_keyword_groups;

$system_keyword_groups = array (
	array(
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
	
	array(
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
	)
);

function create_groups($keyword_groups) {
	foreach ($keyword_groups as $skwg) {
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

		$result = lcm_query($q);
		$kwg_id = lcm_insert_id();

		if ($kwg_id < 1) {
			lcm_log("create_groups: creation of keyword group seems to have failed. Aborting.");
			lcm_log("-> Query was: " . $q);
			return;
		}

		foreach ($skwg['keywords'] as $k) {
			$q = "INSERT INTO lcm_keyword
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

echo "KWG = $system_keyword_groups";
print_r($system_keyword_groups);

?>

