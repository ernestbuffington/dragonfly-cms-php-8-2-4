<?php

namespace Dragonfly\Blocks;

class Custom extends Block
{

	function __get($k)
	{
		if ('body' === $k || 'S_CONTENT' === $k) {
			return \Dragonfly\BBCode::decodeAll($this->data['content'], 1, true);
		}

		return parent::__get($k);
	}

}
