<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

/**
 * Scrypt key derivation function
 *
 * @see      http://www.tarsnap.com/scrypt.html
 * @see      https://tools.ietf.org/html/draft-josefsson-scrypt-kdf-01
 */
abstract class Scrypt
{
	public const
		OPSLIMIT_INTERACTIVE = 534288,
		MEMLIMIT_INTERACTIVE = 16777216,
		STRPREFIX            = '$7$',
		ITOA64               = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

	/**
	 * Execute the scrypt algorithm
	 *
	 * @param  string $password
	 * @param  string $salt
	 * @param  int $cost CPU/Memory cost
	 * @param  int $block_size Block size parameter.
	 * @param  int $iterations parallelization cost
	 * @param  int $length size of the output key
	 * @return string
	 * @see    https://tools.ietf.org/html/draft-josefsson-scrypt-kdf-01#section-5
	 */
	public static function kdf($password, $salt, $N, $r, $p, $length, $raw_output = false)
	{
		if ($N == 0 || ($N & ($N - 1)) != 0) {
			throw new \Exception('CPU cost must be > 0 and a power of 2: '.$N);
		}
		if ($N > PHP_INT_MAX / 128 / $r) {
			throw new \Exception('CPU cost is too large');
		}
		if ($r > PHP_INT_MAX / 128 / $p) {
			throw new \Exception('Block size is too large');
		}

		if ($length < 16) {
			throw new \Exception('Key length must be greater or equal to 16');
		}

		if (extension_loaded('Scrypt')) {
			return \scrypt($password, $salt, $N, $r, $p, $length, $raw_output);
		}

		trigger_error('Poodle\Scrypt is very slow. Consider using Argon2i or Bcrypt, else https://github.com/DomBlack/php-scrypt', E_USER_WARNING);

		$MFLen = 128 * $r;

		$b = hash_pbkdf2('sha256', $password, $salt, 1, $p * $MFLen, true);

		$salt = '';
		for ($i = 0; $i < $p; ++$i) {
			$salt .= self::ROMix($r, substr($b, $i * $MFLen, $MFLen), $N);
		}

		return hash_pbkdf2('sha256', $password, $salt, 1, $length, $raw_output);
	}

	/**
	 * $opslimit:
	 *      32768 = 0.4 seconds
	 *      65536 = 0.8 seconds
	 *     131072 = 1.6 seconds
	 */
	public static function hash($string, $opslimit = 32768, $memlimit = 16777216)
	{
		if ($opslimit < 32768) {
			$opslimit = 32768;
		}
		$r = 8;
		if ($opslimit < (double)$memlimit / 32) {
			$p = 1;
			$maxN = $opslimit / ($r * 4);
			for ($N = 1; $N < 63; ++$N) {
				if (1 << $N > $maxN / 2)
					break;
			}
		} else {
			/* Set N based on the memory limit. */
			$maxN = $memlimit / ($r * 128);
			for ($N = 1; $N < 63; ++$N) {
				if (1 << $N > $maxN / 2)
					break;
			}
			/* Choose p based on the CPU limit. */
			$maxrp = ($opslimit / 4) / (1 << $N);
			if ($maxrp > 0x3fffffff)
				$maxrp = 0x3fffffff;
			$p = round($maxrp / $r);
		}
		$salt = static::encode64(random_bytes(32));
		return static::STRPREFIX
			.static::ITOA64[$N]
			.static::encode64_uint32($r, 30)
			.static::encode64_uint32($p, 30)
			.$salt.'$'.static::encode64(static::kdf($string, $salt, pow(2, $N), $r, $p, 32, true));
	}

	public static function verify($hash, $str)
	{
		$string = null;
  $params = explode('$', $hash);
		if (count($params) == 5) {
			$hash = base64_decode($params[4]);
			$str = static::kdf($string, $params[3], $params[0], $params[1], $params[2], strlen($hash), true);
		} else if (preg_match('#^\\$7\\$([\\'.static::ITOA64.']+)\\$([\\'.static::ITOA64.']+)$#', $hash, $params)) {
			$str = static::kdf(
				$str,
				substr($params[1],11),
				pow(2, strpos(static::ITOA64, $params[1][0])),
				static::decode64_uint32(substr($params[1],1,5), 30),
				static::decode64_uint32(substr($params[1],6,5), 30),
				32, true
			);
			$hash = static::decode64($params[2]);
		} else {
			return false;
		}
		return Hash::equals($str, $hash);
	}

	protected static function encode64_uint32($src, $srcbits)
	{
		$dst = '';
		for ($bit = 0; $bit < $srcbits; $bit += 6) {
			$dst .= static::ITOA64[$src & 0x3f];
			$src >>= 6;
		}
		return $dst;
	}

	protected static function encode64($src)
	{
		$srclen = strlen($src); // mbstring.func_overload must be off
		$dst = '';
		for ($i = 0; $i < $srclen;) {
			$value = 0;
			$bits = 0;
			do {
				$value |= ord($src[$i++]) << $bits;
				$bits += 8;
			} while ($bits < 24 && $i < $srclen);
			$dst .= static::encode64_uint32($value, $bits);
		}
		return $dst;
	}

	protected static function decode64_uint32($src, $dstbits)
	{
		$i = 0;
		$value = 0;
		$dstbits = min($dstbits, strlen($src)*6);
		for ($bit = 0; $bit < $dstbits; $bit += 6) {
			$value |= strpos(static::ITOA64, (string) $src[$i++]) << $bit;
		}
		return $value;
	}

	protected static function decode64($src)
	{
		$srclen = strlen($src); // mbstring.func_overload must be off
		$dst = '';
		for ($i = 0; $i < $srclen; $i += 4) {
			$str = substr($src, $i, 4);
			$chars = floor(strlen($str)*6/8);
			$value = static::decode64_uint32($str, 24);
			$dst .= chr($value);
			if (1 < $chars) {
				$dst .= chr($value >> 8);
				if (2 < $chars) {
					$dst .= chr($value >> 16);
				}
			}
		}
		return $dst;
	}

	/**
	 * Salsa 20/8 core (32 bit version)
	 *
	 * @param  string $b
	 * @return string
	 * @see    https://tools.ietf.org/html/draft-josefsson-scrypt-kdf-01#section-2
	 * @see    http://cr.yp.to/salsa20.html
	 */
	protected static function salsa208Core32($b)
	{
		$b32 = array();
		for ($i = 0; $i < 16; ++$i) {
			list(, $b32[$i]) = unpack('V', substr($b, $i * 4, 4));
		}

		$x = $b32;
		for ($i = 0; $i < 8; $i += 2) {
			$a      = ($x[ 0] + $x[12]);
			$x[ 4] ^= ($a << 7) | ($a >> 25) & 0x7f;
			$a      = ($x[ 4] + $x[ 0]);
			$x[ 8] ^= ($a << 9) | ($a >> 23) & 0x1ff;
			$a      = ($x[ 8] + $x[ 4]);
			$x[12] ^= ($a << 13) | ($a >> 19) & 0x1fff;
			$a      = ($x[12] + $x[ 8]);
			$x[ 0] ^= ($a << 18) | ($a >> 14) & 0x3ffff;
			$a      = ($x[ 5] + $x[ 1]);
			$x[ 9] ^= ($a << 7) | ($a >> 25) & 0x7f;
			$a      = ($x[ 9] + $x[ 5]);
			$x[13] ^= ($a << 9) | ($a >> 23) & 0x1ff;
			$a      = ($x[13] + $x[ 9]);
			$x[ 1] ^= ($a << 13) | ($a >> 19) & 0x1fff;
			$a      = ($x[ 1] + $x[13]);
			$x[ 5] ^= ($a << 18) | ($a >> 14) & 0x3ffff;
			$a      = ($x[10] + $x[ 6]);
			$x[14] ^= ($a << 7) | ($a >> 25) & 0x7f;
			$a      = ($x[14] + $x[10]);
			$x[ 2] ^= ($a << 9) | ($a >> 23) & 0x1ff;
			$a      = ($x[ 2] + $x[14]);
			$x[ 6] ^= ($a << 13) | ($a >> 19) & 0x1fff;
			$a      = ($x[ 6] + $x[ 2]);
			$x[10] ^= ($a << 18) | ($a >> 14) & 0x3ffff;
			$a      = ($x[15] + $x[11]);
			$x[ 3] ^= ($a << 7) | ($a >> 25) & 0x7f;
			$a      = ($x[ 3] + $x[15]);
			$x[ 7] ^= ($a << 9) | ($a >> 23) & 0x1ff;
			$a      = ($x[ 7] + $x[ 3]);
			$x[11] ^= ($a << 13) | ($a >> 19) & 0x1fff;
			$a      = ($x[11] + $x[ 7]);
			$x[15] ^= ($a << 18) | ($a >> 14) & 0x3ffff;
			$a      = ($x[ 0] + $x[ 3]);
			$x[ 1] ^= ($a << 7) | ($a >> 25) & 0x7f;
			$a      = ($x[ 1] + $x[ 0]);
			$x[ 2] ^= ($a << 9) | ($a >> 23) & 0x1ff;
			$a      = ($x[ 2] + $x[ 1]);
			$x[ 3] ^= ($a << 13) | ($a >> 19) & 0x1fff;
			$a      = ($x[ 3] + $x[ 2]);
			$x[ 0] ^= ($a << 18) | ($a >> 14) & 0x3ffff;
			$a      = ($x[ 5] + $x[ 4]);
			$x[ 6] ^= ($a << 7) | ($a >> 25) & 0x7f;
			$a      = ($x[ 6] + $x[ 5]);
			$x[ 7] ^= ($a << 9) | ($a >> 23) & 0x1ff;
			$a      = ($x[ 7] + $x[ 6]);
			$x[ 4] ^= ($a << 13) | ($a >> 19) & 0x1fff;
			$a      = ($x[ 4] + $x[ 7]);
			$x[ 5] ^= ($a << 18) | ($a >> 14) & 0x3ffff;
			$a      = ($x[10] + $x[ 9]);
			$x[11] ^= ($a << 7) | ($a >> 25) & 0x7f;
			$a      = ($x[11] + $x[10]);
			$x[ 8] ^= ($a << 9) | ($a >> 23) & 0x1ff;
			$a      = ($x[ 8] + $x[11]);
			$x[ 9] ^= ($a << 13) | ($a >> 19) & 0x1fff;
			$a      = ($x[ 9] + $x[ 8]);
			$x[10] ^= ($a << 18) | ($a >> 14) & 0x3ffff;
			$a      = ($x[15] + $x[14]);
			$x[12] ^= ($a << 7) | ($a >> 25) & 0x7f;
			$a      = ($x[12] + $x[15]);
			$x[13] ^= ($a << 9) | ($a >> 23) & 0x1ff;
			$a      = ($x[13] + $x[12]);
			$x[14] ^= ($a << 13) | ($a >> 19) & 0x1fff;
			$a      = ($x[14] + $x[13]);
			$x[15] ^= ($a << 18) | ($a >> 14) & 0x3ffff;
		}
		for ($i = 0; $i < 16; ++$i) {
			$b32[$i] = $b32[$i] + $x[$i];
		}
		$result = '';
		for ($i = 0; $i < 16; ++$i) {
			$result .= pack('V', $b32[$i]);
		}

		return $result;
	}

	/**
	 * Salsa 20/8 core (64 bit version)
	 *
	 * @param  string $b
	 * @return string
	 * @see    https://tools.ietf.org/html/draft-josefsson-scrypt-kdf-01#section-2
	 * @see    http://cr.yp.to/salsa20.html
	 */
	protected static function salsa208Core64($b)
	{
		$b32 = array();
		for ($i = 0; $i < 16; ++$i) {
			list(, $b32[$i]) = unpack('V', substr($b, $i * 4, 4));
		}

		$x = $b32;
		for ($i = 0; $i < 8; $i += 2) {
			$a      = ($x[ 0] + $x[12]) & 0xffffffff;
			$x[ 4] ^= ($a << 7) | ($a >> 25);
			$a      = ($x[ 4] + $x[ 0]) & 0xffffffff;
			$x[ 8] ^= ($a << 9) | ($a >> 23);
			$a      = ($x[ 8] + $x[ 4]) & 0xffffffff;
			$x[12] ^= ($a << 13) | ($a >> 19);
			$a      = ($x[12] + $x[ 8]) & 0xffffffff;
			$x[ 0] ^= ($a << 18) | ($a >> 14);
			$a      = ($x[ 5] + $x[ 1]) & 0xffffffff;
			$x[ 9] ^= ($a << 7) | ($a >> 25);
			$a      = ($x[ 9] + $x[ 5]) & 0xffffffff;
			$x[13] ^= ($a << 9) | ($a >> 23);
			$a      = ($x[13] + $x[ 9]) & 0xffffffff;
			$x[ 1] ^= ($a << 13) | ($a >> 19);
			$a      = ($x[ 1] + $x[13]) & 0xffffffff;
			$x[ 5] ^= ($a << 18) | ($a >> 14);
			$a      = ($x[10] + $x[ 6]) & 0xffffffff;
			$x[14] ^= ($a << 7) | ($a >> 25);
			$a      = ($x[14] + $x[10]) & 0xffffffff;
			$x[ 2] ^= ($a << 9) | ($a >> 23);
			$a      = ($x[ 2] + $x[14]) & 0xffffffff;
			$x[ 6] ^= ($a << 13) | ($a >> 19);
			$a      = ($x[ 6] + $x[ 2]) & 0xffffffff;
			$x[10] ^= ($a << 18) | ($a >> 14);
			$a      = ($x[15] + $x[11]) & 0xffffffff;
			$x[ 3] ^= ($a << 7) | ($a >> 25);
			$a      = ($x[ 3] + $x[15]) & 0xffffffff;
			$x[ 7] ^= ($a << 9) | ($a >> 23);
			$a      = ($x[ 7] + $x[ 3]) & 0xffffffff;
			$x[11] ^= ($a << 13) | ($a >> 19);
			$a      = ($x[11] + $x[ 7]) & 0xffffffff;
			$x[15] ^= ($a << 18) | ($a >> 14);
			$a      = ($x[ 0] + $x[ 3]) & 0xffffffff;
			$x[ 1] ^= ($a << 7) | ($a >> 25);
			$a      = ($x[ 1] + $x[ 0]) & 0xffffffff;
			$x[ 2] ^= ($a << 9) | ($a >> 23);
			$a      = ($x[ 2] + $x[ 1]) & 0xffffffff;
			$x[ 3] ^= ($a << 13) | ($a >> 19);
			$a      = ($x[ 3] + $x[ 2]) & 0xffffffff;
			$x[ 0] ^= ($a << 18) | ($a >> 14);
			$a      = ($x[ 5] + $x[ 4]) & 0xffffffff;
			$x[ 6] ^= ($a << 7) | ($a >> 25);
			$a      = ($x[ 6] + $x[ 5]) & 0xffffffff;
			$x[ 7] ^= ($a << 9) | ($a >> 23);
			$a      = ($x[ 7] + $x[ 6]) & 0xffffffff;
			$x[ 4] ^= ($a << 13) | ($a >> 19);
			$a      = ($x[ 4] + $x[ 7]) & 0xffffffff;
			$x[ 5] ^= ($a << 18) | ($a >> 14);
			$a      = ($x[10] + $x[ 9]) & 0xffffffff;
			$x[11] ^= ($a << 7) | ($a >> 25);
			$a      = ($x[11] + $x[10]) & 0xffffffff;
			$x[ 8] ^= ($a << 9) | ($a >> 23);
			$a      = ($x[ 8] + $x[11]) & 0xffffffff;
			$x[ 9] ^= ($a << 13) | ($a >> 19);
			$a      = ($x[ 9] + $x[ 8]) & 0xffffffff;
			$x[10] ^= ($a << 18) | ($a >> 14);
			$a      = ($x[15] + $x[14]) & 0xffffffff;
			$x[12] ^= ($a << 7) | ($a >> 25);
			$a      = ($x[12] + $x[15]) & 0xffffffff;
			$x[13] ^= ($a << 9) | ($a >> 23);
			$a      = ($x[13] + $x[12]) & 0xffffffff;
			$x[14] ^= ($a << 13) | ($a >> 19);
			$a      = ($x[14] + $x[13]) & 0xffffffff;
			$x[15] ^= ($a << 18) | ($a >> 14);
		}
		for ($i = 0; $i < 16; ++$i) {
			$b32[$i] = ($b32[$i] + $x[$i]) & 0xffffffff;
		}
		$result = '';
		for ($i = 0; $i < 16; ++$i) {
			$result .= pack('V', $b32[$i]);
		}

		return $result;
	}

	/**
	 * BlockMix
	 *
	 * @param  string $b
	 * @param  int $r
	 * @return string
	 * @see    https://tools.ietf.org/html/draft-josefsson-scrypt-kdf-01#section-3
	 */
	protected static function BlockMix($b, $r)
	{
		$x    = substr($b, -64);
		$even = '';
		$odd  = '';
		$len  = 2 * $r;

		for ($i = 0; $i < $len; ++$i) {
			if (PHP_INT_SIZE === 4) {
				$x = self::salsa208Core32($x ^ substr($b, 64 * $i, 64));
			} else {
				$x = self::salsa208Core64($x ^ substr($b, 64 * $i, 64));
			}
			if ($i % 2 == 0) {
				$even .= $x;
			} else {
				$odd .= $x;
			}
		}
		return $even . $odd;
	}

	/**
	 * ROMix
	 *
	 * @param  int $r
	 * @param  string $B
	 * @param  int $N
	 * @return string
	 * @see    https://tools.ietf.org/html/draft-josefsson-scrypt-kdf-01#section-4
	 */
	protected static function ROMix($r, $B, $N)
	{
		$X = $B;
		$V = array();
		for ($i = 0; $i < $N; ++$i) {
			$V[$i] = $X;
			$X = self::BlockMix($X, $r);
		}
		for ($i = 0; $i < $N; ++$i) {
			$j = self::integerify($X) % $N;
			$X = self::BlockMix($X ^ $V[$j], $r);
		}
		return $X;
	}

	/**
	 * Integerify
	 *
	 * Integerify (B[0] ... B[2 * r - 1]) is defined as the result
	 * of interpreting B[2 * r - 1] as a little-endian integer.
	 * Each block B is a string of 64 bytes.
	 *
	 * @param  string $b
	 * @return int
	 * @see    https://tools.ietf.org/html/draft-josefsson-scrypt-kdf-01#section-4
	 */
	protected static function integerify($b)
	{
		$v = (PHP_INT_SIZE === 8) ? 'V' : 'v';
		list(,$n) = unpack($v, substr($b, -64));
		return $n;
	}

}
