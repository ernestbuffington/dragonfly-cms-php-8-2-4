<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

class CPG_File
{

	/**
	 * @deprecated Use $_FILES->getAsFileObject()->moveTo()
	 */
	public static function move_upload($file, $newfile)
	{
		trigger_deprecated('Use $_FILES->getAsFileObject(\'INPUT-POST-KEY\')->moveTo()');
		if (!is_uploaded_file($file['tmp_name'])) {
			switch ($file['error']) {
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
			  case 8: //introduced in 5.2.0
				trigger_error('File upload stopped by extension.', E_USER_ERROR);
			  case 0: //no error, the file was uploaded with success
			  default: //a default error, just in case!  :)
				trigger_error('There was a problem with your upload.', E_USER_ERROR);
				break;
			}
			return false;
		}
		if (!mkdir(dirname($newfile), 0777, true)) { return false; }
		if (!move_uploaded_file($file['tmp_name'], $newfile)) {
			if (!copy($file['tmp_name'], $newfile)) {
				trigger_error('Couldn\'t move the uploaded file.', E_USER_WARNING);
				return false;
			}
		}
		chmod($newfile, 0666);
		return true;
	}

	/**
	 * @deprecated Use \Poodle\File::putContents($filename, $content)
	 */
	public static function write($filename, &$content, $mode='wb')
	{
		trigger_deprecated('Use \Poodle\File::putContents($filename, $content)');
		return \Poodle\File::putContents($filename, $content);
	}

	public static function secure_download(&$error, $filename, $realname='')
	{
		\Dragonfly::ob_clean();
		$realname = basename($realname ?: $filename);
		if (strpos($filename,'://')) {
			// send remote file
			$rdf = parse_url($filename);
			if (!isset($rdf['host'])) return false;
			if (!isset($rdf['port'])) $rdf['port'] = 80;
			if (!isset($rdf['query'])) $rdf['query'] = '';
			$fp = fsockopen($rdf['host'], $rdf['port'], $errno, $errstr, 5);
			if ($fp === false) {
				$error = "{$errno}: {$errstr}";
				trigger_error($error, E_USER_WARNING);
				return false;
			}
			fputs($fp, 'GET ' . $rdf['path'] . $rdf['query'] . " HTTP/1.1\r\n");
			fputs($fp, 'User-Agent: Dragonfly Passthru ('.URL::index('credits', true, true).")\r\n");
			fputs($fp, 'Referer: ' . $rdf['host'] ."\r\n");
			fputs($fp, 'HOST: ' . $rdf['host'] . "\r\n\r\n");
			$data = rtrim(fgets($fp, 512));
			if (false === strpos($data, ' 200 OK')) {
				$error = $data;
				trigger_error($data, E_USER_WARNING);
				return false;
			}
			// Read all headers
			while (!empty($data)) {
				$data = rtrim(fgets($fp, 300)); // read lines
				if (preg_match('#(Content-Length|Content-Type|Last-Modified): #i', $data)) {
					header($data);
				}
			}
		} else {
			if (preg_match('#\.(\.|php$)#', $filename)) {
				$error = "{$filename} isn't allowed to be downloaded";
				trigger_error($error, E_USER_WARNING);
				return false;
			}
			if (!($fp = fopen($filename, 'rb'))) {
				$error = "{$filename} could not be opened";
				trigger_error($error, E_USER_WARNING);
				return false;
			}
			// check if Range header is sent by browser (or download manager)
			$file_size  = filesize($filename);
			$offset = 0;
			$length = $file_size - 1;
			if (isset($_SERVER['HTTP_RANGE']) && 'GET' === $_SERVER['REQUEST_METHOD']) {
				if (preg_match('#bytes=([0-9]*)-([0-9]*)#', $_SERVER['HTTP_RANGE'], $range)) {
					if (strlen($range[1])) {
						$offset = (int)$range[1];
						if (strlen($range[2])) {
							$length = min((int)$range[2], $length);
						}
					} else if (strlen($range[2])) {
						// The final N bytes
						$offset = max($file_size - $range[2], 0);
					}
					if ($length < $offset) {
						\Poodle\HTTP\Status::set(416);
						fclose($fp);
						return false;
					}
				} else {
					\Poodle\HTTP\Status::set(416);
					fclose($fp);
					return false;
				}
			}
			header('Accept-Ranges: bytes');
			header('Content-Length: '.($length - $offset + 1));
			\Poodle\HTTP\Headers::setContentType(\Poodle\File::getMime($filename), array('name'=>$realname));
			if ($offset > 0 || $length < $file_size - 1) {
				\Poodle\HTTP\Status::set(206);
				header("Content-Range: bytes {$offset}-{$length}/{$file_size}");
				// seek to start
				fseek($fp, $offset);
				// send partial data
				if ($length < $file_size - 1) {
					header('Content-Encoding:');
					\Poodle\HTTP\Headers::setContentDisposition('attachment', array('filename'=>$realname));
					$buffer = 1024 * 8;
					while (!feof($fp) && ($p = ftell($fp)) <= $length) {
						if ($p + $buffer > $length) {
							$buffer = $length - $p + 1;
						}
						set_time_limit(10);
						echo fread($fp, $buffer);
						flush();
					}
					return fclose($fp);
				}
			}
		}
		header('Content-Encoding:');
		\Poodle\HTTP\Headers::setContentDisposition('attachment', array('filename'=>$realname));
		set_time_limit(0);
		if (false === fpassthru($fp)) {
			$error = 'fpassthru failed';
			trigger_error($error, E_USER_WARNING);
			fclose($fp);
			return false;
		}
		return fclose($fp);
	}

	/**
	 * @deprecated
	 */
	public static function analyze_system()
	{
		trigger_deprecated();
		$disabled = ini_get('disable_functions');
		return array(
			'set_time_limit' => false === strpos($disabled, 'set_time_limit'),
			'fsockopen'      => false === strpos($disabled, 'fsockopen'),
			'fopen'          => false === strpos($disabled, 'fopen'),
			'url_fopen'      => !!ini_get('allow_url_fopen'),
			'upload' => array(
				'active'     => !!ini_get('file_uploads'),
				'tmp_dir'    => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
				'max'        => ini_get('upload_max_filesize'),
			),
			'open_basedir'   => ini_get('open_basedir'),
/*
			max_execution_time = 30 ; Maximum execution time of each script, in seconds
			max_input_time = 60     ; Maximum amount of time each script may spend parsing request data
			memory_limit = 8M       ; Maximum amount of memory a script may consume (8MB)
*/
		);
	}
}
