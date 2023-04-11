<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Math;

abstract class Base
{
	public static function longToBinary($long)
	{
		$cmp = static::cmp($long, '0');
		if ($cmp < 0) {
			trigger_error('Poodle\\Math::longToBinary takes only positive integers.', E_USER_ERROR);
			return null;
		}
		if (0 == $cmp) { return "\x00"; }

		$bytes = array();
		while (static::cmp($long, 0) > 0) {
			array_unshift($bytes, static::mod($long, 256));
			$long = static::div($long, pow(2, 8));
		}
		if ($bytes && ($bytes[0] > 127)) { array_unshift($bytes, 0); }

		$string = '';
		foreach ($bytes as $byte) { $string .= pack('C', $byte); }
		return $string;
	}

	public static function binaryToLong($str)
	{
		if (null === $str) { return null; }
		# Use array_merge to return a zero-indexed array instead of a one-indexed array.
		$bytes = array_merge(unpack('C*', $str));
		if ($bytes && ($bytes[0] > 127)) {
			trigger_error('Poodle\\Math::bytesToNum works only for positive integers.', E_USER_WARNING);
			return null;
		}
		$n = '0';
		foreach ($bytes as $byte) { $n = static::add(static::mul($n, pow(2, 8)), $byte); }
		return $n;
	}

	public static function base64ToLong($str)
	{
		$b64 = base64_decode($str);
		return (false === $b64) ? false : static::binaryToLong($b64);
	}

	public static function longToBase64($str)
	{
		return base64_encode(static::longToBinary($str));
	}

	public static function rand($stop)
	{
		static $duplicate_cache = array();

		// Used as the key for the duplicate cache
		$rbytes = static::longToBinary($stop);

		if (array_key_exists($rbytes, $duplicate_cache)) {
			list($duplicate, $nbytes) = $duplicate_cache[$rbytes];
		} else {
			$nbytes = strlen($rbytes);
			if ("\x00" === $rbytes[0]) {
				--$nbytes;
			}
			$mxrand = static::pow(256, $nbytes);
			# If we get a number less than this, then it is in the duplicated range.
			$duplicate = static::mod($mxrand, $stop);
			if (count($duplicate_cache) > 10) {
				$duplicate_cache = array();
			}
			$duplicate_cache[$rbytes] = array($duplicate, $nbytes);
		}

		do {
			$bytes = "\x00" . random_bytes($nbytes);
			$n = static::binaryToLong($bytes);
			// Keep looping if this value is in the low duplicated range
		} while (static::cmp($n, $duplicate) < 0);

		return static::mod($n, $stop);
	}

	public static function getValid($n)
	{
		if (is_float($n)) { $n = number_format($n,14,'.',''); }
		if (preg_match('/^([-+]?\\d+)(\\.\\d+)?$/',$n,$m)) { return $m[1].(isset($m[2])?rtrim($m[2], '0.'):''); }
		return false;
	}

	public static function getScale($l, $r)
	{
		if (!preg_match('/^[-+]?\d+(?:\.(\d+))?$/',$l,$ld)
		 || !preg_match('/^[-+]?\d+(?:\.(\d+))?$/',$l,$rd)) return '0';
		// remove ending zeroes
		$ld = isset($ld[1]) ? rtrim($ld[1],'0') : '';
		$rd = isset($rd[1]) ? rtrim($rd[1],'0') : '';
		return max(strlen($ld), strlen($rd));
	}
}

interface MathInterface
{
	public static function add($l, $r, $d=14);
	public static function cmp($l, $r, $d=14);
	public static function div($l, $r, $d=14);
	public static function mod($l, $m);
	public static function mul($l, $r, $d=14);
	public static function pow($l, $r, $d=14);
	public static function powmod($l, $r, $m, $d=14);
	public static function sqrt($o, $d=14);
	public static function sub($l, $r, $d=14);
}

if (extension_loaded('bcmath')) {
	class_alias('Poodle\\Math\\BcMath', 'Poodle\\Math');
} else if (extension_loaded('gmp')) {
	class_alias('Poodle\\Math\\GMP', 'Poodle\\Math');
} else {
	class_alias('Poodle\\Math\\PHP', 'Poodle\\Math');
}
