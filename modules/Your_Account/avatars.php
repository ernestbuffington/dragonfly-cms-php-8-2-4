<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Your_Account/avatars.php,v $
  $Revision: 9.8 $
  $Author: djmaze $
  $Date: 2005/05/08 03:20:12 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
global $pagetitle;
$pagetitle .= ' '._BC_DELIM.' '._AVATAR_GALLERY;

function check_image_type($filetype)
{
	if (!preg_match('#image/[x\-]*([a-z]+)#', $filetype, $type)) {
		cpg_error(sprintf(_AVATAR_ERR_IMTYPE, $filetype));
	}
	$type = $type[1];
//GIF, JPG, PNG, SWF, PSD, BMP, IFF
//Support for JPC, JP2, JPX, JB2, XBM, and WBMP became available in PHP 4.3.2
//Support for SWC exists as of PHP 4.3.0 and TIFF support was added in PHP 4.2.0
	switch ($type) {
		case 'jpeg':
		case 'pjpeg':
		case 'jpg':
			return '.jpg';
			break;
		case 'gif': return '.gif'; break;
		case 'png': return '.png'; break;
	}
	cpg_error(sprintf(_AVATAR_ERR_IMTYPE, $filetype));
}

function avatar_delete(&$userinfo) {
	global $MAIN_CFG;
	if ($userinfo['user_avatar_type'] == 1 && file_exists($MAIN_CFG['avatar']['path'].'/'.$userinfo['user_avatar'])) {
		unlink($MAIN_CFG['avatar']['path'].'/'.$userinfo['user_avatar']);
	}
	return 'user_avatar=\'\', user_avatar_type=0';
}

function avatar_size($image, $delete=false) {
	global $MAIN_CFG;
	list($width, $height) = getimagesize($image);
	if ($height > $MAIN_CFG['avatar']['max_height'] || $width > $MAIN_CFG['avatar']['max_width']) {
		if ($delete) { unlink($image); }
		cpg_error(sprintf(_AVATAR_ERR_SIZE, $width, $height), 'ERROR: Image size');
	}
	return true;
}

function avatar_upload($remote, &$userinfo, $avatar_filename, $avatar)
{
	$new_filename = null;
 require_once(CORE_PATH.'classes/cpg_file.php');
	global $MAIN_CFG, $db, $lang;
	if ($remote) {
		if (!preg_match('/^(http:\/\/)?([\w\-\.]+)\:?([0-9]*)\/(.*)$/', $avatar_filename, $url_ary) || empty($url_ary[4])) {
			cpg_error('The URL you entered is incomplete');
		}
		$avatar = get_fileinfo($avatar_filename, !$MAIN_CFG['avatar']['animated'], true);
		if (!isset($avatar['size'])) {
			cpg_error(_AVATAR_ERR_DATA);
		} elseif ($avatar['animation'] && !$MAIN_CFG['avatar']['animated']) {
			cpg_error('Animated avatar not allowed');
		}
		$avatar_filesize = $avatar['size'];
		$avatar_filetype = $avatar['type'];
		$imgtype = check_image_type($avatar_filetype);
		if ($avatar['size'] > 0 && $avatar['size'] < $MAIN_CFG['avatar']['filesize']) {
			$new_filename = $userinfo['user_id'].'_'.uniqid(random_int(0, mt_getrandmax())).$imgtype;
			$avatar_filename = $MAIN_CFG['avatar']['path']."/$new_filename";
			if (CPG_File::write($avatar_filename, $avatar['data']) != $avatar['size']) {
				trigger_error('Could not write avatar to local storage', E_USER_ERROR);
			}
		}
	} else {
		$avatar_filesize = $avatar['size'];
		$avatar_filetype = $avatar['type'];
		$imgtype = check_image_type($avatar_filetype);
		$new_filename = $userinfo['user_id'].'_'.uniqid(random_int(0, mt_getrandmax())).$imgtype;
		$avatar_filename = $MAIN_CFG['avatar']['path']."/$new_filename";
		if (!CPG_File::move_upload($avatar, $avatar_filename)) {
			trigger_error('Could not copy avatar to local storage', E_USER_ERROR);
		}
		if (!$MAIN_CFG['avatar']['animated'] && $fp = fopen($avatar_filename, 'rb')) {
			$data = fread($fp, $avatar_filesize);
			fclose($fp);
			$data = preg_split('/\x00[\x00-\xFF]\x00\x2C/', $data); // split GIF frames
			if ((is_countable($data) ? count($data) : 0) > 2) {
				unlink($avatar_filename);
				cpg_error('Animated avatar not allowed');
			}
			unset($data);
		}
	}
	if ($avatar_filesize < 40 || $avatar_filesize > $MAIN_CFG['avatar']['filesize']) {
		unlink($avatar_filename);
		cpg_error(sprintf(_AVATAR_FILESIZE, round($MAIN_CFG['avatar']['filesize'] / 1024)));
	}
	avatar_size($avatar_filename, true);
	avatar_delete($userinfo);
	return "user_avatar='$new_filename', user_avatar_type=1";
}

function display_avatar_gallery(&$userinfo)
{
	$avatar_name = [];
 $buttons = null;
 global $MAIN_CFG;
	$category = (!empty($_POST['avatarcategory'])) ? $_POST['avatarcategory'] : '';
	$avatar_path = $MAIN_CFG['avatar']['gallery_path'];
	$dir = opendir($avatar_path);
	$avatar_images = array();
	while ($file = readdir($dir)) {
		if ($file != '.' && $file != '..' && !is_file("$avatar_path/$file") && !is_link("$avatar_path/$file")) {
			$sub_dir = opendir("$avatar_path/$file");
			while ($sub_file = readdir($sub_dir)) {
				if (preg_match('/(\.gif$|\.png$|\.jpg|\.jpeg)$/is', $sub_file)) {
					$avatar_images[$file][] = $file . '/' . $sub_file;
					$avatar_name[$file][] = ucfirst(str_replace("_", " ", preg_replace('/^(.*)\..*$/', '\1', $sub_file)));
				}
			}
		}
	}
	closedir($dir);
	ksort($avatar_images);
	if (empty($category)) { $category = array_key_first($avatar_images); }
	reset($avatar_images);
	$s_categories = '<select name="avatarcategory">';
	foreach (array_keys($avatar_images) as $key) {
     $selected = ($key == $category) ? ' selected="selected"' : '';
     if (count($avatar_images[$key])) {
   			$s_categories .= '<option value="'.$key.'"'.$selected.'>'.ucfirst($key).'</option>';
   		}
 }
	$s_categories .= '</select>';
	$s_colspan = count($avatar_images[$category]);
	$s_colspan = ($s_colspan < 5) ? $s_colspan : 5;

	if (!defined('ADMIN_PAGES')) {
		define('MEMBER_BLOCK', true);
		require_once('header.php');
		$action = getlink('&amp;edit=avatar');
	} else {
		echo "<strong>$userinfo[username]</strong>";
		if ($userinfo['user_level'] == 0) { echo ' ('._ACCTSUSPEND.')'; }
		elseif ($userinfo['user_level'] < 0) { echo ' ('._ACCTDELETE.')'; }
		echo '<br />
		<a href="'.adminlink('users&amp;mode=edit&amp;edit=profile&amp;id='.$userinfo['user_id']).'">'._MA_PROFILE_INFO.'</a> |
		<a href="'.adminlink('users&amp;mode=edit&amp;edit=reg_details&amp;id='.$userinfo['user_id']).'">'._MA_REGISTRATION_INFO.'</a> |
		<a href="'.adminlink('users&amp;mode=edit&amp;edit=avatar&amp;id='.$userinfo['user_id']).'">'._AVATAR_CONTROL.'</a>';
		if ($userinfo['user_level'] > 0) {
			echo ' | <a href="'.adminlink("users&amp;mode=edit&amp;edit=admin&amp;id=$userinfo[user_id]").'">'._MA_PRIVILEGES.'</a>';
		}
		echo '<br /><br />';
		$action = adminlink('users&amp;mode=edit&amp;edit=avatar&amp;id='.$userinfo['user_id']);
	}
	echo '<form action="'.$action.'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">
	<tr>
	  <td class="catBottom" align="center" valign="middle" colspan="6" height="28"><span class="genmed">'._CATEGORY_SELECT.':&nbsp;'.$s_categories.'&nbsp;<input type="submit" class="liteoption" value="'._GO.'" name="avatargallery" /></span></td>
	</tr>';
	for ($i = 0; $i < count($avatar_images[$category]); ++$i) {
		if ($i%5 == 0) {
			$buttons = '';
			echo '<tr>';
		}
		echo '	<td class="row1" align="center"><img src="'.$avatar_path.'/'.$avatar_images[$category][$i].'" alt="'.$avatar_name[$category][$i].'" title="'.$avatar_name[$category][$i].'" /></td>'."\n";
		$buttons .= '  <td class="row2" align="center"><input type="radio" name="avatarselect" value="'.$avatar_images[$category][$i].'" /></td>'."\n";
		if ($i%5 == 4) {
			echo '</tr><tr>'.$buttons.'</tr>';
		}
	}
	if ($i%5 != 0) {
		echo '</tr><tr>'.$buttons.'</tr>';
	}
	echo '	  <tr>
	  <td class="catbottom" colspan="'.$s_colspan.'" align="center" height="28">
		<input type="submit" name="submitavatar" value="'._SELECT_AVATAR.'" class="mainoption" />&nbsp;&nbsp;<input type="submit" name="cancelavatar" value="'._CANCEL_AVATAR.'" class="liteoption" />
	  </td>
	</tr>
  </table>
</form>';
}
