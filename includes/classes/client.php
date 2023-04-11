<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  https://dragonfly.coders.exchange
  Released under GNU GPL version 2 or any later version
**********************************************/

/**
 * @deprecated v9 class
 */
abstract class Client
{
	public static
		$os,
		$name,
		$version,
		$engine,
		$engineV,
		$isMobile,
		$verified,
		$hostname,

		$ua = '';

	public static function init()
	{
		self::$ua = empty($_SERVER['HTTP_USER_AGENT']) ? '' : strtolower($_SERVER['HTTP_USER_AGENT']);
		$ua = \Poodle\UserAgent::getInfo();
		self::$name = $ua->name;
		if ($ua->bot) {
			self::$engine = 'bot';
		} else {
			self::$version = $ua->version;
			self::$os      = $ua->OS->name;
			self::$engine  = $ua->OS->name;
			self::$engineV = $ua->OS->version;
		}
		self::$isMobile = (int)(\Poodle\UserAgent::isTablet() || \Poodle\UserAgent::isMobile());
	}
}

Client::init();
