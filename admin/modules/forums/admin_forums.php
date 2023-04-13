<?php
/***************************************************************************
 *                 admin_forums.php
 *                -------------------
 *   begin        : Thursday, Jul 12, 2001
 *   copyright        : (C) 2001 The phpBB Group
 *   email        : support@phpbb.com
 *
 ***************************************************************************/
/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ************************************************************************/
/* Modifications made by CPG Dev Team http://cpgnuke.com                */
/* Last modification notes:                                             */
/*                                                                      */
/*   $Id: admin_forums.php,v 9.9 2007/04/16 08:56:45 nanocaiordo Exp $  */
/*                                                                      */
/************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
include("includes/phpBB/functions_admin.php");

$forum_auth_ary = array(
    "auth_view" => AUTH_ALL,
    "auth_read" => AUTH_ALL,
    "auth_post" => AUTH_ALL,
    "auth_reply" => AUTH_ALL,
    "auth_edit" => AUTH_REG,
    "auth_delete" => AUTH_REG,
    "auth_sticky" => AUTH_MOD,
    "auth_announce" => AUTH_MOD,
    "auth_vote" => AUTH_REG,
    "auth_pollcreate" => AUTH_REG
);

//if (defined('BBAttach_mod')) {
    $forum_auth_ary['auth_attachments'] = AUTH_REG;
    $forum_auth_ary['auth_download'] = AUTH_REG;
//
// Mode setting
//
if( isset($_POST['mode']) || isset($_GET['mode']) ) {
    $mode = $_POST['mode'] ?? $_GET['mode'];
    $mode = htmlprepare($mode);
} else {
    $mode = "";
}
$show_index = FALSE;
// ------------------
// Begin function block
//
function get_info($mode, $id)
{
    global $db;

    switch($mode)
    {
        case 'category':
            $table = CATEGORIES_TABLE;
            $idfield = 'cat_id';
            $namefield = 'cat_title';
            break;

        case 'forum':
            $table = FORUMS_TABLE;
            $idfield = 'forum_id';
            $namefield = 'forum_name';
            break;

        default:
            message_die(GENERAL_ERROR, "Wrong mode for generating select list", "", __LINE__, __FILE__);
            return;
            break;
    }
    $result = $db->sql_query("SELECT count(*) as total FROM $table");
    $count = $db->sql_fetchrow($result);
    $count = $count['total'];

    $result = $db->sql_query("SELECT * FROM $table WHERE $idfield = $id");
    if ( $db->sql_numrows($result) != 1 ) {
        message_die(GENERAL_ERROR, "Forum/Category doesn't exist or multiple forums/categories with ID $id", "", __LINE__, __FILE__);
        return;
    }

    $return = $db->sql_fetchrow($result);
    $return['number'] = $count;
    return $return;
}

function get_list($mode, $id, $select)
{
    global $db;

    switch($mode)
    {
        case 'category':
            $table = CATEGORIES_TABLE;
            $idfield = 'cat_id';
            $namefield = 'cat_title';
            break;

        case 'forum':
            $table = FORUMS_TABLE;
            $idfield = 'forum_id';
            $namefield = 'forum_name';
            break;

        default:
            message_die(GENERAL_ERROR, "Wrong mode for generating select list", "", __LINE__, __FILE__);
            return;
            break;
    }

    $sql = "SELECT * FROM $table";
    if( $select == 0 ) {
        $sql .= " WHERE $idfield <> $id";
    }
    if( !$result = $db->sql_query($sql) ) {
        message_die(GENERAL_ERROR, "Couldn't get list of Categories/Forums", "", __LINE__, __FILE__, $sql);
        return;
    }

    $catlist = "";

    while( $row = $db->sql_fetchrow($result) ) {
        $s = "";
        if ($row[$idfield] == $id) {
            $s = " selected=\"selected\"";
        }
        $catlist .= "<option value=\"$row[$idfield]\"$s>" . $row[$namefield] . "</option>\n";
    }

    return($catlist);
}

function renumber_order($mode, $cat = 0)
{
    $catfield = null;
    global $db;

    switch($mode)
    {
        case 'category':
            $table = CATEGORIES_TABLE;
            $idfield = 'cat_id';
            $orderfield = 'cat_order';
            $cat = 0;
            break;

        case 'forum':
            $table = FORUMS_TABLE;
            $idfield = 'forum_id';
            $orderfield = 'forum_order';
            $catfield = 'cat_id';
            break;

        default:
            message_die(GENERAL_ERROR, "Wrong mode for generating select list", "", __LINE__, __FILE__);
            return;
            break;
    }

    $sql = "SELECT * FROM $table";
    if( $cat != 0) {
        $sql .= " WHERE $catfield = $cat";
    }
    $sql .= " ORDER BY $orderfield ASC";
    if( !$result = $db->sql_query($sql) ) {
        message_die(GENERAL_ERROR, "Couldn't get list of Categories", "", __LINE__, __FILE__, $sql);
        return;
    }

    $i = 10;
    $inc = 10;

    while( $row = $db->sql_fetchrow($result) ) {
        $db->sql_query("UPDATE $table SET $orderfield = $i WHERE $idfield = " . $row[$idfield]);
        $i += 10;
    }

}
//
// End function block
// ------------------

//
// Begin program proper
if( isset($_POST['addforum']) || isset($_POST['addcategory']) )
{
  $mode = ( isset($_POST['addforum']) ) ? "addforum" : "addcat";
  if( $mode == "addforum" )
  {
    foreach (array_keys($_POST['addforum']) as $cat_id)
    $cat_id = intval($cat_id);
    // stripslashes needs to be run on this because slashes are added when the forum name is posted
    $forumname = stripslashes($_POST['forumname'][$cat_id]);
   }
}

if( !empty($mode) ) {
    switch($mode)
    {
        case 'addforum':
        case 'editforum':
            //
            // Show form to create/modify a forum
            //
            if ($mode == 'editforum')
            {
                // $newmode determines if we are going to INSERT or UPDATE after posting?

                $l_title = $lang['Edit_forum'];
                $newmode = 'modforum';
                $buttonvalue = $lang['Update'];

                $forum_id = intval($_GET[POST_FORUM_URL]);

                $row = get_info('forum', $forum_id);

                $cat_id = $row['cat_id'];
                $forumname = $row['forum_name'];
                $forumdesc = $row['forum_desc'];
                $forumstatus = $row['forum_status'];

                //
                // start forum prune stuff.
                //
                if( $row['prune_enable'] )
                {
                    $prune_enabled = "checked=\"checked\"";
                    $pr_result = $db->sql_query("SELECT * FROM " . PRUNE_TABLE . " WHERE forum_id = $forum_id");
                    $pr_row = $db->sql_fetchrow($pr_result);
                } else {
                    $prune_enabled = '';
                }
            } else {
                $l_title = $lang['Create_forum'];
                $newmode = 'createforum';
                $buttonvalue = $lang['Create_forum'];

                $forumdesc = '';
                $forumstatus = FORUM_UNLOCKED;
                $forum_id = '';
                $prune_enabled = '';
            }

            $catlist = get_list('category', $cat_id, TRUE);
            $forumlocked ='';
            $forumstatus == ( FORUM_LOCKED ) ? $forumlocked = "selected=\"selected\"" : $forumunlocked = "selected=\"selected\"";

            // These two options ($lang['Status_unlocked'] and $lang['Status_locked']) seem to be missing from
            // the language files.
            $lang['Status_unlocked'] ??= 'Unlocked';
            $lang['Status_locked'] ??= 'Locked';

            $statuslist = "<option value=\"" . FORUM_UNLOCKED . "\" $forumunlocked>" . $lang['Status_unlocked'] . "</option>\n";
            $statuslist .= "<option value=\"" . FORUM_LOCKED . "\" $forumlocked>" . $lang['Status_locked'] . "</option>\n";

            $template->set_filenames(array('body' => 'forums/admin/forum_edit_body.html'));

            $s_hidden_fields = '<input type="hidden" name="mode" value="' . $newmode .'" /><input type="hidden" name="' . POST_FORUM_URL . '" value="' . $forum_id . '" />';

            $template->assign_vars(array(
                'S_FORUM_ACTION' => adminlink("&amp;do=forums"),
                'S_HIDDEN_FIELDS' => $s_hidden_fields,
                'S_SUBMIT_VALUE' => $buttonvalue,
                'S_CAT_LIST' => $catlist,
                'S_STATUS_LIST' => $statuslist,
                'S_PRUNE_ENABLED' => $prune_enabled,

                'L_FORUM_TITLE' => $l_title,
                'L_FORUM_EXPLAIN' => $lang['Forum_edit_delete_explain'],
                'L_FORUM_SETTINGS' => $lang['Forum_settings'],
                'L_FORUM_NAME' => $lang['Forum_name'],
                'L_CATEGORY' => $lang['Category'],
                'L_FORUM_DESCRIPTION' => $lang['Forum_desc'],
                'L_FORUM_STATUS' => $lang['Forum_status'],
                'L_AUTO_PRUNE' => $lang['Forum_pruning'],
                'L_ENABLED' => $lang['Enabled'],
                'L_PRUNE_DAYS' => $lang['prune_days'],
                'L_PRUNE_FREQ' => $lang['prune_freq'],
                'L_DAYS' => $lang['Days'],

                'PRUNE_DAYS' => $pr_row['prune_days'] ?? 7,
                'PRUNE_FREQ' => $pr_row['prune_freq'] ?? 1,
                'FORUM_NAME' => $forumname,
                'DESCRIPTION' => $forumdesc)
            );
            break;

        case 'createforum':
            //
            // Create a forum in the DB
            //
            if( trim($_POST['forumname']) == "" ) {
                message_die(GENERAL_ERROR, "Can't create a forum without a name");
                return;
            }
            $result = $db->sql_query("SELECT MAX(forum_order) AS max_order FROM " . FORUMS_TABLE . " WHERE cat_id = " . intval($_POST[POST_CAT_URL]));
            $row = $db->sql_fetchrow($result);
            $max_order = $row['max_order'];
            $next_order = $max_order + 10;

            //
            // Default permissions of public ::
            //
            $field_sql = $value_sql = '';
            foreach ($forum_auth_ary as $field => $value) {
                $field_sql .= ", $field";
                $value_sql .= ", $value";
            }

            // There is no problem having duplicate forum names so we won't check for it.
            $sql = "INSERT INTO " . FORUMS_TABLE . " (forum_name, cat_id, forum_desc, forum_order, forum_status, prune_enable" . $field_sql . ")
                VALUES ('" . Fix_Quotes($_POST['forumname']) . "', " . intval($_POST[POST_CAT_URL]) . ", '" . Fix_Quotes($_POST['forumdesc']) . "', $next_order, " . intval($_POST['forumstatus']) . ", " . (!empty($_POST['prune_enable']) ? intval($_POST['prune_enable']) : 0) . $value_sql . ")";
            $db->sql_query($sql);
            if( isset($_POST['prune_enable']) ) {
                if( $_POST['prune_days'] == "" || $_POST['prune_freq'] == "") {
                    message_die(GENERAL_MESSAGE, $lang['Set_prune_data']);
                    return;
                }
                $next_id = $db->sql_nextid('forum_id');
                $sql = "INSERT INTO " . PRUNE_TABLE . " (forum_id, prune_days, prune_freq)
                    VALUES('" . $next_id . "', " . intval($_POST['prune_days']) . ", " . intval($_POST['prune_freq']) . ")";
                $db->sql_query($sql);
            }
            $message = $lang['Forums_updated'] . "<br /><br />" . sprintf($lang['Click_return_forumadmin'], "<a href=\"".adminlink("&amp;do=forums").'">', '</a>') . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op).'">', "</a>");
            message_die(GENERAL_MESSAGE, $message);
            return;
            break;

        case 'modforum':
            // Modify a forum in the DB
            if( isset($_POST['prune_enable'])) {
                if( $_POST['prune_enable'] != 1 ) {
                    $_POST['prune_enable'] = 0;
                }
            }

            $sql = "UPDATE " . FORUMS_TABLE . "
                SET forum_name = '" . Fix_Quotes($_POST['forumname']) . "', cat_id = " . intval($_POST[POST_CAT_URL]) . ", forum_desc = '" . Fix_Quotes($_POST['forumdesc']) . "', forum_status = " . intval($_POST['forumstatus']) . ", prune_enable = " . (isset($_POST['prune_enable']) ? intval($_POST['prune_enable']) : 0) . "
                WHERE forum_id = " . intval($_POST[POST_FORUM_URL]);
            $db->sql_query($sql);
            if( isset($_POST['prune_enable']) && $_POST['prune_enable'] == 1 ) {
                if( $_POST['prune_days'] == "" || $_POST['prune_freq'] == "" ) {
                    message_die(GENERAL_MESSAGE, $lang['Set_prune_data']);
                    return;
                }

                $result = $db->sql_query("SELECT * FROM " . PRUNE_TABLE . " WHERE forum_id = " . intval($_POST[POST_FORUM_URL]));
                if( $db->sql_numrows($result) > 0 ) {
                    $sql = "UPDATE " . PRUNE_TABLE . "
                        SET    prune_days = " . intval($_POST['prune_days']) . ",    prune_freq = " . intval($_POST['prune_freq']) . "
                         WHERE forum_id = " . intval($_POST[POST_FORUM_URL]);
                } else {
                    $sql = "INSERT INTO " . PRUNE_TABLE . " (forum_id, prune_days, prune_freq)
                        VALUES(" . intval($_POST[POST_FORUM_URL]) . ", " . intval($_POST['prune_days']) . ", " . intval($_POST['prune_freq']) . ")";
                }
                $db->sql_query($sql);
            }

            $message = $lang['Forums_updated'] . "<br /><br />" . sprintf($lang['Click_return_forumadmin'], "<a href=\"".adminlink("&amp;do=forums")."\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
            message_die(GENERAL_MESSAGE, $message);
            return;
            break;

        case 'addcat':
            // Create a category in the DB
            if( trim($_POST['categoryname']) == '') {
                message_die(GENERAL_ERROR, "Can't create a category without a name");
                return;
            }

            $result = $db->sql_query("SELECT MAX(cat_order) AS max_order FROM " . CATEGORIES_TABLE);
            $row = $db->sql_fetchrow($result);
            $max_order = $row['max_order'];
            $next_order = $max_order + 10;

            //
            // There is no problem having duplicate forum names so we won't check for it.
            //
            $sql = "INSERT INTO " . CATEGORIES_TABLE . " (cat_title, cat_order)
                VALUES ('" . Fix_Quotes($_POST['categoryname']) . "', $next_order)";
            $db->sql_query($sql);
            $message = $lang['Forums_updated'] . "<br /><br />" . sprintf($lang['Click_return_forumadmin'], "<a href=\"".adminlink("&amp;do=forums")."\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
            Cache::array_delete('category_rows', $module_name);
            message_die(GENERAL_MESSAGE, $message);
            return;
            break;

        case 'editcat':
            //
            // Show form to edit a category
            //
            $newmode = 'modcat';
            $buttonvalue = $lang['Update'];

            $cat_id = intval($_GET[POST_CAT_URL]);

            $row = get_info('category', $cat_id);
            $cat_title = $row['cat_title'];

            $template->set_filenames(array('body' => 'forums/admin/category_edit_body.html'));

            $s_hidden_fields = '<input type="hidden" name="mode" value="' . $newmode . '" /><input type="hidden" name="' . POST_CAT_URL . '" value="' . $cat_id . '" />';

            $template->assign_vars(array(
                'CAT_TITLE' => $cat_title,

                'L_EDIT_CATEGORY' => $lang['Edit_Category'],
                'L_EDIT_CATEGORY_EXPLAIN' => $lang['Edit_Category_explain'],
                'L_CATEGORY' => $lang['Category'],

                'S_HIDDEN_FIELDS' => $s_hidden_fields,
                'S_SUBMIT_VALUE' => $buttonvalue,
                'S_FORUM_ACTION' => adminlink("&amp;do=forums")
                )
            );
            break;

        case 'modcat':
            // Modify a category in the DB
            $sql = "UPDATE " . CATEGORIES_TABLE . "
                SET cat_title = '" . Fix_Quotes($_POST['cat_title']) . "'
                WHERE cat_id = " . intval($_POST[POST_CAT_URL]);
            $db->sql_query($sql);
            $message = $lang['Forums_updated'] . "<br /><br />" . sprintf($lang['Click_return_forumadmin'], "<a href=\"".adminlink("&amp;do=forums")."\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
            Cache::array_delete('category_rows', $module_name);
            message_die(GENERAL_MESSAGE, $message);
            return;
            break;

        case 'deleteforum':
            // Show form to delete a forum
            $forum_id = intval($_GET[POST_FORUM_URL]);

            $select_to = '<select name="to_id">';
            //$select_to .= "<option value=\"-1\"$s>" . $lang['Delete_all_posts'] . "</option>\n";
            $select_to .= "<option value=\"-1\">" . $lang['Delete_all_posts'] . "</option>\n";
            $select_to .= get_list('forum', $forum_id, 0);
            $select_to .= '</select>';

            $buttonvalue = $lang['Move_and_Delete'];

            $newmode = 'movedelforum';

            $foruminfo = get_info('forum', $forum_id);
            $name = $foruminfo['forum_name'];

            $template->set_filenames(array('body' => 'forums/admin/forum_delete_body.html'));

            $s_hidden_fields = '<input type="hidden" name="mode" value="' . $newmode . '" /><input type="hidden" name="from_id" value="' . $forum_id . '" />';

            $template->assign_vars(array(
                'NAME' => $name,

                'L_FORUM_DELETE' => $lang['Forum_delete'],
                'L_FORUM_DELETE_EXPLAIN' => $lang['Forum_delete_explain'],
                'L_MOVE_CONTENTS' => $lang['Move_contents'],
                'L_FORUM_NAME' => $lang['Forum_name'],

                "S_HIDDEN_FIELDS" => $s_hidden_fields,
                'S_FORUM_ACTION' => adminlink("&amp;do=forums"),
                'S_SELECT_TO' => $select_to,
                'S_SUBMIT_VALUE' => $buttonvalue)
            );
            break;

        case 'movedelforum':
            //
            // Move or delete a forum in the DB
            //
            $from_id = intval($_POST['from_id']);
            $to_id = intval($_POST['to_id']);
            $delete_old = isset($_POST['delete_old']) ? intval($_POST['delete_old']) : 0;

            // Either delete or move all posts in a forum
            if($to_id == -1)
            {
                // Delete polls in this forum
                $sql = "SELECT v.vote_id
                    FROM " . VOTE_DESC_TABLE . " v, " . TOPICS_TABLE . " t
                    WHERE t.forum_id = $from_id
                        AND v.topic_id = t.topic_id";
                $result = $db->sql_query($sql);

                if ($row = $db->sql_fetchrow($result)) {
                    $vote_ids = '';
                    do {
                        $vote_ids .= (($vote_ids != '') ? ', ' : '') . $row['vote_id'];
                    }
                    while ($row = $db->sql_fetchrow($result));

                    $db->sql_query("DELETE FROM " . VOTE_DESC_TABLE . " WHERE vote_id IN ($vote_ids)");
                    $db->sql_query("DELETE FROM " . VOTE_RESULTS_TABLE . " WHERE vote_id IN ($vote_ids)");
                    $db->sql_query("DELETE FROM " . VOTE_USERS_TABLE . " WHERE vote_id IN ($vote_ids)");
                }
                $db->sql_freeresult($result);

                include("includes/phpBB/prune.php");
                prune($from_id, 0, true); // Delete everything from forum
            } else {
                $result = $db->sql_query("SELECT * FROM " . FORUMS_TABLE . " WHERE forum_id IN ($from_id, $to_id)");

                if($db->sql_numrows($result) != 2) {
                    message_die(GENERAL_ERROR, "Ambiguous forum ID's", "", __LINE__, __FILE__);
                    return;
                }
                $sql = "UPDATE " . TOPICS_TABLE . "
                    SET forum_id = $to_id
                    WHERE forum_id = $from_id";
                $db->sql_query($sql);
                $sql = "UPDATE " . POSTS_TABLE . "
                    SET    forum_id = $to_id
                    WHERE forum_id = $from_id";
                $db->sql_query($sql);
                sync('forum', $to_id);
            }

            // Alter Mod level if appropriate - 2.0.4
            $sql = "SELECT ug.user_id
                FROM " . AUTH_ACCESS_TABLE . " a, " . USER_GROUP_TABLE . " ug
                WHERE a.forum_id <> $from_id
                    AND a.auth_mod = 1
                    AND ug.group_id = a.group_id";
            $result = $db->sql_query($sql);

            if ($row = $db->sql_fetchrow($result))
            {
                $user_ids = '';
                do
                {
                    $user_ids .= (($user_ids != '') ? ', ' : '' ) . $row['user_id'];
                }
                while ($row = $db->sql_fetchrow($result));

                $sql = "SELECT ug.user_id
                    FROM " . AUTH_ACCESS_TABLE . " a, " . USER_GROUP_TABLE . " ug
                    WHERE a.forum_id = $from_id
                        AND a.auth_mod = 1
                        AND ug.group_id = a.group_id
                        AND ug.user_id NOT IN ($user_ids)";
                $result2 = $db->sql_query($sql);

                if ($row = $db->sql_fetchrow($result2)) {
                    $user_ids = '';
                    do {
                        $user_ids .= (($user_ids != '') ? ', ' : '' ) . $row['user_id'];
                    }
                    while ($row = $db->sql_fetchrow($result2));

                    $sql = "UPDATE " . USERS_TABLE . "
                        SET user_level = " . USER . "
                        WHERE user_id IN ($user_ids)
                            AND user_level <> " . ADMIN;
                    $db->sql_query($sql);
                $db->sql_freeresult($result2);
                }
                $db->sql_freeresult($result);

            }
            //$db->sql_freeresult($result2);

            $db->sql_query("DELETE FROM " . FORUMS_TABLE . " WHERE forum_id = $from_id");
            $db->sql_query("DELETE FROM " . AUTH_ACCESS_TABLE . " WHERE forum_id = $from_id");
            $db->sql_query("DELETE FROM " . PRUNE_TABLE . " WHERE forum_id = $from_id");

            $message = $lang['Forums_updated'] . "<br /><br />" . sprintf($lang['Click_return_forumadmin'], "<a href=\"".adminlink("&amp;do=forums")."\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
            message_die(GENERAL_MESSAGE, $message);
            return;
            break;

        case 'deletecat':
            //
            // Show form to delete a category
            //
            $cat_id = intval($_GET[POST_CAT_URL]);

            $buttonvalue = $lang['Move_and_Delete'];
            $newmode = 'movedelcat';
            $catinfo = get_info('category', $cat_id);
            $name = $catinfo['cat_title'];

            if ($catinfo['number'] == 1) {
                $result = $db->sql_query("SELECT count(*) as total FROM ". FORUMS_TABLE);
                $count = $db->sql_fetchrow($result);
                $count = $count['total'];

                if ($count > 0) {
                    message_die(GENERAL_ERROR, $lang['Must_delete_forums']);
                    return;
                } else {
                    $select_to = $lang['Nowhere_to_move'];
                }
            } else {
                $select_to = '<select name="to_id">';
                $select_to .= get_list('category', $cat_id, 0);
                $select_to .= '</select>';
            }

            $template->set_filenames(array('body' => 'forums/admin/forum_delete_body.html'));

            $s_hidden_fields = '<input type="hidden" name="mode" value="' . $newmode . '" /><input type="hidden" name="from_id" value="' . $cat_id . '" />';

            $template->assign_vars(array(
                'NAME' => $name,

                'L_FORUM_DELETE' => $lang['Forum_delete'],
                'L_FORUM_DELETE_EXPLAIN' => $lang['Forum_delete_explain'],
                'L_MOVE_CONTENTS' => $lang['Move_contents'],
                'L_FORUM_NAME' => $lang['Forum_name'],

                'S_HIDDEN_FIELDS' => $s_hidden_fields,
                'S_FORUM_ACTION' => adminlink("&amp;do=forums"),
                'S_SELECT_TO' => $select_to,
                'S_SUBMIT_VALUE' => $buttonvalue)
            );
            break;

        case 'movedelcat':
            //
            // Move or delete a category in the DB
            //
            $from_id = intval($_POST['from_id']);
            $to_id = isset($_POST['to_id']) ? intval($_POST['to_id']) : '';

            if (!empty($to_id)) {
                $result = $db->sql_query("SELECT * FROM " . CATEGORIES_TABLE . " WHERE cat_id IN ($from_id, $to_id)");
                if($db->sql_numrows($result) != 2) {
                    message_die(GENERAL_ERROR, "Ambiguous category ID's", "", __LINE__, __FILE__);
                    return;
                }
                $db->sql_query("UPDATE " . FORUMS_TABLE . " SET cat_id = $to_id WHERE cat_id = $from_id");
            }
            $db->sql_query("DELETE FROM " . CATEGORIES_TABLE ." WHERE cat_id = $from_id");

            $message = $lang['Forums_updated'] . "<br /><br />" . sprintf($lang['Click_return_forumadmin'], "<a href=\"".adminlink("&amp;do=forums")."\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"".adminlink($op)."\">", "</a>");
            Cache::array_delete('category_rows', $module_name);
            message_die(GENERAL_MESSAGE, $message);
            return;
            break;

        case 'forum_order':
            //
            // Change order of forums in the DB
            //
            $move = intval($_GET['move']);
            $forum_id = intval($_GET[POST_FORUM_URL]);
            $forum_info = get_info('forum', $forum_id);
            $cat_id = $forum_info['cat_id'];
            $db->sql_query("UPDATE " . FORUMS_TABLE . " SET forum_order = forum_order + $move WHERE forum_id = $forum_id");
            renumber_order('forum', $forum_info['cat_id']);
            $show_index = TRUE;
            break;

        case 'cat_order':
            //
            // Change order of categories in the DB
            //
            $move = intval($_GET['move']);
            $cat_id = intval($_GET[POST_CAT_URL]);
            $db->sql_query("UPDATE " . CATEGORIES_TABLE . " SET cat_order = cat_order + $move WHERE cat_id = $cat_id");
            renumber_order('category');
            $show_index = TRUE;
            Cache::array_delete('category_rows', $module_name);
            break;

        case 'forum_sync':
            sync('forum', intval($_GET[POST_FORUM_URL]));
            $show_index = TRUE;
            break;

        default:
            message_die(GENERAL_MESSAGE, $lang['No_mode']);
            return;
            break;
    }

    if ($show_index != TRUE) { return; }
}

//
// Start page proper
//
$template->set_filenames(array('body' => 'forums/admin/forum_admin_body.html'));

$template->assign_vars(array(
    'S_FORUM_ACTION' => adminlink("&amp;do=forums"),
    'L_FORUM_TITLE' => $lang['Forum_admin'],
    'L_FORUM_EXPLAIN' => $lang['Forum_admin_explain'],
    'L_CREATE_FORUM' => $lang['Create_forum'],
    'L_CREATE_CATEGORY' => $lang['Create_category'],
    'L_EDIT' => $lang['Edit'],
    'L_DELETE' => $lang['Delete'],
    'L_MOVE_UP' => $lang['Move_up'],
    'L_MOVE_DOWN' => $lang['Move_down'],
    'L_RESYNC' => $lang['Resync'])
);

$q_categories = $db->sql_query("SELECT cat_id, cat_title, cat_order FROM " . CATEGORIES_TABLE . " ORDER BY cat_order");

if( $total_categories = $db->sql_numrows($q_categories) ) {
    $category_rows = $db->sql_fetchrowset($q_categories);

    $q_forums = $db->sql_query("SELECT * FROM " . FORUMS_TABLE . " ORDER BY cat_id, forum_order");
    if( $total_forums = $db->sql_numrows($q_forums) ) {
        $forum_rows = $db->sql_fetchrowset($q_forums);
    }

    //
    // Okay, let's build the index
    //
    $gen_cat = array();

    for($i = 0; $i < $total_categories; $i++)
    {
        $cat_id = $category_rows[$i]['cat_id'];

        $template->assign_block_vars("catrow", array(
            'S_ADD_FORUM_SUBMIT' => "addforum[$cat_id]",
            'S_ADD_FORUM_NAME' => "forumname[$cat_id]",

            'CAT_ID' => $cat_id,
            'CAT_DESC' => $category_rows[$i]['cat_title'],

            'U_CAT_EDIT' => adminlink("&amp;do=forums&amp;mode=editcat&amp;" . POST_CAT_URL . "=$cat_id"),
            'U_CAT_DELETE' => adminlink("&amp;do=forums&amp;mode=deletecat&amp;" . POST_CAT_URL . "=$cat_id"),
            'U_CAT_MOVE_UP' => adminlink("&amp;do=forums&amp;mode=cat_order&amp;move=-15&amp;" . POST_CAT_URL . "=$cat_id"),
            'U_CAT_MOVE_DOWN' => adminlink("&amp;do=forums&amp;mode=cat_order&amp;move=15&amp;" . POST_CAT_URL . "=$cat_id"),
            'U_VIEWCAT' => getlink("&amp;file=index&amp;c=$cat_id"))
        );

        for($j = 0; $j < $total_forums; $j++)
        {
            $forum_id = $forum_rows[$j]['forum_id'];

            if ($forum_rows[$j]['cat_id'] == $cat_id)
            {

                $template->assign_block_vars("catrow.forumrow",    array(
                    'FORUM_NAME' => $forum_rows[$j]['forum_name'],
                    'FORUM_DESC' => $forum_rows[$j]['forum_desc'],
                    'NUM_TOPICS' => $forum_rows[$j]['forum_topics'],
                    'NUM_POSTS' => $forum_rows[$j]['forum_posts'],

                    'U_VIEWFORUM' => getlink("&amp;file=viewforum&amp;f=$forum_id"),
                    'U_FORUM_EDIT' => adminlink("&amp;do=forums&mode=editforum&amp;" . POST_FORUM_URL . "=$forum_id"),
                    'U_FORUM_DELETE' => adminlink("&amp;do=forums&mode=deleteforum&amp;" . POST_FORUM_URL . "=$forum_id"),
                    'U_FORUM_MOVE_UP' => adminlink("&amp;do=forums&mode=forum_order&amp;move=-15&amp;" . POST_FORUM_URL . "=$forum_id"),
                    'U_FORUM_MOVE_DOWN' => adminlink("&amp;do=forums&mode=forum_order&amp;move=15&amp;" . POST_FORUM_URL . "=$forum_id"),
                    'U_FORUM_RESYNC' => adminlink("&amp;do=forums&mode=forum_sync&amp;" . POST_FORUM_URL . "=$forum_id")
                    )
                );

            }// if ... forumid == catid

        } // for ... forums

    } // for ... categories

}// if ... total_categories
