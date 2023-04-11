<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Math;

abstract class GMP extends PHP
{
	const ENGINE = 'gmp';

	public static function add($l, $r, $d=14)
	{
		return static::gmp('add', $l, $r);
	}

	public static function div($l, $r, $d=14)
	{
		return static::gmp('div_qr', $l, $r);
	}

	public static function mul($l, $r, $d=14)
	{
		return static::gmp('mul', $l, $r);
	}

	public static function sub($l, $r, $d=14)
	{
		return static::gmp('sub', $l, $r);
	}

	protected static function gmp($fn, $l, $r)
	{
		if (!preg_match('/^([-+])?(\d+)(?:\.(\d+))?$/',$l,$l)
		 || !preg_match('/^([-+])?(\d+)(?:\.(\d+))?$/',$r,$r))
		{
			return false;
		}
		$l[1] = ('-' === $l[1] ? '-' : '') . ltrim($l[2],'0');
		$r[1] = ('-' === $r[1] ? '-' : '') . ltrim($r[2],'0');
		$d = 0;
		// Work with decimals?
		if (isset($l[3]) || isset($r[3])) {
			if (!isset($l[3])) { $l[3] = ''; }
			if (!isset($r[3])) { $r[3] = ''; }
			if ($d = max(strlen($l[3]), strlen($r[3]))) {
				$l[1] .= str_pad($l[3], $d, '0');
				$r[1] .= str_pad($r[3], $d, '0');
				if ('mul' === $fn) { $d *= 2; }
			}
		}
		$fn = "gmp_{$fn}";
		$v = $fn(gmp_init($l[1],10), gmp_init($r[1],10));
		if (is_array($v)) {
			return gmp_strval($v[0]) . '.' . gmp_strval($v[1]);
		}
		$v = gmp_strval($v);
		return $d ? static::shiftDecimal($v, $d) : $v;
	}

}
