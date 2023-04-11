<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

class Report
{

	public static function notFound($message='')
	{
		self::error(404, $message);
	}

	protected static function fix_bin($s)
	{
		return str_replace('"','\\"', preg_replace_callback(
			'#([\x00-\x08\x0B\x0C\x0E-\x1F\x7F])#',
			function($m){return '\\x'.bin2hex($m[1]);},
			$s));
	}

	public static function error($title, $error='', $redirect=false)
	{
		$message = is_array($error) ? $error['msg'] : $error;
		cpg_error($message, $title, $redirect);
	}

	public static function confirm($msg, $hidden='', $uri=false)
	{
	}
}
