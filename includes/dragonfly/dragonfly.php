<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

require_once(dirname(__DIR__).'/poodle/bootstrap.php');

class Dragonfly extends Poodle
{
	const
		VERSION = '10.0.42.9360',
		DB_VERSION = 20180120;

	public static function isDemo()
	{
		return CPGN_DEMO || (!empty($_SESSION['DF_VISITOR']->admin) && $_SESSION['DF_VISITOR']->admin->isDemo());
	}

	public static function isLocal()
	{
		// IPv4 | IPv6 loopback | IPv6 link-local | IPv6 ULA
		if (!isset($_SERVER['SERVER_ADDR']) && isset($_SERVER['LOCAL_ADDR'])) $_SERVER['SERVER_ADDR'] = $_SERVER['LOCAL_ADDR'];
		return preg_match('#^(10|127.0.0|172.(1[6-9]|2\d|3[0-1])|192\.168|::1[:$]|fe80:|fc00:)#', $_SERVER['SERVER_ADDR'])
			||   preg_match('#^(10|127.0.0|172.(1[6-9]|2\d|3[0-1])|192\.168|::1[:$]|fe80:|fc00:)#', $_SERVER['REMOTE_ADDR']);
	}

	// Detects if module is inside a Phar
	public static function getModulePath($name)
	{
		$path = MODULE_PATH.$name;
		if (strtoupper($name[0]) !== $name[0] && !is_dir($path) && !is_file($path.'.phar')) {
			$name = ucfirst($name);
		}
		$K = \Dragonfly::getKernel();
		if (!$K || !$K->CFG || empty($K->CFG->global->phar_modules)) {
			return MODULE_PATH.$name.'/';
		}
		static $paths = array();
		if (!isset($paths[$name])) {
			$path = MODULE_PATH.$name;
			if (is_file($path.'.phar') && is_file($path.'.phar.pubkey')) {
				$dir = 'phar://'.$path.'.phar/modules/'.$name;
				if (is_dir($dir)) {
					$path = $dir;
				}
			}
			$paths[$name] = $path.'/';
		}
		return $paths[$name];
	}

}

/**
 * @deprecated v9 class
 */
abstract class PHP extends \Poodle\PHP\INI
{

	public static function init()
	{
		# http://bugs.php.net/bug.php?id=31849
		if (WINDOWS_OS || !function_exists('posix_getpwuid')) {
			define('_PROCESS_UID', 0);
			define('_PROCESS_OWNER', 'nobody');
		} else {
			define('_PROCESS_UID', posix_geteuid());
			$processUser = posix_getpwuid(_PROCESS_UID);
			define('_PROCESS_OWNER', $processUser['name']);
		}

		umask((
			_PROCESS_UID < 100 // Linux statically allocated system users
			// read /etc/login.defs for UID_MIN?
//			_PROCESS_UID < 1000 // Linux dynamically allocated
			|| _PROCESS_UID != getmyuid()
			|| _PROCESS_UID != fileowner(__FILE__)
			|| preg_match('#^(www-data|nobody|apache)$#D', _PROCESS_OWNER)
		) ? 0 : 0022);

		// Needs to register to DF on BOOT_DOWN
		$settings = array(
			'default_socket_timeout' => 7,
			'display_startup_errors' => DF_MODE_DEVELOPER, // should work in cgi
			'mysql.connect_timeout' => 7,
		);
		if (POODLE_CLI) {
			$settings['display_errors'] = DF_MODE_DEVELOPER ? 'stderr' : 0;
		}
		foreach ($settings as $cfg => $val) {
			parent::set($cfg, $val);
		}
		//self::$version > 53 ini_set('mail.log', ''); // very cool, also verifing mailing lists?
	}

	# return false on failure, mixed on success
	# also saves config changes to rollback
	public static function set($cfg, $val)
	{
		trigger_deprecated("Use \\Poodle\\PHP\\INI::set({$cfg}, \$val)");
		return parent::set($cfg, $val);
	}

	public static function get($cfg, $def = null)
	{
		trigger_deprecated("Use \\Poodle\\PHP\\INI::get({$cfg})");
		$val = trim(parent::get($cfg, $def));
		# "", "0", null, "Off"
		if (!$val || 'off' == strtolower($val)) return false;
		# "1", true, "On"
		if (1 == $val || 'on' == strtolower($val)) return true;
		# digits
		if (ctype_digit($val)) return (int)$val;
		# strings and floats
		return $val;
	}

	public static function restore()
	{
		trigger_deprecated('Use \\Poodle\\PHP\\INI::restore()');
		parent::restore();
	}

}

PHP::init();
