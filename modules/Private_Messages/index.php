<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }

require_once(__DIR__ . '/init.inc');

use Dragonfly\Modules\Private_Messages\Message as Message;

if ('post' == $mode) {
	require_once(__DIR__ . '/compose.php');
	return;
}

if ('inbox' == $folder || 'sentbox' == $folder) {
	if (isset($_POST['save'])) {
		$mark_list = array($_GET->uint('p'));
		require_once(__DIR__ . '/save.php');
		return;
	}
	if ('save' === $_POST->txt('do_with_marked')) {
		$mark_list = $_POST['mark'];
		require_once(__DIR__ . '/save.php');
		return;
	}
}

if (isset($_POST['delete'])) {
	$mark_list = array($_GET->uint('p'));
	require_once(__DIR__ . '/delete.php');
	return;
}
if ('delete' === $_POST->txt('do_with_marked')) {
	$mark_list = $_POST['mark'];
	require_once(__DIR__ . '/delete.php');
	return;
}

if ('read' == $mode) {
	require_once(__DIR__ . '/read.php');
	return;
}

$offset = (int) $_GET->uint('offset');
$limit  = (int) $PMCFG->per_page;

$LNG = \Dragonfly::getKernel()->L10N;

# Show messages over previous x days/months
$limit_msg_time = '';
if ($msg_days = $_POST->uint('msgdays')) {
	$min_msg_time = time() - ($msg_days * 86400);
	$limit_msg_time = " AND pm_date > {$min_msg_time}";
/*
	if (!empty($_POST['msgdays'])) {
		$offset = 0;
	}
*/
}

$pm_total = 0;

# General SQL to obtain messages
$sql = "SELECT
		pm_id       id,
		pm_date     ctime,
		pm_subject  subject,
		pmr.user_id recipient,
		pmr_status  status,
		u.user_id,
		u.username
	FROM {$db->TBL->privatemessages} pm
	INNER JOIN {$db->TBL->privatemessages_recipients} pmr USING (pm_id)
	INNER JOIN {$db->TBL->users} u ";
switch ($folder)
{
	case 'inbox':
		# Reset PM new/unread counters
		$update_user = array();
		$userinfo->new_privmsg = 0;
		$qr = $db->query("SELECT pm_id FROM {$db->TBL->privatemessages_recipients} WHERE user_id = {$userinfo->id} AND pmr_status = ".Message::STATUS_NEW);
		if ($qr->num_rows) {
			$pm_ids = array();
			while ($r = $qr->fetch_row()) $pm_ids[] = $r[0];
			$db->exec("UPDATE {$db->TBL->privatemessages} SET pm_status = ".Message::STATUS_SENT." WHERE pm_id IN (".implode(',',$pm_ids).")");
			$db->exec("UPDATE {$db->TBL->privatemessages_recipients} SET pmr_status = ".Message::STATUS_UNREAD." WHERE user_id = {$userinfo->id} AND pmr_status = ".Message::STATUS_NEW);
			$update_user['user_new_privmsg'] = 0;
		}
		$unread_privmsg = $db->TBL->privatemessages_recipients->count("user_id = {$userinfo->id} AND pmr_status = ".Message::STATUS_UNREAD);
		if ($unread_privmsg != $userinfo->unread_privmsg) {
			$update_user['user_unread_privmsg'] = $unread_privmsg;
			$userinfo->unread_privmsg = $unread_privmsg;
		}
		if ($update_user) {
			$db->TBL->users->update($update_user, "user_id = {$userinfo->id}");
		}

		$pm_all_total = $db->TBL->privatemessages_recipients->count("user_id = {$userinfo->id} AND pmr_status IN (".Message::STATUS_NEW.', '.Message::STATUS_UNREAD.', '.Message::STATUS_READ.")");
		if ($pm_all_total) {
			if ($limit_msg_time) {
				list($pm_total_r) = $db->uFetchRow("SELECT COUNT(*)
				FROM {$db->TBL->privatemessages_recipients} pmr
				INNER JOIN {$db->TBL->privatemessages} pm ON (pm.pm_id = pmr.pm_id{$limit_msg_time})
				WHERE pmr.user_id = {$userinfo->id} AND pmr_status IN (".Message::STATUS_NEW.', '.Message::STATUS_UNREAD.', '.Message::STATUS_READ.")");
				$pm_total += $pm_total_r;
			} else {
				$pm_total = $db->TBL->privatemessages_recipients->count("user_id = {$userinfo->id} AND pmr_status IN (".Message::STATUS_NEW.', '.Message::STATUS_UNREAD.', '.Message::STATUS_READ.")".$limit_msg_time);
			}
		}
		$sql .= "ON (u.user_id = pm.user_id)
		WHERE pmr.user_id = {$userinfo->id}
		  AND pmr_status IN (".Message::STATUS_NEW.', '.Message::STATUS_UNREAD.', '.Message::STATUS_READ.")"
		  .$limit_msg_time;
		break;

	case 'outbox':
		$pm_all_total = $db->TBL->privatemessages->count("user_id = {$userinfo->id} AND pm_status = ".Message::STATUS_NEW);
		if ($pm_all_total) {
			$pm_total = $db->TBL->privatemessages->count("user_id = {$userinfo->id} AND pm_status = ".Message::STATUS_NEW.$limit_msg_time);
		}
		$sql_tot = "SELECT COUNT(pm_id) FROM {$db->TBL->privatemessages}
			WHERE user_id = {$userinfo->id}
			  AND pm_status = ".Message::STATUS_NEW;

		$sql .= "ON (u.user_id = pmr.user_id)
		WHERE pm.user_id = {$userinfo->id}
		  AND pm.pm_status = ".Message::STATUS_NEW
		  .$limit_msg_time;
		break;

	case 'sentbox':
		$pm_all_total = $db->TBL->privatemessages->count("user_id = {$userinfo->id} AND pm_status = ".Message::STATUS_SENT);
		if ($pm_all_total) {
			$pm_total = $db->TBL->privatemessages->count("user_id = {$userinfo->id} AND pm_status = ".Message::STATUS_SENT.$limit_msg_time);
		}
		$sql .= "ON (u.user_id = pmr.user_id)
		WHERE pm.user_id = {$userinfo->id}
		  AND pm.pm_status = ".Message::STATUS_SENT
		  .$limit_msg_time;
		break;

	case 'savebox':
		$pm_all_total = $db->TBL->privatemessages->count("user_id = {$userinfo->id} AND pm_status = ".Message::STATUS_SAVED);
		if ($pm_all_total) {
			$pm_total = $db->TBL->privatemessages->count("user_id = {$userinfo->id} AND pm_status = ".Message::STATUS_SAVED.$limit_msg_time);
		}
		$pm_all_total_r = $db->TBL->privatemessages_recipients->count("user_id = {$userinfo->id} AND pmr_status = ".Message::STATUS_SAVED);
		if ($pm_all_total_r) {
			$pm_all_total += $pm_all_total_r;
			if ($limit_msg_time) {
				list($pm_total_r) = $db->uFetchRow("SELECT COUNT(*)
				FROM {$db->TBL->privatemessages_recipients} pmr
				INNER JOIN {$db->TBL->privatemessages} pm ON (pm.pm_id = pmr.pm_id{$limit_msg_time})
				WHERE pmr.user_id = {$userinfo->id} AND pmr_status = ".Message::STATUS_SAVED);
				$pm_total += $pm_total_r;
			} else {
				$pm_total += $db->TBL->privatemessages_recipients->count("user_id = {$userinfo->id} AND pmr_status = ".Message::STATUS_SAVED.$limit_msg_time);
			}
		}
		$sql .= "ON (u.user_id = pm.user_id)";
		$sql .= "
			WHERE pm.user_id = {$userinfo->id} AND pm_status = ".Message::STATUS_SAVED.$limit_msg_time."
		UNION {$sql}
			WHERE pmr.user_id = {$userinfo->id} AND pmr_status = ".Message::STATUS_SAVED.$limit_msg_time;
		break;

	default:
		cpg_error($LNG['No_such_folder']);
		break;
}

$order_by = $_POST->txt('order_by');
$order_by = in_array($order_by, array('ctime', 'username', 'subject')) ? $order_by : 'ctime';
$order_dir = 'asc' === $_POST->txt('asc_desc')?'ASC':'DESC';

$max_limit = $PMCFG->{$folder.'_max'};
$template->folder = array(
	'name' => $folder,
	'max' => $max_limit,
	'total_pms' => $pm_all_total,
	'quota' => sprintf($LNG[$folder.'_size'], 0 < $max_limit ? round(($pm_all_total/$max_limit)*100) : 100),
);

$template->listrow = array();
$result = $db->query("{$sql} ORDER BY {$order_by} {$order_dir} LIMIT {$limit} OFFSET {$offset}");
if ($result->num_rows) {
	$template->msgdays = array(
		array('value' =>   0, 'current' =>   0==$msg_days, 'label' => $LNG['All_Messages']),
		array('value' =>   1, 'current' =>   1==$msg_days, 'label' => $LNG->timeReadable(86400, '%d')),
		array('value' =>   7, 'current' =>   7==$msg_days, 'label' => $LNG->timeReadable(604800, '%d')),
		array('value' =>  14, 'current' =>  14==$msg_days, 'label' => $LNG->timeReadable(604800*2, '%w')),
		array('value' =>  30, 'current' =>  30==$msg_days, 'label' => $LNG->timeReadable(2628000, '%m')),
		array('value' =>  90, 'current' =>  90==$msg_days, 'label' => $LNG->timeReadable(2628000*3, '%m')),
		array('value' => 180, 'current' => 180==$msg_days, 'label' => $LNG->timeReadable(2628000*6, '%m')),
		array('value' => 364, 'current' => 364==$msg_days, 'label' => $LNG->timeReadable(31536000, '%y')),
	);
	$template->order_by = array(
		array('value' => 'ctime', 'current' => 'ctime'==$order_by, 'label' => $LNG['Order_by_time']),
		array('value' => 'username', 'current' => 'username'==$order_by, 'label' => $LNG['Order_by_username']),
		array('value' => 'subject', 'current' => 'subject'==$order_by, 'label' => $LNG['Order_by_subject']),
	);
	$template->order_dir = $order_dir;

	while ($row = $result->fetch_assoc()) {
		$template->listrow[] = array_merge($row, array(
			'date' => $template->L10N->date($userinfo->dateformat, $row['ctime']),
			'unread' => ($row['status'] == Message::STATUS_UNREAD && $row['recipient'] == $userinfo->id),
			'U_READ' => URL::index('&folder='.$folder.'&mode=read&p='.$row['id']),
			'U_FROM_USER_PROFILE' => \Dragonfly\Identity::getProfileURL($row['user_id']),
		));
	}
}
$result->free();

$template->pmcfg = $PMCFG;
$template->pm_pagination = new \Poodle\Pagination(URL::index("&folder={$folder}&offset=\${offset}"), $pm_total, $offset, $limit);
$template->display('Private_Messages/index');
