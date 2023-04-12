<?php 
/***************************************************************************  
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify	   
   it under the terms of the GNU General Public License as published by	   
   the Free Software Foundation; either version 2 of the License, or		  
   (at your option) any later version.										
  **************************************************************************  
  Last modification notes:
  $Source: /cvs/html/modules/coppermine/ratepic.php,v $
  $Revision: 9.2 $
  $Author: nanocaiordo $
  $Date: 2007/08/27 02:39:23 $
****************************************************************************/
if (!defined('CPG_NUKE')) { exit; }

define('RATEPIC_PHP', true);
require("modules/" . $module_name . "/include/load.inc");
//$location = getlink("&file=displayimage&pid=$pic");
$location = getlink($_POST['currentpage']);
/* Check if required parameters are present
if (!isset($_GET['pic']) || !isset($_GET['rate'])) {
	pageheader(INFO, $location);
	msg_box(_ERROR, PARAM_MISSING, CONTINU, $location);
	pagefooter();
}*/
$pic = intval($_POST['pic']) ? $_POST['pic'] : cpg_die(_ERROR, PARAM_MISSING, __FILE__,__LINE__);
$rate = is_numeric($_POST['rate']) ? $_POST['rate'] : cpg_die(_ERROR, PARAM_MISSING.$_POST['rate'], __FILE__,__LINE__);

$rate = min($rate, 5);
$rate = max($rate, 0);
// Retrieve picture/album information & check if user can rate picture
$sql = "SELECT a.votes as votes_allowed, p.votes as votes, pic_rating " . "FROM {$CONFIG['TABLE_PICTURES']} AS p, {$CONFIG['TABLE_ALBUMS']} AS a " . "WHERE p.aid = a.aid AND pid = '$pic' LIMIT 0,1";
$result = $db->sql_query($sql);
if (!$db->sql_numrows($result)) {
	pageheader(INFO, $location);
	msg_box(_ERROR, NON_EXIST_AP, CONTINU, $location);
	pagefooter();
}
$row = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

if (!USER_CAN_RATE_PICTURES || $row['votes_allowed'] == 'NO') {
	//$location = getlink("&file=displayimage&pid=$pic");
	pageheader(INFO, $location);
	msg_box(_ERROR, PERM_DENIED, CONTINU, $location);
	pagefooter();
}
// Clean votes older votes
$curr_time = time();
$clean_before = $curr_time - $CONFIG['keep_votes_time'] * 86400;
$result = $db->sql_query("DELETE " . "FROM {$CONFIG['TABLE_VOTES']} " . "WHERE vote_time < $clean_before");

// Check if user already rated this picture
$user_md5_id = USER_ID ? md5(USER_ID) : $USER['ID'];
$result = $db->sql_query("SELECT * FROM {$CONFIG['TABLE_VOTES']} WHERE pic_id = '$pic' AND user_md5_id = '$user_md5_id'");
if ($db->sql_numrows($result)) {
	//$location = getlink("&file=displayimage&pid=$pic");
	pageheader(INFO, $location);
	msg_box(_ERROR, ALREADY_RATED, CONTINU, $location);
	pagefooter();
}
// Update picture rating
$new_rating = round(($row['votes'] * $row['pic_rating'] + $rate * 2000) / ($row['votes'] + 1));
$result = $db->sql_query("UPDATE {$CONFIG['TABLE_PICTURES']} SET pic_rating = '$new_rating', votes = votes + 1 WHERE pid = '$pic'");

// Update the votes table
$result = $db->sql_query("INSERT INTO {$CONFIG['TABLE_VOTES']} (pic_id, user_md5_id, vote_time) VALUES ('$pic', '$user_md5_id', '$curr_time')");

pageheader(INFO, $location);
msg_box(INFO, RATE_OK, CONTINU, $location);
pagefooter();
