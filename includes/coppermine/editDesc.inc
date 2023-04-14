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
  $Source: /public_html/includes/coppermine/editDesc.inc,v $
  $Revision: 9.4 $
  $Author: akamu $
  $Date: 2006/01/07 01:13:27 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
function process_post_data()
{
    global $CONFIG, $db,$user_album_set;
    $pid = is_numeric($_POST['id']) ? $_POST['id'] : cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__) ;
    $aid = is_numeric($_POST['aid']) ? $_POST['aid'] : cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__) ;
    $title = isset($_POST['title']) ? Fix_Quotes($_POST['title'],1) : NULL;
    check_words($title);
    $caption = isset($_POST['caption']) ? Fix_Quotes(html2bb($_POST['caption'])) : NULL;
    check_words($caption);
    $keywords = isset($_POST['keywords']) ? Fix_Quotes($_POST['keywords'],1) : NULL; 
    check_words($keywords);
    $user1 = isset($_POST['user1']) ? Fix_Quotes($_POST['user1'],1) : NULL;
    check_words($user1);
    $user2 = isset($_POST['user2']) ? Fix_Quotes($_POST['user2'],1) : NULL;
    check_words($user2);
    $user3 = isset($_POST['user3']) ? Fix_Quotes($_POST['user3'],1) : NULL;
    check_words($user3);
    $user4 = isset($_POST['user4']) ? Fix_Quotes($_POST['user4'],1) : NULL;
    check_words($user4);
    $read_exif = isset($_POST['read_exif']);
    $reset_vcount = isset($_POST['reset_vcount']);
    $reset_votes = isset($_POST['reset_votes']);
    $del_comments = isset($_POST['del_comments']) || $delete;
    $query = "SELECT category, filepath, filename FROM {$CONFIG['TABLE_PICTURES']}, {$CONFIG['TABLE_ALBUMS']} WHERE {$CONFIG['TABLE_PICTURES']}.aid = {$CONFIG['TABLE_ALBUMS']}.aid AND pid='$pid'"; 
    if ($result = $db->sql_query($query)) {
        $pic = $db->sql_fetchrow($result);
    } else {
        cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
    } 
    if (!GALLERY_ADMIN_MODE && !USER_ADMIN_MODE) {
        if ($pic['category'] != FIRST_USER_CAT + USER_ID) cpg_die(_ERROR, PERM_DENIED . "<br />(picture category = {$pic['category']}/ $pid)", __FILE__, __LINE__);
        if (!isset($user_album_set[$aid])) cpg_die(_ERROR, PERM_DENIED . "<br />(target album = $aid)", __FILE__, __LINE__);
    } 

    $update = "aid = '" . intval($aid) . "'";
    $update .= ", title = '" . $title . "'";
    $update .= ", caption = '" . $caption . "'";
    $update .= ", keywords = '" . $keywords . "'";
    $update .= ", user1 = '" . $user1 . "'";
    $update .= ", user2 = '" . $user2 . "'";
    $update .= ", user3 = '" . $user3 . "'";
    $update .= ", user4 = '" . $user4 . "'";
    
    if ($reset_vcount) $update .= ", hits = '0'";
    if ($reset_votes) $update .= ", pic_rating = '0', votes = '0'";

    if ($del_comments) {
        $result = $db->sql_query("DELETE FROM {$CONFIG['TABLE_COMMENTS']} WHERE pid='$pid'");
    } else {
        $result = $db->sql_query("UPDATE {$CONFIG['TABLE_PICTURES']} SET $update WHERE pid='$pid'");
    } 

    if (!$read_exif) {
        // redirect to image if there isn't asked for EXIF info
/*
        // pos is ordered by filename
        $result = $db->sql_query("SELECT pid FROM {$CONFIG['TABLE_PICTURES']} WHERE aid = '$aid' ORDER BY filename");
        $pos = 0;
        while ($row = $db->sql_fetchrow($result)) {
            if ($row['pid'] == $pid) {
                url_redirect(getlink("&file=displayimage&album=$aid&pos=$pos"));
            }
            $pos++;
        } 
*/
    } 
} 

function get_user_albums($user_id)
{
    global $user_albums_list, $public_albums_list;
    $public_albums_list = get_albumlist($user_id);
    $user_albums_list = array();
}

function form_alb_list_box()
{
    global $CONFIG, $CURRENT_PIC;
    global $user_albums_list, $public_albums_list;
    $sel_album = $CURRENT_PIC['aid'];

    echo '
        <tr>
            <td class="tableb" style="white-space: nowrap;">
                        '.ALBUM.'
        </td>
        <td class="tableb" valign="top">
                <select name="aid" class="listbox">

';
    foreach($public_albums_list as $album) {
        echo '                        <option value="' . $album['aid'] . '"' . ($album['aid'] == $sel_album ? ' selected' : '') . '>';
        echo $album['title'] . "</option>\n";
    } 
    foreach($user_albums_list as $album) {
        echo '                        <option value="' . $album['aid'] . '"' . ($album['aid'] == $sel_album ? ' selected' : '') . '>* ' . $album['cname'] . "-" . $album['title'] . "</option>\n";
    } 
    echo <<<EOT
                        </select>
                </td>
        </tr>

EOT;
} 

if (isset($_POST['submitDescription'])){
    process_post_data();
    url_redirect(getlink("&file=displayimage&pid=$pid"));
}
$result = $db->sql_query("SELECT * FROM {$CONFIG['TABLE_PICTURES']} WHERE pid = '$pid'");
$CURRENT_PIC = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

$thumb_url = get_pic_url($CURRENT_PIC, 'thumb');
$thumb_link = getlink('&file=displayimage&pid='.$CURRENT_PIC['pid']);
$filename = htmlprepare($CURRENT_PIC['filename']);

$THUMB_ROWSPAN = 5;
if ($CONFIG['user_field1_name'] != '') $THUMB_ROWSPAN++;
if ($CONFIG['user_field2_name'] != '') $THUMB_ROWSPAN++;
if ($CONFIG['user_field3_name'] != '') $THUMB_ROWSPAN++;
if ($CONFIG['user_field4_name'] != '') $THUMB_ROWSPAN++;

get_user_albums(USER_ID);

starttable("100%", $title, 3);
// starttable("100%", $lang_editpics_php['desc'], 3);
//$pic_info = sprintf($lang_editpics_php['pic_info_str'], $CURRENT_PIC['pwidth'], $CURRENT_PIC['pheight'], ($CURRENT_PIC['filesize'] >> 10), $CURRENT_PIC['hits'], $CURRENT_PIC['votes']);
$pic_info = sprintf(PIC_INFO_STR, $CURRENT_PIC['pwidth'], $CURRENT_PIC['pheight'], ($CURRENT_PIC['filesize'] >> 10), $CURRENT_PIC['hits'], $CURRENT_PIC['votes']);

require($THEME_DIR . '/' . $template_edit_one_pic);
/*
$thefile = implode("", file($THEME_DIR . '/' . $template_edit_one_pic));
$thefile = addslashes($thefile);
$thefile = "\$template=\"" . $thefile . "\";";
eval($thefile);
$template_edit_one_pic = $template;
*/
$template_editonepic_userfield = template_extract_block($template_edit_one_pic, 'user_field');
$template_editonepic_main      = template_extract_block($template_edit_one_pic, 'main');
$template_editonepic_footer    = template_extract_block($template_edit_one_pic, 'footer');

echo $template_edit_one_pic;

form_alb_list_box();

echo $template_editonepic_main;

if ($CONFIG['user_field1_name'] != '') {
    $params = array('{FIELD_NAME}' => $CONFIG['user_field1_name'],
        '{USER_FIELD}' => 'user1',
        '{USER_PIC}' => $CURRENT_PIC['user1'],
        );
    echo template_eval($template_editonepic_userfield, $params);
} 
if ($CONFIG['user_field2_name'] != '') {
    $params = array('{FIELD_NAME}' => $CONFIG['user_field2_name'],
        '{USER_FIELD}' => 'user2',
        '{USER_PIC}' => $CURRENT_PIC['user2'],
        );
    echo template_eval($template_editonepic_userfield, $params);
} 
if ($CONFIG['user_field3_name'] != '') {
    $params = array('{FIELD_NAME}' => $CONFIG['user_field3_name'],
        '{USER_FIELD}' => 'user3',
        '{USER_PIC}' => $CURRENT_PIC['user3'],
        );
    echo template_eval($template_editonepic_userfield, $params);
} 
if ($CONFIG['user_field4_name'] != '') {
    $params = array('{FIELD_NAME}' => $CONFIG['user_field4_name'],
        '{USER_FIELD}' => 'user4',
        '{USER_PIC}' => $CURRENT_PIC['user4'],
        );
    echo template_eval($template_editonepic_userfield, $params);
} 

echo $template_editonepic_footer;

endtable();