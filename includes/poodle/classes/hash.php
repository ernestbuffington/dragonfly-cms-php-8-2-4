<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class Hash
{
	const
		BCRYPT_DEFAULT_WORK_FACTOR = 10;

	public static
		$PBKDF2_HASH_ALGORITHM = 'sha256',
		$PBKDF2_ITERATIONS = 1000,
		$PBKDF2_SALT_BYTES = 24,
		$PBKDF2_HASH_BYTES = 24;

	public static function algos()
	{
		static $algos;
		if (!$algos) {
			$algos = array('bcrypt','pbkdf2');
			if (is_callable('scrypt') || is_callable('sodium_crypto_pwhash_scryptsalsa208sha256_str') || is_callable('\\Sodium\\crypto_pwhash_scryptsalsa208sha256_str')) {
				$algos[] = 'scrypt';
			}
			if (defined('PASSWORD_ARGON2I') || is_callable('sodium_crypto_pwhash_str') || is_callable('\\Sodium\\crypto_pwhash_str')) {
				$algos[] = 'argon2i';
			}
			if (is_callable('blake2') || is_callable('sodium_crypto_generichash') || is_callable('\\Sodium\\crypto_generichash')) {
				$algos[] = 'blake2';
			}
			$algos += hash_algos();
			sort($algos);
		}
		return $algos;
	}

	public static function available($algo)
	{
		return (in_array($algo, self::algos()) || function_exists($algo));
	}

	public static function file($algo, $filename, $raw=false)
	{
		if ('blake2' === $algo) {
			return static::blake2_file($filename, $raw);
		}
		if (in_array($algo, hash_algos())) {
			return hash_file($algo, $filename, $raw);
		}
		$algo = $algo.'_file';
		if (function_exists($algo)) {
			return $algo($filename, $raw);
		}
		return false;
	}

	public static function string($algo, $string, $raw=false)
	{
		switch ($algo)
		{
		case 'none':
		case '':
			return $string;

		case 'bcrypt':
			return static::bcrypt($string);

		case 'blake2':
			return static::blake2($string, $raw);

		case 'pbkdf2':
			// format: algorithm$iterations$salt$hash
			$salt = base64_encode(random_bytes(static::$PBKDF2_SALT_BYTES));
			return static::$PBKDF2_HASH_ALGORITHM
				.'$'.static::$PBKDF2_ITERATIONS
				.'$'.$salt
				.'$'.base64_encode(hash_pbkdf2(
					static::$PBKDF2_HASH_ALGORITHM,
					$string,
					$salt,
					static::$PBKDF2_ITERATIONS,
					static::$PBKDF2_HASH_BYTES,
					true
				));

		case 'scrypt':
			if (is_callable('sodium_crypto_pwhash_scryptsalsa208sha256_str')) {
				return sodium_crypto_pwhash_scryptsalsa208sha256_str(
					$string,
					SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
					SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE
				);
			}
			if (is_callable('\\Sodium\\crypto_pwhash_scryptsalsa208sha256_str')) {
				return \Sodium\crypto_pwhash_scryptsalsa208sha256_str(
					$string,
					\Sodium\CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
					\Sodium\CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE
				);
			}
			return Scrypt::hash($string, Scrypt::OPSLIMIT_INTERACTIVE, Scrypt::MEMLIMIT_INTERACTIVE);

		case 'argon2i':
			if (defined('PASSWORD_ARGON2I')) {
				return password_hash($string, PASSWORD_ARGON2I);
			}
			if (is_callable('sodium_crypto_pwhash_str')) {
				return sodium_crypto_pwhash_str(
					$string,
					SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
					SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
				);
			}
			if (is_callable('\\Sodium\\crypto_pwhash_str')) {
				return \Sodium\crypto_pwhash_str(
					$string,
					\Sodium\CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
					\Sodium\CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
				);
			}
			return false;

		default: # sha1, md5, etc.
			if (in_array($algo, hash_algos())) {
				return hash($algo, $string, $raw);
			}
			if (function_exists($algo)) {
				return $algo($string, $raw);
			}
		}
		return false;
	}

	public static function bcrypt($string, $work_factor = 0)
	{
		if (strlen($string) > 72) {
			trigger_error('bcrypt $string truncated to 72 characters', E_USER_WARNING);
		}
		if ($work_factor < 4 || $work_factor > 31) {
			$work_factor = self::BCRYPT_DEFAULT_WORK_FACTOR;
		}
		return password_hash($string, PASSWORD_BCRYPT, array('cost'=>$work_factor));
	}

	public static function blake2($string, $raw = false, $size = 64)
	{
		if (is_callable('sodium_crypto_generichash')) {
			return $raw
				? sodium_crypto_generichash($string, null, $size)
				: bin2hex(sodium_crypto_generichash($string, null, $size));
		}
		if (is_callable('\\Sodium\\crypto_generichash')) {
			return $raw
				? \Sodium\crypto_generichash($string, null, $size)
				: bin2hex(\Sodium\crypto_generichash($string, null, $size));
		}
		if (is_callable('blake2')) {
			return blake2($string, $size, null, $raw);
		}
		trigger_error('BLAKE2 hashing not available', E_USER_WARNING);
		return false;
	}

	public static function blake2_file($filename, $raw = false, $size = 64)
	{
		$hash = false;
		if ($fp = fopen($filename, 'rb')) {
			$data = fread($fp, 4096);
			if (is_callable('sodium_crypto_generichash_init')) {
				$state = sodium_crypto_generichash_init(null, $size);
				while (strlen($data)) {
					sodium_crypto_generichash_update($state, $data);
					$data = fread($fp, 4096);
				}
				$hash = sodium_crypto_generichash_final($state, $size);
			} else if (is_callable('\\Sodium\\crypto_generichash_init')) {
				$state = \Sodium\crypto_generichash_init(null, $size);
				while (strlen($data)) {
					\Sodium\crypto_generichash_update($state, $data);
					$data = fread($fp, 4096);
				}
				$hash = \Sodium\crypto_generichash_final($state, $size);
			} else {
				trigger_error('BLAKE2 file hashing not available', E_USER_WARNING);
			}
			fclose($fp);
		} else {
			trigger_error('BLAKE2 file hashing failed', E_USER_WARNING);
		}
		return ($raw || !$hash) ? $hash : bin2hex($hash);
	}

	public static function equals($known_string, $user_string)
	{
		$result = false;
		if (!is_string($known_string)) {
			trigger_error(sprintf("Poodle\\Hash::equals(): Expected known_string to be a string, %s given", gettype($known_string)), E_USER_WARNING);
		} else if (!is_string($user_string)) {
			trigger_error(sprintf("Poodle\\Hash::equals(): Expected user_string to be a string, %s given", gettype($user_string)), E_USER_WARNING);
		} else if (strlen($known_string) == strlen($user_string)) {
			if (function_exists('hash_equals')) {
				return hash_equals($known_string, $user_string);
			}
			$result = $user_string == $known_string;
		}
		usleep(mt_rand(10000, 100000)); // wait between 0.01 and 0.1 second
		return $result;
	}

	public static function verify($algo, $string, $stored_hash, $raw=false)
	{
		$hash = false;
		switch ($algo)
		{
		case 'none':
		case '':
			$hash = $string;
			break;

		case 'bcrypt':
	 		return password_verify($string, $stored_hash);

		case 'blake2':
			$size = strlen($string);
			if (!$raw) {
				$size /= 2;
			}
			$hash = static::blake2($string, $raw, $size);
			break;

		case 'pbkdf2':
			$params = explode('$', $stored_hash);
			if (count($params) !== 4) {
				return false;
			}
			$stored_hash = base64_decode($params[3]);
			$hash = hash_pbkdf2($params[0], $string, $params[2], $params[1], strlen($stored_hash), true);
			break;

		case 'scrypt':
			if (is_callable('sodium_crypto_pwhash_scryptsalsa208sha256_str_verify')) {
				return sodium_crypto_pwhash_scryptsalsa208sha256_str_verify($stored_hash, $string);
			}
			if (is_callable('\\Sodium\\crypto_pwhash_scryptsalsa208sha256_str_verify')) {
				return \Sodium\crypto_pwhash_scryptsalsa208sha256_str_verify($stored_hash, $string);
			}
			return Scrypt::verify($stored_hash, $string);

		case 'argon2i':
			if (defined('PASSWORD_ARGON2I')) {
				return password_verify($string, $stored_hash);
			}
			if (is_callable('sodium_crypto_pwhash_str_verify')) {
				return sodium_crypto_pwhash_str_verify($stored_hash, $string);
			}
			if (is_callable('\\Sodium\\crypto_pwhash_str_verify')) {
				return \Sodium\crypto_pwhash_str_verify($stored_hash, $string);
			}
			return false;

		default: # sha1, md5, etc.
			if (in_array($algo, hash_algos())) {
				$hash = hash($algo, $string, $raw);
			} else
			if (function_exists($algo)) {
				$hash = $algo($string, $raw);
			}
		}

		return static::equals($stored_hash, $hash);
	}

	public static function hmac($algo, $data, $key, $raw=false)
	{
		if (in_array($algo, hash_algos())) {
			return hash_hmac($algo, $data, $key, $raw);
		}
		// PHP compiled with --disable-hash
		if (function_exists($algo)) {
			if (strlen($key) > 64) { $key = $algo($key, true); }
			$key  = str_pad($key, 64, "\x00");
			$ipad = str_repeat("\x36", 64);
			$opad = str_repeat("\x5c", 64);
			return $algo(($key ^ $opad) . $algo(($key ^ $ipad) . $data, true), $raw);
		}
		return false;
	}

	public static function hmac_file($algo, $filename, $key, $raw=false)
	{
		if (in_array($algo, hash_algos())) {
			return hash_hmac_file($algo, $filename, $key, $raw);
		}
		return false;
	}

	public static function sha1($string, $raw=false) { return self::string('sha1', $string, $raw); }
	public static function md5($string,  $raw=false) { return self::string('md5',  $string, $raw); }

}
