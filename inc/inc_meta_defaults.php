<?php

// Execute this file only once
if (defined('_INC_DEFAULTS')) return;
define('_INC_DEFAULTS', '1');

include_lcm('inc_meta');

//
// Apply default configurations (usually used at installation time)
//
function init_default_config() {
	// default language of the site = installation language (cookie)
	// (if no cookie, then set to English)
	if (!$lang = $GLOBALS['lcm_lang'])
		$lang = 'en';

	// [ML] Cleanup needed
	$liste_meta = array(
		'default_language' => $lang,
		// 'langue_site' => $lang, // OLD

		'site_open_subscription' => 'no',
		// 'accepter_inscriptions' => 'non', // OLD

		'site_name' => _T('title_software'),
		'site_description' => _T('title_software_description'),

		// This might not even be a question in the future,
		// but let's leave it for now.
		'charset' => 'UTF-8',

		'available_languages' => $GLOBALS['all_langs']
		// 'langues_multilingue' => $GLOBALS['all_langs'], // OLD
	);

	while (list($nom, $valeur) = each($liste_meta)) {
		if (!read_meta($nom)) {
			write_meta($nom, $valeur);
			$modifs = true;
		}
	}

	if ($modifs) write_metas();
}

?>
