<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\HTTP;

abstract class Headers
{
	# en.wikipedia.org/wiki/MIME#Encoded-Word
	public static function encodeValue($v)
	{
		return (preg_match('#^[\x01-\x7F]*$#D', $v) ? $v : '=?UTF-8?B?'.base64_encode($v).'?=');
	}

	# tools.ietf.org/html/rfc2183
	public static function setContentDisposition($type /* inline|attachment */, array $params)
	{
		if (isset($params['filename'])) { $params['filename'] = self::encodeValue($params['filename']); }
		if (isset($params['creation-date'])) { $params['creation-date'] = date(DATE_RFC822, $params['creation-date']); }
		if (isset($params['modification-date'])) { $params['modification-date'] = date(DATE_RFC822, $params['modification-date']); }
		if (isset($params['read-date'])) { $params['read-date'] = date(DATE_RFC822, $params['read-date']); }
		if (isset($params['size'])) { $params['size'] = (int)$params['size']; }
		$parms = array($type);
		foreach ($params as $k => $v) { $parms[] = $k.'='.$v; }
		header('Content-Disposition: '.implode('; ', $parms));
	}

	public static function setContentType($type, array $params = array(/* charset, name */))
	{
		if (isset($params['name'])) { $params['name'] = self::encodeValue($params['name']); }
		$parms = array($type);
		foreach ($params as $k => $v) { $parms[] = $k.'='.$v; }
		header('Content-Type: '.implode('; ', $parms));
	}

	public static function setCookie($name, $value='', $expire=-1, $domain=null)
	{
		return setcookie($name, $value, $expire, \Poodle::$URI_BASE, $domain?$domain:$_SERVER['HTTP_HOST'], false);
	}

	# tools.ietf.org/html/rfc2616#section-14.30
	public static function setLocation($uri=null, $status_code=0)
	{
		if (!$uri) { $uri = \Poodle::$URI_BASE; }
		if (302==$status_code || 303==$status_code || 307==$status_code) {
			// Force browser not to cache the redirect
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Pragma: no-cache');
		}
		# HTTP/1.1 specs say a single absolute URI
		header('Location: '.\Poodle\URI::abs(str_replace('&amp;', '&', $uri)));
		if ($status_code > 300 && $status_code < 400) {
			\Poodle\HTTP\Status::set($status_code);
		}
	}

	public static function setStatus($code)
	{
		return \Poodle\HTTP\Status::set($code);
	}

	public static function setETagLastModified($ETag, $time)
	{
		static::setETag($ETag);
		static::setLastModified($time);
	}

	public static function setETag($ETag)
	{
		$K = \Poodle::getKernel();
		if ($K && $K->IDENTITY) {
			$ETag = $K->IDENTITY->id.'-'.$ETag;
		}
		$ETag = "\"$ETag\"";
		header('ETag: '.$ETag);
		if (!empty($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			if (false !== strpos($_SERVER['HTTP_IF_NONE_MATCH'], $ETag))
			{
				\Poodle\HTTP\Status::set(304);
				exit;
			}
			$_SERVER['HTTP_IF_MODIFIED_SINCE'] = null;
		}
		if (isset($_SERVER['HTTP_IF_MATCH']) && false === strpos($_SERVER['HTTP_IF_MATCH'], $ETag))
		{
			\Poodle\HTTP\Status::set(412);
			exit;
		}
	}

	public static function setLastModified($time)
	{
		$time = (int)$time;
		$K = \Poodle::getKernel();
		if ($K && $K->IDENTITY) {
			$time = max($time, $K->IDENTITY->auth_time);
		}
		header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', $time)); # DATE_RFC1123
		if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $time <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']))
		{
			\Poodle\HTTP\Status::set(304);
			exit;
		}
		if (!empty($_SERVER['HTTP_IF_UNMODIFIED_SINCE']) && $time > strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']))
		{
			\Poodle\HTTP\Status::set(412);
			exit;
		}
	}

	# We only accept HEAD, GET and POST requests by default.
	# PUT, DELETE, etc. are dropped. More details at: bugs.php.net/15693
	public static function validateMethod(array $allowed_methods = array('GET','HEAD','POST'))
	{
		if (!self::request_method($allowed_methods))
		{
			\Poodle\HTTP\Status::set(405);
			header('Allow: '.implode(', ', $allowed_methods));
			exit;
		}
	}

	public static function request_method($method=null)
	{
		if (is_array($method)) { return in_array($_SERVER['REQUEST_METHOD'], $method); }
		return $method ? ($method === $_SERVER['REQUEST_METHOD']) : $_SERVER['REQUEST_METHOD'];
	}

	public static function gzip()
	{
		static $gzip = null;
		return $gzip = (is_null($gzip)
			? (!empty($_SERVER['HTTP_ACCEPT_ENCODING']) && extension_loaded('zlib') && stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
			: $gzip);
	}

}
