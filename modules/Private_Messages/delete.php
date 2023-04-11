<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

if (!class_exists('Dragonfly', false)) { exit; }

use Dragonfly\Modules\Private_Messages\Message as Message;

if (empty($mark_list) || !is_array($mark_list)) {
	$mark_list = array();
}

if (!isset($_POST['confirm'])) {
	$s_hidden_fields = array(array('name'=>'do_with_marked','value'=>'delete'));
	foreach ($mark_list as $i) {
		$s_hidden_fields[] = array('name'=>'mark[]','value'=>$i);
	}
	\Dragonfly\Page::title($LNG['Confirm_delete_pm'], false);
	\Dragonfly\Page::confirm(URL::index('&folder='.$folder),
				   ((count($mark_list) == 1) ? $LNG['Confirm_delete_pm'] : $LNG['Confirm_delete_pms']),
				   $s_hidden_fields);
	return;
}

else if ($mark_list) {
	$pm_ids = implode(',', array_map('intval', $mark_list));

	if ('outbox' == $folder) {
		// Get information relevant to new or unread mail
		// so we can adjust users counters appropriately
		$qr = $db->query("SELECT
			pm.pm_id,
			pmr.user_id
		FROM {$db->TBL->privatemessages} pm
		INNER JOIN {$db->TBL->privatemessages_recipients} pmr ON (pmr.pm_id = pm.pm_id AND pmr_status = ".Message::STATUS_NEW.")
		WHERE pm.pm_id IN ({$pm_ids})
		  AND pm.user_id = {$userinfo->id}
		  AND pm.pm_status = ".Message::STATUS_NEW);
		$pm_ids = $user_ids = array();
		while ($r = $qr->fetch_row()) {
			$pm_ids[$r[0]] = $r[0];
			$user_ids[] = $r[1];
		}
		if ($pm_ids) {
			$pm_ids = implode(',', $pm_ids);
			$db->query("DELETE FROM {$db->TBL->privatemessages_recipients} WHERE pm_id IN ({$pm_ids}) AND pmr_status = ".Message::STATUS_NEW);
			$db->query("DELETE FROM {$db->TBL->privatemessages} WHERE pm_id IN ({$pm_ids}) AND pm_status = ".Message::STATUS_NEW);
			if ($user_ids) {
				$user_ids = implode(',', $user_ids);
				$db->query("UPDATE {$db->TBL->users} u SET
					user_new_privmsg = (SELECT COUNT(*) FROM {$db->TBL->privatemessages_recipients} WHERE user_id = u.user_id AND pmr_status = ".Message::STATUS_NEW.")
				WHERE user_id IN ({$user_ids})");
			}
		}
	} else {
		// Update status
		$db->TBL->privatemessages->update(array(
			'pm_status' => Message::STATUS_DELETED
		), "pm_id IN ({$pm_ids}) AND user_id = {$userinfo->id}");
		$db->TBL->privatemessages_recipients->update(array(
			'pmr_status' => Message::STATUS_DELETED
		), "pm_id IN ({$pm_ids}) AND user_id = {$userinfo->id}");

		// Delete the messages when everyone has STATUS_DELETED
		$qr = $db->query("SELECT pm_id
			FROM {$db->TBL->privatemessages} pm
			INNER JOIN {$db->TBL->privatemessages_recipients} USING (pm_id)
			WHERE pm_id IN ({$pm_ids}) AND pm_status = ".Message::STATUS_DELETED."
			GROUP BY pm_id
			HAVING MIN(pmr_status) = ".Message::STATUS_DELETED);
		$pm_ids = array();
		while ($r = $qr->fetch_row()) $pm_ids[] = $r[0];
		if ($pm_ids) {
			$pm_ids = implode(',', $pm_ids);
			$db->TBL->privatemessages->delete("pm_id IN ({$pm_ids})");
			$db->TBL->privatemessages_recipients->delete("pm_id IN ({$pm_ids})");
		}
	}
}

URL::redirect(URL::index("&folder={$folder}"));
