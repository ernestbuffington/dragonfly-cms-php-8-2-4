<?php

namespace Dragonfly\Blocks;

class RSS extends Block
{

	function __get($k)
	{
		if ('body' === $k || 'S_CONTENT' === $k) {
			if ($this->data['time'] < (time()-$this->data['refresh'])) {
				if ($content = \Dragonfly\RSS::display($this->data['url'])) {
					$this->data['content'] = $content;
					$this->data['time'] = time();
					\Dragonfly::getKernel()->SQL->TBL->blocks->update(array(
						'content' => $content,
						'time' => time()
					), "bid={$this->data['bid']}");
				} else {
					trigger_error(_RSSPROBLEM.' ('.$this->data['title'].')', E_USER_WARNING);
				}
			}
			return str_replace('_HREADMORE', _HREADMORE, $this->data['content']);
		}

		return parent::__get($k);
	}

}
