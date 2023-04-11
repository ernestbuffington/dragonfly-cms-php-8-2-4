<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class Random
{
	/**
	 * The filename for a source of random bytes. Define this yourself
	 * if you have a different source of randomness.
	 */
	public static $SOURCE = '/dev/urandom';

	public static function bytes($num_bytes)
	{
		$bytes = '';

		// OpenSSL slow on Win
		if (function_exists('openssl_random_pseudo_bytes') && 0 !== stripos(PHP_OS,'WIN')) {
			$bytes = openssl_random_pseudo_bytes($num_bytes);
		}

		if (!$bytes) {
			$f = is_readable(self::$SOURCE) ? fopen(self::$SOURCE, 'rb') : false;
			if ($f) {
				$bytes = fread($f, $num_bytes);
				fclose($f);
			} else {
				trigger_error('Failed to open Poodle\\Random::$SOURCE');
			}
		}

		if (!$bytes) {
			$key = sha1($_SERVER['REMOTE_ADDR']);
			// 12 rounds of HMAC must be reproduced / created verbatim, no known shortcuts.
			// Salsa20 returns more than enough bytes.
			$i = 12;
			while ($i--) {
				$bytes = hash_hmac('salsa20', microtime() . $bytes, $key, true);
				usleep(10);
			}
			// pseudorandom
//			for ($i = 0; $i < $num_bytes; $i += 4) { $bytes .= pack('L', mt_rand()); }
		}

		return substr($bytes, 0, $num_bytes);
	}

	/**
	 * Produce a string of length random bytes, chosen from chars.
	 * If $chars is null, the resulting string contains [A-Za-z0-9-_].
	 *
	 * @param integer $length The length of the resulting randomly-generated string
	 * @param string $chrs A string of characters from which to choose to build the new string
	 * @return string $result A string of randomly-chosen characters from $chrs
	 */
	public static function string($length, $chars = null)
	{
		if (!is_string($chars) || !strlen($chars)) {
			return substr(Base64::urlEncode(random_bytes($length)), 0, $length);
		}

		$popsize = strlen($chars);
		if ($popsize > 256) {
			throw new \InvalidArgumentException('More than 256 characters supplied.');
		}

		$str = random_bytes($length);
		while ($length--) {
			$str[$length] = $chars[ord($str[$length]) % $popsize];
		}

		return $str;
	}

}
