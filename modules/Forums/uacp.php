<?php
/***************************************************************************
 *								   uacp.php
 *							  -------------------
 *	 begin				  : Oct 30, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 ***************************************************************************/

/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/
 
/* Applied rules:
 * TernaryToNullCoalescingRector
 * ParenthesizeNestedTernaryRector (https://www.php.net/manual/en/migration74.deprecated.php)
 */

/**
 * User Attachment Control Panel
 *
 * From this 'Control Panel' the user is able to view/delete his Attachments.
 */

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

//
// Obtain initial var settings
//
$user_id = ($_POST->int('u') ?: $_GET->int('u')) ?: \Dragonfly::getKernel()->IDENTITY->id;
if ($user_id <= \Dragonfly\Identity::ANONYMOUS_ID) {
	$profiledata = array(
		'user_id' => \Dragonfly\Identity::ANONYMOUS_ID,
		'username' => $lang['Guest']
	);
} else {
	$profiledata = getusrdata($user_id);
	if (!$profiledata) {
		message_die(GENERAL_MESSAGE, $lang['No_user_id_specified']);
	}
}

if ($profiledata['user_id'] != $userinfo['user_id'] && !can_admin($module_name)) {
	message_die(GENERAL_MESSAGE, $lang['Not_Authorised']);
}

\Dragonfly\Page::title($lang['User_acp_title']);

$start = (int)$_GET->uint('start');

if (isset($_POST['order'])) {
	$sort_order = ($_POST['order'] == 'DESC') ? 'DESC' : 'ASC';
} else if (isset($_GET['order'])) {
	$sort_order = ($_GET['order'] == 'DESC') ? 'DESC' : 'ASC';
} else {
	$sort_order = 'ASC';
}

$mode = $_POST->txt('mode') ?: $_GET->txt('mode');

$order_by = '';
switch ($mode)
{
	case 'comment':
		$order_by = 'ORDER BY a.comment '.$sort_order;
		break;
	case 'extension':
		$order_by = 'ORDER BY a.extension '.$sort_order;
		break;
	case 'filesize':
		$order_by = 'ORDER BY u.upload_size '.$sort_order;
		break;
	case 'downloads':
		$order_by = 'ORDER BY a.download_count '.$sort_order;
		break;
	case 'post_time':
		$order_by = 'ORDER BY u.upload_time '.$sort_order;
		break;
	case 'filename':
	default:
		$mode = 'filename';
		$order_by = 'ORDER BY u.upload_name '.$sort_order;
		break;
}

$delete_id_list = $_POST['delete_id_list'] ?? array();
if ($delete_id_list) {
	if (isset($_POST['confirm'])) {
		\Dragonfly\Forums\Attachments::delete($delete_id_list);
	} else if (isset($_POST['delete'])) {
		// Not confirmed, show confirmation message
		$hidden_fields = array(
			array('name'=>'mode','value'=>$mode),
			array('name'=>'order','value'=>$sort_order),
			array('name'=>'start','value'=>$start),
		);
		$delete_id_list = array_map('intval', $delete_id_list);
		foreach ($delete_id_list as $id) {
			$hidden_fields[] = array('name'=>'delete_id_list[]','value'=>$id);
		}
		\Dragonfly\Page::confirm(URL::index('&file=uacp'), $lang['Confirm_delete_attachments'], $hidden_fields);
		exit;
	}
}

//
// Assign Template Vars
//
$mode_types = array(
	array('value' => 'filename', 'label' => $lang['Sort_Filename'], 'selected' => ('filename' == $mode)),
	array('value' => 'comment', 'label' => $lang['Sort_Comment'], 'selected' => ('comment' == $mode)),
	array('value' => 'extension', 'label' => $lang['Sort_Extension'], 'selected' => ('extension' == $mode)),
	array('value' => 'filesize', 'label' => $lang['Sort_Size'], 'selected' => ('filesize' == $mode)),
	array('value' => 'downloads', 'label' => $lang['Sort_Downloads'], 'selected' => ('downloads' == $mode)),
	array('value' => 'post_time', 'label' => $lang['Sort_Posttime'], 'selected' => ('post_time' == $mode))
);

$template->USERNAME = $profiledata['username'];
$template->uacp_sort_asc = ('ASC' == $sort_order);
$template->uacp_sort_modes = $mode_types;

list($total_rows) = $db->uFetchRow("SELECT COUNT(attach_id) FROM ".ATTACHMENTS_TABLE." WHERE user_id_1 = {$profiledata['user_id']}");

if ($total_rows) {
	$result = $db->query("SELECT
		d.attach_id,
		d.download_count,
		d.comment,
		d.extension,
		u.upload_name,
		u.upload_size,
		u.upload_time,
		a.post_id,
		t.topic_title
	FROM ".ATTACHMENTS_TABLE." a
	INNER JOIN ".ATTACHMENTS_DESC_TABLE." d USING (attach_id)
	LEFT JOIN {$db->TBL->users_uploads} u USING (upload_id)
	LEFT JOIN ".POSTS_TABLE." p ON (p.post_id = a.post_id)
	LEFT JOIN ".POSTS_ARCHIVE_TABLE." pa ON (pa.post_id = a.post_id)
	LEFT JOIN ".TOPICS_TABLE." t ON (t.topic_id = COALESCE(pa.topic_id, p.topic_id))
	WHERE a.user_id_1 = {$profiledata['user_id']}
	{$order_by}
	LIMIT {$board_config['topics_per_page']} OFFSET {$start}");
	$i = $start;
	while ($attachment = $result->fetch_assoc())
	{
		$view_topic = URL::index('&file=viewtopic&p='.$attachment['post_id']).'#'.$attachment['post_id'];
		$attachment += array(
			'SIZE' => $lang->filesizeToHuman($attachment['upload_size']),
			'POST_TIME' => $lang->date($board_config['default_dateformat'], $attachment['upload_time']),
			'U_VIEW_ATTACHMENT' => URL::index('Forums&file=download&id='.$attachment['attach_id']),
			'U_VIEW_POST' => $attachment['post_id'] ? $view_topic : '',
		);
		$template->assign_block_vars('attachrow', $attachment);
	}
}

//
// Generate Pagination
//
if ($total_rows > $board_config['topics_per_page']) {
	$pagination = generate_pagination('&file=uacp&mode='.$mode.'&order='.$sort_order.'&u='.$profiledata['user_id'], $total_rows, $board_config['topics_per_page'], $start).'&nbsp;';
	$template->assign_vars(array(
		'PAGINATION' => $pagination,
		'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $board_config['topics_per_page'] ) + 1 ), ceil( $total_rows / $board_config['topics_per_page'] )),
	));
} else {
	$template->assign_vars(array(
		'PAGINATION' => '',
		'PAGE_NUMBER' => ''
	));
}

require_once("includes/phpBB/page_header.php");
$template->display('forums/uacp');
