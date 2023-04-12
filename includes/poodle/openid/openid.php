<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://openid.net/developers/specs/
*/

namespace Poodle;

abstract class OpenID
{
	public const
		VERSION   = '1.0',

		XMLNS_1_0 = 'http://openid.net/xmlns/1.0',

		// Namespaces
		NS_1_0    = 'http://openid.net/signon/1.0',
		NS_1_1    = 'http://openid.net/signon/1.1',
		NS_2_0    = 'http://specs.openid.net/auth/2.0',

		// Yadis service types
		TYPE_V1_0    = 'http://openid.net/signon/1.0',
		TYPE_V1_1    = 'http://openid.net/signon/1.1',
		TYPE_V2_0    = 'http://specs.openid.net/auth/2.0/signon',
		TYPE_V2_0_OP = 'http://specs.openid.net/auth/2.0/server',    // OpenID Provider
		TYPE_V2_0_RP = 'http://specs.openid.net/auth/2.0/return_to'; // Relying Party

	public static function getTypeURIs()
	{
		return array(
			self::TYPE_V2_0_OP,
			self::TYPE_V2_0,
			self::TYPE_V1_1,
			self::TYPE_V1_0);
	}

	public static function getRelyingPartyTypeURIs()
	{
		return array(self::TYPE_V2_0_RP);
	}

	public static function getTypeName($type_uri)
	{
		switch ($type_uri) {
		case self::TYPE_V2_0_OP: return 'OpenID 2.0 OpenID Provider';
		case self::TYPE_V2_0_RP: return 'OpenID 2.0 Relying Party';
		case self::TYPE_V2_0:    return 'OpenID 2.0';
		case self::TYPE_V1_1:    return 'OpenID 1.1';
		case self::TYPE_V1_0:    return 'OpenID 1.0';
		}
		return 'unknown';
	}

	public static function getNamespaces()
	{
		return array(self::NS_1_0, self::NS_1_1, self::NS_2_0);
	}

	/**
	 * Gets the query data from the server environment based on the request method used.
	 * Because PHP converts dots and spaces in variable names to underscores,
	 * as noted at http://php.net/manual/en/language.variables.external.php,
	 * this method fetches data from $_SERVER['QUERY_STRING'] directly,
	 * and if POST is used, also from the php://input file stream.
	 *
	 * Skips invalid key/value pairs (i.e. keys with no '=value' portion).
	 *
	 * Returns an empty array if GET nor POST was used,
	 * or if POST was used but php://input cannot be opened.
	 */
	public static function getQueryVars($query = null)
	{
		$data = array();
		if (null !== $query) {
			$data = \Poodle\URI::parseQuery($query);
		} else {
			$data = \Poodle\URI::parseQuery($_SERVER['QUERY_STRING']);
			if ('POST' === $_SERVER['REQUEST_METHOD'] && $str = \Poodle\Input\POST::raw_data()) {
				// We're using the default behavior by overwriting
				// GET with POST if POST data is available.
				$data = array_merge($data, \Poodle\URI::parseQuery($str));
			}
		}
		return $data;
	}

	/**
	 * PHP natively doesn't support unsigned int
	 */
	public static function unsigned_int($value)
	{
		return preg_match('/^\\d+$/', $value) ? (int)$value : false;
	}

}
