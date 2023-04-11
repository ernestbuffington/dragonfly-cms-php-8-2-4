<?php

namespace Dragonfly\Blocks;

class File extends Block
{

	function __get($k)
	{
		if ('body' === $k || 'S_CONTENT' === $k) {
			if (is_file($this->data['blockfile'])) {
				include($this->data['blockfile']);
				if (!empty($content) && 'ERROR' != $content) {
					return $content;
				}
				trigger_error("{$this->data['blockfile']} - "._BLOCKPROBLEM, E_USER_WARNING);
			}
			return false;
		}

		return parent::__get($k);
	}

}
