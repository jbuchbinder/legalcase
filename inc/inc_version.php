<?php

/*
	This file is part of the Legal Case Management System (LCM).
	(C) 2004-2006 Free Software Foundation, Inc.

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

	$Id: inc_version.php,v 1.114 2008/04/07 19:12:02 mlutfy Exp $
*/

// Execute this file only once
if (defined('_INC_VERSION')) return;
define('_INC_VERSION', '1');


//
// Management of inclusion and information on directories
//

$included_files = array();

function include_lcm($file) {
	$lcmfile = 'inc/' . $file . '.php';

	// This does not work correctly on PHP5, and who knows for PHP4..
	if (! isset($GLOBALS['included_files'][$file]))
		@$GLOBALS['included_files'][$file] = 0;
	
	if (@$GLOBALS['included_files'][$file]++)
		return;

	if (! @file_exists($lcmfile))
		lcm_panic("File for include_lcm does not exist: $lcmfile");

	lcm_debug("include_lcm: (start) $lcmfile", 5);
	include($lcmfile);
	lcm_debug("include_lcm: (ready) $lcmfile", 5);
}

function include_config_exists($file) {
	$lcmfile = $file . '.php';

	if (isset($_SERVER['LcmConfigDir']))
		$lcmfile = $_SERVER['LcmConfigDir'] . '/' . $lcmfile;
	else
		$lcmfile = 'inc/config/' . $lcmfile;

	return @file_exists($lcmfile);
}

function include_config($file) {
	$lcmfile = $file . '.php';

	if (isset($_SERVER['LcmConfigDir']))
		$lcmfile = $_SERVER['LcmConfigDir'] . '/' . $lcmfile;
	else
		$lcmfile = 'inc/config/' . $lcmfile;

	if (array_key_exists($lcmfile, $GLOBALS['included_files']))
		return;

	if (! @file_exists($lcmfile)) {
		lcm_log("CRITICAL: file for include_config does not exist: " . $lcmfile);
		if ($GLOBALS['debug']) echo lcm_getbacktrace();
	}
	
	lcm_debug("include_config: (start) $lcmfile", 5);
	include($lcmfile);
	$GLOBALS['included_files'][$lcmfile] = 1;
	lcm_debug("include_config: (ready) $lcmfile", 5);
}

function include_data_exists($file) {
	$lcmfile = $file . '.php';

	if (isset($_SERVER['LcmDataDir']))
		$lcmfile = $_SERVER['LcmDataDir'] . '/' . $lcmfile;
	else
		$lcmfile = 'inc/data/' . $lcmfile;

	return @file_exists($lcmfile);
}

function include_data($file) {
	$lcmfile = $file . '.php';

	if (isset($_SERVER['LcmDataDir']))
		$lcmfile = $_SERVER['LcmDataDir'] . '/' . $lcmfile;
	else
		$lcmfile = 'inc/data/' . $lcmfile;

	if (array_key_exists($lcmfile, $GLOBALS['included_files']))
		return;

	if (! @file_exists($lcmfile)) {
		lcm_log("CRITICAL: file for include_data does not exist: " . $lcmfile);
		if ($GLOBALS['debug']) echo lcm_getbacktrace();
	}
	
	lcm_debug("include_data: (start) $lcmfile", 5);
	include($lcmfile);
	$GLOBALS['included_files'][$lcmfile] = 1;
	lcm_debug("include_data: (ready) $lcmfile", 5);
}

function include_validator_exists($file) {
	$lcmfile = 'inc/config/custom/validation/validate_' . $file . '.php';
	return @file_exists($lcmfile);
}

function include_validator($file) {
	$lcmfile = 'inc/config/custom/validation/validate_' . $file . '.php';

	// This does not work correctly on PHP5, and who knows for PHP4..
	if (! isset($GLOBALS['included_files'][$file]))
		@$GLOBALS['included_files'][$file] = 0;
	
	if (@$GLOBALS['included_files'][$file]++)
		return;

	if (! @file_exists($lcmfile))
		lcm_panic("File for include_lcm does not exist: $lcmfile");

	lcm_debug("include_validator: (start) $lcmfile", 5);
	include($lcmfile);
	lcm_debug("include_validator: (ready) $lcmfile", 5);
}

function include_custom_report_exists($file) {
	$lcmfile = 'inc/config/custom/reports/' . $file . '.php';
	return @file_exists($lcmfile);
}

function include_custom_report($file) {
	$lcmfile = 'inc/config/custom/reports/' . $file . '.php';

	// This does not work correctly on PHP5, and who knows for PHP4..
	if (! isset($GLOBALS['included_files'][$file]))
		@$GLOBALS['included_files'][$file] = 0;
	
	if (@$GLOBALS['included_files'][$file]++)
		return;

	if (! @file_exists($lcmfile))
		lcm_panic("File for include_lcm does not exist: $lcmfile");

	lcm_debug("include_validator: (start) $lcmfile", 5);
	include($lcmfile);
	lcm_debug("include_validator: (ready) $lcmfile", 5);
}


//  ************************************
// 	*** Default configuration of LCM ***
//
// The following parameters can be overriden via inc/my_options.php.
//

// Default timezone for PHP >= 5.1
// c.f. http://www.php.net/manual/en/ref.datetime.php#ini.date.timezone
if (function_exists("date_default_timezone_get")) {
	if (! ($tz = date_default_timezone_get())) {
		lcm_log("PHP variable date.timezone not set. Falling back on UCT");
		lcm_log("For more info, see http://www.php.net/manual/en/ref.datetime.php#ini.date.timezone");

		date_default_timezone_set("UCT");
	} else {
		if (! date_default_timezone_set($tz)) {
			lcm_log("Problem setting tz = $tz, falling back on UCT");
			lcm_log("For more info, see http://www.php.net/manual/en/ref.datetime.php#ini.date.timezone");
			date_default_timezone_set("UCT");
		}
	}
}

// Prefix of tables in the database
// (to modify in order to have many LCM running in the same database)
$table_prefix = 'lcm';

// Prefix and path of cookies
// (to modify in order to have many LCM running in sub-directories)
$cookie_prefix = 'lcm';
$cookie_path = '';

// Maximum size of uploaded files
// NOTE: also check your php.ini, very often, it is less than this.
$max_file_upload_size = 10 * 1024 * 1024; // 10 megs

// [ML] This is probably not used
// Should we authorize LCM to compress the pages on the fly when
// the navigator accepts it (Apache 1.3 only) ?
$auto_compress = true;

// [ML] This will probably not be used
// creation of thumbnails with ImageMagick on the command line: put the
// complete path '/bin/convert' (Linux) or '/sw/bin/convert' (fink/Mac OS X)
// Note : better to use GD2 or the php module imagick if they are available
$convert_command = 'convert';

// Should we debug in data/lcm.log ?
$debug = 0; // 0 = No debug, see lcm_debug function for more info

// Shoud we highlight translation strings? (helps to find non-translated strings)
$debug_tr = false;

// Should SQL queries run in debug mode?
$sql_debug = true;

// Should SQL queries be profiled (chronometer) ?
$sql_profile = false;

// Should we make full connection requests including the server name and
// the database name? (useful if you plan adding extentions which make
// requests to other SQL databases)
$mysql_recall_link = false;

// Shoud non-translated strings be shown in red?
$test_i18n = false;


//
// *** End of configuration ***
//

// This allows users to override the defaults
if(include_config_exists('my_options'))
	include_config('my_options');

// Backwards compatibility for LCM <= 0.6.4
if (@file_exists('inc/my_options.php')) {
	lcm_log("File inc/my_options.php deprecated, please move to inc/config/my_options.php");
	include('inc/my_options.php');
}

// Current version of LCM
$lcm_version = 0.730;

// Current version of LCM shown on screen
$lcm_version_shown = "0.7.3 CVS";

// Current version of LCM database
$lcm_db_version = 57;

// Error reporting
# error_reporting(E_ALL); // [ML] recommended for debug
error_reporting(E_ALL ^ E_NOTICE); // PHP default

// ** Security **
$author_session = '';
$connect_status = '';
$hash_recherche = '';
$hash_recherche_strict = '';

//
// PHP version information
// (should be equal or larger to 3.0.8)
//

$php_version = explode('.', phpversion());
$php_version_maj = intval($php_version[0]);
$php_version_med = intval($php_version[1]);
if (preg_match('/([0-9]+)/', $php_version[2], $match)) $php_version_min = intval($match[1]);

$flag_levenshtein = ($php_version_maj >= 4);
$flag_uniqid2 = ($php_version_maj > 3 OR $php_version_min >= 13);
$flag_get_cfg_var = (@get_cfg_var('error_reporting') != "");
$flag_strtr2 = ($php_version_maj > 3);

$flag_ini_get = (function_exists("ini_get")
	&& (@ini_get('max_execution_time') > 0));	// verifier pas desactivee
$flag_gz = function_exists("gzopen");
$flag_ob = ($flag_ini_get
	&& !preg_match("/ob_/", ini_get('disable_functions'))
	&& function_exists("ob_start"));
$flag_obgz = ($flag_ob && function_exists("ob_gzhandler"));
$flag_pcre = function_exists("preg_replace");
$flag_crypt = function_exists("crypt");
$flag_wordwrap = function_exists("wordwrap");
$flag_apc = function_exists("apc_rm");
$flag_sapi_name = function_exists("php_sapi_name");
$flag_utf8_decode = function_exists("utf8_decode");
$flag_ldap = function_exists("ldap_connect");
$flag_flock = function_exists("flock");
$flag_ImageCreateTrueColor = function_exists("ImageCreateTrueColor");
$flag_ImageCopyResampled = function_exists("ImageCopyResampled");
$flag_ImageGif = function_exists("ImageGif");
$flag_ImageJpeg = function_exists("ImageJpeg");
$flag_ImagePng = function_exists("ImagePng");
$flag_imagick = function_exists("imagick_readimage");	// http://pear.sourceforge.net/en/packages.imagick.php
$flag_multibyte = function_exists("mb_encode_mimeheader");
$flag_iconv = function_exists("iconv");
$flag_strtotime = function_exists("strtotime");

$flag_gd = $flag_ImageGif || $flag_ImageJpeg || $flag_ImagePng;


//
// Apply the cookie prefix
//
function lcm_setcookie ($name='', $value='', $expire=0, $path='AUTO', $domain='', $secure='') {
	lcm_log("setcookie here.. name = $name, value = $value");
	$name = preg_replace ('/^lcm/', $GLOBALS['cookie_prefix'], $name);
	if ($path == 'AUTO') $path=$GLOBALS['cookie_path'];

	if ($secure)
		@setcookie ($name, $value, $expire, $path, $domain, $secure);
	else if ($domain)
		@setcookie($name, $value, $expire, $path, $domain);
	else if ($path)
		@setcookie($name, $value, $expire, $path);
	else if ($expire)
		@setcookie($name, $value, $expire);
	else
		@setcookie($name, $value);
}

// XXX TODO Double-check this one day
// Probably doesn't work anymore because we use $_COOKIE
if ($cookie_prefix != 'lcm') {
	reset ($HTTP_COOKIE_VARS);
	while (list($name,$value) = each($HTTP_COOKIE_VARS)) {
		if (preg_match('/^lcm/', $name)) {
			unset($HTTP_COOKIE_VARS[$name]);
			unset($$name);
		}
	}
	reset ($HTTP_COOKIE_VARS);
	while (list($name,$value) = each($HTTP_COOKIE_VARS)) {
		if (preg_match('/^'.$cookie_prefix.'/', $name)) {
			$spipname = preg_replace ('/^'.$cookie_prefix.'/', 'lcm', $name);
			$HTTP_COOKIE_VARS[$spipname] = $value;
			$$spipname = $value;
		}
	}
}


//
// Information about the web hosting
// [ML] alot was removed
//

/* [ML] DEPRECATED ?
$os_server = '';

if (preg_match('/\(Win/i', $_SERVER['SERVER_SOFTWARE']))
	$os_server = 'windows';
*/

// By default, set maximum access rights
// [ML] This will require auditing..
@umask(0);


//
// Information on the current file
//

// For error handling after failed fopen/mkdir/etc
$lcm_errormsg = '';

function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
	$dt = date("Y-m-d H:i:s (T)");

	$errortype = array (
			E_ERROR          => "Error",
			E_WARNING        => "Alert",
			E_PARSE          => "Parse error", // This won't actually get caught here..
			E_NOTICE          => "Note",  // This can really get annoying sometimes
			E_CORE_ERROR      => "Core Error",
			E_CORE_WARNING    => "Core Warning",
			E_COMPILE_ERROR  => "Compile Error",
			E_COMPILE_WARNING => "Compile Warning",
			E_USER_ERROR      => "Specific Error",
			E_USER_WARNING    => "Specific Alert",
			E_USER_NOTICE    => "Specific Note",
			E_STRICT          => "Runtime Notice"
		);

	$log_errors = array(E_USER_ERROR, E_USER_WARNING);

	$err = $dt . ": $filename,$linenum " . $errortype[$errno] . " ($errno) $errmsg\n"
		. lcm_getbacktrace(false); // false = without html


	if (in_array($errno, $log_errors))
		lcm_log($err);
	else {
		// [ML] Annoying errors. We are not limiting LCM to PHP5 syntax for now.
		if (preg_match('/^var: Deprecated. Please use the public\/private\/protected modifiers/', $errmsg))
			return;

		lcm_debug("[dbg] " . $err, 2);
	}

	// set our custom errno, because PHP doesn't seem to have one!
	$GLOBALS['lcm_errormsg'] = $errmsg;
}

$old_error_handler = set_error_handler("userErrorHandler");


$flag_connect = include_config_exists('inc_connect');

function lcm_query($query, $accept_fail = false) {
	include_lcm('inc_db');

	// We silently fail if there is no database, this avoids 
	// many warnings while installation, for example.
	if ($GLOBALS['flag_connect']) {
		include_config('inc_connect');
		if (!$GLOBALS['db_ok'])
			return;
	}

	$GLOBALS['db_query_count']++;

	return lcm_query_db($query, $accept_fail);
}

function spip_query($query) {
	return lcm_query($query);
}

//
// PHP configuration information
//

// cf. list of sapi_name - http://www.php.net/php_sapi_name
$php_module = (($flag_sapi_name AND preg_match("/apache/i", @php_sapi_name())) OR
	preg_match("/^Apache.* PHP/", $SERVER_SOFTWARE));
$php_cgi = ($flag_sapi_name AND preg_match("/cgi/i", @php_sapi_name()));

function http_status($status) {
	global $php_cgi, $REDIRECT_STATUS;

	if ($REDIRECT_STATUS && $REDIRECT_STATUS == $status) return;
	$status_string = array(
		200 => '200 OK',
		304 => '304 Not Modified',
		401 => '401 Unauthorized',
		403 => '403 Forbidden',
		404 => '404 Not Found'
	);
	if ($php_cgi) Header("Status: $status");
	else Header("HTTP/1.0 ".$status_string[$status]);
}

function http_last_modified($lastmodified, $expire = 0) {
	$gmoddate = gmdate("D, d M Y H:i:s", $lastmodified);
	if ($GLOBALS['HTTP_IF_MODIFIED_SINCE']) {
		$if_modified_since = preg_replace('/;.*$/', '', $GLOBALS['HTTP_IF_MODIFIED_SINCE']);
		$if_modified_since = trim(str_replace('GMT', '', $if_modified_since));
		if ($if_modified_since == $gmoddate) {
			http_status(304);
			$headers_only = true;
		}
	}
	@Header ("Last-Modified: ".$gmoddate." GMT");
	if ($expire) 
		@Header ("Expires: ".gmdate("D, d M Y H:i:s", $expire)." GMT");
	return $headers_only;
}

$flag_upload = (!$flag_get_cfg_var || (get_cfg_var('upload_max_filesize') > 0));

function tester_upload() {
	return $GLOBALS['flag_upload'];
}


//
// Setup of buffered output: if possible, generate a compressed output
// to save bandwith
if ($auto_compress && $flag_obgz) {
	$use_gz = true;

	// if a buffer is already open, stop
	if (ob_get_contents())
		$use_gz = false;

	// if the compression is already started, stop
	else if (@ini_get("zlib.output_compression") || @ini_get("output_handler"))
		$use_gz = false;

	/* [ML] HTTP_VIA does not always exist?
	// special proxy bug
	else if (preg_match("/NetCache|Hasd_proxy/i", $HTTP_VIA))
		$use_gz = false;
	*/

	// special bug Netscape Win 4.0x
	else if (preg_match("/Mozilla\/4\.0[^ ].*Win/i", $_SERVER['HTTP_USER_AGENT']))
		$use_gz = false;

	// special bug Apache2x
	else if (preg_match("/Apache(-[^ ]+)?\/2/i", $_SERVER['SERVER_SOFTWARE']))
		$use_gz = false;
	else if ($flag_sapi_name && preg_match("/^apache2/i", @php_sapi_name()))
		$use_gz = false;
	
	if ($use_gz) {
		@ob_start("ob_gzhandler");
	}
	@header("Vary: Cookie, Accept-Encoding");
}
else @header("Vary: Cookie");


class Link {
	var $file;
	var $vars;
	var $arrays;
	var $s_vars;
	var $t_vars, $t_var_idx, $t_var_cnt;

	//
	// Constructor: Create a new URL, optionally with parameters.
	// If no URL is given, the current one is unsed.
	function Link($url = '', $reentrant = false) {
		static $link = '';
		$vars = '';

		// If root link not defined, create it
		if (!$link && !$reentrant) {
			$link = new Link('', true);
		}

		$this->vars = array();
		$this->s_vars = array();
		$this->t_vars = array();
		$this->t_var_idx = array();
		$this->t_var_cnt = 0;

		// Normal case
		if ($link) {
			$this->s_vars = $link->s_vars;
			$this->t_vars = $link->t_vars;
			$this->t_var_idx = $link->t_var_idx;
			$this->t_var_cnt = $link->t_var_cnt;
			if ($url) {
				$v = split('[\?\&]', $url);
				list(, $this->file) = each($v);
				while (list(, $var) = each($v)) {
					list($name, $value) = split('=', $var, 2);
					$name = urldecode($name);
					$value = urldecode($value);
					if (preg_match('/^(.*)\[\]$/', $name, $regs)) {
						$this->arrays[$regs[1]][] = $value;
					}
					else {
						$this->vars[$name] = $value;
					}
				}
			}
			else {
				$this->file = $link->file;
				$this->vars = $link->vars;
				$this->arrays = $link->arrays;
			}
			return;
		}

		// Special case : create root link

		// If no URL specified, take current one
		if (!$url) {
			$url = $_SERVER['REQUEST_URI'];
			$url = substr($url, strrpos($url, '/') + 1);
			if (!$url) $url = "./";
			if (count($_POST))
				$vars = $_POST;
		}

		$v = split('[\?\&]', $url);
		list(, $this->file) = each($v);

		// GET variables are read from the original URL
		// (HTTP_GET_VARS may contain additional variables introduced by rewrite-rules)
		if (!$vars) {
			while (list(, $var) = each($v)) {
				list($name, $value) = split('=', $var, 2);
				$name = urldecode($name);
				$value = urldecode($value);
				if (preg_match('/^(.*)\[\]$/', $name, $regs)) {
					$vars[$regs[1]][] = $value;
				}
				else {
					$vars[$name] = $value;
				}
			}
		}

		if (is_array($vars)) {
			reset($vars);
			while (list($name, $value) = each($vars)) {
				$p = substr($name, 0, 2);
				if ($p == 's_') {
					$this->s_vars[$name] = $value;
				}
				else if ($p == 't_') {
					$this->_addTmpHash($name, $value);
				}
				else {
					if (is_array($value))
						$this->arrays[$name] = $value;
					else
						$this->vars[$name] = $value;
				}
			}
		}
	}

	function _addTmpHash($name, $value) {
		if ($i = $this->t_var_idx[$name]) {
			$this->t_vars[--$i] = $value;
		}
		else {
			$this->t_vars[$this->t_var_cnt] = $value;
			$this->t_var_idx[$name] = ++$this->t_var_cnt;
			if ($this->t_var_cnt >= 5) $this->t_var_cnt = 0;
		}
	}

	//
	// Erase all the variables
	function clearVars() {
		$this->vars = '';
		$this->arrays = '';
	}

	//
	// Erase only one variable
	function delVar($name) {
		if(isset($this->vars[$name]))
			unset($this->vars[$name]);

		if(isset($this->arrays[$name]))
			unset($this->arrays[$name]);
	}

	//
	// Add one variable
	// (if not value is provided, we take the global value of the variable)
	function addVar($name, $value = '__global__') {
		if ($value == '__global__') $value = $GLOBALS[$name];
		if (is_array($value))
			$this->arrays[$name] = $value;
		else
			$this->vars[$name] = $value;
	}

	function getVar($name) {
		if (isset($this->vars[$name]))
			return $this->vars[$name];
		else
			return ""; // XXX
	}

	//
	// Add a session variable
	// (variable whose value is transmitted from one URL to another)
	function addSessionVar($name, $value) {
		$this->addVar('s_'.$name, $value);
	}

	function getSessionVar($name) {
		return $this->vars['s_'.$name];
	}

	//
	// Add a temporary variable
	// (variable whose name is arbitrarely long, and whose value is
	// transmitted from link to link. Limited to 5 variables)
	function addTmpVar($name, $value) {
		$this->_addTmpHash('t_'.substr(md5($name), 0, 4), $value);
	}

	function getTmpVar($name) {
		if ($i = $this->t_var_idx['t_'.substr(md5($name), 0, 4)]) {
			return $this->t_vars[--$i];
		}
	}

	function getAllVars() {
		$vars = array();

		if (is_array($this->t_var_idx)) {
			reset($this->t_var_idx);
			while (list($name, $i) = each($this->t_var_idx))
				$vars[$name] = $this->t_vars[--$i];
		}
		if (is_array($this->vars)) {
			reset($this->vars);
			while (list($name, $value) = each($this->vars)) 
				$vars[$name] = $value;
		}
		if (is_array($this->s_vars)) {
			reset($this->s_vars);
			while (list($name, $value) = each($this->s_vars))
				$vars[$name] = $value;
		}
		return $vars;
	}

	//
	// Fetch the URL assiciated with the link
	function getUrl($anchor = '', $and_mode = 'content') {
		$url = $this->file;
		if (!$url) $url = './';
		$query = '';
		$vars = $this->getAllVars();

		$symb_and = ($and_mode == 'header' ? '&' : '&amp;');
		
		if (is_array($vars)) {
			$first = true;
			reset($vars);
			while (list($name, $value) = each($vars)) {
				$query .= (($query) ? $symb_and : '?').$name.'='.urlencode($value);
			}
		}
		if (is_array($this->arrays)) {
			reset($this->arrays);
			while (list($name, $table) = each($this->arrays)) {
				reset($table);
				while (list(, $value) = each($table)) {
					$query .= (($query) ? $symb_and : '?').$name.'[]='.urlencode($value);
				}
			}
		}
		if ($anchor) $anchor = '#'.$anchor;
		return $url.$query.$anchor;
	}

	function getUrlForHeader($anchor = '', $and_mode = 'header') {
		return $this->getUrl($anchor, $and_mode);
	}

	//
	// Fetch the beginning of the form associated with the link
	// (opening tag + hidden variables representing the variables)
	function getForm($method = 'get', $anchor = '', $enctype = '', $class = '') {
		if ($anchor) $anchor = '#'.$anchor;
		$form = "<form method='$method' action='".$this->file.$anchor."'";
		if ($enctype) $form .= " enctype='$enctype'";
		if ($class) $form .= " class='$class'";
		$form .= ">\n";
		$vars = $this->getAllVars();
		if (is_array($vars)) {
			reset($vars);
			while (list($name, $value) = each($vars)) {
				$value = preg_replace('/&amp;(#[0-9]+;)/', '&\1', htmlspecialchars($value));
				$form .= "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
			}
		}
		if (is_array($this->arrays)) {
			reset($this->arrays);
			while (list($name, $table) = each($this->vars)) {
				reset($table);
				while (list(, $value) = each($table)) {
					$value = preg_replace('/&amp;(#[0-9]+;)/', '&\1', htmlspecialchars($value));
					$form .= "<input type=\"hidden\" name=\"".$name."[]\" value=\"$value\" />\n";
				}
			}
		}
		return $form;
	}

	//
	// Fetch the attribut href="<URL>" associated with the link
	function getHref($anchor = '') {
		return 'href="'.$this->getUrl($anchor).'"';
	}
}

//
// Create a link and return directly the associated href="<URL>"
function newLinkHref($url) {
	$link = new Link($url);
	return $link->getHref();
}

function newLinkUrl($url) {
	$link = new Link($url);
	return $link->getUrl();
}

//
// Fetch the value of a session variable in the current page
function getSessionVar($name) {
	return $GLOBALS['this_link']->getSessionVar($name);
}

//
// Fetch the value of a temporary variable in the current page
function getTmpVar($name) {
	return $GLOBALS['this_link']->getTmpVar($name);
}


// Link to the currently requested page and clean it so that it has only id_objects
$this_link = new Link();

// [ML] XXX WARNING: This should be deprecated, will be removed soon
// (mostly present in old Spip code)
$clean_link = $this_link;
$clean_link->delVar('submit');
$clean_link->delVar('recalcul');
if (isset($GLOBALS['HTTP_POST_VARS']) && count($GLOBALS['HTTP_POST_VARS'])) {
	$clean_link->clearVars();
	// There are surely missing..
	// [ML] This may cause bugs!! XXX
	$vars = array('var_login');
	while (list(,$var) = each($vars)) {
		if (isset($$var)) {
			$clean_link->addVar($var, $$var);
			break;
		}
	}

}

// Read the cached meta information
if (include_data_exists('inc_meta_cache') AND !defined('_INC_META_CACHE')  AND !defined('_INC_META')) {
	include_data('inc_meta_cache');
}

// This is used usually at installation, when the inc/data/inc_meta_cache.php
// is not yet created, and avoids having tons of warnings printed.
if (!defined('_INC_META_CACHE')) {
	function read_meta($name) {
		global $meta;
		return $meta[$name];
	}
	function read_meta_upd($name) {
		global $meta_upd;
		return $meta_upd[$name];
	}
	define('_INC_META_CACHE', '1');
}


// Verify the confirmity of one or many email adresses
function is_valid_email($address) {
	$many_addresses = explode(',', $address);
	if (is_array($many_addresses)) {
		while (list(, $address) = each($many_addresses)) {
			// clean certain formats
			// "Marie Toto <Marie@toto.com>"
			$address = preg_replace("/^[^<>\"]*<([^<>\"]+)>$/i", "\\1", $address);
			// RFC 822
			if (!preg_match('/^[^()<>@,;:\\"\/[:space:]]+(@([-_0-9a-z]+\.)*[-_0-9a-z]+)?$/i', trim($address)))
				return false;
		}
		return true;
	}
	return false;
}


//
// Translation of LCM texts
//
function _T($text, $args = '') {
	include_lcm('inc_lang');
	return translate_string($text, $args);
}

// Translate text and use it in 'input field', meaning that it should
// have ':' at the end.
function _Ti($text, $args = '') {
	include_lcm('inc_lang');
	$str = translate_string($text, $args);
	
	if (! preg_match('/:$/', $str))
		$str .= ':';
	
	$str .= '&nbsp;';
	return $str;
}

// Translate text and use it in 'header field', meaning that it should
// NOT have ':' at the end.
function _Th($text, $args = '') {
	include_lcm('inc_lang');
	$str = translate_string($text, $args);
	
	if (preg_match('/(.*):$/', $str, $regs))
		$str = $regs[1];
	
	return $str;
}

function _Tkw($grp, $val, $args = '') {
	global $system_kwg;
	$kwg = array();

	// If a 'contact' keyword (starts with +), remove the +
	if (substr($val, 0, 1) == '+')
		$val = substr($val, 1);

	if ($system_kwg[$grp])
		$kwg = $system_kwg[$grp]['keywords'];
	else
		$kwg = get_keywords_in_group_name($grp, false);

	if (count($kwg)) {
		if ($kwg[$val])
			return _T(remove_number_prefix($kwg[$val]['title']), $args);
		else {
			// This is a weird case where the upgrade didn't refresh the
			// group correctly, so we will try to fix the situation.
			// First, we check in the database to see if the keyword exists,
			// and if it does, then we refresh the keywords.
			// Note: that get_keywords_in_group_id() consults only the DB
			// [ML] Note: this should not happen from 0.6.3, but i'm fed up of
			// thinking that this time we fixed it, so the code stays..
			$tmp_group = get_kwg_from_name($grp);
			$kws1 = get_keywords_in_group_id($tmp_group['id_group'], false);

			foreach ($kws1 as $kw) {
				if ($kw['name'] == $val) {
					include_lcm('inc_keywords_default');

					$system_keyword_groups = get_default_keywords();
					create_groups($system_keyword_groups);
					write_metas(); // regenerate inc/data/inc_meta_cache.php

					return _T(remove_number_prefix($kw['title']), $args);
				}
			}

			lcm_panic("*** The keyword does not exist. It is possible that a
				minor error occured while the last upgrade of the software. Please
				ask your administrator to do the following: Go to the 'Keywords'
				menu, then click on the 'Maintenance' tab, then click on the
				'Validate' button. This will refresh the available keywords.");
		}
	} else {
		lcm_panic("*** The keyword group does not exist. It is possible that a
			minor error occured while the last upgrade of the software. Please
			ask your administrator to do the following: Go to the 'Keywords'
			menu, then click on the 'Maintenance' tab, then click on the
			'Validate' button. This will refresh the available keywords.");
	}
}

include_lcm('inc_filters');
function _request ($name, $default = '') {
	if (! isset($_REQUEST[$name]))
		return $default;

	if (is_array($_REQUEST[$name])) {
		// TODO: recursively clean all array items ?
		// (note: at the moment, we don't have arrays with more than 1 level)
		$ret = array();

		foreach ($_REQUEST[$name] as $key => $val)
			$ret[$key] = trim(clean_input($val));

		return $ret;
	}

	if (is_string($_REQUEST[$name]))
		if ($v = trim(clean_input($_REQUEST[$name])))
			return $v;
		return $default;

	lcm_log("** WARNING: suspicious data received in request:");
	lcm_log(htmlspecialchars(get_var_dump($_REQUEST[$name])));
	return $default;
}

function _session ($name, $default = '') {
	if (isset($_SESSION['form_data'][$name]) && $_SESSION['form_data'][$name])
		return clean_input($_SESSION['form_data'][$name]);
	else
		return $default;
}

// Main language of the site
$langue_site = read_meta('default_language');
if (!$langue_site) include_lcm('inc_lang');
$lcm_lang = $langue_site;


// Journal of events
function lcm_log($message, $type = 'lcm') {
	$pid = '(pid '.@getmypid().')';
	if (!$ip = $_SERVER['REMOTE_ADDR']) $ip = '-';
	$message = date("M d H:i:s") . " $ip $pid " . preg_replace("/\n*$/", "\n", $message);
	$rotate = false;

	// Admins can put "SetEnv LcmLogDir /var/log/..." in their apache.conf or vhost
	$logfile = $type . ".log";

	if (isset($_SERVER['LcmLogDir']))
		$logfile = $_SERVER['LcmLogDir'] . "/" . $logfile;
	else
		$logfile = "log/" . $logfile;

	// Keep about 20Kb of data per file, on 4 files (.1, .2, .3)
	// generates about 80Kb in total per log type.
	$kb_size = ($GLOBALS['debug'] ? 200 : 20); // more if we debug!
	if (is_file($logfile) && @filesize($logfile) > $kb_size * 1024) {
		$rotate = true;
		$message .= "[-- rotate --]\n";
	}
	
	$f = @fopen($logfile, "ab");
	if ($f) {
		fputs($f, $message);
		fclose($f);
	} else {
		global $debug;

		if ($debug)
			echo "<p>$logfile: Failed to open log file </p>\n";
	}
	
	if ($rotate) {
		@unlink($logfile . '.3');
		@rename($logfile . '.2', $logfile . '.3');
		@rename($logfile . '.1', $logfile . '.2');
		@rename($logfile, $logfile . '.1');
	}
}

//
// Check wheter we can launch large calculations, and eventually place a lock
// Results: true = go ahead with operation, false = stop
// 
// [ML] This should not be needed - perhaps before generating reports, so I
// will leave it for now.
function timeout($lock=false, $action=true, $connect_mysql=true) {
	static $ok = true;
	global $db_ok;

	// Has the hosting provided put a lock? (maximum age, 10 minutes)
	$timeoutfile = (isset($_SERVER['LcmDataDir']) ? $_SERVER['LcmDataDir'] : 'inc/data') . '/lock';

	if (@file_exists($timeoutfile)
	AND ((time() - @filemtime($timeoutfile)) < 600)) {
		lcm_debug ("lock hebergeur $timeoutfile");
		return $ok = false;
	}

	// Nothing to do?
	if (!$action || !$ok)
		return $ok;

	$ok = false;

	// Database connected?
	if ($connect_mysql) {
		include_ecrire('inc_connect.php');
		if (!$db_ok)
			return false;

		// Lock requested?
		if ($lock) {
			lcm_debug("test lock mysql $lock");
			if (!spip_get_lock($lock)) {
				lcm_debug ("lock mysql $lock");
				return false;
			}
		}
	}

	// Go ahead
	return true;
}

//
// Tests on the name of the browser
function verif_butineur() {
	global $HTTP_USER_AGENT, $browser_name, $browser_version, $browser_description, $browser_rev;
	preg_match("/^([A-Za-z]+)\/([0-9]+\.[0-9]+) (.*)$/", $HTTP_USER_AGENT, $match);
	$browser_name = $match[1];
	$browser_version = $match[2];
	$browser_description = $match[3];

	if (preg_match("/opera/i", $browser_description)) {
		preg_match("/Opera ([^\ ]*)/i", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
	}
	else if (preg_match("/msie/i", $browser_description)) {
		preg_match("/MSIE ([^;]*)/i", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
	}
	else if (preg_match("/mozilla/i", $browser_name) AND $browser_version >= 5) {
		// Authentic Mozilla version
		if (preg_match("/rv:([0-9]+\.[0-9]+)/i", $browser_description, $match))
			$browser_rev = doubleval($match[1]);
		// Other Geckos => equivalent to 1.4 by default (Galeon, etc.)
		else if (strpos($browser_description, "Gecko") and !strpos($browser_description, "KHTML"))
			$browser_rev = 1.4;
		// Random versions => equivalent to 1.0 by default (Konqueror, etc.)
		else $browser_rev = 1.0;
	}

	if (!$browser_name) $browser_name = "Mozilla";
}

// Based from the comments in:
// http://www.php.net/manual/fr/function.debug-backtrace.php
function lcm_getbacktrace($html = true, $level = 0)
{
	$cpt_level = 0;
	$s = '';
	$MAXSTRLEN = 1024;

	if ($html)
		$s = '<pre align="left">';

	if (! function_exists("debug_backtrace"))
		return "debug_backtrace function not available (PHP = " . PHP_VERSION . ")";

	$traceArr = debug_backtrace();
	array_shift($traceArr);
	$tabs = sizeof($traceArr)-1;
	// foreach($traceArr as $arr) {
	while (($arr = array_shift($traceArr)) && ((! $level) || $cpt_level++ < $level)) {
		for ($i=0; $i < $tabs; $i++) 
			$s .= ($html ? ' &nbsp; ' : '  ');

		$tabs -= 1;
		if ($html) $s .= '<font face="Courier New,Courier">';
		if (isset($arr['class'])) $s .= $arr['class'] . $arr['type'];
		$args = array();
		if(!empty($arr['args'])) foreach($arr['args'] as $v)
		{
			if (is_null($v)) $args[] = 'null';
			else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
			else if (is_object($v)) $args[] = 'Object:'.get_class($v);
			else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
			else
			{
				$v = (string) @$v;
				$str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
				if (strlen($v) > $MAXSTRLEN) $str .= '...';
				$args[] = "\"".$str."\"";
			}
		}
		$s .= $arr['function'] . '(' . implode(', ', $args). ')' . ($html ? '</font>' : '');
		$Line = (isset($arr['line'])? $arr['line'] : "unknown");
		$File = (isset($arr['file'])? $arr['file'] : "unknown");

		if ($html)
			$s .= sprintf("<font color='#993333' size='-1'> # line %4d, file: %s</font>", $Line, $File, $File);
		else
			$s .= sprintf(" # line %4d, file: %s", $Line, $File, $File);

		$s .= "\n";
	}

	if ($html) $s .= '</pre>';
	return $s;
}

function get_var_dump($v = null) {
	ob_start();
	var_dump($v);
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}

function lcm_panic($message) {
	global $lcm_version, $lcm_db_version;

	function lcm_ini_get($param) {
		$ret = ini_get($param);

		return ($ret ? $ret : 'n/a');
	}

	echo "<p>" . _T('warning_panic_is_useful') . "</p>\n";
	$error = "[INTERNAL] (v" . $lcm_version . "-db" . $lcm_db_version . ", PHP v" . PHP_VERSION . ")\n";
	$error .= "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";

	if (function_exists('lcm_sql_server_info'))
		$error .= "SQL server: " . lcm_sql_server_info() . "\n";
	else
		$error .= "SQL server: not yet connected\n";

	$error .= "Referer: " . $_SERVER['HTTP_REFERER'] . "\n";
	$error .= "Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n";
	$error .= "Error: " . $message . "\n";

	// Show DB version in meta cache
	$error .= "Version-DB: " . read_meta('lcm_db_version') . " (in cache)\n";

	// Show existence + size of cache, in case it doesnt exist, or there were
	// problems while generating it (i.e. it will be less than 30kb)
	if (include_data_exists('inc_meta_cache')) {
		 if (isset($_SERVER['LcmDataDir']))
		 	$prefix = $_SERVER['LcmDataDir'] . '/';
		else
			$prefix = 'inc/data/';
	
		$error .= "inc_meta_cache: exists (" . filesize($prefix . 'inc_meta_cache.php') . " bytes)\n";
	} else {
		$error .= "inc_meta_cache: does NOT exists\n";
	}

	$check_confs = array('safe_mode', 'safe_mode_gid', 'safe_mode_include_dir', 'safe_mode_exec_dir',
						'open_basedir', 'disable_functions');

	foreach ($check_confs as $conf)
		$error .= $conf . ': ' . lcm_ini_get($conf) . "\n";

	if ($GLOBALS['debug']) {
		$error .= "cookie_prefix: " . $GLOBALS['cookie_prefix'] . "\n";
		$error .= "table_prefix: " . $GLOBALS['table_prefix'] . "\n";
		$error .= "_GET: " . get_var_dump($_GET) . "\n";
		$error .= "_POST: " . get_var_dump($_POST) . "\n";
		$error .= "_COOKIE: " . get_var_dump($_COOKIE) . "\n";
		$error .= "_SERVER: " . get_var_dump($_SERVER) . "\n";
		$error .= "included_files: " . get_var_dump($GLOBALS['included_files']) . "\n";
		$error .= "meta: " . get_var_dump($GLOBALS['meta']) . "\n";
	}

	// Too much paranoia? I am not even sure if we can inject code
	// either XSS or shellcode .. but should not hurt..
	$error = htmlspecialchars($error);

	// Make different lcm_getbacktrace() calls to avoid html in logs
	lcm_log($error . lcm_getbacktrace(false) . "END OF REPORT\n");
	die("<pre>" . $error . " " . lcm_getbacktrace() . "END OF REPORT\n</pre>");
}

function lcm_assert_value($value, $allow_zero = false) {
	if (is_numeric($value) && $value == 0 && (! $allow_zero))
		lcm_panic("Value is 0, but allow_zero is false");

	if ((! isset($value)) || (! $value))
		lcm_panic("Missing value (unset or non-true)");
	
	return $value;
}

function lcm_debug($message, $level = 1, $type = 'lcm') {
	// Level 0: No debug
	// Level 1: General debug
	// Level 2: PHP warnings
	// Level 3-4: future use?
	// Level 5: 1-4 + Includes

	if (isset($GLOBALS['debug']) && $GLOBALS['debug'] >= $level)
		lcm_log("[D$level] $message", $type);
}

function lcm_header($h) {
	if ($GLOBALS['debug']) {
		lcm_log(lcm_getbacktrace(false, 2));
	}

	header($h);
}

// In debug mode, log the calling URI
lcm_debug($_SERVER['REQUEST_METHOD'] . ": " . $_SERVER['REQUEST_URI'], 1);
lcm_debug($_SERVER['REQUEST_METHOD'] . ": " . $_SERVER['REQUEST_URI'], 1, 'sql');

?>
