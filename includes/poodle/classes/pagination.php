<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

class Pagination
{
	protected
		$left    = 3,
		$padding = 1,
		$right   = 3,
		$spacer  = '…',

		$uri,
		$limit   = 1,
		$offset  = 0,
		$current = 1,
		$pages   = 1;

	function __construct($uri, $count, $offset, $limit=1)
	{
		if (!$uri) {
			$uri = trim(preg_replace('#(^|&)(offset|limit)=[^&]+#','',$_SERVER['QUERY_STRING']),'&');
			$uri = '?'.($uri ? $uri.'&' : '');
			$uri .= 'limit=${limit}&offset=${offset}';
		}
		$this->uri     = $uri;
		$this->limit   = (int)$limit; // max(1,(int)$limit)
		$this->offset  = max(0,(int)$offset);
		$this->current = (int)floor($this->offset / $this->limit)+1;
		$this->pages   = ceil($count / $this->limit);
	}

	function __set($k, $v)
	{
		switch ($k)
		{
		case 'left':
		case 'padding':
		case 'right':
			$v = max(0, (int)$v);
		case 'spacer':
			$this->$k = $v;
			return;
		}
		if (property_exists($this, $k)) {
			throw new \ErrorException('Cannot access protected property ' . __CLASS__ . '::' . $k);
		}
	}

	public function prev()
	{
		if (1 < $this->current) {
			return $this->createURI($this->current-1, $this->offset-$this->limit);
		}
	}

	public function next()
	{
		if ($this->current < $this->pages) {
			return $this->createURI($this->current+1, $this->offset+$this->limit);
		}
	}

	public function current()
	{
		return $this->current;
	}

	public function count()
	{
		return $this->pages;
	}

	/**
	 * items() contains a default of 11 items
	 *     left + space + padding + current + padding + space + right
	 *       3  +   1   +    1    +    1    +    1    +   1   +   3
	 *
	 * The [space] can also be a page, as it is odd to generate:
	 *     1, 2, 3, [space], 5, current
	*/
	public function items()
	{
		$items = array();
		if ($this->pages > 1) {
			$visible = 3 + $this->left + $this->right + $this->padding*2;

			// left section
			$r = $this->left + 2 + $this->padding*2;
			if ($this->pages <= $visible) { $last = $this->pages; }
			else if ($this->current < $r) { $last = $r; }
			else { $last = $this->left; }
			for ($i = 1; $i <= $last; ++$i) {
				$items[] = array(
					'page' => $i,
					'uri'  => $this->createURI($i),
					'current' => ($i === $this->current)
				);
			}

			if ($this->pages >= $i)
			{
				$spacer = array('page'=>$this->spacer,'uri'=>null,'current'=>false);
				$items[] = $spacer;

				$r = $this->pages - $this->right - $this->padding*2;

				// middle section
				if ($last === $this->left && $this->current < $r) {
					$i  = $this->current-$this->padding;
					$mr = $this->current+$this->padding;
					for (; $i <= $mr; ++$i) {
						$items[] = array(
							'page' => $i,
							'uri'  => $this->createURI($i),
							'current' => ($i === $this->current)
						);
					}
					$items[] = $spacer;
				}

				// end section
				$i = ($this->current < $r) ? $this->pages+1-$this->right : $r-1;
				for (; $i <= $this->pages; ++$i) {
					$items[] = array(
						'page' => $i,
						'uri'  => $this->createURI($i),
						'current' => ($i === $this->current)
					);
				}
			}

		}
		return $items;
	}

	public function allItems()
	{
		$items = array();
		if ($this->pages > 1) {
			for ($i = 1; $i <= $this->pages; ++$i) {
				$items[] = array(
					'page' => $i,
					'uri'  => $this->createURI($i),
					'current' => ($i === $this->current)
				);
			}
		}
		return $items;
	}

	protected function createURI($page, $offset=null)
	{
		if ($page != $this->current) {
			return str_replace(
				array('${page}', '${offset}', '${limit}'),
				array($page, is_null($offset)?($page-1)*$this->limit:$offset, $this->limit),
				$this->uri);
		}
	}

}
