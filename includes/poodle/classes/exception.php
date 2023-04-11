<?php

namespace Poodle;

abstract class Exception extends \Exception
{
	function __construct($message = null, $code = 0)
	{
		# make sure everything is assigned properly
		parent::__construct($message, $code);
		# skip LIB class
		$class = preg_replace('#Poodle\\\\((.+)\\\\)?Exception#i','$2',get_class($this));
		if (!$class) { return; }
		$tmp = $this->getTrace();
		$count = count($tmp);
		for ($i=0; $i<$count; ++$i) {
			if (isset($tmp[$i]['file'], $tmp[$i]['line'])) {
				$this->file = $tmp[$i]['file'];
				$this->line = $tmp[$i]['line'];
				if (empty($tmp[$i+1]) || empty($tmp[$i+1]['class']) || false === stripos($tmp[$i+1]['class'],$class)) { break; }
			}
		}
	}
}
