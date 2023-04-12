<?php
/***************************************************************************
 *								  download.php
 *							  -------------------
 *	 begin				  : Monday, Apr 1, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 *	 $Id: download.php,v 9.5 2007/12/12 12:54:23 nanocaiordo Exp $
 *
 *
 ***************************************************************************/

/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/
if (!defined('CPG_NUKE')) { exit; }

define('IN_PHPBB', true);
$phpbb_root_path = 'modules/Forums/';
require_once($phpbb_root_path.'nukebb.php');

$download_id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$thumbnail = (isset($_POST['thumb']) || isset($_GET['thumb']));

// Send file to browser
function send_file_to_browser($attachment, $upload_dir)
{
	global $_SERVER, $lang, $db, $attach_config, $board_config;
	$filename = ($upload_dir == '') ? $attachment['physical_filename'] : $upload_dir . '/' . $attachment['physical_filename'];
	$gotit = FALSE;
	if (!intval($attach_config['allow_ftp_upload'])) {
		if (!file_exists(amod_realpath($filename))) {
			message_die(GENERAL_ERROR, $lang['Error_no_attachment'] . "<br /><br /><b>404 File Not Found:</b> The File <i>" . $filename . "</i> does not exist.");
		} else {
			$gotit = TRUE;
		}
	}

	//
	// Determine the Browser the User is using, because of some nasty incompatibilities.
	// Most of the methods used in this function are from phpMyAdmin. :)
	//
	$HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'] ?? '';
	if (preg_match('#Opera(\/| )([0-9].[0-9]{1,2})#m', $HTTP_USER_AGENT))	 {
		$browser_agent = 'opera';
	} else if (preg_match('#MSIE ([0-9].[0-9]{1,2})#m', $HTTP_USER_AGENT)) {
		$browser_agent = 'ie';
	} else if (preg_match('#OmniWeb\/([0-9].[0-9]{1,2})#m', $HTTP_USER_AGENT)) {
		$browser_agent = 'omniweb';
	} else if (preg_match('#Netscape([0-9]{1})#m', $HTTP_USER_AGENT)) {
		$browser_agent = 'netscape';
	} else if (preg_match('#Mozilla\/([0-9].[0-9]{1,2})#m', $HTTP_USER_AGENT)) {
		$browser_agent = 'mozilla';
	} else if (preg_match('#Konqueror\/([0-9].[0-9]{1,2})#m', $HTTP_USER_AGENT)) {
		$browser_agent = 'konqueror';
	} else {
		$browser_agent = 'other';
	}

	if (GZIPSUPPORT) {
		while (ob_end_clean());
		header('Content-Encoding: none');
	}
	// Now the tricky part... let's dance
/*
	header('Pragma: public');
	header('Content-Transfer-Encoding: none');
	header("Expires: 0"); // set expiration time
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
*/
	//
	// Now send the File Contents to the Browser
	//
	if ($gotit) {
		$size = filesize($filename);
		if ($attachment['mimetype']=='application/x-zip-compressed') {
			if (intval($attach_config['allow_ftp_upload'])) {
				if (trim($attach_config['download_path']) == '') {
					message_die(GENERAL_ERROR, 'Physical Download not possible with the current Attachment Setting');
				}
				$url = trim($attach_config['download_path']) . '/' . $attachment['physical_filename'];
				$redirect_path = $url;
			} else {
				$redirect_path = '/'.$upload_dir . '/' . $attachment['physical_filename'];
			}
			url_redirect($redirect_path);
		} else {
			// Correct the mime type - we force application/octetstream for all files, except images
			// Please do not change this, it is a security precaution
			if (!preg_match('#image#m', $attachment['mimetype'])) {
				$attachment['mimetype'] = ($browser_agent == 'ie' || $browser_agent == 'opera') ? 'application/octetstream' : 'application/octet-stream';
			}
			if (!($fp = fopen($filename, 'rb'))) {
				cpg_error('Could not open file for sending');
			}
			// Send out the Headers
			header('Content-Type: ' . $attachment['mimetype'] . '; name="' . $attachment['real_filename'] . '"');
			header('Content-Disposition: inline; filename="' . $attachment['real_filename'] . '"');
			print fread($fp, $size);
			fclose ($fp);
		}
	}
	else if (!$gotit && intval($attach_config['allow_ftp_upload']))
	{
		$ini_val = ( phpversion() >= '4.0.0' ) ? 'ini_get' : 'get_cfg_var';
		$tmp_path = ( !$ini_val('safe_mode') ) ? '/tmp' : $upload_dir . '/tmp';
		$tmp_filename = tempnam($tmp_path, 't0000');
		unlink($tmp_filename);

		include_once('includes/classes/cpg_ftp.php');
		$ftp = new cpg_ftp($attach_config['ftp_server'], $attach_config['ftp_user'], $attach_config['ftp_pass'], $attach_config['ftp_path'], $attach_config['ftp_pasv_mode']);
		$mode = FTP_BINARY;
		if ( (preg_match("/text/i", $attachment['mimetype'])) || (preg_match("/html/i", $attachment['mimetype'])) ) {
			$mode = FTP_ASCII;
		}
		$result = ftp_get($ftp->connect_id, $tmp_filename, $filename, $mode);
		$ftp->close();
		if (!$result) {
			message_die(GENERAL_ERROR, $lang['Error_no_attachment'] . "<br /><br /><b>404 File Not Found:</b> The File <i>" . $filename . "</i> does not exist.");
		}

		$size = filesize($tmp_filename);
		if ($size) {
			header("Content-length: $size");
		}
		if ($attachment['mimetype']=='application/x-zip-compressed') {
			if (intval($attach_config['allow_ftp_upload'])) {
				if (trim($attach_config['download_path']) == '') {
					message_die(GENERAL_ERROR, 'Physical Download not possible with the current Attachment Setting');
				}
				$url = trim($attach_config['download_path']) . '/' . $attachment['physical_filename'];
				$redirect_path = $url;
			} else {
				$redirect_path = $upload_dir . '/' . $attachment['physical_filename'];
			}
			url_redirect($redirect_path);
		} else {
			// Correct the mime type - we force application/octetstream for all files, except images
			// Please do not change this, it is a security precaution
			if (!strstr($attachment['mimetype'], 'image')) {
				$attachment['mimetype'] = ($browser_agent == 'ie' || $browser_agent == 'opera') ? 'application/octetstream' : 'application/octet-stream';
			}
			// Send out the Headers
			header('Content-Type: ' . $attachment['mimetype'] . '; name="' . $attachment['real_filename'] . '"');
			header('Content-Disposition: inline; filename="' . $attachment['real_filename'] . '"');
			print readfile($filename);
			unlink($tmp_filename);
		}
	} else {
		message_die(GENERAL_ERROR, $lang['Error_no_attachment'] . "<br /><br /><b>404 File Not Found:</b> The File <i>" . $filename . "</i> does not exist.");
	}
	exit;
}
//
// End Functions
//

//
// Start Session Management
//
$userdata = session_pagestart($user_ip, PAGE_INDEX);
init_userprefs($userdata);

if ($download_id < 1) {
	message_die(GENERAL_ERROR, $lang['No_attachment_selected']);
}

if ((intval($attach_config['disable_mod']) == 1) && ($userdata['user_level'] != ADMIN)) {
	message_die(GENERAL_MESSAGE, $lang['Attachment_feature_disabled']);
}

$attachment = $db->sql_ufetchrow('SELECT * FROM ' . ATTACHMENTS_DESC_TABLE . ' WHERE attach_id = '.$download_id);
if (empty($attachment)) {
	message_die(GENERAL_MESSAGE, $lang['Error_no_attachment']);
}

//
// get forum_id for attachment authorization or private message authorization
//
$authorised = FALSE;

$auth_pages = $db->sql_ufetchrowset('SELECT * FROM ' . ATTACHMENTS_TABLE . ' WHERE attach_id = ' . $attachment['attach_id']);
$num_auth_pages = is_countable($auth_pages) ? count($auth_pages) : 0;

for ($i = 0; $i < $num_auth_pages && $authorised == FALSE; $i++) {
	if (intval($auth_pages[$i]['post_id']) != 0) {
		$row = $db->sql_ufetchrow('SELECT forum_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . $auth_pages[$i]['post_id']);
		$forum_id = $row['forum_id'];
		$is_auth = array();
		$is_auth = auth(AUTH_ALL, $forum_id, $userdata);
		if ($is_auth['auth_download']) {
			$authorised = TRUE;
		}
	} else {
		if (intval($attach_config['allow_pm_attach']) && ( ($userdata['user_id'] == $auth_pages[$i]['user_id_2']) || ($userdata['user_id'] == $auth_pages[$i]['user_id_1']) ) || ($userdata['user_level'] == ADMIN) ) {
			$authorised = TRUE;
		}
	}
}

if (!$authorised) {
//	  message_die(GENERAL_MESSAGE, $lang['Sorry_auth_view_attach']);
}

//
// Get Information on currently allowed Extensions
//
$rows = $db->sql_ufetchrowset("SELECT e.extension, g.download_mode FROM " . EXTENSION_GROUPS_TABLE . " g, " . EXTENSIONS_TABLE . " e WHERE (g.allow_group = 1) AND (g.group_id = e.group_id)");
$num_rows = is_countable($rows) ? count($rows) : 0;
for ($i = 0; $i < $num_rows; $i++) {
	$extension = strtolower(trim($rows[$i]['extension']));
	$allowed_extensions[] = $extension;
	$download_mode[$extension] = $rows[$i]['download_mode'];
}

//
// disallowed ?
//
if (!in_array($attachment['extension'], $allowed_extensions) && $userdata['user_level'] != ADMIN) {
	message_die(GENERAL_MESSAGE, sprintf($lang['Extension_disabled_after_posting'], $attachment['extension']));
}

$download_mode = intval($download_mode[$attachment['extension']]);

if ($thumbnail) {
	$attachment['physical_filename'] = THUMB_DIR . '/t_' . $attachment['physical_filename'];
} else {
	// Update download count
	$db->sql_query('UPDATE '.ATTACHMENTS_DESC_TABLE.' SET download_count = download_count + 1 WHERE attach_id = '.$attachment['attach_id']);
}

//
// Determine the 'presenting'-method
//
if ($download_mode == PHYSICAL_LINK) {
	if (intval($attach_config['allow_ftp_upload'])) {
		if (trim($attach_config['download_path']) == '') {
			message_die(GENERAL_ERROR, 'Physical Download not possible with the current Attachment Setting');
		}
		$url = trim($attach_config['download_path']) . '/' . $attachment['physical_filename'];
		$redirect_path = $url;
	} else {
		$redirect_path = '/'.$upload_dir.'/'.$attachment['physical_filename'];
	}
	url_redirect($redirect_path);
} else {
	if (intval($attach_config['allow_ftp_upload'])) {
		// We do not need a download path, we are not downloading physically
		send_file_to_browser($attachment, '');
	} else {
		send_file_to_browser($attachment, $upload_dir);
	}
}
