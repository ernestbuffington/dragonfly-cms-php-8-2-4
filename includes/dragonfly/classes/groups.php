<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/

namespace Dragonfly;

class Groups
{

	const
		TYPE_OPEN   = 0,
		TYPE_CLOSED = 1,
		TYPE_HIDDEN = 2;

	public static function getAll()
	{
		static $groups = array();
		if (!$groups) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$qr = $SQL->query("SELECT group_id, group_name FROM {$SQL->TBL->bbgroups} WHERE group_single_user=0 ORDER BY group_name");
			while ($r = $qr->fetch_row()) {
				$groups[$r[0]] = array('id' => $r[0], 'label' => $r[1]);
			}
		}
		return $groups;
	}

	public static function getSystem($mvanon=false, $all=true)
	{
		static $groups = array();
		if (!$groups) {
			$K = \Dragonfly::getKernel();
			$groups = array(
				array(
					'label' => $K->L10N->get('System'),
					'groups' => array(
						array('id' => 0, 'label' => _MVALL),
						array('id' => 1, 'label' => _MVUSERS),
						array('id' => 2, 'label' => _MVADMIN),
						array('id' => 3, 'label' => _MVANON),
					)
				),
				array(
					'label' => $K->L10N->get('Groups'),
					'groups' => array()
				)
			);
			$qr = $K->SQL->query("SELECT group_id, group_name FROM {$K->SQL->TBL->bbgroups} WHERE group_single_user=0 ORDER BY group_name");
			while ($r = $qr->fetch_row()) {
				$groups[1]['groups'][$r[0]+3] = array('id' => $r[0]+3, 'label' => $r[1]);
			}
		}
		$tmpgroups = $groups;
		if (!$all) { unset($tmpgroups[0]['groups'][0]); }
		if (!$mvanon) { unset($tmpgroups[0]['groups'][3]); }
		return $tmpgroups;
	}

	// Create a private user group for the user_id
	public static function createPrivate($user_id)
	{
		$TBL = \Dragonfly::getKernel()->SQL->TBL;
		$group_id = $TBL->bbgroups->insert(array(
			'group_name' => '',
			'group_description' => 'Personal User',
			'group_single_user' => 1,
			'group_moderator' => 0
		), 'group_id');
		$TBL->bbuser_group->insert(array(
			'user_id' => $user_id,
			'group_id' => $group_id,
			'user_pending' => 0
		));
		return $group_id;
	}

}
