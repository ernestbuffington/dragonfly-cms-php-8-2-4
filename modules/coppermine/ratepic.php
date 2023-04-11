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

/* Applied rules:
 * CountOnNullRector (https://3v4l.org/Bndc9)
 */

require(__DIR__ . '/include/load.inc');

$title = \Dragonfly::getKernel()->L10N['Information'];

$location = $_POST['currentpage'];

$pic = $_POST->uint('pic') ?: cpg_error(PARAM_MISSING, 404);

if (!isset($_POST['rate']) && 1 != (is_countable($_POST['rate']) ? count($_POST['rate']) : 0)) {
	cpg_error(PARAM_MISSING, 404);
}
$rate = array_keys($_POST['rate']);
$rate = max(min($rate[0], 5), 0);

// Retrieve picture/album information & check if user can rate picture
$row = $db->uFetchAssoc("SELECT
		a.votes as votes_allowed
	FROM {$CONFIG['TABLE_PICTURES']} AS p, {$CONFIG['TABLE_ALBUMS']} AS a
	WHERE p.aid = a.aid AND pid = {$pic}");
if (!$row) {
	pageheader($title);
	msg_box(_ERROR, NON_EXIST_AP, CONTINU, $location);
	pagefooter();
}

if (!$USER_DATA['can_rate_pictures'] || 'NO' == $row['votes_allowed']) {
	pageheader($title);
	msg_box(_ERROR, PERM_DENIED, CONTINU, $location);
	pagefooter();
}

// Clean votes older votes
$curr_time = time();
$clean_before = $curr_time - $CONFIG['keep_votes_time'] * 86400;
$result = $db->query("DELETE FROM {$CONFIG['TABLE_VOTES']} WHERE vote_time < {$clean_before}");

// Check if user already rated this picture
$user_md5_id = md5(USER_ID>1 ? USER_ID : $_SERVER['REMOTE_ADDR']);
$result = $db->query("SELECT * FROM {$CONFIG['TABLE_VOTES']} WHERE pic_id = {$pic} AND user_md5_id = '{$user_md5_id}'");
if ($result->num_rows) {
	pageheader($title);
	msg_box(_ERROR, ALREADY_RATED, CONTINU, $location);
	pagefooter();
}

// Update picture rating
$result = $db->query("UPDATE {$CONFIG['TABLE_PICTURES']} SET
	pic_rating = ROUND((votes * pic_rating + {$rate} * 2000) / (votes + 1)),
	votes = votes + 1
WHERE pid = {$pic}");

// Update the votes table
$result = $db->query("INSERT INTO {$CONFIG['TABLE_VOTES']} (pic_id, user_md5_id, vote_time) VALUES ({$pic}, '{$user_md5_id}', {$curr_time})");

\Dragonfly::closeRequest(RATE_OK, 200, $location);
