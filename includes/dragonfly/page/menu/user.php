<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
namespace Dragonfly\Page\Menu;

class User
{

	private static $active;

	public static function init()
	{
		if (self::$active) return;
		self::$active = true;

		$K = \Dragonfly::getKernel();
		$db = $K->SQL;

		$cache = null;
		$view = array(0);
		if ($is_admin = is_admin()) {
			$view[] = 2;
		}
		if (is_user()) {
			$view[] = 1;
			foreach ($K->IDENTITY->groups as $key => $value) {
				$view[] = $key+3;
			}
		} else {
			// Speedup anon menu
			if (!$is_admin) {
				$cache = __CLASS__ . '_anonymous-' . $K->L10N->id;
				if ($menu = $K->CACHE->get($cache)) {
					array_walk_recursive($menu, 'Dragonfly\Page\Menu\User::modifyLoginLink');
					\Dragonfly\Page\Menu::create('usermenu', $menu);
					return;
				}
			}
			$view[] = 3;
		}
		$view_q = ' active = 1 AND view IN ('.implode(',', $view).')';
		if (!$is_admin) {
			$view_q .= ' AND 0 < cat_id';
		}

		# Load categories
		$categories = $menu = array();
		$result = $db->query('SELECT cid, name as title, image, link_type, link FROM '.$db->TBL->modules_cat.' ORDER BY pos');
		while ($row = $result->fetch_assoc()) {
			if (!empty($row['link'])) {
				$row['link'] = array(
					'url' => $row['link'],
					'type' => $row['link_type']
				);
			}
			if (!empty($row['image'])) {
				$row['image'] = array(
					'filename' => $row['image'],
					'directory' => 'blocks/CPG_Main_Menu'
				);
			}
			$row['items'] = array();
			$cname = $row['title'];
			unset($row['title'], $row['link_type']);
			$menu[$cname] = $row;
			$categories[$row['cid']] = &$menu[$cname];
		}
		$result->free();
		if ($is_admin) {
			$menu[_NONE] = array(
				'cat_id' => -1,
				'title' => _NONE,
				'image' => array(
					'filename' => 'icon_exclaim.gif',
					'directory' => 'smiles'),
				'pos' => -1,
				'items' => array()
			);
			$categories[0] = &$menu[_NONE];
		}

		# Load permitted and active modules and links
		# Will not load modules that have specified not to be "inmenu" as well as "inactive" links.
		$result = $db->query('
			(SELECT mid AS id, custom_title AS title, title as link, active, view, inmenu, cat_id, -1 as link_type, pos
				FROM '.$db->TBL->modules.' WHERE inmenu = 1 AND '.$view_q.')
			UNION
			(SELECT lid AS id, title, link, active, view, active as inmenu, cat_id, link_type, pos
				FROM '.$db->TBL->modules_links.' WHERE '.$view_q.')
			ORDER BY pos');
		if (is_user()) {
			while ($row = $result->fetch_assoc()) {
				// Hide login link when logged in
				if ('login' === $row['link']) {
					continue;
				}
				if ('_LOGOUT' === $row['title'] || 'logout' === $row['link']) {
					$row['title'] = $K->L10N['Logout'];
					$row['link'] = array(
						'url' => \Dragonfly\Identity::logoutURL(),
						'type' => 1
					);
				} else {
					# Manually specifying title to avoid defaults and extend search to _*LANG
					$row['title'] = \Dragonfly\Modules\Module::get_title($row['title'] ?: $row['link']);
					$row['link'] = array(
						'url' => $row['link'],
						'type' => $row['link_type']
					);
					if (!$row['active']) {
						$row['css_class'] = 'inactive';
					}
					unset($row['link_type']);
				}
				$categories[$row['cat_id']]['items'][$row['title']] = $row;
			}
		} else {
			while ($row = $result->fetch_assoc()) {
				// Make login link fancy
				if ('login' === $row['link']) {
					$row['title'] = $K->L10N['Login'];
					$row['link'] = array(
						'url' => \Dragonfly\Identity::loginURL(),
						'type' => 1,
						'rel' => 'nofollow'
					);
				} else
				// When not logged in show register link instead
				if ('Your_Account' === $row['link']) {
					if ($url = \Dragonfly\Identity::getRegisterURL()) {
						$row['title'] = $K->L10N['Register'];
						$row['link'] = array(
							'url' => $url,
							'type' => 1,
							'rel' => 'nofollow'
						);
					} else {
						continue;
					}
				} else {
					# Manually specifying title to avoid defaults and extend search to _*LANG
					$row['title'] = \Dragonfly\Modules\Module::get_title($row['title'] ?: $row['link']);
					$row['link'] = array(
						'url' => $row['link'],
						'type' => $row['link_type']
					);
					if (!$row['active']) {
						$row['css_class'] = 'inactive';
					}
					unset($row['link_type']);
				}
				$categories[$row['cat_id']]['items'][$row['title']] = $row;
			}
		}
		$result->free();

		if ($cache) {
			$K->CACHE->set($cache, $menu);
		}

		\Dragonfly\Page\Menu::create('usermenu', $menu);
	}

	public static function display($type = 'main')
	{
		self::init();

		if ('vertical' == $type) {
			\Dragonfly\Output\Css::inline('
				nav.vertical ul { padding:0; margin:0 }
				nav.vertical ul ul { padding-left:10px }
				nav.vertical a { font-weight:bold }
				nav.vertical li li a { font-weight:normal }
				nav.vertical img { width:18px; height:18px }
				nav.vertical li li img { width:9px; height:9px }
			');
			$menu = \Dragonfly\Page\Menu::create('vertical')->merge(\Dragonfly\Page\Menu::getCopy('usermenu'));

			foreach ($menu->items as $cname => $cdata) {
				if (_NONE == $cname) {
					$cdata->title = '';
					$cdata->image = null;
				}
				foreach ($cdata->items as $idata) {
					$image = 'icon_unselect.gif';
					if ($idata->current) { $image = 'icon_select.gif'; }
					else if (false !== strpos($idata->css_class, 'inactive')) $image = 'icon_cantselect.gif';
					$idata->image = array('filename' => $image, 'directory' => 'blocks/CPG_Main_Menu');
				}
			}
			return $menu->toString('menu/vertical');
		}

		else {
			\Dragonfly\Output\Css::add('menu');
			if (\Poodle\UserAgent::isTablet() || \Poodle\UserAgent::isMobile()) {
				$menu = \Dragonfly\Page\Menu::create('main');
				$menu->push(_MENU, \Dragonfly\Page\Menu::getCopy('usermenu'));
				self::mergeAdmin($menu);
				$menu->toTouchScreen();
			} else {
				$menu = \Dragonfly\Page\Menu::create('main')->merge(\Dragonfly\Page\Menu::getCopy('usermenu'));
				self::mergeAdmin($menu);
			}
			return \Dragonfly\Page\Menu::toString('main', 'menu/main');
		}
	}

	// TODO: $extraitems
	private static function mergeAdmin($menu, $extraitems=false) {
		if (is_admin()) {
			\Dragonfly\Page\Menu\Admin::init();
			$menu->push(_ADMIN, \Dragonfly\Page\Menu::getCopy('adminmenu'));
		}
	}

	private static function modifyLoginLink(&$value, $key)
	{
		if ('url' === $key && strpos($value, 'redirect_uri=')) {
			$value = \Dragonfly\Identity::loginURL();
		}
	}

}
