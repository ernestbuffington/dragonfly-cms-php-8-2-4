<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Social;

abstract class Adapter extends \ArrayIterator
{
	protected static
		$MAIN_CFG;

	public function init($name) {
		if (empty(self::$MAIN_CFG)) self::$MAIN_CFG = \Dragonfly::getKernel()->CFG;

		if (!isset(self::$MAIN_CFG->$name)) {
			foreach ($this->install as $k => $v) {
				self::$MAIN_CFG->add($name, $k, $v);
			}
		}
		if (isset(self::$MAIN_CFG->$name)) {
			foreach (self::$MAIN_CFG->$name as $k => $v) $this->$k = $v;
		}
	}

	public abstract function loadApi();
	public abstract function htmlHeadTags();
	public abstract function html5Button();

	function __get($k)
	{
		if ('install' === $k) return $this->install;
		return $this->offsetGet($k);
	}
	function __isset($k)   { return $this->offsetExists($k); }
	function __unset($k)   { $this->offsetUnset($k); }
	function __set($k, $v) { $this->offsetSet($k, $v); }
}
