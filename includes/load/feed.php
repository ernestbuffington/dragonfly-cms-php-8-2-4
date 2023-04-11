<?php
/*********************************************
  CPG DragonflyCMS, Copyright (c) 2011 by DragonflyCMS Dev Team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }

if ('POST' === $_SERVER['REQUEST_METHOD']) { \Dragonfly\Net\Http::headersFlush(400); }
if (!preg_match('#^[a-z][a-z0-9_\-]+$#i', $_GET['feed'])) { \Dragonfly\Net\Http::headersFlush(404); }

$type = 'rss';
//if (isset($_GET['type'])) {
//	if ('rss' != $_GET['type']/* && 'atom' != $_GET['type']*/) { \Dragonfly\Net\Http::headersFlush(404); }
//	$type = $_GET['type'];
//}

SynFeed::$category = $_GET['feed'];

//if (isset($_GET['ver'])) {
//	if (!preg_match('#^[\d]\.[\d]$#', $_GET['ver'])) { \Dragonfly\Net\Http::headersFlush(404); }
//	SynFeed::$version = $_GET['ver'];
//}

$file = \Dragonfly::getModulePath(SynFeed::$category) . 'feed_' .$type .'.inc';
if (is_file($file)) {
	require_once(CORE_PATH .'cmsinit.inc');

	$cpgtpl = new \Dragonfly\TPL\XML();

	header('Content-Type: text/xml; charset=utf-8'); // application/rss+xml
	require_once($file);
	exit;
}
\Dragonfly\Net\Http::headersFlush(404);
