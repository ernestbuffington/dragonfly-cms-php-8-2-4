<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/
namespace Dragonfly\Page;

class Router
{

	static protected
		$routes;

	private static function init()
	{
		if (is_null(self::$routes)) {
			self::$routes = array();
			$db = \Dragonfly::getKernel()->SQL;
			$result = $db->query("SELECT src, dest FROM {$db->TBL->router_rules}");
			while ($row = $result->fetch_assoc()) {
				self::$routes[$row['dest']] = $row['src'];
			}
		}
	}

	public static function GET()
	{
		self::init();
	}

	public static function HEAD()
	{
		//self::init();
	}

	public static function POST()
	{
		//self::init();
	}


	public static function fwdSrc()
	{
		if (self::$routes && $dest = array_search(strtolower(\URL::query()), array_map('strtolower', self::$routes))) {
			\Dragonfly\LEO::updateQuery('name='.$dest);
		}
	}

	public static function fwdDst($url)
	{
		return isset(self::$routes[$url]) ? self::$routes[$url] : $url;
	}

	public static function getRoutes()
	{
		$db = \Dragonfly::getKernel()->SQL;
		return $db->uFetchAll("SELECT id, mid, src, dest FROM {$db->TBL->router_rules}");
	}

	public static function routeSave()
	{
	}

	public static function routeDelete()
	{
	}

}

if (is_callable("Dragonfly\Page\Router::{$_SERVER['REQUEST_METHOD']}")) {
	Router::{$_SERVER['REQUEST_METHOD']}();
}
