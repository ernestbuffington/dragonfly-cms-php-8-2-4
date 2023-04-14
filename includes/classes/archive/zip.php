<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/classes/archive/zip.php,v $
  $Revision: 1.7 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:43 $
**********************************************/

class archive_zip
{

	public $toc;
	public $filename;
	public $type = 'zip';

	function __construct($filename)
	{
		$this->filename = $filename;
		$this->load_toc();
	}

	function load_toc()
	{
		if ($fp = fopen($this->filename, 'rb')) {
			# find ToC summary (Central Dir)
			fseek($fp, -18, SEEK_END);
			while (ftell($fp) > 76) {
				$id = fread($fp, 4);
				# "PK\x05\x06"
				if ($id == "\x50\x4b\x05\x06") {
					$this->toc['cd'] = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', fread($fp, 18));
					$this->toc['cd']['comment'] = ($this->toc['cd']['comment_size'] > 0) ? htmlprepare(fread($fp, $this->toc['cd']['comment_size'])) : '';
					break;
				}
				fseek($fp, -5, SEEK_CUR);
			}
			if (empty($this->toc['cd'])) { return false; }
			# Read all ToC entries
			$dir = NULL;
			fseek($fp, $this->toc['cd']['offset']);
			for ($i=0; $i<$this->toc['cd']['entries']; ++$i) {
				$entry = $this->_ReadFileHeader($fp);
				if (substr($entry['filename'],-1) != '/') {
					$this->toc['files'][$i] = $entry;
					$dir['entries'][$i] =& $this->toc['files'][$i];
				} else {
					$this->toc['dirs'][$i] = $entry;
					$dir =& $this->toc['dirs'][$i];
				}
			}
			fclose($fp);
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
		if ($fp = fopen($this->filename, 'rb')) {
			fseek($fp, $this->toc['files'][$id]['offset']);
			$file = $this->_ReadFileHeader($fp);
			if (!isset($file['external']) || ($file['external'] != 0x41FF0010 && $file['external']!=16)) {
				$size = $file['compressed_size'];
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
					$tmp = fread($fp, $size);
					if ($file['compression'] == 8) { $tmp = gzinflate($tmp); }
	//				if ($file['compression'] == 8) { $tmp = gzinflate(pack('a'.$size, $tmp)); }
					if ($to) {
						fwrite($wfp, $tmp);
					} else {
						$file['data'] .= $tmp;
					}
					unset($tmp);
				}
				if ($to) { fclose($wfp); }
				fclose($fp);
				return $file;
			}
			fclose($fp);
		}
		return false;
	}

	function _ReadFileHeader(&$zip)
	{
		$id = fread($zip, 4);
		if ($id == "\x50\x4b\x01\x02") {
			# Table of Contents entry (ID: "\x50\x4b\x01\x02")
			$header = unpack('vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', fread($zip, 42));
			$toc = true;
		} else if ($id == "\x50\x4b\x03\x04") {
			# File entry header
			$header = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', fread($zip, 26));
			$toc = false;
		} else {
			trigger_error('Incorrect file header found at offset '.(ftell($zip)-4).': '.rawurlencode($id), E_USER_ERROR);
		}
		$header['filename'] = ($header['filename_len'] != 0) ? fread($zip, $header['filename_len']) : '';
		$header['extra'] = ($header['extra_len'] != 0) ? fread($zip, $header['extra_len']) : '';
		if ($toc) {
			$header['comment'] = $header['comment_len'] ? fread($zip, $header['comment_len']) : '';
			unset($header['comment_len']);
		}
		if ($header['mdate'] && $header['mtime']){
			$hour  = ($header['mtime']&0xF800)>>11;
			$minute= ($header['mtime']&0x07E0)>>5;
			$second= ($header['mtime']&0x001F)*2;
			$year  = (($header['mdate']&0xFE00)>>9)+1980;
			$month = ($header['mdate']&0x01E0)>>5;
			$day   = $header['mdate']&0x001F;
			$header['mtime'] = mktime($hour, $minute, $second, $month, $day, $year);
		} else {
			$header['mtime'] = gmtime();
		}
		unset($header['mdate'], $header['filename_len'], $header['extra_len']);
		return $header;
	}

}
