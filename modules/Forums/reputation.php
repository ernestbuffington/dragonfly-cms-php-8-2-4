<?php
/***************************************************************************
 *								index.php
 *							-------------------
 *	 begin				: Saturday, Feb 13, 2001
 *	 copyright			: (C) 2001 The phpBB Group
 *	 email				: support@phpbb.com
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

if (!defined('IN_PHPBB')) { define('IN_PHPBB', true); }
require_once(__DIR__ . '/common.php');

$post_id = (int)$_GET->uint('p');

$post_info = $db->uFetchRow("SELECT
	poster_id,
	post_reputation
FROM ".POSTS_TABLE." p
LEFT JOIN ".POSTS_REPUTATIONS_TABLE." r ON (r.post_id = p.post_id AND r.user_id = {$userinfo->id})
WHERE p.post_id = {$post_id}");
if (!$post_info || !$userinfo->isMember()) {
	\Poodle\HTTP\Status::set(404);
	cpg_error(\Dragonfly::getKernel()->L10N->get('Topic_post_not_exist'));
}
if ($post_info[0] == $userinfo->id) {
	\Poodle\HTTP\Status::set(403);
	cpg_error('You can\'t change the reputatation of your own post');
}

if (isset($_GET['up'])) {
	$url = $_GET['up'];
	if (0 == $post_info[1]) {
		$db->query("INSERT INTO ".POSTS_REPUTATIONS_TABLE." (post_id, user_id, post_reputation)
		VALUES ({$post_id}, {$userinfo->id}, 1)");
		$db->query("UPDATE ".POSTS_TABLE." SET post_reputation_up = post_reputation_up + 1 WHERE post_id = {$post_id}");
	} else
	if (0 > $post_info[1]) {
		$db->query("DELETE FROM ".POSTS_REPUTATIONS_TABLE."
		WHERE post_id = {$post_id}
		  AND user_id = {$userinfo->id}");
		$db->query("UPDATE ".POSTS_TABLE." SET post_reputation_down = post_reputation_down - 1 WHERE post_id = {$post_id}");
	}
} else
if (isset($_GET['down'])) {
	$url = $_GET['down'];
	if (0 == $post_info[1]) {
		$db->query("INSERT INTO ".POSTS_REPUTATIONS_TABLE." (post_id, user_id, post_reputation)
		VALUES ({$post_id}, {$userinfo->id}, -1)");
		$db->query("UPDATE ".POSTS_TABLE." SET post_reputation_down = post_reputation_down + 1 WHERE post_id = {$post_id}");
	} else
	if (0 < $post_info[1]) {
		$db->query("DELETE FROM ".POSTS_REPUTATIONS_TABLE."
		WHERE post_id = {$post_id}
		  AND user_id = {$userinfo->id}");
		$db->query("UPDATE ".POSTS_TABLE." SET post_reputation_up = post_reputation_up - 1 WHERE post_id = {$post_id}");
	}
}

URL::redirect(\Poodle\Base64::urlDecode($url, true) ?: URL::index("&file=viewtopic&p={$post_id}"));
