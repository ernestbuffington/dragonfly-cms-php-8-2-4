<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly;

class Modules
{
	protected static
		$active = array(),
		$list = array();

	# ls('intall/cpg_inst.php,index.php');
	# ls('index.php')
	public static function ls($child='', $active=true)
	{
		if (false !== strpos($child, './')) return array();
		$children = array_map('trim', explode(',', $child));
		$search = implode(',modules/*/', $children);
		$active = $active ? 'active_' : '';

		if (!isset(self::$list[$active.$child])) {
			$files = preg_grep('#^modules\/([a-zA-Z0-9_\-]+)\/.*#', glob('{modules/*/'.$search.'}', GLOB_BRACE)); // BASEDIR issues C:\\dir\\file
			foreach (glob('modules/*.phar.pubkey') as $file) {
				$name = substr(basename($file),0,-12);
				$file = \Dragonfly::getModulePath($name);
				foreach ($children as $cname) {
					if (is_file($file.$cname) || is_dir($file.$cname)) {
						$files[] = $file.$cname;
					}
				}
			}
			$modules = preg_replace('#^.*modules\/([a-zA-Z0-9_\-]+)\/.*#', '$1', $files);
			self::$list[$active.$child] = array_combine($modules, $files);
			if ($active) {
				self::$list[$active.$child] = array_intersect_key(self::$list[$active.$child], self::getActiveList());
			}
		}
		return self::$list[$active.$child];
	}

	public static function clearStatCache()
	{
		self::$list = array();
	}

	public static function getActiveList()
	{
		if (empty(self::$active)) {
			$K = \Dragonfly::getKernel();
			if (!(self::$active = $K->CACHE->get('modules_active'))) {
				$result = $K->SQL->query("SELECT title, version, view FROM {$K->SQL->TBL->modules} WHERE active=1");
				while ($row = $result->fetch_row()) {
					self::$active[$row[0]] = array(
						'version' => (intval($row[1]) > 0) ? (int)$row[1] : 1,
						'view' => (int)$row[2],
					);
				}
				# Cache everything
				$K->CACHE->set('modules_active', self::$active);
			}
		}
		return self::$active;
	}

	public static function isActive($module_name)
	{
		self::getActiveList();
		return isset(self::$active[$module_name]);
	}

	public static function isVisible($module_name)
	{
		self::getActiveList();
		if (empty(self::$active[$module_name])) {
			return false;
		}
		if (can_admin()) {
			return true;
		}
		$view = self::$active[$module_name]['view'];
		if (1 == $view && !is_user()) {
			return false;
		}
		else if (2 == $view && !can_admin($module_name)) {
			return false;
		}
		else if (3 < $view && !in_group($view-3)) {
			return false;
		}
		return true;
	}

}
