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

	$Id: inc_meta_defaults.php,v 1.9 2005/02/07 08:16:20 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_DEFAULTS')) return;
define('_INC_DEFAULTS', '1');

include_lcm('inc_meta');

//
// Apply default configurations 
// (called by installation + upgrade)
//
function init_default_config() {
	// default language of the site = installation language (cookie)
	// (if no cookie, then set to English)
	if (!$lang = $GLOBALS['lcm_lang'])
		$lang = 'en';

	// c.f. http://www.lcm.ngo-bg.org/article28.html
	$list_meta = array(
		// Default language of the site 
		// (users can also personalise it individually).
		'default_language' => $lang,

		// Does the site allow users to self-register an account? (yes/no)
		'site_open_subscription' => 'no',

		// Defaut name/description of the site
		'site_name' => _T('title_software'),
		'site_description' => _T('title_software_description'),

		// Default currency (based on the language/regional of admin)
		'currency' => _T('currency_default_format'),

		// ** Collaborative work **
		// Is case information (read) public/private?
		// If public, anyone can view the case/follow-up information
		'case_default_read' => 'yes',

		// Is case participation (write) public/private?
		// If public, anyone can add follow-up information
		'case_default_write' => 'no',

		// Is the policy systematically enforced? (yes/no)
		// If 'no' the user will be allowed to choose how others
		// can read/write to his case
		'case_read_always' => 'no',
		'case_write_always' => 'no',

		// ** Policy **
		'client_name_middle' => 'yes',
		'client_citizen_number' => 'no',
		'case_court_archive' => 'yes',
		'case_assignment_date' => 'yes',
		'case_alledged_crime' => 'yes',
		'case_allow_modif' => 'yes',
		'fu_sum_billed' => 'yes',
		'fu_allow_modif' => 'yes',
		'hide_emails' => 'no',

		// Default character set, it may not even be a question
		// in the future, but may have uses.
		'charset' => 'UTF-8'
	);

	while (list($key, $value) = each($list_meta)) {
		if (!read_meta($key)) {
			write_meta($key, $value);
			$modifs = true;
		}
	}

	if ($modifs) write_metas();

	// Force the update list of available languages
	include_lcm('inc_lang');
	init_languages(true);
}

?>
