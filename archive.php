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

	$Id: archive.php,v 1.19 2006/04/21 19:30:36 mlutfy Exp $
*/

include('inc/inc.php');
include_lcm('inc_acc');
include_lcm('inc_filters');
include_lcm('inc_impex');
include_lcm('inc_obj_case');

// Restrict page to administrators
if ($author_session['status'] != 'admin') {
	lcm_page_start(_T('title_archives'), '', '', 'archives_intro');
	echo '<p class="normal_text">' . _T('warning_forbidden_not_admin') . "</p>\n";
	lcm_page_end();
	exit;
}

$find_case_string = '';
if (isset($_REQUEST['find_case_string']))
	$find_case_string = $_REQUEST['find_case_string'];

if (!empty($_REQUEST['export']) && ($GLOBALS['author_session']['status'] == 'admin')) {
	export('case', $_REQUEST['exp_format'], $find_case_string);
	exit;
}

// Show page start
lcm_page_start(_T('title_archives'), '', '', 'archives_intro');

// Show tabs
$tabs = array(	array('name' => _T('archives_tab_all_cases'), 'url' => 'archive.php'),
		array('name' => _T('archives_tab_export'), 'url' => 'export_db.php'),
		array('name' => _T('archives_tab_import'), 'url' => 'import_db.php')
	);
show_tabs_links($tabs,0);

show_find_box('case', $find_case_string, '__self__');

$case_list = new LcmCaseListUI();

$case_list->setSearchTerm($find_case_string);
$case_list->start();
$case_list->printList();
$case_list->finish();

lcm_page_end();

?>
