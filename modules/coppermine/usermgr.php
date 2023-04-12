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
  $Source: /cvs/html/modules/coppermine/usermgr.php,v $
  $Revision: 9.13 $
  $Author: brennor $
  $Date: 2006/02/24 19:14:03 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
define('USERMGR_PHP', true);
define('PROFILE_PHP', true);
require("modules/" . $module_name . "/include/load.inc");
if (!GALLERY_ADMIN_MODE) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);


function list_users()
{
    global $db, $CONFIG, $CPG_URL, $CPG_M_DIR,$THEME_DIR;
    global $lang_usermgr_php, $lang_byte_units, $register_date_fmt;
    global $module_name;

    $sort_codes = array(
        'name_a' => 'username ASC',
        'name_d' => 'username DESC',
        'group_a' => 'group_name ASC',
        'group_d' => 'group_name DESC',
        'reg_a' => 'user_id ASC',
        'reg_d' => 'user_id DESC',
        'pic_a' => 'pic_count ASC',
        'pic_d' => 'pic_count DESC',
        'disku_a' => 'disk_usage ASC',
        'disku_d' => 'disk_usage DESC',
    );

    $sort = (!isset($_GET['sort']) || !isset($sort_codes[$_GET['sort']])) ? 'reg_d' : $_GET['sort'];

    $tab_tmpl = array(
        'left_text' => '<td width="100%%" align="left" valign="middle" class="tableh1_compact" style="white-space: nowrap"><b>' . U_USER_ON_P_PAGES . '</b></td>' . "\n",
        'tab_header' => '',
        'tab_trailer' => '',
        'active_tab' => '<td><img src="images/spacer.gif" alt="" width="1" height="1" /></td>' . "\n" . '<td align="center" valign="middle" class="tableb_compact"><b>%d</b></td>',
        'inactive_tab' => '<td><img src="images/spacer.gif" alt="" width="1" height="1" /></td>' . "\n" . '<td align="center" valign="middle" class="navmenu"><a href="' . getlink('&file=usermgr&page=%d&sort=' . $sort) . '"><b>%d</b></a></td>' . "\n"
    );

    $result = $db->sql_query("SELECT count(*) FROM {$CONFIG['TABLE_USERS']}");
    $nbEnr = $db->sql_fetchrow($result);
    $user_count = $nbEnr[0];
    $db->sql_freeresult($result);

    if (!$user_count) cpg_die(_CRITICAL_ERROR, ERR_NO_USERS, __FILE__, __LINE__);

    $user_per_page = 25;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $lower_limit = ($page-1) * $user_per_page;
    $total_pages = ceil($user_count / $user_per_page);

    $sql = "SELECT user_id, username, user_email, user_regdate as user_regdate_cp, group_name, user_active_cp, user_level," .
     "COUNT(pid) as pic_count, ROUND(SUM(total_filesize)/1024) as disk_usage, group_quota " .
     "FROM {$CONFIG['TABLE_USERS']} AS u " .
     "INNER JOIN {$CONFIG['TABLE_USERGROUPS']} AS g ON user_group_cp = group_id " .
     "LEFT JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON category = " . FIRST_USER_CAT . " + user_id " .
     "LEFT JOIN {$CONFIG['TABLE_PICTURES']} AS p ON p.aid = a.aid " .
     "GROUP BY user_id, username, user_email, user_regdate, group_name, user_active_cp, user_level, group_quota " .
     "ORDER BY " . $sort_codes[$sort] . " " .
     "LIMIT $lower_limit, $user_per_page";

    $result = $db->sql_query($sql);

    $tabs = create_tabs($user_count, $page, $total_pages, $tab_tmpl);

    starttable('100%');

    echo '<tr>
    <td class="tableh1" colspan="7"><form method="POST" action="'.getlink("&amp;file=usermgr",0,1).'">
        <input type="hidden" name="opp" value="edit" />
        <b><span class="statlink">'.SEARCH_LNK.' '.U_NAME.': </span></b>
        <input type="text" name="user_name" maxlength="25" />
        <input type="submit" name="submit" value="'._GO.'"></form></td>
    </tr><tr>
    <td class="tableh1"><b><span class="statlink">'.U_NAME.'</span></b></td>
    <td class="tableh1"><b><span class="statlink">'.GROUP.'</span></b></td>
    <td class="tableh1" colspan="2" align="center"><b><span class="statlink">'.OPERATIONS.'</span></b></td>
    <td class="tableh1" align="center"><b><span class="statlink">'.PICTURES.'</span></b></td>
    <td class="tableh1" colspan="2" align="center"><b><span class="statlink">'.DISK_SPACE.'</span></b></td>
    </tr>
';

    while ($user = $db->sql_fetchrow($result)){
        if (!$user['user_active_cp'] || $user['user_level']==0 ) $user['group_name'] = '<i>' . INACTIVE . '</i>';
        $user['user_regdate_cp'] = localised_date($user['user_regdate_cp'], $register_date_fmt);
        if ($user['pic_count']){
            $usr_link_start = '<a href="' . getlink('&cat=' . ($user['user_id'] + FIRST_USER_CAT)) . '" target="_blank">';
            $usr_link_end = '</a>';
        }else{
            $usr_link_start = '';
            $usr_link_end = '';
        }
    $user['disk_usage'] = ($user['disk_usage']!='')? $user['disk_usage'] : '0';
        echo '
    <tr>
    <td class="tableb">'.$usr_link_start.$user['username'].$usr_link_end.'</td>
    <td class="tableb">'.$user['group_name'].'</td>
    <td class="tableb" valign="middle" align="center"><br />
    <form method="post" action="'.getlink("&amp;file=usermgr").'">
                <input type="hidden" name="opp" value="edit" />
                <input type="hidden" name="user_id" value="'.$user['user_id'].'" />
                <input type="submit" name="submit" class="admin_menu"  value="'.EDIT.'" />
                </form>
    </td>
    <td class="tableb" valign="middle" align="center"><br />
    <form method="post" action="'.getlink("&amp;file=delete").'">
                <input type="hidden" name="what" value="user" />
                <input type="hidden" name="id" value="'.$user['user_id'].'" />
                <input type="submit" name="submit" class="admin_menu"  value="'.DELETE.'" />
                </form>
                </td>
    <td class="tableb" align="center">'.$user['pic_count'].'</td>
    <td class="tableb" align="right">'.$user['disk_usage'].' '.$lang_byte_units[1].'</td>
    <td class="tableb" align="right">'.$user['group_quota'].' '.$lang_byte_units[1].'</td>
    </tr>
';
    } // while
    $db->sql_freeresult($result);
    //$CPG_URL = getlink("&file=usermgr&page=$page&sort=",0);
    //echo <<<EOT
    //<select onChange="if(this.options[this.selectedIndex].value) window.location.href='$CPG_URL'+this.options[this.selectedIndex].value;"  name="album_listbox" class="listbox">";
    //EOT;
    //$lb = "<select name=\"album_listbox\" class=\"listbox\" onChange=\"if(this.options[this.selectedIndex].value) window.location.href='" . $CPG_URL . "&file=usermgr&page=$page&sort='+this.options[this.selectedIndex].value;\">\n";
    $lb ='';
    foreach($sort_codes as $key => $value){
        $selected = ($key == $sort) ? "SELECTED" : "";
        $lb .= "    <option value=\"" . $key . "\" $selected>" . $lang_usermgr_php[$key] . "</option>\n";
    }
    $lb .= "</select>\n";
    $CPG_URL = getlink("&file=usermgr&page=$page&sort=",0,1);
    echo '<tr><form method="post" action="'.ADDUSER_URL.'" enctype="multipart/form-data" accept-charset="'._CHARSET.'">
  <td colspan="8" align="center" class="tablef">
    <table cellpadding="0" cellspacing="0">
    <tr>
    <td><input type="submit" value="'.CREATE_NEW_USER.'" class="button" /></td>
    <td><img src="'.$THEME_DIR.'/images/spacer.gif" width="50" height="1" alt="" /></td>
    <td><b>'.SORT_BY.'</b></td>
    <td><img src="'.$THEME_DIR.'/images/spacer.gif" width="10" height="1" alt="" /></td>
    <td><select onchange="if(this.options[this.selectedIndex].value) window.location.href=\''.$CPG_URL.'\'+this.options[this.selectedIndex].value;" name="album_listbox" class="listbox">'.$lb.'</td>
    </tr>
    </table>
  </td>
  </form>
</tr><tr>
  <td colspan="8" style="padding: 0px;">
    <table width="100%" cellspacing="0" cellpadding="0">
    <tr>'.$tabs.'</tr>
    </table>
  </td>
</tr>';
    endtable();
}

function edit_user($user_id)
{
    global $db, $CONFIG, $lang_usermgr_php, $lang_yes, $lang_no;

    $sql = "SELECT username, user_active_cp, user_group_cp, user_group_list_cp FROM {$CONFIG['TABLE_USERS']} WHERE user_id = '$user_id'";
    $result = $db->sql_query($sql);
    if (!$db->sql_numrows($result)) cpg_die(_CRITICAL_ERROR, ERR_UNKNOWN_USER, __FILE__, __LINE__);
    $user_data = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);

    starttable(500, MODIFY_USER, 2);
    $chset =_CHARSET;
    echo '<form method="post" action="'.getlink("&amp;file=usermgr&amp;opp=update&amp;user_id=$user_id")."\" enctype=\"multipart/form-data\" accept-charset=\"$chset\">";
    echo '
    <tr>
        <td width="40%" class="tableb">'.U_NAME.'</td>
        <td width="60%" class="tableb">'.$user_data['username'].'</td>
    </tr>';

    $yes_selected = ($user_data['user_active_cp']) ? 'selected' : '';
    $no_selected = (!$user_data['user_active_cp']) ? 'selected' : '';
    echo '
    <tr>
        <td class="tableb">'.USER_ACTIVE_CP.'</td>
        <td class="tableb">
            <select name="user_active_cp" class="listbox">
                <option value="1" '.$yes_selected.'>'.YES.'</option>
                <option value="0" '.$no_selected.'>'.NO.'</option>
            </select>
        </td>
    </tr>';

    $result = $db->sql_query("SELECT group_id, group_name FROM {$CONFIG['TABLE_USERGROUPS']} ORDER BY group_name");
    $group_list = $db->sql_fetchrowset($result);
    $db->sql_freeresult($result);

    $sel_group = $user_data['user_group_cp'];
    $user_group_list = explode(',', $user_data['user_group_list_cp']);

    echo '
    <tr>
        <td class="tableb">'.USER_GROUP_CP.'</td>
        <td class="tableb" valign="top">
            <select name="user_group_cp" class="listbox">';

    $group_cb = '';
    foreach($group_list as $group) {
        echo '                <option value="' . $group['group_id'] . '"' . ($group['group_id'] == $sel_group ? ' selected' : '') . '>' . $group['group_name'] . "</option>\n";
        $checked = (user_ingroup($group['group_id'], $user_group_list)) ? 'checked' : '';
        $group_cb .= '<input name="group_list[]" type="checkbox" value="' . $group['group_id'] . '" ' . $checked . '>' . $group['group_name'] . "<br />\n";
    }

    echo '
            </select><br />
            '.$group_cb.'
        </td>
    </tr>
    <tr>
        <td colspan="2" align="center" class="tablef"><input type="submit" value="'.MODIFY_USER.'" class="button"></td>
    </form>
    </tr>';

    endtable();
}

function update_user($user_id)
{
    global $db, $CONFIG, $lang_usermgr_php, $lang_register_php;

    $user_active_cp = $_POST['user_active_cp'];
    $user_group_cp = $_POST['user_group_cp'];
    $group_list = $_POST['group_list'] ?? '';
    $username ??= '';

    $sql = "SELECT user_id FROM {$CONFIG['TABLE_USERS']} WHERE username = '" . Fix_Quotes($username) . "' AND user_id != $user_id";
    $result = $db->sql_query($sql);

    if ($db->sql_numrows($result)){
        cpg_die(_ERROR, $lang_register_php['err_user_exists'], __FILE__, __LINE__);
        return false;
    }
    $db->sql_freeresult($result);
    
    $user_group_list = '';
    if (is_array($group_list)) {
        foreach($group_list as $group) $user_group_list .= ($group != $user_group_cp) ? $group . ',' : '';
        $user_group_list = substr($user_group_list, 0, -1);
    }

    $sql_update = "UPDATE {$CONFIG['TABLE_USERS']} SET " .
     "user_active_cp    = '$user_active_cp', " .
     "user_group_cp     = '$user_group_cp', " .
     "user_group_list_cp     = '$user_group_list' " .
     "WHERE user_id = '$user_id'";
    $db->sql_query($sql_update);
}

$opp = $_POST['opp'] ?? $_GET['opp'] ?? '';

switch ($opp){
    case 'edit' :
        if (isset($_POST['user_name'])) {
            $user_name = substr($_POST['user_name'], 0, 25);
            $sql = "SELECT user_id FROM {$CONFIG['TABLE_USERS']} WHERE username = '" . $user_name . "'";
            $result = $db->sql_query($sql);
            if ($db->sql_numrows($result)){
                $user_data = $db->sql_fetchrow($result);
                $user_id = $user_data[0];
            }
            $db->sql_freeresult($result);
        } else {
            $user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? -1);
        }
        if (isset($user_id) && USER_ID == $user_id && !can_admin()) {
            cpg_die(_ERROR, ERR_EDIT_SELF, __FILE__, __LINE__);
        }
        pageheader(U_TITLE);
        edit_user(($user_id ?? ''));
        pagefooter();
        break;

    case 'update' :
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : -1;
        update_user($user_id);
        pageheader(U_TITLE);
        list_users();
        pagefooter();
        break;

    default :
        pageheader(U_TITLE);
        list_users();
        pagefooter();
        break;
}
