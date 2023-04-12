<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/functions/messagebox.php,v $
  $Revision: 9.11 $
  $Author: phoenix $
  $Date: 2007/10/18 14:38:06 $
**********************************************/

function message_box() {
	global $prefix, $multilingual, $currentlang, $db, $userinfo;
	
	require_once(CORE_PATH.'nbbcode.php');
	
	$query = ($multilingual) ? "AND (mlanguage='$currentlang' OR mlanguage='')" : '';
	if (!is_admin()) {
		if (is_user()) { $query .= ' AND view!=2 AND view!=3'; }
		else { $query .= ' AND (view=0 OR view=3)'; }
	}
	$result = $db->sql_query('SELECT mid, title, content, date, expire, view FROM '.$prefix."_message WHERE active='1' $query ORDER BY date DESC");
	while (list($mid, $title, $content, $date, $expire, $view) = $db->sql_fetchrow($result)) {
		$content = decode_bb_all($content, 1, true);
		if (!empty($title) && !empty($content)) {
			$output = '';
			if ($view == 0) {
				$output = _MVIEWALL;
			} elseif ($view == 1) {
				$output = _MVIEWUSERS;
			} elseif ($view == 2) {
				$output = _MVIEWADMIN;
			} elseif ($view == 3) {
				$output = _MVIEWANON;
			} elseif ($view > 3 && (in_group($view - 3) || is_admin())) {	// <= phpBB User Groups Integration
				$view = $view - 3;
				if (!in_group($view)) list($output) = $db->sql_ufetchrow("SELECT group_name FROM ".$prefix."_bbgroups WHERE group_id='$view'", SQL_NUM);
				else $output = in_group($view);
			}
			if ($output != '') {
				$remain = '';
				if (can_admin()) {
					if ($expire == 0) {
						$remain = _UNLIMITED;
					} else {
						$etime = (($date+$expire)-gmtime())/3600;
						$etime = intval($etime);
						$remain = ($etime < 1) ? _EXPIRELESSHOUR : _EXPIREIN." $etime "._HOURS;
					}
				}
				global $cpgtpl;
				$cpgtpl->assign_block_vars('messageblock', array(
					'S_TITLE'   => $title,
					'S_CONTENT' => $content,
					'S_OUTPUT'  => $output,
					'S_DATE'    => _POSTEDON.' '.formatDateTime($date, _DATESTRING2),
					'S_REMAIN'  => $remain,
					'S_EDIT'    => _EDIT,
					'U_EDITMSG' => adminlink('messages&amp;edit='.$mid)
				));
			}
			if ($expire != 0) {
				if ($date+$expire < gmtime()) {
					$db->sql_query("UPDATE ".$prefix."_message SET active='0' WHERE mid='$mid'");
				}
			}
		}
	}
	$db->sql_freeresult($result);
}
