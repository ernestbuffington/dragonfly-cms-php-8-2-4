<?php

namespace Dragonfly\Blocks;

abstract class Block implements \ArrayAccess
{

	protected
		$data = array(
			'bid'       => 0,
			'bkey'      => '',
			'title'     => '',
			'content'   => '',
			'url'       => '',
			'active'    => false,
			'refresh'   => 3600,
			'time'      => 0, // varchar ??
			'blanguage' => '',
			'blockfile' => '',
			'view_to'   => array(0),
			// old system
			'bposition' => '', // l=left, c=center-top, d=center-bottom, r=right
			'in_module' => '',
			'weight'    => 0,
			// table blocks_custom
			'mid'       => 0,
			'side'      => '',
			'toggleid'  => ''
		),

		$label,
		$body,
		$S_BID,
		$S_TITLE,
		$S_CONTENT,
		$S_VISIBLE,
		$S_HIDDEN,
		$S_IMAGE;

	function __construct($bid=0)
	{
		if (is_array($bid)) {
			$data = $bid;
			$bid = (int)$bid['bid'];
		} else {
			$bid = (int)$bid;
			if ($bid) {
				$SQL = \Dragonfly::getKernel()->SQL;
				$data = $SQL->uFetchAssoc("SELECT * FROM {$SQL->TBL->blocks} WHERE bid={$bid}");
			}
		}
		if (!empty($data)) {
			$this->data['bid'] = $bid;
			foreach ($data as $k => $v) {
				$this->__set($k, $v);
			}
		}
	}

	function __get($k)
	{
		if ('label' === $k || 'S_TITLE' === $k) {
			return (defined($this->data['title']) ? constant($this->data['title']) : str_replace('_', ' ',$this->data['title']));
		}
		if ('body' === $k || 'S_CONTENT' === $k) {
			return $this->data['content'];
		}
		if ('S_VISIBLE' === $k) {
			return \Dragonfly\Blocks::isHidden($block['bid']) ? 'style="display:none"' : '';
		}
		if ('S_HIDDEN' === $k) {
			return \Dragonfly\Blocks::isHidden($block['bid']) ? '' : 'style="display:none"';
		}
		if ('S_IMAGE' === $k) {
			return 'themes/'.\Dragonfly::getKernel()->OUT->theme.'/images/'.(\Dragonfly\Blocks::isHidden($block['bid']) ? 'plus' : 'minus');
		}
		if ('id' === $k || 'S_BID' === $k) { $k = 'bid'; }
		else if ('language' === $k) { $k = 'blanguage'; }
		else if ('position' === $k) { $k = 'bposition'; }
		if (array_key_exists($k, $this->data))  {
			return $this->data[$k];
		}
		trigger_error("Property '{$k}' does not exist");
	}

	function __set($k, $v)
	{
		if ('bid' === $k || 'id' === $k) { return; }
		if ('language' === $k) { $k = 'blanguage'; }
		if ('position' === $k) { $k = 'bposition'; }
		if ('view_to' === $k) {
			$this->data[$k] = array_unique(array_map('intval', is_array($v)?$v:explode(',', $v)));
			return;
		}
		if (array_key_exists($k, $this->data)) {
			if (is_int($this->data[$k])) {
				$this->data[$k] = (int)$v;
			} else if (is_bool($this->data[$k])) {
				$this->data[$k] = !!$v;
			} else {
				$this->data[$k] = trim($v);
			}
		} else {
			trigger_error("Property '{$k}' does not exist");
		}
	}

	function __isset($k)
	{
		return array_key_exists($k, $this->data);
	}

	public function offsetExists($k)  { return array_key_exists($k, $this->data); }
	public function offsetGet($k)     { return $this->__get($k); }
	public function offsetSet($k, $v) { $this->__set($k, $v); }
	public function offsetUnset($k)   {}
}
