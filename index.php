<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Released under GNU GPL version 2 or any later version

	A free program released under the terms and conditions
	of the GNU GPL version 2 or any later version

	Linking Dragonfly CMS statically or dynamically with other modules is making a
	combined work based on Dragonfly CMS.  Thus, the terms and conditions of the GNU
	General Public License cover the whole combination.

	As a special exception, the copyright holders of Dragonfly CMS give you
	permission to link Dragonfly CMS with independent modules that communicate with
	Dragonfly CMS solely through the CPG-Core interface, regardless of the license
	terms of these independent modules, and to copy and distribute the
	resulting combined work under terms of your choice, provided that
	every copy of the combined work is accompanied by a complete copy of
	the source code of Dragonfly CMS (the version of Dragonfly CMS used to produce the
	combined work), being distributed under the terms of the GNU General
	Public License plus this exception.  An independent module is a module
	which is not derived from or based on Dragonfly CMS.

	Note that people who make modified versions of Dragonfly CMS are not obligated
	to grant this special exception for their modified versions; it is
	their choice whether to do so.  The GNU General Public License gives
	permission to release a modified version without this exception; this
	exception also makes it possible to release a modified version which
	carries forward this exception.
	http://gnu.org/licenses/gpl-faq.html#LinkingOverControlledInterface
*/

if (version_compare(PHP_VERSION, '5.5.4', '<')) {
	exit('This software needs atleast PHP 5.5.4, currently: '.PHP_VERSION);
}

date_default_timezone_set('UTC');

if (defined('DF_MODE_DEVELOPER')) { exit; }
define('DF_MODE_DEVELOPER', is_file('developer'));

# get current mem usage
define('START_MEMORY_USAGE', memory_get_usage());

# ignore abort requests to allow clean shutdowns
//ignore_user_abort(1);

# Core properties
define('BASEDIR',     __DIR__ . DIRECTORY_SEPARATOR);
define('CORE_PATH',   BASEDIR.   'includes/');
define('ADMIN_PATH',  BASEDIR.   'admin/');
define('CACHE_PATH',  BASEDIR.   'cache/');
define('CLASS_PATH',  CORE_PATH. 'classes/');
define('MODULE_PATH', BASEDIR.   'modules/');
define('DF_LOG_FILE', CACHE_PATH.'error.'.date('o-\WW').'.log');
ini_set('error_log', DF_LOG_FILE);
if (DF_MODE_DEVELOPER) {
	ini_set('log_errors',1);
	ini_set('display_errors',1);
	error_reporting(E_ALL);
	set_include_path(str_replace(PATH_SEPARATOR.'/usr/share/php','',get_include_path()));
}
umask(0);

require_once(CORE_PATH.'core.inc');

# Init the engine and register it with PHP
$DF = new DF;
register_shutdown_function(array($DF, 'detachAll'));

/**
 * Fastest autoloader when using:
 *   Dragonfly\Namespace\Classname as /includes/dragonfly/namespace/classname.php
 */
spl_autoload_register();
/**
 * Slower autoloader when using:
 *   Dragonfly\Classname as /includes/Dragonfly/classes/classname.php
 *   Dragonfly\Classname as /includes/Dragonfly/classname/classname.php
 *   Dragonfly\Modules\Name\Classname as /modules/name/classname.php
 */
spl_autoload_register('DF::autoLoad');


# Define our error handled asap to also catch early errors
require_once(CORE_PATH.'dragonfly/classes/debugger.php');
$Debugger = new \Dragonfly\Debugger;
$DF->attach('Debugger', $Debugger);
function trigger_deprecated($msg_more='', $offset=1)
{
	\Dragonfly\Debugger::deprecated($msg_more, $offset+1);
}

# load Dragonfly/Poodle framework
require_once(CORE_PATH.'dragonfly/dragonfly.php');
define('CPG_NUKE', Dragonfly::VERSION);

$DF->setState(DF::BOOT_CORE);

\Dragonfly\Net\Http::init();
require_once(CLASS_PATH.'url.php');

# Continue creating constants
define('GZIPSUPPORT', extension_loaded('zlib'));
define('GZIP_OUT', GZIPSUPPORT && !ini_get('zlib.output_compression') && \Poodle\HTTP\Headers::gzip());
define('WINDOWS', WINDOWS_OS);
if (empty($_SERVER['HTTP_HOST'])) { $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME']; }
define('DOMAIN_PATH', \Dragonfly::$URI_BASE.'/');

// Poodle_load_config()
$config_file = is_file(CORE_PATH. 'config_'. $_SERVER['HTTP_HOST']. '.php')
	? CORE_PATH. 'config_'. $_SERVER['HTTP_HOST']. '.php'
	: (is_file(CORE_PATH. 'config.php') ? CORE_PATH. 'config.php' : false);
if ($config_file) {
	require_once($config_file);
	if (!defined('DF_MODE_INSTALL')) {
		exit('Invalid config.php, more details at http://dragonflycms.org/Wiki/id=135/');
	}
	\Dragonfly::$URI_ADMIN = '?'. str_replace('.php', '', $adminindex);
} else {
	$loader = 'install';
	define('DF_MODE_INSTALL', true);
	define('DB_CHARSET', NULL);
	\Dragonfly::$URI_ADMIN = '?admin';
	$dbhost = $dbuname = $dbpass = $dbname = $prefix = $user_prefix = '';
}
unset($config_file);

\Dragonfly::$URI_INDEX = basename(__FILE__);

define('BASEHREF', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . DOMAIN_PATH);
# backward compatibility
$mainindex = \Dragonfly::$URI_INDEX;
$adminindex = \Dragonfly::$URI_ADMIN;

#
# HTTP stuff
#

\Dragonfly\LEO::resolveQuery();

if (!($_POST instanceof \Poodle\Input\POST)) {
	$_POST = new \Poodle\Input\POST($_POST);
}

if (!isset($loader)) {
	$loader = !empty($_GET) ? (string) key($_GET) : 'name';
	$loader = empty($loader) || 'newlang' == $loader ? 'name' : $loader;
	$loader = POODLE_CLI ? 'cli' : $loader;
}

header('Date: '. gmdate(DATE_RFC1123));
header('X-Powered-By: Dragonfly CMS using PHP engine');

if (empty($_SERVER['REQUEST_METHOD']) || 1 !== preg_match('#^(?:HEAD|GET|POST)$#', $_SERVER['REQUEST_METHOD']) ) {
	\Dragonfly\Net\Http::headersPush(405);
	\Dragonfly\Net\Http::headersFlush('Allow: HEAD, GET, POST');
}

if (isset($_SERVER['REDIRECT_STATUS'])) {
	if (between($_SERVER['REDIRECT_STATUS'], 400, 505)) {
		require_once('error.php');
		exit;
	}
}
if (!preg_match('#^[a-z0-9_]+$#', $loader) || !is_file(CORE_PATH. 'load/'. $loader. '.php')) {
	$_SERVER['REDIRECT_STATUS'] = 404;
	$_SERVER['REDIRECT_ERROR_NOTES'] = 'Loader file not found';
	require_once('error.php');
	exit;
}

include_once(CORE_PATH. 'load/'. $loader. '.php');
