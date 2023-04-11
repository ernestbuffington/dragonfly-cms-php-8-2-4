<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	NOTE: we work with 14 decimals, because if bcmath isn't loaded the code
	converts the decimal to (float).
	The size of a float is platform-dependent, although a maximum of ~1.8e308
	with a double-precision of roughly 14 decimal digits is a common value.
*/

namespace Poodle\Math;

abstract class PHP extends Base implements MathInterface
{
	const ENGINE = null;

	protected static function shiftDecimal($v, $shift = 2)
	{
		if (!preg_match('/^([-+])?(\d+)(?:\.(\d+))?$/',$v,$v)) { return false; }
		$n = ('-' === $v[1]) ? '-' : '';
		$v[2] = str_pad('', $shift-1, '0').$v[2];
		if (!isset($v[3])) { $v[3] = ''; }
		return $n . intval(substr($v[2],0,-$shift)) . rtrim('.' . substr($v[2],-$shift).$v[3],'0.');
	}

	public static function add($l, $r, $d=14)
	{
		return static::shiftDecimal(static::getValid($l) * 100 + static::getValid($r) * 100);
	}

	public static function cmp($l, $r, $d=14)
	{
		return version_compare(static::getValid($l), static::getValid($r));
	}

	public static function div($l, $r, $d=14)
	{
		return static::shiftDecimal(static::getValid($l) * 100 / static::getValid($r));
	}

	public static function mod($l, $m)
	{
		$l = static::getValid($l);
		$m = static::getValid($m);
		$len = strlen(PHP_INT_MAX)-1;
		$mod = '';
		do {
			$l = $mod.$l;
			$mod = substr($l, 0, $len) % $m;
			$l = substr($l, $len);
		} while (strlen($l));
   		return $mod;
	}

	public static function mul($l, $r, $d=14)
	{
		return static::shiftDecimal(static::getValid($l) * 100 * static::getValid($r));
	}

	public static function sub($l, $r, $d=14)
	{
		return static::shiftDecimal(static::getValid($l) * 100 - static::getValid($r) * 100);
	}

	public static function pow($l, $r, $d=14)
	{
		return pow($l, $r);
		$a = 1;
		while ($r) {
			$a = static::mul($r, $l, $d);
			$r = static::sub($r, '1', $d);
		}
		return static::getValid($a);
	}

	public static function powmod($l, $r, $m, $d=14) { return static::mod(static::pow($l, $r, $d), $m); }

	public static function sqrt($o, $d=14) { return static::getValid(sqrt($o)); }

}
