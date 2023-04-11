<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\File\Archive;

class TAR extends \Poodle\File\Archive
{
	function __construct($filename, $type='')
	{
		$this->filename = $filename;
		if ('bzip2' === $type) {
			$this->f = 'Poodle\\File\\Stream\\Bzip2';
		} else if ('gzip' === $type) {
			$this->f = 'Poodle\\File\\Stream\\Gzip';
		} else {
			$this->f = 'Poodle\File\\Stream\\Raw';
		}
		$this->dec = array('mode', 'uid', 'gid', 'size', 'mtime', 'crc', 'type');
		$this->load_toc();
	}

	public function extract($id, $to=false)
	{
		if (empty($this->toc['files'][$id])) {
			if (empty($this->toc['dirs'][$id])) {
				trigger_error("'$id' is not a valid entry.", E_USER_ERROR);
			} else {
				trigger_error("'$id' is not a file.", E_USER_ERROR);
			}
		}
		if ($fp = new $this->f($this->filename)) {
			$wfp  = null;
			$file = $this->toc['files'][$id];
			# go to file offset and skip header as well
			$fp->seek($file['offset']+512, SEEK_SET);
			# now load/write the file
			if ($to && !is_resource($to)) {
				$file['tmp_name'] = rtrim($to,'/').'/'.md5($file['filename'].$file['crc']);
				if (!($wfp = fopen($file['tmp_name'], 'wb'))) {
					fclose($wfp);
					return false;
				}
			} else {
				$file['data'] = '';
			}
			if (!empty($file['size'])) {
				$blocksize = ceil($file['size']/512)*512;
				while ($blocksize > 0) {
					$size = min($blocksize, 8192);
					$tmp = $fp->read($size);
					$blocksize -= 8192;
					if ($wfp) {
						fwrite($wfp, $tmp);
					} else if ($to) {
						fwrite($to, $tmp);
					} else {
						$file['data'] .= $tmp;
					}
				}
				unset($tmp);
			}
			if ($wfp) { fclose($wfp); }
			$fp->close();
			return $file;
		}
		return false;
	}

	protected function load_toc()
	{
		if ($fp = new $this->f($this->filename)) {
			$i = $offset = 0;
			$dir = null;
			while (!$fp->eof()) {
				$entry = $this->ReadFileHeader($fp);
				if (!$entry) { break; }
				$entry['offset'] = $offset;
				if (substr($entry['filename'],-1) !== '/') {
					if (!preg_match('#(^|/)PaxHeader/#',$entry['filename'])) {
						$this->toc['files'][$i] = $entry;
						$dir['entries'][$i] = &$this->toc['files'][$i];
					}
				} else {
					if (!preg_match('#(^|/)PaxHeader/#',$entry['filename'])) {
						$this->toc['dirs'][$i] = $entry;
						$dir = &$this->toc['dirs'][$i];
					} else {
						$dir = null;
					}
				}
				$blocksize = (ceil($entry['size']/512)*512);
				$offset += (512+$blocksize);
				++$i;
				$fp->seek($offset, SEEK_SET);
			}
			$this->toc['cd']['entries'] = $i;
			$fp->close();
			return true;
		}
		return false;
	}

	private function ReadFileHeader(&$fp)
	{
		$data = $fp->read(512);
		if (strlen($data) !== 512) { return false; }
		$header = unpack("a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/a8crc/a1type/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155path", $data);
		if (empty($header['filename'])) { return false; }
		foreach ($header as $k => $v) {
			$header[$k] = in_array($k, $this->dec) ? octdec(trim($v)) : trim($v);
		}
		$crc = 0;
		for ($i = 0;   $i < 148; ++$i) { $crc += ord(substr($data,$i,1)); }
		for ($i = 148; $i < 156; ++$i) { $crc += ord(' '); }
		for ($i = 156; $i < 512; ++$i) { $crc += ord(substr($data,$i,1)); }
		if ($header['crc'] > 0 && $header['crc'] !== $crc) {
			\Poodle\Debugger::trigger("Checksum of '{$header['filename']}' incorrect.", __DIR__);
			return false;
		}
		return $header;
	}

}
