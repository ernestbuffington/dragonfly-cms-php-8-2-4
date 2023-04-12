<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/index.php,v $
  $Revision: 9.36 $
  $Author: nanocaiordo $
  $Date: 2008/02/06 11:14:50 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
require_once('header.php');
require_once(CORE_PATH.'nbbcode.php');
/*
$cpgtpl->set_filenames(array('body' => 'admin/index_body.html'));
$cpgtpl->display('body');
$cpgtpl->destroy();
*/

if (isset($_POST['np_save'])) {
	$np_notes = Fix_Quotes(check_words($_POST['np_notes']));
	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='$np_notes' WHERE cfg_field='text' AND cfg_name='notepad'");
	Cache::array_delete('MAIN_CFG');
	url_redirect(adminlink());
}
if (isset($_POST['np_lock']) && can_admin()) {
	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='1' WHERE cfg_field='lock' AND cfg_name='notepad'");
	Cache::array_delete('MAIN_CFG');
	url_redirect(adminlink());
}
if (isset($_POST['np_unlock']) && can_admin()) {
	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='0' WHERE cfg_field='lock' AND cfg_name='notepad'");
	Cache::array_delete('MAIN_CFG');
	url_redirect(adminlink());
}

if (can_admin() && $MAIN_CFG['global']['update_monitor']) {
	if (!isset($CPG_SESS['update_monitor'])) { $CPG_SESS['update_monitor'] = false; }
	if ($CPG_SESS['update_monitor']) {
		$CPG_SESS['update_monitor'] = Cache::array_load('data', 'update_monitor', true);
	}
	if (!$CPG_SESS['update_monitor']) {
		$update_url = 'udp://69.57.185.107/update.php?vers='.CPG_NUKE;
		$updinfo = get_fileinfo($update_url, false, true);
		if ($updinfo) {
			$items = preg_split('#(<item>)#s', $updinfo['data'], -1, PREG_SPLIT_NO_EMPTY);
			unset($updinfo);
			$curvers = preg_replace('#^(.*)<version>(.*)</version>(.*)#s','\\2',$items[0], 1);
			$upgurl = preg_replace('#^(.*)<url>(.*)</url>(.*)#s','\\2',$items[0], 1);
			unset($items[0]);
			$data = array('current'=>$curvers, 'url'=>$upgurl, 'num'=>count($items), 'msg'=>array());
			foreach ($items as $item) {
				if (!empty($item)) {
					$alrt_vers = preg_replace('#(.*)<version>(.*)</version>(.*)#s','\\2',$item);
					$alrt_title = preg_replace('#(.*)<title>(.*)</title>(.*)#s','\\2',$item);
					$alrt_desc = preg_replace('#(.*)<description>(.*)</description>(.*)#s','\\2',$item);
					$alrt_date = preg_replace('#(.*)<date>(.*)</date>(.*)#s','\\2',$item);
					$data['msg'][] = array('vers'=>$alrt_vers, 'title'=>$alrt_title, 'desc'=>$alrt_desc, 'date'=>$alrt_date);
				}
			}
			$CPG_SESS['update_monitor'] = true;
			Cache::array_save('data', 'update_monitor', $data);
		}
	}
	OpenTable();
	echo '<div><center><span class="genmed"><strong>'._UM.'</strong></span><br /><br />';
	if (!$CPG_SESS['update_monitor']) {
		echo '<img src="images/update/error.png" alt="" /><br /><br />'._UM_F.'</center>';
	} else {
		echo (version_compare(CPG_NUKE, $data['current'], '>=') ? '<img src="images/update/green.png" alt="" /><br /><br /><span style="color: #009933;"><strong>'._UM_G.' ('.CPG_NUKE.(version_compare(CPG_NUKE, $data['current'], '>') ? ' CVS' : '').')</strong></span>' : '<img src="images/update/red.png" alt="" /><br /><br /><span style="color: #ae0000;"><strong>'.sprintf(_UM_R, $data['current'], $data['url']).'</strong></span>');
		echo '</center>';
		if (count($data['msg'])) { echo '<br />'; }
		foreach ($data['msg'] as $item) {
			echo '<fieldset><legend>'.$item['title'].'</legend>'.decode_bbcode($item['desc']).'<br /><br />'._POSTEDON.' '.formatDateTime($item['date'], '%B %d, %Y').'</fieldset>';
		}
	}
	echo '</div>';
	CloseTable();
	echo '<br />';
}

GraphicAdmin();

$result = $db->sql_query('SELECT COUNT(*), guest FROM '.$prefix.'_session GROUP BY guest ORDER BY guest');
$online_num = array(0, 0, 0, 0);
while ($row = $db->sql_fetchrow($result)) {
	$online_num[$row[1]] = intval($row[0]);
}
$db->sql_freeresult($result);

$day = L10NTime::tolocal((mktime(0,0,0,date('n'),date('j'),date('Y'))-date('Z')), $userinfo['user_dst'], $userinfo['user_timezone']);
list($userCount) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$user_prefix."_users WHERE user_regdate>='".$day."'", SQL_NUM);
list($userCount2) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$user_prefix."_users WHERE user_regdate<'".$day."' AND user_regdate>='".($day-86400)."'", SQL_NUM);

OpenTable();
echo '<div align="center"><span class="genmed"><strong>'._WHOSONLINE.'</strong></span><br />
<br /><strong>'._BON.'</strong><br />'._BMEM.': <strong>'.$online_num[0].'</strong> | '._BVIS.': <strong>'.$online_num[1].'</strong> | '._BOTS.': <strong>'.$online_num[3].'</strong> | '._STAFF.': <strong>'.$online_num[2].'</strong><br />
<br /><strong>'._BMEMP.'</strong><br />'._BTD.": <strong>$userCount</strong> | "._BYD.": <strong>$userCount2</strong>
</div>";
CloseTable();
echo '<br />';

OpenTable();
echo '<div align="center"><span class="genmed"><strong>'._DEFHOMEMODULE.'</strong></span><br /><br /><img src="images/home.gif" alt="" /><br /><br />[ <strong>'.ereg_replace('_', ' ', $MAIN_CFG['global']['main_module']).'</strong> ]';
if (can_admin()) { echo '&nbsp;&nbsp;[ <a href="'.adminlink('modules').'">'._CHANGE.'</a> ]'; }
echo '</div>';
CloseTable();
echo '<br />';

if (is_active('Surveys') && can_admin('surveys')) {
	list($poll_id, $poll_title) = $db->sql_ufetchrow("SELECT poll_id, poll_title FROM ".$prefix."_poll_desc WHERE artid=0 ORDER BY poll_id DESC LIMIT 0,1", SQL_NUM);
	OpenTable();
	echo '<div align="center"><span class="genmed"><strong>'._CURRENTPOLL.'</strong></span><br /><br />'.$poll_title;
	echo '<br /><br />[ <a href="'.adminlink('Surveys&amp;mode=edit&amp;id='.$poll_id).'">'._EDIT.'</a> | <a href="'.adminlink('Surveys&amp;mode=add').'">'._ADD.'</a> ]';
	echo '</div>';
	CloseTable();
	echo '<br />';
}

$np_text = $MAIN_CFG['notepad']['text'];
$np_lock = $MAIN_CFG['notepad']['lock'];
OpenTable();
echo '<div align="center">
<span class="genmed"><strong>'._NP_ADMIN.'</strong></span>
<br /><br />';

$np_status = $np_write = '';
if ($np_lock) {
	$np_status = ' readonly="readonly"';
	$np_write = ' disabled="disabled"';
	echo '<div style="color: #ff0000"><strong>'._NP_LOCKED.'</strong></div><br />';
}

echo '<form action="'.adminlink().'" method="post" accept-charset="utf-8">
<textarea name="np_notes" rows="9" cols="50"'.$np_status.'>'.$np_text.'</textarea><br /><br />
<input type="submit" name="np_save" value="'._NP_SAVE.'"'.$np_write.' />';

if (can_admin()) {
	echo '&nbsp;&nbsp;';
	if ($np_lock) {
		echo '<input type="submit" name="np_unlock" value="'._NP_UNLOCK.'" />';
	} else {
		echo '<input type="submit" name="np_lock" value="'._NP_LOCK.'" />';
	}
}

echo '</form></div>';
CloseTable();
