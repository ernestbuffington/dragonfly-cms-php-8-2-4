<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\File\Stream;

class Raw
{

	private $fp;
	const type = 'default';

	function __construct($filename)
	{
		$this->fp = fopen($filename, 'rb');
		if (!$this->fp) { return false; }
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
		return fread($this->fp, $size);
	}

	public function gets()
	{
		if (!$this->fp) { return false; }
		$data = '';
		while (!feof($this->fp) && substr($data, -1) !== "\n") {
			$data .= fgets($this->fp, 8192);
		}
		return $data;
	}

	public function close()
	{
		if (!$this->fp) { return false; }
		$ret = fclose($this->fp);
		$this->fp = false;
		return $ret;
	}

	# only allow forward seeking to stay compatible with bzip2 functionality
	public function seek($offset, $whence=SEEK_CUR)
	{
		if (!$this->fp) { return false; }
		if ($whence === SEEK_SET) { $offset -= ftell($this->fp); }
		if ($offset <= 0) { return false; }
		return (fseek($this->fp, $offset, SEEK_CUR) === 0);
	}

}
