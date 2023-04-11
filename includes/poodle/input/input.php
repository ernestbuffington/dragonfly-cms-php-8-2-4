<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class Input extends \ArrayIterator
{

	public function append($i) { throw new \BadMethodCallException(); }

	public static function fixEOL($str, $eol="\n") { return str_replace("\r\n", $eol, $str); }

	public static function fixSpaces($str) { return preg_replace('#\\p{Zs}#u', ' ', $str); }

	/**
	 * http://tools.ietf.org/html/rfc5321#section-4.1.2
	 * While the definition for Local-part is relatively permissive, for
	 * maximum interoperability, a host that expects to receive mail SHOULD
	 * avoid defining mailboxes where the Local-part requires (or uses) the
	 * Quoted-string form or where the Local-part is case-sensitive.
	 */
	public static function lcEmail($str)
	{
		return mb_strtolower(trim($str));
	}

	public static function asDate($v)
	{
		if (preg_match('#^([0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01]))#', $v, $m)) {
			return new \Poodle\Date($m[1]);
		}
		return null;
	}

	public static function asDateFromMonth($v)
	{
		if (preg_match('#^([0-9]{4}-(0[1-9]|1[0-2]))#', $v, $m)) {
			return new \Poodle\Date($m[1]);
		}
		return null;
	}

	public static function asDateFromWeek($v)
	{
		if (preg_match('#^([0-9]{4}-W([0-4][0-9]|5[0-3]))#', $v, $m)) {
			return new \Poodle\Date($m[1]);
		}
		return null;
	}

	// $local=true uses current date_default_timezone
	public static function asDateTime($v, $local=false)
	{
		if (preg_match('#^([0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])[T ]([01][0-9]|2[0-3]):[0-5][0-9](:([0-5][0-9]|60))?)#', $v, $m)) {
			return $local ? new \DateTime($m[1]) : new \Poodle\DateTime($m[1]);
		}
		return null;
	}

	public static function asTime($v)
	{
		if (preg_match('#(([01][0-9]|2[0-3]):[0-5][0-9](:([0-5][0-9]|60)(\\.[0-9]+)?)?)#', $v, $m)) {
			return new \Poodle\Time('1970-01-01 '.$m[1]);
		}
		return null;
	}

	public static function str2float($value, $def=null)
	{
		return preg_match('#-?[0-9]+(\\.[0-9]+)?$#D', $value)
			? (float)$value
			: $def;
	}

	public static function strip($value, $def='')
	{
		if (!is_string($value)) { return $value; }
		$value = self::fixEOL(trim(self::fixSpaces(strip_tags($value))));
		return strlen($value) ? $value : $def;
	}

	public static function validateEmail($v)
	{
		return \Dragonfly\Net\Validate::emailaddress($v);
	}

	public static function validateURI($v)
	{
		return \Dragonfly\Net\Validate::uri($v);
	}
/*
	protected function a2c($v)
	{
		$c = get_class($this);
		return is_array($v) ? new $c($v) : $v;
	}
	public function offsetGet($i) { return $this->a2c(parent::offsetGet($i)); }
	public function current() { return $this->a2c(parent::current()); }
*/
}
