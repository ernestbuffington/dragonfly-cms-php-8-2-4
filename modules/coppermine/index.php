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
  $Source: /cvs/html/modules/coppermine/index.php,v $
  $Revision: 9.18 $
  $Author: nanocaiordo $
  $Date: 2007/07/31 05:43:06 $
  ****************************************************************************/
if (!defined('CPG_NUKE')) { die('You do not have permission to access this file'); }
global $FAVPICS;
require_once('includes/nbbcode.php');
if (isset($_GET['mode']) && $_GET['mode'] == 'smilies') {
  echo smilies_table("window", $field, $form);
  exit;
}

require("modules/" . $module_name . "/include/load.inc");

function html_albummenu($id)
{
  global $template_album_admin_menu;
  static $template = '';
  if ($template == '') {
    $params = array('{CONFIRM_DELETE}' => CONFIRM_DELETE_ALB,
     '{DELETE}' => DELETE,
     '{MODIFY}' => MODIFY,
     '{EDIT_PICS}' => EDIT_PICS,
    );
    $template = template_eval($template_album_admin_menu, $params);
  } 
  $params = array('{ALBUM_ID}' => $id,);
  return template_eval($template, $params);
} 

function get_subcat_data($parent, &$cat_data, &$album_set_array, $level, $ident = '')
{
  global $db, $CONFIG, $HIDE_USER_CAT, $CPG_M_DIR, $CPG_M_URL;
  $categories_data = get_categories_data();
  $rowset = array();
  for ($i=0;$i<count($categories_data);$i++) {
    if ($categories_data[$i]['parent'] != $parent) continue;
    $rowset[] = $categories_data[$i];
  }
  if (!empty($rowset)) {
    $datacount = init_cpg_count();
    $albums_data = get_albums_data();
    foreach ($rowset as $subcat) {
      $album_count = 0;
      if ($subcat['cid'] == USER_GAL_CAT) {
        for ($i=0;$i<count($albums_data);$i++) {
          if ($albums_data[$i]['category'] < FIRST_USER_CAT) continue;
            $album_set_array[] = $albums_data[$i]['aid'];
            $album_count++;
          }
          list($pic_count)  = $db->sql_ufetchrow("SELECT count(*) FROM {$CONFIG['TABLE_PICTURES']}, {$CONFIG['TABLE_ALBUMS']} WHERE {$CONFIG['TABLE_PICTURES']}.aid = {$CONFIG['TABLE_ALBUMS']}.aid AND category >= " . FIRST_USER_CAT, SQL_NUM);
          $subcat['description'] = preg_replace("/<br.*?>[\r\n]*/i", '<br />' . $ident , decode_bbcode($subcat['description']));
          $link = $ident . '<a href="'.getlink("$_GET[name]&amp;cat=$subcat[cid]").'">'.$subcat['catname'].'</a>';
//        $link = $ident . "<a href=$CPG_M_URL&cat={$subcat['cid']}>{$subcat['catname']}</a>";

          if ($album_count) {
            $cat_data[$subcat['cid']] = array($link, $ident . $subcat['description'], $album_count, $pic_count);
            $HIDE_USER_CAT = 0;
          }
        } else {
          for ($i=0;$i<count($albums_data);$i++) {
            if ($albums_data[$i]['category'] >= FIRST_USER_CAT) continue;
            $album_set_array[] = $albums_data[$i]['aid'];
          }
          $pic_count = empty($datacount[$subcat['cid']]['pic_count']) ? 0 : $datacount[$subcat['cid']]['pic_count'];
          $album_count = empty($datacount[$subcat['cid']]['album_count']) ? 0 : $datacount[$subcat['cid']]['album_count'];
          $subcat['catname'] = $subcat['catname'];
          $subcat['description'] = preg_replace("/<br.*?>[\r\n]*/i", '<br />' . $ident , decode_bbcode($subcat['description']));
          $link = $ident . '<a href="'.getlink("$_GET[name]&amp;cat=$subcat[cid]").'">'.$subcat['catname'].'</a>';
          $cat_albums = '';
          if ($pic_count == 0 && $album_count == 0) {
            $cat_data[$subcat['cid']] = array($link, $ident . $subcat['description'], 0, 0);
          }
          else {
            // Check if you need to show subcat_level
            if ($level == 0 && $CONFIG['first_level']) $cat_albums = list_cat_albums($subcat['cid']);
            $cat_data[$subcat['cid']] = array($link, $ident . $subcat['description'], $album_count, $pic_count);
          } 
          if ($level < $CONFIG['subcat_level']) get_subcat_data($subcat['cid'], $cat_data, $album_set_array, $level+1, $ident . '<img src="images/spacer.gif" width="20" height="1" />');
          if ($cat_albums) $cat_data[$subcat['cid']]['cat_albums'] = $cat_albums;
        }
      }
  }
}
// List all categories
function get_cat_list(&$cat_data, &$statistics)
{
  global $db, $CONFIG, $BREADCRUMB_TEXT, $STATS_IN_ALB_LIST;
  global $HIDE_USER_CAT;
  global $cat;
  // Build the category list
  $cat_data = array();
  $album_set_array = array();
  $HIDE_USER_CAT = 1;
  get_subcat_data($cat, $cat_data, $album_set_array, 0);
  // Add the albums in the current category to the album set
  if ($cat) {
   if ($cat == USER_GAL_CAT) {
     foreach (get_albums_data() as $row) {
       if ($row['category'] < FIRST_USER_CAT) continue;
       $album_set_array[] = $row['aid'];
     }
   }
   else {
    foreach (get_albums_data() as $row) {
      if ($row['category'] != $cat) continue;
        $album_set_array[] = $row['aid'];
      }
    }
  }
  if (count($album_set_array) && $cat) {
    $set = '';
    foreach ($album_set_array as $album) $set .= $album . ',';
    $set = substr($set, 0, -1);
    $current_album_set = "aid IN ($set) ";
  } elseif ($cat) {
    $current_album_set = "aid IN (-1) ";
  }
  // Gather gallery statistics
  if ($cat == 0) {
    $album_count = count(get_albums_data());
    $picture_count = cpg_tablecount($CONFIG['TABLE_PICTURES'], 'count(*)', __FILE__,__LINE__);
    $comment_count = cpg_tablecount($CONFIG['TABLE_COMMENTS'], 'count(*)', __FILE__,__LINE__);
    $cat_count = count(get_categories_data());
    $hit_count = cpg_tablecount($CONFIG['TABLE_PICTURES'], 'sum(hits)', __FILE__,__LINE__);
    if (count($cat_data)) {
      $statistics = strtr(STAT1, array(
        '[pictures]' => $picture_count,
        '[albums]' => $album_count,
        '[cat]' => $cat_count,
        '[comments]' => $comment_count,
        '[views]' => $hit_count)
      );
    } else {
      $STATS_IN_ALB_LIST = true;
      $statistics = strtr(STAT3, array(
        '[pictures]' => $picture_count,
        '[albums]' => $album_count,
        '[comments]' => $comment_count,
        '[views]' => $hit_count)
      );
    } 
  } elseif ($cat >= FIRST_USER_CAT && $current_album_set) {
    $album_count = cpg_tablecount($CONFIG['TABLE_ALBUMS']." WHERE $current_album_set", 'count(*)', __FILE__,__LINE__);
    $picture_count = cpg_tablecount($CONFIG['TABLE_PICTURES']." WHERE $current_album_set", 'count(*)', __FILE__,__LINE__);
    $hit_count = cpg_tablecount($CONFIG['TABLE_PICTURES']." WHERE $current_album_set", 'sum(hits)', __FILE__,__LINE__);
    $statistics = strtr(STAT2, array(
      '[pictures]' => $picture_count,
      '[albums]' => $album_count,
      '[views]' => $hit_count)
    );
  } else {
    $statistics = '';
  } 
} 

function list_users()
{
  global $db, $CONFIG, $PAGE, $CPG_M_DIR, $CPG_M_URL;
  global $template_user_list_info_box;
  $sql = "SELECT user_id, username, user_avatar as avatar, user_avatar_type, a.title, " .
         "COUNT(DISTINCT a.aid) as alb_count, " .
         "COUNT(DISTINCT pid) as pic_count, " .
         "MAX(pid) as thumb_pid " .
         "FROM {$CONFIG['TABLE_USERS']} AS u " .
         "INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (category = " . FIRST_USER_CAT . " + user_id " . " AND ".VIS_GROUPS.")".
         "LEFT JOIN {$CONFIG['TABLE_PICTURES']} AS p ON (p.aid = a.aid AND approved = '1') " .
         "GROUP BY user_id, username, user_avatar, user_avatar_type ORDER BY username";
  $result = $db->sql_query($sql);
  $user_count = $db->sql_numrows($result);
  if (!$user_count) {
    msg_box(USER_LIST, NO_USER_GAL, '', '', '100%');
    $db->sql_freeresult($result);
    return;
  } 

  $user_per_page = $CONFIG['thumbcols'] * $CONFIG['thumbrows'];
  $totalPages = ceil($user_count / $user_per_page);
  if ($PAGE > $totalPages) $PAGE = $totalPages;
  $lower_limit = ($PAGE-1) * $user_per_page;
  $upper_limit = min($user_count, $PAGE * $user_per_page);

  $row_count = $upper_limit - $lower_limit;

  $rowset = array();
  $i = 0;
  $db->sql_rowseek($lower_limit, $result);
  while (($row = $db->sql_fetchrow($result)) && ($i++ < $row_count)) $rowset[] = $row;
  $db->sql_freeresult($result);

  $user_list = array();

  for ($i = 0; $i < count($rowset); $i++) {
    $user =& $rowset[$i];
    $user_thumb = '<img src="' . $CPG_M_DIR . '/images/nopic.jpg" alt="'.NO_IMG_TO_DISPLAY.'" title="'.NO_IMG_TO_DISPLAY.'" class="image" border="0" />';
    $user_pic_count = $user['pic_count'];
    $user_thumb_pid = $user['thumb_pid'];
    $user_album_count = $user['alb_count'];

    // User avatar as config opt
    if (!eregi("blank.gif", $user['avatar']) && strlen($user['avatar']) > 3 && $CONFIG['avatar_private_album']) {
      global $MAIN_CFG;
      if ($user['user_avatar_type'] == 1) {
        $avatar = $MAIN_CFG['avatar']['path'].'/';
      } else if ($user['user_avatar_type'] == 2) {
        $avatar = '';
      } else if ($user['user_avatar_type'] == 3) {
        $avatar = $MAIN_CFG['avatar']['gallery_path'].'/';
      }
      if (isset($avatar)) {
        $user_thumb = '<img src="'.$avatar.$user['avatar'].'" alt="" class="image" border="0" />';
      }
    } else if ($user_pic_count) {
      $sql = "SELECT filepath, filename, url_prefix, pwidth, pheight " .
             "FROM {$CONFIG['TABLE_PICTURES']} " .
             "WHERE pid='$user_thumb_pid'";
      $result = $db->sql_query($sql, false,__FILE__,__LINE__);
      if ($db->sql_numrows($result)) {
        $picture = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        $image_size = compute_img_size($picture['pwidth'], $picture['pheight'], $CONFIG['thumb_width']);
        $user_thumb = "<img src=\"" . get_pic_url($picture, 'thumb') . "\" {$image_size['geom']} title=\"".$user['username']."\" alt=\"".$user['username']."\" border=\"0\" class=\"image\" />"; // $user['username']
      }
    } 

    $albums_txt = sprintf(N_ALBUMS, $user_album_count);
    $pictures_txt = sprintf(N_PICS, $user_pic_count);

    $params = array(
      '{username}' => $user['username'],
      '{USER_PROFILE_LINK}' => getlink("Your_Account&amp;profile=".$user['user_id']),
      '{ALBUMS}' => $albums_txt,
      '{PICTURES}' => $pictures_txt,
    );
    $caption = template_eval($template_user_list_info_box, $params);

    $user_list[] = array(
      'cat' => FIRST_USER_CAT + $user['user_id'],
      'image' => $user_thumb,
      'caption' => $caption,
      'url' => getlink("&amp;cat=".(FIRST_USER_CAT + $user['user_id'])),
    );
  } 
  $page_link = getlink("&amp;cat=1&amp;page=%d");
  theme_display_thumbnails($user_list, $user_count, '', $page_link, $PAGE, $totalPages, false, true, 'user');
} 
// List (category) albums
// Redone for a cleaner approach: DJMaze
function list_cat_albums($cat = 0, $buffer = true)
{
  global $db, $CONFIG, $USER, $PAGE, $USER_DATA, $CPG_M_DIR;

  if ($cat == 0 && $buffer) return '';
  $cat = intval($cat);

  $alb_per_page = $CONFIG['albums_per_page'];
  $maxTab = $CONFIG['max_tabs'];
  $visible = '';
  if (!USER_IS_ADMIN && !$CONFIG['show_private']) {
    $visible = "AND ".VIS_GROUPS;// NEW gtroll
    $tmpvis = explode(',', USER_IN_GROUPS);
    $vis[0] = 0;
    foreach ($tmpvis as $dummy => $group) $vis[$group] = $group;
    unset($tmpvis);
  }
  /*
  $result = $db->sql_query("SELECT count(*) FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = $cat $visible",false,__FILE__,__LINE__);
  $nbEnr = $db->sql_fetchrow($result);
  $nbAlb = $nbEnr[0];
  $db->sql_freeresult($result);
  */
  //$nbAlb = cpg_tablecount($CONFIG['TABLE_ALBUMS']." WHERE category = $cat $visible", 'count(*)',__FILE__, __LINE__);
  $nbAlb = 0;
  foreach (get_albums_data() as $row) {
    if ($row['category'] != $cat) continue;
    if (isset($vis) && !isset($vis[$row['visibility']])) continue;
    $nbAlb++;
  }
  if (!$nbAlb) return '';

  $totalPages = ceil($nbAlb / $alb_per_page);
  if (isset($_GET['page'])) $PAGE = max(intval($_GET['page']), 1);
  //if ($PAGE > $totalPages || $cat != $_GET['cat']) $PAGE = 1;
  if ($PAGE > $totalPages) $PAGE = 1;
  $lower_limit = ($PAGE-1) * $alb_per_page;
  $upper_limit = min($nbAlb, $PAGE * $alb_per_page);
  $sql = "SELECT a.aid, a.title, a.description, visibility, filepath, " .
         "filename, url_prefix, pwidth, pheight " .
         "FROM {$CONFIG['TABLE_ALBUMS']} as a " .
         "LEFT JOIN {$CONFIG['TABLE_PICTURES']} as p ON thumb=pid " .
         "WHERE category = '$cat' $visible ORDER BY pos " .
         "LIMIT " . $lower_limit . "," . ($upper_limit - $lower_limit);
  $alb_thumbs = $db->sql_ufetchrowset($sql,SQL_BOTH,__FILE__,__LINE__);
  $disp_album_count = count($alb_thumbs);
  $album_set = '';
  foreach($alb_thumbs as $value) {
    $album_set .= $value['aid'] . ', ';
  } 
  $album_set = '(' . substr($album_set, 0, -2) . ')';

  $sql = "SELECT aid, count(pid) as pic_count, max(pid) as last_pid, max(ctime) as last_upload " .
         "FROM {$CONFIG['TABLE_PICTURES']} " .
         "WHERE aid IN $album_set AND approved = '1' " .
         "GROUP BY aid";
  if ($alb_stats = $db->sql_ufetchrowset($sql,SQL_BOTH)) {
    foreach ($alb_stats as $key => $value) {
      $cross_ref[$value['aid']] =& $alb_stats[$key];
    }
  }

  for ($alb_idx = 0; $alb_idx < $disp_album_count; $alb_idx++) {
    $alb_thumb = &$alb_thumbs[$alb_idx];
    $aid = $alb_thumb['aid'];

    if (isset($cross_ref[$aid])) {
      $alb_stat = $cross_ref[$aid];
      $count = $alb_stat['pic_count'];
    } else {
      $alb_stat = array();
      $count = 0;
    } 
    // Inserts a thumbnail if the album contains 1 or more images
    $visibility = $alb_thumb['visibility'];
    if ($visibility == '0' || $visibility == (FIRST_USER_CAT + USER_ID) || $visibility == $USER_DATA['group_id'] || USER_IS_ADMIN || user_ingroup($visibility,$USER_DATA['user_group_list_cp'])) {
      if ($count > 0) { // Inserts a thumbnail if the album contains 1 or more images
        if ($alb_thumb['filename']) {
          $picture = &$alb_thumb;
        } else {
          $sql = "SELECT filepath, filename, url_prefix, pwidth, pheight FROM {$CONFIG['TABLE_PICTURES']} WHERE pid='{$alb_stat['last_pid']}'";
          $result = $db->sql_query($sql, false,__FILE__,__LINE__);
          $picture = $db->sql_fetchrow($result);
          $db->sql_freeresult($result);
        } 
        $image_size = compute_img_size($picture['pwidth'], $picture['pheight'], $CONFIG['alb_list_thumb_size']);
        $alb_list[$alb_idx]['thumb_pic'] = "<img src=\"" . get_pic_url($picture, 'thumb') . "\" {$image_size['geom']} title=\"".$alb_thumb['title']."\" alt=\"".$alb_thumb['title']."\" border=\"0\" class=\"image\" />";
      } else { // Inserts an empty thumbnail if the album contains 0 images
        $image_size = compute_img_size(100, 75, $CONFIG['alb_list_thumb_size']);
        $alb_list[$alb_idx]['thumb_pic'] = "<img src=\"$CPG_M_DIR/images/nopic.jpg\" {$image_size['geom']} alt=\"".NO_IMG_TO_DISPLAY."\" title=\"".NO_IMG_TO_DISPLAY."\" border=\"0\" class=\"image\" />";
      } 
    } elseif ($CONFIG['show_private']) {
      $image_size = compute_img_size(100, 75, $CONFIG['alb_list_thumb_size']);
      $alb_list[$alb_idx]['thumb_pic'] = "<img src=\"$CPG_M_DIR/images/private.jpg\" {$image_size['geom']} alt=\"".MEMBERS_ONLY."\" title=\"".MEMBERS_ONLY."\" border=\"0\" class=\"image\" />";
    } 

    // Prepare everything
    $last_upload_date = $count ? localised_date($alb_stat['last_upload'], LASTUP_DATE_FMT) : '';
    $alb_list[$alb_idx]['aid'] = $alb_thumb['aid'];
    $alb_list[$alb_idx]['album_title'] = $alb_thumb['title'];
    $alb_list[$alb_idx]['album_desc'] = decode_bbcode($alb_thumb['description']);
    $alb_list[$alb_idx]['pic_count'] = $count;
    $alb_list[$alb_idx]['last_upl'] = $last_upload_date;
    $alb_list[$alb_idx]['album_info'] = sprintf(N_PICTURES, $count) . ($count ? sprintf(LAST_ADDED, $last_upload_date) : "");
    $alb_list[$alb_idx]['album_adm_menu'] = (GALLERY_ADMIN_MODE || (USER_ADMIN_MODE && $cat == USER_ID + FIRST_USER_CAT)) ? html_albummenu($alb_thumb['aid']) : '';
  }

  if ($buffer) {
    ob_start();
    theme_display_album_list_cat($alb_list, $nbAlb, $cat, $PAGE, $totalPages);
    $cat_albums = ob_get_contents();
    ob_end_clean();
    return $cat_albums;
  }
  else{
    theme_display_album_list($alb_list, $nbAlb, $cat, $PAGE, $totalPages);
  }
}

/**
 * Main code
 */

$PAGE = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Gather data for categories
$cat_data = array();
$statistics = '';
$STATS_IN_ALB_LIST = false;
get_cat_list($cat_data, $statistics);
global $BREADCRUMB_TEXT, $thisalbum ;
//limit meta blocks to the current album or category
// NEW
$thisalbum = "category >= 0";
if ($cat < 0) { //  && $cat<0 Meta albums, we need to restrict the albums to the current category
  $actual_album = -$cat;
  $thisalbum .= $CONFIG['TABLE_ALBUMS'].'.aid = '.$actual_album;
} else if ($cat){
  if ($cat == USER_GAL_CAT) {
    $thisalbum = 'category > ' . FIRST_USER_CAT;
  } elseif (is_numeric($cat)) {
    $thisalbum = "category = '$cat'";
  }
}
// NEW
/*if ((is_numeric($cat))&&(!$album)){ //cat list
    $thisalbum = "category =  $cat";
}else if ((is_numeric($cat))||(is_numeric($album))){ // numeric album
    $thisalbum = "aid = $album";
}elseif ((!is_numeric($cat))&&(!$album)){ //home page
    $thisalbum = "category =  0";
}
*/
//non-numeric album don't need this value as there is no meta blocks

pageheader($BREADCRUMB_TEXT ? $BREADCRUMB_TEXT : WELCOME);

$elements = preg_split("|/|", $CONFIG['main_page_layout'], -1, PREG_SPLIT_NO_EMPTY);
foreach ($elements as $element) {
  if (preg_match("/(\w+),*(\d+)*/", $element, $matches)) {
    switch ($matches[1]) {
      case 'breadcrumb': 
        // Added breadcrumb as a separate listable block from config
        if ($breadcrumb != '' || count($cat_data) > 0) set_breadcrumb();//theme_display_breadcrumb($breadcrumb, $cat_data);
        break;

      case 'catlist':
        if (count($cat_data) > 0) theme_display_cat_list($breadcrumb, $cat_data, $statistics);
        if (isset($cat) && $cat == USER_GAL_CAT) list_users();
        break;

      case 'alblist':
        list_cat_albums($cat, false);
        break;

      case 'random':
        if ($cat != 1) display_thumbnails('random', '', $cat, 1, $CONFIG['thumbcols'], max(1, $matches[2]), false);
        break;

      case 'lastup':
        if ($cat != 1) display_thumbnails('lastup', '', $cat, 1, $CONFIG['thumbcols'], max(1, $matches[2]), false);
        break;

      case 'lastupby':
        if ($cat != 1 && USER_ID > 1) display_thumbnails('lastupby', '', $cat, 1, $CONFIG['thumbcols'], max(1, $matches[2]), false);
        break;

      case 'lastalb':
        if ($cat != 1) display_thumbnails('lastalb', '', $cat, 1, $CONFIG['thumbcols'], max(1, $matches[2]), false);
        break;

      case 'topn':
        if ($cat != 1) display_thumbnails('topn', '', $cat, 1, $CONFIG['thumbcols'], max(1, $matches[2]), false);
        break;

      case 'toprated':
        if ($cat != 1) display_thumbnails('toprated', '', $cat, 1, $CONFIG['thumbcols'], max(1, $matches[2]), false);
        break;

      case 'lastcom':
        if ($cat != 1)display_thumbnails('lastcom', '', $cat, 1, $CONFIG['thumbcols'], max(1, $matches[2]), false);
        break;

      case 'lastcomby':
        if ($cat != 1 && USER_ID > 1) display_thumbnails('lastcomby', '', $cat, 1, $CONFIG['thumbcols'], max(1, $matches[2]), false);
        break;

      case 'anycontent':
        require("$CPG_M_DIR/anycontent.php");
        break;

      case 'favpics':
        if (count($FAVPICS) > 0) {
          display_thumbnails('favpics', '', '', 1, count($FAVPICS), max(1, $matches[2]), false);
        }
        break;
    } 
  }
}

pagefooter();
?>
