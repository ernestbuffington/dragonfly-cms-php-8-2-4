<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\PHP;

abstract class INI
{
	private static
		$vars = null,
		$set  = false,
		$rollback = array();

	public static function get($var, $def=null)
	{
		if (!is_array(self::$vars)) { return $def; }
		# get_cfg_var() returns the value from php.ini, while ini_get() returns the runtime value
		return isset(self::$vars[$var]) ? self::$vars[$var] : ini_get($var);
	}

	public static function getBool($var, $def=null)
	{
		$val = trim(self::get($var, $def));
		return ($val && 'off' !== strtolower($val));
	}

	public static function getInt($var, $def=null)
	{
		$value = self::get($var, $def);
		$size = intval($value);
		switch (strtoupper(substr($value,-1))) {
			case 'G': $size *= 1024;
			case 'M': $size *= 1024;
			case 'K': $size *= 1024;
		}
		return $size;
	}

	public static function restore()
	{
		while ($var = array_pop(self::$rollback)) ini_restore($var);
	}

	public static function set($var, $val)
	{
		if (self::$set) {
			self::$rollback[$var] = $var;
			return ini_set($var, $val);
		}
		$r = self::get($var);
		self::$vars[$var] = $val;
		return $r;
	}

	public static function canSet() { return self::$set; }

	public static function enabled($var, $or_null=true)
	{
		$var = self::get($var);
		return (!empty($var) || (is_null($var) && $or_null));
	}

	public static function init()
	{
		if (!empty($_COOKIE['PoodleTimezone'])) {
			$_COOKIE['PoodleTimezone'] = str_replace('Etc/GMT ','Etc/GMT+',$_COOKIE['PoodleTimezone']);
		}
		if (empty($_COOKIE['PoodleTimezone']) || !date_default_timezone_set($_COOKIE['PoodleTimezone']))
		{
			unset($_COOKIE['PoodleTimezone']);
			date_default_timezone_set('UTC');
		}

		$err_level = error_reporting(0);

		# php.net/manual/en/outcontrol.configuration.php#ini.output-buffering
		\Poodle::ob_clean();

		# php.net/manual/en/outcontrol.configuration.php#ini.implicit-flush
		//ob_implicit_flush(0);

		if (function_exists('ini_get')) {
			self::$vars = array();
		} else {
			if (!function_exists('ini_get_all')) { return; }
			self::$vars = ini_get_all();
			foreach (self::$vars as &$v) { $v = $v['local_value']; }
			/**
				PHP_INI_USER    1 Entry can be set in user scripts
				PHP_INI_PERDIR  2 Entry can be set in php.ini, .htaccess or httpd.conf
				PHP_INI_SYSTEM  4 Entry can be set in php.ini or httpd.conf
				PHP_INI_??????  8
				PHP_INI_?????? 16
				PHP_INI_?????? 32
				PHP_INI_ALL    63
			*/
		}

		if (self::get('safe_mode')) { exit('safe_mode must be off, info: http://php.net/features.safe-mode'); }

		$fl = self::get('disable_functions');
		self::$set = (!is_null($fl) && false === stripos($fl, 'ini_set'));
		if (self::$set) {
			self::set('html_errors', 0);
			self::set('track_errors', 0);
			self::set('xmlrpc_errors', 0);
			self::set('log_errors_max_len', 0); # prevent half error messages
			self::set('ignore_repeated_errors', 1);

			self::set('implicit_flush', 0); # don't flush automatically
//			self::set('output_buffering', 0); # PHP_INI_PERDIR, failing Poodle ob_handler
//			self::set('output_handler', null); # PHP_INI_PERDIR
			self::set('zend.ze1_compatibility_mode', 0); # __clone bug
			self::set('zlib.output_compression', 0); # double compression
			self::set('zlib.output_handler', ''); # double compression

			self::set('default_charset', \Poodle::CHARSET);
			self::set('internal_encoding', \Poodle::CHARSET);
		}

		// Destroy session.auto_start
		if (session_status() === PHP_SESSION_ACTIVE) {
			$_SESSION = array();
			if (self::get('session.use_cookies')) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', -1, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
			}
			session_destroy();
		}

		error_reporting($err_level);

		// fix include_path
		$dirs = array_unique(explode(PATH_SEPARATOR, get_include_path()));
		foreach ($dirs as $i => $dir) {
			$dir = rtrim($dir,DIRECTORY_SEPARATOR);
			if (is_dir($dir)) {
				$dirs[$i] = $dir;
			} else {
				// directory doesn't exist and may cause errors
				// when open_basedir is configured
				trigger_error("include_path dir '{$dir}' not accessible");
				unset($dirs[$i]);
			}
		}
		set_include_path(implode(PATH_SEPARATOR, $dirs));

		if (!function_exists('mb_strlen')) { \Poodle\Unicode\MB::init(); }
		if (function_exists('mb_internal_encoding')) {
			mb_internal_encoding(\Poodle::CHARSET);
			mb_language('uni');
		}
		if (function_exists('iconv_set_encoding') && PHP_VERSION_ID < 50600) {
			iconv_set_encoding('input_encoding',    \Poodle::CHARSET);
			iconv_set_encoding('internal_encoding', \Poodle::CHARSET);
			iconv_set_encoding('output_encoding',   \Poodle::CHARSET);
		}
	}
}
