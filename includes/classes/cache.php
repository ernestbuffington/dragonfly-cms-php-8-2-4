<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

abstract class Cache {

	public static function clear()
	{
		trigger_deprecated('Use Dragonfly::getKernel()->CACHE->clear()');
		Dragonfly::getKernel()->CACHE->clear();
	}

	public static function array_save($name, $module_name='config', $array=false)
	{
		trigger_deprecated('Use Dragonfly::getKernel()->CACHE->set($key, $array)');
		Dragonfly::getKernel()->CACHE->set("modules/{$module_name}/{$name}", $array);
	}

	public static function array_load($name, $module_name='config', $global=true)
	{
		trigger_deprecated('Use Dragonfly::getKernel()->CACHE->get($key)');
		$data = Dragonfly::getKernel()->CACHE->get("modules/{$module_name}/{$name}");
		if ($global) { $GLOBALS[$name] = $data; }
		return $data;
	}

	public static function array_delete($name, $module_name='config')
	{
		trigger_deprecated('Use Dragonfly::getKernel()->CACHE->delete($key)');
		Dragonfly::getKernel()->CACHE->delete("modules/{$module_name}/{$name}");
	}

	public static function remove($name, $module_name='config')
	{
		trigger_deprecated('Use Dragonfly::getKernel()->CACHE->delete($key)');
		Dragonfly::getKernel()->CACHE->delete("modules/{$module_name}/{$name}");
	}

}
