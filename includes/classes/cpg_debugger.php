<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/cpg_debugger.php,v $
  $Revision: 9.5 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:41 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
if (!defined('E_STRICT')) { define('E_STRICT', 2048); }
if (!defined('E_RECOVERABLE_ERROR')) { define('E_RECOVERABLE_ERROR', 4096); }

if (PHPVERS < 43) {
function cpg_error_handler($errno, $errmsg, $filename, $linenum, $vars='') {
	global $cpgdebugger;
	$cpgdebugger->handler($errno, $errmsg, $filename, $linenum, $vars);
}}

class cpg_debugger {
	// Define variables that store the old error reporting and logging states
	public $old_handler;
	public $old_display_level;
	public $old_error_logging;
	public $old_error_log;

	public $logfile;
	public $report;
	public $active = false;
	public $error_level;

	function __construct($log = 'debug.log') {
		$this->logfile = $log;
	}

	function start() {
		if (!$this->active) {
			$this->report = false;
			if (CAN_MOD_INI) {
				$this->old_display_level = ini_set('display_errors', 1);
				$this->old_error_logging = ini_set('log_errors', 0);
			}
			if (PHPVERS < 43) {
				$this->old_handler = set_error_handler('cpg_error_handler');
			} else {
				$this->old_handler = set_error_handler(array(&$this, 'handler'));
			}
//			$this->old_error_log = ini_set('error_log', $this->logfile);
			$this->error_level = E_ALL;
			$this->active = true;
		}
	}

	function stop() {
		if ($this->active) {
			// restore the previous state
			if (!is_bool($this->old_handler) && $this->old_handler) set_error_handler($this->old_handler);
			if (CAN_MOD_INI) {
				ini_set('display_errors', $this->old_display_level);
				ini_set('log_errors', $this->old_error_logging);
//				ini_set('error_log', $this->old_error_log);
			}
			$this->active = false;
			return $this->report;
		}
	}

	// user defined error handling function
	function handler($errno, $errmsg, $filename, $linenum, $vars='')
	{
//		$errmsg = utf8_encode($errmsg);
		$errortype = array (
//			E_ERROR           => 'Error',
			E_WARNING         => 'Warning',
//			  E_PARSE           => 'Parsing Error',
			E_NOTICE          => 'Notice',
			E_CORE_ERROR      => 'Core Error',
			E_CORE_WARNING    => 'Core Warning',
			E_COMPILE_ERROR   => 'Compile Error',
			E_COMPILE_WARNING => 'Compile Warning',
			E_USER_ERROR      => 'CMS Error',
			E_USER_WARNING    => 'CMS Warning',
			E_USER_NOTICE     => 'CMS Notice',
			E_STRICT          => 'Runtime Notice',
			E_RECOVERABLE_ERROR => 'Error'
		);
		// NOTE: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR and E_COMPILE_WARNING
		// error levels will be handled as per the error_reporting settings.
		if ($errno == E_USER_ERROR) {
			if (is_admin()) {
				cpg_error($errortype[$errno]." $filename line $linenum: ".$errmsg);
			} else {
				cpg_error("A error occured while processing this page.<br />Please report the following error to the owner of this website.<br /><br /><b>$errmsg</b>");
			}
		}

		// set of errors for which a trace will be saved
		if ((CPG_DEBUG || is_admin()) && $errno & $this->error_level) {
			
			if (preg_match('#mysql_#m', $errmsg)) {
				global $db;
				$filename = $db->file;
				$linenum = $db->line;
			}
			if ($errno & $this->error_level != null) {
			$this->report[$filename][] = $errortype[$errno]." line $linenum: ".$errmsg;
			}
		}

		// save to the error log
		// error_log($err, 0); //message is sent to PHP's system logger
		// error_log($err, 1, 'operator@example.com'); //message is sent by email to the address in the destination
		// error_log($err, 3, $this->logfile); //message is appended to the file destination.
	}
}

error_reporting(E_ALL);
$cpgdebugger = new cpg_debugger();
$cpgdebugger->start();
