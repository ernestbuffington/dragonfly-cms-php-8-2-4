<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Social;

class Extender extends \ArrayIterator
{
	//function __construct(array $array=array()) { foreach ($array as $k => $v) $this->$k = $v; }
	function __get($k)     { return $this->offsetGet($k); }
	function __isset($k)   { return $this->offsetExists($k); }
	function __unset($k)   { $this->offsetUnset($k); }
	function __set($k, $v) { $this->offsetSet($k, $v); }
}
