<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   http://dragonflycms.org/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

require(__DIR__ . '/include/load.inc');

if (!USER_ID) {
	cpg_error(ACCESS_DENIED, 403);
}

$user_data = $db->uFetchAssoc("SELECT
	username, group_name,
	COUNT(pid) as pic_count,
	SUM(total_filesize) as disk_usage
FROM {$db->TBL->users} AS u
INNER JOIN {$db->TBL->cpg_usergroups} AS g ON user_group_cp = group_id
LEFT JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (a.user_id = ".USER_ID." AND category = ".\Coppermine::USER_GAL_CAT.") OR category = " . (\Coppermine::FIRST_USER_CAT + USER_ID) . "
LEFT JOIN {$CONFIG['TABLE_PICTURES']} AS p ON p.aid = a.aid
WHERE u.user_id = " . USER_ID . "
GROUP BY u.user_id, username, user_email, user_regdate, group_name, user_from, user_interests, user_website, user_occ, group_quota");

if (!$user_data) {
	cpg_error($lang_register_php['err_unk_user'], 404);
}

$title = sprintf(X_S_PROFILE, $user_data['username']);
pageheader($title);

$OUT = \Dragonfly::getKernel()->OUT;
$OUT->cpg_title = $Module->title;
$OUT->cpg_user_data = $user_data;
$OUT->display('coppermine/profile');

require_once('modules/Your_Account/functions.php');
$class = new \Dragonfly\Modules\Your_Account\Userinfo();
$class->display(USER_ID);

pagefooter();
