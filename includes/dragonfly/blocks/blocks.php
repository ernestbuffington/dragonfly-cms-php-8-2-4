<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2015 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly;

class Blocks
{

	/* settings */
	public static
		$preview = false,
		$showblocks = true;
	/* end settings */

	const
		NONE   = 0,
		LEFT   = 1,
		RIGHT  = 2,
		CENTER = 4,
		DOWN   = 8,
		ALL    = 15;

	public
		$list = array();

	private static
		$custom = array(),
		$sidenames = array(
			self::LEFT   => 'leftblock',
			self::CENTER => 'centerblock',
			self::RIGHT  => 'rightblock',
			self::DOWN   => 'bottomblock'
		);

	private
		$data = array(),
		$active = false;

	# __get' slows down each call made to the class, performance wide? set property as public, strict coding? likes it this way
	public function __get($p)
	{
		switch ($p) {
			case 'data': return $this->data;
		}
	}

	public function __construct($mid=0)
	{
		global $Module;
		$K = \Dragonfly::getKernel();
		$db = $K->SQL;
		$OUT = $K->OUT;
		$L10N = $OUT->L10N;
		foreach (static::$sidenames as $name) {
			$OUT->$name = array();
		}

		$mid = (int)$mid;
		if (self::$preview || 0 === $mid || !$Module) {
			self::$showblocks = false;
			return;
		}

		//$db->query("DELETE FROM {$db->TBL->blocks_custom} WHERE mid NOT IN (SELECT mid FROM {$db->TBL->modules}) AND mid > 0");
		//$db->query("DELETE FROM {$db->TBL->blocks_custom} WHERE bid NOT IN (SELECT bid FROM {$db->TBL->blocks})");
		$querylang = ($L10N->multilingual ? "AND (blanguage='{$L10N->lng}' OR blanguage='')" : '');
		$result = $db->query("SELECT
			b.bid, b.bkey, b.title, b.content, b.url, b.blockfile, b.view_to, b.refresh, b.time,
			bc.mid, bc.side
			FROM {$db->TBL->blocks_custom} as bc
			LEFT JOIN {$db->TBL->blocks} as b USING (bid)
			WHERE b.active=1 AND bc.mid={$mid} {$querylang}
			ORDER BY bc.weight");
		while ($row = $result->fetch_assoc()) {
			// temporary table data needs upgrade
			if      ($row['side'] === 'l') $row['side'] = self::LEFT;
			else if ($row['side'] === 'c') $row['side'] = self::CENTER;
			else if ($row['side'] === 'r') $row['side'] = self::RIGHT;
			else if ($row['side'] === 'd') $row['side'] = self::DOWN;
			if ($Module->sides & $row['side'] && self::allow($row['view_to'])) {
				$row['bid']     = (int) $row['bid'];
				$row['time']    = (int) $row['time'];
				$row['refresh'] = (int) $row['refresh'];
				$this->data[$row['side']][] = $row;
			}
		}
		$result->free();
	}

	public static function custom($data)
	{
		if (!isset($data['view_to'])) {
			$data['view_to'] = $data['view'];
		}
		if (is_array($data) && self::allow($data['view_to'])) {
			if      ('l' === $data['side']) $data['side'] = self::LEFT;
			else if ('c' === $data['side']) $data['side'] = self::CENTER;
			else if ('r' === $data['side']) $data['side'] = self::RIGHT;
			else if ('d' === $data['side']) $data['side'] = self::DOWN;
			$data['bkey'] = 'custom';
			self::$custom[$data['side']][] = $data;
		}
	}

	public function preview($block)
	{
		self::$preview = true;
		self::$showblocks = false;
		$this->data = array(self::CENTER => array($block));
		$this->prepare(self::CENTER);
	}

	public function display($side)
	{
		\Dragonfly\Debugger::deprecated('$Blocks->display() is no longer needed, you can safely delete the call to this function.');
	}

	public function prepare($side)
	{
		$side = (int)$side;
		if (!self::$preview) {
			if (!self::$showblocks || !$side) {
				return;
			}
			if (!empty(self::$custom[$side])) {
				if (!isset($this->data[$side])) {
					$this->data[$side] = array();
				}
				while ($c = array_pop(self::$custom[$side])) {
					array_unshift($this->data[$side], $c);
				}
			}
			if (!isset($this->data[$side])) {
				return;
			}
		}

		foreach ($this->data[$side] as $k => $block) {
			switch ($block['bkey']) {
				case 'admin':
					if (is_admin()) {
						$this->assign($side, new \Dragonfly\Blocks\Admin($block));
					}
					break;
				case 'rss':
					$this->assign($side, new \Dragonfly\Blocks\RSS($block));
					break;
				case 'custom':
					$this->assign($side, new \Dragonfly\Blocks\Custom($block));
					break;
				case 'file':
					if (preg_match('#^blocks/block-([^/]+)#', $block['blockfile'])
					 || (preg_match('#^modules/([^/]+)/(blocks/.+)$#D', $block['blockfile'], $m) && \Dragonfly\Modules::isActive($m[1])))
					{
						if ('modules/Your_Account/blocks/userbox.php' == $block['blockfile']) {
							$ID = \Dragonfly::getKernel()->IDENTITY;
							if (!$ID->ublockon) {
								continue;
							}
							$block['title'] = _MENUFOR.' '.$ID->nickname;
						} else if (!empty($m)) {
							$block['blockfile'] = \Dragonfly::getModulePath($m[1]).$m[2];
						}
						$this->assign($side, new \Dragonfly\Blocks\File($block));
					}
					break;
				default:
					trigger_error('Undefined bkey for '.$block['title'], E_USER_WARNING);
			}
			$this->data[$side][$k] = null;
		}
	}

	public static function isHidden($id)
	{
		static $hiddenblocks;
		if (!isset($hiddenblocks)) {
			$hiddenblocks = array();
			if (isset($_COOKIE['hiddenblocks'])) {
				$tmphidden = explode(':', $_COOKIE['hiddenblocks']);
				foreach ($tmphidden as $id) {
					$hiddenblocks[$id] = true;
				}
			}
		}
		return isset($hiddenblocks[$id]);
	}
	// v9
	public function hideblock($id) { return static::isHidden($id); }

	private function assign($side, $block)
	{
		$OUT = \Dragonfly::getKernel()->OUT;
		$block->toggleid = preg_replace('/\s+/', '', $block->title);
		if (!self::$preview) {
			$OUT->{static::$sidenames[$side]}[] = $block;
		} else {
			$OUT->blockpreview = $block;
			$OUT->display('admin/blocks/preview');
		}
	}

	public static function allow($view_to)
	{
		static $views;
		if (!$views) {
			$views = array(0);
			if (is_admin()) {
				$views[] = 2;
			}
			if (is_user()) {
				$views[] = 1;
				foreach (\Dragonfly::getKernel()->IDENTITY->groups as $key => $value) {
					$views[] = $key+3;
				}
			} else {
				$views[] = 3;
			}
		}
		return !!array_intersect(explode(',',$view_to), $views);
	}

}
