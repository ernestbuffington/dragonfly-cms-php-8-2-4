<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Stream\Wrapper;

class Cache
{
	protected
		$data = null,
		$datapos = 0,
		$datalen = 0,
		$options,
		$key;

	protected function getData()
	{
		if (is_null($this->data)) {
			$CACHE = \Poodle::getKernel()->CACHE;
			if ($CACHE->exists($this->key)) {
				$this->data    = $CACHE->get($this->key);
				$this->datalen = strlen($this->data);
			} else {
				$this->data = '';
			}
		}
	}

	public function stream_open($path, $mode, $options, $opened_path)
	{
		if (!preg_match('#://(.+)$#D', $path, $match)) { return false; }
		$this->key = $match[1];
		$this->options = (int)$options;
		return true;
	}

	public function stream_close()
	{
		$this->data = null;
	}

	public function stream_eof()
	{
		$this->getData();
		return $this->datapos >= $this->datalen;
	}

	public function stream_read($bytes)
	{
		if ($this->stream_eof()) { return ''; }
		$r = substr($this->data, $this->datapos, $bytes);
		$this->datapos += strlen($r);
		return $r;
	}

	public function stream_write($data)
	{
		$this->data .= $data;
		return $CACHE->set($this->key, $this->data) ? strlen($data) : 0;
	}

	public function stream_stat() { return false; }
	public function url_stat($path, $flags) { return false; }

	private function error($msg)
	{
		if ($this->options & STREAM_REPORT_ERRORS) { trigger_error($msg, E_USER_WARNING); }
		return false;
	}
}
