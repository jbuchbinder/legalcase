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

	$Id: inc_filters.php,v 1.99 2008/02/01 21:36:40 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_FILTERS')) return;
define('_INC_FILTERS', '1');


// Makes $match substring of $source in bold
// FIXME: does not work with non-latin!
function highlight_matches($source, $match) {
	// Initialize variables
	$model = strtolower($source);
	$match = strtolower($match);
	$p = 0;
	$result = '';
	$ml = strlen($match);

	if ($ml > 0) {
		$i = strpos($model, $match);

		// Cycle each match
		while (!($i === false)) {
			$result .= (substr($source, $p, $i - $p) . '<b>' . substr($source, $i, $ml) . '</b>');
			$p = $i + $ml;
			$i = strpos($model, $match, $p);
		}
	}

	$result .= substr($source, $p, strlen($source) - $p);
	return $result;
}


// Format the date according to the user's preferences or
// the localised format (format ex: datetime_{full,short},
// date_{full,short}, time_short).
function format_date($timestamp = '', $format = 'full') {
	// XXX [ML] this is an absurd waste and redundant, but
	// it works well and accepts many formats.. and I am tired.
	// The most common case anyway, will be to have a date in
	// format 0000-00-00 HH:MM:DD

	if (! $timestamp)
		$timestamp = strftime("%Y-%m-%d %H:%M:%S", time());
	
	if (is_numeric($timestamp))
		$timestamp = strftime("%Y-%m-%d %H:%M:%S", $timestamp);

	// Reacts strangely when date is 00:00:00
	if (preg_match('/0000-.*/', $timestamp))
		return '';

	$dd = recup_date($timestamp);
	$tt = recup_time($timestamp);

	// [AG] strftime() always returns 0 for me (Windows 2K server?).
	// The following works, but Sunday is day 0 instead of 7
	// $day_of_w = strftime("%u", mktime(0, 0, 0, $dd[1], $dd[2], $dd[0]));
	$day_of_w = date("w", mktime(0, 0, 0, $dd[1], $dd[2], $dd[0]));

	if ($format == 'short' && preg_match('/[0-9]{2}([0-9]{2})/', $dd[0], $regs))
		$dd[0] = $regs[1];

	// [ML] Important for backwards compatiblity in code
	if ($format == 'short' || $format == 'full')
		$format = 'datetime_' . $format;
	
	// If no time, do not show it.
	if ($format == 'datetime_full' && $tt[0] == '00' && $tt[1] == '00')
		$format = 'date_full';

	$my_date = _T('format_' . $format, array(
				'day_name' => _T('date_wday_' . ($day_of_w + 0)),
				'month_name' => _T('date_month_' . ($dd[1] + 0)),
				'month_short' => _T('date_month_short_' .($dd[1] + 0)),
				'month' => ($dd[1] + 0),
				'day_order' => _T('date_day_' . $dd[2]),
				'day' => ($dd[2] + 0),
				'year' => $dd[0],
				'hours' => $tt[0],
				'mins' => $tt[1]));

	return $my_date;
}

function format_time($timestamp = '', $format = 'short') {
	return format_date($timestamp, 'time_' . $format);
}

// Formats time interval
function format_time_interval($time, $hours_only=false, $hours_only_format='%.2f') {
	$ret = array();

	if (isset($GLOBALS['db']) && $GLOBALS['db'] == 'pgsql') {
		// FIXME rather bad patch..
		$tmp = recup_time($time);

		$days = 0;

		if ($hours_only) {
			$hours = $tmp[0];
			$minutes = 0;

			if ($tmp[1])
				$hours += ($tmp[1] + 0) / 60;

		} else {
			$hours = $tmp[0] + 0;
			$minutes = $tmp[1] + 0;
		}
	} else {
		if (! is_numeric($time))
			return '';

		if ($time == 0)
			return '0';

		if ($time < 0)
			return '-1'; // should never happen, right? :-)

		if ($hours_only) {
			$days = 0;
			$hours = $time / 3600;
			$minutes = 0;
		} else {
			$days = (int) ($time / 86400);
			$hours = (int) ( ($time % 86400) / 3600);
			$minutes = (int) ( ($time % 3600) / 60);
		}
	}

	if ($days)
		$ret[] = $days . ($hours_only ? '' : '&nbsp;' . _T('time_info_short_day'));

	if ($hours) {
		if ($hours_only) // limit hours to 2 decimals
			$ret[] = sprintf($hours_only_format, $hours)
				. ($hours_only ? '' : '&nbsp;' . _T('time_info_short_hour'));
		else
			$ret[] = $hours . ($hours_only ? '' : '&nbsp;' . _T('time_info_short_hour'));
	}

	if ($minutes) 
		$ret[] = $minutes . ($hours_only ? '' : '&nbsp;' . _T('time_info_short_min'));

	return join(' ',$ret);
}

function format_time_interval_prefs($time) {
	global $prefs;

	$hours_only = $prefs['time_intervals_notation'] == 'hours_only';
	return format_time_interval($time, $hours_only);
}

function format_money($money, $two_cents = true, $show_currency_sign = false) {
	// this is very stupid i18n because windows does not have strfmon,
	// altough we cannot depend on locales on all servers for all languages
	// so for our small needs, this should be good enough.
	//
	// FIXME: $two_cents usually means that it is money, not a file size.
	// This should be documented more clearly.
	//
	// FIXME: put this in a contrib, somewhere else. Allow to override using
	// a syntax such as format_money_$func(...) and provide a default function
	// for those who wish to use money_format() of PHP (linux only).
	if ($two_cents && isset($GLOBALS['format_money_function'])) {
		$f = 'format_money_' . $GLOBALS['format_money_function'];
		return $f($money, $two_cents, $show_currency_sign);
	}

	if (is_string($money))
		$money = trim($money);

	if (! $money)
		$money = 0.0;

	if (! ($money===0.0 || is_numeric($money)) )
		lcm_panic("parameter is not a valid number: " . $money);
	
	$seperator_cents    = _T('currency_format_seperator_cents');
	$seperator_hundreds = _T('currency_format_seperator_hundreds');

	$hundreds = (int) $money;
	$cents = round(($money - $hundreds) * 100); // only two last digits

	// format as text
	if ($two_cents) // i.e. "is money"
		$str_cents = sprintf('%02u', $cents);
	else // i.e. "not money" (ex: file size)
		$str_cents = preg_replace("/0+$/", "", $cents);

	if ($hundreds >= 1000)
		$str_hundreds = sprintf('%03u', ($hundreds % 1000));
	else
		$str_hundreds = $hundreds;

	// Test with values: 1000, 100000 etc.
	// Before 0.7.3, it would print "1,0.00" for 1000$
	// Reported by BM on 2007-03-24.
	while ($hundreds >= 1000) {
		$hundreds /= 1000;
		if ($hundreds >= 1000)
			$str_hundreds = sprintf('%03u', ($hundreds % 1000)) . $seperator_hundreds . $str_hundreds;
		else
			$str_hundreds = ($hundreds % 1000) . $seperator_hundreds . $str_hundreds;
	}

	$str_final = $str_hundreds;

	if ($str_cents)
		$str_final .= $seperator_cents . $str_cents;

	if ($show_currency_sign)
		$str_final = _T('currency_format_placement',
				array('currency' => htmlspecialchars(read_meta('currency')), 'money' => $str_final));

	return $str_final;
}

function format_money_india($money, $two_cents = true, $show_currency_sign = false) {
	// See format_money() above.
	// This version formats money for indian standards.
	// ex: 100000 is 1,00,000.00  -- not 100,000.00
	if (is_string($money))
		$money = trim($money);

	if (! $money)
		$money = 0.0;

	if (! ($money===0.0 || is_numeric($money)) )
		lcm_panic("parameter is not a valid number: " . $money);
	
	$seperator_cents    = _T('currency_format_seperator_cents');
	$seperator_hundreds = _T('currency_format_seperator_hundreds');

	$hundreds = (int) $money;
	$cents = round(($money - $hundreds) * 100); // only two last digits

	// format as text
	if ($two_cents) // i.e. "is money"
		$str_cents = sprintf('%02u', $cents);
	else // i.e. "not money" (ex: file size)
		$str_cents = preg_replace("/0+$/", "", $cents);

	// Start with the first 3 digits
	if ($hundreds >= 1000)
		$str_hundreds = sprintf('%03u', ($hundreds % 1000));
	else
		$str_hundreds = $hundreds;

	// not clean hack because it will go into the loop below
	$hundreds /= 10;
	$hundreds = intval($hundreds);

	while ($hundreds > 99) {
		$hundreds /= 100;
		if ($hundreds >= 100)
			$str_hundreds = sprintf('%02u', ($hundreds % 100)) . $seperator_hundreds . $str_hundreds;
		else
			$str_hundreds = ($hundreds % 100) . $seperator_hundreds . $str_hundreds;
	}

	$str_final = $str_hundreds;

	if ($str_cents)
		$str_final .= $seperator_cents . $str_cents;

	if ($show_currency_sign)
		$str_final = _T('currency_format_placement',
				array('currency' => htmlspecialchars(read_meta('currency')), 'money' => $str_final));

	return $str_final;
}

function years_diff($start, $end = '') {
	if (! $end)
		$end = date("Y-m-d"); // Today

	$s = recup_date($start);
	$e = recup_date($end);

	$year_diff = $e[0] - $s[0];

	// check if birthdate has passed in current year (if not, decrease)
	if (($s[1] > $e[1]) || ($s[1] == $e[1] && $e[2] < $s[2]))
		$year_diff--;

	return $year_diff; 
}

// Error display function
// Highlights (outlines) errors in the form data
function f_err($fn, $errors) {
	return (isset($errors[$fn]) ? '<span style="color: #ff0000">' . $errors[$fn] . '</span>' : '');
}

function f_err_star($fn) {
	if (! isset($_SESSION['errors']))
		return '';

	return (isset($_SESSION['errors'][$fn]) ? '<a name="' . $fn . '"></a><span style="color: #ff0000">*</span>' : '');
}

function show_all_errors() {
	$ret = '';

	$all_errors = array();
	$all_info = array();

	if (isset($_SESSION['errors'])) {
		$all_errors = $_SESSION['errors'];
		$_SESSION['errors'] = array();
	}

	if (count($all_errors)) {
		$ret .= '<div align="left" class="err_box">'
			 . '<p class="normal_text">' . _Ti('title_error') . '</p>'
			 . '<ul class="err_list">';


		foreach ($all_errors as $key => $error)
			$ret .= "<li><a href='#$key'>" . $error . "</a></li>\n";

		$ret .= "</ul></div>\n";
	}

	if (isset($_SESSION['info'])) {
		$all_info = $_SESSION['info'];
		$_SESSION['info'] = array();
	}

	if (count($all_info)) {
		$ret .= '<div align="left" class="sys_msg_box">'
			 . '<div>' . _T('siteconf_info_changes_made') . '</div>'
			 . '<ul>';

		foreach ($all_info as $key => $info)
			$ret .= "<li><a href='#$key'>" . $info . "</a></li>\n";

		$ret .= "</ul></div>\n";
	}

	return $ret;
}

// Cleans user input string from 'dangerous' characters
function clean_input($string) {
	if (get_magic_quotes_gpc()) {
		return $string;
	} else {
		return addslashes($string);
	}
}

// Cleans text to be send out
function clean_output($string) {
	if (get_magic_quotes_gpc()) {
		return htmlspecialchars(stripslashes($string));
	} else {
		return htmlspecialchars($string);
	}
}

// Converts &...; to the respective characters
function unhtmlentities($string)
{
   $trans_tbl = get_html_translation_table(HTML_ENTITIES);
   $trans_tbl = array_flip($trans_tbl);
   return strtr($string, $trans_tbl);
}

// Joins non-empty elements of the array
function njoin($parts,$separator=' ') {
	if (!empty($parts) && is_array($parts)) {
		foreach ($parts as $key => $value) {
			if (empty($value)) unset($parts[$key]);
		}
		return join($separator,$parts);
	} else return false;
}

function get_person_name($item) {
	if (! is_array($item)) {
		lcm_debug("get_person_name: parameter is not an array.");
		return '';
	}

	if (! isset($item['name_middle']))
		$item['name_middle'] = ''; // because of PHP warnings

	$format = read_meta('person_name_format');

	if (! $format)
		$format = '1';

	switch ($format) {
		case '10':
			return njoin(array($item['name_last'] . ',', $item['name_first'], $item['name_middle']));
			break;
		case '1':
		default:
			return njoin(array($item['name_first'], $item['name_middle'], $item['name_last']));
	}
}

function get_person_initials($item, $with_abbver = true) {
	if (! is_array($item)) {
		lcm_debug("get_person_name: parameter is not an array.");
		return '';
	}

	$ret = "";

	if (function_exists("mb_substr")) {
		$ret .= mb_substr($item['name_first'], 0, 1, "utf-8");
		$ret .= mb_substr($item['name_middle'], 0, 1, "utf-8");
		$ret .= mb_substr($item['name_last'], 0, 1, "utf-8");
	} else {
		// [ML] Works with Cyrillic (Bulgarian), but not Chinese :-)
		$ret  = substr($item['name_first'],0,floor(ord($item['name_first']) / 128) + 1);
		$ret .= substr($item['name_middle'],0,floor(ord($item['name_first']) / 128) + 1);
		$ret .= substr($item['name_last'],0,floor(ord($item['name_first']) / 128) + 1);
	}

	if ($with_abbver)
		$ret = '<abbr title="' . get_person_name($item) . '">' . $ret . '</abbr>';

	return $ret;
}

function lcm_unserialize($string) {
	$tmp = unserialize((get_magic_quotes_runtime() ? stripslashes($string) : $string));
	return $tmp;
}

function get_fu_description($item, $make_short = true) {
	if (! is_array($item)) {
		lcm_debug("get_fu_description: parameter is not an array.");
		return '';
	}

	global $prefs;
	global $fu_desc_len; // configure via my_options.php with $GLOBALS['fu_desc_len'] = NNN;

	$short_description = '';

	// Set the length of short followup title (was: wide = 48, narrow = 115)
	$title_length = ((isset($fu_desc_len) && $fu_desc_len > 0) ? $fu_desc_len : 256);

	if ($item['type'] == 'assignment' && is_numeric($item['description'])) {
		$res1 = lcm_query("SELECT * FROM lcm_author WHERE id_author = " . $item['description']);
		$author1 = lcm_fetch_array($res1);
		$short_description = _T('case_info_author_assigned', array('name' => get_person_name($author1)));
	} elseif ($item['type'] == 'unassignment' && is_numeric($item['description'])) {
		$res1 = lcm_query("SELECT * FROM lcm_author WHERE id_author = " . $item['description']);
		$author1 = lcm_fetch_array($res1);
		$short_description = _T('case_info_author_unassigned', array('name' => get_person_name($author1)));
	} elseif ($item['type'] == 'stage_change' || is_status_change($item['type'])) {
		$tmp = lcm_unserialize($item['description']);

		// for backward compatibility, make it optional
		if ($item['case_stage'])
			$short_description = _Tkw('stage', $item['case_stage']);

		if ($tmp['description'])
			$short_description .= " / " . $tmp['description'];

		if ($tmp['result'] || $tmp['conclusion'])
			$short_description .= "\n" . _Ti('fu_input_conclusion');

		if ($tmp['result'])
			$short_description .= _Tkw('_crimresults', $tmp['result']) . "/";

		if ($tmp['conclusion'])
			$short_description .= _Tkw('conclusion', $tmp['conclusion']);

		if ($tmp['sentence'])
			$short_description .= "\n" . _Ti('fu_input_sentence')
				. _Tkw('sentence', $tmp['sentence'], array('currency' => read_meta('currency')));

		if ($tmp['sentence_val'])
			$short_description .= ": " . $tmp['sentence_val'];
	} else {
		if ($item['description']) {
			if ((! $make_short) || strlen(lcm_utf8_decode($item['description'])) < $title_length) 
				$short_description = $item['description'];
			else
				$short_description = substr($item['description'], 0, $title_length) . '...';

			$short_description = clean_output($short_description);
		} else {
			$short_description = _T('fu_info_emptydesc');
		}
	}

	$short_description = nl2br($short_description);

	if (empty($short_description))
		$short_description = _T('info_not_available');

	return $short_description;
}

function show_case_id($cid) {
	if (isset($GLOBALS['show_case_id_function'])) {
		$f = 'show_case_id_' . $GLOBALS['show_case_id_function'];
		return $f($cid);
	}

	// else, TODO, search keywords which have "show in case id string" option
	// this is not implemented at all.
	return $cid;
}

// Dirty hack: utf8_decode is mainly used for strlen(),
// so if it is not installed, it's not such a big problem.
// Use with care!
function lcm_utf8_decode($string) {
	if (function_exists("utf8_decode"))
		return utf8_decode($string);
	else
		return $string;
}

// [ML] This is Spip's "supprimer_numero"
function remove_number_prefix($string) {
	$string = preg_replace("/^[[:space:]]*[0-9]+[.)".chr(176)."][[:space:]]+/", "", $string);
	return $string; 
}

function recup_date($numdate) {
	if (! $numdate) return array('', '', '');

	if (preg_match('/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{1,2})/', $numdate, $regs)) {
		$day = $regs[1];
		$month = $regs[2];
		$year = $regs[3];

		if ($year < 90){
			$year = 2000 + $year;
		} else {
			$year = 1900 + $year ;
		}
	} elseif (preg_match('/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/',$numdate, $regs)) {
		$year = $regs[1];
		$month = $regs[2];
		$day = $regs[3];
	} elseif (preg_match('/([0-9]{4})-([0-9]{2})/', $numdate, $regs)){
		$year = $regs[1];
		$month = $regs[2];
	}

	if ($year > 4000)
		$year -= 9000;

	if (substr($day, 0, 1) == '0')
		$day = substr($day, 1);

	return array($year, $month, $day);
}

function recup_time($numdate) {
	if (!$numdate) return '';

	if (preg_match('/([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/', $numdate, $regs)) {
		$hours = $regs[1];
		$minutes = $regs[2];
		$seconds = $regs[3];
	}
	return array($hours, $minutes, $seconds);
}

/*
 * get_datetime_from_array:
 * $source = array
 * $prefix = 'foo_year', for example (in html widgets)
 * $type = 'start' or 'end' (so that dates fallback on start or end of month, if no value entered)
 * $fallback = if no date entered at all, it will return $fallback
 * $soften_errors = if one field is missing (ex: day or month), it will
 *   fallback on the 1st or last unit. This is good for filters, especially
 *   in the reports functions, but not recommended in follow-ups or apps.
 */
function get_datetime_from_array($source, $prefix, $type = 'start', $fallback = '', $soften_errors = true) {
	$has_date = false;

	$day = $month = $year = '';
	$hour = $min = $sec = '';

	if ($prefix)
		$prefix = $prefix . '_';

	if (isset($source[$prefix . 'year']) && is_numeric(trim($source[$prefix .  'year']))) {
		$year = sprintf("%04d", trim($source[$prefix . 'year']));
		$has_date = true;
	}

	if (! $year)
		$year = '0000';

	if (isset($source[$prefix . 'month']) && is_numeric(trim($source[$prefix . 'month']))) {
		$month = sprintf("%02d", trim($source[$prefix . 'month']));
		$has_date = true;
	}

	if ($month < 0) $month = 0;
	if ($month > 12) $month = 12;

	if (! $month)
		$month = ($type == 'start' ? '01' : '12');
	
	// [ML] Too much fool-proof, show errors, if any
	if (isset($source[$prefix . 'day']) && is_numeric(trim($source[$prefix . 'day']))) {
		$day = sprintf("%02d", trim($source[$prefix . 'day']));
		$has_date = true;
	} elseif ($soften_errors) {
		$day = ($type == 'start' ? '01' : '31');
	} else {
		// [ML] This use to return $source[$prefix . 'day'] but that's
		// nonsense, since it would be unset (tested above)
		return $fallback;
	}

	if ($day > 31) $day = 31;
	if ($day < 0) $day = 0;

	// Try to eliminate days, such as Feb 31 2006, but don't loop indefinately
	// if the year is invalid
	while ($year != '0000' && $day > 28 && (! checkdate($month, $day, $year)))
		$day--;

	if (isset($source[$prefix . 'hour']) && is_numeric(trim($source[$prefix . 'hour']))) {
		$hour = sprintf("%02d", trim($source[$prefix . 'hour']));
		$has_date = true;
	} else {
		$hour = ($type == 'start' ? '00' : '23');
	}

	if (isset($source[$prefix . 'minutes']) && is_numeric(trim($source[$prefix . 'minutes']))) {
		$min = sprintf("%02d", trim($source[$prefix . 'minutes']));
		$has_date = true;
	} else {
		$min = ($type == 'start' ? '00' : '59');
	}
	
	if (isset($source[$prefix . 'seconds']) && is_numeric(trim($source[$prefix . 'seconds']))) {
		$sec = sprintf("%02d", trim($source[$prefix . 'seconds']));
		$has_date = true;
	} else {
		$sec = ($type == 'start' ? '00' : '59');
	}

	if ($has_date)
		return "$year-$month-$day $hour:$min:$sec";
	
	if ($fallback) 
		return $fallback;
	
	// Return empty date (0000-01-01 00:00:00 or 0000-01-01 23:59:59)
	return "$year-$month-$day $hour:$min:$sec";
}

function isset_datetime_from_array($source, $prefix, $check = 'year_only') {
	if ($prefix)
		$prefix = $prefix . '_';

	if (! is_numeric($source[$prefix . 'year']))
		return false;

	if ($check == 'year_only')
		return true;

	if (! is_numeric($source[$prefix . 'month'])) 
		return false;
	
	if (! is_numeric($source[$prefix . 'day']))
		return false;

	if ($check == 'date_only') 
		return true;

	if (! is_numeric($source[$prefix . 'hour']))
		return false;

	if (! is_numeric($source[$prefix . 'minutes']))
		return false;

	if ($check != 'with_seconds')
		return true;
	
	// For fanatics.. :-)
	if (! is_numeric($source[$prefix . 'seconds']))
		return false;

	return true;
}

// This is the opposite of the above function: it makes sure that the date is
// completely empty (ex: for new followup, "end date" may be empty, but must be
// completely empty
function isempty_datetime_from_array($source, $prefix, $check = 'year_only') {
	if ($prefix)
		$prefix = $prefix . '_';

	if (is_numeric($source[$prefix . 'year']))
		return false;

	if ($check == 'year_only')
		return true;

	if (is_numeric($source[$prefix . 'month'])) 
		return false;
	
	if (is_numeric($source[$prefix . 'day']))
		return false;

	if ($check == 'date_only') 
		return true;

	if (is_numeric($source[$prefix . 'hour']))
		return false;

	if (is_numeric($source[$prefix . 'minutes']))
		return false;

	if ($check != 'with_seconds')
		return true;
	
	// For fanatics.. :-)
	if (is_numeric($source[$prefix . 'seconds']))
		return false;

	return true;
}


function checkdate_sql($date) {
	$tmp = recup_date($date);
	return checkdate($tmp[1], $tmp[2], $tmp[0]);
}

/* ********************************************************
 * DEPRECATED: The following functions will be removed soon
 * ******************************************************** */

// Echappement des entites HTML avec correction des entites "brutes"
// (generees par les butineurs lorsqu'on rentre des caracteres n'appartenant
// pas au charset de la page [iso-8859-1 par defaut])
function corriger_entites_html($texte) {
	return preg_replace('/&amp;(#[0-9]+;)/', '&\1', $texte);
}
// idem mais corriger aussi les &amp;eacute; en &eacute; (etait pour backends, mais n'est plus utilisee)
function corriger_toutes_entites_html($texte) {
	return preg_replace('/&amp;(#?[a-z0-9]+;)/i', '&\1', $texte);
}

function entites_html($texte) {
	return corriger_entites_html(htmlspecialchars($texte));
}

// Transformer les &eacute; dans le charset local
function filtrer_entites($texte) {
	include_lcm('inc_charsets');
	// filtrer
	$texte = html2unicode($texte);
	// remettre le tout dans le charset cible
	return unicode2charset($texte);
}

// Tout mettre en entites pour l'export backend (sauf iso-8859-1)
function entites_unicode($texte) {
	include_lcm('inc_charsets');
	return charset2unicode($texte);
}

// Nettoyer les backend
function texte_backend($texte) {

	// " -> &quot; et tout ce genre de choses
	$texte = str_replace("&nbsp;", " ", $texte);
	$texte = entites_html($texte);

	// verifier le charset
	$texte = entites_unicode($texte);

	// Caracteres problematiques en iso-latin 1
	if (read_meta('charset') == 'iso-8859-1') {
		$texte = str_replace(chr(156), '&#156;', $texte);
		$texte = str_replace(chr(140), '&#140;', $texte);
		$texte = str_replace(chr(159), '&#159;', $texte);
	}

	// nettoyer l'apostrophe curly qui semble poser probleme a certains rss-readers
	$texte = str_replace("&#8217;","'",$texte);

	return $texte;
}

// Suppression basique et brutale de tous les <...>
function supprimer_tags($texte, $rempl = "") {
	// super gavant : la regexp ci-dessous plante sous php3, genre boucle infinie !
	// $texte = preg_replace("/<([^>\"']*|\"[^\"]*\"|'[^']*')*>/", $rempl, $texte);
	$texte = preg_replace("/<[^>]*>/", $rempl, $texte);
	return $texte;
}

// Convertit les <...> en la version lisible en HTML
function echapper_tags($texte, $rempl = "") {
	$texte = preg_replace("/<([^>]*)>/", "&lt;\\1&gt;", $texte);
	return $texte;
}

// Convertit un texte HTML en texte brut
function textebrut($texte) {
	$texte = preg_replace("/[\n\r]+/", " ", $texte);
	$texte = preg_replace("/<(p|br)([[:space:]][^>]*)?/i".">", "\n\n", $texte);
	$texte = preg_replace("/^\n+/", "", $texte);
	$texte = preg_replace("/\n+$/", "", $texte);
	$texte = preg_replace("/\n +/", "\n", $texte);
	$texte = supprimer_tags($texte);
	$texte = preg_replace("/(&nbsp;| )+/", " ", $texte);
	// nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
	$texte = str_replace("&#8217;","'",$texte);
	return $texte;
}

// Fabrique une balise A, avec un href conforme au validateur W3C
// attention au cas ou la href est du Javascript avec des "'"

function http_href($href, $clic, $title='', $style='', $class='', $evt='') {
	return '<a href="' .
		str_replace('&', '&amp;', $href) .
		'"' .
		(!$title ? '' : (" title=\"" . supprimer_tags($title)."\"")) .
		(!$style ? '' : (" style=\"" . $style . "\"")) .
		(!$class ? '' : (" class=\"" . $class . "\"")) .
		($evt ? " $evt" : '') .
		'>' .
		$clic .
		'</a>';
}

// Produces an IMG tag with an 'alt' field and optional title + extra attributes
// Warning: htmlentities and translation must be applied first.
function http_img_pack($img, $alt = '', $att = '', $title = '') {
	return '<img border="0" align="middle" src="images/spip/' . $img . '" '
		. 'alt="' . ($alt ? $alt : '') . '" '
		. ($title ? " title=\"$title\"" : '')
		. $att . " />";
}

function http_href_img($href, $img, $att, $title='', $style='', $class='', $evt='') {
	return  http_href($href, http_img_pack($img, $title, $att), $title, $style, $class, $evt);
}

// Corrects annoying characters caused by various Windows messy apps
function corriger_caracteres($texte) {
	static $trans;
	if (!$trans) {
		// 145,146,180 = simple quote ; 147,148 = double quote ; 150,151 = tiret long
		$trans['iso-8859-1'] = array(
			chr(146) => "'",
			chr(180) => "'",
			chr(147) => '&#8220;',
			chr(148) => '&#8221;',
			chr(150) => '-',
			chr(151) => '-',
			chr(133) => '...'
		);
		$trans['utf-8'] = array(
			chr(194).chr(146) => "'",
			chr(194).chr(180) => "'",
			chr(194).chr(147) => '&#8220;',
			chr(194).chr(148) => '&#8221;',
			chr(194).chr(150) => '-',
			chr(194).chr(151) => '-',
			chr(194).chr(133) => '...'
		);
	}
	$charset = read_meta('charset');
	if (!$trans[$charset]) return $texte;
	if ($GLOBALS['flag_strtr2']) return strtr($texte, $trans[$charset]);
	reset($trans[$charset]);
	while (list($from, $to) = each($trans[$charset])) 
		$texte = str_replace($from, $to, $texte);
	return $texte;
}


// "127.4 kb" or "3.1 Mb"
function size_in_bytes ($mysize) {
	if (! is_numeric($mysize))
		return -1; // soft error
	
	if ($mysize < 1024) {
		$sizetxt = format_money($mysize, false); // I know, bad name..
		$mysize = _T('file_size_info_bytes', array('size' => $sizetxt));
	} else if ($mysize < 1024 * 1024) {
		$sizetxt = format_money((floor($mysize / 102.4))/10, false);
		$mysize = _T('file_size_info_kbytes', array('size' => $sizetxt));
	} else if ($mysize < 1024 * 1024 * 1024) {
		$sizetxt = format_money((floor(($mysize / 1024) / 102.4))/10, false);
		$mysize = _T('file_size_info_mbytes', array('size' => $sizetxt));
	} else {
		$sizetxt = format_money((floor(($mysize / 1024) / 1024 / 102.4))/10, false);
		$mysize = _T('file_size_info_gbytes', array('size' => $sizetxt));
	}

	return $mysize;
}

function filesize_in_bytes($file) {
	$size = filesize($file);
	return size_in_bytes($size);
}

// Transforme n'importe quel champ en une chaine utilisable
// en PHP ou Javascript en toute securite
// < ? php $x = '[(#TEXTE|texte_script)]'; ? >
function texte_script($texte) {
	$texte = str_replace('\\', '\\\\', $texte);
	$texte = str_replace('\'', '\\\'', $texte);
	return $texte;
}


// Rend une chaine utilisable sans dommage comme attribut HTML
function attribut_html($texte) {
	$texte = preg_replace('/"/', '&quot;', supprimer_tags($texte));
	return $texte;
}

// Vider les url nulles comme 'http://' ou 'mailto:'
function vider_url($url) {
	if (preg_match("/^(http:?\/?\/?|mailto:?)$/i", trim($url)))
		return false;
	else
		return $url;
}

// Maquiller une adresse e-mail
function antispam($texte) {
	include_ecrire ("inc_acces.php3");
	$masque = creer_pass_aleatoire(3);
	return preg_replace("/@/", " $masque ", $texte);
}


//
// Date, heure, saisons
//

function normaliser_date($date) {
	if ($date) {
		$date = vider_date($date);
		if (preg_match("/^[0-9]{8,10}$/", $date))
			$date = date("Y-m-d H:i:s", $date);
		if (preg_match("/^([12][0-9]{3})([-\/]00)?( [-0-9:]+)?$/", $date, $regs))
			$date = $regs[1]."-01-01".$regs[3];
		else if (preg_match("/^([12][0-9]{3}[-\/][01]?[0-9])([-\/]00)?( [-0-9:]+)?$/", $date, $regs))
			$date = preg_replace("/\//","-",$regs[1])."-01".$regs[3];
		else if ($GLOBALS['flag_strtotime']) {
			$date = date("Y-m-d H:i:s", strtotime($date));
		}
		else $date = preg_replace('/[^-0-9\/: ]/', '', $date);
	}
	return $date;
}

function vider_date($letexte) {
	if (preg_match("/^0000-00-00/", $letexte)) return;
	if (preg_match("/^1970-01-01/", $letexte)) return;	// eviter le bug GMT-1
	return $letexte;
}

function heures($numdate) {
	$date_array = recup_time($numdate);
	if ($date_array)
		list($heures, $minutes, $secondes) = $date_array;
	return $heures;
}

function minutes($numdate) {
	$date_array = recup_time($numdate);
	if ($date_array)
		list($heures, $minutes, $secondes) = $date_array;
	return $minutes;
}

function secondes($numdate) {
	$date_array = recup_time($numdate);
	if ($date_array)
		list($heures,$minutes,$secondes) = $date_array;
	return $secondes;
}

function heures_minutes($numdate) {
	return _T('date_fmt_heures_minutes', array('h'=> heures($numdate), 'm'=> minutes($numdate)));
}


function affdate_base($numdate, $vue) {
	global $lcm_lang;
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee, $mois, $jour) = $date_array;
	else
		return '';

	// 1er, 21st, etc.
	$journum = $jour;

	if ($jour == 0)
		$jour = '';
	else if ($jourth = _T('date_day_'.$jour))
			$jour = $jourth;

	$mois = intval($mois);
	if ($mois > 0 AND $mois < 13) {
		$nommois = _T('date_month_'.$mois);
		if ($jour)
//			$jourmois = _T('date_de_mois_'.$mois, array('j'=>$jour, 'nommois'=>$nommois));	// TRAD
			$jourmois = $jour . ' ' . $nommois;
	}

	if ($annee < 0) {
		$annee = -$annee." "._T('date_avant_jc');
		$avjc = true;
	}
	else $avjc = false;

	switch ($vue) {
	case 'saison':
		if ($mois > 0){
			$saison = 1;
			if (($mois == 3 AND $jour >= 21) OR $mois > 3) $saison = 2;
			if (($mois == 6 AND $jour >= 21) OR $mois > 6) $saison = 3;
			if (($mois == 9 AND $jour >= 21) OR $mois > 9) $saison = 4;
			if (($mois == 12 AND $jour >= 21) OR $mois > 12) $saison = 1;
		}
		return _T('date_saison_'.$saison);	// TRAD

	case 'court':
		if ($avjc) return $annee;
		$a = date('Y');
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
//		if ($annee != $a) return _T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>ucfirst($nommois), 'annee'=>$annee));	// TRAD
//		return _T('date_fmt_jour_mois', array('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee)); // TRAD
		if ($annee != $a) return $mois . ' ' . $annee;
		return $jourmois;

	case 'jourcourt':
		if ($avjc) return $annee;
		$a = date('Y');
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
//		if ($annee != $a) return _T('date_fmt_jour_mois_annee', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
		if ($annee != $a) return $jourmois . ' ' . $annee;
//		return _T('date_fmt_jour_mois', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
		return $jourmois;

	case 'entier':
		if ($avjc) return $annee;
		if ($jour) {
//			return _T('date_fmt_jour_mois_annee', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
			return $jourmois . ' ' . $annee;
		} else {
//			return _T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
			return $mois . ' ' . $annee;
		}

	case 'nom_mois':
		return $nommois;

	case 'mois':
		return sprintf("%02s",$mois);

	case 'jour':
		return $jour;

	case 'journum':
		return $journum;

	case 'nom_jour':
		if (!$mois OR !$jour) return '';
		$nom = mktime(1,1,1,$mois,$jour,$annee);
//		$nom = 1+date('w',$nom);
		$nom = date('w',$nom);
		return _T('date_wday_'.$nom);

	case 'mois_annee':
		if ($avjc) return $annee;
		// return trim(_T('date_fmt_mois_annee', array('mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee)));
		return $nommois . ' ' . $annee;

	case 'annee':
		return $annee;
	}

	return "<blink>"._T('info_format_non_defini')."</blink>";
}

function nom_jour($numdate) {
	return affdate_base($numdate, 'nom_jour');
}

function jour($numdate) {
	return affdate_base($numdate, 'jour');
}

function journum($numdate) {
	return affdate_base($numdate, 'journum');
}

function mois($numdate) {
	return affdate_base($numdate, 'mois');
}

function nom_mois($numdate) {
	return affdate_base($numdate, 'nom_mois');
}

// year
function annee($numdate) {
	return affdate_base($numdate, 'annee');
}

function year($numdate) {
	return affdate_base($numdate, 'annee');
}

function saison($numdate) {
	return affdate_base($numdate, 'saison');
}

function affdate($numdate) {
	return affdate_base($numdate, 'entier');
}

function affdate_court($numdate) {
	return affdate_base($numdate, 'court');
}

function affdate_jourcourt($numdate) {
	return affdate_base($numdate, 'jourcourt');
}

function affdate_mois_annee($numdate) {
	return affdate_base($numdate, 'mois_annee');
}

function affdate_heure($numdate) {
	return _T('date_fmt_jour_heure', array('jour' => affdate($numdate), 'heure' => heures_minutes($numdate)));
}


//
// Alignements en HTML
//

function aligner($letexte,$justif) {
	$letexte = preg_replace("/<p([^>]*)/i", "<p\\1 align='$justif'", trim($letexte));
	if ($letexte AND !preg_match("/^[[:space:]]*<p/", $letexte)) {
		$letexte = "<p class='spip' align='$justif'>" . $letexte . "</p>";
	}
	return $letexte;
}

//
// Export iCal
//

function filtrer_ical($texte) {
	include_lcm('inc_charsets');
	$texte = html2unicode($texte);
	$texte = unicode2charset(charset2unicode($texte, read_meta('charset'), 1), 'utf-8');
	$texte = preg_replace("/\n/", " ", $texte);
	$texte = preg_replace("/,/", "\,", $texte);

	return $texte;
}

function date_ical($date_heure, $minutes = 0) {
	return date("Ymd\THis", mktime(heures($date_heure),minutes($date_heure)+$minutes,0,mois($date_heure),jour($date_heure),annee($date_heure)));
}

function date_iso($date_heure) {
	list($annee, $mois, $jour) = recup_date($date_heure);
	list($heures, $minutes, $secondes) = recup_time($date_heure);
	$time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
	return gmdate("Y-m-d\TH:i:s\Z", $time);
}


//
// Recuperation de donnees dans le champ extra
// Ce filtre n'a de sens qu'avec la balise #EXTRA
//
function extra($letexte, $champ) {
	$champs = unserialize($letexte);
	return $champs[$champ];
}

// postautobr : transforme les sauts de ligne en _
function post_autobr($texte, $delim="\n_ ") {
	$texte = str_replace("\r\n", "\r", $texte);
	$texte = str_replace("\r", "\n", $texte);
	list($texte, $les_echap) = echappe_html($texte, "POSTAUTOBR", true);

	$debut = '';
	$suite = $texte;
	while ($t = strpos('-'.$suite, "\n", 1)) {
		$debut .= substr($suite, 0, $t-1);
		$suite = substr($suite, $t);
		$car = substr($suite, 0, 1);
		if (($car<>'-') AND ($car<>'_') AND ($car<>"\n") AND ($car<>"|"))
			$debut .= $delim;
		else
			$debut .= "\n";
		if (preg_match("/^\n+/", $suite, $regs)) {
			$debut.=$regs[0];
			$suite = substr($suite, strlen($regs[0]));
		}
	}
	$texte = $debut.$suite;

	$texte = echappe_retour($texte, $les_echap, "POSTAUTOBR");
	return $texte;
}


//
// Gestion des blocs multilingues
//

// renvoie la traduction d'un bloc multi dans la langue demandee
function multi_trad ($lang, $trads) {
	// si la traduction existe, genial
	if (isset($trads[$lang])) {
		$retour = $trads[$lang];

	}	// cas des langues xx_yy
	else if (preg_match('/^([a-z]+)_/', $lang, $regs) AND isset($trads[$regs[1]])) {
		$retour = $trads[$regs[1]];

	}	// sinon, renvoyer la premiere du tableau
		// remarque : on pourrait aussi appeler un service de traduction externe
		// ou permettre de choisir une langue "plus proche",
		// par exemple le francais pour l'espagnol, l'anglais pour l'allemand, etc.
	else {
		list (,$trad) = each($trads);
		$retour = $trad;
	}


	// dans l'espace prive, mettre un popup multi
	if ($GLOBALS['flag_ecrire']) {
		$retour = ajoute_popup_multi($lang, $trads, $retour);
	}

	return $retour;
}

// analyse un bloc multi
function extraire_trad ($langue_demandee, $bloc) {
	$lang = '';

	while (preg_match("/^(.*?)\[([a-z_]+)\]/si", $bloc, $regs)) {
		$texte = trim($regs[1]);
		if ($texte OR $lang)
			$trads[$lang] = $texte;
		$bloc = substr($bloc, strlen($regs[0]));
		$lang = $regs[2];
	}
	$trads[$lang] = $bloc;

	// faire la traduction avec ces donnees
	return multi_trad($langue_demandee, $trads);
}

// repere les blocs multi dans un texte et extrait le bon
function extraire_multi ($letexte) {
	global $flag_pcre;

	if (!strpos('-'.$letexte, '<multi>')) return $letexte; // perf
	if ($flag_pcre AND preg_match_all("@<multi>(.*?)</multi>@s", $letexte, $regs, PREG_SET_ORDER)) {
		while (list(,$reg) = each ($regs)) {
			$letexte = str_replace($reg[0], extraire_trad($GLOBALS['lcm_lang'], $reg[1]), $letexte);
		}
	}
	return $letexte;
}

// popup des blocs multi dans l'espace prive (a ameliorer)
function ajoute_popup_multi($langue_demandee, $trads, $texte) {
	static $num_multi=0;
	global $multi_popup;
	while (list($lang,$bloc) = each($trads)) {
		if ($lang != $langue_demandee)
			$survol .= "[$lang] ".supprimer_tags(couper($bloc,20))."\n";
		$texte_popup .= "<br /><b>".translate_language_name($lang)."</b> ".preg_replace("/\n+/","<br />", supprimer_tags(couper(propre($bloc),200)));
	}

	if ($survol) {
		$num_multi ++;
		$texte .= " <img src=\"img_pack/langues-modif-12.gif\" alt=\"(multi)\" title=\"$survol\" height=\"12\" width=\"12\" border=\"0\" onclick=\"return openmulti($num_multi)\" />";
		$multi_popup .= "textes_multi[$num_multi] = '".addslashes($texte_popup)."';\n";
	}

	return $texte;
}

function quote_amp ($text) {
	// avoids cases "&amp; & me"
	$text = str_replace("&amp;", "&", $text);
	$text = str_replace("&", "&amp;", $text);
	return $text;
}


?>
