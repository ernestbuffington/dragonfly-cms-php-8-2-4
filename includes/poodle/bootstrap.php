<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

if (function_exists('sys_getloadavg')) {
	$load = sys_getloadavg();
	if ($load[0] > 80) {
		header('HTTP/1.1 503 Service Unavailable');
		header('Retry-After: 120');
		exit('Server too busy. Please try again later.');
	}
	unset($load);
}

// PHP 7
if (7 > PHP_MAJOR_VERSION) {
	function random_int($min, $max) { return mt_rand($min, $max); }
	function random_bytes($length) { return \Poodle\Random::bytes($length); }
	interface Throwable {}
	class Error extends Exception {}
	class TypeError extends Error {}
	class ParseError extends Error {}
	class ArithmeticError extends Error {}
	class AssertionError extends Error {}
	class DivisionByZeroError extends ArithmeticError {}
	class ArgumentCountError extends TypeError {} // PHP 7.1
	class ClosedGeneratorException extends Exception {}
}

set_include_path('.'.PATH_SEPARATOR.dirname(__DIR__).PATH_SEPARATOR . get_include_path());

if (!defined('POODLE_HOSTS_PATH')){ define('POODLE_HOSTS_PATH','poodle_hosts/'); }
// When php-cgi is executed from cli, we ignore it. It has incorrect behavior and a very bad config!
define('POODLE_CLI', false !== stripos(php_sapi_name(), 'cli'));
define('XMLHTTPRequest', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' === $_SERVER['HTTP_X_REQUESTED_WITH']));
define('WINDOWS_OS', '\\' === DIRECTORY_SEPARATOR);

if (WINDOWS_OS) {
	$_SERVER = str_replace(DIRECTORY_SEPARATOR,'/',$_SERVER);
	function uri_dirname($path) { return rtrim(strtr(dirname($path),DIRECTORY_SEPARATOR,'/'),'/'); }
	// Workaround issue with IIS Helicontech APE which defines vars lowercased
	foreach ($_SERVER as $k => $v) { $_SERVER[strtoupper($k)] = $v; }
	// When using ISAPI with IIS, the value will be off if the request was not made through the HTTPS protocol.
	if (isset($_SERVER['HTTPS']) && 'off'===$_SERVER['HTTPS']) { unset($_SERVER['HTTPS']); }
} else {
	function uri_dirname($path) { return rtrim(dirname($path),'/'); }
}

// Default HTTP Strict Transport Security
if (isset($_SERVER['HTTPS'])) {
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

# Custom function to detect if array is associative
if (!function_exists('is_assoc')) { function is_assoc($a) { return (is_array($a) && array_keys($a) !== array_keys(array_keys($a))); } }

abstract class Poodle
{
	const
		VERSION    = '2.4.1.0',
		CHARSET    = 'UTF-8',

		DBG_MEMORY              = 1,
		DBG_PARSE_TIME          = 2,
		DBG_TPL_TIME            = 4,
		DBG_SQL                 = 8,
		DBG_SQL_QUERIES         = 16,
		DBG_JAVASCRIPT          = 32,
		DBG_PHP                 = 64,
		DBG_EXEC_TIME           = 128,
		DBG_TPL_INCLUDED_FILES  = 256,
		DBG_INCLUDED_FILES      = 268435456,
		DBG_DECLARED_CLASSES    = 536870912,
		DBG_DECLARED_INTERFACES = 1073741824,
		DBG_ALL                 = 2147483647; # 64bit: 9223372036854775807

	public static
		$DEBUG = 0,

		$UMASK = null, # octdec()?

		$COMPRESS_OUTPUT = false,

		$EXT  = null,
		$PATH = array(),

		$DIR_BASE  = '',
		$DIR_MEDIA = 'media/',

		$URI_ADMIN,
		$URI_BASE,
		$URI_INDEX,
		$URI_MEDIA,
		$UA_LANGUAGES;

	protected
		$CACHE,
		$IDENTITY;

	function __get($key)
	{
		if ('SQL' === $key) { return $this->loadDatabase(); }
		if ('CFG' === $key) { return $this->loadConfig(); }
		if (property_exists($this,$key)) { return $this->$key; }
		$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		trigger_error("Undefined property: {$key} by: {$bt[1]['file']}#{$bt[1]['line']}");
		return null;
	}
/*
	private static
		$PROCESS_UID = 0,
		$PROCESS_OWNER = 'nobody';
*/
	public static function chmod($file, $mask=0666)
	{
		return chmod($file, self::$UMASK ^ $mask);
	}

	public static function closeRequest($msg, $status_code=200, $uri=null, $box_msg=null)
	{
		if (POODLE_CLI) { echo $status_code.' '.$msg; exit; }
		if (XMLHTTPRequest) {
			header('Pragma: no-cache');
			header('Cache-Control: no-cache');
			\Poodle\HTTP\Status::set($status_code);
			switch ($status_code) {
			case 201: if ($uri) { header('Location: '.$uri); } break;
			case 204: exit;
			}
			if ($box_msg) { \Poodle\Notify::success($box_msg); }
			exit($msg);
		}
		if ($status_code >= 400) { \Poodle\Report::error($status_code, $msg); }
		if ($msg) { \Poodle\Notify::success($msg); }
		\Poodle\URI::redirect($uri);
	}

	public static function getFile($name, array $dirs = array())
	{
		if (!$dirs) {
			return stream_resolve_include_path($name);
		}
		foreach ($dirs as $dir) {
			// Plesk issue when using '.' and open_basedir
			// Warning: spl_autoload(): open_basedir restriction in effect.
			// Warning: is_file(): open_basedir restriction in effect.
			if ('.' === $dir[0]) { $dir = getcwd().substr($dir,1); }

			$file = $dir.DIRECTORY_SEPARATOR.$name;
			if (is_file($file)) {
				return $file;
			}
		}
	}

	public static function shortFilePath($file)
	{
		static $paths;
		if (!$paths) { $paths = array_merge(array(static::$DIR_BASE), explode(PATH_SEPARATOR, preg_replace('#\\.+'.PATH_SEPARATOR.'#', '', get_include_path()))); }
		return str_replace($paths, '', $file);
//		if (!$paths) { $paths = '#^('.strtr(get_include_path(),PATH_SEPARATOR,'|').')#'; }
//		if (!$paths) { $paths = '#^('.implode('|',array_merge(array(static::$DIR_BASE), explode(PATH_SEPARATOR, preg_replace('#\.+'.PATH_SEPARATOR.'#', '', get_include_path())))).')#'; }
//		return preg_replace(self::$re_paths, '', $file);
	}

	public static function getConfig($cfg_dir=null)
	{
		$dir = mb_strtolower($cfg_dir ? $cfg_dir : $_SERVER['HTTP_HOST']);
		$config = array();

		if (!is_file(POODLE_HOSTS_PATH."{$dir}/config.php")) {
			if ($cfg_dir) {
				trigger_error('Poodle config not found');
				return false;
			}
			// Redirect to domain when config with(out) 'www' does exists
			$dir = (0===strpos($dir,'www.')) ? substr($dir,4) : "www.{$dir}";
			if (is_file(POODLE_HOSTS_PATH."{$dir}/config.php")) {
				\Poodle\URI::redirect("http://{$dir}{$_SERVER['REQUEST_URI']}");
			}
			// Detect any domain config
			$dir = 'default';
		}

		if (is_file(POODLE_HOSTS_PATH."{$dir}/config.php")) {
			include(POODLE_HOSTS_PATH."{$dir}/config.php");
		}

		if ($config) {
			if (!isset($config['general']['cache_dir'])) {
				$config['general']['cache_dir'] = strtr(realpath(POODLE_HOSTS_PATH.$dir),DIRECTORY_SEPARATOR,'/').'/cache';
			}
			if (!isset($config['general']['cache_uri'])) {
				$config['general']['cache_uri'] = 'file://'.$config['general']['cache_dir'];
			}
		}

		if (is_null(self::$UMASK)) {
			self::$UMASK = ('cgi-fcgi' === PHP_SAPI) ? 0022 : 0;
/*
			# Get the process information
			if (!WINDOWS_OS && function_exists('posix_getpwuid')) {
				# w32 get_current_user() returns process
				$pwuid = posix_getpwuid(posix_geteuid());
				self::$PROCESS_UID = posix_geteuid();
				self::$PROCESS_OWNER = array_shift($pwuid);
			}
			self::$UMASK = (preg_match('#(www-data|nobody|apache)#', self::$PROCESS_OWNER) || getmyuid() !== self::$PROCESS_UID) ? 0 : 0022;
*/
		}
		umask(self::$UMASK);

		return $config;
	}

	protected static $KERNEL = null;
	public static function getKernel() { return self::$KERNEL; }
	public static function loadKernel($kernel_name=null, array $cfg=array())
	{
		if (self::$KERNEL) { throw new \Exception('Poodle Kernel already loaded'); }
		$name = $kernel_name;
		if (!$name && isset(self::$PATH[0])) {
			$name = self::$PATH[0];
		}
		$name = $name ? strtolower($name) : 'general';

		self::$COMPRESS_OUTPUT = true;

		$config = self::getConfig(isset($cfg['cfg_dir'])?$cfg['cfg_dir']:null);
		unset($cfg['cfg_dir']);
		$config = array_merge($config, $cfg);
		if ($config) {
			if (!isset($config[$name])) {
				if ($kernel_name) {
					// Kernel name was requested and therefore required. So we kill the process
					trigger_error("Poodle config[{$kernel_name}] not found", E_USER_ERROR);
				}
				$name = 'general';
			}
			if (isset($config['general'])) {
				$config[$name] = array_merge($config['general'], $config[$name]);
			}
			$config = isset($config[$name]) ? $config[$name] : array();
		}

		$class  = 'Poodle\\Kernels\\'.$name;
		self::$KERNEL = new $class($config);
		self::$KERNEL->init();

		return self::$KERNEL;
	}

	protected function init(){}
	public function addEventListener($type, callable $function){}

	# destroy output buffering
	public static function ob_clean()
	{
		if ($i = ob_get_level()) {
			# Clear buffers:
			while ($i-- && ob_end_clean());
			if (!ob_get_level()) header('Content-Encoding: ');
		}
	}

	# Flush all output buffers
	public static function ob_flush_all()
	{
		flush();
		if ($i = ob_get_level()) {
			while ($i-- && ob_end_flush());
		}
	}

	# autoload() ads a few milliseconds on each call
	public static function autoload($name)
	{
		$name = ltrim($name,'\\');
		//trigger_error('Autoload: '.$name);
/*
		if (!preg_match('#^([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$#', $name)) {
			# PEAR bug and such
			return;
		}
*/
		# split class_name into segments where the
		# first segment is the library or component
		$path = explode(strpos($name, '\\') ? '\\' : '_', $name);
		if (empty($path[1])) { return; }

		/** Default spl_autoload also lowercases the filename */
		$path = array_map('strtolower', $path);

		/** When the class name is the directory itself add itself as filename  */
		if (!isset($path[2])) { $path[2] = $path[1]; }

		if ($file = stream_resolve_include_path(implode('/',$path) . '.php')) {
			include_once $file;
		} else {
			/** Attempt to find class/interface/trait in global container directory */
			$lib = array_shift($path);
			if ($path[0] === $path[1]) { array_shift($path); }
			if ($file = stream_resolve_include_path($lib.'/classes/'.implode('_',$path) . '.php')) {
				include_once $file;
			}
		}
	}

	/** case-sensitive autoload for Zend Framework and such */
	public static function autoloadCS($name)
	{
		if ($file = stream_resolve_include_path(strtr($name,'\\', DIRECTORY_SEPARATOR) . '.php')) {
			include_once $file;
		}
	}

	public function loadDatabase()
	{
		static $dbms = null;
		if (!empty($this->SQL) && $this->SQL->ping()) { return $this->SQL; }
		if (null === $dbms) {
			$dbms = $this->_readonly_data['dbms'];
			unset($this->_readonly_data['dbms']);
		}
		$this->SQL = new \Poodle\SQL($dbms['adapter'], $dbms['master'], $dbms['tbl_prefix'], $dbms['slave']);
		$this->SQL->debug = \Poodle::$DEBUG;
		if (!isset($this->SQL->TBL->config)) {
			header('Retry-After: 3600');
			\Poodle\Report::error(503);
		}
		return $this->SQL;
	}

	public function loadConfig()
	{
		if (empty($this->CFG)) {
			$this->CFG = \Poodle\Config::load();
		}
		return $this->CFG;
	}

	public static function dataToJSON($data, $options = 0)
	{
		return json_encode($data, $options | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
	}
}
\Poodle::$DIR_BASE = getcwd().DIRECTORY_SEPARATOR;

/** Use default spl_autoload first (lowercased) */
spl_autoload_extensions('.php');
//spl_autoload_register();
/** Else use our extended autoload functions */
//spl_autoload_register('Poodle::autoload');
spl_autoload_register('Poodle::autoloadCS');

if (POODLE_CLI) { include('bootstrap_cli.php'); }

#
# User-Agent
#

$_SERVER['HTTP_USER_AGENT'] = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'JDatabaseDriverMysql')) {
	error_log("Invalid User-Agent [{$_SERVER['REMOTE_ADDR']}]: {$_SERVER['HTTP_USER_AGENT']}");
	exit;
}
/*
if (empty($_SERVER['HTTP_USER_AGENT'])
 || 10 > strlen($_SERVER['HTTP_USER_AGENT'])
 || !preg_match('#^[a-zA-Z]#', $_SERVER['HTTP_USER_AGENT']))
{
	error_log("Invalid User-Agent [{$_SERVER['REMOTE_ADDR']}]: {$_SERVER['HTTP_USER_AGENT']}");
	\Poodle\HTTP\Status::set(412);
	exit('You must send a correct User-Agent header so we can identify your browser');
}
*/

# Nagios check_http, hyperspin.com, paessler.com/support/kb/questions/12 and StatusCake
if (preg_match('#(check_http/|hyperspin\.com|paessler.com|StatusCake|Test Certificate Info)#i', $_SERVER['HTTP_USER_AGENT'])) {
	exit('OK');
}

# http://support.microsoft.com/kb/293792
if ('contype' === $_SERVER['HTTP_USER_AGENT']) {
	\Poodle\HTTP\Headers::setContentType('application/'.(strpos($_SERVER['REQUEST_URI'], 'pdf')?'pdf':'octet-stream'));
	exit;
}

#
# Load default server behavior
#

class_exists('Poodle\\DateTime');

setlocale(LC_ALL, 'C');

header('X-Content-Type-Options: nosniff'); # IE8 google.com/search?q=X-Content-Type-Options
header('X-UA-Compatible: IE=edge');
header('imagetoolbar: no'); # IE
header('X-Powered-By: Poodle WCMS using PHP');

putenv('HOME'); # cannot open /root/*: Permission denied
//if (!getenv('MAGIC')) { putenv('MAGIC='.__DIR__.(WINDOWS_OS?'/win32':'').'/magic.mime'); } # /usr/share/misc/magic

\Poodle\PHP\INI::init();

// PHP 5.6.6
if (!defined('JSON_PRESERVE_ZERO_FRACTION')) {
	define('JSON_PRESERVE_ZERO_FRACTION', 0);
}

if (!isset($_SERVER['REQUEST_SCHEME'])) { $_SERVER['REQUEST_SCHEME'] = isset($_SERVER['HTTPS']) ? 'https' : 'http'; }
if (!isset($_SERVER['HTTP_ACCEPT'])) { $_SERVER['HTTP_ACCEPT'] = '*/*'; }
if (empty($_SERVER['SERVER_PROTOCOL'])) { $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0'; }
if (isset($_SERVER['REDIRECT_STATUS']) && 200 == $_SERVER['REDIRECT_STATUS']) {
	if (empty($_SERVER['PATH_INFO']) && isset($_SERVER['REDIRECT_PATH_INFO'])) {
		$_SERVER['PATH_INFO'] = $_SERVER['REDIRECT_PATH_INFO'];
	}
	unset($_SERVER['REDIRECT_PATH_INFO']);
	unset($_SERVER['REDIRECT_STATUS']);
	unset($_SERVER['REDIRECT_QUERY_STRING']);
	unset($_SERVER['REDIRECT_URL']);
}
if (empty($_SERVER['HTTP_HOST'])) { $_SERVER['HTTP_HOST'] = (empty($_SERVER['SERVER_NAME']) ? '127.0.0.1' : $_SERVER['SERVER_NAME']); }
if (empty($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO'])) { $_SERVER['PATH_INFO'] = str_replace($_SERVER['PHP_SELF'],'',$_SERVER['ORIG_PATH_INFO']); } // cgi.fix_pathinfo=1
//if (empty($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO'])) { $_SERVER['PATH_INFO'] = str_replace($_SERVER['SCRIPT_NAME'],'',$_SERVER['ORIG_PATH_INFO']); } // cgi.fix_pathinfo=1
if (empty($_SERVER['PATH_INFO'])) { $_SERVER['PATH_INFO'] = !empty($_GET['PATH_INFO']) ? $_GET['PATH_INFO'] : '/'; }
unset($_SERVER['PATH_TRANSLATED']); # it's incorrect
unset($_GET['PATH_INFO']);
/*
// DOCUMENT_ROOT fix for IIS Webserver
if (empty($_SERVER['DOCUMENT_ROOT'])) {
	if (isset($_SERVER['SCRIPT_FILENAME'])) {
		$_SERVER['DOCUMENT_ROOT'] = strtr(substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])), '\\', '/');
	} else if (isset($_SERVER['PATH_TRANSLATED'])) {
		$_SERVER['DOCUMENT_ROOT'] = strtr(substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])), '\\', '/');
	} else {
		// define here your DOCUMENT_ROOT path if the previous fails (e.g. '/var/www')
		$_SERVER['DOCUMENT_ROOT'] = '/';
	}
}
*/
# Poodle entries
$_SERVER['HTTP_SEARCH_QUERY'] = '';
if (!empty($_GET['q'])) {
	$_SERVER['HTTP_SEARCH_QUERY'] = urldecode($_GET['q']);
} else if (!empty($_SERVER['HTTP_REFERER']) && preg_match('#[\?&](p|q|query|text)=([^&]+)#', $_SERVER['HTTP_REFERER'], $path)) {
	$_SERVER['HTTP_SEARCH_QUERY'] = urldecode($path[2]);
	$_SERVER['HTTP_REFERER'] = preg_replace('#^([^\?;]+).*$#D', '$1', $_SERVER['HTTP_REFERER']).'?'.$path[1].'='.$path[2];
}
$_SERVER['SERVER_MOD_REWRITE'] = !empty($_SERVER['SERVER_MOD_REWRITE']) || !empty($_SERVER['REDIRECT_SERVER_MOD_REWRITE']) || !empty($_SERVER['HTTP_X_SERVER_MOD_REWRITE']);
unset($_SERVER['REDIRECT_SERVER_MOD_REWRITE']);

# Harden PHP
unset($HTTP_RAW_POST_DATA);
$_REQUEST = array();
//$_GET = new \Poodle\Input\GET($_GET);
if ($_POST || 'POST' === $_SERVER['REQUEST_METHOD']) {
	if (!$_POST && !$_FILES) {
		$fp = fopen('php://input','r');
		if ($fp && fread($fp,1024)) {
			\Poodle\HTTP\Status::set(400); // Bad Request
			\Poodle\HTTP\Status::set(413); // Request Entity Too Large
			exit('POST data exceeds post_max_size: '.\Poodle\PHP\INI::get('post_max_size'));
		}
	}
	$_POST = new \Poodle\Input\POST($_POST);
	if ($_FILES) { $_FILES = new \Poodle\Input\FILES($_FILES); }
}

#
# Let's configure the system
#

// IPv4 | IPv6 loopback | IPv6 link-local | IPv6 ULA
if (!isset($_SERVER['SERVER_ADDR']) && isset($_SERVER['LOCAL_ADDR'])) $_SERVER['SERVER_ADDR'] = $_SERVER['LOCAL_ADDR'];
if (preg_match('#^(10|127.0.0|172.(1[6-9]|2\d|3[0-1])|192\.168|::1[:$]|fe80:|fc00:)#', $_SERVER['SERVER_ADDR'])
 || preg_match('#^(10|127.0.0|172.(1[6-9]|2\d|3[0-1])|192\.168|::1[:$]|fe80:|fc00:)#', $_SERVER['REMOTE_ADDR']))
{               #  ^^ 1000::/8
	\Poodle::$DEBUG = \Poodle::DBG_ALL; // not what you want for intranets
}

\Poodle::$URI_INDEX = \Poodle::$URI_ADMIN = $_SERVER['SCRIPT_NAME'];
\Poodle::$URI_ADMIN = uri_dirname(\Poodle::$URI_ADMIN).'/admin/index.php';
\Poodle::$URI_BASE  = uri_dirname(\Poodle::$URI_INDEX);
\Poodle::$URI_MEDIA = \Poodle::$URI_BASE.'/media';
if ($_SERVER['SERVER_MOD_REWRITE']) {
	\Poodle::$URI_ADMIN = uri_dirname(\Poodle::$URI_ADMIN);
	\Poodle::$URI_INDEX = uri_dirname(\Poodle::$URI_INDEX);
	$_SERVER['PHP_SELF'] = preg_replace("#^{$_SERVER['SCRIPT_NAME']}/*#",uri_dirname($_SERVER['SCRIPT_NAME']).'/',$_SERVER['PHP_SELF']);
}

$path = strpos($_SERVER['REQUEST_URI'], '?');
$_SERVER['REQUEST_PATH'] = $path ? substr($_SERVER['REQUEST_URI'], 0, $path) : $_SERVER['REQUEST_URI'];

# tools.ietf.org/html/rfc2616#section-3.9
\Poodle::$UA_LANGUAGES = (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])
	? ''
	: preg_replace('#;q=(?!0\\.)([0-9]*)\\.?#', ';q=0.$1',strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'])));
/*
if ('/' !== $_SERVER['PATH_INFO']) {
	# Detect and strip ISO 639-1 language name + optional ISO-3166-1 country code (RFC 1766)
	if (preg_match('#^/([a-z]{2}(?:-[a-z]{2})?)(/.*)?$#D', $_SERVER['PATH_INFO'], $path)) {
		if (empty($path[2])) {
			\Poodle\HTTP\Headers::setLocation($path[1].'/'.(empty($_SERVER['QUERY_STRING'])?'':'?'.$_SERVER['QUERY_STRING']), 301);
			exit;
		}
		\Poodle::$UA_LANGUAGES = $path[1].';q=9,'.\Poodle::$UA_LANGUAGES;
		$_SERVER['PATH_INFO'] = $path[2];
	}

	# Detect output extension and split directories
	if ('/' !== $_SERVER['PATH_INFO']) {
		if (preg_match('#^(/.+)(?:/|\.([a-z0-9]+))$#D', $_SERVER['PATH_INFO'], $path)) {
			$_SERVER['PATH_INFO'] = $path[1];
			if (empty($path[2])) {
				$_SERVER['PATH_INFO'] .= '/';
			} else {
				\Poodle::$EXT = $path[2];
			}
		}
	}
}
*/
\Poodle::$PATH = new \Poodle\HTTP\PathInfo($_SERVER['PATH_INFO']);

unset($path);
