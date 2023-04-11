<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Output;

abstract class Js extends Tools
{

	protected static
		$files = array(),
		$inline = array(),
		$mtime = 0,
		$theme,
		$toClientPattern = array(
			'#^i:([a-z0-9_-]+)$#i',
			'#^p:([a-z0-9_-]+)$#i',
			'#^d:([a-z0-9_-]+)$#i',
			'#^t:([a-z0-9_]+):([a-z0-9_-]+)$#i',
			'#^m:([a-z0-9_]+):([a-z0-9_-]+)$#i'
		),
		$toClientReplace = array(
			'includes/javascript/$1.js',
			'includes/poodle/javascript/$1.js',
			'includes/dragonfly/javascript/$1.js',
			'themes/$1/javascript/$2.js',
			'modules/$1/javascript/$2.js'
		),

		$toTplPattern = array(
			'#^includes/javascript/([a-z0-9_-]+)\.js$#i',
			'#^includes/poodle/javascript/([a-z0-9_-]+)\.js$#i',
			'#^includes/dragonfly/javascript/([a-z0-9_-]+)\.js$#i',
			'#^themes/([a-z0-9_]+)/javascript/([a-z0-9_-]+)\.js$#i',
			'#^modules/([a-z0-9_]+)/javascript/([a-z0-9_-]+)\.js$#i'
		),
		$toTplReplace = array(
			'i:$1',
			'p:$1',
			'd:$1',
			't:$1:$2',
			'm:$1:$2'
		),

		$langPattern = array(
			'#^p:([a-z0-9_-]+)$#i',
			'#^d:([a-z0-9_-]+)$#i',
			'#^m:([a-z0-9_-]+):([a-z0-9_-]+)$#i'
		),
		$langReplace = array(
			'language/!lang!/poodle/javascript/$1.js',
			'language/!lang!/dragonfly/javascript/$1.js',
			'modules/$1/l10n/$2.!langcode!.js'
		);

	private static
		$langFiles = array();

	final public static function add($file, $useLang=false)
	{
		if (self::filter($file, 'toTpl') && is_file(BASEDIR.$file)) {
			$file = preg_replace(self::$toTplPattern, self::$toTplReplace, $file);
			self::$files[] = $file;
			if ($useLang) { self::$langFiles[$file] = ''; }
			return true;
		}
		\Dragonfly\Debugger::warning('Invalid javascript file name: '.$file);
		return false;
	}

	final public static function inline($str)
	{
		if (is_string($str) && $str) {
			self::$inline[] = $str;
		}
	}

	final public static function request()
	{
		if (parent::processRequest('js')) {
			// Process @import rules
			$files = array('includes/javascript/poodle.js'=>'includes/javascript/poodle.js');
			$c = count(static::$files);
			for ($i=0; $i<$c; ++$i) {
				$file = static::$files[$i];
				if (is_file($file)) {
					$files[$file] = $file;
					if (preg_match_all('#@import[^"]+"([^"]+)"#', file_get_contents($file,false,null,0,4096), $m)) {
						foreach ($m[1] as $filename) {
							$filename = 'includes/javascript/'.str_replace('.js','',$filename).'.js';
							$filename = preg_replace('#/javascript/(?:(poodle|dragonfly)_)#','/$1/javascript/',$filename);
							if (!in_array($filename, static::$files)) {
								static::$files[] = $filename;
								++$c;
							}
							// imported file should be included before
							// this file as it relies on it
							static::assoc_array_push_before($files,$file,$filename);
						}
					}
				} else {
					trigger_error("File not found: {$file}");
				}
			}
			static::$files = array_keys($files);
			return true;
		}
		return false;
	}

	final public static function flushToClient()
	{
			parent::flushUsingCache('js');
	}

	final public static function flushToTpl()
	{
		$return = '';
		self::$files = array_values(array_unique(self::$files));

		if (empty(self::$files)) return $return;
		$lng = \Dragonfly::getKernel()->L10N->lng;
		foreach (self::$files as &$file) {
			if (isset(self::$langFiles[$file])) $file .= '?l='.$lng;
		}
		self::$files = implode(';', self::$files);
		$return .= '<script type="text/javascript" src="'.\Dragonfly::$URI_BASE.'/?js='.self::$files."\"></script>\n";
		self::$files = array();

		if (!empty(self::$inline)) {
			$return .= "<script type=\"text/javascript\">\n";
			while ($file = array_shift(self::$inline)) {
				$return .= self::minify($file) ."\n";
			}
			$return .= "</script>\n";
		}
		return DF_MODE_DEVELOPER ? $return : preg_replace('#\\s*\\R+\\s*#',  "\n", $return);
	}

	# http://blog.stevenlevithan.com/archives/match-quoted-string speed?
//	static protected $str_re = '#(["\'])(?:.*[^\\\\]+)*(?:(?:\\\\{2})*)+\1#xU';
//	static protected $str_re = '#(["\'])(?:\\\\?[^\n])*?\1#s';
	static protected $str_re = '#"[^\n"\\\\]*(?:\\\\.[^\n"\\\\]*)*"|\'[^\n\'\\\\]*(?:\\\\.[^\n\'\\\\]*)*\'|/[^\s/\\\\]+(?:\\\\.[^\n/\\\\]*)*/[gmi]*#';

	final public static function minify($str)
	{
		if ($str && !DF_MODE_DEVELOPER) {
			$str = str_replace("\r", '', $str);
			$str = preg_replace('#(^|\s+)//.*#m', '', $str);
			$str = preg_replace('#(^|\s+)/\*[^@].*?\*/#s', '', $str); // Strip comments but keep IE specific stuff
			$str = preg_replace('#console\.[a-z]+\(((?(?!\);).)*)\);#', '', $str);
			preg_match_all(self::$str_re, $str, $strings);
			$strings = $strings[0];
			$str = preg_split(self::$str_re, $str);
//			$str = str_replace("\n", '', $str);
			$str = preg_replace('#\n+#', '', $str);
			$str = preg_replace('#\s+#', ' ', $str);
			# case|else if|function|in|new|return|typeof|var
			$str = preg_replace('#\s*([&%/\[\]{}\(\)\|\+!\?\-=:;,><\.\*]+)\s*#', '$1', $str);
//			$str = preg_replace('#\s*(\$?[^a-z_])\s*#si', '$1', $str);
			$str = str_replace(';}', '}', $str);
			$c = 1;
			while ($c) { $str = preg_replace('#var([^;]+);var#', 'var$1,', $str, -1, $c); }
			$c = count($strings);
			for ($i = 0; $i < $c; ++$i) { $str[$i] .= $strings[$i]; }
			return implode('', $str);
		}
		return $str;
	}
}
