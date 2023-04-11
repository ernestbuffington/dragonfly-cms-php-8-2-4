<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

	Apache bug: mod_rewrite decodes url encoded slashes

	/Forums/viewforum/f=1.html?foo=bar
		RewriteRule ^(.*)$ index.php?$1 [L]
			$_GET = Array(
				[Forums/viewforum/f] => 1.html
			)
			$_SERVER[QUERY_STRING]      = Forums/viewforum/f=1.html
			$_SERVER[REQUEST_URI]       = /dragonfly/html/Forums/viewforum/f=1.html?foo=bar

		RewriteRule ^(.*)$ index.php?$1 [L,QSA]
			$_GET = Array(
				[Forums/viewforum/f] => 1.html
				[foo] => bar
			$_SERVER[QUERY_STRING]      = Forums/viewforum/f=1.html&foo=bar
			$_SERVER[REQUEST_URI]       = /dragonfly/html/Forums/viewforum/f=1.html?foo=bar
			$_SERVER[PHP_SELF]          = /dragonfly/html/index.php

		RewriteRule ^(.+)$ index.php/$1 [L,QSA]
			$_GET = Array(
				[foo] => bar
			$_SERVER[PATH_INFO]         = /Forums/viewforum/f=1.html
			$_SERVER[QUERY_STRING]      = foo=bar
			$_SERVER[REQUEST_URI]       = /dragonfly/html/Forums/viewforum/f=1.html?foo=bar
			$_SERVER[PHP_SELF]          = /dragonfly/html/Forums/viewforum/f=1.html

**********************************************/

namespace Dragonfly;

abstract class LEO
{

	public static function resolveQuery()
	{
		// firefox encodes space by default as %20 (RFC 3986) others might use '+' (RFC 1866)
		$_SERVER['REQUEST_URI'] = str_replace('+', '%20', $_SERVER['REQUEST_URI']);
		// RFC 3986 does not require encoding tilde characters, RFC 1738 does
		$_SERVER['REQUEST_URI'] = str_replace('%7E', '~', $_SERVER['REQUEST_URI']);

		// Resolve LEO generated URI's
		$path = trim(preg_replace('#\.html$#D', '', $_SERVER['PATH_INFO']), '/');
		if (strlen($path)) {
			$path = preg_replace('#^([a-z0-9_]+)/([a-z0-9_]+)($|/)#Di', 'name=$1&file=$2$3', $path);
			$path = preg_replace('#^([a-z0-9_]+)($|/)#Di', 'name=$1$2', $path);
			parse_str(str_replace('/','&',$path), $path);
		} else {
			$path = array();
		}
		parse_str($_SERVER['QUERY_STRING'], $q);
		if ($q) {
			$path = array_merge($path, $q);
		}
		$_GET = new \Poodle\Input\GET($path);
	}

	public static function updateQuery($query)
	{
		parse_str($query, $q);
		$_GET = new \Poodle\Input\GET($q);
	}

}
