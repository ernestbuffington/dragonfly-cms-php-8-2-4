<?php 
/***************************************************************************  
   Coppermine Photo Gallery 1.3.1 for CPG-Nuke								
  **************************************************************************  
   Port Copyright © 2004 Coppermine/CPG-Nuke Dev Team						
   http://cpgnuke.com/											   
  **************************************************************************  
   Copyright (C) 2002,2003  Grégory DEMAR <gdemar@wanadoo.fr>				 
   http://coppermine.sf.net/team/										
   This program is free software; you can redistribute it and/or modify	   
   it under the terms of the GNU General Public License as published by	   
   the Free Software Foundation; either version 2 of the License, or		  
   (at your option) any later version.										
  **************************************************************************  
  Last modification notes:
  $Source: /cvs/html/modules/coppermine/db_input.php,v $
  $Revision: 9.25 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:31 $
****************************************************************************/
if (!defined('CPG_NUKE')) { exit; }

define('DB_INPUT_PHP', true);
require("modules/$module_name/include/load.inc");
global $MAIN_CFG,$CLASS;
require('includes/coppermine/picmgmt.inc');
$IMG_TYPES = array(
	1 => 'GIF',
	2 => 'JPG',
	3 => 'PNG',
	4 => 'SWF',
	5 => 'PSD',
	6 => 'BMP',
	7 => 'TIFF',
	8 => 'TIFF',
	9 => 'JPC',
	10 => 'JP2',
	11 => 'JPX',
	12 => 'JB2',
	13 => 'SWC',
	14 => 'IFF'
);
function check_comment($str)
{
	global $CONFIG, $lang_bad_words, $queries;

	$ercp = array('/\S{' . ($CONFIG['max_com_wlength'] + 1) . ',}/i');
	if ($CONFIG['filter_bad_words']) foreach($lang_bad_words as $word) {
		$ercp[] = '/' . ($word[0] == '*' ? '': '\b') . str_replace('*', '', $word) . ($word[(strlen($word)-1)] == '*' ? '': '\b') . '/i';
	} 

	if (strlen($str) > $CONFIG['max_com_size']) $str = substr($str, 0, ($CONFIG['max_com_size'] -3)) . '...';
	$str = preg_replace($ercp, '(...)', $str);
	return $str;
} 

if (!isset($_GET['event']) && !isset($_POST['event'])) {
	cpg_die(_CRITICAL_ERROR, PARAM_MISSING, __FILE__, __LINE__);
} 

$event = $_POST['event'] ?? $_GET['event'];
//$event = isset($_POST['event']) ? $_POST['event'] : NULL;
switch ($event) {
	
	// Comment update
	
	case 'comment_update':
		if (!(USER_CAN_POST_COMMENTS)) cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
		// variable sanitation 8/27/2004 11:51PM
		$msg_body = (isset($_POST['msg_body']) && !empty($_POST['msg_body'])) ? Fix_Quotes($_POST['msg_body'], true): cpg_die(_ERROR, ERR_COMMENT_EMPTY, __FILE__, __LINE__);
		$msg_body = check_comment($msg_body);
		$msg_author = (isset($_POST['msg_author']) && !empty($_POST['msg_author'])) ? Fix_Quotes($_POST['msg_author'], true): cpg_die(_ERROR, EMPTY_NAME_OR_COM, __FILE__, __LINE__);
		check_words($msg_author);
		$msg_id = (isset($_POST['msg_id']) && is_numeric($_POST['msg_id'])) ? $_POST['msg_id'] : cpg_die(_CRITICAL_ERROR, PARAM_MISSING, __FILE__, __LINE__);

		if (USER_IS_ADMIN) {
			$update = "UPDATE {$CONFIG['TABLE_COMMENTS']} SET msg_body='$msg_body', msg_author='$msg_author' WHERE msg_id='$msg_id'";
		} elseif (USER_ID) {
			$update = "UPDATE {$CONFIG['TABLE_COMMENTS']} SET msg_body='$msg_body', msg_author='$msg_author' WHERE msg_id='$msg_id' AND author_id ='" . USER_ID . "'";
		} else {
			$update = "UPDATE {$CONFIG['TABLE_COMMENTS']} SET msg_body='$msg_body', msg_author='$msg_author' WHERE msg_id='$msg_id' AND author_md5_id ='{$USER['ID']}' AND author_id = '0'";
		}
		if (!USER_IS_ADMIN) {
			$redirect = getlink("$module_name&file=displayimage&pid=" . $GET['pid']);
			$host     = $MAIN_CFG['server']['domain'].$MAIN_CFG['server']['path'];
			if ($CONFIG['comment_email_notification']&&!GALLERY_ADMIN_MODE) {
				$mail_body = 'Comment Updated '.$msg_body . "\n\r ".COM_ADDED." @ http://" . $host  .''. $redirect;
				require_once("includes/classes/phpmailer.php");
				$CLASS['mail']->ClearAll();
				
				//$mail = new PHPMailer();
				$CLASS['mail'] = new PHPMailer(true);
				
				$CLASS['mail']->SetLanguage();
				$CLASS['mail']->From     = $CONFIG['gallery_admin_email'];
				$CLASS['mail']->FromName = $MAIN_CFG['global']['sitename'];
				$CLASS['mail']->AddAddress($CONFIG['gallery_admin_email'],$MAIN_CFG['global']['sitename']);
				$CLASS['mail']->Priority = 3;
				$CLASS['mail']->Encoding = "8bit";
				$CLASS['mail']->CharSet = _CHARSET;
				$CLASS['mail']->Subject = REVIEW_TITLE;
				$CLASS['mail']->Body    = $mail_body;
				if($MAIN_CFG['email']['smtp_on']){
					$CLASS['mail']->IsSMTP();   // set mailer to use SMTP
					$CLASS['mail']->Host = $MAIN_CFG['email']['smtphost'];
					if ($MAIN_CFG['email']['smtp_auth']){
						$CLASS['mail']->SMTPAuth = true;	 // turn on SMTP authentication
						$CLASS['mail']->Username = $MAIN_CFG['email']['smtp_uname'];  // SMTP username
						$CLASS['mail']->Password = $MAIN_CFG['email']['smtp_pass']; // SMTP password
					}
				} else {
					$CLASS['mail']->IsMail();
				}
				if (!$CLASS['mail']->Send()) {
					cpg_die(_ERROR, $mailer_mesage, __FILE__, __LINE__);
				}
				//cpg_mail($CONFIG['gallery_admin_email'], $lang_db_input_php['email_comment_subject'], $mail_body);
			}
		}
		$db->sql_query($update, true);
		$is_comment = "SELECT pid FROM {$CONFIG['TABLE_COMMENTS']} WHERE msg_id='$msg_id'";
		if (($com = $db->sql_query($is_comment, true)) && $db->sql_numrows($com)) {
			$comment_data = $db->sql_fetchrow($com);
			$redirect = getlink("&file=displayimage&pid=".$comment_data['pid']);
		} else {
			$redirect = getlink();
		}
		url_redirect($redirect, 1);
		break;
	
	// Comment
	
	case 'comment':
		if (!(USER_CAN_POST_COMMENTS)) cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);

		// variable sanitation 8/28/2004 12:06AM
		$msg_body = (isset($_POST['msg_body']) && !empty($_POST['msg_body'])) ? Fix_Quotes($_POST['msg_body'], true): cpg_die(_ERROR, ERR_COMMENT_EMPTY, __FILE__, __LINE__);
		$msg_body = check_comment($msg_body);
		$msg_author = (isset($_POST['msg_author']) && !empty($_POST['msg_author'])) ? Fix_Quotes($_POST['msg_author'], true): cpg_die(_ERROR, EMPTY_NAME_OR_COM, __FILE__, __LINE__);
		check_words($msg_author);
		$pid = (isset($_POST['pid']) && is_numeric($_POST['pid'])) ? $_POST['pid'] : cpg_die(_CRITICAL_ERROR, PARAM_MISSING, __FILE__, __LINE__);
		$is_comment ="SELECT comments FROM {$CONFIG['TABLE_PICTURES']}, {$CONFIG['TABLE_ALBUMS']} WHERE {$CONFIG['TABLE_PICTURES']}.aid = {$CONFIG['TABLE_ALBUMS']}.aid AND pid='$pid'";
		if (($result = $db->sql_query($is_comment, true)) && $db->sql_numrows($result)) { 
			$album_data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		} else {
			cpg_die(_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
		}	

		if (!$album_data['comments']) cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);

		if (!$CONFIG['disable_flood_protection']) {
			$result = $db->sql_query("SELECT author_md5_id, author_id FROM {$CONFIG['TABLE_COMMENTS']} WHERE pid = '$pid' ORDER BY msg_id DESC LIMIT 0,1");
			if ($db->sql_numrows($result)) {
				$last_com_data = $db->sql_fetchrow($result);
				if ((USER_ID && $last_com_data['author_id'] == USER_ID) || (!USER_ID && $last_com_data['author_md5_id'] == $USER['ID'])) {
					cpg_die(_ERROR, NO_FLOOD, __FILE__, __LINE__);
				}
			} 
		} 

		if (!USER_ID) { // Anonymous users, we need to use META refresh to save the cookie
			$insert = $db->sql_query("INSERT INTO {$CONFIG['TABLE_COMMENTS']} (pid, msg_author, msg_body, msg_date, author_md5_id, author_id, msg_raw_ip, msg_hdr_ip) VALUES ('$pid', '$msg_author', '$msg_body', ".gmtime().", '{$USER['ID']}', '0', '$raw_ip', $hdr_ip)");
			$USER['name'] = $_POST['msg_author'];
			$redirect = getlink("$module_name&file=displayimage&pid=" . $pid);
			$host =  $MAIN_CFG['server']['domain'].$MAIN_CFG['server']['path'];
			if ($CONFIG['comment_email_notification']) {
				$mail_body = $msg_body . "\n\r ".COM_ADDED." @ http://" . $host  .''. $redirect;
				require_once('includes/classes/phpmailer.php');
				$CLASS['mail']->ClearAll();
				
				///$mail = new PHPMailer(true);
				$CLASS['mail'] = new PHPMailer(true);
				
				$CLASS['mail']->SetLanguage();
				$CLASS['mail']->FromName = $MAIN_CFG['global']['sitename'];
				$CLASS['mail']->From	 = $CONFIG['gallery_admin_email'];
				$CLASS['mail']->AddAddress($CONFIG['gallery_admin_email']);
				$CLASS['mail']->Priority = 3;
				$CLASS['mail']->Encoding = '8bit';
				$CLASS['mail']->CharSet = _CHARSET;
				$CLASS['mail']->Subject = REVIEW_TITLE;
				$CLASS['mail']->Body	= $mail_body;
				if ($MAIN_CFG['email']['smtp_on']) {
					$CLASS['mail']->IsSMTP();   // set mailer to use SMTP
					$CLASS['mail']->Host = $MAIN_CFG['email']['smtphost'];
					if ($MAIN_CFG['email']['smtp_auth']){
						$CLASS['mail']->SMTPAuth = true;	 // turn on SMTP authentication
						$CLASS['mail']->Username = $MAIN_CFG['email']['smtp_uname'];  // SMTP username
						$CLASS['mail']->Password = $MAIN_CFG['email']['smtp_pass']; // SMTP password
					}
				} else {
					$CLASS['mail']->IsMail();
				}
				if (!$CLASS['mail']->Send()) {
					cpg_die(_ERROR, $mailer_mesage, __FILE__, __LINE__);
				}
				//cpg_mail($CONFIG['gallery_admin_email'], $lang_db_input_php['email_comment_subject'], $mail_body);
			} 
			$redirect = getlink("&file=displayimage&pid=" . $pid);
			pageheader(COM_ADDED, $redirect);
			msg_box(INFO, COM_ADDED, CONTINU, $redirect);
			pagefooter();
		} else { // Registered users, we can use Location to redirect
			$insert = $db->sql_query("INSERT INTO {$CONFIG['TABLE_COMMENTS']} (pid, msg_author, msg_body, msg_date, author_md5_id, author_id, msg_raw_ip, msg_hdr_ip) VALUES ('$pid', '" . CPG_USERNAME . "', '$msg_body', ".gmtime().", '', '" . USER_ID . "', '$raw_ip', $hdr_ip)");
			$redirect = getlink("$module_name&file=displayimage&pid=" . $pid);
			$host =  $MAIN_CFG['server']['domain'].$MAIN_CFG['server']['path'];
			if ($CONFIG['comment_email_notification']) {
				$mail_body = $msg_body . "\n\r ".COM_ADDED." @ http://" . $host  .''. $redirect;
				require_once("includes/classes/phpmailer.php");
				$CLASS['mail']->ClearAll();
				
				//$mail = new PHPMailer();
				$CLASS['mail'] = new PHPMailer(true);
				
				$CLASS['mail']->SetLanguage();
				$CLASS['mail']->FromName = $MAIN_CFG['global']['sitename'];
				$CLASS['mail']->From	 = $CONFIG['gallery_admin_email'];
				$CLASS['mail']->AddAddress($CONFIG['gallery_admin_email']);
				$CLASS['mail']->Priority = 3;
				$CLASS['mail']->Encoding = "8bit";
				$CLASS['mail']->CharSet = CHARSET;
				$CLASS['mail']->Subject = REVIEW_TITLE;
				$CLASS['mail']->Body	= $mail_body;
				if($MAIN_CFG['email']['smtp_on']){
					$CLASS['mail']->IsSMTP();   // set mailer to use SMTP
					$CLASS['mail']->Host = $MAIN_CFG['email']['smtphost'];
					if ($MAIN_CFG['email']['smtp_auth']){
						$CLASS['mail']->SMTPAuth = true;	 // turn on SMTP authentication
						$CLASS['mail']->Username = $MAIN_CFG['email']['smtp_uname'];  // SMTP username
						$CLASS['mail']->Password = $MAIN_CFG['email']['smtp_pass']; // SMTP password
					}
				} else {
					$CLASS['mail']->IsMail();
				}
				if (!$CLASS['mail']->Send()) {
					cpg_die(_ERROR, $mailer_message, __FILE__, __LINE__);
				}
			}
			pageheader(COM_ADDED, $redirect);
			msg_box(INFO, COM_ADDED, CONTINU, $redirect);
			pagefooter();
		}
		break;
	
	// Update album
	
	case 'album_update':
		if (!(USER_CAN_CREATE_ALBUMS || GALLERY_ADMIN_MODE)) cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
		// variable sanitation 8/28/2004 12:11AM
		$title = (isset($_POST['title']) && !empty($_POST['title'])) ? Fix_Quotes($_POST['title'], true) : cpg_die(_ERROR, ALB_NEED_TITLE, __FILE__, __LINE__);
		check_words($title);
		$description = (isset($_POST['description']) && !empty($_POST['description'])) ? Fix_Quotes(html2bb($_POST['description'])): '';
		check_words($description);
		$aid = (isset($_POST['aid']) && is_numeric($_POST['aid'])) ? $_POST['aid'] : cpg_die(_CRITICAL_ERROR, '$aid '.PARAM_MISSING, __FILE__, __LINE__);
		$category = (isset($_POST['category']) && is_numeric($_POST['category'])) ? $_POST['category'] : cpg_die(_CRITICAL_ERROR, '$category'.PARAM_MISSING, __FILE__, __LINE__);
		$visibility = (isset($_POST['visibility']) && is_numeric($_POST['visibility'])) ? $_POST['visibility'] : cpg_die(_CRITICAL_ERROR, '$visibility '.PARAM_MISSING, __FILE__, __LINE__);
		$thumb = (isset($_POST['thumb']) && is_numeric($_POST['thumb'])) ? $_POST['thumb'] : cpg_die(_CRITICAL_ERROR, '$thumb '.PARAM_MISSING, __FILE__, __LINE__);
		$uploads = intval($_POST['uploads']);
		$comments = intval($_POST['comments']);
		$votes = intval($_POST['votes']);

		if (GALLERY_ADMIN_MODE) {
			$query = "UPDATE {$CONFIG['TABLE_ALBUMS']} SET title='$title', description='$description', category='$category', thumb='$thumb', uploads='$uploads', comments='$comments', votes='$votes', visibility='$visibility' WHERE aid='$aid'";
		} else {
			$category = FIRST_USER_CAT + USER_ID;
			if ($visibility != $category && $visibility != $USER_DATA['group_id']) $visibility = 0; //not in 1.2.0
			$query = "UPDATE {$CONFIG['TABLE_ALBUMS']} SET title='$title', description='$description', thumb='$thumb',  comments='$comments', votes='$votes', visibility='$visibility' WHERE aid='$aid' AND category='$category'";
		} 

		$update = $db->sql_query($query);
		$redirect = getlink("&cat=$category");
		url_redirect($redirect, 1);
		break;
	
	// Picture upload
	
	case 'picture':
		if (!USER_CAN_UPLOAD_PICTURES) cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
		// Test if the uploaded picture is valid
		if (empty($_FILES['userpicture']['tmp_name']) || $_FILES['userpicture']['tmp_name'] == 'none'){
			if (!empty($_FILES['userpicture']['error'])) {
				switch ($_FILES['userpicture']['error']) {
					case '1':
						trigger_error('<br />The picture you have attempted to upload is larger than '.ini_get(upload_max_filesize).' which is allowed by php',E_USER_ERROR);
					case '2':
						trigger_error('<br />'.sprintf(ERR_IMGSIZE_TOO_LARGE,$_POST['MAX_FILE_SIZE']),E_USER_ERROR);
					case '3':
						trigger_error('<br />The uploaded picture was only partially uploaded.',E_USER_ERROR);
					default :
						trigger_error('<br />'.NO_PIC_UPLOADED,E_USER_ERROR);
				}
			} else {
				trigger_error(NO_PIC_UPLOADED,E_USER_ERROR);
			}
		}
		// Test if the uploaded picture size is valid
		if ($_FILES['userpicture']['size'] > ($CONFIG['max_upl_size'] << 10)) {
			trigger_error('<br />'.sprintf(ERR_IMGSIZE_TOO_LARGE,$_POST['MAX_FILE_SIZE']), E_USER_ERROR);
		}

		$title = (isset($_POST['title']) && !empty($_POST['title'])) ? Fix_Quotes($_POST['title'], true) : '';
		check_words($title);
		$caption = (isset($_POST['caption']) && !empty($_POST['caption'])) ? Fix_Quotes(html2bb($_POST['caption'],1)) : '';
		check_words($caption);
		$keywords = (isset($_POST['keywords']) && !empty($_POST['keywords'])) ? Fix_Quotes($_POST['keywords'],1) : '';
		check_words($keywords);
		$user1 = (isset($_POST['user1']) && !empty($_POST['user1'])) ? Fix_Quotes($_POST['user1'],1) : '';
		check_words($user1);
		$user2 = (isset($_POST['user2']) && !empty($_POST['user2'])) ? Fix_Quotes($_POST['user2'],1) : '';
		check_words($user2);
		$user3 = (isset($_POST['user3']) && !empty($_POST['user3'])) ? Fix_Quotes($_POST['user3'],1) : '';
		check_words($user3);
		$user4 = (isset($_POST['user4']) && !empty($_POST['user4'])) ? Fix_Quotes($_POST['user4'],1) : '';
		check_words($user4);
		$album = (isset($_POST['album']) && is_numeric($_POST['album'])) ? $_POST['album'] : cpg_die(_CRITICAL_ERROR, PARAM_MISSING, __FILE__, __LINE__);

		// Check if the album id provided is valid
		if (!GALLERY_ADMIN_MODE) {
			$alb_cat = "SELECT category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid='$album' and (uploads = 1 OR category = '" . (USER_ID + FIRST_USER_CAT) . "')";
		} else {
			$alb_cat = "SELECT category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid='$album'";
		}
		if (($result = $db->sql_query($alb_cat, true)) && $db->sql_numrows($result)){
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$category = $row['category'];
		} else {
			cpg_die(_ERROR, UNKNOWN_ALBUM, __FILE__, __LINE__);
		}

		$picname = strtolower($_FILES['userpicture']['name']);
		// Pictures are moved in a directory named 10000 + USER_ID
		if (_PROCESS_UID != getmyuid() && ini_get('safe_mode')) {
			# safe_mode workaround
			if (USER_ID) { $picname = (USER_ID + FIRST_USER_CAT).'-'.$picname; }
			$dest_dir = $CONFIG['userpics'];
		}  else {
			if (USER_ID) {
				$dest_dir = $CONFIG['userpics'].(USER_ID + FIRST_USER_CAT);
			} else {
				$dest_dir = $CONFIG['userpics'].FIRST_USER_CAT;
			}
			if (!is_dir($dest_dir)) {
				if (mkdir($dest_dir, (PHP_AS_NOBODY ? 0777 : 0755))) {
					$fp = fopen($dest_dir . '/index.html', 'w');
					if ($fp) {
						fwrite($fp, ' ');
						fclose($fp);
					}
				} else {
					trigger_error(sprintf(ERR_MKDIR, $dest_dir), E_USER_WARNING);
				}
			}
			$dest_dir .= '/';
			// Check that target dir is writable
			if (!is_writable($dest_dir)) {
				trigger_error(sprintf(DEST_DIR_RO, $dest_dir), E_USER_WARNING);
				$dest_dir = $CONFIG['userpics'];
				if (USER_ID) $picname = (USER_ID + FIRST_USER_CAT).'-'.$picname;
			}
		}
		if (!is_writable($dest_dir)) {
			cpg_die(_CRITICAL_ERROR, sprintf(DEST_DIR_RO, $dest_dir), __FILE__, __LINE__, true);
		}
		// Replace forbidden chars with underscores
		$picname = explode('.',$picname);
		$ext = array_pop($picname);
		$picnamearray = preg_split('//', implode('.', $picname), -1, PREG_SPLIT_NO_EMPTY);
		$picname='';
		foreach ($picnamearray AS $char) {
			if (!preg_match('#^[a-zA-Z0-9_\\\\\-]+$#m', $char)) {
				if (preg_match('#^([áàaaaâãä]+)$#m', $char)) {
					$char  = 'a';
				} elseif (preg_match('#^([éèæëê\?]+)$#m', $char)) {
					$char  = 'e';
				} elseif (preg_match('#^([ìíîï\?\?]+)$#m', $char)) {
					$char  = 'i';
				} elseif(preg_match('#^([ðòóôõöøœo\?]+)$#m', $char)) {
					$char  = 'o';
				} elseif(preg_match('#^([ùúûü\?]+)$#m', $char)) {
					$char  = 'u';
				} elseif(preg_match('#^([ýþÿ\?]+)$#m', $char)) {
					$char  = 'y';
				} elseif(preg_match('#^([ccccç]+)$#m', $char)) {
					$char  = 'c';
				} else {
					$char  = '_';
				}
			}
			$picname .= $char;
		}   
		if ($ext == '' || !stristr($CONFIG['allowed_file_extensions'], $ext)) {
			cpg_die(_ERROR, sprintf(ERR_INVALID_FEXT, $CONFIG['allowed_file_extensions']), __FILE__, __LINE__);
		}
		// Create a unique name for the uploaded file
		$picture_name = $picname . '.' . $ext;
		$nr = 0;
		while (file_exists($dest_dir . $picture_name)) {
			$picture_name = $picname . '~' . $nr++ . '.' . $ext;
		} 
		$uploaded_pic = $dest_dir . $picture_name; 
		/*
		$matches = array();
		$forbidden_chars = strtr($CONFIG['forbiden_fname_char'], array('&amp;' => '&', '&quot;' => '"', '&lt;' => '<', '&gt;' => '>'));
		// Check that the file uploaded has a valid extension
		$picture_name = strtr($_FILES['userpicture']['name'], $forbidden_chars, str_repeat('_', strlen($CONFIG['forbiden_fname_char'])));
		if (!preg_match("/(.+)\.(.*?)\Z/", $picture_name, $matches)) {
			$matches[1] = 'invalid_fname';
			$matches[2] = 'xxx';
		}
		if ($matches[2] == '' || !stristr($CONFIG['allowed_file_extensions'], $matches[2])) {
			cpg_die(_ERROR, sprintf(ERR_INVALID_FEXT, $CONFIG['allowed_file_extensions']), __FILE__, __LINE__);
		}
		// Create a unique name for the uploaded file
		$nr = 0;
		$picture_name = $matches[1] . '.' . $matches[2];

		// Create a unique name for the uploaded file
		$picture_name = $matches[1] . '.' . $matches[2];
		$nr = 0;
		while (file_exists($dest_dir . $picture_name)) {
			$picture_name = $exp[0] . '~' . $nr++ . '.' . $ext;
		}
		$uploaded_pic = $dest_dir . $picture_name; */

		// open_basedir restriction workaround
		// if (!ereg(dirname($_FILES['userpicture']['tmp_name']), ini_get('open_basedir')))
		require_once('includes/classes/cpg_file.php');
		$tmpfile = $CONFIG['userpics'].md5(microtime()).'.tmp';
		if (!(new CPG_File)->move_upload($_FILES['userpicture'], $tmpfile)) {
			cpg_die(_ERROR, 'Couldn\'t create a copy of the uploaded image', __FILE__, __LINE__);
		}
		// Get picture information
		if (!($imginfo = getimagesize($tmpfile))) {
			unlink($tmpfile);
			cpg_die(_ERROR, ERR_INVALID_IMG, __FILE__, __LINE__, true); 
		}
		// Check GD for GIF support else only JPEG and PNG are allowed
		if (($imginfo[2] != IMAGETYPE_JPEG && $imginfo[2] != IMAGETYPE_PNG) &&
		   ($CONFIG['thumb_method'] == 'gd1' || ($CONFIG['thumb_method'] == 'gd2' && !function_exists('imagecreatefromgif')))) {
			unlink($tmpfile);
			cpg_die(_ERROR, GD_FILE_TYPE_ERR, __FILE__, __LINE__, true);
		}
		// Check image type is among those allowed for ImageMagick
		if ($CONFIG['thumb_method'] == 'im' && !stristr($CONFIG['allowed_img_types'], $IMG_TYPES[$imginfo[2]])) {
			unlink($tmpfile);
			cpg_die(_ERROR, sprintf(ALLOWED_IMG_TYPES, $CONFIG['allowed_img_types']), __FILE__, __LINE__);
		}
		// Check that picture size (in pixels) is lower than the maximum allowed
		$max = max($imginfo[0], $imginfo[1]);
		if ($max > $CONFIG['max_upl_width_height']) {
			$max = $CONFIG['max_upl_width_height'];
		}
		// Setup a textual watermark ?
		if ($CONFIG['watermark']) {
			$tolocal = L10NTime::tolocal(gmtime(), $userinfo['user_dst'], $userinfo['user_timezone']) ;
			$watermark = '(c)'.date('Y',$tolocal).' '.CPG_USERNAME.' & '.(!empty($MAIN_CFG['server']['domain']) ? $MAIN_CFG['server']['domain'] : $MAIN_CFG['global']['sitename']);
		} else {
			$watermark = false;
		}
		// Create the "big" image
		if (!resize_image($tmpfile, $imginfo, $uploaded_pic, $max, $CONFIG['thumb_method'], '', $watermark)) {
			unlink($tmpfile);
			cpg_die(_ERROR, $ERROR, __FILE__, __LINE__);
		}
		// Create thumbnail and intermediate image and add the image into the DB
		if (!add_picture($album, $dest_dir, basename($uploaded_pic), $title, $caption, $keywords, $user1, $user2, $user3, $user4, $category, $watermark, $tmpfile)) {
			unlink($uploaded_pic);
			unlink($tmpfile);
			cpg_die(_CRITICAL_ERROR, sprintf(ERR_INSERT_PIC, $uploaded_pic) . '<br /><br />' . $ERROR, __FILE__, __LINE__, true);
		}
		unlink($tmpfile);
		$redirect = ($PIC_NEED_APPROVAL) ? getlink() : getlink("&amp;file=displayimage&amp;pid=".$db->sql_nextid('pid'));
		pageheader(INFO, $redirect);
		msg_box(INFO, UPLOAD_SUCCESS, CONTINU, $redirect);
		pagefooter();
		break;
	
	// Unknown event
	
	default:
		cpg_die(_CRITICAL_ERROR, $_GET['event'].PARAM_MISSING, __FILE__, __LINE__);
}
