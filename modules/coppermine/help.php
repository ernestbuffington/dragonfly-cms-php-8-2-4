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
  $Source: /public_html/modules/coppermine/help.php,v $
  $Revision: 9.2 $
  $Author: djmaze $
  $Date: 2005/03/24 01:52:37 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

define('HELP_PHP', true);
require("modules/" . $module_name . "/include/load.inc");
if (file_exists("$CPG_M_DIR/include/help-$currentlang.inc")) {
    require_once("$CPG_M_DIR/include/help-$currentlang.inc");
} else {
    require_once("$CPG_M_DIR/include/help-english.inc");
}
$TMPCONFIG = $CONFIG;
require_once("header.php");
$CONFIG = $TMPCONFIG;
//define('META_LNK','&cat=0');
print'<link rel="stylesheet" href="modules/' . $module_name . '/docs/styles.css" type="text/css">';
pageheader('Help',false);
OpenTable(); 
print'<p class="doc_head doc_title" align="center">Coppermine Photo CMS Documentation</p>';
set_breadcrumb(1);

$template_help_overview = '
<!-- BEGIN adminsum -->
<p class="doc_desc" align="left">' . GTDOC_ADMIN_SUMMARY . ' </p>
<!-- END adminsum -->
<!-- BEGIN usersum -->
<p class="doc_desc" align="left">' . GTDOC_USER_SUMMARY . ' </p>
<!-- END usersum -->
<!-- BEGIN anonsum -->
<p class="doc_desc" align="left">' . GTDOC_ANON_SUMMARY . ' </p>
<!-- END anonsum -->

<!-- BEGIN sitead_overviewdesc -->
<p class="doc_head doc_title" align="left">' . GTDOC_SITEADM_OVERVIEW_DESC . ' </p>
<!-- END sitead_overviewdesc -->
<!-- BEGIN overviewtitle -->
<p class="doc_title" align="left">' . GTDOC_OVERVIEW_TITLE . ' </p>
<!-- END overviewtitle -->

<!-- BEGIN sitead_overviewdesc -->
<p class="doc_desc" align="left">' . GTDOC_SITEADM_OVERVIEW_DESC . '</p>
<!-- END sitead_overviewdesc -->

<!-- BEGIN gal_admin_overviewdesc -->
<p class="doc_desc" align="left">' . GTDOC_ADM_OVERVIEW_DESC . '</p>
<!-- END gal_admin_overviewdesc -->

<!-- BEGIN canup_overviewdesc -->
<p class="doc_desc" align="left">' . GTDOC_CANUP_USER_OVERVIEW_DESC . '</p>
<!-- END canup_overviewdesc -->

<!-- BEGIN all_overviewdesc -->
<p class="doc_desc" align="left">' . GTDOC_USER_OVERVIEW_DESC . '</p>
<!-- END all_overviewdesc -->
<!-- BEGIN alblist -->
<p class="doc_title" align="left">' . GTDOC_ALBLIST_TITLE . ' </p>
<p class="doc_desc" align="left">' . GTDOC_ALBLIST_DESC . '</p>
<!-- END alblist -->
<!-- BEGIN my_gal_link -->
<p class="doc_title" align="left">' . GTDOC_MY_GAL_LNK_TITLE . ' </p>
<p class="doc_desc" align="left">' . GTDOC_MY_GAL_LNK_DESC . '</p>
<!-- END my_gal_link -->
<!-- BEGIN upload_title -->
<p class="doc_title" align="left">' . GTDOC_USERUPLOAD_TITLE . '</p>
<!-- END upload_title -->
<!-- BEGIN sitead_upload_desc -->
<p class="doc_desc" align="left">' . GTDOC_SITEADM_USERUPLOAD_DESC . '</p>
<!-- END sitead_upload_desc -->
<!-- BEGIN ad_upload_desc -->
<p class="doc_desc" align="left">' . GTDOC_ADM_USERUPLOAD_DESC . '</p>
<!-- END ad_upload_desc -->
<!-- BEGIN canug_upload_desc -->
<p class="doc_desc" align="left">' . GTDOC_CANUG_USER_USERUPLOAD_DESC . '</p>
<!-- END canug_upload_desc -->
<!-- BEGIN user_upload_desc -->
<p class="doc_desc" align="left">' . GTDOC_USER_USERUPLOAD_DESC . '</p>
<!-- END user_upload_desc -->
<!-- BEGIN doc_gen_lnks -->
<!-- BEGIN doc_login -->
<p class="doc_title" align="left">' . GTDOC_LOGIN_LNK_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_LOGIN_LNK_DESC . '</p>
<!-- END doc_login -->
<!-- BEGIN doc_logout -->
<p class="doc_title" align="left">' . GTDOC_LOGOUT_LNK_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_LOGOUT_LNK_DESC . '</p>
<!-- END doc_logout -->
<!-- BEGIN meta_title -->
<p class="doc_title" align="left">' . GTDOC_META_LNK_TITLE . '</p>
<!-- END meta_title -->
<!-- BEGIN meta_desc -->
<p class="doc_desc" align="left">' . GTDOC_META_LNK_DESC . '</p>
<!-- END meta_desc -->
<!-- BEGIN search_lnk_title -->
<p class="doc_title" align="left">' . GTDOC_SEARCH_LNK_TITLE . '</p>
<!-- END search_lnk_title -->
<!-- BEGIN search_lnk_desc -->
<p class="doc_desc" align="left">' . GTDOC_SEARCH_LNK_DESC . '</p>
<!-- END search_lnk_desc -->
<!-- BEGIN fav_lnk_title -->
<p class="doc_title" align="left">' . GTDOC_FAV_LNK_TITLE . '</p>
<!-- END fav_lnk_title -->
<!-- BEGIN fav_lnk_desc -->
<p class="doc_desc" align="left">' . GTDOC_FAV_LNK_DESC . '</p>
<!-- END fav_lnk_desc -->
<!-- BEGIN admin_lnks -->
<p class="doc_head doc_title" align="left">' . GTDOC_ADMIN_FUNC_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_ADMIN_FUNC_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_UPLOAD_APP_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_UPLOAD_APP_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_BATCH_AD_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_BATCH_AD_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_REVEIW_COM_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_REVEIW_COM_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_BANUSERS_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_BANUSERS_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_GROUPCP_TITLE . ' <a  target="new" href="' . $CPG_M_DIR . '/docs/images/group_cp.gif" width="843: height="305">' . GTDOC_GROUPCP_TITLE . '</a></p>

<p class="doc_desc" align="left">' . GTDOC_GROUPCP_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_USER_MGR_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_USER_MGR_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_CREATE_ORDER_ALBUM_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_CREATE_ORDER_ALBUM_DESC . '</p>
<!--<p class="doc_title" align="left">' . GTDOC_MENU_UPDATE_ALERT_TITLE . '</p> -->
<!--<p class="doc_desc" align="left">' . GTDOC_UPDATE_ALERT_DESC . '</p> -->
<p class="doc_title" align="left">' . GTDOC_MODALBUM_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MODALBUM_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_ALBUMPROPS_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_ALBUMPROPS_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PERMISSIONS_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_SITEADM_PERMISSIONS_DESC. '</p>

<!-- END admin_lnks -->
<!-- BEGIN user_admin_lnks -->
<p class="doc_head doc_title" align="left">' . GTDOC_ADMIN_FUNC_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_ADMIN_FUNC_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_USER_CREATE_ORDER_ALBUM_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_USER_CREATE_ORDER_ALBUM_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_USER_ALBUMPROPS_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_USER_ALBUMPROPS_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_MY_PROFILE_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MY_PROFILE_DESC . '</p>
<!-- END user_admin_lnks -->
';
function doc_overview()
{
    global $template_help_overview;
    $template = eval_tmplfile($template_help_overview, false);
    if (is_admin()) {
        echo template_extract_block($template, 'adminsum');
        echo template_extract_block($template, 'overviewtitle');
        echo template_extract_block($template, 'sitead_overviewdesc');
        CloseTable();
        OpenTable();
        echo template_extract_block($template, 'alblist');
        echo template_extract_block($template, 'my_gal_link');
        echo template_extract_block($template, 'upload_title');
        echo template_extract_block($template, 'sitead_upload_desc');
           echo template_extract_block($template, 'meta_title');
        echo template_extract_block($template, 'meta_desc');
        echo template_extract_block($template, 'fav_lnk_title');
        echo template_extract_block($template, 'fav_lnk_desc');
        echo template_extract_block($template, 'search_lnk_title');
        echo template_extract_block($template, 'search_lnk_desc');
        echo template_extract_block($template, 'doc_login');
        echo template_extract_block($template, 'doc_logout');
        CloseTable();
        OpenTable();
        //echo template_extract_block($template, 'admin_mode_link_title');
        //echo template_extract_block($template, 'site_admin_mode_link_desc');
        echo template_extract_block($template, 'admin_lnks');
        CloseTable();
        } elseif (GALLERY_ADMIN_MODE || USER_IS_ADMIN) {
        echo template_extract_block($template, 'adminsum');
        echo template_extract_block($template, 'overviewtitle');
        echo template_extract_block($template, 'gal_admin_overviewdesc');
        CloseTable();
        OpenTable();
        echo template_extract_block($template, 'alblist');
        echo template_extract_block($template, 'my_gal_link');
        echo template_extract_block($template, 'upload_title');
        echo template_extract_block($template, 'canug_upload_desc');
        echo template_extract_block($template, 'meta_title');
        echo template_extract_block($template, 'meta_desc');
        echo template_extract_block($template, 'fav_lnk_title');
        echo template_extract_block($template, 'fav_lnk_desc');
        echo template_extract_block($template, 'search_lnk_title');
        echo template_extract_block($template, 'search_lnk_desc');
        //echo template_extract_block($template, 'ad_mode_link_desc');
        //echo template_extract_block($template, 'ad_upload_desc');
        echo template_extract_block($template, 'doc_login');
        echo template_extract_block($template, 'doc_logout');
        CloseTable();
        OpenTable();
        //echo template_extract_block($template, 'ad_mode_link_desc');
        //echo template_extract_block($template, 'admin_mode_link_title');
        echo template_extract_block($template, 'admin_lnks');
        CloseTable();
        } elseif (USER_CAN_CREATE_ALBUMS || USER_ADMIN_MODE) {
        echo template_extract_block($template, 'usersum');
        echo template_extract_block($template, 'overviewtitle');
        echo template_extract_block($template, 'canup_overviewdesc');
        CloseTable();
        OpenTable();
        echo template_extract_block($template, 'alblist');
        echo template_extract_block($template, 'my_gal_link');
        echo template_extract_block($template, 'doc_login');
        echo template_extract_block($template, 'doc_logout');
        echo template_extract_block($template, 'meta_title');
        echo template_extract_block($template, 'meta_desc');
        echo template_extract_block($template, 'fav_lnk_title');
        echo template_extract_block($template, 'fav_lnk_desc');
        echo template_extract_block($template, 'search_lnk_title');
        echo template_extract_block($template, 'search_lnk_desc');
        //echo template_extract_block($template, 'admin_mode_link_title');
        //echo template_extract_block($template, 'user_admin_mode_link_desc');
        echo template_extract_block($template, 'upload_title');
        echo template_extract_block($template, 'canug_upload_desc');
        CloseTable();
        OpenTable();
        echo template_extract_block($template, 'user_admin_lnks');
        CloseTable();
    } else {
        echo template_extract_block($template, 'anonsum');
        echo template_extract_block($template, 'overviewtitle');
        echo template_extract_block($template, 'all_overviewdesc');
        CloseTable();
        OpenTable();
        echo template_extract_block($template, 'alblist');
        echo template_extract_block($template, 'upload_title');
        echo template_extract_block($template, 'user_upload_desc');
        echo template_extract_block($template, 'doc_login');
        echo template_extract_block($template, 'doc_logout');
        echo template_extract_block($template, 'meta_title');
        echo template_extract_block($template, 'meta_desc');
        echo template_extract_block($template, 'fav_lnk_title');
        echo template_extract_block($template, 'fav_lnk_desc');
        echo template_extract_block($template, 'search_lnk_title');
        echo template_extract_block($template, 'search_lnk_desc');
        CloseTable();
    } 
} 
$template_config = '
<!-- BEGIN doc_config_title -->
<p class="doc_title" align="left">' . GTDOC_CONFIG_TITLE . ' </p>
<p class="doc_desc" align="left">' . GTDOC_CONFIG_DESC . '</p>
<!-- END doc_config_title -->
<!-- BEGIN default_config_title -->
<p class="doc_title" align="left">' . GTDOC_USER_CONFIG_TITLE . ' </p>
<!-- END default_config_title -->
<!-- BEGIN doc_config_desc -->
<!-- BEGIN gen_settings -->
<p class="doc_head doc_title" align="left">' . GTDOC_GENSET_TITLE . '</p>
<p class="doc_title" align="left">' . GTDOC_GAL_NAME_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_GAL_NAME_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_GAL_DESC_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_GAL_DESC_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_GAL_EMAIL_TITLE . ' </p>
<p class="doc_desc" align="left">' . GTDOC_GAL_EMAIL_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_HOME_LINK_TITLE . ' </p>
<p class="doc_desc" align="left">' . GTDOC_HOME_LINK_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_LANG_TITLE . ' </p>
<p class="doc_desc" align="left">' . GTDOC_LANG_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_THEME_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_THEME_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_NICE_TITLES_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_NICE_TITLES_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_RIGHTBLOCKS_TITLE. '</p>
<p class="doc_desc" align="left">' . GTDOC_RIGHTBLOCKS_DESC. '</p>
<!-- END gen_settings -->
<!-- BEGIN album_list_view_settings -->
<p class="doc_head doc_title" align="left">' . GTDOC_ALBUM_LIST_TITLE . '</p>
<p class="doc_title" align="left">' . GTDOC_MAIN_TITLE_WIDTH_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MAIN_TITLE_WIDTH_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_SUBCAT_LEVEL_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_SUBCAT_LEVEL_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_ALBUMS_PER_PAGE_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_ALBUMS_PER_PAGE_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_ALB_LIST_COLS_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_ALB_LIST_COLS_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_ALBLIST_THUMB_SIZE_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_ALBLIST_THUMB_SIZE_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_MAIN_CONT_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MAIN_CONT_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_FIRST_LEVEL_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_FIRST_LEVEL_DESC . '</p>
<!-- END album_list_view_settings -->
<!-- BEGIN thumbnail_view_settings -->
<p class="doc_head doc_title" align="left">' . GTDOC_THUMBVIEW_TITLE. '</p>
<p class="doc_title" align="left">' . GTDOC_THUMBCOLS_TITLE. '</p>
<p class="doc_desc" align="left">' . GTDOC_THUMBCOLS_DESC. '</p>
<p class="doc_title" align="left">' . GTDOC_THUMBROWS_TITLE. '</p>
<p class="doc_desc" align="left">' . GTDOC_THUMBROWS_DESC. '</p>
<p class="doc_title" align="left">' . GTDOC_MAX_TABS_TITLE. '</p>
<p class="doc_desc" align="left">' . GTDOC_MAX_TABS_DESC. '</p>
<p class="doc_title" align="left">' . GTDOC_CAPTION_THUMBVIEW_TITLE. '</p>
<p class="doc_desc" align="left">' . GTDOC_CAPTION_THUMBVIEW_DESC. '</p>
<!-- END thumbnail_view_settings -->

<!-- BEGIN config_image_comment_settings -->
<p class="doc_head doc_title" align="left">' . GTDOC_IMAGE_COMMENT_TITLE . '</p>
<p class="doc_title" align="left">' . GTDOC_DISP_PICINFO_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_DISP_PICINFO_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_BADWORDS_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_BADWORDS_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_SMILIES_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_SMILIES_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_NO_FLOOD_COM_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_NO_FLOOD_COM_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_COM_EMAIL_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_COM_EMAIL_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_MAX_DESC_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MAX_DESC_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_MAX_COM_WLENGTH_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MAX_COM_WLENGTH_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_MAX_COM_LINES_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MAX_COM_LINES_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_MAX_COM_CHARS_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MAX_COM_CHARS_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_FILM_STRIP_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_FILM_STRIP_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_FILM_FRAMES_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_FILM_FRAMES_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_ANON_FULL_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_ANON_FULL_DESC . '</p>
<!-- END config_image_comment_settings -->

<!-- BEGIN pictures_thumbnails_settings -->
<p class="doc_head doc_title" align="left">' . GTDOC_PIC_THUMB_TITLE . '</p>
<p class="doc_title" align="left">' . GTDOC_JPEG_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_JPEG_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_THUMBW_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_THUMBW_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_THUMBU_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_THUMBU_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_INTERM_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_INTERM_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PWIDTH_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PWIDTH_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_MAXUPSIZE_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MAXUPSIZE_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_MAXUPW_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_MAXUPW_DESC . '</p>
<!-- END pictures_thumbnails_settings -->

<!-- BEGIN config_user_settings -->
<p class="doc_head doc_title" align="left">' . GTDOC_USER_SET_TITLE . '</p>
<p class="doc_title" align="left">' . GTDOC_USEREG_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_USEREG_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_USEREGM_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_USEREGM_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_USEML_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_USEML_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PRVALB_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PRVALB_DESC . '</p>
<!-- END config_user_settings -->
<!-- BEGIN config_user_fields -->
<p class="doc_head doc_title" align="left">' . GTDOC_USRFLDS_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_USRFLDS_DESC . '</p>
<!-- END config_user_fields -->
<!-- BEGIN pic_thumb_adv_desc -->
<p class="doc_head doc_title" align="left">' . GTDOC_PIC_THUMB_ADV_TITLE . '</p>
<p class="doc_title" align="left">' . GTDOC_PRIVICON_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PRIVICON_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_FCHAR_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_FCHAR_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_FEX_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_FEX_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_TMBM_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_TMBM_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_IPATH_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_IPATH_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_FEX_TITLE2 . '</p>
<p class="doc_desc" align="left">' . GTDOC_FEX_DESC2 . '</p>
<p class="doc_title" align="left">' . GTDOC_IMOPT_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_IMOPT_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_EXIF_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_EXIF_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_IPTC_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_IPTC_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_FPATH_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_FPATH_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_UPICS_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_UPICS_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PFNAME_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PFNAME_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PANAME_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PANAME_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PFSIZE_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PFSIZE_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PDIM_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PDIM_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PCNT_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PCNT_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PURL_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PURL_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PBKMK_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PBKMK_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_PFAV_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_PFAV_DESC . '</p>
<!-- BEGIN doc_cookie_desc -->
<p class="doc_head doc_title" align="left">' . GTDOC_COOKIE_TITLE . '</p>
<p class="doc_title" align="left">' . GTDOC_CNAME_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_CNAME_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_CPATH_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_CPATH_DESC . '</p>
<p class="doc_title" align="left">' . GTDOC_CHARSET_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_CHARSET_DESC . '</p>
<!-- END doc_cookie_desc -->
<!-- BEGIN doc_debug_desc -->
<p class="doc_head doc_title" align="left">' . GTDOC_DEBUG_TITLE . '</p>
<p class="doc_desc" align="left">' . GTDOC_DEBUG_DESC . '</p>
<!-- END doc_debug -->
<!-- END doc_config_desc -->
';
function doc_config()
{
    global $template_config;
    $template = eval_tmplfile($template_config, false);
    if (is_admin()) {
        echo template_extract_block($template, 'doc_config_title');
        echo template_extract_block($template, 'doc_config_desc');
    } else {
        echo template_extract_block($template, 'default_config_title'); //if function is called by another page
    } 
} 
doc_overview();
if (GALLERY_ADMIN_MODE) {
    OpenTable();
    doc_config();
    CloseTable();
        
        
}
print'<p class="doc_desc" align="center">Copyleft 2003-4<br />written by gtroll and moorey of the <a href="http://coppermine.sourceforge.net/team/"> Coppermine Dev Crew </a></p>';
pagefooter();

?>
