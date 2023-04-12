<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\HTTP;

abstract class Status
{
	# Status Codes http://w3.org/Protocols/rfc2616/rfc2616-sec10.html
	private static array $codes = array(
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',

		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
//		208 => 'Already Reported',
		// http://tools.ietf.org/html/rfc3229
//		226 => 'IM Used',

		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
//		306 => 'Switch Proxy',        # obsolete
		307 => 'Temporary Redirect',

		// Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',    # reserved for future use
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',  # header('Allow: HEAD, GET, POST')
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		// https://tools.ietf.org/html/rfc7540#section-9.1.2
		421 => 'Misdirected Request',
		// https://tools.ietf.org/html/rfc4918
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		// http://tools.ietf.org/html/rfc2817
//		426 => 'Upgrade Required',
		// http://tools.ietf.org/html/rfc6585
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',

		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable', # may have Retry-After header
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
//		506 => 'Variant Also Negotiates',
//		507 => 'Insufficient Storage',
//		508 => 'Loop Detected',
//		509 => 'Bandwidth Limit Exceeded',
//		510 => 'Not Extended',
		511 => 'Network Authentication Required',
	);

	public static function set($code)
	{
		$str = self::get($code);
		if (!$str) { return false; }
		header($_SERVER['SERVER_PROTOCOL'].' '.$str, true, $code);
		header('Status: '.$str); # on some servers the above fails?
		return true;
	}

	public static function get($code)
	{
		if (!isset(self::$codes[$code])) {
			trigger_error('Unknown status code: '.$code);
			return false;
		}
		if (303 === $code && 'POST' !== $_SERVER['REQUEST_METHOD']) { $code = 302; }
		# Many pre-HTTP/1.1 user agents do not understand the 303 & 307
		if ((303 === $code || 307 === $code) && 1.1 > preg_replace('#^.*/([0-9\\.]+).*$#D', '$1', $_SERVER['SERVER_PROTOCOL'])) { $code = 302; }
		$code .= ' '.self::$codes[$code];
		return $code;
	}

	public static function getCodeText($code)
	{
		return self::$codes[$code] ?? false;
	}

}
