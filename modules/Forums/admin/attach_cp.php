<?php
/***************************************************************************
 *							  admin_attach_cp.php
 *							  -------------------
 *	  begin				   : Saturday, Feb 09, 2002
 *	  copyright			   : (C) 2002 Meik Sievertsen
 *	  email				   : acyd.burn@gmx.de
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

if (!defined('ADMIN_PAGES')) { exit; }

//
// Build SQL-Statement for the search feature
//
function get_search_attachments_filter($order_by, &$total_rows)
{
	$db = \Dragonfly::getKernel()->SQL;

	$where_sql = array();

	//
	// Get submitted Vars
	//
	$search_vars = array('search_keyword_fname', 'search_keyword_comment', 'search_author', 'search_size_smaller', 'search_size_greater', 'search_count_smaller', 'search_count_greater', 'search_days_greater', 'search_forum', 'search_cat');
	foreach ($search_vars as $k) {
		$$k = trim($_POST->raw($k) ?: $_GET->raw($k));
	}

	//
	// Author name search
	//
	if ($search_author) {
		//
		// We need the post_id's, because we want to query the Attachment Table
		//
		$result = $db->query('SELECT user_id FROM ' . $db->TBL->users . ' WHERE username LIKE ' . str_replace('*', '%', $db->quote($search_author)));
		$matching_userids = array();
		while ($row = $result->fetch_row()) {
			$matching_userids[] = $row[0];
		}
		if (!$matching_userids) {
			message_die(GENERAL_MESSAGE, \Dragonfly::getKernel()->L10N['No_attach_search_match']);
		}
		$where_sql[] = ' (a.user_id_1 IN (' . implode(',',$matching_userids) . ')) ';
	}

	//
	// Search Keyword
	//
	if ($search_keyword_fname) {
		$where_sql[] = ' (u.upload_name LIKE ' . str_replace('*', '%', $db->quote($search_keyword_fname)) . ') ';
	}

	if ($search_keyword_comment) {
		$where_sql[] = ' (a.comment LIKE ' . str_replace('*', '%', $db->quote($search_keyword_comment)) . ') ';
	}

	//
	// Search Download Count
	//
	if ($search_count_smaller) {
		$where_sql[] = ' (a.download_count < ' . intval($search_count_smaller) . ') ';
	} else if ($search_count_greater) {
		$where_sql[] = ' (a.download_count > ' . intval($search_count_greater) . ') ';
	}

	//
	// Search Filesize
	//
	if ($search_size_smaller) {
		$where_sql[] = ' (u.upload_size < ' . intval($search_size_smaller) . ') ';
	} else if ($search_size_greater) {
		$where_sql[] = ' (u.upload_size > ' . intval($search_size_greater) . ') ';
	}

	//
	// Search Attachment Time
	//
	if ($search_days_greater) {
		$where_sql[] = ' (u.upload_time < ' . (time() - ($search_days_greater * 86400)) . ') ';
	}

	return $where_sql ? 'WHERE ' . implode('AND', $where_sql) : '';
}

//
// Init Variables
//
$start = (int)$_GET->uint('start');

if (isset($_POST['order'])) {
	$sort_order = ($_POST['order'] == 'ASC') ? 'ASC' : 'DESC';
} else if (isset($_GET['order'])) {
	$sort_order = ($_GET['order'] == 'ASC') ? 'ASC' : 'DESC';
} else {
	$sort_order = '';
}

$mode = $_POST->txt('mode') ?: $_GET->txt('mode');

$view = $_POST->txt('view') ?: $_GET->txt('view');
if ('search' === $view && isset($_POST['search'])) {
	$view = 'attachments';
}

$uid = (int)$_GET->uint('uid');

//
// process modes based on view
//
if ('username' == $view) {
	$mode_types = array(
		'username' => $lang['Username'],
		'attachments' => $lang['Sort_Attachments'],
		'filesize' => $lang['Sort_Size']
	);
	if (empty($mode)) {
		$mode = 'attachments';
		$sort_order = 'DESC';
	}
}
else if ('attachments' == $view)
{
	$mode_types = array(
		'real_filename' => $lang['Sort_Filename'],
		'comment' => $lang['Sort_Comment'],
		'extension' => $lang['Sort_Extension'],
		'filesize' => $lang['Sort_Size'],
		'downloads' => $lang['Sort_Downloads'],
		'post_time' => $lang['Sort_Posttime']
	);
	if (empty($mode)) {
		$mode = 'real_filename';
		$sort_order = 'ASC';
	}
}
else if ('search' == $view)
{
	$mode_types = array(
		'real_filename' => $lang['Sort_Filename'],
		'comment' => $lang['Sort_Comment'],
		'extension' => $lang['Sort_Extension'],
		'filesize' => $lang['Sort_Size'],
		'downloads' => $lang['Sort_Downloads'],
		'post_time' => $lang['Sort_Posttime']
	);
	$sort_order = 'DESC';
}
else
{
	$view = 'stats';
	$mode_types = array();
	$sort_order = '';
}

//
// Set Order
//
$order_by = '';

if ('username' == $view)
{
	switch ($mode)
	{
		case 'username':
			$order_by = 'u.username';
			break;
		case 'attachments':
			$order_by = 'total_attachments';
			break;
		case 'filesize':
			$order_by = 'total_size';
			break;
		default:
			$mode = 'attachments';
			$sort_order = 'DESC';
			$order_by = 'total_attachments';
			break;
	}
}
else if ('attachments' == $view)
{
	switch ($mode)
	{
		case 'filename':
			$order_by = 'u.upload_name';
			break;
		case 'comment':
			$order_by = 'a.comment';
			break;
		case 'extension':
			$order_by = 'a.extension';
			break;
		case 'filesize':
			$order_by = 'u.upload_size';
			break;
		case 'downloads':
			$order_by = 'a.download_count';
			break;
		case 'post_time':
			$order_by = 'u.upload_time';
			break;
		default:
			$mode = 'filename';
			$sort_order = 'ASC';
			$order_by = 'u.upload_name';
			break;
	}
}
if ($order_by) {
	$order_by =  " ORDER BY {$order_by} {$sort_order} LIMIT {$board_config['topics_per_page']} OFFSET {$start}";
}

//
// Set select fields
//

if ($mode_types)
{
	$attach_sort_fields = array();
	foreach ($mode_types as $k => $v) {
		$attach_sort_fields[] = array(
			'value' => $k,
			'label' => $v,
			'current' => ($k == $mode),
		);
	}
}

$delete_id_list = isset($_POST['delete_id_list']) ? $_POST['delete_id_list'] : array();

$confirm = isset($_POST['confirm']);

if ($confirm && $delete_id_list) {
	\Dragonfly\Forums\Attachments::delete($delete_id_list);
} else if (isset($_POST['delete']) && $delete_id_list) {
	//
	// Not confirmed, show confirmation message
	//
	$hidden_fields = array(
		array('name'=>'view','value'=>$view),
		array('name'=>'mode','value'=>$mode),
		array('name'=>'order','value'=>$sort_order),
		array('name'=>'uid','value'=>$uid),
		array('name'=>'start','value'=>$start),
	);
	$delete_id_list = array_map('intval', $delete_id_list);
	foreach ($delete_id_list as $id) {
		$hidden_fields[] = array('name'=>'delete_id_list[]','value'=>$id);
	}
	\Dragonfly\Page::confirm(URL::admin('&do=attach_cp'), $lang['Confirm_delete_attachments'], $hidden_fields);
	return;
}

//
// Assign Default Template Vars
//
$template->assign_vars(array(
	'U_VIEW_STATS' => URL::admin('&do=attach_cp'),
	'U_VIEW_SEARCH' => URL::admin('&do=attach_cp&view=search'),
	'U_VIEW_USERNAME' => URL::admin('&do=attach_cp&view=username'),
	'U_VIEW_ATTACHMENTS' => URL::admin('&do=attach_cp&view=attachments'),
));

if ('stats' == $view)
{
	$template->set_handle('body', 'Forums/admin/attach_cp_stats');

	list($number_of_attachments) = $db->uFetchRow('SELECT COUNT(*) FROM ' . ATTACHMENTS_DESC_TABLE);

	list($number_of_posts, $number_of_users, $number_of_topics) = $db->uFetchRow('SELECT
		COUNT(DISTINCT post_id),
		COUNT(DISTINCT user_id_1),
		COUNT(DISTINCT p.topic_id) + COUNT(DISTINCT pa.topic_id)
	FROM ' . ATTACHMENTS_TABLE . ' a
	LEFT JOIN ' . POSTS_TABLE . ' p USING (post_id)
	LEFT JOIN ' . POSTS_ARCHIVE_TABLE . ' pa USING (post_id)
	WHERE post_id > 0');

	$size = 0;
	if (is_dir($attach_config['upload_dir'])) {
		foreach (glob($attach_config['upload_dir'].'/*') as $file) {
			$bn = basename($file);
			if (is_file($file) && $bn != 'index.php' && $bn != '.htaccess') {
				$size += filesize($file);
			}
		}
	}

	$template->assign_vars(array(
		'TOTAL_FILESIZE' => $size ? $template->L10N->filesizeToHuman($size) : $template->L10N['Not_available'],
		'NUMBER_OF_ATTACHMENTS' => $number_of_attachments,
		'NUMBER_OF_POSTS' => $number_of_posts,
		'NUMBER_OF_TOPICS' => $number_of_topics,
		'NUMBER_OF_USERS' => $number_of_users
	));
}

else if ('search' == $view)
{
	$result = $db->query("SELECT
		forum_id id,
		forum_name name
	FROM " . FORUMS_TABLE . "
	ORDER BY forum_name");
	if  (!$result->num_rows) {
		message_die(GENERAL_MESSAGE, $lang['No_searchable_forums']);
	}

	$template->set_handle('body', 'Forums/admin/attach_cp_search');

	$template->assign_vars(array(
		'search_forums' => $result,
		'search_categories' => $db->query("SELECT
				cat_id id,
				cat_title title
			FROM " . CATEGORIES_TABLE . "
			ORDER BY cat_title"),
		'attach_sort_fields' => $attach_sort_fields,
		'attach_sort_desc' => ($sort_order != 'ASC')
	));
}

else if ('username' == $view)
{
	$template->set_handle('body', 'Forums/admin/attach_cp_user');

	//
	// Get all Users with their respective total attachments amount
	//
	$members = $db->query("SELECT
		u.username,
		COUNT(attach_id) as total_attachments,
		ROUND(SUM(up.upload_size)/1024) as total_size,
		'".URL::admin('&do=attach_cp&view=attachments&uid=')."' || a.user_id_1 as u_view_member
	FROM " . ATTACHMENTS_TABLE . " a
	INNER JOIN {$db->TBL->users} u ON (u.user_id = a.user_id_1)
	INNER JOIN " . ATTACHMENTS_DESC_TABLE . " d USING (attach_id)
	LEFT JOIN {$db->TBL->users_uploads} up USING (upload_id)
	GROUP BY a.user_id_1, u.username " . $order_by);

	$template->assign_vars(array(
		'attach_sort_fields' => $attach_sort_fields,
		'attach_sort_desc' => ($sort_order != 'ASC'),
		'memberrow' => $members,
	));

	list($total_rows) = $db->uFetchRow("SELECT
		COUNT(DISTINCT user_id_1)
	FROM " . ATTACHMENTS_TABLE . " a
	INNER JOIN {$db->TBL->users} u ON (u.user_id = a.user_id_1)");
}

else if ('attachments' == $view)
{
	if (isset($_POST['submit_change'])) {
		if (isset($_POST['attach_list']) && is_array($_POST['attach_list'])) {
			$result = $db->query("SELECT attach_id, comment, download_count FROM " . ATTACHMENTS_DESC_TABLE
			. " WHERE attach_id IN (" . implode(',', array_keys($_POST['attach_list'])) . ")");
			while ($a = $result->fetch_assoc()) {
				if (isset($_POST['attach_list'][$a['attach_id']])) {
					$attachment = $_POST['attach_list'][$a['attach_id']];
					if ($a['comment'] != $attachment['comment']
					 || $a['download_count'] != $attachment['download_count']
					) {
						$db->query("UPDATE " . ATTACHMENTS_DESC_TABLE . " SET
							comment = " . $db->quote($attachment['comment']) . ",
							download_count = " . (int)$attachment['download_count'] . "
						WHERE attach_id = " . $a['attach_id']);
					}
				}
			}
		}
	}

	$template->set_handle('body', 'Forums/admin/attach_cp_attachments');
	$template->attach_sort_fields = $attach_sort_fields;
	$template->attach_sort_desc   = ($sort_order != 'ASC');
	$template->STATISTICS_FOR_USER = '';

	$total_rows = 0;
	$where = '';

	if (isset($_POST['search'])) {
		//
		// we are called from search
		//
		$where = get_search_attachments_filter();

		list($total_rows) = $db->uFetchRow("SELECT
			COUNT(*)
		FROM ".ATTACHMENTS_TABLE." a
		INNER JOIN ".ATTACHMENTS_DESC_TABLE." d USING (attach_id)
		INNER JOIN {$db->TBL->users_uploads} u USING (upload_id)
		{$where}");
		if (!$total_rows) {
			message_die(GENERAL_MESSAGE, $template->L10N['No_attach_search_match']);
		}
	}
	else if ($uid) {
		//
		// Are we called from Username ?
		//
		$where = "WHERE user_id_1 = {$uid}";

		list($username) = $db->uFetchRow("SELECT username FROM {$db->TBL->users} WHERE user_id = " . $uid);
		list($total_rows) = $db->uFetchRow("SELECT COUNT(DISTINCT attach_id) FROM " . ATTACHMENTS_TABLE . " {$where}");
		if (!$total_rows) {
			message_die(GENERAL_MESSAGE, 'For some reason no Attachments are assigned to the User "' . $username . '".');
		}
		$template->STATISTICS_FOR_USER = sprintf($lang['Statistics_for_user'], $username);
	}
	else {
		list($total_rows) = $db->uFetchRow("SELECT COUNT(*) FROM " . ATTACHMENTS_DESC_TABLE);
	}

	$attachments = $db->query("SELECT
		d.attach_id,
		d.download_count,
		d.comment,
		d.extension,
		u.upload_name as real_filename,
		u.upload_size as filesize,
		u.upload_time as filetime,
		a.post_id,
		t.topic_title
	FROM ".ATTACHMENTS_TABLE." a
	INNER JOIN ".ATTACHMENTS_DESC_TABLE." d USING (attach_id)
	INNER JOIN {$db->TBL->users_uploads} u USING (upload_id)
	LEFT JOIN ".POSTS_TABLE." p ON (p.post_id = a.post_id)
	LEFT JOIN ".POSTS_ARCHIVE_TABLE." pa ON (pa.post_id = a.post_id)
	LEFT JOIN ".TOPICS_TABLE." t ON (t.topic_id = COALESCE(pa.topic_id, p.topic_id))
	{$where}
	{$order_by}");

	foreach ($attachments as $attachment) {
		$view_topic = URL::index('&file=viewtopic&p=' . $attachment['post_id']) . '#' . $attachment['post_id'];
		$template->assign_block_vars('attachrow', array(
			'ID'                => $attachment['attach_id'],
			'FILENAME'          => $attachment['real_filename'],
			'COMMENT'           => $attachment['comment'],
			'EXTENSION'         => $attachment['extension'],
			'SIZE'              => round($attachment['filesize'] / 1024),
			'DOWNLOAD_COUNT'    => $attachment['download_count'],
			'POST_TIME'         => $lang->date($board_config['default_dateformat'], $attachment['filetime']),
			'post_url'          => $view_topic,
			'topic_title'       => $attachment['topic_title'],
			'DELETE_CHECKED'    => in_array($attachment['attach_id'], $delete_id_list),
			'U_VIEW_ATTACHMENT' => URL::index('&file=download&id=' . $attachment['attach_id'])
		));
	}
}

//
// Generate Pagination
//
if ($view != 'stats' && $view != 'search' && $total_rows > $board_config['topics_per_page']) {
	$template->PAGINATION  = generate_admin_pagination("do=attach_cp&view={$view}&mode={$mode}&order={$sort_order}&uid={$uid}", $total_rows, $board_config['topics_per_page'], $start);
	$template->PAGE_NUMBER = sprintf($lang['Page_of'], ( floor( $start / $board_config['topics_per_page'] ) + 1 ), ceil( $total_rows / $board_config['topics_per_page'] ));
} else {
	$template->PAGINATION = '';
}
