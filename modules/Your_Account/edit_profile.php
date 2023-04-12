<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Your_Account/edit_profile.php,v $
  $Revision: 9.35 $
  $Author: nanocaiordo $
  $Date: 2007/12/18 23:04:23 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
function edituser(&$userinfo) {
	$rank_select = [];
 $sel = [];
 $section = null;
 global $db, $prefix, $user_prefix, $pagetitle, $allowmailchange, $allowusertheme;
	$mode = $_GET['edit'] ?? 'profile';
	if ($mode == 'admin' && !defined('ADMIN_PAGES')) $mode = 'profile';
	if ($mode == 'reg_details') {
		$pagetitle .= ' '._BC_DELIM.' '._MA_REGISTRATION_INFO;
	} elseif ($mode == 'profile') {
		$section = 'section=1 OR section=2';
		$pagetitle .= ' '._BC_DELIM.' '._MA_PROFILE_INFO;
	} elseif ($mode == 'private') {
		$section = 'section=3';
		$pagetitle .= ' '._BC_DELIM.' '._MA_PRIVATE;
	} elseif ($mode == 'prefs') {
		$section = 'section=5';
		$pagetitle .= ' '._BC_DELIM.' '._MA_PREFERENCES;
	} elseif ($mode == 'avatar') {
		$pagetitle .= ' '._BC_DELIM.' '._AVATAR_CONTROL;
	} else {
		if (!defined('ADMIN_PAGES')) url_redirect(getlink('Your_Account'));
	}
	if (!defined('ADMIN_PAGES')) {
		global $Blocks;
		$block = array(
			'bid' => 10000,
			'view' => 1,
			'side' => 'l',
			'title' => _TB_BLOCK,
			'content' => member_block()
		);
		$Blocks->custom($block);
		$block = NULL;
		require_once('header.php');
		$action = getlink();
	} else {
		echo "<strong>$userinfo[username]</strong>";
		if ($userinfo['user_level'] == 0) { echo ' ('._ACCTSUSPEND.')'; }
		elseif ($userinfo['user_level'] < 0) { echo ' ('._ACCTDELETE.')'; }
		echo '<br />
		'.(($mode == 'profile') ? '<strong>'._MA_PROFILE_INFO.'</strong>' : '<a href="'.adminlink('users&amp;mode=edit&amp;edit=profile&amp;id='.$userinfo['user_id']).'">'._MA_PROFILE_INFO.'</a>').' |
		'.(($mode == 'reg_details') ? '<strong>'._MA_REGISTRATION_INFO.'</strong>' : '<a href="'.adminlink('users&amp;mode=edit&amp;edit=reg_details&amp;id='.$userinfo['user_id']).'">'._MA_REGISTRATION_INFO.'</a>').' |
		'.(($mode == 'avatar') ? '<strong>'._AVATAR_CONTROL.'</strong>' : '<a href="'.adminlink('users&amp;mode=edit&amp;edit=avatar&amp;id='.$userinfo['user_id']).'">'._AVATAR_CONTROL.'</a>').' |
		'.(($mode == 'admin') ? '<strong>'._MA_PRIVILEGES.'</strong>' : '<a href="'.adminlink('users&amp;mode=edit&amp;edit=admin&amp;id='.$userinfo['user_id']).'">'._MA_PRIVILEGES.'</a>').'
		<br /><br />';
		$action = adminlink('users&amp;id='.$userinfo['user_id']);
	}
	if (!preg_match('#http:\/\/#mi',$userinfo['user_website']) && !empty($userinfo['user_website'])) {
		$userinfo['user_website'] = "http://$userinfo[user_website]";
	}
	global $MAIN_CFG;
	$MAIN_CFG['avatar']['allow_upload'] = (ini_get('file_uploads') == '0' || strtolower(ini_get('file_uploads') == 'off')) ? false : $MAIN_CFG['avatar']['allow_upload'];
	$form_enctype = $MAIN_CFG['avatar']['allow_upload'] ? 'enctype="multipart/form-data"' : '';
	echo '<form action="'.$action.'" method="post" name="Profile" '.$form_enctype.' accept-charset="utf-8">
<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">';

if ($mode == 'reg_details') {
	if (defined('ADMIN_PAGES')) {
		$userinfo['username'] = '<input type="text" name="username" value="'.$userinfo['username'].'" size="25" maxlength="25" class="post" style="width:200px" />';
	}
	echo '<tr>
	<td class="row1" width="40%"><span class="gen">'._USERNAME.'</span></td><td class="row2"><b>'.$userinfo['username'].'</b></td>
  </tr><tr>
	<td class="row1"><span class="gen">'._EMAILADDRESS.'</span></td>
	<td class="row2">';
	if (defined('ADMIN_PAGES') || $allowmailchange) {
		echo '<input type="text" name="user_email" value="'.$userinfo['user_email'].'" size="25" maxlength="255" class="post" style="width:200px" />';
	} else {
		echo '<b>'.$userinfo['user_email']."</b><input type=\"hidden\" name=\"user_email\" value=\"$userinfo[user_email]\" />";
	}
	if (!defined('ADMIN_PAGES')) {
		echo '</td>
  </tr><tr>
	<td class="row1"><span class="gen">'._CURRENTPASSWORD.'</span>'.((!$allowmailchange && !defined('ADMIN_PAGES')) ? '<br />'._CURRENTPASSWORDMSG : '').'</td>
	<td class="row2"><input type="password" name="current_password" size="25" maxlength="20" class="post" style="width:200px" />';
	}
	echo '</td>
  </tr><tr>
	<td class="row1"><span class="gen">'._NEWPASSWORD.'</span><br />'._NEWPASSWORDMSG.'</td>
	<td class="row2"><input type="password" name="new_password" size="25" maxlength="20" class="post" style="width:200px" /></td>
  </tr><tr>
	<td class="row1"><span class="gen">'._CONFIRMPASSWORD.'</span><br />'._CONFIRMPASSWORDMSG.'</td>
	<td class="row2"><input type="password" name="verify_password" size="25" maxlength="20" class="post" style="width:200px" /></td>
  </tr>';
}
elseif ($mode == 'avatar') {
	if (isset($_POST['submitavatar']) && isset($_POST['avatarselect'])) {
		$user_avatar = $_POST['avatarselect'];
		$user_avatar_type = 3;
	} else {
		$user_avatar = $userinfo['user_avatar'];
		$user_avatar_type = $userinfo['user_avatar_type'];
	}
	if ($user_avatar_type == 1) {
		$avatar = $MAIN_CFG['avatar']['path'] . '/' .$user_avatar;
	} elseif ($user_avatar_type == 2) {
		$avatar = $user_avatar;
	} elseif ($user_avatar_type == 3) {
		$avatar = $MAIN_CFG['avatar']['gallery_path'] . '/' .$user_avatar;
	} else {
		$avatar = $MAIN_CFG['avatar']['gallery_path'] . '/' .$MAIN_CFG['avatar']['default'];
	}
	echo '<tr>
		<td class="row1" width="40%"><span class="gensmall">'._AVATAR_INFO.'</span></td>
		<td class="row1" align="center"><span class="gen">'._CURRENT_IMAGE.'</span><br /><br /><img src="'.$avatar.'" name="avatar" alt="" /><br /><br />';
	if ($user_avatar_type != 0) {
		echo '<input type="checkbox" name="avatardel" />&nbsp;<span class="gensmall">'._DELETE_IMAGE.'</span>';
	}
	echo '</td>
	</tr>';
	if ($MAIN_CFG['avatar']['allow_remote']) {
		echo '<tr>
		<td class="row1"><span class="gen">'._AVATAR_OFFSITE.':</span><br /><span class="gensmall">'._AVATAR_OFFSITEMSG.'</span></td>
		<td class="row2"><input type="text" name="avatarremoteurl" size="40" class="post" style="width: 300px" /></td>
	</tr>';
	}
	if ($MAIN_CFG['avatar']['allow_local']) {
		echo '<tr>
		<td class="row1"><span class="gen">'._AVATAR_SELECT.':</span></td>
		<td class="row2"><input type="hidden" name="user_avatar" value="'.$user_avatar.'" /><input type="submit" name="avatargallery" value="'._SHOW_GALLERY.'" class="liteoption" /></td>
	</tr>';
	}
	if ($MAIN_CFG['avatar']['allow_upload']) {
		echo '<tr>
		<td class="row1"><span class="gen">'._AVATAR_UPLOAD_URL.':</span></td>
		<td class="row2"><input type="text" name="avatarurl" size="40" class="post" style="width: 300px" /></td>
	</tr><tr>
		<td class="row1"><span class="gen">'._AVATAR_UPLOAD.':</span></td>
		<td class="row2"><input type="hidden" name="MAX_FILE_SIZE" value="'.$MAIN_CFG['avatar']['filesize'].'" /><input type="file" name="avatar" size="40" class="post" /></td>
	</tr>';
	}
}
elseif ($mode == 'admin') {
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_bbranks WHERE rank_special = 1 ORDER BY rank_title');
	$rank_select[0] = 'No special rank assigned';
	while ($row = $db->sql_fetchrow($result)) {
		$rank_select[$row['rank_id']] = $row['rank_title'];
	}
	$db->sql_freeresult($result);
	$sel[0] = ($userinfo['user_allow_pm']) ? ' checked="checked"' : '';
	$sel[1] = (!$userinfo['user_allow_pm']) ? ' checked="checked"' : '';
	$sel[2] = ($userinfo['user_allowavatar']) ? ' checked="checked"' : '';
	$sel[3] = (!$userinfo['user_allowavatar']) ? ' checked="checked"' : '';
	$sel[4] = ($userinfo['user_level'] < 1) ? ' checked="checked"' : '';
	$sel[5] = ($userinfo['user_level'] > 0) ? ' checked="checked"' : '';
	echo '<tr>
	<td class="row1" colspan="2"><span class="gensmall">These fields are not able to be modified by the users. Here you can set their status and other options that are not given to users.</span></td>
  </tr><tr>
	<td class="row1"><span class="gen">Can send Private Messages</span>
	<td class="row2">
	  <input type="radio" name="user_allow_pm" value="1"'.$sel[0].' /><span class="gen">'._YES.'</span>&nbsp;&nbsp;
	  <input type="radio" name="user_allow_pm" value="0"'.$sel[1].' /><span class="gen">'._NO.'</span>
	</td>
  </tr><tr>
	<td class="row1"><span class="gen">Can display avatar</span>
	<td class="row2">
	  <input type="radio" name="user_allowavatar" value="1"'.$sel[2].' /><span class="gen">'._YES.'</span>&nbsp;&nbsp;
	  <input type="radio" name="user_allowavatar" value="0"'.$sel[3].' /><span class="gen">'._NO.'</span>
	</td>
  </tr><tr>
	<td class="row1"><span class="gen">Rank Title</span>
	<td class="row2">'.select_box('user_rank', $userinfo['user_rank'], $rank_select).'</td>
  </tr><tr>
	<td class="row1"><span class="gen">'._SUSPENDUSER.'</span>
	<td class="row2">
	  <input type="radio" name="user_suspend" value="1"'.$sel[4].' /><span class="gen">'._YES.'</span>&nbsp;&nbsp;
	  <input type="radio" name="user_suspend" value="0"'.$sel[5].' /><span class="gen">'._NO.'</span>
	</td>
  </tr><tr>
	<td class="row1" valign="top"><span class="gen">'._SUSPENDREASON.'</span>
	<td class="row2"><textarea name="suspendreason" rows="5" cols="40" wrap="virtual">'.($userinfo['susdel_reason'] ?? '').'</textarea></td>
  </tr>';
}
else {
	$result = $db->sql_query('SELECT * FROM '.$user_prefix.'_users_fields WHERE '.$section.' ORDER BY section, fid');
	if ($db->sql_numrows($result) > 0) {
		echo '<tr><td class="row1" colspan="2">'._MA_ITEMS_REQUIRED."</td></tr>\n";
		while ($row = $db->sql_fetchrow($result)) {
			if ($row['type'] == 7 && !$allowusertheme) continue;
			if ($row['field'] == 'user_lang' && !$MAIN_CFG['global']['multilingual']) continue;
			$info = $row['langdef'];
			if (defined($info)) $info = constant($info);
			$info .= ($row['visible'] == 2) ? ': *' : ':';
			$align = ($row['type'] == 2) ? ' valign="top"' : '';
			echo '<tr><td class="row1"'.$align.' width="40%"><span class="gen">'.$info.'</span>';
			if (defined($row['langdef'].'MSG') != '') echo '<br />'.constant($row['langdef'].'MSG');
			if ($row['field'] == 'user_timezone') {
				echo '<br /><br /><span class="gen">Daylight Saving Time</span> (<a href="http://webexhibits.org/daylightsaving/" target="_blank">'.strtolower(_TB_INFO).'</a>):';
			}
			echo '</td><td class="row2">'.ma_formfield($row['type'], $row['field'], $row['size'], $userinfo)."</td></tr>\n";
		}
	}
}
	echo '<tr>
		<td class="catbottom" colspan="2" align="center" height="28">
		<input type="hidden" name="id" value="'.$userinfo['user_id'].'" />
		<input type="hidden" name="save" value="'.$mode.'" />
		<input type="submit" name="submit" value="'._SAVECHANGES.'" class="mainoption" />&nbsp;&nbsp;<input type="reset" value="'._RESET.'" name="reset" class="liteoption" />
		</td>
	</tr>
</table></form>
';
}

function saveuser(&$userinfo) {
	$mailer_message = null;
 $section = null;
 $new_password = null;
 global $db, $user_prefix, $MAIN_CFG, $allowusertheme, $CPG_SESS, $SESS;
	$mode = $_POST['save'] ?? 'profile';
	if ($mode == 'admin' && !defined('ADMIN_PAGES')) $mode = 'profile';
	if ($mode == 'profile') {
		$section = 'section=1 OR section=2';
	} elseif ($mode == 'private') {
		$section = 'section=3';
	} elseif ($mode == 'prefs') {
		$section = 'section=5';
	}

	$sql = $pass_change = false;
	if ($mode == 'reg_details') {
		global $allowmailchange;
		$current_password = isset($_POST['current_password']) ? md5($_POST['current_password']) : '';
		if (isset($_POST['new_password'])) {
			$new_password =  $_POST['new_password'];
			$verify_password = $_POST['verify_password'] ?? '';
			if ($new_password != $verify_password) {
				cpg_error(_PASSDIFFERENT, 'ERROR: Password mismatch');
			} elseif ($new_password != '') {
				if (strlen($new_password) < $MAIN_CFG['member']['minpass']) {
					cpg_error(_YOUPASSMUSTBE.' <b>'.$MAIN_CFG['member']['minpass'].'</b> '._CHARLONG, 'ERROR: Password too short');
				}
				$new_password = md5($new_password);
				if ($new_password != $userinfo['user_password']) {
					if (!defined('ADMIN_PAGES') && $current_password != $userinfo['user_password']) { cpg_error('Password incorrect'); }
					$sql = " user_password='$new_password'";
					$pass_change = true;
				}
			}
		}
		$user_email = $_POST['user_email'] ?? $userinfo['user_email'];
		if (($allowmailchange || defined('ADMIN_PAGES')) && $user_email != $userinfo['user_email']) {
			if ($current_password != $userinfo['user_password'] && !defined('ADMIN_PAGES')) { cpg_error('Password incorrect'); }
			if (is_email($user_email) < 1) {
				cpg_error(_ERRORINVEMAIL);
			}
			if ($sql) $sql .= ', ';
			$sql .= "user_email='$user_email'";
		}
		if (defined('ADMIN_PAGES') && isset($_POST['username']) && $_POST['username'] != $userinfo['username']) {
			if (preg_match('#\(\\\ \|\\\\\*\|#\|\\\\\\\\\|%\|"\|\'\|`\|&\|\\\\\^\|@\)#mi', $_POST['username'])) { cpg_error(_ERRORINVNICK); }
			if ($db->sql_count($user_prefix.'_users u, '.$user_prefix.'_users_temp t', "u.username='$_POST[username]' OR t.username='$_POST[username]' LIMIT 0,1") > 0) {
				cpg_error(_NICKTAKEN);
			}
			if ($sql) $sql .= ', ';
			$sql .= "username='$_POST[username]'";
		}
	}
	elseif ($mode == 'avatar') {
		require_once('modules/'.basename(dirname(__FILE__)).'/avatars.php');
		// Local avatar?
		$avatar_local = $_POST['user_avatar'] ?? '';
		// Remote avatar?
		$avatar_remoteurl = !empty($_POST['avatarremoteurl']) ? htmlprepare($_POST['avatarremoteurl']) : '';
		// Upload avatar thru remote or upload?
		$avatar_upload = !empty($_POST['avatarurl']) ? trim($_POST['avatarurl']) : (!empty($_FILES['avatar']) && ($_FILES['avatar']['tmp_name'] != "none") ? $_FILES['avatar']['tmp_name'] : '');
		$avatar_name = !empty($_FILES['avatar']['name']) ? $_FILES['avatar']['name'] : '';

		// 0 = USER_AVATAR_NONE
		if (isset($_POST['avatardel']) || $avatar_local == '') { $sql = avatar_delete($userinfo); }
		// 1 = USER_AVATAR_UPLOAD
		if ((!empty($avatar_upload) || !empty($avatar_name)) && $MAIN_CFG['avatar']['allow_upload']) {
			if (!empty($avatar_upload)) {
				$sql = avatar_upload(empty($avatar_name), $userinfo, $avatar_upload, $_FILES['avatar']);
			} elseif (!empty($avatar_name)) {
				cpg_error(sprintf(_AVATAR_FILESIZE, round($MAIN_CFG['avatar']['filesize'] / 1024)), 'ERROR: Filesize');
			}
		}
		// 2 = USER_AVATAR_REMOTE
		elseif ($avatar_remoteurl != $userinfo['user_avatar'] && $avatar_remoteurl != '' && $MAIN_CFG['avatar']['allow_remote']) {
			if (!preg_match('#^(http)|(ftp):\/\/#i', $avatar_remoteurl) ) {
				$avatar_remoteurl = 'http://' . $avatar_remoteurl;
			}
			if (preg_match('#^((http)|(ftp):\/\/[\w\-]+?\.([\w\-]+\.)+[\w]+(:[0-9]+)*\/.*?\.(gif|jpg|jpeg|png)$)#is', $avatar_remoteurl) ) {
				if ((in_array ('getimagesize', explode(',', ini_get ('disable_functions')))) || (ini_get ('disable_functions') =='getimagesize')) {
					cpg_error('getimagesize is disabled', _AVATAR_ERR_URL);
				} elseif ((!getimagesize($avatar_remoteurl)) ){
					cpg_error('Image has wrong filetype', _AVATAR_ERR_URL);
				} elseif (!($file_data = get_fileinfo($avatar_remoteurl, !$MAIN_CFG['avatar']['animated']))) {
					cpg_error(_AVATAR_ERR_URL);
				} elseif ($file_data['size'] > $MAIN_CFG['avatar']['filesize']) {
					cpg_error(sprintf(_AVATAR_FILESIZE, round($MAIN_CFG['avatar']['filesize'] / 1024)));
				} elseif (!$MAIN_CFG['avatar']['animated'] && $file_data['animation']) {
					cpg_error('Animated avatar not allowed');
				}
				if (avatar_size($avatar_remoteurl)) {
					avatar_delete($userinfo);
					$sql = "user_avatar='$avatar_remoteurl', user_avatar_type=2";
				}
			} else {
				cpg_error('Image has wrong filetype', 'ERROR: Image filetype');
			}
		}
		// 3 = USER_AVATAR_GALLERY
		elseif ($avatar_local != $userinfo['user_avatar'] && $avatar_local != '' &&
		        $MAIN_CFG['avatar']['allow_local'] && file_exists($MAIN_CFG['avatar']['gallery_path'].'/'.$avatar_local)) {
			avatar_delete($userinfo);
			$sql = "user_avatar='$avatar_local', user_avatar_type=3";
		}
	}
	elseif ($mode == 'admin') {
		$sql = 'user_allow_pm='.intval($_POST['user_allow_pm']).', user_allowavatar='.intval($_POST['user_allowavatar']).', user_rank='.intval($_POST['user_rank']);
		$suspendreason = $_POST['suspendreason'] ?? 'no reason';
		if ($_POST['suspendreason'] != $userinfo['susdel_reason']) {
			$sql .= ', susdel_reason=\''.Fix_Quotes($suspendreason)."'";
		}
		if (intval($_POST['user_suspend']) == 0 && $userinfo['user_level'] == 0) {
			$sql .= ', user_level=1';
		} elseif (intval($_POST['user_suspend']) > 0 && $userinfo['user_level'] > 0) {
			$message = _SORRYTO.' '.$MAIN_CFG['global']['sitename'].' '._HASSUSPEND;
			if ($suspendreason > '') {
				$message .= "\n\n"._SUSPENDREASON."\n$suspendreason";
			}
			$from = 'noreply@'.preg_replace('#www.#m', '', $MAIN_CFG['server']['domain']);
			if (!send_mail($mailer_message, $message, 0, _ACCTSUSPEND, $userinfo['user_email'], $userinfo['username'], $from)) {
				trigger_error($mailer_message, E_USER_WARNING);
			}
			$sql .= ', user_level=0, susdel_reason=\''.Fix_Quotes($suspendreason)."'";
		}
	}
	else {
		$result = $db->sql_query('SELECT field, type FROM '.$user_prefix.'_users_fields WHERE '.$section);
		if ($db->sql_numrows($result) > 0) {
			while ($row = $db->sql_fetchrow($result)) {
				$field = ($row['field'] == 'name')?'realname':$row['field'];
				$value = Fix_Quotes($_POST[$field],1);
				if ($row['field'] == 'user_lang' && !$MAIN_CFG['global']['multilingual']) continue;
				if ($row['type'] == 1 || $row['type'] == 4) {
					$value = intval($value);
				} else {
					if ($field == 'user_website') {
						if (!preg_match('#^http[s]?:\/\/#i', $value)) {
							$value = 'http://' . $value;
						}
						if (!preg_match('#^http[s]?\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $value)) {
							$value = '';
						}
					}
				}
				if ($row['type'] == 7 && !$allowusertheme) {
					$value = $MAIN_CFG['global']['Default_Theme'];
				}
				if ($row['type'] == 6) {
					$value = date_raw($value);
					if (checkdate(substr($value, 4, 2), substr($value, 6, 2), substr($value, 0, 4))) {
						$sql .= ", $row[field]='$value'";
					}
				} elseif (array_key_exists($row['field'], $userinfo) && $userinfo[$row['field']] != $value) {
					$sql .= ", $row[field]='$value'";
				}
				if ($field == 'user_timezone') {
					$sql .= ', user_dst='.intval($_POST['user_dst']);
				}
			}
			if ($sql) { $sql = substr($sql, 2); }
		}
	}
	if ($sql) {
		$db->sql_query('UPDATE '.$user_prefix.'_users SET '.$sql." WHERE user_id=".intval($userinfo['user_id']));
		$_SESSION['CPG_USER'] = false;
		unset($_SESSION['CPG_USER']);
		if (!defined('ADMIN_PAGES')) {
			if ($pass_change) {
				global $CLASS;
				$CLASS['member']->setmemcookie($userinfo['user_id'],$userinfo['username'], $new_password);
			}
			if (isset($_POST['theme']) && $allowusertheme) {
				$CPG_SESS['theme'] = $_POST['theme'];
			}
			cpg_error(_TASK_COMPLETED, _TB_INFO, getlink('&edit='.$mode));
		} else {
			cpg_error(_TASK_COMPLETED, _TB_INFO, adminlink('users&mode=edit&edit='.$mode.'&id='.$userinfo['user_id']));
		}
	}
	if (!defined('ADMIN_PAGES')) {
		url_redirect(getlink('&edit='.$mode));
	} else {
		cpg_error('Nothing changed', 'No update', adminlink('users&mode=edit&edit='.$mode.'&id='.$userinfo['user_id']));
	}
}
