<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Kernels;

// PHP 5.3.2 on destruct Fatal error:  Class 'Poodle\Events\Event' not found in /poodle/events/events.php on line 52
class_exists('Poodle\\Events\\Event');

#
# The kernel class
#

class Dragonfly extends \Poodle
{
	use \Poodle\Events;

	public
		$mlf      = 'html',           

//		$CFG      = null,
//		$L10N     = null,
//		$OUT      = null,
		$RESOURCE = null,
//		$SESSION  = null,
//		$SQL      = null,

		$host,
		$path     = '/',
		$base_uri = '/',

		$cookie_domain = null,

		$domains  = null;

	protected
		$_readonly_data = array(
			'db_user_prefix' => '',
			'auth_realm'  => 'My Website',
			'cache_dir'   => null,
			'cache_uri'   => null,
			'design_mode' => false,
			'dbms' => array('adapter'=>'', 'tbl_prefix'=>'', 'master'=>array(), 'slave'=>array()),
		);

	private static $CACHE_URI;

	function __construct(array $cfg)
	{
		if (!$cfg)
		{
			\Poodle\HTTP\Headers::setStatus(503);
			exit('The URI that you requested, is temporarily unavailable due to maintenance on the server.');
		}

//		\Poodle\Debugger::start();

		if (\Poodle::$EXT) { $this->mlf = \Poodle::$EXT; }
		if (!isset($cfg['dbms']['slave'])) { $cfg['dbms']['slave'] = array(); }
		$this->_readonly_data = array_merge($this->_readonly_data, $cfg);

		\Poodle\PHP\INI::set('user_agent', 'Dragonfly/'.self::VERSION.' ('.PHP_OS.'; '.PHP_SAPI.'; +http://'.$_SERVER['HTTP_HOST'].'/)');
/*		if (\Poodle::$DEBUG & \Poodle::DBG_PHP) {
			\Poodle\PHP\INI::set('docref_root', 'http://php.net/');
			\Poodle\PHP\INI::set('html_errors', 1);
		}*/

		# Set server defaults
		$this->host = $_SERVER['HTTP_HOST'];
		$this->path = dirname($_SERVER['SCRIPT_NAME']);
		if (strlen($this->path)>1) $this->path .= '/';
		$this->base_uri  = $this->path;
		if (!\Poodle\PHP\INI::enabled('allow_url_fopen', false) || \Poodle\PHP\INI::enabled('allow_url_include'))
		{
			# Force allow_url_fopen=on and allow_url_include=off
			stream_wrapper_unregister('ftp');
			stream_wrapper_unregister('ftps');
			stream_wrapper_unregister('http');
			stream_wrapper_unregister('https');
			stream_wrapper_register('http',  'Poodle\\Stream\\Wrapper\\HTTP');
			stream_wrapper_register('https', 'Poodle\\Stream\\Wrapper\\HTTP');
		}

		self::$CACHE_URI = $cfg['cache_uri'];

		register_shutdown_function(array($this, 'onDestroy'));
	}

	function __destruct() { if (property_exists($this, '_readonly_data')) { self::onDestroy(); } }
	private static $destroyed = false;
	public function onDestroy()
	{
		if (!self::$destroyed) {
			try {
				$this->triggerEvent('destroy');
			} catch (\Exception $e) { } # skip

			try {
				if (isset($this->SESSION)) {
					$this->SESSION->write_close();
					unset($this->SESSION);
				}
			} catch (\Exception $e) { } # skip

			foreach (array_keys(get_object_vars($this)) as $val) {
				$this->$val = null;
			}

			self::$destroyed = true;
		}
	}

	function __get($key)
	{
		if ('IDENTITY'===$key) {
			if (!isset($this->IDENTITY)) {
				$this->IDENTITY = \Dragonfly\Identity::getCurrent();
			}
			return $this->IDENTITY;
		}

		if ('CACHE'===$key) {
			if (!isset($this->CACHE)) {
				$this->CACHE = \Poodle\Cache::factory(self::$CACHE_URI);
			}
			return $this->CACHE;
		}

		if ('OUT'===$key) {
			if (!isset($this->OUT)) {
				$this->OUT = new \Dragonfly\TPL\HTML();
			}
			return $this->OUT;
		}

		if ('L10N'===$key) {
			return $this->L10N = new \Dragonfly\L10N();
		}

		if ('CFG'===$key) {
			if (defined('DF_MODE_NOCFG')) return;
			return $this->CFG = \Dragonfly\Config::load();
		}

		if ('SESSION'===$key) {
			if (empty($this->CFG->session->handler)) {
				$this->CFG->set('session','handler','Dragonfly\\Session\\Handler\\Builtin');
			}
			return $this->SESSION = \Dragonfly\Session::factory($this->CFG->session);
		}

		if ('SQL'===$key) {
			if (defined('DF_MODE_NOSQL')) return;
			$this->connect_db();
			return $this->SQL;
		}

		if ('DEBUGGER'===$key) {
			if (!isset($this->DEBUGGER)) {
				if (isset($GLOBALS['Debugger'])) { $this->DEBUGGER = $GLOBALS['Debugger']; }
				else { $this->DEBUGGER = new \Dragonfly\Debugger(); }

			}
			return $this->DEBUGGER;
		}

		if (isset($this->_readonly_data[$key])) { return $this->_readonly_data[$key]; }
		$tmp = debug_backtrace();
		trigger_error("Undefined property: {$key} by: {$tmp[0]['file']}#{$tmp[0]['line']}");
		return null;
	}

	function __set($key, $val)
	{
		if (array_key_exists($key, $this->_readonly_data)) {
			throw new \Exception('Disallowed to set property: '.$key);
		}
		$this->$key = $val;
	}

	function __isset($key)
	{
		return (property_exists($this, $key) && isset($this->$key)) || isset($this->_readonly_data[$key]);
	}

	public function extend($name, $class) {
		$capitols = strtoupper($name);
		if (!isset($this->$capitols) && class_exists($class)) $this->$capitols = new $class();
		$implements = class_implements($this->$capitols);
		if (isset($implements['DFService'])) $GLOBALS['DF']->attach($name);
		return $this->$name;
	}

	public function connect_db()
	{
		static $dbms = null;
		if (isset($this->SQL) && $this->SQL->ping()) { return false; }
		if (null === $dbms) {
			$dbms = $this->_readonly_data['dbms'];
			unset($this->_readonly_data['dbms']);
		}
		$this->SQL = new \Dragonfly\SQL($dbms['adapter'], $dbms['master'], $dbms['tbl_prefix'], $dbms['slave']);
		$this->SQL->debug = \Poodle::$DEBUG & \Poodle::DBG_SQL | \Poodle::$DEBUG & \Poodle::DBG_SQL_QUERIES;
//		if (!isset($this->SQL->TBL->config)) { \Poodle\Report::error(503); }
	}

	# start output buffering
	public static function ob_start() {}

	public function setCookie($name, $value='', $expire=-1)
	{
		return setcookie($name, $value, ['expires' => $expire, 'path' => $this->base_uri, 'domain' => $this->cookie_domain?$this->cookie_domain:null, 'secure' => false]);
	}

	public function run()
	{
		# Load database
		$this->connect_db();
	}
}

class_alias('Dragonfly\\L10N', 'Poodle\\L10N');
