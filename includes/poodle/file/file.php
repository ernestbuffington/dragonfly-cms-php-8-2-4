<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

abstract class File
{

	# Replace ampersand, spaces and reserved characters (based on Win95 VFAT)
	# en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
	public static function fixName($filename)
	{
		return preg_replace('#[|\\\\?*<":>+\\[\\]/&\\s\\pC]#su', '-', $filename);
	}

	public static function open($filename, $skip_default=true)
	{
		switch (self::getType($filename, false))
		{
		case 'bzip2':
			if (function_exists('bzopen')) {
				if ($bz = bzopen($filename, 'rb')) {
					$id = substr(bzread($bz, 262), -5);
					bzclose($bz);
					if ('ustar' === $id) {
						return new \Poodle\File\Archive\TAR($filename, 'bzip2');
					}
					return new \Poodle\File\Stream\Bzip2($filename);
				}
			} else {
				\Poodle\Debugger::trigger('Bzip2 PHP module not loaded, see http://php.net/Bzip2', __DIR__);
			}
			return false;

		case 'gzip':
			if ($gz = gzopen($filename, 'rb')) {
				$id = substr(gzread($gz, 262), -5);
				gzclose($gz);
				if ('ustar' === $id) {
					return new \Poodle\File\Archive\TAR($filename, 'gzip');
				}
				return new \Poodle\File\Stream\Gzip($filename);
			}
			return false;

		case 'rar':
			if (function_exists('rar_open')) {
				return new \Poodle\File\Archive\RAR($filename);
			}
			\Poodle\Debugger::trigger('Rar PHP module not loaded, see http://php.net/rar', __DIR__);
			return false;

		case 'gtar':
		case 'tar':
			return new \Poodle\File\Archive\TAR($filename);

		case 'zip':
			return new \Poodle\File\Archive\ZIP($filename);

		default:
			if ($skip_default) { return false; }
			return new \Poodle\File\Stream\Raw($filename);
		}
	}

	public static function getType($filename, $extended=true)
	{
		preg_match('#^[a-z]+/([a-z0-9]+)#', self::getMime($filename, $extended), $match);
		return $match[1];
	}

	protected static
		$finfo = null;

	public static function getMime($str, $extended=true)
	{
		if (null === self::$finfo) {
//			if (!getenv('MAGIC')) { putenv('MAGIC='.__DIR__.'/magic.mime'.(WINDOWS_OS?'.win32':'')); } # /usr/share/misc/magic
			self::$finfo = class_exists('finfo',false) ? new \finfo(FILEINFO_MIME) : false; // FILEINFO_CONTINUE
		}

		$file = null;
		$mime = false;

		if (is_file($str)) {
			$file = $str;
			if (self::$finfo) {
				$mime = preg_replace('#[,;].*#','',self::$finfo->file($str));
			} else if ($fp = fopen($str, 'rb')) {
				$mime = self::getMimeFromData(fread($fp, 265));
				fclose($fp);
			}
			if ('application/ogg' === $mime && '.ogv' === substr($str,-4)) {
				$mime = 'video/ogg';
			} else
			if ('application/vnd.ms-office' === $mime && '.xls' === substr($str,-4)) {
				$mime = 'application/vnd.ms-excel';
			} else
			if ('application/octet-stream' === $mime && '.ods' === substr($str,-4)) {
				$mime = 'application/vnd.oasis.opendocument.spreadsheet';
			} else
			if ($extended && (!$mime || 'application/octet-stream' === $mime) && $fp = fopen($str, 'rb')) {
				$str = fread($fp, 265);
				fclose($fp);
			}
		} else {
			$mime = self::$finfo
				? preg_replace('#[,;].*#','',self::$finfo->buffer($str))
				: self::getMimeFromData($str);
		}

		if ($extended && (!$mime || 'application/octet-stream' === $mime)) {
			# check for mime unknown by magic.mime or MSWord
			$mime = self::getMimeFromData($str);
		}

		$mime = str_replace('/x-', '/', $mime);
		if ('application/zip' === $mime && $file) {
			$zip = new \Poodle\File\Archive\ZIP($file);
			foreach ($zip->toc['files'] as $file) {
				if ('word/_rels/document.xml.rels' === $file['filename']) {
					$mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
				}
				if ('xl/_rels/workbook.xml.rels' === $file['filename']) {
					$mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
				}
			}
		}

		return $mime;
	}

	protected static function getMimeFromData($str)
	{
		static $magic;
		if (!$magic) { include(__DIR__.'/magic.mime.php'); }
		$str = preg_replace(array_keys($magic), array_values($magic), $str, 1, $c);
		return $c ? $str : false;
	}

	public static function mimeToExtension($mime, $include_dot=true)
	{
		return ($include_dot ? '.' : '').self::extension($mime);
	}
	protected static function extension($mime)
	{
/*
		global $SQL;
		$mime = preg_replace('#;.*#','', str_replace('/x-', '/', $mime));
		if (is_object($SQL))
		{
			$ext = $SQL->uFetchRow("SELECT mmt_extensions FROM {$SQL->TBL->media_types}
			WHERE mmt_value IN ({$SQL->quote($mime)}, {$SQL->quote(str_replace('/', '/x-', $mime))})");
			if ($ext) {
				$ext = explode(',', $ext[0]);
				$ext = trim($ext[0]);
			}
			return $ext ? $ext : 'bin';
		}
*/
		# w3schools.com/media/media_mimeref.asp
		switch (str_replace('/x-', '/', $mime))
		{
		case 'application/bzip2' : return 'bz2';
		case 'application/gzip'  : return 'gz';
		case 'application/gtar'  : return 'tar';
		case 'application/msword': return 'doc';
		case 'application/shockwave-flash': return 'swf';
		case 'application/vnd.ms-excel': return 'xls';
		case 'application/vnd.oasis.opendocument.text': return 'odt';
		case 'application/vnd.oasis.opendocument.spreadsheet': return 'ods';
		case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': return 'xlsx';
		case 'application/vnd.openxmlformats-officedocument.spreadsheetml.template': return 'xltx';
		case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': return 'docx';
		case 'application/vnd.openxmlformats-officedocument.wordprocessingml.template': return 'dotx';
		case 'video/quicktime': return 'mov';
		case 'video/msvideo': return 'avi';
		case 'video/ogg': return 'ogv';
		case 'audio/mpeg': return 'mp3';
		case 'audio/ogg': return 'ogg';

		case 'application/7z-compressed':
		case 'application/tar':
		case 'application/rar-compressed':
		case 'application/zip':
		case 'application/pdf':
		case 'application/ogg':
		case 'image/gif':
		case 'image/jpeg':
		case 'image/svg+xml':
		case 'image/png':
		case 'text/csv':
		case 'text/sql':
		case 'text/vcard':
		case 'video/flv':
			return preg_replace('#^[a-z]+/([a-z0-9]+).*$#D', '$1', $mime);
		}
		return 'bin';
	}

	public static function putContents($filename, $content)
	{
		if (!mkdir(dirname($filename), 0777, true)) {
			return false;
		}
		$bytes_written = file_put_contents($filename, $content, LOCK_EX);
		if (false === $bytes_written) {
			trigger_error("Cannot write to file {$filename}", E_USER_WARNING);
			unlink($filename);
			return false;
		}
		chmod($filename, 0666);
		return $bytes_written;
	}

}
