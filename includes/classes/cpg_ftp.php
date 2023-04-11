<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

class cpg_ftp extends \Poodle\FTP
{

	// Constructor
	function __construct($server, $user, $pass, $path, $passive=false)
	{
		if (!$server) { $server = '127.0.0.1'; }
		if (!$this->connect($server, $user, $pass)) { return false; }
		if (!$this->setPassiveMode($passive)) { return false; }
		if (!$this->chdir($path)) { return false; }
	}

	public function close() { $this->disconnect(); }
	public function del($file) { return $this->delete($file); }

	public function up($source, $dest_file, $mimetype=null)
	{
		if (is_resource($source)) {
			$res = $this->fput($dest_file, $source);
		} else if (is_file($source)) {
			$res = $this->uploadFile($dest_file, $source);
		} else {
			$res = $this->uploadString($dest_file, $source);
		}
		if ($res) { $this->chmod($dest_file, 0644); }
		return $res;
	}

	public function exists($name, $path='.')
	{
		try {
			$list = $this->scanDir($path);
			return ($list && in_array($name, $list, true));
		} catch (\Exception $e) {}
		return false;
	}

	public function file_size($filename)
	{
		return $this->size($filename);
	}

	public function mkdir($dirname)
	{
		$res = parent::mkdir($dirname);
		$this->chmod($dirname, 0755);
		return $res;
	}

	public function is_dir($name)
	{
		$dir = $this->getCWD();
		try {
			$this->chdir($name);
			$this->chdir($dir);
			return true;
		} catch (\Exception $e) {}
		return false;
	}

	public function dirlist($path='.', $fileinfo=true)
	{
		if ($fileinfo) {
			$rawlist = $this->rawlist($path);
			if (!$rawlist) return false;
			$list = array();
			foreach ($rawlist as $file) {
				if (preg_match('#([-dl])[rwxst-]{9}.* ([0-9]*) ([a-zA-Z]+[0-9: ]*[0-9]) ([0-9]{2}:[0-9]{2}) (.+)#', $file, $info) ) {
					// Directory, Size, Date, Time, Filename
					$list[] = array(($info[1] == 'd'), intval($info[2]), $info[3], $info[4], trim($info[5]));
				}
			}
			return $list;
		}
		return $this->scanDir($path);
	}

}
