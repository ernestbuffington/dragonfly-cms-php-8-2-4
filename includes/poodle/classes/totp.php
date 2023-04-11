<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	RFC 6238
	As used by Google Authenticator
*/

namespace Poodle;

class TOTP
{

	protected static
		$base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	public static function createSecret($length = 16)
	{
		$secret = '';
		while (0 < $length--) {
			$secret .= static::$base32chars[random_int(0,31)];
		}
		return $secret;
	}

	public static function getUri($name, $secret, $issuer = '')
	{
		$name = rawurlencode($name);
		if ($issuer) {
			$issuer = rawurlencode($issuer);
			return "otpauth://totp/{$issuer}:{$name}?secret={$secret}&issuer={$issuer}";
		}
		return "otpauth://totp/{$name}?secret={$secret}";
	}

	public static function getQRCode($name, $secret, $issuer = '')
	{
		return \Poodle\QRCode::getMinimumQRCode(
			static::getUri($name, $secret, $issuer),
			\Poodle\QRCode::ERROR_CORRECT_LEVEL_M
		);
	}

	/**
	 * Check if the code is correct. This will accept codes starting
	 * from $discrepancy*30sec ago to $discrepancy*30sec from now
	 */
	public static function verifyCode($secret, $code, $discrepancy = 1, $digits = 6)
	{
		$key = static::base32Decode($secret);
		$digits = (8 == $digits) ? 8 : 6;
		$modulo = pow(10, $digits);
		$timeSlice = floor(time() / 30);

		for ($i = -$discrepancy; $i <= $discrepancy; ++$i) {
			// Pack time into binary string and hash it with users secret key
			$hm = \Poodle\Hash::hmac('SHA1', "\x00\x00\x00\x00".pack('N*', $timeSlice + $i), $key, true);
			// Unpak 4 bytes of the result, use last nipple of result as index/offset
			$value = unpack('N', substr($hm, (ord(substr($hm, -1)) & 0x0F), 4));
			// Only 32 bits
			$value = $value[1] & 0x7FFFFFFF;

			if (str_pad($value % $modulo, $digits, '0', STR_PAD_LEFT) == $code) {
				return true;
			}
		}

		return false;
	}

	protected static function base32Decode($secret)
	{
		$secret = trim($secret,'=');
		if (empty($secret)) {
			return '';
		}

		if (!preg_match('/^[A-Z2-7]+$/D', $secret)) {
			return false;
		}

		$sl = strlen($secret);
		$string = '';
		for ($i = 0; $i < $sl; ++$i) {
			$string .= sprintf('%05d', decbin(strpos(static::$base32chars, $secret[$i])));
		}

		$sl = strlen($string);
		$binary = '';
		for ($i = 0; $i < $sl; $i += 8) {
			$binary .= chr(bindec(substr($string,$i,8)));
		}
		return $binary;
	}

}
