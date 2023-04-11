<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Input;

class File
{

	protected
		$file = array(
			'name' => null,
			'type' => null,
			'tmp_name' => null,
			'size' => 0
		),
		$org_name  = null,
		$tmpfile   = null,
		$errno     = false,
		$error_val = null,
		$moved     = false;

	const
/*
		UPLOAD_ERR_OK         = 0, // There is no error, the file uploaded with success.
		UPLOAD_ERR_INI_SIZE   = 1, // The uploaded file exceeds the upload_max_filesize directive in php.ini.
		UPLOAD_ERR_FORM_SIZE  = 2, // The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
		UPLOAD_ERR_PARTIAL    = 3, // The uploaded file was only partially uploaded.
		UPLOAD_ERR_NO_FILE    = 4, // No file was uploaded.
		UPLOAD_ERR_NO_TMP_DIR = 6, // Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.
		UPLOAD_ERR_CANT_WRITE = 7, // Failed to write file to disk. Introduced in PHP 5.1.0.
		UPLOAD_ERR_EXTENSION  = 8, // A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.
*/
		ERR_NO_UPLOAD_FILE = 101, # not an uploaded file
		ERR_FILE_EMPTY     = 102,
		ERR_TMP_UNREADABLE = 103, # not readable and failed to move file to tmp
		ERR_FILE_INVALID   = 104, # didn't match any allowed mime type
		ERR_ACCESS_FAILED  = 105,
		ERR_MOVE_FAILED    = 106,
		ERR_ALREADY_MOVED  = 107;

	function __construct(array $file)
	{
		$this->errno = $file['error'];
		$file['error'] = &$this->errno;
		if (UPLOAD_ERR_OK !== $this->errno) {
			# UPLOAD_ERR_*: INI_SIZE, FORM_SIZE, PARTIAL, NO_FILE, NO_TMP_DIR, CANT_WRITE, EXTENSION:
			if (UPLOAD_ERR_INI_SIZE == $this->errno) {
				$this->error_val = \Poodle\PHP\INI::get('upload_max_filesize');
			}
			return;
		}

		if (!$file['size']) {
			$this->errno = self::ERR_FILE_EMPTY;
			return;
		}

//		if ($file['tmp_name'] === 'none')
		if (!is_uploaded_file($file['tmp_name'])) {
			$this->errno = self::ERR_NO_UPLOAD_FILE;
			return;
		}

		if (!is_readable($file['tmp_name'])) {
			$tmpfile = CACHE_PATH.'tmp/upload-'.md5(microtime());
			# safe_mode workaround
			if (!copy($file['tmp_name'], $tmpfile)) {
				$this->errno = self::ERR_TMP_UNREADABLE;
				return;
			}
			$this->tmpfile = $tmpfile;
		} else {
			$this->tmpfile = $file['tmp_name'];
		}

		$file['name'] = $this->org_name = \Poodle\File::fixName($file['name']);
		$file['name'] = preg_replace('#\\.jpg$#Di', '.jpeg', $file['name']);
		# Detect and check MIME
		$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
		if ('csv' === $ext) {
			$file['type'] = 'text/csv';
		} else {
			$file['type'] = preg_replace('#;.*#','', \Poodle\File::getMime($this->tmpfile));
			if (strpos($file['type'], '/empty')) {
				$this->errno = self::ERR_FILE_EMPTY;
				return;
			}
			# MS Failures
			if ('xls' === $ext && 'application/msword' === $file['type']) {
				$file['type'] = 'application/vnd.ms-excel';
			}
			if ('xlsx' === $ext && 'application/msword' === $file['type']) {
				$file['type'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			}
			if ('xltx' === $ext && ('application/msword' === $file['type'] || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' === $file['type'])) {
				$file['type'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';
			}
			if ('dotx' === $ext && 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' === $file['type']) {
				$file['type'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
			}
		}
		$this->file = array_merge($this->file, $file);
	}

	function __destruct()
	{
		if ($this->tmpfile && $this->tmpfile != $this->file['tmp_name']) { unlink($this->tmpfile); }
	}

	function __get($key)
	{
		switch ($key)
		{
		case 'tmp_name':  return $this->__toString();
		case 'md5':       return $this->errno ? null : md5_file($this->__toString());
		case 'sha1':      return $this->errno ? null : sha1_file($this->__toString());
		case 'basename':
		case 'name':      return basename($this->moved ?: $this->file['name']);
		case 'org_name':  return $this->org_name;
//		case 'dirname':   return dirname($this->moved ?: $this->file['name']);
		case 'extension': return pathinfo($this->moved ?: $this->file['name'], PATHINFO_EXTENSION);
		case 'filename':  return pathinfo($this->moved ?: $this->file['name'], PATHINFO_FILENAME);
		case 'size':      return $this->file['size'];
		case 'type':
		case 'mime':      return $this->file['type']; # mime_type
		case 'mime_dir':  return dirname($this->file['type']);
		case 'errno':     return $this->errno;
		case 'error':
			if (!$this->errno) { return null; }
			$L10N = \Poodle::getKernel()->L10N;
			$L10N->load('poodle_input');
			$msg = $L10N->get('Poodle\\Input\\File\\Errors', $this->errno);
			return $this->error_val ? sprintf($msg, $this->error_val) : $msg;
		}
	}

	public function validateType(array $allowed_types)
	{
		if (!$this->file['type']) { return false; }
		$allowed_types = str_replace('jpg', 'jpeg', implode('|',$allowed_types));
		if (!preg_match('#(^|[/\\-])('.$allowed_types.')(\\-|$)#', $this->file['type']))
		{
			$this->errno = self::ERR_FILE_INVALID;
			$this->error_val = $this->file['type'];
			return false;
		}
		return true;
	}

	// Old way: CPG_File::move_upload()
	public function moveTo($newfile, $ext=null, $overwrite=false)
	{
		if ($this->errno) { return false; }
		if ($this->moved) {
			$this->errno = self::ERR_ALREADY_MOVED;
			return false;
		}
		$path = dirname($newfile);
		if ((!is_dir($path) && !mkdir($path, 0777, true)) || !is_writable($path)) {
			$this->errno = self::ERR_ACCESS_FAILED;
			$this->error_val = str_replace(\Poodle::$DIR_BASE,'',$path);
			return false;
		}

		# set extension
		$ext = $ext ? ".{$ext}" : \Poodle\File::mimeToExtension($this->file['type']);

		if (is_file($newfile.$ext)) {
			if ($overwrite && !unlink($newfile.$ext)) {
				trigger_error("Failed to remove file");
				$overwrite = false;
			}
			if (!$overwrite) {
				$s_ext = pathinfo($newfile, PATHINFO_EXTENSION);
				if ('tar' === $s_ext) {
					$ext = ".tar{$ext}";
					$newfile = substr($newfile, 0, -4);
				}
				$i = glob("{$newfile}-[0-9]*{$ext}");
				if ($i) {
					natcasesort($i);
					$i = 1 + preg_replace('#'.preg_quote($newfile, '#').'\\-([0-9]+).*$#','$1',array_pop($i));
				} else {
					$i = 1;
				}
				$newfile .= '-'.$i;
			}
		}

		$newfile .= $ext;
		# move it
		if (!move_uploaded_file($this->file['tmp_name'], $newfile)) {
			if (!copy($this->file['tmp_name'], $newfile)) {
				$this->errno = self::ERR_MOVE_FAILED;
				return false;
			}
		}

		# set perms so that it is deletable thru FTP
		\Poodle::chmod($newfile);

		return $this->moved = $newfile;
	}

	function __toString()
	{
		return $this->errno ? null : $this->moved ?: $this->tmpfile;
	}
}
