<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Debugger;

abstract class Exception
{
	protected static function fix_bin($s)
	{
		return str_replace('"','\\"', preg_replace_callback(
			'#([\x00-\x08\x0B\x0C\x0E-\x1F\x7F])#',
			function($m){return '\\x'.bin2hex($m[1]);},
			$s));
	}

	public static function process($e)
	{
		try {
			\Poodle\HTTP\Status::set(500);

			$K = \Poodle::getKernel();
			$class_name = get_class($e);
			if (!(\Poodle::$DEBUG & \Poodle::DBG_PHP) && $class_name === 'Poodle\\SQL\\Exception') {
				switch ($e->getQuery())
				{
				case \Poodle\SQL\Exception::NO_EXTENSION:
					exit($e->getMessage().' extension not loaded in PHP. Recompile PHP, edit php.ini or choose a different SQL layer.');
				case \Poodle\SQL\Exception::NO_CONNECTION:
					exit('The connection to the database server failed.');
				case \Poodle\SQL\Exception::NO_DATABASE:
					exit('It seems that the database doesn\'t exist.');
				}
			}

			$title = strtr($class_name, '_', ' ');
			if (\Poodle::$DEBUG & \Poodle::DBG_PHP || \Poodle::getKernel()->IDENTITY->isAdmin()) {
			/*
				echo $e->getTraceAsString();
			*/
				$code = $e->getCode();
				$msg = $e->getMessage();
				$trace = $e->getTrace();
				foreach ($trace as $i => $d) {
					if (isset($d['args'])) {
						foreach ($d['args'] as $a => $s) {
							switch (gettype($s))
							{
							case 'integer' :
							case 'double'  : break;
							case 'boolean' : $s = ($s ? 'true' : 'false'); break;
							case 'object'  : $s = '&'.get_class($s); break;
							case 'resource': $s = 'resource'; break;
							case 'NULL'    : $s = 'null'; break;
							case 'array'   : $s = self::fix_bin(print_r($s, 1)); break;
							case 'string'  : $s = '"'.self::fix_bin($s).'"'; break;
							}
							$trace[$i]['args'][$a] = $s;
						}
					}
				}
				\Poodle\Report::error($title, array('msg' => htmlspecialchars($msg), 'trace' => $trace));
			} else {
				$trace = $e->getTrace();
				$trace = $trace[0]['file'].' @ '.$trace[0]['line']."\n".$trace[0]['class'].$trace[0]['type'].$trace[0]['function'].'(';
				if (!empty($trace[0]['args'])) { $trace .= '"'.implode('", "', $trace[0]['args']).'\"'; }
				$trace .= ")\n";
				try {
					\Poodle\LOG::error($e->getCode(), $trace.$e->getMessage());
				}
				catch (\Throwable $e) {}
				catch (\Exception $e) {}
				\Poodle\Report::error($title, 'An exception occured while processing this page. We have logged this error and will fix it when needed.');
			}
		}
		catch (\Throwable $e) { exit($e->getMessage()); }
		catch (\Exception $error) { exit($e->getMessage()); }
	}
}
