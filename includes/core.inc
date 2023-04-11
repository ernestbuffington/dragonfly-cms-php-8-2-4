<?php
/*********************************************
  CPG DragonflyCMS, Copyright (c) 2011 by DragonflyCMS Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/**
	Notifies observers
**/
interface DFSubject /*extends SplSubject*/
{
	# Get a subject to include an observer
	public function attach($observer);
	# Get a subject to exclude an observer
	public function detach($observer);
	public function notify($subject=__CLASS__);
}

/**
	Be notified by subjects
**/
interface DFService /*extends SplObserver*/
{
	# Let the Subject knows your priority
	# 1 highest, 99 lowest. 1-10 and 90-99 are reserved.
	public function priority();
	public function runlevel();
	public function update();
	//public function initAs();
	//public function addSubject($subject);
}

class DF implements DFSubject
{

	const
		BOOT_DOWN    = 0, // register_shutdown_function
		BOOT_CORE    = 1, // filters-php-debugger-error-loader
		BOOT_BASE    = 2, // cmsinit.inc-db-session-maincfg-url-language-hooks
		BOOT_ETH     = 3, // client-security-networking-hooks
		BOOT_CUSTOM  = 4, // N/A
		BOOT_USER    = 5, // tpl-output-module-blocks-hooks
		BOOT_ERROR   = 6, // hooks-error-reboot(redirect)
		BOOT_INSTALL = 7,
		BOOT_HEADER  = 8;


	# the current working state (runlevel)
	private $current_state;

	# services container
	# group by runlevel
	# execute by priority
	private $services=array(
		DF::BOOT_DOWN   => array(),
		DF::BOOT_CORE   => array(),
		DF::BOOT_BASE   => array(),
		DF::BOOT_ETH    => array(),
		DF::BOOT_CUSTOM => array(),
		DF::BOOT_USER   => array(),
		DF::BOOT_ERROR  => array(),
		DF::BOOT_INSTALL => array()
	);

	public function runlevel() { return DF::BOOT_CORE; }

	public function setState($state)
	{
		$this->current_state = $state;
		$this->notify();
	}

	public function getState()
	{
		return $this->current_state;
	}

	public function attach($observer, $Object=null)
	{
		$toUpper = strtoupper($observer);
		if (class_exists('Dragonfly', false) && !($Object = Dragonfly::getKernel()->$toUpper)) {
			trigger_error("Use Dragonfly::getKernel()->extend() to attach.", E_USER_WARNING);
			return;
		} else if (!isset($Object) || !is_object($Object)) {
			trigger_error("Must be Objects.", E_USER_WARNING);
			return;
		}

		//$GLOBALS[$object]->addSubject = __CLASS__;
		$hash = spl_object_hash($Object);
		$priority = $Object->priority();
		$runlevel = $Object->runlevel();
		$runlevel = is_array($runlevel) ? $runlevel : array($runlevel);
		foreach ($runlevel as $i) {
			if (!isset($this->services[$i][$priority][$hash])) {
				$this->services[$i][$priority][$hash] = $observer;
				if ($i === $this->current_state) $Object->update();
		}}
		return true;
	}

	public function detach($observer)
	{
		$toUpper = strtoupper($observer);
		if (isset($GLOBALS[$observer])) $Object = $GLOBALS[$observer];
		else if (!($Object = Dragonfly::getKernel()->$toUpper)) {
			trigger_error("Could not found $observer.", E_USER_WARNING);
			return;
		}

		//$GLOBALS[$observer]->removeSubject = __CLASS__;
		$hash = spl_object_hash($Object);
		$priority = $Object->priority();
		$runlevel = $Object->runlevel();
		$runlevel = is_array($runlevel) ? $runlevel : array($runlevel);
		foreach ($runlevel as $i) {
			if (isset($this->services[$i][$priority][$hash])) {
				$Object->update();
				unset($this->services[$i][$priority][$hash]);
		}}
	}

	public function detachAll()
	{
		switch ($this->current_state)
		{
			case DF::BOOT_DOWN: break;
			default: $this->setState(DF::BOOT_ERROR);
		}
	}

	# DF will only notify about runlevel state changes
	public function notify($subject=__CLASS__)
	{
		$i=$this->current_state;
		$notified = array();
		if (empty($this->services[$i])) return;
		# notify from higher to lower priority ( 0 -> 100 )
		ksort($this->services[$i]);
		//$pdata['subject'] = $subject;
		//$pdata['state'] = $this->current_state;
		foreach ($this->services[$i] as $priority => $hash__class)
		{
			$hash = key($hash__class);
			$class = $hash__class[$hash];
			$toUpper = strtoupper($class);

			if (isset($GLOBALS[$class])) {
				$Object = $GLOBALS[$class];
			}
			else if (!($Object = Dragonfly::getKernel()->$toUpper)) {
				trigger_error("Could not found $observer.", E_USER_WARNING);
				continue;
			}

			# notify only once for the current runlevel
			if (!isset($notified[$hash])) {
				$notified[$hash] = $class;
				# when update() returns true propagation will be halted for the current level
				$Object->update();
			}
		}
	}

	# autoload() ads a few milliseconds on each call
	public static function autoLoad($name)
	{
		$name = ltrim($name,'\\');
		# avoids endless loops and resultant inproperly closed classes
/*
		if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#', $name)) {
			# PEAR bug and such
			return;
		}
*/
		# split class_name into segments where the
		# first segment is the library or component
		$path = explode(strpos($name, '\\') ? '\\' : '_', $name);
		if (!$path) return;

		// Try Dragonfly legacy class
		if (empty($path[1]) || !strpos($name, '\\')) {
			$file = CLASS_PATH.strtolower($name).'.php';
			if (is_file($file)) {
				include_once($file);
				return;
			}
			if (empty($path[1])) {
				return;
			}
		}

		// Try Dragonfly module
		if (!empty($path[3]) && 0 === stripos($name, 'Dragonfly\\Modules\\')) {
			$mpath = array_slice($path, 2);
			$file = \Dragonfly::getModulePath(array_shift($mpath)).strtolower(implode('/',$mpath)).'.php';
			if (is_file($file)) {
				include_once $file;
				return;
			}
		}

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
}

function between($val, $min, $max)
{
	return (int)$val >= (int)$min && (int)$val <= (int)$max;
}

function shortFilePath($str)
{
	if (class_exists('Poodle',0)) {
		$str = \Poodle::shortFilePath($str);
	}
	return str_replace(BASEDIR, '', $str);
}
