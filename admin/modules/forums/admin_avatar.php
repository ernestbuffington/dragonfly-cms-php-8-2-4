<?php
/***************************************************************************
 *							  avatar_manage.php
 *							-------------------
 *   begin				: Thursday, Apr 25, 2002
 *
 ***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/
/* Modifications made by CPG Dev Team http://cpgnuke.com				*/
/* Last modification notes:											 */
/*																	  */
/*   $Id: admin_avatar.php,v 9.4 2005/10/11 12:31:44 djmaze Exp $	  */
/*																	  */
/************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
// Any mode passed?
if( isset($_GET['mode']) || isset($_POST['mode']) ) {
	$mode = $_GET['mode'] ?? $_POST['mode'];
	$target = $_GET['target'] ?? $_POST['target'];
} else {
	$mode = "";
}

// Read in the board config to maintain dynamic
$config_result = $db->sql_query("select config_name,config_value from ". CONFIG_TABLE ."");
while ($config_row = $db->sql_fetchrow($config_result)) {
	$board_config[$config_row['config_name']] = $config_row['config_value'];
}

// Select all avatars and usernames that have an uploaded avatar currently
$result = $db->sql_query("SELECT user_id, username, user_avatar FROM " . USERS_TABLE
	 . " WHERE user_avatar_type = " . USER_AVATAR_UPLOAD . " AND user_avatar IS NOT NULL");
// Create a hash to keep track of all the user that is using the uploaded avatar
while ($avatar_rowset = $db->sql_fetchrow($result)) {
	$avatar_usage[$avatar_rowset['user_avatar']] = $avatar_rowset['username'];
}

// This is the variable that points to the path of the avatars
// You may need to adjust this to meet your needs ;)
$real_avatar_dir = $MAIN_CFG['avatar']['path'];

switch( $mode )
{
	case "delete":
		echo '<table cellpadding="4" cellspacing="1" border="0" class="forumline">';
		if ( unlink($real_avatar_dir.'/'.$target) ) {
			print "<tr><td>Success, $target deleted!</td></tr><tr><td><a href=\"".adminlink("&amp;do=avatar")."\">Continue</a></td></tr></table>";
		} else {
			print "<tr><td>FAILED to delete $target!</td></tr><tr><td><a href=\"javascript:history.go(-1)\">Go Back</a></td></tr></table>";
		}
		break;

	default:
		$template->assign_vars(array(
			'L_AVATAR' => $lang['Avatar'],
			'L_FILESIZE' => _FILESIZE,
			'L_USERNAME' => $lang['Username'],
			'L_EDIT' => _EDIT,
			'L_BYTES' => $lang['Bytes']
		));
		$template->set_filenames(array('body' => 'forums/admin/admin_avatars.html'));
		$alt1 = '#CCCCFF';
		$alt2 = '#EEEEEE';
		$alter = $alt2;

		// This is where we go through the avatar directory and report whether they are not
		// used or if they are used, by who.
		if ($avatar_dir = opendir($real_avatar_dir)) {
			get_lang('Your_Account');
			while ($file = readdir($avatar_dir)) {
				// This is where the script will filter out any file that doesn't match the patterns
				if( $file != "." && $file != ".." && preg_match('#\.(gif|jpg|jpeg|png)$#m',$file) ) {
					$stats = stat($real_avatar_dir.'/'.$file);

					// Alternating row colows code
					if	 ($alter == $alt1) { $alter = $alt2; }
					elseif ($alter == $alt2) { $alter = $alt1; }
					if (isset($avatar_usage[$file]) ) {
						// Bingo, someone is using this avatar
						// Since we need to supply a link with a valid sid later in html, let's build it now
						$av_id = $avatar_usage[$file];
						$result = $db->sql_query("SELECT user_id FROM " . USERS_TABLE . " WHERE username = '$av_id'");
						list($av_uid) = $db->sql_fetchrow($result);
						$username = $avatar_usage[$file];
						$edit_url = '<a href="'.adminlink("users&amp;mode=edit&amp;edit=avatar&amp;id=$av_uid").'">'._EDITUSER.'</a>';
					} else {
						// Not used, safe to display delete link for admin
						$username = _NONE;
						$edit_url = '<a href="'.adminlink("&amp;do=avatar&amp;mode=delete&target=$file").'" onClick="if(confirm(\'Are you sure you want to delete: '.$file.' ?\')) return true; else return false;">'._DELETE.'</a>';
					}
					$template->assign_block_vars('avatarrow', array(
						'AVATAR_IMG' => "$real_avatar_dir/$file",
						'FILENAME' => $file,
						'STATS' => $stats[7],
						'USERNAME' => $username,
						'EDIT' => $edit_url
					));
				}
			}
		} else {
			// If we made it to this else there was a problem trying to read the avatar directory
			// If you see this error message check this variable:
			// $real_avatar_dir -> This may be set incorrectly for your site.
			print "Avatar directory unavailable!";
		}
		break;
}
