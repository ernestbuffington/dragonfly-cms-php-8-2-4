<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	https://tools.ietf.org/html/rfc3986
*/

namespace Poodle;

abstract class URI
{

	public static function resolve($uri='', $query=null)
	{
		if ($uri) {
			if ('#' === $uri[0]) {
				return $_SERVER['REQUEST_URI'] . $uri;
			}
			else if ('?' === $uri[0]) {
				if ('?admin&op=' === substr($uri,0,10)) {
					return str_replace('&amp;','&',\URL::admin(substr($uri,10)));
				}
				if ('?name=' === substr($uri,0,6)) {
					return str_replace('&amp;','&',\URL::index(substr($uri,6)));
				}
			}
			else if ('cdn://' === substr($uri,0,6)) {
				$uri = substr($uri, 6);
				if ('theme/' === substr($uri,0,6)) {
					return \Dragonfly::getKernel()->OUT->THEME_PATH . substr($uri,5);
				}
				return DF_STATIC_DOMAIN . $uri;
			}
			else if (preg_match('#^[a-z]+/#i',$uri)) {
				return DOMAIN_PATH . $uri;
			}
		}
		return $uri;
	}

	public static function index($str)
	{
		return \URL::index($str);
	}

	public static function scheme($uri)
	{
		return preg_match('/^[a-zA-Z]([a-zA-Z0-9\\+\\-\\.]+):/', $uri, $m)
			? strtolower($m[1])
			: null;
	}

	public static function unparse($parsed_url)
	{
		$url = '//';
		if (!empty($parsed_url['scheme']))   { $url = $parsed_url['scheme'] . '://'; }
		if (!empty($parsed_url['user']) || !empty($parsed_url['pass'])) {
			$url .= RFC_3986::authority_userinfo($parsed_url['user'], $parsed_url['pass']);
		}
		if (!empty($parsed_url['host']))     { $url .= $parsed_url['host']; }
		if (!empty($parsed_url['port']))     { $url .= ':'.$parsed_url['port']; }
		if (!empty($parsed_url['path']))     { $url .= $parsed_url['path']; }
		if (!empty($parsed_url['query']))    { $url .= '?'.$parsed_url['query']; }
		if (!empty($parsed_url['fragment'])) { $url .= '#'.$parsed_url['fragment']; }
		return $url;
	}

	public static function buildQuery($data, $enc=PHP_QUERY_RFC3986)
	{
		return http_build_query($data, '', '&', $enc);
	}

	/**
	 * Workaround, because parse_str() converts dots and spaces in variable
	 * names to underscores, as noted on http://php.net/parse_str
	 * and on http://php.net/manual/en/language.variables.external.php
	 */
	public static function parseQuery($str)
	{
		if (is_array($str)) { return $str; }
		$data = array();
		if (is_string($str) && $str)
		{
			$parts = explode('&', $str);
			foreach ($parts as $part)
			{
				$pair = explode('=', $part, 2);
				$pair[1] = isset($pair[1]) ? urldecode($pair[1]) : '';
				$p = strpos($pair[0], '[');
				if ($p && preg_match_all('/\\[([^\\[\\]]*)\\]/', $pair[0], $m)) {
					print_r($m);
					$m = $m[1];
					$v = $pair[1];
					$i = count($m);
					while ($i--) {
						$v = strlen($m[$i]) ? array(urldecode($m[$i]) => $v) : array($v);
					}
					$key = urldecode(substr($pair[0], 0, $p));
					if (!isset($data[$key])) {
						$data[$key] = $v;
					} else {
						$data[$key] = array_merge_recursive($data[$key], $v);
					}
				} else {
					$data[urldecode($pair[0])] = $pair[1];
				}
			}
		}
		return $data;
	}

	public static function shrink($uri, $len=35)
	{
		$uri = preg_replace('#^([a-z]+?:)?//#i', '', $uri);
		return (strlen($uri) > $len) ? substr($uri,0,round($len*2/3)).'...'.substr($uri,3-round($len/3)) : $uri;
	}

	public static function refresh($uri='', $time=3)
	{
		# Not a HTTP spec but some browsers support it
		header('Refresh: '.(int)$time.'; url='.self::abs($uri));
		\Poodle\Debugger::trigger('HTTP 1.x specs have no "Refresh" header. Some browsers may not support it!', __DIR__, E_USER_NOTICE);
	}

	public static function redirect($uri='', $code=303)
	{
		\URL::redirect(self::resolve($uri), $code);
	}

	/* get absolute uri */
	public static function abs($uri='', $scheme=null)
	{
		if (!$scheme) { $scheme = (empty($_SERVER['HTTPS']) ? 'http' : 'https'); }
		if ('//' === substr($uri, 0, 2)) { $uri = $scheme.':'.$uri; }
		if (!strpos($uri, '://')) {
			$port = empty($_SERVER['HTTPS']) ? 80 : 443;
			if ($port != $_SERVER['SERVER_PORT']) {
				$uri = $_SERVER['SERVER_PORT'].$uri;
			}
			$uri = $scheme.'://'.self::host().$uri;
		}
		return $uri;
	}

	public static function host($strip_lng=false)
	{
		static $host, $host_s;
		if (!$host) {
			$host = (empty($_SERVER['HTTP_HOST']) ? \Poodle::getKernel()->host : $_SERVER['HTTP_HOST']);
//			$host .= $_SERVER['SERVER_PORT'];
			$host_s = (preg_match('#^([a-z]{2})(-[a-z]{2})?\\.#D', $host, $match) ? substr($host, strlen($match[0])) : $host);
		}
		return $strip_lng ? $host_s : $host;
	}

	public static function appendArgs($uri, array $args)
	{
		$uri_q = parse_url($uri, PHP_URL_QUERY);
		if ($uri_q) { $args = array_merge(self::parseQuery($uri_q), $args); }
		$uri = preg_replace('#\\?.*#','',$uri);
		if ($args) { $uri .= '?'.self::buildQuery($args); }
		return $uri;
	}

	public static function isHTTPS($uri) { return 'https:' === substr($uri, 0, 6); }

}
