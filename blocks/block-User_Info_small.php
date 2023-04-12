<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-User_Info_small.php,v $
  $Revision: 9.18 $
  $Author: phoenix $
  $Date: 2007/10/18 14:35:09 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $prefix, $user_prefix, $db, $sec_code, $userinfo, $MAIN_CFG, $CPG_SESS;
$content = '';

// number online
$result = $db->sql_query('SELECT COUNT(*), guest FROM '.$prefix.'_session GROUP BY guest ORDER BY guest');
$online_num = array(0, 0, 0, 0);
while ($row = $db->sql_fetchrow($result)) {
	$online_num[$row[1]] = intval($row[0]);
}
$db->sql_freeresult($result);
// number of members
list($numusers) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$user_prefix."_users 
WHERE user_id > 1 AND user_level > 0",SQL_NUM);
// users registered today
$day = L10NTime::tolocal((mktime(0,0,0,date('n'),date('j'),date('Y'))-date('Z')), $userinfo['user_dst'], $userinfo['user_timezone']);
list($userCount[0]) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$user_prefix."_users 
WHERE user_regdate>='".$day."'", SQL_NUM);
// users registered yesterday
list($userCount[1]) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$user_prefix."_users 
WHERE user_regdate<'".$day."' AND user_regdate>='".($day-86400)."'", SQL_NUM);
// latest member
list($lastuser) = $db->sql_ufetchrow('SELECT username FROM '.$user_prefix.'_users 
WHERE user_active = 1 AND user_level > 0 
ORDER BY user_id DESC 
LIMIT 0,1',SQL_NUM);

if(is_user()) {
	$content .= '<div style="text-align:center;">';
	if ($userinfo['user_avatar_type'] == 1) {
		$avatar = $MAIN_CFG['avatar']['path'].'/'.$userinfo['user_avatar'];
	} else if ($userinfo['user_avatar_type'] == 2) {
		$avatar = $userinfo['user_avatar'];
	} else if ($userinfo['user_avatar_type'] == 3 && !empty($userinfo['user_avatar'])) {
		$avatar = $MAIN_CFG['avatar']['gallery_path'].'/'.$userinfo['user_avatar'];
	} else {
		$avatar = $MAIN_CFG['avatar']['gallery_path'].'/'.$MAIN_CFG['avatar']['default'];
	}
	$content .= "<img src=\"$avatar\" alt=\"\" />";
	$content .= '<br />'._BWEL." <b>$userinfo[username]</b><br /><img src=\"images/spacer.gif\" style=\"height:8px;\" alt=\"\" /></div>\n";
	if (is_active('Private_Messages')) {
		$pm = $userinfo['user_new_privmsg']+$userinfo['user_unread_privmsg'];
		$content .= '&nbsp;<a title="'._READSEND.'" href="'.getlink('Private_Messages').'"><img src="images/blocks/email.gif" alt="" /></a>&nbsp;&nbsp;<a title="'._READSEND.'" href="'.getlink('Private_Messages').'">'._INBOX.'</a>';
		$content .= '&nbsp;&nbsp;'._NEW.": <b>$pm</b><br />\n";
	}
	$content .= '<a title="'._ACCOUNTOPTIONS.'" href="'.getlink('Your_Account').'"><img src="images/blocks/logout.gif" alt="" /></a>&nbsp;<a title="'._ACCOUNTOPTIONS.'" href="'.getlink('Your_Account').'">'._Your_AccountLANG.'</a><br />
	<a title="'._LOGOUTACCT.'" href="'.getlink('Your_Account&amp;op=logout&amp;redirect', false).'"><img src="images/blocks/login.gif" alt="" style="float:left;" /></a>&nbsp;<a title="'._LOGOUTACCT.'" href="'.getlink('Your_Account&amp;op=logout&amp;redirect', false).'">'._LOGOUT.'</a>';
} else {
	if (isset($_GET['redirect']) && !isset($CPG_SESS['user']['redirect'])) { $CPG_SESS['user']['redirect'] = $CPG_SESS['user']['uri']; }
	$redirect = $CPG_SESS['user']['redirect'] ?? get_uri();
	$content .= '<div style="text-align:center;"><img src="images/blocks/no_avatar.gif" alt="" /><br />'._BWEL.' <b>'._ANONYMOUS.'</b></div>
	<hr /><form action="'.$redirect.'" method="post" enctype="multipart/form-data" accept-charset="utf-8" style="margin:0;"><div>
	<span style="float:left; height:25px;">'._NICKNAME.'</span><span style="float:right; height:25px;"><input type="text" name="ulogin" size="10" maxlength="25" /></span><br />
	<span style="float:left; height:25px;">'._PASSWORD.'</span><span style="float:right; height:25px;"><input type="password" name="user_password" size="10" maxlength="20" /></span><br />
	';
	if ($sec_code & 2) {
		$content .= '<span style="float:left; height:25px;">'._SECURITYCODE.'</span><span style="float:right; height:25px;">'.generate_secimg().'</span><br style="clear:left;" />
		<span style="float:left; height:25px;">'._TYPESECCODE.'</span><span style="float:right; height:25px;"><input type="text" name="gfx_check" size="8" maxlength="8" /></span><br />';
	}
	// don't show register link unless allowuserreg is yes
	$content .= '<span style="float:left; height:25px;">'.($MAIN_CFG['member']['allowuserreg'] ? '<input type="button" value="'._BREG.'" onclick="window.location=\''.getlink('Your_Account&amp;file=register',1,1).'\'" />' : '').'</span>
	<span style="float:right; height:25px;"><input type="submit" value="'._LOGIN.'" />
	</span></div></form>';
}
if (is_admin()) {
	$content .= '<br style="clear:left;"/><a title="'._LOGOUTADMINACCT.'" href="'.adminlink('logout').'"><img src="images/blocks/login.gif" alt="" /></a>&nbsp;<a title="'._LOGOUTADMINACCT.'" href="'.adminlink('logout').'">'._ADMIN.' '._LOGOUT."</a><br />\n";
}

$content .= '<hr />
<img src="images/blocks/group-1.gif" alt="" /> <span style="font-weight:bold; text-decoration:underline;">'._BMEMP.':</span><br />
<img src="images/blocks/ur-moderator.gif" alt="" /> '._BLATEST.': <a href="'.getlink("Your_Account&amp;profile=$lastuser").'"><b>'.$lastuser.'</b></a><br />
<img src="images/blocks/ur-author.gif" alt="" /> '._BTD.': <b>'.$userCount[0].'</b><br />
<img src="images/blocks/ur-admin.gif" alt="" /> '._BYD.': <b>'.$userCount[1].'</b><br />
<img src="images/blocks/ur-guest.gif" alt="" /> '._BOVER.': <b>'.$numusers.'</b><br />
<hr />
<img src="images/blocks/group-1.gif" alt="" /> <span style="font-weight:bold; text-decoration:underline;">'._BVISIT.':</span><br />
<img src="images/blocks/ur-member.gif" alt="" /> '._BMEM.': <b>'.$online_num[0].'</b><br />
<img src="images/blocks/ur-anony.gif" alt="" /> '._BVIS.': <b>'.$online_num[1].'</b><br />
<img src="images/blocks/ur-anony.gif" alt="" /> '._BOTS.': <b>'.$online_num[3].'</b><br />
<img src="images/blocks/ur-registered.gif" alt="" /> '._STAFF.': <b>'.$online_num[2].'</b>
<hr />
<span style="font-weight:bold; text-decoration:underline;">'._STAFFONL.':</span><br />';
// staff online
$result = $db->sql_query("SELECT a.uname, u.user_id FROM ".$prefix."_session AS a 
LEFT JOIN ".$user_prefix."_users AS u ON u.username = a.uname 
WHERE guest = 2 ORDER BY a.uname");
if($db->sql_numrows($result) < 1) {
	$content .= '<br /><i>'._STAFFNONE.'</i>';
} else {
	$num = 0;
	while($row = $db->sql_fetchrow($result)) {
		$num++;
		if ($num < 10) { $content .= '0'; }
		$content .= "$num: ";
		if($row['user_id'] > 1) {
			$content .= '<a href="'.getlink('Your_Account&amp;profile='.$row['user_id']).'">'.$row['uname'].'</a><br />';
		}
		else { $content .=	$row['uname'].'<br />'; }
	}
}
$db->sql_freeresult($result);