<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

# PECL
if (!defined('UUID_TYPE_DEFAULT')) { define('UUID_TYPE_DEFAULT', 0); }
if (!defined('UUID_TYPE_TIME'))    { define('UUID_TYPE_TIME',    1); } # v1
if (!defined('UUID_TYPE_DCE'))     { define('UUID_TYPE_DCE',     2); }
if (!defined('UUID_TYPE_NAME'))    { define('UUID_TYPE_NAME',    3); }
if (!defined('UUID_TYPE_RANDOM'))  { define('UUID_TYPE_RANDOM',  4); } # v4

abstract class UUID
{
	const
		TYPE_DEFAULT   = UUID_TYPE_DEFAULT,
		TYPE_TIME      = UUID_TYPE_TIME,   // v1, Time-based with unique or random host identifier
		TYPE_DCE       = UUID_TYPE_DCE,    // v2, DCE Security version (with POSIX UIDs)
		TYPE_NAME      = UUID_TYPE_NAME,   // v3, Name-based (MD5 hash)
		TYPE_RANDOM    = UUID_TYPE_RANDOM; // v4, Random
//		TYPE_NAME_SHA1 = UUID_TYPE_RANDOM; // v5, Name-based (SHA-1 hash)

	public static function generate($mode = self::TYPE_DEFAULT)
	{
		# OSSP
		if (function_exists('uuid_make')) {
			exit('uuid-php package cannot be used in PHP 5.4 and up. Uninstall uuid-php and install php-pecl-uuid');
		}

		# PECL
		// pkgs.org/download/php-pecl-uuid
		if (function_exists('uuid_create')) {
			return uuid_create($mode);
		}

		/**
		 * Else generate v4 UUID (pseudo-random)
		 */

		$uuid = bin2hex(random_bytes(16));

		# set variant and version fields for 'true' random uuid
		$uuid[12] = '4';
		$hex = '0123456789abcdef';
		$uuid[16] = $hex[(8 + (ord($uuid[16]) & 3))];

		return substr($uuid,  0, 8).'-'
			.  substr($uuid,  8, 4).'-'
			.  substr($uuid, 12, 4).'-'
			.  substr($uuid, 16, 4).'-'
			.  substr($uuid, 20);
	}

	public static function time()   { return self::generate(self::TYPE_TIME); }

	public static function random() { return self::generate(self::TYPE_RANDOM); }

	public static function isValid($uuid)
	{
		return function_exists('uuid_is_valid')
			? uuid_is_valid($uuid)
			: !!preg_match('/^[0-9a-f]{8}\\-[0-9a-f]{4}\\-[0-9a-f]{4}\\-[0-9a-f]{4}\\-[0-9a-f]{12}$/i', $uuid);
	}

}
