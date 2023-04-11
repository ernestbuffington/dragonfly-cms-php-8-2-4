<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	https://tools.ietf.org/html/rfc7516
*/

namespace Poodle;

abstract class JWK
{
	public static function parseKeys(array $source)
	{
		$keys = array();
		foreach ($source as $k => $v) {
			if (!is_string($k)) {
				if (is_array($v) && isset($v['kid'])) {
					$k = $v['kid'];
				} else if (is_object($v) && property_exists($v, 'kid')) {
					$k = $v->kid;
				}
			}
			try {
				$keys[$k] = self::parseKey($v);
			} catch (\UnexpectedValueException $e) {
			}
		}
		if (!count($keys)) {
			throw new \UnexpectedValueException('Failed to parse JWK');
		}
		return $keys;
	}

	public static function parseKey($source)
	{
		if (is_string($source)) {
			return $source;
		}
		if (!is_array($source)) {
			$source = (array)$source;
		}
		if (!empty($source) && isset($source['kty']) && isset($source['n']) && isset($source['e'])) {
			switch ($source['kty'])
			{
				case 'RSA':
					if (array_key_exists('d', $source)) {
						throw new \UnexpectedValueException('Failed to parse JWK: RSA private key is not supported');
					}
					return self::createRSAPublicPem($source['n'], $source['e']);

				default:
					throw new \UnexpectedValueException("Failed to parse JWK: {$source['kty']} keys are not supported");
					break;
			}
		}

		throw new \UnexpectedValueException('Failed to parse JWK');
	}

	/**
	 * Create a public key represented in PEM format from RSA modulus and exponent information
	 */
	private static function createRSAPublicPem($n, $e)
	{
		$modulus = Base64::urlDecode($n);
		$modulus = "\x02" . pack('a*a*', self::lengthToDER(strlen($modulus)), $modulus);

		$exponent = Base64::urlDecode($e);
		$exponent = "\x02" . pack('a*a*', self::lengthToDER(strlen($exponent)), $exponent);

		$key = "\x00\x30" . pack(
			'a*a*a*',
			self::lengthToDER(strlen($modulus) + strlen($exponent)),
			$modulus,
			$exponent
		);
		$key = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00\x03" . self::lengthToDER(strlen($key)) . $key;
		$key = "\x30" . pack(
			'a*a*',
			self::lengthToDER(strlen($key)),
			$key
		);

		return "-----BEGIN PUBLIC KEY-----\r\n"
			. Base64::encode($key, 64)
			. '-----END PUBLIC KEY-----';
	}

	/**
	 * DER-encode the length
	 */
	private static function lengthToDER($length)
	{
		if ($length <= 0x7F) {
			return chr($length);
		}

		$temp = ltrim(pack('N', $length), chr(0));
		return pack('Ca*', 0x80 | strlen($temp), $temp);
	}

}
