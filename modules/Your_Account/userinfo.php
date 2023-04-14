<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Your_Account/userinfo.php,v $
  $Revision: 9.39 $
  $Author: nanocaiordo $
  $Date: 2007/09/18 03:33:58 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

function userinfo($username) {
	$board_config = [];
 $blockslist = [];
 global $db, $prefix, $user_prefix, $currentlang, $pagetitle, $MAIN_CFG, $CPG_SESS, $CLASS;
	$owninfo = (is_user() && ($username == is_user() || strtolower($username) == strtolower($CLASS['member']->members[is_user()]['username'])));
	if ($owninfo) {
		$userinfo =& $CLASS['member']->members[is_user()];
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
		//define('MEMBER_BLOCK', true);
	} else if (!($userinfo = getusrdata($username)) || $userinfo['user_level'] < 1) {
		require_once('header.php');
		OpenTable();
		echo _NOINFOFOR.' <strong>'.htmlspecialchars($username).'</strong>';
		if (!$userinfo) { echo '<br /><br /><em>'._MA_USERNOEXIST.'</em>'; }
		elseif ($userinfo['user_level'] == 0) { echo '<br /><br /><em>'._ACCSUSPENDED.'</em>'; }
		elseif ($userinfo['user_level'] == -1) { echo '<br /><br /><em>'._ACCDELETED.'</em>'; }
		CloseTable();
		return;
	}
	$username = $userinfo['username'];
	$imgpath = 'themes/'.$CPG_SESS['theme'].'/images/forums/lang_';
	$imgpath .= (file_exists($imgpath.$currentlang.'/icon_email.gif') ? $currentlang : 'english');
	if ($owninfo) {
		$pagetitle .= ' '._BC_DELIM.' '.$username.', '._THISISYOURPAGE;
	} else {
		$pagetitle .= ' '._BC_DELIM.' '._PERSONALINFO.' '._BC_DELIM.' '.$username;
	}
	require_once('header.php');
	require_once(CORE_PATH.'nbbcode.php');
//OpenTable();
	if ($userinfo['user_avatar_type'] == 1) {
		$avatar = $MAIN_CFG['avatar']['path'].'/'.$userinfo['user_avatar'];
	} else if ($userinfo['user_avatar_type'] == 2) {
		$avatar = $userinfo['user_avatar'];
	} else if ($userinfo['user_avatar_type'] == 3 && !empty($userinfo['user_avatar'])) {
		$avatar = $MAIN_CFG['avatar']['gallery_path'].'/'.$userinfo['user_avatar'];
	} else {
		$avatar = $MAIN_CFG['avatar']['gallery_path'].'/'.$MAIN_CFG['avatar']['default'];
	}
	if ($avatar) {
		$avatar = '<img src="'.$avatar.'" alt="" />';
	}
	if ($userinfo['user_website']) {
		if (!preg_match('#http:\/\/#mi', $userinfo['user_website'])) { $userinfo['user_website'] = "http://$userinfo[user_website]"; } 
	}
	if (strlen($userinfo['user_website']) < 8) { $userinfo['user_website'] = ''; }
	echo '<table class="forumline" width="100%" cellspacing="1" cellpadding="3" border="0" align="center">';
	if ($userinfo['user_rank']) {
		$sql = 'rank_id = '.$userinfo['user_rank'].' AND rank_special = 1';
	} else {
		$sql = 'rank_min <= '.intval($userinfo['user_posts']).' AND rank_special = 0 ORDER BY rank_min DESC';
	}
	list($poster_rank, $rank_image) = $db->sql_ufetchrow('SELECT rank_title, rank_image FROM '.$prefix.'_bbranks WHERE '.$sql, SQL_NUM);
	$poster_rank = ($rank_image) ? '<img src="'.$rank_image.'" alt="'.$poster_rank.'" title="'.$poster_rank.'" />' : $poster_rank;
	echo '<tr>
	<td class="catleft" width="40%" height="28" align="center"><b><span class="gen">'._AVATAR.'</span></b></td>
	<td class="catright" width="60%" align="center"><b><span class="gen">'._ABOUT_USER.$username.'</span></b></td>
  </tr>
  <tr>
	<td class="row1" height="6" valign="top" align="center">'.$avatar.'</td>
	<td class="row1" rowspan="3" valign="top"><table width="100%" border="0" cellspacing="1" cellpadding="3">
		<tr>
		  <td valign="middle" align="right" width="33%"><span class="gen">'._JOINED.':</span></td>
		  <td width="100%"><b><span class="gen">'.formatDateTime($userinfo['user_regdate'], _DATESTRING3).'</span></b></td>
		</tr><tr>
		  <td valign="middle" align="right" width="33%"><span class="gen">'._RANK.':</span></td>
		  <td><b><span class="gen">'.$poster_rank.'</span></b></td>
		</tr><tr>
		  <td valign="middle" align="right" width="33%"><span class="gen">'._LOCATION.':</span></td>
		  <td><b><span class="gen">'.decode_bb_all($userinfo['user_from']).'</span></b></td>
		</tr><tr>
		  <td valign="middle" align="right" width="33%"><span class="gen">'._WEBSITE.':</span></td>
		  <td><b><span class="gen">'.$userinfo['user_website'].'</span></b></td>
		</tr><tr>
		  <td valign="middle" align="right" width="33%"><span class="gen">'._MA_OCCUPATION.':</span></td>
		  <td><b><span class="gen">'.decode_bb_all($userinfo['user_occ']).'</span></b></td>
		</tr><tr>
		  <td valign="top" align="right" width="33%"><span class="gen">'._INTERESTS.':</span></td>
		  <td><b><span class="gen">'.decode_bb_all($userinfo['user_interests']).'</span></b></td>
		</tr>';
	if (can_admin('members')||$owninfo){
		$result = $db->sql_query("SELECT field, langdef, type FROM ".$user_prefix."_users_fields WHERE section = 2 OR section = 3");
	} else {
		$result = $db->sql_query("SELECT field, langdef, type FROM ".$user_prefix."_users_fields WHERE section = 2");
	}
	if ($db->sql_numrows($result) > 0) {
		while ($row = $db->sql_fetchrow($result)) {
			if ($row['type'] == 1) {
				$value = $userinfo[$row['field']] ? _YES : _NO;
			} else {
				$value = $userinfo[$row['field']];
			}
			if (defined($row['langdef'])) $row['langdef'] = constant($row['langdef']);
			echo '<tr>
		  <td valign="top" align="right" width="33%"><span class="gen">'.$row['langdef'].':</span></td>
		  <td><b><span class="gen">'.$value.'</span></b></td>
		</tr>';
		}
	}
	if ($userinfo['user_sig']) {
		echo '<tr>
		  <td valign="top" align="right" style="white-space:nowrap;"><span class="gen">'._SIGNATURE.':</span></td>
		  <td class="row1"><span class="gen">'.decode_bb_all($userinfo['user_sig'], 1, false).'</span></td>
		</tr>';
	}
	if ($userinfo['bio']) {
		echo '<tr>
		  <td valign="top" align="right" style="white-space:nowrap;"><span class="gen">'._MA_EXTRAINFO.':</span></td>
		  <td class="row1"><span class="gen">'.decode_bb_all($userinfo['bio'], 1, false).'</span></td>
		</tr>';
	}
	if (is_active('Blogs')) {
		list($num_blogs) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$prefix."_blogs WHERE aid='$username' AND private=0 LIMIT 1");
		if ($num_blogs > 0) {
			echo '<tr>
		  <td colspan="2" align="center"><b><span class="gen"><a href="'.getlink('Blogs&amp;mode=user&amp;nick='.$username).'">'._READMYJOURNAL.'</a></span></b></td>
		</tr>';
		}
	}
	echo '		</table>
	</td>
  </tr>
  <tr>
	<td class="catleft" align="center" height="28"><b><span class="gen">'._CONTACTINFO.'</span></b></td>
  </tr>
  <tr>
	<td class="row1" valign="top"><table width="100%" border="0" cellspacing="1" cellpadding="3">';
	if (($userinfo['user_viewemail'] && is_user()) || $owninfo || (is_admin() && !($CLASS['member']->demo))) { $email = $userinfo['user_email']; }
	else if ($userinfo['femail']) { $email = $userinfo['femail']; }
	if (isset($email) && Security::check_email($email)) {
		$email = 'mailto:'.$email;
		if (!$owninfo && is_user()) {
			define('IN_PHPBB', true);
			define('PHPBB_INSTALLED', true);
			$phpbb_root_path = "./modules/Forums/";
			require_once($phpbb_root_path.'common.php');
			if ($board_config['board_email_form']) $email = getlink('Forums&amp;file=profile&amp;mode=email&amp;u='.$userinfo['user_id']);
		}
		echo '<tr>
		  <td valign="middle" align="right" style="white-space:nowrap;"><span class="gen">'._EMAILADDRESS.':</span></td>
		  <td class="row1" valign="middle" width="100%"><span class="gen"><a href="'.$email.'"><img src="'.$imgpath.'/icon_email.gif" alt="" /></a></span></td>
		</tr>';
	}
	if (!$owninfo && is_user() && is_active('Private_Messages')) {
		echo '<tr>
		  <td valign="middle" style="white-space:nowrap;" align="right"><span class="gen">'._PM.':</span></td>
		  <td class="row1" valign="middle" width="100%"><span class="gen"><a href="'.getlink("Private_Messages&amp;mode=post&amp;u=$userinfo[user_id]").'"><img src="'.$imgpath.'/icon_pm.gif" alt="" /></a></span></td>
		</tr>';
	}
	if (!empty($userinfo['user_msnm'] )) {
	echo '<tr>
		  <td valign="middle" style="white-space:nowrap;" align="right"><span class="gen">'._MA_MSNM.':</span></td>
		  <td class="row1" valign="middle" width="100%">
		  <span class="gen"><a href="http://members.msn.com/' . $userinfo['user_msnm'] . '" target="_blank"><img src="'.$imgpath.'/icon_msnm.gif" border="0" alt="'.$userinfo['user_msnm'].'"	 title="'.$userinfo['user_msnm'].'" /></a></span></td>
		</tr>';
	}
	if (!empty($userinfo['user_yim'])) {
	echo '<tr>
		  <td valign="middle" style="white-space:nowrap;" align="right"><span class="gen">'._MA_YIM.':</span></td>
		  <td class="row1" valign="middle" width="100%">
		  <span class="gen"><a href="http://edit.yahoo.com/config/send_webmesg?.target='.$userinfo['user_yim'].'&amp;.src=pg"><img src="'.$imgpath.'/icon_yim.gif" alt="" /></a></span></td>
		</tr>';
	}
	if (!empty($userinfo['user_aim'])) {
	echo '<tr>
		  <td valign="middle" style="white-space:nowrap;" align="right"><span class="gen">'._MA_AIM.':</span></td>
		  <td class="row1" valign="middle" width="100%">
		  <span class="gen"><a href="aim:goim?screenname='.$userinfo['user_aim'].'&amp;message=Hey+are+you+there?"><img src="'.$imgpath.'/icon_aim.gif" alt="" /></a></span></td>
		</tr>';
	}
	if (!empty($userinfo['user_icq'])) {
	echo '<tr>
		  <td valign="middle" style="white-space:nowrap;" align="right"><span class="gen">'._MA_ICQ.':</span></td>
		  <td class="row1" valign="middle" width="100%">
		  <span class="gen"><a href="http://wwp.icq.com/scripts/search.dll?to='.$userinfo['user_icq'].'"><img src="'.$imgpath.'/icon_icq_add.gif" alt="" /></a></span></td>
		</tr>';
	}
	if (!empty($userinfo['user_skype'])){
		echo '<tr>
		  <td valign="middle" style="white-space:nowrap;" align="right"><span class="gen">VoIP#:</span></td>
		  <td class="row1" valign="middle" width="100%">
		  <span class="gen"><a href="callto://'.$userinfo['user_skype'].'"><img src="'.$imgpath.'/icon_skype.gif" alt="" /></a></span></td>
		</tr>';
	}
	if (is_active('coppermine')) {
		$user_gallery = 10000+$userinfo['user_id'];
		$ugall_result = $db->sql_query("SELECT p.pid FROM ".$prefix."_cpg_pictures AS p, ".$prefix."_cpg_albums AS a WHERE a.aid = p.aid AND a.category = $user_gallery");
		 if ($db->sql_numrows($ugall_result)>0) {
			echo '<tr>
		  	<td valign="middle" style="white-space:nowrap;" align="right"><span class="gen">'._coppermineLANG.':</span></td>
		  	<td class="row1" valign="middle" width="100%">
		  	<span class="gen"><a href="'.getlink('coppermine&amp;cat='.$user_gallery).'"><img src="'.$imgpath.'/icon_gallery.gif" alt="" /></a></span>
		  	</td>
			</tr>';
		}
	}
	echo '
	  </table>
	</td>
  </tr>
</table>';
//CloseTable();
	if ($owninfo || can_admin('members')) {
		OpenTable();
		echo '<div align="center" class="content"><a href="'.getlink('&amp;edit=prefs').'">';
		if ($userinfo['newsletter']) {
			echo '<b>'._SUBSCRIBED.'</b></a><br />';
		} else {
			echo '<b>'._NOTSUBSCRIBED.'</b></a><br />';
		}
		if (can_admin('members')) {
			echo '<br />[ <a href="'.adminlink("users&amp;mode=edit&amp;edit=profile&amp;id=$userinfo[user_id]").'">'._EDITUSER.'</a>'
			.' | <a href="'.adminlink("users&amp;mode=edit&amp;edit=admin&amp;id=$userinfo[user_id]").'">'._SUSPENDUSER.'</a> ]<br />';
		}
		echo '</div>';
		CloseTable();
		if ($owninfo) {
			if ($MAIN_CFG['member']['my_headlines']) {
				$hid = isset($_POST['hid']) ? intval($_POST['hid']) : 0;
				$url = $_POST['url'] ?? '';
				echo '<br />';
				OpenTable();
				echo '<center><b>'._MA_MYHEADLINES.'</b><br /><br />'._SELECTASITE.'<br /><br />'
					.'<form action="'.get_uri().'" method="post" enctype="multipart/form-data" accept-charset="utf-8">'
					.'<input type="hidden" name="url" value="0" />'
					.'<select name="hid"><option value="0">'._SELECTASITE2.'</option>';
				$sql4 = 'SELECT hid, sitename FROM '.$prefix.'_headlines ORDER BY sitename';
				$headl = $db->sql_query($sql4);
				while (list($nhid, $hsitename) = $db->sql_fetchrow($headl)) {
					$sel = ($hid == $nhid ) ? 'selected="selected"' : '';
					echo "<option value=\"$nhid\" $sel>$hsitename</option>\n";
				}
				echo '</select> <input type="submit" value="'._GO.'" /></form>'._ORTYPEURL.'<br /><br />'
					.'<form action="'.get_uri().'" method="post" enctype="multipart/form-data" accept-charset="utf-8">'
					.'<input type="hidden" name="hid" value="0" />'
					.'<input type="text" name="url" size="40" maxlength="200" value="http://" />&nbsp;&nbsp;'
					.'<input type="submit" value="'._GO.'" /></form></center><br />';
				if ($hid > 0 || ($hid == 0 && strlen($url) > 10)) {
					if ($hid > 0) {
						$sql5 = 'SELECT sitename, headlinesurl FROM '.$prefix."_headlines WHERE hid='$hid'";
						$result5 = $db->sql_query($sql5);
						list($title, $url) = $db->sql_fetchrow($result5);
						$siteurl = preg_replace('#http:\/\/#mi', '', $url);
						$siteurl = explode('/', $siteurl);
					} else {
						if (!preg_match('#http:\/\/#m', $url)) { $url = 'http://'.$url; }
						$siteurl = preg_replace('#http:\/\/#mi', '', $url);
						$siteurl = explode('/', $siteurl);
						$title = 'http://'.$siteurl[0];
					}
					$content = rss_content($url);
					echo '<center>';
					OpenTable2();
					if ($content) {
						echo '<b>'._HEADLINESFROM." <a href=\"http://$siteurl[0]\" target=\"new\">$title</a></b><br /><br /><div align=\"left\">$content</div>";
					} else {
						echo '<div style="text-align:center;">'._RSSPROBLEM.'</div>';
					}
					CloseTable2();
					echo '</center><br />';
				}
				CloseTable();
			}
		}
	} // end owninfo

	$blocksdir = dir('modules/Your_Account/blocks');
	while ($func=$blocksdir->read()) {
		if (substr($func, -3) == 'php') {
			$blockslist[] = $func;
		}
	}
	closedir($blocksdir->handle);
	sort($blockslist);
	for ($i=0; $i < sizeof($blockslist); $i++) {
		require_once('modules/Your_Account/blocks/'.$blockslist[$i]);
	}
}
