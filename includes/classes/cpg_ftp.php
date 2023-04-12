<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/cpg_ftp.php,v $
  $Revision: 9.3 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:41 $
**********************************************/

class cpg_ftp {

	var $connect_id;

	// Constructor
	function cpg_ftp($server, $user, $pass, $path, $passive=false) {
		if ($server == '') { $server = 'localhost'; }
		if (!function_exists('ftp_connect')) {
			if (is_admin()) { trigger_error('PHP FTP module not active', E_USER_ERROR); }
			return false;
		}
		if (!($this->connect_id = ftp_connect($server))) { return false; }
		if (!ftp_login($this->connect_id, $user, $pass)) { return false; }
		if (!ftp_pasv($this->connect_id, $passive)) { return false; }
		if (!ftp_chdir($this->connect_id, $path)) { return false; }
	}

	function close() {
		if (!$this->connect_id) return false;
		if (PHPVERS >= 42) ftp_close($this->connect_id);
		else ftp_quit($this->connect_id);
	}

	function del($file) {
		if (!$this->connect_id) return false;
		return ftp_delete($this->connect_id, $file);
	}

	function up($source, $dest_file, $mimetype) {
		if (!$this->connect_id) return false;
		$mode = (preg_match('/text/i', $mimetype) || preg_match('/html/i', $mimetype)) ? FTP_ASCII : FTP_BINARY;
		if (is_resource($source)) {
			$res = ftp_fput($this->connect_id, $dest_file, $source, $mode);
		} else {
			$res = ftp_put($this->connect_id, $dest_file, $source, $mode);
		}
		if ($res) { ftp_site($this->connect_id, 'CHMOD 0644 ' . $dest_file); }
		return $res;
	}

	function exists($name, $path='.') {
		if (!$this->connect_id) return false;
		// get contents of the current directory
		$list = ftp_nlist($this->connect_id, $path);
		for ($i = 0; $i < count($list); $i++) {
			if ($list[$i] == $name) return true;
		}
		return false;
	}

	function file_size($filename) {
		return ftp_size($this->connect_id, $filename);
	}

	function mkdir($dirname) {
		if (!$this->connect_id) return false;
		$res = ftp_mkdir($this->connect_id, $name);
		if ($res) ftp_site($this->connect_id, 'CHMOD 0755 ' . $dirname);
		return $res;
	}

	function chdir($path) {
		if (!$this->connect_id) return false;
		return ftp_chdir($this->connect_id, $path);
	}

	function is_dir($name) {
		if (!$this->connect_id) return false;
		if (substr($name, -1) == '/') $name = substr($name, 0, -1);
		$dirname = strrchr($name, '/');
		if ($dirname) {
			$path = substr($name, 0, -strlen($dirname));
			$dirname = substr($dirname, 0, -1);
		} else {
			$path = '.';
			$dirname = $name;
		}
		$rawlist = ftp_rawlist($this->connect_id, $path);
		for ($i = 0; $i < count($rawlist); $i++) {
			if (ereg('([-d])[rwxst-]{9}.* ([0-9]*) ([a-zA-Z]+[0-9: ]*[0-9]) ([0-9]{2}:[0-9]{2}) (.+)', $rawlist[$i], $info) ) {
				if ($info[1] == 'd' && $info[5] == $dirname) { return true; }
			}
		}
		return false;
	}

	function dirlist($path='.', $fileinfo=true) {
		if (!$this->connect_id) return false;
		if ($fileinfo) {
			$rawlist = ftp_rawlist($this->connect_id, $path);
			if (!$rawlist) return false;
			$list = array();
			for ($i = 0; $i < count($rawlist); $i++) {
				if (ereg('([-d])[rwxst-]{9}.* ([0-9]*) ([a-zA-Z]+[0-9: ]*[0-9]) ([0-9]{2}:[0-9]{2}) (.+)', $rawlist[$i], $info) ) {
					// Directory, Size, Date, Time, Filename
					$list[] = array(($info[1] == 'd'), intval($info[2]), $info[3], $info[4], trim($info[5]));
				}
			}
			return $list;
		} else {
			return ftp_nlist($this->connect_id, $path);
		}
	}

}
