<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\HTTP;

class PathInfo implements \ArrayAccess, \Countable, \Iterator
{
	protected $path;
	function __construct($path)
	{
		$this->path = explode('/', substr($path, 1));
//		$this->path = explode('/', trim($path, '/'));
	}
	function __toString()     { return '/'.implode('/',$this->path); }
	# Iterator
	public function key()     { return key($this->path); }
	public function current() { return current($this->path); }
	public function next()    { return next($this->path); }
	public function rewind()  { return reset($this->path); }
	public function valid()   { return (null !== key($this->path)); }
	# ArrayAccess
	public function offsetExists($i) { return array_key_exists($i, $this->path); }
	public function offsetGet($i)    { return $this->offsetExists($i) ? $this->path[$i] : null; }
	public function offsetSet($i,$v) {}
	public function offsetUnset($i)  {}
	# Countable
	public function count()   { return is_countable($this->path) ? count($this->path) : 0; }

	public function getArrayCopy() { return $this->path; }
}
