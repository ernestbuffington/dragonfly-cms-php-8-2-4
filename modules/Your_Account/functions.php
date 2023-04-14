<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Your_Account/functions.php,v $
  $Revision: 9.25 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 01:52:39 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

function userCheck($username, $user_email) {
	global $user_prefix, $db, $DeniedUserNames, $CensorList;
	/* This line allows you to set the maximum username length */
	if (strlen($username) > 50) { cpg_error(_NICK2LONG); }
	/* This line allows you to set the minimum username length */
	if (strlen($username) < 4) { cpg_error(_NICK2SHORT); }
	if (is_numeric($username)) { cpg_error(_NICKNUMERIC); }
	/* This line allows you to block the use of a username containing one of the string values in this array */
	$deniedusers = '(staff)';
	foreach ($DeniedUserNames as $denieduser) { $deniedusers .= "|($denieduser)"; }
	foreach ($CensorList as $denieduser) { $deniedusers .= "|($denieduser)"; }
	$words = array();
	if (preg_match('#' . preg_quote($deniedusers, '#') . '#mi', $username, $words)) {
		cpg_error(_NAMEDENIED." <b>\"$words[0]\"</b>");
	}
	if (empty($username) || preg_match('#\(\\\ \|\\\\\*\|#\|\\\\\\\\\|%\|"\|\'\|`\|&\|\\\\\^\|@\)#mi',$username)) {
		cpg_error(_ERRORINVNICK);
	}
	$email = Security::check_email($user_email);
	if ($email == 0)  { cpg_error('Email address to short'); }
	if ($email == -1) { cpg_error(sprintf(_ERROR_BAD_FORMAT, 'email')); }
	if ($email == -2) { cpg_error('The email address domain "'.$user_email[1].'" is disallowed for registration.'); }

	if ($db->sql_count($user_prefix.'_users', "username='$username'") > 0 ||
		$db->sql_count($user_prefix.'_users_temp', "username='$username'") > 0) {
		cpg_error(_NICKTAKEN);
	}

	if ($db->sql_count($user_prefix.'_users', "user_email='$user_email'") > 0 ||
		$db->sql_count($user_prefix.'_users_temp', "user_email='$user_email'") > 0) {
		cpg_error(_EMAILREGISTERED);
	}
	/* Now check deleted PHP-Nuke account emails */
	if ($db->sql_count($user_prefix.'_users', "user_email='".md5($user_email)."'") > 0) {
		cpg_error(_EMAILNOTUSABLE);
	}
	return;
}
function check_fields(&$fieldlist, &$valuelist, &$fields, $post=true) {
	global $db, $user_prefix;
	$input = ($post ? $_POST : $fields);
	$content = '';
	$result = $db->sql_uquery("SELECT * FROM ".$user_prefix."_users_fields WHERE visible > 0");
	while ($row = $db->sql_fetchrow($result)) {
		$var = ($row['field'] == 'name')?'realname':$row['field'];
		$info = $row['langdef'];
		if ($info[0] == '_' && defined($info)) $info = constant($info);
		if (empty($input[$var]) && $row['visible'] == 2) {
			cpg_error('Required field "'.$info.'" can\'t be empty');
		} else {
			$val = Fix_Quotes($input[$var], 1);
			//if (strlen($val) > 0) {
				if ($row['type'] == 1 || $row['type'] == 4) $val = intval($val);
				elseif ($row['type'] != 3) $val = substr($val,0,$row['size']);
				$fieldlist .= ", ".$row['field'];
				$valuelist .= ", '$val'";
				$fields[$var] = htmlprepare($val);
				if ($row['type'] == 1) $val = ($val) ? _YES : _NO;
				$content .= "<tr><td><b>$info:</b></td><td>$val</td></tr>\n";
				if ($row['field'] == 'user_timezone') {
					$fields['user_dst'] = intval($input['user_dst']);
					$fieldlist .= ', user_dst';
					$valuelist .= ', '.$fields['user_dst'];
				}
			//}
		}
	}
	return $content;
}
function member_block() {
	global $db, $MAIN_CFG, $prefix, $userinfo;
	
	get_lang('Your_Account');

	$op = $_GET['op'] ?? '';
	$mode = $_GET['edit'] ?? $_POST['save'] ?? '';
	$content = '<span class="gen">'._TB_INFO.'</span><div style="margin-left: 8px;">'
		.((isset($_GET['profile']) && $_GET['profile'] == $userinfo['user_id']) ? '<b>'._TB_PROFILE_INFO.'</b>' : '<a href="'.getlink('Your_Account&amp;profile='.$userinfo['user_id']).'">'._TB_PROFILE_INFO.'</a>').'<br />'
		.(($mode == 'profile') ? '<b>'._TB_EDIT_PROFILE.'</b>' : '<a href="'.getlink('Your_Account&amp;edit=profile').'">'._TB_EDIT_PROFILE.'</a>').'<br />'
		.(($mode == 'private') ? '<b>'._TB_EDIT_PRIVATE.'</b>' : '<a href="'.getlink('Your_Account&amp;edit=private').'">'._TB_EDIT_PRIVATE.'</a>').'<br />'
		.(($mode == 'reg_details') ? '<b>'._TB_EDIT_REG.'</b>' : '<a href="'.getlink('Your_Account&amp;edit=reg_details').'">'._TB_EDIT_REG.'</a>').'<br />'
	.'</div><span class="gen">'._TB_CONFIG.'</span><div style="margin-left: 8px;">'
		.(($mode == 'prefs') ? '<b>'._TB_EDIT_PREFS.'</b>' : '<a href="'.getlink('Your_Account&amp;edit=prefs').'">'._TB_EDIT_PREFS.'</a>').'<br />'
		.(($op == 'edithome') ? '<b>'._TB_EDIT_HOME.'</b>' : '<a href="'.getlink('Your_Account&amp;op=edithome').'">'._TB_EDIT_HOME.'</a>').'<br />'
		.(($op == 'editcomm') ? '<b>'._TB_EDIT_COMM.'</b>' : '<a href="'.getlink('Your_Account&amp;op=editcomm').'">'._TB_EDIT_COMM.'</a>').'<br />'
	.'</div><span class="gen">'._TB_PERSONAL.'</span><div style="margin-left: 8px;">';
	if (($MAIN_CFG['avatar']['allow_local']) || ($MAIN_CFG['avatar']['allow_remote'])) {
		$content .= (($mode == 'avatar') ? '<b>'._TB_EDIT_AVATAR.'</b>' : '<a href="'.getlink('Your_Account&amp;edit=avatar').'">'._TB_EDIT_AVATAR.'</a>').'<br />';
	}
	if (is_active('coppermine')) {
		$content .= '<a href="'.getlink('coppermine&amp;cat='.(10000+$userinfo['user_id'])).'">'._TB_PERSONAL_GALLERY.'</a><br />';
	}
	if (is_active('Blogs')) { 
		$content .= '<a href="'.getlink('Blogs&amp;mode=add').'">'._TB_PERSONAL_JOURNAL.'</a><br />'; 
	}
	$content .= '</div>';
	if (is_active('Private_Messages')) {
		list($pm_inbox) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_bbprivmsgs
	WHERE privmsgs_to_userid='.$userinfo['user_id'].' AND privmsgs_type IN (0, 1, 5)', SQL_NUM);

		list($pm_outbox) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_bbprivmsgs
	WHERE privmsgs_from_userid='.$userinfo['user_id'].' AND privmsgs_type IN (1, 5)', SQL_NUM);

		list($pm_sentbox) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_bbprivmsgs
	WHERE privmsgs_from_userid='.$userinfo['user_id'].' AND privmsgs_type = 2', SQL_NUM);

		list($pm_savebox) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_bbprivmsgs
	WHERE (privmsgs_to_userid='.$userinfo['user_id'].' AND privmsgs_type = 3)
	OR (privmsgs_from_userid='.$userinfo['user_id'].' AND privmsgs_type = 4)', SQL_NUM);

		$folder = $_GET['folder'] ?? '';
		$content .= '<span class="gen">'._TB_PRIVMSGS.'</span><div style="margin-left: 8px;">'
			.(($folder == 'inbox') ? '<b>'._TB_PRIVMSGS_INBOX.': '.$pm_inbox.'</b>' : '<a href="'.getlink('Private_Messages&amp;folder=inbox').'">'._TB_PRIVMSGS_INBOX.': <b>'.$pm_inbox.'</b></a>').'<br />'
			.(($folder == 'outbox') ? '<b>'._TB_PRIVMSGS_OUTBOX.': '.$pm_outbox.'</b>' : '<a href="'.getlink('Private_Messages&amp;folder=outbox').'">'._TB_PRIVMSGS_OUTBOX.': <b>'.$pm_outbox.'</b></a>').'<br />'
			.(($folder == 'sentbox') ? '<b>'._TB_PRIVMSGS_SENTBOX.': '.$pm_sentbox.'</b>' : '<a href="'.getlink('Private_Messages&amp;folder=sentbox').'">'._TB_PRIVMSGS_SENTBOX.': <b>'.$pm_sentbox.'</b></a>').'<br />'
			.(($folder == 'savebox') ? '<b>'._TB_PRIVMSGS_SAVEBOX.': '.$pm_savebox.'</b>' : '<a href="'.getlink('Private_Messages&amp;folder=savebox').'">'._TB_PRIVMSGS_SAVEBOX.': <b>'.$pm_savebox.'</b></a>').'<br />'
			.((isset($_GET['mode']) && $_GET['mode'] == 'post') ? '<b>'._TB_PRIVMSGS_SEND.'</b>' : '<a href="'.getlink('Private_Messages&amp;mode=post').'">'._TB_PRIVMSGS_SEND.'</a>').'<br />'
			.'</div>';
	}
	$content .= '<span class="gen">'._TB_TASKS.'</span><div style="margin-left: 8px;">
		<a href="'.getlink('Your_Account&amp;op=logout').'">'._ACCTEXIT.'</a></div>';
	//themesidebox(_TB_BLOCK, $content, 10000);
	return $content;
}

function make_pass($length, $type=5) {
	$chars = array();
	$upper = range('A', 'Z');
	$lower = range('a', 'z');
	$num = range(0, 9);
	$special = array('~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-');
	
	if ($type == 0) {
		$chars = array_merge($chars, $upper, $lower, $num);
	} elseif ($type == 1) {
		$chars = array_merge($chars, $special);
	} elseif ($type == 2) {
		$chars = array_merge($chars, $upper);
	} elseif ($type == 3) {
		$chars = array_merge($chars, $lower);
	} elseif ($type == 4) {
		$chars = array_merge($chars, $num);
	} else {
		$chars = array_merge($chars, $upper, $lower, $num, $special);
	}
	
	if (version_compare(PHP_VERSION, '4.2.0') == -1) { mt_srand((double)microtime() * 1234567); }
	
	shuffle($chars);
	$pass = '';
	for ($x=0; $x<$length; $x++) { $pass .= $chars[random_int(0, (sizeof($chars)-1))]; }
	
	return $pass;
}

function ma_formfield($type, $field, $size, $userinfo) {
	global $MAIN_CFG, $CPG_SESS, $l10n_dst_regions, $l10n_gmt_regions;
	if ($type == 0) {
		return '<input type="text" name="'.(($field == 'name')?'realname':$field).'" value="'.htmlprepare($userinfo[$field]).'" class="post" style="width: 200px" size="25" maxlength="'.$size.'" />';
	} else if ($type == 1) {
		return yesno_option($field, (is_user() ? $userinfo[$field] : $size));
	} else if ($type == 2) {
		return '<textarea name="'.$field.'" style="width: 300px" rows="6" cols="30" class="post">'.htmlprepare($userinfo[$field]).'</textarea>';
	} else if ($type == 3) {
		$ret = select_box($field, $userinfo[$field], $l10n_gmt_regions);
		if ($field == 'user_timezone') {
			$ret .= '<br /><select name="user_dst">';
			foreach ($l10n_dst_regions as $region => $data) {
				$sel = ($userinfo['user_dst'] == $region) ? 'selected="selected"' : '';
				$ret .= "<option value=\"$region\" $sel>$data[0]</option>\n";
			}
			$ret .= '</select>';
		}
		return $ret;
	} else if ($type == 4) {
		return '<input type="text" name="'.$field.'" value="'.htmlprepare(is_user() ? $userinfo[$field] : '').'" class="post" style="width: 100px" size="15" maxlength="'.$size.'" />';
	} else if ($type == 5) {
		return select_box($field, (is_user() ? $userinfo[$field] : 'm'), array('m' => _MALE, 'f' => _FEMALE));
	} else if ($type == 6) {
		return '<input type="text" name="'.$field.'" value="'.(is_user() ? date_short($userinfo[$field]) : '').'" class="post" style="width: 100px" size="15" maxlength="10" /> 10/24/1980';
	} else if ($type == 7) {
		$themelist = array();
		$handle=opendir('themes');
		while ($file = readdir($handle)) {
			if (!preg_match('#[\.]#m',$file) && $file != 'CVS' && file_exists("themes/$file/theme.php")) { $themelist[] = "$file"; }
		}
		closedir($handle);
		natcasesort($themelist);
		return select_option($field, ((is_user() && !empty($userinfo['theme']) && is_dir('themes/'.$userinfo['theme'])) ? $userinfo['theme'] : $CPG_SESS['theme']), $themelist);
	} else if ($type == 8) {
		if ($field == 'user_lang') {
			return lang_selectbox($userinfo['user_lang'], $field, false);
		}
		return '';
	}
	return '';
}