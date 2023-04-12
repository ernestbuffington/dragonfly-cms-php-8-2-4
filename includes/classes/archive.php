<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/archive.php,v $
  $Revision: 1.5 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:41 $
**********************************************/

class archive
{

	function load($filename)
	{
		$type = archive::get_type($filename);
		switch ($type) {

			case 'bzip2':
				if (function_exists('bzopen')) {
					if ($bz = bzopen($filename, 'r')) {
						$id = substr(bzread($bz, 262), -5);
						bzclose($bz);
						if ($id == 'ustar') {
							require_once(CORE_PATH.'classes/archive/tar.php');
							return new archive_tar($filename, 'bzip2');
						}
						trigger_error('Single bzipped file not supported yet, only archives', E_USER_WARNING);
					}
				} else {
					trigger_error('Bzip2 PHP module not loaded, see http://php.net/Bzip2', E_USER_WARNING);
				}
				return false;

			case 'gzip':
				if ($gz = gzopen($filename, 'r')) {
					$id = substr(gzread($gz, 262), -5);
					gzclose($gz);
					if ($id == 'ustar') {
						require_once(CORE_PATH.'classes/archive/tar.php');
						return new archive_tar($filename, 'gzip');
					}
					trigger_error('Single gzipped file not supported yet, only archives', E_USER_WARNING);
				}
				return false;

			case 'rar':
				if (function_exists('rar_open')) {
					require_once(CORE_PATH.'classes/archive/rar.php');
					return new archive_rar($filename);
				} else {
					trigger_error('Rar PHP module not loaded, see http://php.net/rar', E_USER_WARNING);
				}
				return false;

			case 'tar':
				require_once(CORE_PATH.'classes/archive/tar.php');
				return new archive_tar($filename);

			case 'zip':
				require(CORE_PATH.'classes/archive/zip.php');
				return new archive_zip($filename);

			default:
				return false;

		}
	}

	function get_type($filename)
	{
		if ($fp = fopen($filename, 'rb')) {
			$id = fread($fp, 265);
			fclose($fp);
			# Compressed Archives
			if (substr($id,0,2) == "\x37\x7a") { #7z
				return '7z'; # application/x-7z-compressed
			} else if (substr($id,0,3) == "\x42\x5a\x68") { # BZh
				return 'bzip2'; # application/x-bzip2
			} else if (substr($id,0,2) == "\x1f\x8b") {
				return 'gzip'; # application/x-gzip
			} else if (substr($id,0,4) == "\x52\x61\x72\x21") { # Rar!
				return 'rar'; # application/x-rar
			} else if (substr($id,0,4) == "\x50\x4b\x03\x04") { # PKxx
				return 'zip'; # application/zip
			}
			# Archives
			else if (substr($id,257,8) == "ustar\x20\x20\x00") {
				return 'tar'; # application/x-gtar
			} else if (substr($id,257,6) == "ustar\x00") {
				return 'tar'; # application/x-tar
			}
			# images
			else if (substr($id,0,4) == "\x89PNG") {
				return 'png'; # image/x-png
			} else if (substr($id,0,4) == "GIF8") {
				return 'gif'; # image/gif
			} else if (substr($id,0,2) == "\xff\xd8") {
				return 'jpg'; # image/jpeg
			}
		}
		return false;
	}

}
