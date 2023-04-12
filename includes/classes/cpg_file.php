<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/cpg_file.php,v $
  $Revision: 9.39 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:41 $
**********************************************/

class CPG_File {

	function check_safe_mode($file) {
		if (ini_get('safe_mode')) {
			if (ini_get('safe_mode_include_dir')) {
				//SEE IF SAFE MODE IS SETUP CORRECTLY
				if (strpos(ini_get('safe_mode_include_dir'), dirname($file['tmp_name']))) {
					trigger_error('Safe mode is not setup properly, "'.dirname($file['tmp_name']).'" must be inside a path of the php config safe_mode_include_dir "'.ini_get('safe_mode_include_dir').'".');
					//return false;
				}
			}
		}
		//return true;
/*
		global $cpgdebugger;
		if (isset($cpgdebugger->report[__FILE__])) {
			$last = count($cpgdebugger->report[$file])-1;
			return eregi('SAFE MODE Restriction', $cpgdebugger->report[$file][$last]);
		}
		return false;
*/
	}

	function move_upload($file, $newfile) {
		if (!is_uploaded_file($file['tmp_name'])) {
			switch($file['error']) {
			  case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
				trigger_error('The file you are trying to upload is too big.', E_USER_ERROR);
			  case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
				trigger_error('The file you are trying to upload is too big.', E_USER_ERROR);
			  case 3: //uploaded file was only partially uploaded
				trigger_error('The file you are trying upload was only partially uploaded.', E_USER_ERROR);
			  case 4: //no file was uploaded
				trigger_error('No file was uploaded.', E_USER_WARNING);
			  case 6: //introduced in 4.3.10 and 5.0.3 
				trigger_error('Missing a temporary folder.', E_USER_ERROR);
			  case 7: //introduced in 5.1.0
				trigger_error('Failed to write file to disk.', E_USER_ERROR);
				break;
			  case 0: //no error, the file was uploaded with success
			  default: //a default error, just in case!  :)
				trigger_error('There was a problem with your upload.', E_USER_ERROR);
				break;
			}
			return false;
		}
		if (!CPG_File::analyze_path(dirname($newfile))) { return false; }
		if (!move_uploaded_file($file['tmp_name'], $newfile)) {
			if (!copy($file['tmp_name'], $newfile)) {
				trigger_error('Couldn\'t move the uploaded file.', E_USER_WARNING);
				return false;
			}
		}
		chmod($newfile, (PHP_AS_NOBODY ? 0666 : 0644));
		return true;
	}

	function write($filename, &$content, $mode='wb') {
		if (!CPG_File::analyze_path(dirname($filename))) { return false; }
		if (!$fp = fopen($filename, $mode)) {
			trigger_error("Cannot open file ($filename)", E_USER_WARNING);
			return false;
		}
		flock($fp, LOCK_EX);
		$bytes_written = fwrite($fp, $content);
		flock($fp, LOCK_UN);
		fclose($fp);
		if ($bytes_written === FALSE) {
			unlink($filename);
			trigger_error("Cannot write to file ($filename)", E_USER_WARNING);
			return false;
		}
		chmod($filename, (PHP_AS_NOBODY ? 0666 : 0644));
		return $bytes_written;
	}

	function copy_special($oldfile, $newfile) {
		$fp = null;
  if (!CPG_File::analyze_path(dirname($newfile))) { return false; }
		if (!($of = fopen($oldfile, 'rb'))) {
			return false;
		}
		if (!($nf = fopen($newfile, 'wb'))) {
			fclose($of);
			return false;
		}
		while (!feof($of)) {
			if (fwrite($fp, fread($of, 2048)) === FALSE) {
				fclose($of);
				fclose($fp);
				return false;
			}
		}
		fclose($of);
		fclose($fp);
		chmod($newfile, (PHP_AS_NOBODY ? 0666 : 0644));
		return true;
	}

	function secure_download(&$error, $filename, $realname='') {
		$chunksize = (2048); // how many bytes per chunk
		if (empty($realname)) { $realname = $filename; }
		if (strpos($filename,'://')) {
			// send remote file
			$rdf = parse_url($filename);
			if (!isset($rdf['host'])) return false;
			if (!isset($rdf['port'])) $rdf['port'] = 80;
			if (!isset($rdf['query'])) $rdf['query'] = '';
			$fp = fsockopen($rdf['host'], $rdf['port'], $errno, $errstr, 15);
			if ($fp === false) {
				$error = "$errno: $errstr";
				trigger_error($error, E_USER_WARNING);
				return false;
			}
			fputs($fp, 'GET ' . $rdf['path'] . $rdf['query'] . " HTTP/1.0\r\n");
			fputs($fp, 'User-Agent: Dragonfly Passthru ('.getlink('credits', true, true).")\r\n");
			fputs($fp, 'Referer: ' . get_uri() ."\r\n");
			fputs($fp, 'HOST: ' . $rdf['host'] . "\r\n\r\n");
			$data = rtrim(fgets($fp, 512));
			if (!preg_match('# 200 OK#m', $data)) {
				$error = $data;
				trigger_error($data, E_USER_WARNING);
				return false;
			}
			while (ob_end_clean());
			// Read all headers
			while (!empty($data)) {
				$data = rtrim(fgets($fp, 300)); // read lines
				if (preg_match('#(Content\-Length|Content\-Type|Last\-Modified|Content\-Length): #mi', $data)) {
					header($data);
				}
			}
		} else {
			if (preg_match('#\.(\.|php$)#m', $filename)) {
				$error = "$filename isn't allowed to be downloaded";
				trigger_error($error, E_USER_WARNING);
				return false;
			}
			if (!($fp = fopen($filename, 'rb'))) {
				$error = "$filename could not be opened";
				trigger_error($error, E_USER_WARNING);
				return false;
			}
			while (ob_end_clean());
			$mimetype = ($img = getimagesize($filename)) ? $img['mime'] : '';
			// send local file
			if (!strstr($mimetype, 'image')) {
				$ext = explode('.', $realname);
				$ext = strtolower(array_pop($ext));
				if ($ext == 'bz2') { $mimetype = 'application/bzip2'; }
				elseif ($ext == 'gz' || $ext == 'tgz') { $mimetype = 'application/x-gzip'; }
				elseif ($ext == 'gtar') { $mimetype = 'application/x-gtar'; }
				elseif ($ext == 'tar') { $mimetype = 'application/x-tar'; }
				elseif ($ext == 'zip') { $mimetype = 'application/zip'; }
				elseif ($ext == 'wma') { $mimetype = 'audio/x-ms-wma'; }
				elseif ($ext == 'wmv') { $mimetype = 'video/x-ms-wmv'; }
				else { $mimetype = 'application/octet'.(preg_match('#(Opera|compatible; MSIE)#m', $_SERVER['HTTP_USER_AGENT']) ? 'stream' : '-stream'); }
			}
//			header('Content-Type: "'.mime_content_type(basename($realname)).'"'); // PHP >= 4.3.0
			header('Content-Type: '.$mimetype.'; name="'.basename($realname).'"');
			header('Content-Length: '.filesize($filename));
		}
		header('Content-Encoding:');
//		header('Content-Disposition: inline; filename="'.basename($realname).'"');
		header('Content-Disposition: attachment; filename="'.basename($realname).'"');
		set_time_limit(0);
		while (!feof($fp)) { print fread($fp, $chunksize); }
		return fclose($fp);
	}

	function analyze_path($path) {
		if (empty($path)) return false;
		if ($path[0] == '.') { $path = substr($path, 1); }
		if ($path[0] == '.') { $path = substr($path, 1); }
		if ($path[0] == '/') { $path = substr($path, 1); }
		$parts = (preg_match('#\/#m', $path) ? explode('/', $path) : array($path));
		$npath = '';
		while ($dir = array_shift($parts)) {
			$npath .= "$dir/";
			if (!is_dir($npath)) {
				if (!mkdir($npath, (PHP_AS_NOBODY ? 0777 : 0755))) {
					trigger_error("Couldn't create $npath for $path", E_USER_WARNING);
					return false;
				}
			}
		}
		return true;
	}

	function analyze_system() {
		$analized = [];
  $disabled = ini_get('disable_functions'); // string
		$analized['set_time_limit'] = !preg_match('#set_time_limit#mi', $disabled);
		$analized['fsockopen']      = !preg_match('#fsockopen#mi', $disabled);
		$analized['fopen']          = !preg_match('#fopen#mi', $disabled);
		$analized['url_fopen']      = ini_get('allow_url_fopen'); // 0 or 1

		$analized['upload']['active']  = ini_get('file_uploads');   // 0 or 1
		$analized['upload']['tmp_dir'] = ini_get('upload_tmp_dir'); // String, if empty it uses system default
		$analized['upload']['max']     = ini_get('upload_max_filesize'); // String, default = 2M
		$analized['safe_mode']['active'] = ini_get('safe_mode');         // 0 or 1, UID compare
		$analized['safe_mode']['gid']    = ini_get('safe_mode_gid');     // 0 or 1, GID compare i/o UID
		$analized['safe_mode']['include_dir'] = ini_get('safe_mode_include_dir'); // String
		$analized['safe_mode']['exec_dir']    = ini_get('safe_mode_exec_dir');    // String
		$analized['enable_dl']    = ini_get('enable_dl');    // 0 or 1, dl('php_mime_magic.dll');
		$analized['open_basedir'] = ini_get('open_basedir'); // NULL or String
/*
max_execution_time = 30 ; Maximum execution time of each script, in seconds
max_input_time = 60     ; Maximum amount of time each script may spend parsing request data
memory_limit = 8M       ; Maximum amount of memory a script may consume (8MB)
*/
		return $analized;
	}
}
