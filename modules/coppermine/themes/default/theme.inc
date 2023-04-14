<?php
/***************************************************************************
   Coppermine Photo Gallery 1.3.1 for CPG-Nuke
  **************************************************************************
   Port Copyright (C) 2004-2005 Coppermine/CPG-Nuke Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 originally written by Grégory Demar
   Copyright (C) 2002,2003 http://coppermine.sf.net/team/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
  **************************************************************************
  Last modification notes:
  $Source: /public_html/modules/coppermine/themes/default/theme.inc,v $
  $Revision: 9.5 $
  $Author: nanocaiordo $
  $Date: 2007/04/06 12:35:47 $
****************************************************************************/
if (!defined('IN_COPPERMINE')) die('Not in Coppermine...');
global $template, $template_display_picture, $template_image_comments, $template_add_your_comment;
$modheader .= '<link rel="stylesheet" href="'.$THEME_DIR.'/style.css" />
<script type="text/javascript" src="'.$CPG_M_DIR.'/scripts.js"></script>
<script type="text/javascript" src="'.$CPG_M_DIR.'/jspw.js"></script>
<script type="text/javascript" src="includes/javascript/MM_funcs.js"></script>
';
// HTML template for the USER info box in the user list view
require($THEME_DIR."/user_list_info_box.inc"); //$template_user_list_info_box       index.php

// HTML template for the ALBUM admin menu displayed in the album list
require($THEME_DIR."/album_admin_menu.inc");   //$template_album_admin_menu         index.php

// HTML template for the image navigation bar
require($THEME_DIR."/img_navbar.inc");         //$template_img_navbar               displayimage.php

// HTML template for intermediate image display
require($THEME_DIR."/display_picture.inc");    //$template_display_picture          displayimage.php

// HTML template for the image rating box
require($THEME_DIR."/image_rating.inc");       //$template_image_rating             displayimage.php

// HTML template for the display of comments
$template_image_comments = eval_tmplfile("image_comments.html");     // displayimage.php
$template_add_your_comment = eval_tmplfile("add_your_comment.html"); // displayimage.php

// HTML template used by the cpg_die function
$template_cpg_die = "cpg_die.html";                                  // functions.inc

// HTML template used by the msg_box function
$template_msg_box = eval_tmplfile("msg_box.html");                   // functions.inc

// HTML template for e-cards
$template_ecard = eval_tmplfile("ecard.html");                       // displayecard.php

// HTML template for One picture editing
$template_edit_one_pic = "editonepic.inc";                       // editDesc.inc

// Template used for tabbed display
$template_tab_display = array(
                'left_text' => '<td align="left" valign="middle" class="tableh1_compact" style="white-space: nowrap"><b>{LEFT_TEXT}</b></td>'."\n",
                'tab_header' => '',
                'tab_trailer' => '',
                'active_tab' => '<td><img src="images/spacer.gif" alt="" width="1" height="1" /></td>'."\n".'<td align="center" valign="middle" class="tableb_compact"><b>%d</b></td>',
                'inactive_tab' => '<td><img src="images/spacer.gif" alt="" width="1" height="1" /></td>'."\n".'<td align="center" valign="middle" class="navmenu"><a href="{LINK}"<b>%d</b></a></td>'."\n"
);

// Function to start a 'standard' table
function starttable($width = '-1', $title='', $title_colspan='1')
{
        global $CONFIG;

        if ($width == '-1' )   $width = $CONFIG['picture_table_width'];
        if ($width == '100%' ) $width = $CONFIG['main_table_width'];
        if (defined('ADMIN_PAGES')) {
        echo <<<EOT

<!-- Start standard table -->
<table cellspacing="1" cellpadding="0" align="center" class="maintable">

EOT;
        } else {
        echo <<<EOT

<!-- Start standard table -->
<table width="$width" cellspacing="1" cellpadding="0" align="center" class="cpg_maintable">

EOT;
        }
        if ($title!='') {
        echo <<<EOT
        <tr>
                <td class="tableh1" colspan="$title_colspan">$title</td>
        </tr>

EOT;
        }
}

function endtable()
{
echo <<<EOT
</table>
<!-- End standard table -->
EOT;
}

function theme_main_menu()
{
    global $MAIN_CFG, $CONFIG, $THEME_DIR,$CPG_M_DIR, $CPG_M_URL, $CPG_URL, $album, $meta_link, $cat;

    //static $main_menu = '';

    //if ($main_menu != '') return $main_menu;
    //require($THEME_DIR . '/main_menu.inc');
    //$album_l = isset($album) ? "&album=$album" : "&album=$realalbum";
    //$cat_l = isset($cat) ? "&cat=$cat"  : (isset($cat) ? "&cat=$cat" : '');
    //$cat_l2 = isset($cat) ? "&cat=$cat" : -$album;
       //$cat_l = isset($cat) ? "&cat=$cat"  : (isset($cat) ? "&cat=$cat" : '');
    //$cat_l2 = isset($cat) ? "&cat=$cat" : -$album;
    $meta_link = META_LNK;
    $my_gallery_id = FIRST_USER_CAT + USER_ID;

    require($THEME_DIR . '/main_menu.inc');

//    if (USER_ID) {
        template_extract_block($template_main_menu, 'login');
//    } else {
        template_extract_block($template_main_menu, 'logout');
        template_extract_block($template_main_menu, 'my_profile');
//    }
/*
    
    if (GALLERY_ADMIN_MODE || USER_ADMIN_MODE) {
        template_extract_block($template_main_menu, 'enter_admin_mode');
    } elseif (USER_CAN_CREATE_ALBUMS || USER_IS_ADMIN) {
        template_extract_block($template_main_menu, 'leave_admin_mode');
    }

    if (!USER_CAN_CREATE_ALBUMS && !USER_IS_ADMIN) {
        template_extract_block($template_main_menu, 'enter_admin_mode');
        template_extract_block($template_main_menu, 'leave_admin_mode');
    }
*/
//    if ((!USER_CAN_CREATE_ALBUMS) || (!USER_ID && GALLERY_ADMIN_MODE) ){
        template_extract_block($template_main_menu, 'my_gallery');
//    }

    if (USER_ID || !USER_CAN_UPLOAD_PICTURES) {
        template_extract_block($template_main_menu, 'upload_pic');
    }

    if (USER_ID || !$MAIN_CFG['member']['allowuserreg']) {
        template_extract_block($template_main_menu, 'register');
    }

    return $template_main_menu;
}

function theme_admin_mode_menu()
{
    global $CPG_URL, $THEME_DIR, $cat, $album, $cat_l;

    $cat_l = is_numeric($album) ? "&album=$album" : (is_numeric($cat) ? "&cat=$cat" :'');
    //$cat_l =  (is_numeric($cat)) ? "&cat=$cat" :'';
    $html  = '';
    $userhtml ='';

    if (GALLERY_ADMIN_MODE) {
        require($THEME_DIR . '/admin_menu.inc');
    }elseif (USER_ADMIN_MODE) {
        require($THEME_DIR . '/admin_menu_user.inc');
    }
    return $html;
}

function theme_display_cat_list($breadcrumb, &$cat_data, $statistics)
{
    global $THEME_DIR, $CPG_M_DIR;
    require($THEME_DIR . '/cat_list.inc');

    starttable('100%');

    if (count($cat_data) > 0) {
        echo template_extract_block($template_cat_list, 'header');
    }

    $template_noabl = template_extract_block($template_cat_list, 'catrow_noalb');
    $template = template_extract_block($template_cat_list, 'catrow');
    foreach($cat_data as $category) {
        if (count($category) == 2) {
            $params = array(
                '{CAT_TITLE}' => $category[0],
                '{CAT_DESC}' => $category[1]
            );
            echo template_eval($template_noabl, $params);
        } elseif (isset($category['cat_albums']) && ($category['cat_albums'] != '')) {
            // Show category album
            $params = array(
                '{CAT_TITLE}' => $category[0],
                '{CAT_DESC}' => $category[1],
                '{CAT_ALBUMS}' => $category['cat_albums'],
                '{ALB_COUNT}' => $category[2],
                '{PIC_COUNT}' => $category[3],
            );
            echo template_eval($template, $params);
        } else {
            // empty category
            $params = array(
                '{CAT_TITLE}' => $category[0],
                '{CAT_DESC}' => $category[1],
                '{CAT_ALBUMS}' => '',
                '{ALB_COUNT}' => $category[2],
                '{PIC_COUNT}' => $category[3],
            );
            echo template_eval($template, $params);
        }
    }

    if ($statistics && count($cat_data)>0) {
        $template = template_extract_block($template_cat_list, 'footer');
        $params = array('{STATISTICS}' => $statistics);
        echo template_eval($template, $params);
    }
    endtable();

    if (count($cat_data)>0)
        echo template_extract_block($template_cat_list, 'spacer');
}

function theme_display_breadcrumb($breadcrumb, &$cat_data)
{
    global $THEME_DIR,$album;
    starttable('100%');
    if ($breadcrumb) {
        echo "        <tr>
                <td colspan=\"7\" class=\"tableh1\"><div class=\"statlink\">$breadcrumb</div></td>
        </tr>";
    }
    endtable();
}

function theme_display_album_list(&$alb_list,$nbAlb, $cat, $page, $total_pages)
{
    global $CONFIG, $STATS_IN_ALB_LIST, $CPG_URL, $CPG_M_URL, $statistics, $template_tab_display;

    $theme_alb_list_tab_tmpl = $template_tab_display;
    $theme_alb_list_tab_tmpl['left_text'] = strtr($theme_alb_list_tab_tmpl['left_text'],array('{LEFT_TEXT}' => ALBUM_ON_PAGE));
    $theme_alb_list_tab_tmpl['inactive_tab'] = strtr($theme_alb_list_tab_tmpl['inactive_tab'],array('{LINK}' => $CPG_M_URL.'&cat='.$cat.'&page=%d'));
    $tabs = create_tabs($nbAlb, $page, $total_pages, $theme_alb_list_tab_tmpl);

    $template_album_list = eval_tmplfile("album_list.html");
    $album_cell = template_extract_block($template_album_list, 'album_cell');
    $empty_cell = template_extract_block($template_album_list, 'empty_cell');
    $tabs_row = template_extract_block($template_album_list, 'tabs');
    $stat_row = template_extract_block($template_album_list, 'stat_row');
    $spacer = template_extract_block($template_album_list, 'spacer');
    $header = template_extract_block($template_album_list, 'header');
    $footer = template_extract_block($template_album_list, 'footer');
    $rows_separator = template_extract_block($template_album_list, 'row_separator');

    $count = 0;

    $columns = $CONFIG['album_list_cols'];
    $column_width = ceil(100/$columns);
    $thumb_cell_width = $CONFIG['alb_list_thumb_size']+2;

    starttable('100%');

    if ($STATS_IN_ALB_LIST) {
        $params = array(
                  '{STATISTICS}' => $statistics,
                  '{COLUMNS}' => $columns,
        );
        echo template_eval($stat_row, $params);
    }

    echo $header;

    if (is_array($alb_list)) {
        foreach($alb_list as $album) {
            $count ++;

            $params = array(
                        '{COL_WIDTH}' => $column_width,
                        '{ALBUM_TITLE}' => $album['album_title'],
                        '{THUMB_CELL_WIDTH}' => $thumb_cell_width,
                        '{ALB_LINK_TGT}' => getlink("&file=thumbnails&album={$album['aid']}"),
                        '{ALB_LINK_PIC}' => $album['thumb_pic'],
                        '{ADMIN_MENU}' => $album['album_adm_menu'],
                        '{ALB_DESC}' => $album['album_desc'],
                        '{ALB_INFOS}' => $album['album_info'],
            );
            echo template_eval($album_cell, $params);
            if ($count % $columns == 0 && $count < count($alb_list)) {
                        echo $rows_separator;
            }
        }
    }

    $params = array('{COL_WIDTH}' => $column_width);
    $empty_cell = template_eval($empty_cell, $params);
    while ($count++ % $columns != 0) {
        echo $empty_cell;
    }
    echo $footer;
    // Tab display
    $params = array(
        '{COLUMNS}' => $columns,
        '{TABS}' => $tabs,
    );
    echo template_eval($tabs_row, $params);

    endtable();

    echo $spacer;
}

// Function to display Albums
function theme_display_album_list_cat(&$alb_list, $nbAlb, $cat, $page, $total_pages)
{
    global $CONFIG, $STATS_IN_ALB_LIST, $CPG_URL, $CPG_M_URL, $statistics, $template_tab_display;
    if (!$CONFIG['first_level']) {
        return;
    }

    $theme_alb_list_tab_tmpl = $template_tab_display;
    $theme_alb_list_tab_tmpl['left_text'] = strtr($theme_alb_list_tab_tmpl['left_text'], array('{LEFT_TEXT}' => ALBUM_ON_PAGE));
    $theme_alb_list_tab_tmpl['inactive_tab'] = strtr($theme_alb_list_tab_tmpl['inactive_tab'], array('{LINK}' => $CPG_M_URL.'&cat=' . $cat . '&page=%d'));
    $tabs = create_tabs($nbAlb, $page, $total_pages, $theme_alb_list_tab_tmpl);

    $template_album_list_cat = eval_tmplfile("album_list_cat.html");
    $album_cell = template_extract_block($template_album_list_cat, 'c_album_cell');
    $empty_cell = template_extract_block($template_album_list_cat, 'c_empty_cell');
    $tabs_row = template_extract_block($template_album_list_cat, 'c_tabs');
    $stat_row = template_extract_block($template_album_list_cat, 'c_stat_row');
    $header = template_extract_block($template_album_list_cat, 'c_header');
    $footer = template_extract_block($template_album_list_cat, 'c_footer');
    $rows_separator = template_extract_block($template_album_list_cat, 'c_row_separator');

    $count = 0;

    $columns = $CONFIG['album_list_cols'];
    $column_width = ceil(100 / $columns);
    $thumb_cell_width = $CONFIG['alb_list_thumb_size'] + 2;

    echo $header;

    if ($STATS_IN_ALB_LIST) {
        $params = array('{STATISTICS}' => $statistics,
            '{COLUMNS}' => $columns,
            );
        echo template_eval($stat_row, $params);
    }

    if (is_array($alb_list)) {
        foreach($alb_list as $album) {
            $count ++;

            $params = array('{COL_WIDTH}' => $column_width,
                '{ALBUM_TITLE}' => $album['album_title'],
                '{THUMB_CELL_WIDTH}' => $thumb_cell_width,
                '{ALB_LINK_TGT}' => getlink("&amp;file=thumbnails&album={$album['aid']}"),
                '{ALB_LINK_PIC}' => $album['thumb_pic'],
                '{ADMIN_MENU}' => $album['album_adm_menu'],
                '{ALB_DESC}' => $album['album_desc'],
                '{ALB_INFOS}' => $album['album_info'],
                );

            echo template_eval($album_cell, $params);

            if ($count % $columns == 0 && $count < count($alb_list)) {
                echo $rows_separator;
            }
        }
    }

    $params = array('{COL_WIDTH}' => $column_width);
    $empty_cell = template_eval($empty_cell, $params);

    while ($count++ % $columns != 0) {
        echo $empty_cell;
    }

    echo $footer;

    // Tab display
    $params = array(
        '{COLUMNS}' => $columns,
        '{TABS}' => $tabs,
    );
    echo template_eval($tabs_row, $params);

    endtable();
}

function theme_display_thumbnails(&$thumb_list, $nbThumb, $album_name, $page_link, $page, $total_pages, $sort_options, $display_tabs, $mode='thumb')
{
    global $CONFIG, $CPG_URL, $CPG_M_URL,$THEME_DIR;
    global $template_tab_display;

    static $header='';
    static $thumb_cell='';
    static $empty_cell='';
    static $row_separator='';
    static $footer='';
    static $tabs='';
    static $spacer='';

    if ($header == '') {
        $template_thumbnail_view = eval_tmplfile("thumbnail_view.html");
        $thumb_cell = template_extract_block($template_thumbnail_view, 'thumb_cell');
        $tabs = template_extract_block($template_thumbnail_view, 'tabs');
        $header = template_extract_block($template_thumbnail_view, 'header');
        $empty_cell = template_extract_block($template_thumbnail_view, 'empty_cell');
        $row_separator = template_extract_block($template_thumbnail_view, 'row_separator');
        $footer = template_extract_block($template_thumbnail_view, 'footer');
        $spacer = template_extract_block($template_thumbnail_view, 'spacer');
    }

    $theme_thumb_tab_tmpl = $template_tab_display;
    if ($mode == 'thumb') {
        $theme_thumb_tab_tmpl['left_text'] = strtr($theme_thumb_tab_tmpl['left_text'],array('{LEFT_TEXT}' => PIC_ON_PAGE));
    } else {
        $theme_thumb_tab_tmpl['left_text'] = strtr($theme_thumb_tab_tmpl['left_text'],array('{LEFT_TEXT}' => USER_ON_PAGE));
    }
    $theme_thumb_tab_tmpl['inactive_tab'] = strtr($theme_thumb_tab_tmpl['inactive_tab'],array('{LINK}' => $page_link));

    $thumbcols = $CONFIG['thumbcols'];
    $cell_width = ceil(100/$CONFIG['thumbcols']).'%';

    $tabs_html = $display_tabs ? create_tabs($nbThumb, $page, $total_pages, $theme_thumb_tab_tmpl) : '';

    // The sort order options are not available for meta albums
    if ($sort_options) {
        require($THEME_DIR."/thumb_view_title_row.inc");
        $title = $template_thumb_view_title_row;
    }
    // else $title = '<h2>'.$album_name.'</h2>';
    else $title = '<p class="statlink">'.$album_name.'</p>';
    if ($mode == 'thumb') starttable('100%', $title, $thumbcols);
    else starttable('100%');

    echo $header;

    $i = 0;
    foreach($thumb_list as $thumb){
        $i++;
        $params['{CELL_WIDTH}'] = $cell_width;
        $params['{LINK_TGT}'] = $thumb['url'];
        $params['{THUMB}'] = $thumb['image'];
        $params['{CAPTION}'] = $thumb['caption'];
        $params['{ADMIN_MENU}'] = ($mode == 'thumb') ? $thumb['admin_menu'] : '';
        echo template_eval($thumb_cell, $params);
        if ((($i % $thumbcols) == 0) && ($i < count($thumb_list))) echo $row_separator;
    }
    for (;($i % $thumbcols); $i++){
        echo $empty_cell;
    }
    echo $footer;

    if ($display_tabs) {
        $params = array(
            '{THUMB_COLS}' => $thumbcols,
            '{TABS}' => $tabs_html
        );
        echo template_eval($tabs, $params);
    }

    endtable();
    echo $spacer;
}

// Added to display film_strip
function theme_display_film_strip(&$thumb_list)
{
    global $CONFIG, $CPG_URL, $CPG_M_URL,$THEME_DIR;
    static $template = '';
    static $thumb_cell = '';
    static $empty_cell = '';
    static $spacer = '';
    if (!$template) {
        $template = eval_tmplfile("film_strip.html");
        $thumb_cell = template_extract_block($template, 'thumb_cell');
        $empty_cell = template_extract_block($template, 'empty_cell');
    }
    $thumbcols = $CONFIG['thumbcols'];
    $cell_width = ceil(100 / $CONFIG['max_film_strip_items']) . '%';
    $i = 0;
    $thumb_strip = '';
    foreach($thumb_list as $thumb) {
        $i++;
        $params['{CELL_WIDTH}'] = $cell_width;
        $params['{LINK_TGT}'] = $thumb['url'];
        $params['{THUMB}'] = $thumb['image'];
        $params['{CAPTION}'] = '';
        $params['{ADMIN_MENU}'] = '';
        $thumb_strip .= template_eval($thumb_cell, $params);
    }
    $params = array(
        '{THUMB_STRIP}' => $thumb_strip, 
        '{COLS}' => $i,
        '{TILE_IMAGE}' => $THEME_DIR.'/images/tile.gif' 
    );
    //ob_start();
    //echo template_eval($template, $params);
    //$film_strip = ob_get_contents();
    //ob_end_clean();
    $film_strip = template_eval($template, $params);
    return $film_strip;
}

function theme_no_img_to_display($album_name)
{
    starttable('100%', $album_name);
    echo '
        <tr>
                <td class="tableb" height="200" align="center">
                        <font size="3"><b>'.NO_IMG_TO_DISPLAY.'</b></font>
                </td>
        </tr>';
    endtable();
}

function theme_display_image($nav_menu, $picture, $votes, $pic_info, $comments, $film_strip)
{
        global $HTTP_COOKIE_VARS, $CONFIG;
        starttable();
        echo $nav_menu;
        endtable();

        starttable();
        echo $picture;
        endtable();

        if ($CONFIG['display_film_strip'] == 1) echo $film_strip;

        starttable();
        echo $votes;
        endtable();

        $picinfo = isset($HTTP_COOKIE_VARS['picinfo']) ? $HTTP_COOKIE_VARS['picinfo'] : ($CONFIG['display_pic_info'] ? 'block' : 'none');
        echo "<div id=\"picinfo\" style=\"display: $picinfo;\">\n";

        starttable();
        echo $pic_info;
        endtable();

        echo "</div>\n";

        starttable();
        echo $comments;
        endtable();
}
function theme_html_picinfo(&$info)
{
        $html = '<tr><td colspan="2" class="tableh2_compact"><b>'.PIC_INF_TITLE.'</b></td></tr>';
        $template ="        <tr><td class=\"tableb_compact\" valign=\"top\" nowrap=\"nowrap\">%s:</td><td class=\"tableb_compact\">%s</td></tr>\n";
        foreach ($info as $key => $value) $html .= sprintf($template, $key, $value);
        return $html;
}