<?php

namespace Poodle\SQL;

class Exception extends \Exception
{

	protected $query;
	public const
		NO_EXTENSION  = -1,
		NO_CONNECTION = 1,
		NO_DATABASE   = 2;

	# Redefine the exception so message isn't optional
	function __construct($message, $code=0, $query=null)
	{
		parent::__construct($message, $code);
		$this->query = $query;
	}

	final function getQuery() { return $this->query; }

	final function __toString()
	{
		$error = '<br /><b>A database error has occurred</b><br /><br />';
		if (DF_MODE_INSTALL || CPG_DEBUG || DF_MODE_DEVELOPER || (function_exists('is_admin') && is_admin())) {
			$error .= '<div style="text-align:left;margin-left:100px">Error code: ';
			$error .= $this->getCode().'<br />'.$this->getMessage().'<br /><br /><em>'.$this->getQuery().'</em><br />Called by ';
			$trace = debug_backtrace();
			for ($i=0, $c=count($trace); $i<$c; ++$i) {
				if (!isset($trace[$i]['file']) || false !== strpos($trace[$i]['file'], CORE_PATH.'poodle')) continue;
				$error .= "{$trace[$i]['file']} at line {$trace[$i]['line']}<br />";
				break;
			}
			$error .= '</div>';
		} else {
			$error .= 'The webmaster has been notified of the error</b><br />'.($mailer_message ?? '');
		}
		return $error;
	}

}
