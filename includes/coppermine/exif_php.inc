<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   https://dragonfly.coders.exchange/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

function IFD_rational2float($value)
{
	if (!preg_match('#^([0-9]+)/([0-9]+)$#D', $value, $float)) { return 0.0; }
	return (float)(empty($float[2]) ? $float[1] : $float[1]/$float[2]);
}

function exif_parse_file($filename)
{
	require('includes/coppermine/picmgmt.inc');
	$exif = getExifData($filename);
	if (!$exif) {
		return false;
	}

	$exifParsed = array();

	if (isset($exif['IFD0']['Make']) && isset($exif['IFD0']['Model'])) {
		$exifParsed['Camera'] = $exif['IFD0']['Make'] . ' - ' . $exif['IFD0']['Model'];
	}

	if (isset($exif['EXIF']['DateTimeDigitized'])) {
		$exifParsed['DateTaken'] = preg_replace('#^([0-9 ]*):([0-9 ]*):([0-9 ]*)#', '\1-\2-\3', $exif['EXIF']['DateTimeDigitized']);
	}

	if (isset($exif['EXIF']['FNumber'])) {
		$exifParsed['Aperture'] = 'f/' . IFD_rational2float($exif['EXIF']['FNumber']);
	} else if (isset($exif['COMPUTED']['ApertureFNumber'])) {
		$exifParsed['Aperture'] = $exif['COMPUTED']['ApertureFNumber'];
	}

	if (isset($exif['COMPUTED']['ExposureTime'])) {
		$exifParsed['ExposureTime'] = $exif['COMPUTED']['ExposureTime'];
	} else if (isset($exif['EXIF']['ExposureTime'])) {
		$exTime = IFD_rational2float($exif['EXIF']['ExposureTime']);
		if ($exTime < 1) {
			$exifParsed['ExposureTime'] = sprintf('%3.2f ms (1/%d)', ($exTime*1000), 1 / $exTime);
		} else {
			$exifParsed['ExposureTime'] = sprintf('%3.2f s', $exTime);
		}
	}

	if (isset($exif['EXIF']['FocalLength'])) {
		$exifParsed['FocalLength'] = sprintf('%d mm', IFD_rational2float($exif['EXIF']['FocalLength']));
	}

	if (isset($exif['COMMENT'])) {
		$exifParsed['Comment'] = implode("\n", $exif['COMMENT']);
	}

	return $exifParsed;
}
