<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Math;

abstract class BcMath extends Base implements MathInterface
{
	const ENGINE = 'bcmath';

	public static function add($l, $r, $d=14) { return static::doMath('add', $l, $r, $d); }
	public static function cmp($l, $r, $d=14) { return bccomp(static::getValid($l), static::getValid($r), $d); }
	public static function div($l, $r, $d=14) { return static::doMath('div', $l, $r, $d); }
	public static function mod($l, $m)        { return bcmod(static::getValid($l), static::getValid($m)); }
	public static function mul($l, $r, $d=14) { return static::doMath('mul', $l, $r, $d); }
	public static function pow($l, $r, $d=14) { return static::doMath('pow', $l, $r, $d); }
	public static function powmod($l, $r, $m, $d=14) { return static::doMath('powmod', $l, $r, $m, $d); }
	public static function sqrt($o, $d=14)    { return static::doMath('sqrt', $o, $d); }
	public static function sub($l, $r, $d=14) { return static::doMath('sub', $l, $r, $d); }

	protected static function doMath()
	{
		$args = func_get_args();
		$fn = 'bc'.array_shift($args);
		foreach ($args as $i => $v) { $args[$i] = static::getValid($v); }
		return static::getValid(call_user_func_array($fn, $args));
	}
}
