<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Input;

class GET extends \Poodle\Input
{
	# ArrayAccess
	public function offsetGet($k) { return ($this->offsetExists($k) ? parent::offsetGet($k) : null); }

	# Poodle
	public function keys () { return array_keys(func_num_args() ? self::_get(func_get_args()) : $this->getArrayCopy()); }
	public function exist() { return !is_null(self::_get(func_get_args())); }
	public function bit  () { return !self::_get(func_get_args())?0:1; }
	public function bool () { $v = self::_get(func_get_args()); return ('false'!==$v && !empty($v)); }
	public function float() { return self::str2float(self::_get(func_get_args())); }
	public function int  () { $v = self::_get(func_get_args()); return (preg_match('#^-?\d+$#', $v) ? (int)$v : null); }
	public function map  () {
		$c = get_class($this);
		$v = self::_get(func_get_args());
		return is_array($v) ? new $c($v) : null;
	}
	public function raw  () { return self::_get(func_get_args()); }
	public function txt  () { return self::strip(self::_get(func_get_args())); }
	public function text () { return self::strip(self::_get(func_get_args())); }
	public function uint () { $v = self::_get(func_get_args()); return (ctype_digit((string)$v) ? (int)$v : null); }

	# HTML5 form fields
	public function date()          { return self::asDate(self::_get(func_get_args())); }
	public function datetime()      { return self::asDateTime(self::_get(func_get_args())); }
	public function datetime_local(){ return self::asDateTime(self::_get(func_get_args()), true); }
	public function email()         { $v = self::_get(func_get_args()); return (true===self::validateEmail($v) ? self::lcEmail($v) : null); }
	public function month()         { return self::asDateFromMonth(self::_get(func_get_args())); }
	public function week()          { return self::asDateFromWeek(self::_get(func_get_args())); }
	public function time()          { return self::asTime(self::_get(func_get_args())); }
	public function color()         { $v = self::_get(func_get_args()); return (preg_match('/^#([0-9A-F]{3}|[0-9A-F]{6}|rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})(?:\s*,\s*(0(?:\.[0-9]*)?))?\s*\)|hsla?\(\s*([0-2]?[0-9]{1,2}|3[0-5][0-9]|360)\s*,\s*([0-9]{1,2}|100)%\s*,\s*([0-9]{1,2}|100)%(?:\s*,\s*(0(?:\.[0-9]*)?))?\s*\))$/i', $v) ? $v : null); }
	public function number()        { $v = self::_get(func_get_args()); return (false!==\Poodle\Math::getValid($v) ? new \Poodle\Number($v) : null); }
	public function range()         { $v = self::_get(func_get_args()); return (false!==\Poodle\Math::getValid($v) ? new \Poodle\Number($v) : null); }
	public function tel()           { return self::_get(func_get_args()); }
	public function url()           { $v = self::_get(func_get_args()); return (true===self::validateURI($v) ? $v : null); }

	protected function _get($args)
	{
		if (!$args) { return null; }
		$c = count($args);
		$v = $this;
		for ($i=0; $i<$c; ++$i)
		{
			$k = $args[$i];
			if (!is_string($k) && !is_int($k)) { throw new \InvalidArgumentException("Parameter {$i} is not a string or integer. Type is: ".gettype($k)); }
			if (!isset($v[$k])) { return null; }
			$v = $v[$k];
		}
		return $v;
	}

	public function __toString() { return \Poodle\URI::buildQuery($this); }
}
