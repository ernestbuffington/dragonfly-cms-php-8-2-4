<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/classes/cpg_cache.php,v $
  $Revision: 9.12 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:41 $
**********************************************/

class Cache {

	public static function clear() {
		$cache_dir = BASEDIR.'cache';
		$cache = dir($cache_dir);
		while($file = $cache->read()) {
			if (!is_dir("$cache_dir/$file") && $file != '.htaccess') {
				unlink("$cache_dir/$file");
			}
		}
		$cache->close();
	}

	public static function _array_parse($array, $space='  ') {
		$return = '';
		foreach($array as $key => $value) {
			$key = is_int($key) ? $key : "'$key'";
			$return .= "$space$key => ";
			if (is_array($value)) {
				$return .= "array(\n".Cache::_array_parse($value, "$space  ")."$space),\n";
			} else {
				if (!is_int($value)) {
					$value = str_replace('\\', "\\\\", trim($value));
					$value = "'".preg_replace('#\'#m', "\\'", $value)."'";
				}
				$return .= $value.",\n";
			}
		}
		return $return;
	}
	public static function array_save($name, $module_name='config', $array=false) {
		$cache_dir = BASEDIR.'cache';
		$filename = $cache_dir.'/'.$module_name."_$name.php";
		if (is_dir($cache_dir) && is_writable($cache_dir)) {
			$data = "<?php\nif (!defined('CPG_NUKE')) { exit; }\n";
			if (is_array($array)) {
				$data .= "\$$name = array(\n".Cache::_array_parse($array).");";
			} else {
				global ${$name};
				if (is_array(${$name})) { $data .= "\$$name = array(\n".Cache::_array_parse(${$name}).");"; }
			}
			file_write($filename, $data);
		}
	}
	public static function array_load($name, $module_name='config', $global=true) {
		if ($global) global ${$name};
		$filename = BASEDIR.'cache/'.$module_name."_$name.php";
		if (file_exists($filename)) {
			include($filename);
			if (!defined('PHP_AS_NOBODY')) { define_nobody($filename); }
			return ${$name};
		}
		return false;
	}
	public static function array_delete($name, $module_name='config') {
		Cache::remove($name, $module_name);
	}

	public static function remove($name, $module_name='config') {
		$cache_dir = BASEDIR.'cache';
		$filename = "$cache_dir/$module_name"."_$name.php";
		if (is_dir($cache_dir) && is_writable($cache_dir) && file_exists($filename)) {
			unlink($filename);
		}
		clearstatcache();
	}

	public static function defines_save($name, $module_name, $defines) {
		$cache_dir = BASEDIR.'cache';
		$filename = $cache_dir.'/'.$module_name."_$name.php";
		if (is_dir($cache_dir) && is_writable($cache_dir)) {
			$data = "<?php\nif (!defined('CPG_NUKE')) { exit; }\n";
			foreach ($defines as $name => $value) {
				if (!is_int($value)) {
					$value = str_replace('\\', '\\\\', trim($value));
					$value = "'".preg_replace('#\'#m', "\\'", $value)."'";
				}
				$data .= "define('$name', $value);\n";
			}
			file_write($filename, $data);
		}
	}
	public static function defines_load($name, $module_name) {
		$filename = BASEDIR.'cache/'.$module_name."_$name.php";
		if (file_exists($filename)) {
			include_once($filename);
			return true;
		}
		return false;
	}

}
