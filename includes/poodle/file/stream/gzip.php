<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\File\Stream;

class Gzip
{

	private $fp;
	private $offset;
	const type = 'gzip';

	function __construct($filename)
	{
		$this->fp = gzopen($filename, 'rb');
		if (!$this->fp) { return false; }
		$this->offset = 0;
	}
	public function type() { return self::type; }

	public function eof()
	{
		if (!$this->fp) { return true; }
		return gzeof($this->fp);
	}

	public function read($size=1024)
	{
		if (!$this->fp) { return false; }
		$this->offset += $size;
		return gzread($this->fp, $size);
	}

	public function gets()
	{
		if (!$this->fp) { return false; }
		$data = '';
		while (!gzeof($this->fp) && substr($data, -1) !== "\n") {
			$data .= gzgets($this->fp, 8192);
		}
		return $data;
	}

	public function close()
	{
		if (!$this->fp) { return false; }
		$ret = gzclose($this->fp);
		$this->fp = false;
		return $ret;
	}

	# gzseek can be extremely slow so we only allow forward seeking
	public function seek($offset, $whence=SEEK_CUR)
	{
		if (!$this->fp) { return false; }
		if ($whence === SEEK_SET) { $offset -= $this->offset; }
		if ($offset <= 0) { return false; }
		$this->offset += $offset;
		gzread($this->fp, $offset);
		return true;
	}

}
