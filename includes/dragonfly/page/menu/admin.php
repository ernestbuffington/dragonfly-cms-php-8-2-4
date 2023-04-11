<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
namespace Dragonfly\Page\Menu;

/* TODO
	adminmenu database driven in DragonflyCMS 11:
		by adding a 'menu_id' to modules_links table will be possible to handle multiple menus
		$menuitems will have to be moved to each module installer's data
*/

class Admin
{
	const
		BLOCK =  1,
		GRAPH =  2,
		CSS   =  4,
		JS    =  8,
		TABS  = 16;

	private static
		$active,
		$extraitems;


	# Constructor
	public static function init()
	{
		if (self::$active) return;
		self::$active = true;

		if (!is_admin() || defined('ADMIN_PAGES') && (isset($_GET['op']) && $_GET['op'] == 'logout')) {
			return;
		}

		self::$extraitems = array(
			_ADMIN => array(
				'items' => array(
					_HOME => array(
						'link' => array(
							'url'  => \Dragonfly::$URI_ADMIN,
							'type' => 1)
						),
					_ADMINLOGOUT => array(
						'link' => array(
							'url'  => \URL::admin('logout'),
							'type' => 1)))));

		$adlinks = \Dragonfly::getKernel()->CACHE->get(__CLASS__);
		if (!$adlinks) {
			$adlinks = array();

			$linksdir = dir('admin/links');
			while ($file = $linksdir->read()) {
				#                CPG-Nuke|PHP-Nuke
				if (preg_match('#^(adlnk_|links\.).*?\.php$#', $file)) {
					$adlinks[] = 'admin/links/'.$file;
				}
			}
			$linksdir->close();
			# DragonflyCMS module's
			$adlinks += \Dragonfly\Modules::ls('admin/adlinks.inc', false);
			\Dragonfly::getKernel()->CACHE->set(__CLASS__, $adlinks);
		}
		$menu = $menuitems = array();
		foreach ($adlinks as $module => $path) {
			if (is_file($path)) include($path);
		}
		$menuitems = is_array($menuitems) ? $menuitems : array();
		ksort($menuitems);

		# Transform data into Menu compatible data
		foreach ($menuitems as $cname => $cdata)
		{
			ksort($cdata);
			$items = array();

			foreach ($cdata as $iname => $idata)
			{
				$items[$iname] = array();
				if (!empty($idata['IMG'])) {
					$items[$iname]['image'] = array(
						'filename' => $idata['IMG'].'.png',
						'directory' => 'admin/48x48'
					);
				}
				if (!empty($idata['URL'])) {
					$items[$iname]['link'] = array(
						'url'    => $idata['URL'],
						'target' => !empty($idata['TARGET']) ? $idata['TARGET'] : null,
						'type'   => 1
					);
				}
				if (!empty($idata['MOD'])) {
					$items[$iname]['image']['extra_path'] = 'modules/'.$idata['MOD'];
				}
				if (!empty($idata['SUB'])) {
					$items[$iname]['items'] = array();
					foreach ($idata['SUB'] as $sname => $sdata) {
						$items[$iname]['items'][$sname]['link'] = array(
							'url'    => $sdata,
							'target' => !empty($sdata['TARGET']) ? $sdata['TARGET'] : null,
							'type'   => false !== strpos($sdata, '://') ? 2 : 1
						);
					}
				}
			}
			# Do not display empty categories
			if ($items) {
				$menu[$cname] = array('items' => $items);
			}
		}
		\Dragonfly\Page\Menu::create('adminmenu', $menu);
	}

	public static function display($type)
	{
		self::init();
		$template = \Dragonfly::getKernel()->OUT;
		$MAIN_CFG = \Dragonfly::getKernel()->CFG;

		# Small images only block
		if ('blockgfx' == $type)
		{
			# Patch image size for all items
			$menu = \Dragonfly\Page\Menu::create('adminmenugfx')->merge(\Dragonfly\Page\Menu::getCopy('adminmenu'));
			$menu->updateImageRecursive('directory', 'admin/24x24');
			return $menu->toString('menu/gfx');
		}

		$type = (int) $type;

		# Sideblock
		if (self::BLOCK & $type)
		{
			\Dragonfly\Output\Css::inline('
				nav.vtextonly a.current, nav.vtextonly span { font-weight:bold }
				nav.vtextonly ul { padding:0; margin:0 }
				nav.vtextonly ul ul { padding-left:10px }
			');
			$block = \Dragonfly\Page\Menu::create('vtextonly', self::$extraitems)->merge(\Dragonfly\Page\Menu::getCopy('adminmenu'));
			\Dragonfly\Blocks::custom(array(
				'bid' => 9999,
				'view_to' => 2,
				'side' => 'r',
				'title' => _ADMINMENU,
				'content' => $block->toString('menu/vertical-textonly')
			));
		}

		# Big graphical menu
		if (self::GRAPH & $type)
		{
			$template->S_ADMIN_GRAPHMENU = true;
			$menu = \Dragonfly\Page\Menu::create('adminmenugraph')->merge(\Dragonfly\Page\Menu::getCopy('adminmenu'));
			if (\URL::query() === \Dragonfly::$URI_ADMIN) {
				foreach ($menu->items as $cname => $cdata) {
					$cdata->current = true;
				}
			}
		}

		# CSS menu
		if (self::CSS & $type || self::JS & $type)
		{
			\Dragonfly\Output\Css::add('menu');
			$menu = \Dragonfly\Page\Menu::create('adminmenucompact')->merge(\Dragonfly\Page\Menu::getCopy('adminmenu'));
			$menu->updateImageRecursive('directory', 'admin/12x12');
			return $menu->toString('menu/compact');
		}

		# Tabbed menu
		else if (self::TABS & $type)
		{
			return \Dragonfly\Page\Menu::toString('adminmenu', 'menu/tabs', false);
		}
	}
}
