<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/cpg_ftpfake.php,v $
  $Revision: 9.4 $
  $Author: akamu $
  $Date: 2007/04/23 11:15:38 $
**********************************************/

class cpg_ftpfake {

	var $path;

	// Constructor
	function cpg_ftpfake($server, $user, $pass, $path, $passive=false) {
		$path = $this->_construct_path(BASEDIR, $path);
		if (is_dir($path)) {
			$this->path = $path;
		}
		else { trigger_error('Path failed', E_USER_WARNING); }
	}

	function close() {
		if (!$this->path) return false;
		clearstatcache();
		$this->path = false;
	}

	function _construct_path($path, $new) {
		$dirs = explode('/', $path);
		$new  = explode('/', $new);
		while (empty($dirs[(count($dirs)-1)])) { array_pop($dirs); }
		foreach($new as $dir) {
			if ('..' == $dir) { array_pop($dirs); }
			elseif (!empty($dir)) $dirs[] = $dir;
		}
		return implode('/', $dirs);
	}

	function del($file) {
		if (!$this->path) return false;
		if (is_dir($this->path."/$file")) {
			$return = rmdir($this->path."/$file");
		} else {
			$return = unlink($this->path."/$file");
		}
		clearstatcache();
		return $return;
	}

	function up($source, $dest_file, $mimetype) {
		if (!$this->path) return false;
		$res = false;
		if (is_resource($source)) {
//			$mode = (preg_match('/text/i', $mimetype) || preg_match('/html/i', $mimetype)) ? FTP_ASCII : FTP_BINARY;
//			$res = ftp_fput($this->connect_id, $dest_file, $source, $mode);
		} else if (is_uploaded_file($source)) {
			$res = move_uploaded_file($source, $this->path."/$dest_file");
		}
		if ($res) chmod($this->path."/$dest_file", 0644);
		return $res;
	}

	function exists($name) {
		if (!$this->path) return false;
		return file_exists($this->path."/$name");
	}

	function file_size($filename) {
		return filesize($this->path."/$filename");
	}

	function mkdir($dirname) {
		if (!$this->path) return false;
		return mkdir($this->path."/$dirname", 0755);
	}

	function chdir($path) {
		if (!$this->path) return false;
		$path = $this->_construct_path($this->path, $path);
		if (is_dir($path)) {
			$this->path = $path;
			return true;
		} else {
			return false;
		}
	}

	function is_dir($dirname) {
		if (!$this->path) return false;
		return is_dir($this->path."/$dirname");
	}

	function filelist($path='.', $fileinfo=true) {
		if (!$this->path) return false;
		$path = $this->path.(($path[0] == '.') ? '' : "/$path");
		$handle = opendir($path);
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && (!ereg("thumb_",$file)) && (!ereg("normal_",$file))) {
				if ($fileinfo) {
					// Directory, Size, Date, Time, Filename
					$list[] = array(
						is_dir("$path/$file"),
						intval(filesize("$path/$file")),
						filemtime("$path/$file"),
						filemtime("$path/$file"),
						"$file"
					);
				} else {
					$list[] = $file;
				}
			}
		}
		closedir($handle);
		return $list;
	}
	function dirlist($path='.', $fileinfo=true) {
		if (!$this->path) return false;
		$path = $this->path.(($path[0] == '.') ? '' : "/$path");
		$handle = opendir($path);
		//http://us3.php.net/manual/en/function.readdir.php
		// Note that !== did not exist until 4.0.0-RC2
		// while ($file = readdir($handle)) {
		while (false !== ($file = readdir($handle))) {
			if (!ereg("[.]",$file)) {
				if ($fileinfo) {
					// Directory, Size, Date, Time, Filename
					$list[] = array(
						is_dir("$path/$file"),
						intval(filesize("$path/$file")),
						filemtime("$path/$file"),
						filemtime("$path/$file"),
						"$file"
					);
				} else {
					$list[] = "$file";
				}
			}
		}
		closedir($handle);
		return $list;
	}

}
