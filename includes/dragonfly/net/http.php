<?php
/*********************************************
  Copyright (c) 2011 by Dragonfly CMS team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Net;

class Http
{

	public static $protocolVersion;
	public static $contentType = array(
		'ecma' => 'Content-Type: application/ecmascript; charset=utf-8',
		'js'   => 'Content-Type: application/javascript; charset=utf-8',
		'gz'   => 'Content-Type: application/x-gzip',
		'gzip' => 'Content-Type: application/x-gzip',
		'jpeg' => 'Content-Type: image/jpeg',
		'jpg'  => 'Content-Type: image/jpeg',
		'png'  => 'Content-Type: image/png',
		//'tiff' => 'Content-Type: image/tiff',
		'css'  => 'Content-Type: text/css; charset=utf-8',
		'html' => 'Content-Type: text/html; charset=utf-8',
		'text' => 'Content-Type: text/plain; charset=utf-8',
		'delimtext' => 'Content-Type: text/x-delimtext',
		'xml' => 'Content-Type: text/xml; charset=utf-8',
	);

	protected static $headers, $to_send = array();

	private static $exit;

	public static function init()
	{
		self::$protocolVersion = floatval(preg_replace('#^.*/([0-9\\.]+).*$#D', '$1', $_SERVER['SERVER_PROTOCOL']));
	}

	final public static function contentType($var)
	{
		if ( isset(self::$contentType[$var]) ) {
			self::headersPush(self::$contentType[$var]);
		}
	}

/**
 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec13.html#sec13.3.3
 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.19 .24 .25 .26 .28 .44
 * if the request method is GET or HEAD, the server SHOULD respond with a 304 (Not Modified) response,
 * including the cache- related header fields (particularly ETag) of one of the entities that matched.
 * For all other request methods, the server MUST respond with a status of 412 (Precondition Failed).
 */
	final public static function entityCache($ETag, $time)
	{
		if ('GET' !== $_SERVER['REQUEST_METHOD'] && 'HEAD' !== $_SERVER['REQUEST_METHOD']) return 412;
		$ETag = "\"{$ETag}\"";
		header('ETag: '. $ETag);
		if (!empty($_SERVER['HTTP_IF_NONE_MATCH'])) {
			if (0 === strpos($_SERVER['HTTP_IF_NONE_MATCH'], $ETag)) {
				return 304;
			}
			# If none of the entity tags match,
			# then the server MAY perform the requested method as if the If-None-Match header field did not exist,
			# but MUST also ignore any If-Modified-Since header field(s) in the request.
			$_SERVER['HTTP_IF_MODIFIED_SINCE'] = null;
		}
		if (isset($_SERVER['HTTP_IF_MATCH']) && false === strpos($_SERVER['HTTP_IF_MATCH'], $ETag)) {
			return 412;
		}
		$time = (int)$time;
		header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', $time)); # DATE_RFC1123
		if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $time <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			return 304;
		}
		if (!empty($_SERVER['HTTP_IF_UNMODIFIED_SINCE']) && $time > strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])) {
			return 412;
		}
	}

	public static function headersGet($h=null)
	{
		if (empty(self::$headers)) {
			foreach ($_SERVER as $key => $val) {
				if (substr($key, 0, 5) == 'HTTP_') {
					self::$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $val;
				}
			}
		}
		if (is_null($h)) return self::$headers;
		if (is_string($h) && isset(self::$headers[$h])) return self::$headers[$h];
	}

/**
 * Retrive an always fresh list of headers already sent to php buffer
 * apache_response_headers() style but without the need to flush()
 *
 * @access protected
 * @static
 */
	public static function headersSent()
	{
		self::headersFlush();
		$array = headers_list();
		if (empty($array)) return array();
		while ($val = array_shift($array)) {
			if ($pos = strpos($val, ':')) {
				$response[trim(substr($val, 0, $pos))] = trim(substr($val, $pos+1, strlen($val)));
//			} else { var_dump($val); }
//				} else if ( preg_match('#\s\([0-9]{3})\s#', $val, $match) ) {
//				$response = $match[1];
//			}
			}
		}
		return $response;
	}

	final public static function headersPush($h)
	{
		//if ( POODLE_CLI ) return;
		if (ctype_digit($h)) { $h = (int) $h; }
		if (is_int($h) && \Poodle\HTTP\Status::get($h) ) {
			if (200 > $h && '1.1' > self::$protocolVersion) { return; }
			if (between($h, 300, 599)) { self::$exit = true; }
			$h = $_SERVER['SERVER_PROTOCOL']. ' '. \Poodle\HTTP\Status::get($h);
		}
		self::$to_send[] = $h;
	}

	final public static function headersFlush($code=0, $msg='')
	{
		if ($code) { self::headersPush($code); }
		while ($var = array_shift(self::$to_send)) {
			header($var);
		}
		if (self::$exit) {
			if (!$msg || 'HEAD' === $_SERVER['REQUEST_METHOD']) { exit; }
			exit($msg);
		}
	}

	/**
	 * @deprecated Use \Dragonfly\Output\Tools::attachFile() instead.
	 */
	final public static function attachFile($filename, $type, $gzip)
	{
		trigger_deprecated('Use \Dragonfly\Output\Tools::attachFile() instead.');
		\Dragonfly\Output\Tools::attachFile($filename, $type, $gzip);
	}

	/**
	 * @deprecated Use \Dragonfly\Output\Tools::sendFile() instead.'
	 */
	final public static function sendFile($str, $compress, $end=false)
	{
		trigger_deprecated('Use \Dragonfly\Output\Tools::sendFile() instead.');
		\Dragonfly\Output\Tools::sendFile($str, $end);
	}

	final public static function clear() { self::$to_send = array(); }
}
