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
include_lcm('inc_filters');

function read_author_data($id_author) {
	$q = "SELECT * FROM lcm_author WHERE id_author=" . $id_author;
	$result = lcm_query($q);
	if (!($usr = lcm_fetch_array($result))) die(_T('error_no_such_user'));

	return $usr;
}

function show_author_form() {
	global $author_session;
	global $prefs;

?>
<form name="upd_user_profile" method="post" action="config_author.php">
	<input type="hidden" name="author_ui_modified" value="yes"/>
	<input type="hidden" name="referer" value="<?php echo $GLOBALS['HTTP_REFERER']; ?>"/>

	<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">
		<tr>
			<td colspan="2" align="center" valign="middle" class="heading"><h4><?php echo _T('authorconf_subtitle_interface'); ?></h4></td>
		</tr>
<?php
	if ($GLOBALS['all_langs']) {
		echo "
			<tr>
				<td align=\"right\" valign=\"top\">" . _T('authorconf_input_language') . "</td>
				<td align=\"left\" valign=\"top\">
					<input type='hidden' name='old_language' value='" .  $author_session['lang'] . "'/>\n";

		echo menu_languages('sel_language');
		echo "
				</td>
			</tr>\n";
	}
?>
	    <tr>
	    	<td align="right" valign="top"><?php echo _T('authorconf_input_screen') ?></td>
			<td align="left" valign="top">
				<input type="hidden" name="old_screen" id="old_screen" value="<?php echo $prefs['screen'] ?>" />
				<select name="sel_screen" class="sel_frm">
<?php
	$screen_modes = array("wide","narrow");
	foreach ($screen_modes as $scrm) {
		$selected_mode = ($scrm == $prefs['screen'] ? " selected='selected'" : '');
		echo "<option value='" . $scrm . "'" . $selected_mode . ">" 
			. _T('authorconf_input_screen_' . $scrm) 
			. "</option>\n";
	}
?>
				</select>
			</td>
		</tr>
		<tr>
			<td align="right" valign="top"><?php echo _T('authorconf_input_theme'); ?></td>
			<td align="left" valign="top">
				<input type="hidden" name="old_theme" id="old_theme" value="<?php echo $prefs['theme'] ?>" />
				<select name="sel_theme" class="sel_frm" id="sel_theme">
<?php
	$themes = get_theme_list();
	foreach ($themes as $t) {
		$selected = ($t == $prefs['theme'] ? " selected='selected'" : '');
		echo "<option value='" . $t . "'" . $selected . ">" . _T('authorconf_input_theme_' . $t) . "</option>\n";
	}
?>
				</select>
			</td>
		</tr>
		<tr>
			<td align="right" valign="top">Font size:</td>
			<td align="left" valign="top"><input name="inc_fnt" type="button" class="search_form_btn" id="inc_fnt" value="A -" />
                &nbsp; <input name="dec_fnt" type="button" class="search_form_btn" id="dec_fnt" value="A +" />
				(not working yet)
			</td>
		</tr>
		<tr>
			<td align="right" valign="top"><?php echo _T('authorconf_input_results_per_page'); ?></td>
			<td align="left" valign="top">
				<input name="page_rows" type="text" class="search_form_txt" id="page_rows" size="3" value="<?php
					// page_rows gets default value in inc_auth.php
					echo $prefs['page_rows']; ?>" />
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center" valign="middle">
				<input type="submit" name="submit" type="submit" class="search_form_btn" id="submit" value="<?php echo _T('authorconf_button_update_preferences'); ?>" /></td>
		</tr>
	</table>
</form>

		  <br />

<form name="upd_user_profile" method="post" action="config_author.php">
	<input type="hidden" name="author_password_modified" value="yes"/>
	<input type="hidden" name="referer" value="<?php echo $GLOBALS['HTTP_REFERER']; ?>"/>
          <table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" class="tbl_usr_dtl">
            <tr>
              <td colspan="2" align="center" valign="middle"
			  class="heading"><h4><?php echo _T('authorconf_subtitle_password'); ?></h4></td>
            </tr>
            <tr>
              <td align="right" valign="top"><?php echo _T('authorconf_input_password_current'); ?></td>
              <td align="left" valign="top"><input name="usr_old_passwd" type="password" class="search_form_txt" id="usr_old_passwd" size="35" /></td>
            </tr>
            <tr>
              <td align="right" valign="top"><?php echo _T('authorconf_input_password_new'); ?></td>
              <td align="left" valign="top"><input name="usr_new_passwd" type="password" class="search_form_txt" id="usr_new_passwd" size="35" /></td>
            </tr>
            <tr>
              <td align="right" valign="top"><?php echo _T('authorconf_input_password_confirm'); ?></td>
              <td align="left" valign="top"><input name="usr_retype_passwd" type="password" class="search_form_txt" id="usr_retype_passwd" size="35" /></td>
            </tr>
            <tr> 
              <td colspan="2" align="center" valign="middle"> 
                <input name="submit" type="submit" class="search_form_btn" id="submit" value="<?php echo _T('authorconf_button_change_password'); ?>" /></td>
            </tr>
			</table>
</form>


            <!-- tr>
              <td colspan="2" align="center" valign="middle" class="heading"><h4>Change personal data</h4></td>
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
              <td align="left" valign="top"><input name="usr_fname" type="text" class="search_form_txt" id="usr_fname" size="35" value="<?php echo clean_output($usr['name_first']); ?>"/></td>
            </tr>
            <tr>
              <td align="right" valign="top">Middle name:</td>
              <td align="left" valign="top"><input name="usr_mname" type="text" class="search_form_txt" id="usr_mname" size="35"  value="<?php echo clean_output($usr['name_middle']); ?>"/></td>
            </tr>
            <tr>
              <td align="right" valign="top">Last name:</td>
              <td align="left" valign="top"><input name="usr_lname" type="text" class="search_form_txt" id="usr_lname" size="35"  value="<?php echo clean_output($usr['name_last']); ?>"/></td>
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
              <td colspan="2" align="right" valign="top" class="separate">&nbsp;</td>
            </tr>
            <tr>
              <td colspan="2" align="center" valign="middle">
                <input name="submit" type="submit" class="search_form_btn" id="submit" value="Save changes" /></td>
            </tr>
          </table -->
<?php

}

function apply_author_ui_change() {
	global $author_session;
	global $lcm_session;
	global $prefs;

	// From the form
	global $sel_language, $old_language;
	global $sel_theme, $old_theme;
	global $sel_screen, $old_screen;

	// Show modifications made one finished
	$log = array();

	//
	// Change the user's language (done in inc.php, we only log the result)
	//

	if ($sel_language <> $old_language) {
		array_push($log, "Language set to " .
			translate_language_name($sel_language) . ", was " .
			translate_language_name($old_language) . ".");
	}

	//
	// Change the user's UI colors (done in inc.php, we only log the result)
	//

	if ($sel_theme == $prefs['theme'] && $sel_theme <> $old_theme)
		array_push($log, "Theme set to " . $sel_theme . ", was " . $old_theme . ".");
		
	//
	// Change the type of the screen - wide or narrow
	//
	
	if ($sel_screen == $prefs['sel_screen'] && $sel_screen <> $old_screen)
		array_push($log, "Screen mode set to " . $sel_screen . ", was " . $old_screen . ".");

}

function apply_author_password_change() {
	global $author_session;
	global $lcm_session;
	global $prefs;
	global $log;

	// From the form
	global $usr_old_passwd;
	global $usr_new_passwd;
	global $usr_retype_passwd;


	// Show modifications made one finished
	$log = array();

	//
	// Change the author's password
	//

	// TODO:
	// - verify is new password long enough (> 5)
	// - verify if two passwords match
	// - verify if old password matches


}

function show_changes() {
	global $log;
	//
	// Show changes on screen
	//
	if (! empty($log)) {
		echo "<div align='left' style='border: 1px solid #00ff00; padding: 5px;'>\n";
		echo "<div>Changes made:</div>\n";
		echo "<ul>";

		foreach ($log as $line)
			echo "<li>" . $line . "</li>\n";

		echo "</ul>\n";
		echo "</div>\n";
	}
}

if (isset($_POST['author_ui_modified']))
	apply_author_ui_change();

if (isset($_POST['$author_password_modified']))
	apply_author_password_change();

if (isset($_POST['author_ui_modified']) || isset($_POST['author_password_modified'])) {
	if ($referer) {
		header('Location: ' . $referer);
//		header('Retry-After: 30');
		exit;
	} else {
		show_changes();
	}
}

lcm_page_start(_T('title_authorconf'));

show_author_form();
lcm_page_end();

?>
