<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   https://dragonfly.coders.exchange/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($op)) { exit; }
require("modules/{$op}/include/load.inc");

if ('POST' === $_SERVER['REQUEST_METHOD']) {
	$nb_com_del = 0;
	if (isset($_POST['cid_array']) && is_array($_POST['cid_array'])) {
		$cids = implode(',', array_map('intval', $_POST['cid_array']));
		$nb_com_del = $CONFIG['TABLE_COMMENTS']->delete("msg_id IN ($cids)");
	}
	\Dragonfly::closeRequest(sprintf(N_COMM_DEL, $nb_com_del), 200, $_SERVER['REQUEST_URI']);
}

$comment_count = $CONFIG['TABLE_COMMENTS']->count();
if (!$comment_count) {
	cpg_error(NO_COMMENT);
}

$page = max(1, $_GET->uint('page'));
$limit = 25;
$offset = $limit * ($page - 1);

\Dragonfly\Page::title(REVIEW_TITLE);
$OUT = \Dragonfly::getKernel()->OUT;
$OUT->cpg_comments = $db->query("SELECT
	msg_id, msg_author, msg_body, msg_date, pid
FROM {$CONFIG['TABLE_COMMENTS']}
ORDER BY msg_date DESC
LIMIT {$limit} OFFSET {$offset}");
$OUT->cpg_comments_pagination = new \Poodle\Pagination(URL::admin('&file=reviewcom&page=${page}'), $comment_count, $offset, $limit);
$OUT->display('coppermine/admin/reviewcom');
