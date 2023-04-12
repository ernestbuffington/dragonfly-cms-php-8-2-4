<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/archive/tar.php,v $
  $Revision: 1.7 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:43 $
**********************************************/

class archive_tar
{

	var $toc;
	var $filename;
	var $type = 'tar';

	function __construct($filename, $type='')
	{
		$this->filename = $filename;
		$this->type = 'tar';
		if ($type == 'bzip2') {
			$this->f = array('open' => 'bzopen', 'read' => 'bzread', 'close' => 'bzclose', 'seek' => 'bzseek');
		} else if ($type == 'gzip') {
			$this->f = array('open' => 'gzopen', 'read' => 'gzread', 'close' => 'gzclose', 'seek' => 'gzseek');
		} else {
			$this->f = array('open' => 'fopen', 'read' => 'fread', 'close' => 'fclose', 'seek' => 'fseek');
		}
		$this->dec = array('mode', 'uid', 'gid', 'size', 'mtime', 'crc', 'type');
		$this->load_toc();
	}

	function load_toc()
	{
		if ($fp = $this->f['open']($this->filename, 'rb')) {
			$i = $offset = 0;
			$dir = NULL;
			while (!feof($fp)) {
				$entry = $this->_ReadFileHeader($fp);
				if (!$entry) { break; }
				$entry['offset'] = $offset;
				if (substr($entry['filename'],-1) != '/') {
					$this->toc['files'][$i] = $entry;
					$dir['entries'][$i] =& $this->toc['files'][$i];
				} else {
					$this->toc['dirs'][$i] = $entry;
					$dir =& $this->toc['dirs'][$i];
				}
				$blocksize = (ceil($entry['size']/512)*512);
				$offset += (512+$blocksize);
				++$i;
				if ($this->f['seek'] == 'bzseek') {
					# bzread has a max of 8192 bytes on some systems
					while ($blocksize > 0) {
						$size = min($blocksize, 8192);
						$this->f['read']($fp, $size);
						$blocksize -= 8192;
					}
				} else {
					$this->f['seek']($fp, $offset);
				}
			}
			$this->toc['cd']['entries'] = $i;
			$this->f['close']($fp);
			return true;
		}
		return false;
	}

	function extract($id, $to=false)
	{
		if (empty($this->toc['files'][$id])) {
			if (empty($this->toc['dirs'][$id])) {
				trigger_error("'$id' is not a valid entry.", E_USER_ERROR);
			} else {
				trigger_error("'$id' is not a file.", E_USER_ERROR);
			}
		}
		if ($fp = $this->f['open']($this->filename, 'rb')) {
			$file = $this->toc['files'][$id];
			# go to file offset and skip header as well
			if ($this->f['seek'] == 'bzseek') {
				$blocksize = $file['offset']+512;
				# bzread has a max of 8192 bytes on some systems
				while ($blocksize > 0) {
					$size = min($blocksize, 8192);
					$this->f['read']($fp, $size);
					$blocksize -= 8192;
				}
			} else {
				$this->f['seek']($fp, $file['offset']+512);
			}
			# now load/write the file
			if ($to) {
				$file['tmp_name'] = $to.'/'.md5($file['filename'].$file['crc']);
				if (!($wfp = fopen($file['tmp_name'], "wb"))) {
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
					$tmp = $this->f['read']($fp, $size);
					$blocksize -= 8192;
					if ($to) {
						fwrite($wfp, $tmp);
					} else {
						$file['data'] .= $tmp;
					}
				}
				unset($tmp);
			}
			if ($to) { fclose($wfp); }
			$this->f['close']($fp);
			return $file;
		}
		return false;
	}

	function _ReadFileHeader(&$fp)
	{
		$data = $this->f['read']($fp, 512);
		if (strlen($data) != 512) { return false; }
		$header = unpack("a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/a8crc/a1type/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155path", $data);
		if (empty($header['filename'])) { return false; }
		foreach ($header as $k => $v) {
			$header[$k] = in_array($k, $this->dec) ? octdec(trim($v)) : trim($v);
		}
		$crc = 0;
		for ($i = 0;   $i < 148; $i++) { $crc += ord(substr($data,$i,1)); }
		for ($i = 148; $i < 156; $i++) { $crc += ord(' '); }
		for ($i = 156; $i < 512; $i++) { $crc += ord(substr($data,$i,1)); }
		if ($header['crc'] > 0 && $header['crc'] != $crc) {
			trigger_error("Checksum of '$header[filename]' incorrect.", E_USER_WARNING);
			return false;
		}
		return $header;
	}

}
