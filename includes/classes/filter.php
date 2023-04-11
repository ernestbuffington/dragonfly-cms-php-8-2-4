<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by DragonflyCMS Dev. Team.
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

# http://www.php.net/manual/en/filter.filters.validate.php
abstract class Filter
{
	public static function username($var) {}
	public static function hostname($var) {}

	/*
		@ Return bool
	*/
	public static function email($email) {
		# Although RFC 1035 doesn't allow 1 char subdomains we allow it due to bug report 641
		return 1 === preg_match('#^[\w\.\+\-]+@(([\w]{1,25}\.)?[0-9a-z\-]{2,63}\.[a-z]{2,6}(\.[a-z]{2,6})?)$#', $email);
	}

	/*
		@ Return bool
	*/
	public static function domain($www)
	{
		if (false === strpos($www, '://')) {
			$www = (preg_match('#^([a-z0-9\-\.]+)?[a-z0-9\-]+\.[a-z]{2,4}\:443$#i', $www) ? 'https://' : 'http://') .$www;
		}
		return 1 === preg_match('#^http[s]?\:\/\/([a-z0-9\-\.]+)?[a-z0-9\-]+\.[a-z]{2,4}$#i', $www);
	}
}
