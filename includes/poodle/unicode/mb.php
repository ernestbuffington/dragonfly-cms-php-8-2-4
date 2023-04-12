<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	Also read: php.net/regexp.reference#regexp.reference.unicode
*/

namespace Poodle\Unicode;

abstract class MB
{
	protected static
		$lowercase = array(),
		$uppercase = array(),
		$charset   = 'utf-8',
		$encoding  = 'UTF-8';

	public static function init()
	{
		# http://php.net/mbstring
		if (!function_exists('mb_internal_encoding')) { function mb_internal_encoding($encoding=null){ return MB::internal_encoding($encoding); } }
		if (!function_exists('mb_language'))     { function mb_language($language){ return 'uni'; } }
		if (!function_exists('mb_strtolower'))   { function mb_strtolower($str)   { return MB::tolower($str); } }
		if (!function_exists('mb_strtoupper'))   { function mb_strtoupper($str)   { return MB::toupper($str); } }
		if (!function_exists('mb_strlen'))       { function mb_strlen  ($str, $e) { return MB::length($str, $e); } }
		if (!function_exists('mb_strpos'))       { function mb_strpos  ($str, $n) { return MB::strpos($str, $n); } }
		if (!function_exists('mb_strrpos'))      { function mb_strrpos ($str, $n) { return MB::strrpos($str, $n); } }
		if (!function_exists('mb_stripos'))      { function mb_stripos ($str, $n) { return MB::stripos($str, $n); } }
		if (!function_exists('mb_strripos'))     { function mb_strripos($str, $n) { return MB::strripos($str, $n); } }
		if (!function_exists('mb_strstr'))       { function mb_strstr  ($str, $n, $p=false) { return MB::strstr ($str, $n, $p); } }
		if (!function_exists('mb_stristr'))      { function mb_stristr ($str, $n, $p=false) { return MB::stristr($str, $n, $p); } }
		if (!function_exists('mb_strrchr'))      { function mb_strrchr ($str, $n, $p=false) { return MB::strrchr($str, $n, $p); } }
		if (!function_exists('mb_substr'))       { function mb_substr  ($str, $s, $l=null)  { return MB::substr($str, $s, $l); } }
		if (!function_exists('mb_substr_count')) { function mb_substr_count($str, $n)       { return MB::substr_count($str, $n); } }
	}

	protected static function init_CaseFolding()
	{
		if (self::$lowercase) { return; }
		$casefolding = __DIR__.'/'.self::$charset.'/casefolding.inc';
		if (is_file($casefolding)) { require($casefolding); }
	}

	public static function tolower($str) { self::init_CaseFolding(); return strlen($str) ? str_replace(self::$uppercase, self::$lowercase, $str) : $str; }
	public static function toupper($str) { self::init_CaseFolding(); return strlen($str) ? str_replace(self::$lowercase, self::$uppercase, $str) : $str; }

	public static function internal_encoding($encoding=null)
	{
		if (empty($encoding)) return self::$encoding;
		if (!is_dir(__DIR__.'/'.strtolower($encoding))) return false;
		if (self::$encoding !== $encoding) {
			self::$charset = strtolower(self::$encoding = $encoding);
			self::$strip_modifiers = self::$lowercase = array();
		}
		return true;
	}

	public static function length($str, $enc) { return preg_match_all(('8bit'===$enc) ? '#.#s' : '#.#su', $str); }

	public static function substr($str, $start, $end=null)
	{
		if (!strlen($str)) { return $str; }
		if (0>$start) {
			$str = preg_replace('#^.*(.{'.(-$start).'})$#Dsu','$1',$str);
			if (0<$end) return preg_replace('#^(.{'.$end.'}).*$#Dsu','$1',$str);
			if (0>$end) return preg_replace('#^(.*).{'.(-$end).'}$#Dsu','$1',$str);
			return $str;
		}
		return preg_replace('#^'.(0<$start?'.{0,'.$start.'}':'').(0<$end ? '(.{0,'.$end.'}).*' : (0>$end ? '(.*).{'.(-$end).'}' : '(.*)')).'$#Dsu','$1',$str);

		# Issue on large strings?
		preg_match_all('#.#su', $str, $str);
		if ((is_countable($str[0]) ? count($str[0]) : 0) <= $start) return '';
		return implode('', empty($end) ? array_slice($str[0], $start) : array_slice($str[0], $start, $end));
	}

	private static function match (&$str, &$needle, $i=false, $r='.*') { return strlen($str) && strlen($needle) && preg_match("#^(.*)({$needle}{$r})$#Dsu".($i?'i':''), $str, $str); }
	private static function matchr(&$str, &$needle, $i=false) { return self::match($str, $needle, $i,"(?:(?!{$needle}).)*"); }

	public static function strpos ($str, $needle) { return self::match($str, $needle) ? self::length($str[1]) : false; }
	public static function stripos($str, $needle) { return self::match($str, $needle, 1) ? self::length($str[1]) : false; }

	public static function strrpos ($str, $needle) { return self::matchr($str, $needle) ? self::length($str[1]) : false; }
	public static function strripos($str, $needle) { return self::matchr($str, $needle, 1) ? self::length($str[1]) : false; }

	public static function strstr ($str, $needle, $part=false) { return self::match($str, $needle) ? $str[$part?1:2] : false; }
	public static function stristr($str, $needle, $part=false) { return self::match($str, $needle, 1) ? $str[$part?1:2] : false; }

	public static function strrchr($str, $needle, $part=false) { return self::matchr($str, $needle, 0) ? $str[$part?1:2] : false; }

	public static function substr_count($str, $needle) { return preg_match_all("#{$needle}#su", $str, $dummy); }

}
