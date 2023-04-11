<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }

if (isset($_POST['np_save'])) {
	$np_notes = Fix_Quotes(check_words($_POST['np_notes']));
	Dragonfly::getKernel()->CFG->set('notepad', 'text', $np_notes);
	URL::redirect(URL::admin());
}
if ((isset($_POST['np_lock']) || isset($_POST['np_unlock'])) && can_admin()) {
	Dragonfly::getKernel()->CFG->set('notepad', 'lock', intval(isset($_POST['np_lock'])));
	URL::redirect(URL::admin());
}

$update_monitor = false;
if (can_admin() && \Dragonfly::getKernel()->CFG->global->update_monitor) {
	$update_monitor = \Dragonfly::getKernel()->CACHE->get('Dragonfly/update_monitor');
	if (!$update_monitor) {
		$update_url = 'https://dragonflycms.org/update.php?vers='.\Dragonfly::VERSION; // getaddrinfo failed: Name or service not known
		$updinfo = \Poodle\HTTP\URLInfo::get($update_url, false, true);
		if ($updinfo) {
			$items = preg_split('#(<item>)#s', $updinfo['data'], -1, PREG_SPLIT_NO_EMPTY);
			unset($updinfo);
			$curvers = preg_replace('#^(.*)<version>(.*)</version>(.*)#s','\\2',$items[0], 1);
			$upgurl  = preg_replace('#^(.*)<url>(.*)</url>(.*)#s','\\2',$items[0], 1);
			unset($items[0]);
			$update_monitor = array('current'=>$curvers, 'url'=>$upgurl, 'num'=>count($items), 'msg'=>array());
			foreach ($items as $item) {
				if (!empty($item)) {
					$alrt_vers  = preg_replace('#(.*)<version>(.*)</version>(.*)#s','\\2',$item);
					$alrt_title = preg_replace('#(.*)<title>(.*)</title>(.*)#s','\\2',$item);
					$alrt_desc  = preg_replace('#(.*)<description>(.*)</description>(.*)#s','\\2',$item);
					$alrt_date  = preg_replace('#(.*)<date>(.*)</date>(.*)#s','\\2',$item);
					$update_monitor['msg'][] = array('vers'=>$alrt_vers, 'title'=>$alrt_title, 'desc'=>$alrt_desc, 'date'=>$alrt_date);
				}
			}
			$update_monitor['using_latest'] = version_compare(\Dragonfly::VERSION, $update_monitor['current'], '>=');
			$update_monitor['using_scm']    = version_compare(\Dragonfly::VERSION, $update_monitor['current'], '>');
		}

		$update_monitor['packages'] = count(\Dragonfly\PackageManager\Repositories::checkForUpdates());
		// Cache for 24 hours
		Dragonfly::getKernel()->CACHE->set('Dragonfly/update_monitor', $update_monitor, 86400);
	}

	if ($update_monitor['packages']) {
		\Poodle\Notify::info("There are {$update_monitor['packages']} package updates available! <a href='?admin&amp;op=packagemanager&amp;list'>Click here</a>");
	}
}

$TPL = \Dragonfly::getKernel()->OUT;
$TPL->CPG = array('update_monitor' => $update_monitor);

/*
 * Stats
 */

$result = $db->query('SELECT COUNT(*), guest FROM '.$db->TBL->session.' GROUP BY guest ORDER BY guest');
$online_num = array(0, 0, 0, 0);
while ($row = $result->fetch_row()) {
	$online_num[$row[1]] = intval($row[0]);
}
$result->free();
$TPL->online_num = $online_num;

$day = mktime(0,0,0);
$TPL->new_members = array(
	'today' => $db->count('users',"user_regdate>={$day}"),
	'yesterday' => $db->count('users',"user_regdate<{$day} AND user_regdate>=".($day-86400)),
);

$TPL->display('admin/index');
