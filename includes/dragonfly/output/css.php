<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  https://dragonfly.coders.exchange
  Released under GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Output;

abstract class Css extends Tools
{

	protected static
		$files = array(),
		$inline = array(),
		$mtime = 0,
		$theme;

	final public static function add($file, $media=null, $useLang=false)
	{
		$media = trim($media);
		if (!preg_match('#^([a-z0-9_]+/)?[a-z0-9_\\-\\.]+$#Di', $file)) {
			\Dragonfly\Debugger::warning('Invalid css file name: '.$file);
			return false;
		}
		if ($media && !preg_match('#^[a-z0-9:\\(\\)\\,\\- ]+$#Di', $media)) {
			\Dragonfly\Debugger::warning('Invalid css media string: '.$media);
			return false;
		}
		self::$files[$media?:'all'][$file] = $file;
		return true;
	}

	final public static function inline($str, $media=null)
	{
		self::$inline[$media?:'all'][] = $str;
	}

	protected static function findCSSFile($name)
	{
		$m = explode('/',$name, 2);
		if (empty($m[1])) { $m[1] = strtolower($m[0]); }
		$mpath = \Dragonfly::getModulePath($m[0]);
		$options = array(
			"themes/default/style/{$name}.css",
			"{$mpath}style/{$m[1]}.css",
			"{$mpath}tpl/{$m[1]}.css",
			"{$mpath}tpl/css/{$m[1]}.css",
			"includes/css/{$name}.css",
			"includes/{$m[0]}/css/{$m[1]}.css",
		);
		if ('default' !== static::$theme) {
			array_unshift($options, "themes/".static::$theme."/style/{$name}.css");
		}
		foreach ($options as $file) {
			if (is_file($file)) {
				return $file;
			}
		}
	}

	final public static function request()
	{
		if (!isset(\Dragonfly\Net\Http::$contentType['css'])) return false;
//		$_GET['css'] = gzinflate(base64_decode($_GET['css']));
		if (!preg_match('#^(([a-z0-9_]+/)?[a-z0-9_\\-\\.]+;)+$#Di', $_GET['css'].';')) return false;
		if (!preg_match('#^[a-z0-9_]+$#Di', $_GET['theme'])) return false;

		$theme = static::$theme = $_GET['theme'];
		$files = explode(';', $_GET['css']);
		$i = array_search('style', $files);
		if (0 !== $i) {
			if ($i) unset($files[$i]);
			array_unshift($files, 'style');
		}

		$c = count($files);
		for ($i=0; $i<$c; ++$i) {
			$file = static::findCSSFile($files[$i]);
			if ($file) {
				static::$files[$file] = $file;
				static::$mtime = max(static::$mtime, filemtime(BASEDIR.$file));
				if (preg_match_all('#@import[^"]+"([^"]+)"#', file_get_contents($file,false,null,0,4096), $m)) {
					foreach ($m[1] as $filename) {
						$filename = preg_replace('#^(poodle|dragonfly)_#', '$1/', str_replace('.css','',$filename));
						$filename = static::findCSSFile($filename);
						if ($filename) {
							if (!in_array($filename, $files)) {
								$files[] = $filename;
								++$c;
							}
							// imported file should be included before
							// this file as it relies on it
							static::assoc_array_push_before(static::$files,$file,$filename);
						}
					}
				}
			}
		}
		static::$files = array_keys(static::$files);
		return !empty(static::$files);
	}

	final public static function flushToClient()
	{
		parent::flushUsingCache('css');
	}

	final public static function flushToTpl()
	{
		if (empty(self::$files)) return '';

		$return = '';
		self::add('custom');
		foreach (self::$files as $key => $val) {
			sort($val);
			// $val = base64_encode(gzdeflate(implode(';',$val)));
			$href = htmlspecialchars(\URL::buildQuery(array(
				'css'   => implode(';',$val),
				'theme' => \Dragonfly::getKernel()->OUT->theme,
				'lng'   => \Dragonfly::getKernel()->L10N->lng
			)));
			$return .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"{$key}\" href=\"".\Dragonfly::$URI_BASE."/?{$href}\"/>\n";
		}
		self::$files = array();

		foreach (self::$inline as $key => $val) {
			$return .= '<style type="text/css" media="' .$key .'">';
			while ($str = array_shift($val)) {
				$return .= self::minify($str) ."\n";
			}
			$return .= "</style>\n";
		}
		self::$inline = array();

		return DF_MODE_DEVELOPER ? $return : preg_replace('#\\s*\\R+\\s*#',  "\n", $return);
	}

	final public static function minify($str)
	{
		if (DF_MODE_DEVELOPER) return $str;
		$str = trim(preg_replace('#\s\s+#', ' ', $str));
		$str = preg_replace('#/\*.*?\*/#s', '', $str);
		$str = preg_replace('#\s*[^{}]+{\s*}\s*#', '', $str);
		$str = preg_replace('#\s*([{},;:])\s*#', '$1', $str);
		$str = str_replace(';}', '}', $str);
		return $str;
	}

	protected static function processContent($buffer)
	{
		$buffer = preg_replace_callback('#url\((["\']?)\\.\\.(/images/[^"\'\\)]+\\.(png|jpe?g|gif|cur))\\1\)#', 'self::findCSSImage', $buffer);
		return preg_replace('#(url\\(["\']?)/images/#', '$1' .DF_STATIC_DOMAIN .'images/', $buffer);
	}

	protected static function findCSSImage($img=null)
	{
		$img = $img[2];
		$file = 'themes/'.static::$theme.$img;
		if (!is_file($file)) {
			$file = 'themes/default'.$img;
		}
		return 'url('.(DF_STATIC_DOMAIN?:\Dragonfly::$URI_BASE.'/').$file.')';
	}

}
