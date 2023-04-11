<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	Also read: php.net/regexp.reference#regexp.reference.unicode
*/

namespace Poodle;

abstract class Unicode
{
	protected static
		$chars = array(),
		$chars_modified = array();

	# Convert UTF-8 characters to their basic lowercased equivelants
	# examples: the German sharp S becomes ss or Latin L with curl becomes l
	public static function stripModifiers($str)
	{
		if (!self::$chars) { require(__DIR__.'/utf-8/modifiers.inc'); }
		return preg_replace(self::$chars_modified, self::$chars, $str);
	}

	public static function as_search_txt($str)
	{
		$str = preg_replace('#(<script.*?</script>|<style.*?</style>)#si', ' ', $str);
		$str = strip_tags(str_replace('>', '> ', $str));
		$str = \Poodle\Input::fixSpaces($str);
		$str = self::stripModifiers($str);
		$str = preg_replace('#[^\p{L}\p{N}"\-\+]+#su', ' ', $str); # strip non-Letters/non-Numbers
		return trim(preg_replace('#\s[^\s]{1,2}\s#u', ' ', " {$str} "));
	}

	public static function bin2hex($str)
	{
		return preg_match('#[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]#', $str) ? bin2hex($str) : $str;
	}

	public static function ctrl2hex($str)
	{
		return preg_replace_callback(
			'#([\x00-\x08\x0B\x0C\x0E-\x1F\x7F])#', // '#(\p{C})#u'
			function($m){return '\\x'.bin2hex($m[1]);},
			$str);
	}

	public static function to_latin($str)
	{
		$str = STR::tolower($str);
		require __DIR__ . '/utf-8/to_latin.inc';
		$str = strtr($str, $to_latin);
		unset($to_latin);
		return $str;
	}

	public static function ucfirst($str) { return mb_strtoupper(mb_substr($str, 0, 1)).mb_substr($str, 1); }
	public static function lcfirst($str) { return mb_strtolower(mb_substr($str, 0, 1)).mb_substr($str, 1); }

//	public static function casecmp($str1, $str2) { return strcmp(STR::tolower($str1), STR::tolower($str2)); }

	public static function subwords($str, $start, $end)
	{
		$str = preg_replace('#\pC#u','', $str);
		return preg_replace('#([^\pL]+)[\pL\pM\pN\pP]*$#Du','$1',STR::sub($str, $start, $end));
	}
}
