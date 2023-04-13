<?php 
/***************************************************************************  
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify       
   it under the terms of the GNU General Public License as published by       
   the Free Software Foundation; either version 2 of the License, or          
   (at your option) any later version.                                        
  **************************************************************************  
  Last modification notes:
  $Source: /cvs/html/includes/coppermine/picmgmt.inc,v $
  $Revision: 9.14 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:18 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
require_once(CORE_PATH.'imaging/imaging.inc');

// Add a picture to an album
function add_picture($aid, $filepath, $filename, $title = '', $caption = '', $keywords = '', $user1 = '', $user2 = '', $user3 = '', $user4 = '', $category = 0, $watermark=false, $src_file=false)
{
	global $db, $CONFIG, $ERROR, $USER_DATA, $PIC_NEED_APPROVAL, $CPG_M_DIR;
	global $username, $raw_ip, $hdr_ip;

	$image = $filepath.$filename;
	$normal = $filepath.$CONFIG['normal_pfx'].$filename;
	$thumb = $filepath.$CONFIG['thumb_pfx'].$filename;
	// $image = $CPG_M_DIR."/albums/$image";
	// $normal = $CPG_M_DIR."/albums/$normal";
	// $thumb = $CPG_M_DIR."/albums/$thumb";
	if (!$src_file) $src_file = $image;
	if (!($imginfo = getimagesize($src_file))) { return false; }

	if (!file_exists($thumb) &&
	  !resize_image($src_file, $imginfo, $thumb, $CONFIG['thumb_width'], $CONFIG['thumb_method'], $CONFIG['thumb_use'])) {
		return false;
	}

	if (max($imginfo[0], $imginfo[1]) > $CONFIG['picture_width'] && $CONFIG['make_intermediate'] && !file_exists($normal) &&
	  !resize_image($src_file, $imginfo, $normal, $CONFIG['picture_width'], $CONFIG['thumb_method'], $CONFIG['thumb_use'], $watermark)) {
		unlink($thumb);
		return false;
	}

	if (!($imginfo = getimagesize($image))) {
		unlink($normal);
		unlink($thumb);
		return false;
	}
	$image_filesize = filesize($image);
	$total_filesize = $image_filesize + (file_exists($normal) ? filesize($normal) : 0) + filesize($thumb);
	// Test if disk quota exceeded
	if (!GALLERY_ADMIN_MODE && $USER_DATA['group_quota']) {
		$result = $db->sql_query("SELECT sum(total_filesize) FROM {$CONFIG['TABLE_PICTURES']}, {$CONFIG['TABLE_ALBUMS']} WHERE  {$CONFIG['TABLE_PICTURES']}.aid = {$CONFIG['TABLE_ALBUMS']}.aid AND category = '".(FIRST_USER_CAT + USER_ID)."'");
		$record = $db->sql_fetchrow($result);
		$total_space_used = $record[0];
		$db->sql_freeresult($result);

		if ($total_space_used + $total_filesize > $USER_DATA['group_quota'] << 10) {
			unlink($normal);
			unlink($thumb);
			$msg = strtr(QUOTA_EXCEEDED, array('[quota]' => ($USER_DATA['group_quota']),
                            '[space]' => ($total_space_used >> 10)));
			cpg_die(_ERROR, $msg, __FILE__, __LINE__);
		}
	}
    // Test if picture requires approval
    $approved = intval(GALLERY_ADMIN_MODE || defined('BATCH_MODE') ||
	   (!$USER_DATA['priv_upl_need_approval'] && $category == FIRST_USER_CAT + USER_ID) ||
	   (!$USER_DATA['pub_upl_need_approval']));
	$PIC_NEED_APPROVAL = (!$approved);
	// User ID is not recorded when in admin mode (ie. for batch uploads)
	$user_id = GALLERY_ADMIN_MODE ? 0 : USER_ID;
	$filepath = Fix_Quotes($filepath);
	$filename = Fix_Quotes($filename);
	$result = $db->sql_query("INSERT INTO {$CONFIG['TABLE_PICTURES']}
		(pid, aid, filepath, filename, filesize, total_filesize, pwidth, pheight, ctime, owner_id, title, caption, keywords, approved, user1, user2, user3, user4, pic_raw_ip, pic_hdr_ip)
		VALUES
		(DEFAULT, '$aid', '".$filepath."', '".$filename."', '$image_filesize', '$total_filesize', '".$imginfo[0]."', '".$imginfo[1]."', '".time()."', '".USER_ID."', '$title', '$caption', '$keywords', '$approved', '$user1', '$user2', '$user3', '$user4', '$raw_ip', $hdr_ip)", false, __FILE__, __LINE__);

	speedup_pictures();
    
	return $result;
}

/**
 * resize_image()
 *
 * Create a file containing a resized image
 *
 * @param  $src_file the source file
 * @param  $dest_file the destination file
 * @param  $new_size the size of the square within which the new image must fit
 * @param  $method the method used for image resizing
 * @return 'true' in case of success
*/
function resize_image($src_file, $imginfo, &$dest_file, $new_size, $method, $thumb_use, $watermark=false)
{
	global $CONFIG, $ERROR;

	// height/width
	if ($thumb_use == 'ht') {
		$ratio = $imginfo[1] / $new_size;
	} elseif ($thumb_use == 'wd') {
		$ratio = $imginfo[0] / $new_size;
	} else {
		$ratio = max($imginfo[0], $imginfo[1]) / $new_size;
	}
	$ratio = max($ratio, 1.0);
	$dest_info[0] = intval($imginfo[0] / $ratio);
	$dest_info[1] = intval($imginfo[1] / $ratio);
	$dest_info['quality'] = intval($CONFIG['jpeg_qual']);
	$dest_info['im_options'] = $CONFIG['im_options'];
	if ($watermark) {
		$dest_info['watermark']['text'] = $watermark;
	}

	$dest_file = (new Graphic)->resize($src_file, $dest_info, $dest_file, $imginfo, defined('ADDPIC_PHP'));
	if ($dest_file < 0) {
		$ERROR = RESIZE_FAILED;
		return false;
	}
	// Set mode of uploaded picture
	chmod($dest_file, (PHP_AS_NOBODY ? 0666 : 0644));
	return true;
}
