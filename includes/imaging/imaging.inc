<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

class Graphic
{
	public static function resize($src_file, $dest_info, $dest_file, $imginfo=false, $force_extension=false)
	{
		trigger_deprecated();
		return self::_resize($src_file, $dest_info, $dest_file, $imginfo, $force_extension);
	}

	public static function show($src_file, $dest_info)
	{
		trigger_deprecated();
		self::_resize($src_file, $dest_info, '');
		exit;
	}

	private static function _resize($src_file, $dest_info, $dest_file, $imginfo=false, $force_extension=false)
	{
		try
		{
			$img = \Poodle\Image::open($src_file);
			$img->thumbnailImage($dest_info[0], $dest_info[1], true);

			if (!$dest_file) {
				header('Content-type: '.$img->getImageMimeType());
				header('Connection: Close');
				echo $img;
				return true;
			}

			if (isset($dest_info['watermark'])) {
				$img->add_text(array('text' => $dest_info['watermark']['text']));
			}

			if (!$img->writeImage($dest_file))
			{
				return -3;
			}
			$dest_file = $img->getImageFilename();
			chmod($dest_file,0777);
			return $dest_file;
		}
		catch (Exception $e)
		{
			return -2;
		}
	}
}

function image_file_to_extension($file, $imagetype)
{
	$file = explode('.', $file);
	array_pop($file);
	return preg_replace('/\.jpeg$/', '.jpg', implode('.', $file).image_type_to_extension($imagetype));
}
