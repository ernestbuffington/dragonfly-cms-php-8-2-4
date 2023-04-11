<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Blocks\Admin;

class Block extends \Dragonfly\Blocks\Block
{
	protected
		$oldposition;

	function __construct($bid = 0)
	{
		unset($this->data['mid'], $this->data['side'], $this->data['toggleid']);
		parent::__construct((int)$bid);
		if ($this->data['bid']) {
			$this->oldposition = $this->data['bposition'];
		}
	}

	function __get($k)
	{
		if ('data' === $k) { return $this->data; }
		return parent::__get($k);
	}

	public function save()
	{
		$hardcoded = ('admin' === $this->data['bkey']);
		if ($this->data['blockfile']) {
			$this->data['bkey'] = 'file';
			if (!$this->data['title']) {
				$this->data['title'] = preg_replace('#(_|/blocks/)#', ' ', $this->data['blockfile']);
				$this->data['title'] = preg_replace('#(block-|\.php)#','',basename($this->data['title']));
			}
		} else if ($this->data['url']) {
			$this->data['bkey'] = 'rss';
			$this->data['time'] = time();
			$url = $this->data['url'];
			if (!preg_match('#https?://#',$url)) { $url = 'http://'.$url; }
			if (!($this->data['content'] = \Dragonfly\RSS::display($url))) {
				throw new \Exception(\Dragonfly::getKernel()->L10N->get('There seems to be a problem with the URL for this feed'));
			}
			$this->data['url'] = $url;
		} else if (!$hardcoded) {
			$this->data['bkey'] = 'custom';
		}
		if (!$hardcoded && !$this->data['content'] && !$this->data['blockfile']) {
			return false;
		}

		$SQL = \Dragonfly::getKernel()->SQL;
		$tbl = $SQL->TBL->blocks;
		$data = $this->data;
		unset($data['bid']);
		$data['view_to'] = implode(',', $data['view_to']);
		if ($this->data['bid']) {
			# can be removed
			if ($this->oldposition != $data['bposition']) {
				$SQL->exec("UPDATE {$tbl} SET weight=weight+1 WHERE weight>={$data['weight']} AND bposition='{$data['bposition']}'");
				$SQL->exec("UPDATE {$tbl} SET weight=weight-1 WHERE weight>{$data['weight']} AND bposition='{$this->oldposition}'");
			}
			# end
			$tbl->update($data, 'bid='.$this->data['bid']);
		} else {
			# might be removed later on
			list($weight) = $SQL->uFetchRow("SELECT weight FROM {$tbl} WHERE bposition='{$data['bposition']}' ORDER BY weight DESC");
			$this->data['weight'] = $data['weight'] = $weight+1;
			# end
			$this->data['bid'] = $tbl->insert($data, 'bid');
		}
		$this->oldposition = $this->data['bposition'];
//		\Dragonfly::getKernel()->CACHE->delete('blocks_list');
		return true;
	}

	public function delete()
	{
		if ($this->data['bid']) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$SQL->TBL->blocks->delete("bid={$this->data['bid']}");
			$SQL->TBL->blocks_custom->delete("bid={$this->data['bid']}");
			$SQL->exec("UPDATE {$SQL->TBL->blocks} SET weight=weight-1 WHERE weight>{$this->data['weight']} AND bposition='{$this->data['bposition']}'");
//			\Dragonfly::getKernel()->CACHE->delete('blocks_list');
			$this->data['bid'] = 0;
		}
	}

	public function getFileOptions()
	{
		$blocks = array('label' => '', 'blocks' => array());
		foreach (glob("blocks/block-*.php") as $block) {
			$blocks['blocks'][strtoupper($block)] = array(
				'value' => $block,
				'label' => strtr(substr($block, 7+strrpos($block, '/'), -4), '-_', '  '),
				'selected' => ($block === $this->data['blockfile']),
			);
		}
		ksort($blocks['blocks']);
		$blocks['blocks'] = array_values($blocks['blocks']);

		$modblocks = array();
		foreach (\Dragonfly\Modules::ls('blocks/', false) as $name => $path) {
			if (\Dragonfly\Modules::isActive($name)) {
				$name = ucfirst($name);
				$modblocks[$name] = array('label' => $name, 'blocks' => array());
				$path = strtr($path, '\\', '/');
				foreach (scandir($path) as $block) {
					if ('.php' === substr($block, -4)) {
						$block = substr($path, strrpos($path,'modules/')).$block;
						$modblocks[$name]['blocks'][strtoupper($block)] = array(
							'value' => $block,
							'label' => strtr(substr($block, 1+strrpos($block, '/'), -4), '-_', '  '),
							'selected' => ($block == $this->data['blockfile']),
						);
					}
				}
				if (!$modblocks[$name]['blocks']) {
					unset($modblocks[$name]);
				} else {
					ksort($modblocks[$name]['blocks']);
					$modblocks[$name]['blocks'] = array_values($modblocks[$name]['blocks']);
				}
			}
		}
		ksort($modblocks);

		array_unshift($modblocks, $blocks);
		return array_values($modblocks);
	}

	public function getPositionOptions()
	{
		$L10N = \Dragonfly::getKernel()->L10N;
		return array(
			array(
				'value' => 'l',
				'label' => $L10N->get('Left'),
				'selected' => ('l' === $this->data['bposition']),
			),
			array(
				'value' => 'c',
				'label' => $L10N->get('Center Up'),
				'selected' => ('c' === $this->data['bposition']),
			),
			array(
				'value' => 'd',
				'label' => $L10N->get('Center Down'),
				'selected' => ('d' === $this->data['bposition']),
			),
			array(
				'value' => 'r',
				'label' => $L10N->get('Right'),
				'selected' => ('r' === $this->data['bposition']),
			),
		);
	}

	public function getRefreshOptions()
	{
		$L10N = \Dragonfly::getKernel()->L10N;
		return array(
			array(
				'value' => 1800,
				'label' => $L10N->timeReadable(1800),
				'selected' => (1800 == $this->data['refresh']),
			),
			array(
				'value' => 3600,
				'label' => $L10N->timeReadable(3600),
				'selected' => (3600 == $this->data['refresh']),
			),
			array(
				'value' => 10800,
				'label' => $L10N->timeReadable(10800),
				'selected' => (10800 == $this->data['refresh']),
			),
			array(
				'value' => 21600,
				'label' => $L10N->timeReadable(21600),
				'selected' => (21600 == $this->data['refresh']),
			),
			array(
				'value' => 43200,
				'label' => $L10N->timeReadable(43200),
				'selected' => (43200 == $this->data['refresh']),
			),
			array(
				'value' => 86400,
				'label' => $L10N->timeReadable(86400),
				'selected' => (86400 == $this->data['refresh']),
			),
		);
	}

}
