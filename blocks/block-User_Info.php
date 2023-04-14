<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Based on "All Info Block" by Alex Hession
   http://www.gnaunited.com

  Block heavily modified by DJMaze
   http://www.cpgnuke.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-User_Info.php,v $
  $Revision: 9.20 $
  $Author: phoenix $
  $Date: 2007/10/18 14:35:09 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $prefix, $user_prefix, $db, $sec_code, $userinfo, $MAIN_CFG, $CPG_SESS;
$content = '';

$pm_active = is_active('Private_Messages');
$memres = $db->sql_query('SELECT s.uname, s.module, s.url, u.user_allow_viewonline FROM '.$prefix.'_session AS s LEFT JOIN '.$user_prefix.'_users AS u ON u.username=s.uname WHERE guest=0 OR guest=2 ORDER BY s.uname');
$anonres = $db->sql_query('SELECT uname, module, url FROM '.$prefix.'_session WHERE guest=1 OR guest=3');
$online_num[0] = $db->sql_numrows($memres);
$online_num[1] = $db->sql_numrows($anonres);
$online_num[2] = $online_num[0] + $online_num[1];
$who_where = array('', '');
$hidden = 0;
for ($i = 1; $i <= $online_num[0]; $i++) {
	$onluser = $db->sql_fetchrow($memres);
	if ($onluser['user_allow_viewonline'] || is_admin()) {
		$ttt = "<a href=\"".getlink("Your_Account&amp;profile=$onluser[uname]")."\">";
		if ($onluser['user_allow_viewonline']) $ttt .= "$onluser[uname]</a> &gt;";
		else $ttt .= "<i>$onluser[uname]</i></a> &gt;";

		if($i < 10) { $who_where[0] .= "0"; }
		$who_where[0] .= "$i: $ttt <a href=\"$onluser[url]\">$onluser[module]</a><br />\n";
	} else {
		$hidden++;
	}
}
$db->sql_freeresult($memres);
for ($i = 1; $i <= $online_num[1]; $i++) {
	$onluser = $db->sql_fetchrow($anonres);
	if($i < 10) { $who_where[1] .= "0"; }
	$who_where[1] .= "$i: <a href=\"$onluser[url]\">$onluser[module]</a><br />\n";
}
$db->sql_freeresult($anonres);

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
	$content .= "<img style=\"border-radius: 15px; max-width: 150px; max-height: 150px;\" src=\"$avatar\" alt=\"\" />";
	$content .= '<br />'._BWEL.' <b>'.$userinfo['username'].'</b><br /><img src="images/spacer.gif" style="height:8px;" alt="" /></div>';
	if ($pm_active) {
		$pm = $userinfo['user_new_privmsg']+$userinfo['user_unread_privmsg'];
		$content .= '&nbsp;<a title="'._READSEND.'" href="'.getlink('Private_Messages').'"><img src="images/blocks/email.gif" alt="" style="border:0;" /></a>&nbsp;&nbsp;<a title="'._READSEND.'" href="'.getlink('Private_Messages').'">'._INBOX.'</a>';
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
$day = L10NTime::tolocal((mktime(0,0,0,date('n'),date('j'),date('Y'))-date('Z')), $userinfo['user_dst'], $userinfo['user_timezone']);
list($last[0]) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$user_prefix."_users 
WHERE user_regdate>='".$day."'", SQL_NUM);
list($last[1]) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$user_prefix."_users 
WHERE user_regdate<'".$day."' AND user_regdate>='".($day-86400)."'", SQL_NUM);
list($lastuser) = $db->sql_ufetchrow("SELECT username FROM ".$user_prefix."_users 
WHERE user_active = 1 AND user_level > 0 
ORDER BY user_id DESC 
LIMIT 1", SQL_NUM);
list($numusers) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$user_prefix."_users 
WHERE user_id > 1 AND user_level > 0", SQL_NUM);
$content .= '<hr /><span style="font-weight:bold; text-decoration:underline;">'._BMEMP.":</span><br />\n";
$content .= '<img src="images/blocks/ur-moderator.gif" alt="" /> '._BLATEST.': <a href="'.getlink("Your_Account&amp;profile=$lastuser").'"><b>'.$lastuser.'</b></a><br />
<img src="images/blocks/ur-author.gif" alt="" /> '._BTD.': <b>'.$last[0].'</b><br />
<img src="images/blocks/ur-admin.gif" alt="" /> '._BYD.': <b>'.$last[1].'</b><br />
<img src="images/blocks/ur-guest.gif" alt="" /> '._BOVER.': <b>'.$numusers.'</b><br />
<hr />
<img src="images/blocks/group-1.gif" alt="" /> <span style="font-weight:bold; text-decoration:underline;">'._BVISIT.':</span><br />
<img src="images/blocks/ur-member.gif" alt="" /> '._BMEM.': <b>'.$online_num[0].'</b><br />
<img src="images/blocks/ur-anony.gif" alt="" /> '._BVIS.': <b>'.$online_num[1].'</b><br />
<img src="images/blocks/ur-registered.gif" alt="" /> '._BTT.': <b>'.$online_num[2].'</b>
<hr />
<img src="images/blocks/group-1.gif" alt="" /> <span style="font-weight:bold; text-decoration:underline;">'._WHOWHERE.':</span><br />';
if($online_num[0] > 0) {
	$content .= '<img src="images/blocks/members.gif" alt="" />&nbsp;<span class="content"><b>'._BMEM.':</b></span><br />'.$who_where[0];
}
if($online_num[1] > 0) {
	$content .= '<img src="images/blocks/visitors.gif" alt="" />&nbsp;<span class="content"><b>'._BVIS.':</b></span><br />'.$who_where[1];
}
if ($hidden > 0) {
	$content .= '<span class="content"><b>'._BHID.':</b></span> '.$hidden;
}

$num = 0;
$content .= "\n<hr />\n".'<span style="font-weight:bold; text-decoration:underline;">'._STAFFONL.':</span><br />';
$result = $db->sql_query("SELECT a.uname, u.user_id FROM ".$prefix."_session AS a 
LEFT JOIN ".$user_prefix."_users AS u ON u.username = a.uname 
WHERE guest = 2 ORDER BY a.uname");
if($db->sql_numrows($result) < 1) {
	$content .= '<br /><i>'._STAFFNONE.'</i>';
} else while($row = $db->sql_fetchrow($result)) {
	$num++;
	if ($num < 10) {
		$content .= '0';
	}
	$content .= "$num: ";
	if($row['user_id'] > 1) {
		if($pm_active) {
			$content .= "<a href=\"".getlink("Private_Messages&amp;mode=post&amp;u=$row[user_id]")."\"><img src=\"images/blocks/email.gif\" style=\"border:0;\" alt=\"PM\" title=\"Send a private message to $row[uname]\" /></a>";
		}
		$content .= " <a href=\"".getlink("Your_Account&amp;profile=$row[user_id]")."\">$row[uname]</a><br />\n";
	}
	else $content .= "$row[uname]<br />\n";
}
$db->sql_freeresult($result);
