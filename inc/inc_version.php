<?php

//
// Execute this file only once
if (defined('_INC_VERSION')) return;
define('_INC_VERSION', '1');


// *********** clean the variables **************
// Magic quotes: we don't want any in the database,
// and we clean GET/POST/COOKIE in consequence.
function magic_unquote($table) {
	if (is_array($GLOBALS[$table])) {
	        reset($GLOBALS[$table]);
	        while (list($key, $val) = each($GLOBALS[$table])) {
	        	if (is_string($val))
				$GLOBALS[$table][$key] = stripslashes($val);
	        }
	}
}

@set_magic_quotes_runtime(0);
$unquote_gpc = @get_magic_quotes_gpc();

if ($unquote_gpc) {
	magic_unquote('HTTP_GET_VARS');
	magic_unquote('HTTP_POST_VARS');
	magic_unquote('HTTP_COOKIE_VARS');
}


//
// Dirty against the register_globals to 'Off' (PHP 4.1.x)
//
$INSECURE = array();

function feed_globals($table, $insecure = true, $ignore_variables_contexte = false) {
	global $INSECURE;

	// ignore cookies which could include context information
	$is_contexte = array('id_parent'=>1, 'id_rubrique'=>1, 'id_article'=>1, 'id_auteur'=>1,
		'id_breve'=>1, 'id_forum'=>1, 'id_secteur'=>1, 'id_syndic'=>1, 'id_syndic_article'=>1,
		'id_mot'=>1, 'id_groupe'=>1, 'id_document'=>1, 'date'=>1, 'lang'=>1);

	if (is_array($GLOBALS[$table])) {
        reset($GLOBALS[$table]);
        while (list($key, $val) = each($GLOBALS[$table])) {
			if ($ignore_variables_contexte AND isset($is_contexte[$key]))
				unset ($GLOBALS[$key]);
			else
				$GLOBALS[$key] = $val;
			if ($insecure) $INSECURE[$key] = $val;
        }
	}
}

feed_globals('HTTP_COOKIE_VARS', true, true);
feed_globals('HTTP_GET_VARS');
feed_globals('HTTP_POST_VARS');
feed_globals('HTTP_SERVER_VARS', false);


// With register_globals to Off in PHP4, we need to use the new
// HTTP_POST_FILES variable for the uploaded files (does not work
// under PHP3). 
//
// [ML] LCM may need attached files in the future, so I will leave
// this for now.
function feed_post_files($table) {
	global $INSECURE;
	if (is_array($GLOBALS[$table])) {
	        reset($GLOBALS[$table]);
	        while (list($key, $val) = each($GLOBALS[$table])) {
	                $GLOBALS[$key] = $INSECURE[$key] = $val['tmp_name'];
	                $GLOBALS[$key.'_name'] = $INSECURE[$key.'_name'] = $val['name'];
	        }
	}
}

feed_post_files('HTTP_POST_FILES');


//  ************************************
// 	*** Default configuration of LCM ***
//
// The following parameters can be overriden via inc/my_options.php.
//

// Prefix of tables in the database
// (to modify in order to have many LCM running in the same database)
$table_prefix = 'lcm';

// Prefix and path of cookies
// (to modify in order to have many LCM running in sub-directories)
$cookie_prefix = 'lcm';
$cookie_path = '';

// Template path (for report generation)
$dossier_squelettes = 'tpl';

// Should we authorize LCM to compress the pages on the fly when
// the navigator accepts it (Apache 1.3 only) ?
$auto_compress = true;

// [ML] This will probably not be used
// creation of thumbnails with ImageMagick on the command line: put the
// complete path '/bin/convert' (Linux) or '/sw/bin/convert' (fink/Mac OS X)
// Note : better to use GD2 or the php module imagick if they are available
$convert_command = 'convert';

// Should we debug in data/lcm.log ?
$debug = true;

// Shoud we highligh translation strings? (helps to find non-translated strings)
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

// Activate management of "extras"? (see inc/inc_extra.php for more information)
// [ML] This is not used for now
$champs_extra = false;
$champs_extra_proposes = false;

// Should we ignore authentication by auth http/remove user?
// This allows to have a LCM under .htaccess (ignore_remote_user),
// but also to work on crazy disfonctional servers who block
// PHP_AUTH_USER=root (ignore_auth_http) -- [ML] Not sure what this
// is for.
$ignore_auth_http = false;
$ignore_remote_user = false;


//
// *** End of configuration ***
//

// This allows users to override the defaults
if (@file_exists('inc/my_options.php'))
	include('inc/my_options.php');

// Current version of LCM
$lcm_version = 0.41;

// Current version of LCM shown on screen
$lcm_version_shown = "0.4.1";

// Current version of LCM database
$lcm_db_version = 16;

// Error reporting
// error_reporting(E_ALL); // [ML] recommended for debug
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
if (ereg('([0-9]+)', $php_version[2], $match)) $php_version_min = intval($match[1]);

$flag_levenshtein = ($php_version_maj >= 4);
$flag_uniqid2 = ($php_version_maj > 3 OR $php_version_min >= 13);
$flag_get_cfg_var = (@get_cfg_var('error_reporting') != "");
$flag_strtr2 = ($php_version_maj > 3);

$flag_ini_get = (function_exists("ini_get")
	&& (@ini_get('max_execution_time') > 0));	// verifier pas desactivee
$flag_gz = function_exists("gzopen");
$flag_ob = ($flag_ini_get
	&& !ereg("ob_", ini_get('disable_functions'))
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
	$name = ereg_replace ('^lcm', $GLOBALS['cookie_prefix'], $name);
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

if ($cookie_prefix != 'lcm') {
	reset ($HTTP_COOKIE_VARS);
	while (list($name,$value) = each($HTTP_COOKIE_VARS)) {
		if (ereg('^lcm', $name)) {
			unset($HTTP_COOKIE_VARS[$name]);
			unset($$name);
		}
	}
	reset ($HTTP_COOKIE_VARS);
	while (list($name,$value) = each($HTTP_COOKIE_VARS)) {
		if (ereg('^'.$cookie_prefix, $name)) {
			$spipname = ereg_replace ('^'.$cookie_prefix, 'lcm', $name);
			$HTTP_COOKIE_VARS[$spipname] = $INSECURE[$spipname] = $value;
			$$spipname = $value;
		}
	}
}


//
// Information about the web hosting
// [ML] alot was removed
//

$os_server = '';

if (eregi('\(Win', $HTTP_SERVER_VARS['SERVER_SOFTWARE']))
	$os_server = 'windows';

// By default, set maximum access rights
// [ML] This will require auditing..
@umask(0);


//
// Information on the current file
//

// Compatibility with servers who don't provide $REQUEST_URI
if (!$REQUEST_URI) {
	$REQUEST_URI = $PHP_SELF;
	if (!strpos($REQUEST_URI, '?') && $QUERY_STRING)
		$REQUEST_URI .= '?'.$QUERY_STRING;
}

if (!$PATH_TRANSLATED) {
	if ($SCRIPT_FILENAME) $PATH_TRANSLATED = $SCRIPT_FILENAME;
	else if ($DOCUMENT_ROOT && $SCRIPT_URL) $PATH_TRANSLATED = $DOCUMENT_ROOT.$SCRIPT_URL;
}



//
// Management of inclusion and information on directories
//

$included_files = array();

function include_local($file) {
	if ($GLOBALS['included_files'][$file]) return;
	include($file);
	$GLOBALS['included_files'][$file] = 1;
}

function include_lcm($file) {
	$lcmfile = 'inc/' . $file . '.php';

	if (array_key_exists($lcmfile, $GLOBALS['included_files']))
		return;

	if (! @file_exists($lcmfile)) {
		lcm_log("CRITICAL: file for include_lcm does not exist: " . $lcmfile);
		if ($GLOBALS['debug']) echo lcm_getbacktrace();
	}
	
	include($lcmfile);
	$GLOBALS['included_files'][$lcmfile] = 1;
}

function include_config($file) {
	$lcmfile = 'inc/config/' . $file . '.php';

	if (array_key_exists($lcmfile, $GLOBALS['included_files']))
		return;

	if (! @file_exists($lcmfile)) {
		lcm_log("CRITICAL: file for include_config does not exist: " . $lcmfile);
		if ($GLOBALS['debug']) echo lcm_getbacktrace();
	}
	
	include($lcmfile);
	$GLOBALS['included_files'][$lcmfile] = 1;
}

function include_data($file) {
	$lcmfile = 'inc/data/' . $file . '.php';

	if (array_key_exists($lcmfile, $GLOBALS['included_files']))
		return;

	if (! @file_exists($lcmfile)) {
		lcm_log("CRITICAL: file for include_data does not exist: " . $lcmfile);
		if ($GLOBALS['debug']) echo lcm_getbacktrace();
	}
	
	include($lcmfile);
	$GLOBALS['included_files'][$lcmfile] = 1;
}

$flag_connect = @file_exists('inc/config/inc_connect.php');

function lcm_query($query) {
	include_lcm('inc_db');

	// We silently fail if there is no database, this avoids 
	// many warnings while installation, for example.
	if ($GLOBALS['flag_connect']) {
		include_config('inc_connect');
		if (!$GLOBALS['db_ok'])
			return;
	}

	return lcm_query_db($query);
}

function spip_query($query) {
	return lcm_query($query);
}

//
// PHP configuration information
//

// cf. list of sapi_name - http://www.php.net/php_sapi_name
$php_module = (($flag_sapi_name AND eregi("apache", @php_sapi_name())) OR
	ereg("^Apache.* PHP", $SERVER_SOFTWARE));
$php_cgi = ($flag_sapi_name AND eregi("cgi", @php_sapi_name()));

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
		$if_modified_since = ereg_replace(';.*$', '', $GLOBALS['HTTP_IF_MODIFIED_SINCE']);
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
	else if (eregi("NetCache|Hasd_proxy", $HTTP_VIA))
		$use_gz = false;
	*/

	// special bug Netscape Win 4.0x
	else if (eregi("Mozilla/4\.0[^ ].*Win", $HTTP_USER_AGENT))
		$use_gz = false;

	// special bug Apache2x
	else if (eregi("Apache(-[^ ]+)?/2", $SERVER_SOFTWARE))
		$use_gz = false;
	else if ($flag_sapi_name && ereg("^apache2", @php_sapi_name()))
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
					if (ereg('^(.*)\[\]$', $name, $regs)) {
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
			$url = $GLOBALS['REQUEST_URI'];
			$url = substr($url, strrpos($url, '/') + 1);
			if (!$url) $url = "./";
			if (count($GLOBALS['HTTP_POST_VARS']))
				$vars = $GLOBALS['HTTP_POST_VARS'];
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
				if (ereg('^(.*)\[\]$', $name, $regs)) {
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
		unset($this->vars[$name]);
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
		return $this->vars[$name];
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
	function getUrl($anchor = '') {
		$url = $this->file;
		if (!$url) $url = './';
		$query = '';
		$vars = $this->getAllVars();
		if (is_array($vars)) {
			$first = true;
			reset($vars);
			while (list($name, $value) = each($vars)) {
				$query .= (($query) ? '&' : '?').$name.'='.urlencode($value);
			}
		}
		if (is_array($this->arrays)) {
			reset($this->arrays);
			while (list($name, $table) = each($this->arrays)) {
				reset($table);
				while (list(, $value) = each($table)) {
					$query .= (($query) ? '&' : '?').$name.'[]='.urlencode($value);
				}
			}
		}
		if ($anchor) $anchor = '#'.$anchor;
		return $url.$query.$anchor;
	}

	//
	// Fetch the beginning of the form associated with the link
	// (opening tag + hidden variables representing the variables)
	function getForm($method = 'get', $anchor = '', $enctype = '') {
		if ($anchor) $anchor = '#'.$anchor;
		$form = "<form method='$method' action='".$this->file.$anchor."'";
		if ($enctype) $form .= " enctype='$enctype'";
		$form .= ">\n";
		$vars = $this->getAllVars();
		if (is_array($vars)) {
			reset($vars);
			while (list($name, $value) = each($vars)) {
				$value = ereg_replace('&amp;(#[0-9]+;)', '&\1', htmlspecialchars($value));
				$form .= "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
			}
		}
		if (is_array($this->arrays)) {
			reset($this->arrays);
			while (list($name, $table) = each($this->vars)) {
				reset($table);
				while (list(, $value) = each($table)) {
					$value = ereg_replace('&amp;(#[0-9]+;)', '&\1', htmlspecialchars($value));
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

$clean_link = $this_link;
$clean_link->delVar('submit');
$clean_link->delVar('recalcul');
if (count($GLOBALS['HTTP_POST_VARS'])) {
	$clean_link->clearVars();
	// There are surely missing..
	// [ML] This may cause bugs!! XXX
	$vars = array('id_article', 'coll', 'id_breve', 'id_rubrique', 'id_syndic', 'id_mot', 'id_auteur', 'var_login');
	while (list(,$var) = each($vars)) {
		if (isset($$var)) {
			$clean_link->addVar($var, $$var);
			break;
		}
	}
}


// Read the cached meta information
$inc_meta_cache = 'inc/data/inc_meta_cache.php';
if (@file_exists($inc_meta_cache) AND !defined('_INC_META_CACHE')  AND !defined('_INC_META')) {
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
			$address = eregi_replace("^[^<>\"]*<([^<>\"]+)>$", "\\1", $address);
			// RFC 822
			if (!eregi('^[^()<>@,;:\\"/[:space:]]+(@([-_0-9a-z]+\.)*[-_0-9a-z]+)?$', trim($address)))
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

// Strings in the process of being translated
function _L($text) {
	if ($GLOBALS['test_i18n'])
		return "<span style='color:red;'>$text</span>";
	else
		return $text;
}

// Main language of the site
$langue_site = read_meta('langue_site');
if (!$langue_site) include_lcm('inc_lang');
$lcm_lang = $langue_site;


// Journal of events
function lcm_log($message, $type = 'lcm') {
	$pid = '(pid '.@getmypid().')';
	if (!$ip = $GLOBALS['REMOTE_ADDR']) $ip = '-';
	$message = date("M d H:i:s") . " $ip $pid " . ereg_replace("\n*$", "\n", $message);
	$logfile = "log/" . $type . ".log";
	$rotate = false;

	// Keep about 20Kb of data per file, on 4 files (.1, .2, .3)
	// generates about 80Kb in total per log type.
	if (@filesize($logfile) > 20 * 1024) {
		$rotate = true;
		$message .= "[-- rotate --]\n";
	}
	
	$f = @fopen($logfile, "ab");
	if ($f) {
		fputs($f, $message);
		fclose($f);
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
	$timeoutfile = 'inc/data/lock';
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
	ereg("^([A-Za-z]+)/([0-9]+\.[0-9]+) (.*)$", $HTTP_USER_AGENT, $match);
	$browser_name = $match[1];
	$browser_version = $match[2];
	$browser_description = $match[3];

	if (eregi("opera", $browser_description)) {
		eregi("Opera ([^\ ]*)", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
	}
	else if (eregi("msie", $browser_description)) {
		eregi("MSIE ([^;]*)", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
	}
	else if (eregi("mozilla", $browser_name) AND $browser_version >= 5) {
		// Authentic Mozilla version
		if (ereg("rv:([0-9]+\.[0-9]+)", $browser_description, $match))
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
function lcm_getbacktrace()
{
	$s = '';
	$MAXSTRLEN = 64;

	$s = '<pre align=left>';
	$traceArr = debug_backtrace();
	array_shift($traceArr);
	$tabs = sizeof($traceArr)-1;
	foreach($traceArr as $arr) {
		for ($i=0; $i < $tabs; $i++) $s .= ' &nbsp; ';
		$tabs -= 1;
		$s .= '<font face="Courier New,Courier">';
		if (isset($arr['class'])) $s .= $arr['class'].'.';
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
		$s .= $arr['function'].'('.implode(', ',$args).')</font>';
		$Line = (isset($arr['line'])? $arr['line'] : "unknown");
		$File = (isset($arr['file'])? $arr['file'] : "unknown");
		$s .= sprintf("<font color='#993333' size='-1'> # line %4d, file: %s</font>", $Line, $File, $File);
		$s .= "\n";
	}

	$s .= '</pre>';
	return $s;
}

function lcm_panic($message) {
	$error = "[INTERNAL] " . $message . "\n";
	$error .= lcm_getbacktrace();

	lcm_log($error);
	die("<pre>$error</pre>");
}

function lcm_debug($message) {
	if ($GLOBALS['debug'])
		lcm_log($message);
}


// In debug mode, log the calling URI (not very efficient, it's only for debugging!)
if ($debug)
	lcm_log("$REQUEST_METHOD: ".$GLOBALS['REQUEST_URI']);

?>
