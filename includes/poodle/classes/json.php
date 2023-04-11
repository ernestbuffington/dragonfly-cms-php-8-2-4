<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class JSON
{

	public static function encode($data, $options = 0, $depth = 512)
	{
		$data = json_encode($data, $options, $depth);
		if (JSON_ERROR_NONE != json_last_error()) {
			throw new \DomainException(json_last_error_msg());
		}
		return $data;
	}

	public static function decode($data, $options = 0, $depth = 512)
	{
		if ($options & JSON_BIGINT_AS_STRING && defined('JSON_C_VERSION') && PHP_INT_SIZE > 4) {
			/**
			 * When large ints should be treated as strings, not all servers support that.
			 * So we must manually detect large ints and convert them to strings.
			 */
			$data = preg_replace('/:\s*(-?\d{'.(strlen(PHP_INT_MAX)-1).',})/', ': "$1"', $data);
			$options ^= JSON_BIGINT_AS_STRING;
		}
		$data = json_decode($data, $options & JSON_OBJECT_AS_ARRAY, $depth, $options);
		if (JSON_ERROR_NONE != json_last_error()) {
			throw new \DomainException('JSON: ' . json_last_error_msg());
		}
		return $data;
	}

}
