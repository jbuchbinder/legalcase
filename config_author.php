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
*/

include('inc/inc.php');

function show_author_form() {
	// TODO: Show author information

?>
<form name="upd_user_profile" method="post" action="config_author.php">
	<input type="hidden" name="author_modified" value="yes"/>

          <table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">
            <tr> 
              <td colspan="2" align="center" valign="middle" class="heading"><h4>Change 
                  personal data</h4></td>
            </tr>
            <tr> 
              <td align="right" valign="top">Title:</td>
              <td align="left" valign="top"><select name="usr_title" class="sel_frm" id="usr_title">
                  <option value="Mr." selected="selected">Mr.</option>
                  <option value="Mrs.">Mrs.</option>
                  <option value="Miss">Miss</option>
                  <option value="Dr.">Dr.</option>
                  <option value="Prof.">Prof.</option>
                </select></td>
            </tr>
            <tr> 
              <td align="right" valign="top">First name:</td>
              <td align="left" valign="top"><input name="usr_fname" type="text" class="search_form_txt" id="usr_fname" size="35" /></td>
            </tr>
            <tr>
              <td align="right" valign="top">Last name:</td>
              <td align="left" valign="top"><input name="usr_lname" type="text" class="search_form_txt" id="usr_lname" size="35" /></td>
            </tr>
            <tr>
              <td align="right" valign="top">Phone #:</td>
              <td align="left" valign="top"><input name="usr_phonenum" type="text" class="search_form_txt" id="usr_phonenum" size="35" /></td>
            </tr>
            <tr>
              <td align="right" valign="top">E-mail:</td>
              <td align="left" valign="top"><input name="usr_email" type="text" class="search_form_txt" id="usr_email" size="35" /></td>
            </tr>
            <tr>
              <td align="right" valign="top">ICQ:</td>
              <td align="left" valign="top"><input name="usr_icq" type="text" class="search_form_txt" id="usr_icq" size="35" /></td>
            </tr>
            <tr> 
              <td align="right" valign="top">Jabber:</td>
              <td align="left" valign="top"><input name="usr_jabber" type="text" class="search_form_txt" id="usr_jabber" size="35" /></td>
            </tr>
            <tr> 
              <td align="right" valign="top">Address:</td>
              <td align="left" valign="top"><textarea name="usr_address" cols="50" rows="5" wrap="VIRTUAL" class="frm_tarea" id="usr_address"></textarea></td>
            </tr>
            <tr> 
              <td colspan="2" align="center" valign="middle" class="separate">&nbsp;</td>
            </tr>
            <tr> 
              <td colspan="2" align="center" valign="middle" class="heading"><h4>Change 
                  password</h4></td>
            </tr>
            <tr>
              <td align="right" valign="top">Old password:</td>
              <td align="left" valign="top"><input name="usr_old_passwd" type="password" class="search_form_txt" id="usr_old_passwd" size="35" /></td>
            </tr>
            <tr>
              <td align="right" valign="top">New password:</td>
              <td align="left" valign="top"><input name="usr_new_passwd" type="password" class="search_form_txt" id="usr_new_passwd" size="35" /></td>
            </tr>
            <tr> 
              <td align="right" valign="top">Retype new password:</td>
              <td align="left" valign="top"><input name="usr_retype_passwd" type="password" class="search_form_txt" id="usr_retype_passwd" size="35" /></td>
            </tr>
            <tr> 
              <td colspan="2" align="center" valign="middle" class="separate">&nbsp;</td>
            </tr>
            <tr>
              <td colspan="2" align="center" valign="middle" class="heading"><h4>Change user interface preferences</h4></td>
            </tr>
            <tr> 
              <td align="right" valign="top">Theme:</td>
              <td align="left" valign="top"><select name="sel_theme" class="sel_frm" id="sel_theme">
                  <option value="">Default</option>
                  <option value="blue">Blue</option>
                  <option value="orange">Orange</option>
                  <option value="mono">Monochrome</option>
                </select></td>
            </tr>
<?php
	if ($GLOBALS['all_langs']) {
		echo "
			<tr> 
				<td align=\"right\" valign=\"top\">Language:</td>
				<td align=\"left\" valign=\"top\">
					<input type='hidden' name='old_language' value='" .  $GLOBALS['lcm_lang'] . "'/>\n";

		echo menu_languages('sel_language');
		echo "
				</td>
			</tr>\n";
	}
?>
            <tr> 
              <td align="right" valign="top">Font size and font style:</td>
              <td align="left" valign="top"><input name="inc_fnt" type="button" class="search_form_btn" id="inc_fnt" value="A -" /> 
                &nbsp; <input name="dec_fnt" type="button" class="search_form_btn" id="dec_fnt" value="A +" /> 
                <br /> <br /> 
                <select name="sel_fnt" class="sel_frm" id="sel_fnt">
                  <option value="verdana" selected="selected">Verdana</option>
                  <option value="arial">Arial</option>
                  <option value="tahoma">Tahoma</option>
                  <option value="georgia">Georgia</option>
                  <option value="times_new_roman">Times New Roman</option>
                  <option value="Courier">Courier</option>
                </select></td>
            </tr>
            <tr> 
              <td colspan="2" align="right" valign="top" class="separate">&nbsp;</td>
            </tr>
            <tr> 
              <td colspan="2" align="center" valign="middle"> 
                <input name="submit" type="submit" class="search_form_btn" id="submit" value="Save changes" /></td>
            </tr>
          </table>
</form>
<?php

}

// TODO
function apply_author_changes() {
	global $author_session;
	global $lcm_session;

	// From the form
	global $sel_language;
	global $old_language;

	$log = array();
	$all_langs = read_meta('available_languages');

	lcm_log("sel_language = $sel_language");

	if ($sel_language && $sel_language != $old_language) {
		// This part is very strange. In order to use the language immediately,
		// inc/inc.php detects $sel_language and updates the session itself.
		// It is also updated here because I'm paranoid.
	
		// validate if the language exists (altough doesn't validate
		// if the language file is actually present).
		if (ereg(",$sel_language,", ",$all_langs,")) {
			// This cookie is redundant with the session cookie, but allows
			// to kill the session while still remembering the login language.
			lcm_setcookie('lcm_lang', $sel_language, time() + 365 * 24 * 3600);

			// Update the session info
			$GLOBAL['author_session']['lang'] = $sel_language;
			lcm_add_session($author_session, $lcm_session);

			// Update user settings in database
			$query = "UPDATE lcm_author 
						SET lang = '" . addslashes($sel_language) . "'
						WHERE id_author = " . $author_session['id_author'];
			lcm_query($query);

			array_push($log, "Language set to " .
				translate_language_name($sel_language) . ", was " .
				translate_language_name($old_language) . ".");
		} else {
			array_push($log, "Specified language is not valid: " .  htmlspecialchars($sel_language) . ".");
		}
	}

	// Show changes on screen
	if (! empty($log)) {
		echo "<div align='left' style='border: 1px solid #00ff00; padding: 5px;'>\n";
		echo "<div>Changes made:</div>\n";
		echo "<ul>";

		foreach ($log as $line) {
			echo "<li>" . $line . "</li>\n";
		}

		echo "</ul>\n";
		echo "</div>\n";
	}
}

lcm_page_start("Update profile");

if ($author_modified)
	apply_author_changes();

show_author_form();
lcm_page_end();

?>
