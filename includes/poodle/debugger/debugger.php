<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class Debugger
{
	private static
		$active,
		$report = array(),
		$display_errors = false,
		$last_error = array(
			'type' => 0,
			'message' => null,
			'file' => null,
			'line' => 0
		),

		# NOTE: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR
		#       and E_COMPILE_WARNING error levels will be handled as per
		#       the error_reporting settings. NOT thru this error handler.
		#       php.net/manual/en/errorfunc.constants.php
		$errortypes = array (
			E_WARNING           => 'Warning',
			E_NOTICE            => 'Notice',
			E_DEPRECATED        => 'Deprecated',      # 8192
			E_USER_ERROR        => 'WCMS Error',
			E_USER_WARNING      => 'WCMS Warning',
			E_USER_NOTICE       => 'WCMS Notice',
			E_USER_DEPRECATED   => 'WCMS Deprecated', # 16384
			E_STRICT            => 'Runtime Notice',
			E_RECOVERABLE_ERROR => 'Error',           # 4096
			32768      => 'Unknown 32768',
			65536      => 'Unknown 65536',
			131072     => 'Unknown 131072',
			262144     => 'Unknown 262144',
			524288     => 'Unknown 524288',
			1048576    => 'Unknown 1048576',
			2097152    => 'Unknown 2097152',
			4194304    => 'Unknown 4194304',
			8388608    => 'Unknown 8388608',
			16777216   => 'Unknown 16777216',
			33554432   => 'Unknown 33554432',
			67108864   => 'Unknown 67108864',
			134217428  => 'Unknown 134217428',
			268435456  => 'Unknown 268435456',
			536870912  => 'Unknown 536870912',
			1073741824 => 'Unknown 1073741824',
		);

	public static function clear()  { self::$report = array(); }

	public static function report() { ksort(self::$report); return self::$report; }

	public static function start()
	{
		if (self::$active) { return; }
		$callback = array(__CLASS__, 'error');
		set_error_handler($callback, 2147483402); # NO: E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING
		$callback[1] = 'exception';
		set_exception_handler($callback);
		self::$display_errors = \Poodle\PHP\INI::set('display_errors', 0);
		self::$active = true;
	}

	public static function stop()
	{
		if (!self::$active) { return; }
		# restore the previous state
		restore_error_handler();
		restore_exception_handler();
		if (false !== self::$display_errors) \Poodle\PHP\INI::set('display_errors', self::$display_errors);
		self::$active = false;
	}

	public static function displayPHPErrors()
	{
		return !!(self::$active ? self::$display_errors : \Poodle\PHP\INI::get('display_errors'));
	}

	/**
	 * Called by PHP when an Exception is thrown
	 * http://php.net/manual/en/language.exceptions.php
	 */
	public static function exception($e)
	{
		\Poodle\Debugger\Exception::process($e);
	}

	public static function getLastError()
	{
		return self::$last_error;
	}

	/**
	 * Called by PHP internal or through trigger_error()
	 */
	public static function error($errno, $errmsg, $filename, $linenum, $context=array())
	{
		if (!empty($GLOBALS['Debugger']) && $GLOBALS['Debugger'] instanceof \Dragonfly\Debugger) {
			$GLOBALS['Debugger']->error_handler($errno, $errmsg, $filename, $linenum, $context);
			return;
		}

		$errmsg   = \Poodle::shortFilePath($errmsg);
		$filename = \Poodle::shortFilePath($filename);

		self::$last_error = array(
			'type' => $errno,
			'message' => $errmsg,
			'file' => $filename,
			'line' => $linenum
		);

		if ((error_reporting() & $errno) && \Poodle\PHP\INI::get('log_errors')) {
			error_log(self::$errortypes[$errno].": {$errmsg} in {$filename} on line {$linenum}");
		}

		if (E_USER_ERROR === $errno) {
			# save to the error log
			\Poodle\LOG::error(0, $filename.' line '.$linenum.': '.$errmsg);
			if (self::identityIsAdmin()) {
				\Poodle\Report::error(self::$errortypes[$errno], $filename.' line '.$linenum.': '.$errmsg);
			} else {
				\Poodle\Report::error(self::$errortypes[$errno], 'An error occured while processing this page. We have logged this error and will fix it when needed.');
			}
		}

		# set of errors for which a trace will be saved
		if ((error_reporting() & $errno)
		 && (\Poodle::$DEBUG & \Poodle::DBG_PHP || self::identityIsAdmin()))
		{
			self::$report[$filename][self::$errortypes[$errno]][] = $errmsg.' on line '.$linenum;
		}
	}

	/**
	 * Alternative of trigger_error() to skip a part of the backtrace
	 */
	public static function trigger($errmsg, $skip=0, $errno=E_USER_WARNING)
	{
		if (!(\Poodle::$DEBUG & \Poodle::DBG_PHP) && !self::identityIsAdmin()) { return; }
		$skip_class = is_string($skip) && class_exists($skip,false);
		$bt = debug_backtrace($skip_class ? DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS : DEBUG_BACKTRACE_IGNORE_ARGS);
		$file = &$bt[0]['file'];
		$line = &$bt[0]['line'];
		$c = count($bt);
		for ($i=1; $i<$c; ++$i) {
			if ('eval' === $bt[$i]['function'] && 'tpl.php' === basename($bt[$i]['file'])) {
				if (empty($bt[$i+1]['object'])) { $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS); }
				$file = $bt[$i+1]['object']->tpl_file;
				$bt = null;
				break;
			}
		}
		if ($bt) {
			$c = count($bt);
			$i = 1;
			if (is_string($skip)) {
				$skip = array($skip);
			} else if (!is_array($skip)) {
				$i += $skip; $skip = array('\\/');
			}
			for (; $i<$c; ++$i) {
				if ($skip_class) {
					if (empty($bt[$i+1]['object']) || !($bt[$i+1]['object'] instanceof $skip[0])) {
						$file = &$bt[$i]['file'];
						$line = &$bt[$i]['line'];
						break;
					}
				} else if (!empty($bt[$i]['file'])
				 && false === strpos($bt[$i]['file'], (string) $skip[0])
				 && 'deprecated.php' !== basename($bt[$i]['file'])
				 && !in_array($bt[$i]['file'], $skip))
				{
					$file = &$bt[$i]['file'];
					$line = &$bt[$i]['line'];
					break;
				}
			}
		}
		if (self::$active) {
			self::error($errno, $errmsg, $file, $line);
		} else if (!empty($GLOBALS['Debugger']) && $GLOBALS['Debugger'] instanceof \Dragonfly\Debugger) {
			$GLOBALS['Debugger']->error_handler($errno, $errmsg, $file, $line);
		} else {
			trigger_error("$errmsg at $file#$line", $errno);
		}
	}

	protected static function identityIsAdmin()
	{
		$K = \Poodle::getKernel();
		return $K && $K->IDENTITY && $K->IDENTITY->isAdmin();
	}

}
