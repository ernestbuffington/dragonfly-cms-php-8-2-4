<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\File\Stream;

class Bzip2
{

	private $fp;
	private $offset;
	const type = 'bzip2';

	function __construct($filename)
	{
		$this->fp = bzopen($filename, 'rb');
		if (!$this->fp) { return false; }
		$this->offset = 0;
	}
	public function type() { return self::type; }

	public function eof()
	{
		if (!$this->fp) { return true; }
		return feof($this->fp);
	}

	public function read($size=1024)
	{
		if (!$this->fp) { return false; }
		$this->offset += $size;
		$data = '';
		# bzread has a max of 8192 bytes on some systems
		while ($size > 0 && !feof($this->fp)) {
			$blocksize = min($size, 8192);
			$data .= bzread($this->fp, $blocksize);
			$size -= $blocksize;
		}
		return $data;
	}

	# there's no easy way for bzip :(
	public function gets()
	{
		if (!$this->fp) { return false; }
		$data = '';
		while (!feof($this->fp) && substr($data, -1) !== "\n") {
			$data .= bzread($this->fp, 1);
		}
		return $data;
	}

	public function close()
	{
		if (!$this->fp) { return false; }
		$ret = bzclose($this->fp);
		$this->fp = false;
		return $ret;
	}

	# bzip doesn't allow seeking so we emulate it
	public function seek($offset, $whence=SEEK_CUR)
	{
		if (!$this->fp) { return false; }
		if ($whence === SEEK_SET) { $offset -= $this->offset; }
		if ($offset <= 0) { return false; }
		$this->offset += $offset;
		while ($offset > 0 && !feof($this->fp)) {
			$blocksize = min($offset, 8192);
			bzread($this->fp, $blocksize);
			$offset -= $blocksize;
		}
		return true;
	}

}
