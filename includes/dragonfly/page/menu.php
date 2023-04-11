<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
namespace Dragonfly\Page;

class Menu
{

	const
		# Types
		ADMIN  = 1,
		USER   = 2,
		CUSTOM = 4;

	protected static
		$collection = array(),
		$images,
		$theme;

	function __construct($id, array $data=array())
	{
		self::init();
		return self::create($id, $data);
	}

	protected static function init()
	{
		$theme = \Dragonfly::getKernel()->OUT->theme;
		if (!isset($_SESSION['CPG_SESS']['images'])) {
			$_SESSION['CPG_SESS']['images'] = array();
		}
		if (!isset($_SESSION['CPG_SESS']['images'][$theme])) {
			$_SESSION['CPG_SESS']['images'][$theme] = array();
		}
		if (!self::$images) {
			self::$images =& $_SESSION['CPG_SESS']['images'][$theme];
			self::$theme = $theme;
		}
	}

	public static function create($id, array $data=array())
	{
		self::$collection[$id] = new Menu_Factory($id);
		foreach ($data as $cname => $cdata) {
			self::$collection[$id]->$cname = new Menu_Item_Factory($cname, $cdata);
		}
		return self::$collection[$id];
	}

	public static function getCopy($id)
	{
		return unserialize(serialize(self::$collection[$id]));
	}

	# image MUST be under a directory named "images"
	public static function findImage(array $image)
	{
		if (empty($image['directory'])) {
			$image['directory'] = '';
			$size = false;
		} else {
			$image['directory'] .= '/';
			$size = preg_match('#([0-9]+x[0-9]+)#', $image['directory'], $size) ? $size[1] : false;
		}
		$key = $notfound = ($size ?: '48x48').'/not-found.png';
		if (!empty($image['filename'])) {
			$key = $image['directory'] . $image['filename'];
		}

		if (DF_MODE_DEVELOPER || !isset(self::$images[$key])) {
			if (false !== strpos($key, './')) { $key = $notfound; }
			if (!self::$theme) self::init();

			$paths = array('themes/'.self::$theme.'/images/'.$key);
			if ('default' !== self::$theme) {
				$paths[] = 'themes/default/images/'.$key;
			}
			if (!empty($image['extra_path']) && false === strpos($image['extra_path'], '.')) {
				$paths[] = $image['extra_path'].'/images/'.$key;
			}
			// v9 icons
			if (!empty($image['filename'])) {
				if ('admin/12x12/' == $image['directory']) {
					$paths[] = 'themes/'.self::$theme.'/images/admin/small/'.$image['filename'];
					$paths[] = 'themes/default/images/admin/small/'.$image['filename'];
				}
				if ('admin/48x48/' == $image['directory']) {
					$paths[] = 'themes/'.self::$theme.'/images/admin/'.$image['filename'];
					$paths[] = 'themes/default/images/admin/'.$image['filename'];
				}
			}
			$paths[] = 'images/'.$key;

			$val = '';
			foreach ($paths as $path) {
				if (is_file($path)) {
					$val = $path;
					break;
				}
			}
			self::$images[$key] = $val ?: 'themes/default/images/'.$notfound;
		}
		return DF_STATIC_DOMAIN . self::$images[$key];
	}

	public static function toString($id, $handle, $kill=true)
	{
		return self::$collection[$id]->toString($handle, $kill);
	}

	# Supports duplicate items and subs and returns a list of possible menus
	public function getCurrent()
	{
		static $list;
		if (!empty($list)) return $list;

		$list = array(
			1 => array (
				'cat'=>false,
				'item'=>false,
				'sub'=>false
		));

		foreach ($this->items as $cat)
		{
			$i = count($list);
			$current = false;

			foreach ($cat->items as $item) {
				foreach ($item->items as $sub) {
					if ($current |= $sub->current) {
						$list[$i]['sub'] = array('title' => $sub->title, 'link' => $sub->link, 'image' => $sub->image);
						break;
					}
				}
				if ($current |= $item->current) {
					$list[$i]['item'] = array('title' => $item->title, 'link' => $item->link, 'image' => $item->image);
					break;
				}
			}
			if ($current |= $cat->current) {
				$list[$i]['cat'] = array('title' => $cat->title, 'link' => $cat->link, 'image' => $cat->image);
			}
		}
	}
}

class Menu_Factory
{
	protected
		$id,
		$items = array();

	function __construct($id) {
		$this->id = $id;
		return $this;
	}

	function __get($k)
	{
		if ('items' === $k) return is_null($this->$k) ? array() : $this->$k;
		return array_key_exists($k, $this->items) ? $this->items[$k] : null;
	}

	function __set($k, $v)
	{
		$this->items[$k] = $v;
	}

	# Returns an array of Menu_Item_Factory objects from the selected category
	# Use shift() when you need a ready to use Menu_Factory from the selected category
	public function get($name, $level=0)
	{
		return isset($this->items[$name]) ? $this->items[$name] : array();
	}

	public function updateImageRecursive($k, $v)
	{
		foreach ($this->items as $item) {
			$item->updateImageRecursive($k, $v);
		}
	}

	# if the merged object is reused multiple times
	# the passed $data must be already unserialize(serialazed), getCopy() can help you
	# othewise, when you know the menu is only used once, its not needed
	public function merge(Menu_Factory $data)
	{
		foreach ($data->items as $iname => $idata) {
			if (isset($this->items[$iname])) $this->items[$iname]->merge($idata);
			else $this->items[$iname] = $idata;
		}
		return $this;
	}

	# Returns a new Menu_Factory from the selected category
	# This will also revome the category from the original menu, use it on a getCopy() otherwise
	# Use get() when all you need is an array of Menu_Item_Factory objects
	public function shift($name, $level=0)
	{
		if (!($data = $this->get($name, $level))) return;
		unset($this->items[$name]);
		$ret = \Dragonfly\Page\Menu::create($name);
		foreach ($data->items as $cname => $cdata) {
			$ret->push($cname, $cdata);
		}
		return $ret;
	}

	# Push an entire menu inside the $name(ed) category
	public function push($name, Menu_Factory $data)
	{
		$this->items[$name] = new Menu_Item_Factory($name);
		$this->items[$name]->items = $data->items;
	}

	public function toTouchScreen()
	{
		foreach ($this->items as $cname => $cdata)
		{
			$c_match_found = false;
			foreach ($cdata->items as $idata)
			{
				$c_match_found |= $cdata->link['url'] === $idata->link['url'];
				$i_match_found = false;
				foreach ($idata->items as $sdata) {
					$i_match_found |= $idata->link['url'] === $sdata->link['url'];
				}
				if ($idata->items) {
					if (isset($idata->link['url'])) {
						if (!$i_match_found) {
							$tmp = clone $idata;
							$tmp->items = array();
							$idata->prependItem($tmp);
						}
						$idata->updateLink('url', null);
						$idata->updateLink('target', null);
					}
				}
			}
			if ($cdata->items) {
				if (isset($cdata->link['url'])) {
					if (!$c_match_found) {
						$tmp = clone $cdata;
						$tmp->items = array();
						$cdata->prependItem($tmp);
					}
					$cdata->updateLink('url', null);
					$cdata->updateLink('target', null);
				}
			}
		}
	}

	# set $kill to false when the merging object needs to be reused.
	# if the merging object is only used once, $kill can be left true
	public function toString($handle, $kill=true)
	{
		$menu = new Menu_Factory($this->id);
		$menu->merge(unserialize(serialize($this)));
		foreach ($menu->items as $item) {
			$item->toString();
		}
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->{$menu->id} = $menu->items;
		if ($kill) $this->items = array();
		$result = $OUT->toString($handle);
		$OUT->{$menu->id} = null;
		return \Dragonfly\Output\HTML::minify($result);
	}
}

class Menu_Item_Factory
{
	protected
		$id,
		$title,
		$current,
		$coordinates,
		// Satisfy property_exists
		$css_class,
		$link = array(
			'css_class' => null,
			'target'    => null,
			'type'      => null, # 0 will be sent to URL::index(), 1 left as is, 2 _blank target will be added
			'url'       => null,
			'itemprop'  => null,
			'rel'       => null
		),
		$image = array(
			'directory'  => null, # eg: admin/24x24
			'extra_path' => null, # eg: modules/Your_Account
			'filename'   => null,
			'target'     => null
		),
		$items = array();
/*
	 TODO: update class with addItem() and use $coordinates whit it.
	 	$coordinamtes = array(
			0,           "parent" mixed:
			             if string, search for item name in x 'level',
			             if int, go by position (push occupant down),

			null-0-1-2,  "level" mixed:
                   if is_null(level) && is_string(parent), the parent will be searched into the entire menu,
                   but if the parent is found in level 2 the new item will be place after.
                   Otherwise, when is_int(parent) is found to be in levels 0 or 1,
                   the new item will be 'position'ed as child (pushing occupant down).

			0,           "position" integer
		);
*/

	protected static $L10N;
	function __construct($id, array $data=array())
	{
		global $Debugger;
		if (empty(self::$L10N)) {
			self::$L10N = \Dragonfly::getKernel()->L10N;
		}

		$this->id = $id;
		if (is_int($id)) {
			$this->title = '';
			$this->current = false;
			return $this;
		}

		$el = $Debugger->setErrorLevel(0);
		$ll = $Debugger->setLogLevel(0);

		$this->title = !empty($data['title']) ? $data['title'] :
			str_replace('_', ' ', self::$L10N->get($id));

		$Debugger->setErrorLevel($el);
		$Debugger->setLogLevel($ll);

		$this->current |= $this->getCurrent($data);

		if (!empty($data['link']['url'])) {
			$this->link = array_merge($this->link, array_filter($data['link']));
		}
		if (!empty($data['image']['filename'])) {
			$this->image = array_merge($this->image, array_filter($data['image']));
		}
		if (!empty($data['items']) && is_array($data['items'])) {
			foreach ($data['items'] as $iname => $idata) {
				$this->items[$iname] = new Menu_Item_Factory($iname, $idata);
				$this->current |= $this->items[$iname]->current;
			}
		}
		$css_class = array(
			!empty($data['css_class']) ? $data['css_class'] : '',
			$this->current ? 'current' : ''
		);
		$this->css_class = implode(' ', array_filter($css_class)) ?: null;
		return $this;
	}

	function __get($prop)
	{
		switch ($prop) {
			case 'link':
			case 'image':
			case 'items':
				return empty($this->$prop) ? array() : $this->$prop;
			case 'css_class':
				return $this->title ? $this->css_class : 'divider';
			default:
				return property_exists($this, $prop) ? $this->$prop : null;
		}
	}

	function __set($prop, $value)
	{
		if (is_array($value)) {
			$this->$prop = array_filter($value);
		} else {
			$this->$prop = $value;
		}
	}

	protected function getCurrent($data)
	{
		return !empty($data['current']) ||
			!empty($data['link']['url']) && (
				\Dragonfly::$URI_ADMIN === $data['link']['url'] ? \Dragonfly::$URI_ADMIN === \URL::query() :
				false !== strpos($_SERVER['REQUEST_URI'],htmlspecialchars_decode($data['link']['url']))
			);
	}

	public function updateLink($k, $v)
	{
		if ($this->link) $this->link[$k] = $v;
	}

	public function updateImage($k, $v)
	{
		if ($this->image) $this->image[$k] = $v;
	}

	public function updateImageRecursive($k, $v)
	{
		$this->updateImage($k, $v);
		if ($this->items) {
			foreach ($this->items as $item) {
				$item->updateImageRecursive($k, $v);
			}
		}
	}

	public function merge(Menu_Item_Factory $item)
	{
		$this->current |= $item->current;
		if ($item->title) $this->title = $item->title;
		if ($item->css_class) $this->css_class = $item->css_class;
		if ($item->link) {
			$this->link  = array_replace($this->link, $item->link);
		}
		if ($item->image) {
			$this->image = array_replace($this->image, $item->image);
		}
		foreach ($item->items as $iname => $idata) {
			if (isset($this->items[$iname]))
				$this->items[$iname]->merge($idata);
			else
				$this->items[$iname] = $idata;
		}
	}

	public function prependItem(Menu_Item_Factory $data)
	{
		$this->items = array_merge(array($data->id => $data), $this->items);
	}

	public function toString()
	{
		if ($this->image) {
			$this->image = Menu::findImage($this->image);
		}
		if (!empty($this->link['url'])) {
			$this->link['itemprop'] = 'url';
			if (0 >= $this->link['type']) {
				$this->link['url'] = \URL::index($this->link['url']);
			} elseif (2 == $this->link['type']) {
				$this->link['target'] = '_blank';
			}
		}

		foreach ($this->items as $item) {
			$item->toString();
		}
	}
}
