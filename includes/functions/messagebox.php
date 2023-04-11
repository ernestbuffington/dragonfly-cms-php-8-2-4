<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }

message_box();

function message_box()
{
	global $home;
	$K = \Dragonfly::getKernel();
	$K->OUT->messageblock = array();
	if (!$home) return;

	$query = ($K->L10N->multilingual) ? "AND (mlanguage='{$K->L10N->lng}' OR mlanguage='')" : '';
	if (!is_admin()) {
		if (is_user()) { $query .= ' AND view!=2 AND view!=3'; }
		else { $query .= ' AND (view=0 OR view=3)'; }
	}
	$result = $K->SQL->query("SELECT mid, title, content, date, expire, view FROM {$K->SQL->TBL->message} WHERE active=1 {$query} ORDER BY date DESC");
	while (list($mid, $title, $content, $date, $expire, $view) = $result->fetch_row()) {
		$content = \Dragonfly\BBCode::decodeAll($content, 1, true);
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
				if (!in_group($view)) list($output) = $K->SQL->uFetchRow("SELECT group_name FROM {$K->SQL->TBL->bbgroups} WHERE group_id={$view}");
				else $output = in_group($view);
			}
			if ($output != '') {
				$remain = '';
				if (can_admin()) {
					if ($expire == 0) {
						$remain = _UNLIMITED;
					} else {
						$etime = (($date+$expire)-time())/3600;
						$etime = intval($etime);
						$remain = ($etime < 1) ? _EXPIRELESSHOUR : _EXPIREIN." $etime "._HOURS;
					}
				}
				$K->OUT->messageblock[] = array(
					'S_TITLE'   => $title,
					'S_CONTENT' => $content,
					'S_OUTPUT'  => $output,
					'S_DATE'    => _POSTEDON.' '.Dragonfly::getKernel()->L10N->date('DATE_S', $date),
					'S_REMAIN'  => $remain,
					'S_EDIT'    => _EDIT,
					'U_EDITMSG' => URL::admin('messages&edit='.$mid)
				);
			}
			if ($expire != 0 && $date+$expire < time()) {
				$K->SQL->query("UPDATE {$K->SQL->TBL->message} SET active=0 WHERE mid={$mid}");
			}
		}
	}
	$result->free();
}
