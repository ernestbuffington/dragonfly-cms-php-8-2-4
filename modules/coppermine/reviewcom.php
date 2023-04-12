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
  $Source: /cvs/html/modules/coppermine/reviewcom.php,v $
  $Revision: 9.3 $
  $Author: djmaze $
  $Date: 2006/02/09 23:47:21 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

define('REVIEWCOM_PHP', true);
require("modules/" . $module_name . "/include/load.inc");
if (!GALLERY_ADMIN_MODE) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);

// Delete comments if form is posted
$nb_com_del = 0;
if (isset($_POST['cid_array'])) {
    $cid_array = $_POST['cid_array'];
    $cid_set = '';
    foreach ($cid_array as $cid)
        $cid_set .= ($cid_set == '') ? '(' . $cid : ', ' . $cid;
    $cid_set .= ')';
    $db->sql_query("DELETE FROM {$CONFIG['TABLE_COMMENTS']} WHERE msg_id IN $cid_set",false, __FILE__, __LINE__);
    $nb_com_del = $db->sql_affectedrows();
}

/*$result = $db->sql_query("SELECT count(*) FROM {$CONFIG['TABLE_COMMENTS']}",false, __FILE__, __LINE__);
$nbEnr = $db->sql_fetchrow($result);
$comment_count = $nbEnr[0];
*/
$comment_count = cpg_tablecount($CONFIG['TABLE_COMMENTS'], "count(*)", __FILE__, __LINE__);

if (!$comment_count) cpg_die(INFO , NO_COMMENT, __FILE__, __LINE__);

$start = $_GET['start'] ?? 0;
$start = intval($start);
$count = $_GET['count'] ?? 25;
$count = intval($count);
$next_target = getlink('&amp;file=reviewcom&amp;start=' . ($start + $count) . '&amp;count=' . $count);
$prev_target = getlink('&amp;file=reviewcom&amp;start=' . max(0, $start - $count) . '&amp;count=' . $count);
$s50 = $count == 50 ? 'selected' : '';
$s75 = $count == 75 ? 'selected' : '';
$s100 = $count == 100 ? 'selected' : '';

if ($start + $count < $comment_count){
    $next_link = "<a href=\"$next_target\"><b>".R_SEE_NEXT."</b></a>&nbsp;&nbsp;-&nbsp;&nbsp;";
}else{
    $next_link = '';
}

if ($start > 0){
    $prev_link = "<a href=\"$prev_target\"><b>".R_SEE_PREV."</b></a>&nbsp;&nbsp;-&nbsp;&nbsp;";
}else{
    $prev_link = '';
}

pageheader(REVIEW_TITLE);

starttable();
echo '
        <tr>
            <form action="'.getlink("&amp;file=reviewcom&amp;start=".$start."&amp;count=".$count).'" method="post" enctype="multipart/form-data" accept-charset="'._CHARSET.'">
                <td class="tableh1" colspan="3"><h2>'.REVIEW_TITLE.'</h2></td>
        </tr>

';

if ($nb_com_del > 0){
                 $msg_txt = sprintf(N_COMM_DEL, $nb_com_del);
                 echo <<<EOT
        <tr>
                <td class="tableh2" colspan="3" align="center">
                        <br /><b>$msg_txt</b><br /><br />
                </td>
        </tr>

EOT;
                }

echo '
        <tr>
                <td class="tableb" colspan="3">
                        '.$prev_link.'
                        '.$next_link.'
                        <b>'.N_COMM_DISP.'</b>';
$CPG_URL = getlink("&file=reviewcom&start=$start&count=",0,1);
echo <<<EOT
<select onchange="if(this.options[this.selectedIndex].value) window.location.href='$CPG_URL'+this.options[this.selectedIndex].value;"  name="count" class="listbox">";
EOT;
//<select onChange="if(this.options[this.selectedIndex].value) window.location.href='$CPG_URL&file=reviewcom&start=$start&count='+this.options[this.selectedIndex].value;"  name="count" class="listbox">

echo '                                <option value="25">25</option>
                                <option value="50" '.$s50.'>50</option>
                                <option value="75" '.$s75.'>75</option>
                                <option value="100" '.$s100.'>100</option>
                        </select>
                </td>
        </tr>

';

$result = $db->sql_query("SELECT msg_id, msg_author, msg_body, msg_date, author_id, {$CONFIG['TABLE_COMMENTS']}.pid as pid, aid, filepath, filename, url_prefix, pwidth, pheight FROM {$CONFIG['TABLE_COMMENTS']}, {$CONFIG['TABLE_PICTURES']} WHERE {$CONFIG['TABLE_COMMENTS']}.pid = {$CONFIG['TABLE_PICTURES']}.pid ORDER BY msg_id DESC LIMIT $start, $count",false,__FILE__,__LINE__);

while ($row = $db->sql_fetchrow($result)){
                 $thumb_url = get_pic_url($row, 'thumb');
                 $image_size = compute_img_size($row['pwidth'], $row['pheight'], $CONFIG['alb_list_thumb_size']);
                 $thumb_link = getlink("&amp;file=displayimage&amp;pid=".$row['pid']);
                 $msg_date = localised_date($row['msg_date'], COMMENT_DATE_FMT);
                 echo <<<EOT
        <tr>
        <td colspan="2" class="tableh2" valign="top">
                <table cellpadding="0" cellspacing="0" border ="0">
                        <tr>
                        <td><input name="cid_array[]" type="checkbox" value="{$row['msg_id']}" />
                        <td><img src="$CPG_M_DIR/images/spacer.gif" alt="" width="5" height="1" /><br /></td>
                        <td><b>{$row['msg_author']}</b> - {$msg_date}</td>
                        </tr>
                </table>
                </td>
        </tr>
        <tr>
        <td class="tableb" valign="top" width="100%">
                        {$row['msg_body']}
                </td>
            <td class="tableb" align="center">
                        <a href="$thumb_link" target="_blank"><img src="$thumb_url" {$image_size['geom']} class="image" border="0" alt="" /><br /></a>
        </td>
        </tr>

EOT;
                }

$db->sql_freeresult($result);

echo '
        <tr>
            <td colspan="3" align="center" class="tablef">
                        <input type="submit" value="'.R_DEL_COMM.'" class="button" />
                </td>
        </form>
        </tr>

';
endtable();
pagefooter();
