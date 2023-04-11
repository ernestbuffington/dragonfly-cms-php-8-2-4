<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

# Simulate request
$_SERVER['HTTP_HOST']       = gethostname() ?: '127.0.0.1';
$_SERVER['HTTP_ACCEPT']     = 'text/plain'; # text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 ('.PHP_SAPI.'; U; '.PHP_OS.'; en) PHP_CLI/'.PHP_VERSION;
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['REQUEST_METHOD']  = 'CLI';
$_SERVER['REQUEST_URI']     = implode(' ', $argv);
$_SERVER['SCRIPT_NAME']     = strtr(str_replace(getcwd(), '', $_SERVER['SCRIPT_NAME']), '\\', '/');
$_SERVER['SERVER_NAME']     = gethostname() ?: 'localhost';
$_SERVER['SERVER_ADDR']     = gethostbyname($_SERVER['SERVER_ADDR']) ?: '127.0.0.1';
/*
$_SERVER[HTTP_ACCEPT_LANGUAGE] => nl,en-us;q=0.7,en;q=0.3
$_SERVER[HTTP_ACCEPT_ENCODING] => gzip,deflate
$_SERVER[HTTP_ACCEPT_CHARSET] => ISO-8859-1,utf-8;q=0.7,*;q=0.7
$_SERVER[SERVER_SIGNATURE] => <address>Apache/2.2.8 (Fedora) Server at 127.0.0.1 Port 80</address>
$_SERVER[SERVER_SOFTWARE] => Apache/2.2.8 (Fedora)
$_SERVER[SERVER_PORT] => 80
$_SERVER[SERVER_ADMIN] => root@localhost
$_SERVER[REMOTE_PORT] => 37086
$_SERVER[GATEWAY_INTERFACE] => CGI/1.1
*/

if (1 < $argc) {
	$path = parse_url($argv[1]);
	if (!empty($path['host']))  { $_SERVER['HTTP_HOST'] = $path['host']; }
	if (!empty($path['path']))  { $_SERVER['PATH_INFO'] = $path['path']; }
	if (!empty($path['query'])) {
		$_SERVER['QUERY_STRING'] = $path['query'];
		// push query string into $_GET
		$_GET = array();
		parse_str($path['query'], $_GET);
	}
	if (!empty($path['scheme']) && 'https' == $path['scheme']) {
		$_SERVER['HTTPS'] = 'on';
	}

	for ($i=2; $i<$argc; ++$i) {
		switch (substr($argv[$i],0,3))
		{
		case '-h=': $_SERVER['HTTP_HOST'] = trim(substr($argv[$i],3)); break;
		}
	}
}
