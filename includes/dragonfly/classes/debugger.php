<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/

namespace Dragonfly;

class Debugger implements \DFService
{
	# DFService
	//public static function initAs() { return 'Debugger'; }
	public function priority() { return 20; }
	public function runlevel() {
		return array(
			\DF::BOOT_ERROR,
			\DF::BOOT_INSTALL);
	}

	public function update() {
		switch ($GLOBALS['DF']->getState())
		{
			case \DF::BOOT_ERROR:
				$this->shiftToLogs();
				return;
			case \DF::BOOT_INSTALL:
				$this->shiftToLogs();
				return;
		}
		return;
	}

	# Dragonfly_Degubber
	private static
		$original_level,
		$always_on = 245, # E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING
		$active = false;

	private
		$log_level,
		$error_level,
		$logfile,
		$report,
		$errortype = array (
			E_ERROR           => 'PHP Error',               #     1
			E_WARNING         => 'PHP Warning',             #     2
			E_PARSE           => 'PHP Parse Error',         #     4
			E_NOTICE          => 'PHP Notice',              #     8
			E_CORE_ERROR      => 'PHP Core Error',          #    16
			E_CORE_WARNING    => 'PHP Core Warning',        #    32
			E_COMPILE_ERROR   => 'PHP Compile Error',       #    64
			E_COMPILE_WARNING => 'PHP Compile Warning',     #   128
			E_USER_ERROR      => 'CMS Error',               #   256
			E_USER_WARNING    => 'CMS Warning',             #   512
			E_USER_NOTICE     => 'CMS Notice',              #  1024
			E_STRICT          => 'PHP Strict Notice',       #  2048 PHP 5
			E_RECOVERABLE_ERROR => 'PHP Recoverable Error', #  4096 PHP 5.2
			E_DEPRECATED      => 'PHP Deprecated',          #  8192 PHP 5.3
			E_USER_DEPRECATED => 'CMS Deprecated',          # 16384 PHP 5.3
		);

	public function __construct()
	{
		if (self::$active) return $this;
		$this->logfile = DF_LOG_FILE;
		$this->report = array();
		set_error_handler(array(&$this, 'error_handler'));
		set_exception_handler(array(&$this, 'exception_handler'));

		self::$original_level = error_reporting(E_ALL | E_STRICT);
		$this->error_level = $this->log_level = error_reporting();
		self::$active = true;
	}

	public function __destruct()
	{
		error_reporting(self::$original_level);
		self::$active = false;
		restore_exception_handler();
		restore_error_handler();
	}

	public function __get($k)
	{
		switch ($k) {
			case 'error_level':
			case 'log_level':
			case 'report':
				return $this->$k;
		}
		return;
	}

	public function showDetails()
	{
		static $show;
		if (!isset($show)) {
			$show = defined('INSTALL')
			    || (defined('CPG_DEBUG') && CPG_DEBUG)
			    || (defined('DF_MODE_DEVELOPER') && DF_MODE_DEVELOPER)
			    || (function_exists('is_admin') && is_admin());
		}
		return $show;
	}
	# ?admin&op=settings&s=8
	# Shift "Display" settings into "Log".
	public function shiftToLogs($force=false)
	{
		if (!$force && !self::showDetails()) {
			self::setLogLevel($this->log_level | $this->error_level);
			self::setErrorLevel(0);
		}
	}

	public function setErrorLevel($new)
	{
		$old = $this->error_level;
		$this->error_level = (int)$new | self::$always_on;
		return $old;
	}

	public function setLogLevel($new)
	{
		$old = $this->log_level;
		$this->log_level = (int)$new | self::$always_on;
		return $old;
	}

	public function error_handler($errno, $errmsg, $file, $linenum, $vars=array())
	{
		$errmsg = shortFilePath(strip_tags($errmsg));
		$file = shortFilePath($file);
		# save error to buffer
		if ($this->error_level & $errno) {
			$this->report[$file][] = $this->errortype[$errno]." line $linenum: ".$errmsg;
		}
		# save error to file
		if ($this->log_level & $errno) {
			$err = $this->errortype[$errno]." $file line $linenum: $errmsg";
			error_log($err."\n", 3, $this->logfile);
		}
		if ($errno === E_USER_ERROR) {
			if (!function_exists('cpg_error') || 'HEAD' === $_SERVER['REQUEST_METHOD']) {
				\Dragonfly\Net\Http::headersFlush(500, $errmsg);
			}
			$errmsg = self::showDetails() ? "{$this->errortype[$errno]} $file line $linenum: $errmsg" : '';
			cpg_error($errmsg, 500);
		}
		return true;
	}

	public function exception_handler($e)
	{
		$file = $e->getFile();
		$line = $e->getLine();
		if (strpos($file,'/sql/')) {
			foreach ($e->getTrace() as $t) {
				if (!strpos($t['file'],'/sql/')) {
					$file = $t['file'];
					$line = $t['line'];
					break;
				}
			}
		}
		return $this->error_handler(E_USER_ERROR, $e->getMessage(), $file, $line, $e->getTrace());
	}

	public function get_report($type)
	{
		$debug = '';
		switch ($type) {

			case 'sql':
				global $db;
				if (!is_object($db) || empty($db->querylist)) { break; }
				$debug .= '<strong>SQL Queries:</strong><br /><br />';
				foreach ($db->querylist as $file => $queries) {
					$debug .= '<b>'.shortFilePath($file).'</b><ul>';
					foreach ($queries as $q) { $debug .= "<li>line {$q['line']} (".round($q['time']*1000,1)." ms): ".htmlspecialchars($q['query'])."</li>"; }
					$debug .= '</ul>';
				}
			break;

			case 'php':
				if (!is_array($this->report)) { break; }
				foreach ($this->report as $file => $errors) {
					$debug .= '<b>'.shortFilePath($file).'</b><ul>';
					$errors = array_map('shortFilePath', $errors);
					foreach ($errors as $error) { $debug .= "<li>$error</li>"; }
					$debug .= '</ul>';
				}
			break;

			default:
				return trigger_error('Function argument not valid', E_USER_WARNING);
			break;
		}
		return $debug;
	}

	private static function getBacktrace($offset)
	{
		$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		return $bt[max(2,intval($offset+1))];
	}

	public static function backtrace($offset = 0, $limit = 0, $options = DEBUG_BACKTRACE_IGNORE_ARGS)
	{
		return array_slice(debug_backtrace($options), $offset+1, $limit);
	}

	public static function warning($msg, $offset=1)
	{
		$bt = self::getBacktrace($offset);
		if (!empty($GLOBALS['Debugger']) && $GLOBALS['Debugger'] instanceof Debugger) {
			$GLOBALS['Debugger']->error_handler(E_USER_WARNING, $msg, $bt['file'], $bt['line']);
		} else {
			trigger_error(trim("{$msg} by {$bt['file']}#{$bt['line']}. {}"), E_USER_WARNING);
		}
	}

	public static function deprecated($msg='', $offset=1)
	{
		$bt = self::getBacktrace($offset);
		$fn = $bt['function'];
		if (!empty($bt['type'])) { $fn = $bt['class'].$bt['type'].$fn; }
		if (!empty($GLOBALS['Debugger']) && $GLOBALS['Debugger'] instanceof Debugger) {
			$GLOBALS['Debugger']->error_handler(E_USER_DEPRECATED, "DEPRECATED call to {$fn}(). {$msg}", $bt['file'], $bt['line']);
		} else {
			trigger_error(trim("DEPRECATED call to {$fn}() by {$bt['file']}#{$bt['line']}. {$msg}"), E_USER_DEPRECATED);
		}
	}
}
