<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/blocks.php,v $
  $Revision: 1.11 $
  $Author: nanocaiordo $
  $Date: 2008/01/15 10:50:16 $
**********************************************/
class Blocks
{

	var $blocks, $custom = array();
	var $l, $r, $c, $d = 0;
	var $showblocks = 0;
	var $preview, $start = false;

	function init()
	{
		if ($this->start) return;
		$this->start = true;
		global $module_name;
		if (isset($_GET['hideallblocks']) || $this->preview) {
			$this->showblocks = $this->l = $this->r = $this->c = $this->d = 0;
			$this->custom = array();
			return;
		}
		$module_block = defined('ADMIN_PAGES') ? 'Admin' : $module_name;
		$this->blocks = blocks_list();
		$this->blocks = isset($this->blocks[$module_block]) ? $this->blocks[$module_block] : array();
		if (!empty($this->blocks)) {
			$this->showblocks = (int)$this->blocks['blocks'];
			foreach($this->blocks as $key => $val) {
				if (is_int($key)) {
					if ($this->showblocks & 1 && $val == 'l' || $val == 'c') continue;
					if ($this->showblocks & 2 && $val == 'r' || $val == 'd') continue;
				}
				unset($this->blocks[$key]);
			}
		}
		if (!empty($this->blocks)) { $this->get_blocks();	}
		$this->blocks = array_merge_recursive($this->custom, $this->blocks);
		$this->custom = array();
		$this->showblocks = 0;
		if (isset($this->blocks['l'])) $this->showblocks |= 1;
		if (isset($this->blocks['r'])) $this->showblocks |= 2;
	}

	function custom(&$data)
	{
		if (is_array($data) && $this->allowed($data['view'])) {
			++$this->$data['side'];
			if ($data['side'] == 'l') $this->showblocks |= 1;
			if ($data['side'] == 'r') $this->showblocks |= 2;
			$data['bkey'] = 'custom';
			$this->custom[$data['side']][$data['bid']] = $data;
		}
		$data = null;
	}

	function preview(&$block)
	{
		if (!$this->preview) { return trigger_error('Use "$Blocks->preview=TRUE;" before including header.php', E_USER_NOTICE); }
		++$this->c;
		$this->blocks['c'][$block['bid']] = $block;
		$this->display('c');
	}

	function display($side)
	{
		if (!$this->$side) return;
		if ($this->start) {
			$this->blocks = array_merge_recursive($this->custom, $this->blocks);
			$this->custom = array();
		}
		require_once(CORE_PATH.'nbbcode.php');
		foreach ($this->blocks[$side] as $block) {
			$block['title'] = (defined($block['title']) ? constant($block['title']) : $block['title']);
			switch ($block['bkey']) {
				case 'admin':
					if (can_admin()) {
						if ($content = adminblock($block['bid'], $block['title'], $block['content'])) {
							$this->assign($side, $content);
						}
					}
					break;
				case 'userbox':
					global $userinfo;
					if (is_user() && $userinfo['ublockon']) {
						$block['title'] = _MENUFOR." $userinfo[username]";
						$block['content'] = decode_bbcode($userinfo['ublock'], 1, true);
						$this->assign($side, $block);
					}
					break;
				case 'rss':
					$this->rss($side, $block);
					break;
				case 'custom':
					$block['content'] = decode_bbcode($block['content'], 1, true);
					$this->assign($side, $block);
					break;
				case 'file':
					$this->blockfile($side, $block);
					break;
				default:
					trigger_error('Undefined bkey for '.$block['title'], E_USER_WARNING);
				}
		}
		$this->blocks[$side] = array();
	}

	function hideblock($id) {
		static $hiddenblocks;
		if (!isset($hiddenblocks)) {
			$hiddenblocks = array();
			if (isset($_COOKIE['hiddenblocks'])) {
				$tmphidden = explode(':', $_COOKIE['hiddenblocks']);
				for($i=0; $i<count($tmphidden); $i++) {	$hiddenblocks[$tmphidden[$i]] = true;	}
			}
		}
		return (isset($hiddenblocks[$id]) ? true : false);
	}

	function blockfile($side, &$block)
	{
		Method::priv( __FILE__ , __CLASS__ , __FUNCTION__ );
		if (is_file('blocks/'.$block['blockfile'])) {
			include('blocks/'.$block['blockfile']);
			if ($content == 'ERROR') return;
			if (!empty($content)) {
				$block['content'] =& $content;
				$this->assign($side, $block);
				return;
			}
		}
		trigger_error($block['blockfile']._BLOCKPROBLEM, E_USER_WARNING);
	}

	function rss($side, &$block)
	{
		Method::priv( __FILE__ , __CLASS__ , __FUNCTION__ );
		global $db, $prefix;
		if ($block['time'] < (gmtime()-$block['refresh'])) {
			require_once(CORE_PATH.'classes/rss.php');
			if (!($block['content'] = CPG_RSS::format(CPG_RSS::read($block['url'])))) {
				return trigger_error(_RSSPROBLEM.' ('.$block['title'].')', E_USER_WARNING);
			}
			$db->sql_query('UPDATE '.$prefix.'_blocks SET content=\''.Fix_Quotes($block['content']).'\', time=\''.gmtime().'\' WHERE bid=\''.$block['bid'].'\'');
		}
		$this->assign($side, $block);
	}

	function assign($side, &$block)
	{
		Method::priv( __FILE__ , __CLASS__ , __FUNCTION__ );
		global $cpgtpl, $CPG_SESS;
		if (!$this->preview) {
			$sides = array('l' => 'left', 'c' => 'center', 'r' => 'right', 'd' => 'bottom');
			$side = $sides[$side];
		} else {
			$side = '';
		}
		$cpgtpl->assign_block_vars($side.'block', array(
			'S_TITLE'   => $block['title'],
			'S_CONTENT' => $block['content'],
			'S_BID'     => $block['bid'],
			'S_VISIBLE' => $this->hideblock($block['bid']) ? 'style="display:none"' : '',
			'S_HIDDEN'  => $this->hideblock($block['bid']) ? '' : 'style="display:none"',
			'S_IMAGE'   => 'themes/'.$CPG_SESS['theme'].'/images/'.($this->hideblock($block['bid']) ? 'plus' : 'minus')
		));
		if ($this->preview) {
			$cpgtpl->set_filenames(array('block' => 'block.html'));
			$cpgtpl->display('block');
		}
	}

	function get_blocks()
	{
		Method::priv( __FILE__ , __CLASS__ , __FUNCTION__ );
		global $db, $prefix, $MAIN_CFG, $currentlang;
		$querylang = ($MAIN_CFG['global']['multilingual']) ? " AND (blanguage='$currentlang' OR blanguage='')" : '';
		$result = $db->sql_query('SELECT bid, bkey, title, content, url, blockfile, view, refresh, time FROM '.$prefix."_blocks WHERE active='1'$querylang AND bid IN (".implode(',',array_keys($this->blocks)).")");
		$data = array();
		while($row = $db->sql_fetchrow($result, SQL_ASSOC)) {
			if (!empty($this->custom) && isset($this->custom[$this->blocks[$row['bid']]][$row['bid']])) {
				trigger_error('Duplicate block id '.$row['bid'], E_USER_WARNING);
				unset($this->custom[$this->blocks[$row['bid']]][$row['bid']]);
			}
			if ($this->allowed($row['view'])) {
				$row['bid']     = (int)$row['bid'];
				$row['time']    = (int)$row['time'];
				$row['refresh'] = (int)$row['refresh'];
				$data[$row['bid']] = $row;
			}
		}
		if (!empty($data)) {
			foreach ($this->blocks as $bid => $side) {
				unset($this->blocks[$bid]);
				if (isset($data[$bid])) {
					$this->blocks[$side][$bid] = $data[$bid];
					++$this->$side;
				}
			}
		}
		$db->sql_freeresult($result);
		$data = null;
	}

	function allowed($view)
	{
		Method::priv( __FILE__ , __CLASS__ , __FUNCTION__ );
		$view = (int)$view;
		if (is_admin() || ($view === 0)
			|| ($view === 1 && is_user())
			|| ($view === 3 && !is_user())
			|| ($view > 3 && in_group($view-3)))
		{
			return true;
		}
		return;
	}
}
$Blocks =& new Blocks();
