<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class Base64
{

	public static function encode($data, $chunklen = null, $end = "\r\n")
	{
		return 0 < $chunklen
			? chunk_split(base64_encode($data), $chunklen, $end)
			: base64_encode($data);
	}

	public static function decode($data, $strict = false)
	{
		return base64_decode($data, $strict);
	}

	/*
	 * RFC 4648 §4 'Table 2: The "URL and Filename safe" Base 64 Alphabet'
	 *   - instead of +
	 *   _ instead of /
	 *   No padded =
	 */
	public static function urlEncode($data)
	{
		return strtr(rtrim(base64_encode($data),'='), '+/', '-_');
	}

	public static function urlDecode($data, $strict = false)
	{
		return base64_decode(strtr($data, '-_', '+/'), $strict);
	}

}
