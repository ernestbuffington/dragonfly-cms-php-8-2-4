<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://tools.ietf.org/rfc/rfc3986
*/

namespace Poodle;

class RFC_3986
{
	protected static function hex2chr($m) { return chr(intval($m[1], 16)); }
	protected static function hex2chr_safe($m)
	{
		$c = chr(intval($m[1], 16));
		return strspn($c, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890-._~') ? $c : $m[0];
	}

	protected static function chr2hex($m) { return '%'.strtoupper(bin2hex($m[0])); }

	public static function encode_url($url) { return rawurlencode($url); }

	// Section 3.2.1
	public static function authority_userinfo($user, $pass = '')
	{
		return empty($user) && empty($pass)
			? ''
			: preg_replace_callback('/[^a-z0-9\\-\\._~!$&\'()*+,;=:]/i', 'Poodle\\RFC_3986::chr2hex', $user)
			 .':'
			 .preg_replace_callback('/[^a-z0-9\\-\\._~!$&\'()*+,;=]/i', 'Poodle\\RFC_3986::chr2hex', $pass)
			 .'@';
	}

	// Section 6
	public static function normalize_url($url)
	{
		$url = parse_url(trim($url));
		if (!$url || empty($url['scheme']) || empty($url['host'])) return false;

		$url['host'] = mb_strtolower($url['host']); // mb_strtolower(rawurldecode($url['host']))
		if (false !== strpos($url['host'], '%')) {
			$url['host'] = preg_replace_callback('/%([0-9A-F]{2})/', 'Poodle\\RFC_3986::hex2chr', $url['host']);
		}

		if (!empty($url['user'])) {
			if (!empty($url['pass'])) { $url['user'] .= ':'.$url['pass']; }
			$url['host'] = $url['user'].'@'.$url['host'];
		}

		$port = \Poodle\HTTP\Request::getSchemePort($url['scheme']);
		if (empty($url['port']) || ($port && $url['port'] == $port)) {
			$url['port'] = '';
		} else {
			$url['port'] = ':'.$url['port'];
		}

		if (empty($url['path'])) {
			$url['path'] = '/';
		} else {
			$url['path'] = preg_replace_callback('/%([0-9A-F]{2})/i', 'Poodle\\RFC_3986::hex2chr_safe', $url['path']);
			if ('/' !== $url['path'][0]) { $url['path'] = '/'.$url['path']; }
			$url['path'] = preg_replace('#/+\\.?/+#', '/', $url['path']);
			$url['path'] = preg_replace('#(/[^/]+)?/\\.\\.$#D', '/', $url['path']);
			while (false !== strpos($url['path'], '/../')) {
				$url['path'] = preg_replace('#(/[^/]+)?/\\.\\./#', '/', $url['path']);
			}
		}

		$url['query'] = empty($url['query']) ? '' : '?'.$url['query'];
		$url['fragment'] = empty($url['fragment']) ? '' : '#'.$url['fragment'];

		return $url['scheme'].'://'.$url['host'].$url['port'].$url['path'].$url['query'].$url['fragment'];
	}
}
