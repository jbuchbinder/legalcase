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

	$Id: lcm_help.php,v 1.2 2005/02/01 17:11:35 mlutfy Exp $
*/

include('inc/inc.php');

$code = $_REQUEST['code'];

if ($code)
	$page_title = _T('help_title_' . $code);
else
	$page_title = _T('title_software');

help_page_start($page_title);

if ($code) {
	// Include the help page
	echo '<p>Todo: Include help for ' . $page_title . '.</p>';

} else {
	// Show LCM logo
	echo '<div align="center">';
	echo '<img src="images/lcm/lcm_logo_install.png" alt="" width="490" height="242" />' . "\n";
	echo '</div>';
}

help_page_end();

?>
