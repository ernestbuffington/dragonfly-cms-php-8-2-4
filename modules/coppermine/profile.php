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
  $Source: /cvs/html/modules/coppermine/profile.php,v $
  $Revision: 9.2 $
  $Author: brennor $
  $Date: 2006/02/24 19:14:03 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
define('PROFILE_PHP', true);
require("modules/" .  $module_name . "/include/load.inc");
$edit_profile_form_param = array(
    array('text', 'username', USERNAME),
    array('text', 'group', GROUP),
    array('text', 'disk_usage', DISK_USAGE)
);


function make_form($form_param, $form_data)
{
    global $CONFIG;
    global $lang_register_php;

    foreach ($form_param as $element) switch ($element[0]) {
        case 'label' :
            echo <<<EOT
        <tr>
            <td colspan="2" class="tableh2">
                        <b>{$element[1]}<b>
        </td>
        </tr>

EOT;
            break;

        case 'text' :
            echo <<<EOT
        <tr>
            <td width="40%" class="tableb" height="25">
                        {$element[2]}
        </td>
        <td width="60%" class="tableb">
                        {$form_data[$element[1]]}
        </td>
        </tr>

EOT;

            break;
        case 'input' :
            $value = $form_data[$element[1]];

            echo <<<EOT
        <tr>
            <td width="40%" class="tableb"  height="25">
                        {$element[2]}
        </td>
        <td width="60%" class="tableb" valign="top">
                <input type="text" style="width: 100%" name="{$element[1]}" maxlength="{$element[3]}" value="$value" class="textinput" />
                </td>
        </tr>

EOT;
            break;



        default:
            cpg_die(_CRITICAL_ERROR, 'Invalid action for form creation ' . $element[0], __FILE__, __LINE__);
    } 
} 

                global $db;
                if (!USER_ID) cpg_die(_ERROR, ACCESS_DENIED);//, __FILE__, __LINE__

        $sql = "SELECT username, user_email, user_regdate as user_regdate_cp, group_name, " . "user_from, user_interests, user_website, user_occ, "
              ."COUNT(pid) as pic_count, ROUND(SUM(total_filesize)/1024) as disk_usage, group_quota "
              ."FROM {$CONFIG['TABLE_USERS']} AS u "
              ."INNER JOIN {$CONFIG['TABLE_USERGROUPS']} AS g ON user_group_cp = group_id "
              ."LEFT JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON category = " . FIRST_USER_CAT . " + user_id "
              ."LEFT JOIN {$CONFIG['TABLE_PICTURES']} AS p ON p.aid = a.aid "
              ."WHERE user_id ='" . USER_ID . "' "
              ."GROUP BY user_id, username, user_email, user_regdate, group_name, user_from, user_interests, user_website, user_occ, group_quota";

        $result = $db->sql_query($sql);

        if (!$db->sql_numrows($result)) cpg_die(_ERROR, $lang_register_php['err_unk_user'], __FILE__, __LINE__);
        $user_data = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);

        $form_data = array('username' => $user_data['username'],
            'reg_date' => localised_date($user_data['user_regdate_cp'], REGISTER_DATE_FMT),
            'group' => $user_data['group_name'],
            'email' => $user_data['user_email'],
            'disk_usage' => $user_data['disk_usage']?$user_data['disk_usage']:0 .
            ($user_data['group_quota'] ? '/' . $user_data['group_quota'] : '') . ' ' . $lang_byte_units[1],
            'location' => $user_data['user_from'],
            'interests' => $user_data['user_interests'],
            'website' => $user_data['user_website'],
            'occupation' => $user_data['user_occ'],
            );

        $title = sprintf(X_S_PROFILE, CPG_USERNAME);
        pageheader($title);
        starttable(-1, $title, 2);
        $chset = _CHARSET;
        echo '<form method="post" action="'.getlink("").'" enctype="multipart/form-data" accept-charset="$chset">';
        make_form($edit_profile_form_param, $form_data);
        echo <<<EOT

        </form>

EOT;
        endtable();
        if (defined('CPG_NUKE')) {
            get_lang("Your_Account");
            require("modules/Your_Account/userinfo.php");
            userinfo(USER_ID);
        } else {
            pagefooter();
        }
    /*        break;
        default :

        $sql = "SELECT username, user_email, user_regdate as user_regdate_cp, group_name, " . "user_from, user_interests, user_website, user_occ " . "FROM {$CONFIG['TABLE_USERS']} AS u " . "INNER JOIN {$CONFIG['TABLE_USERGROUPS']} AS g ON user_group_cp = group_id " . "WHERE user_id ='$uid'";

        $result = $db->sql_query($sql);

        if (!$db->sql_numrows($result)) cpg_die(_ERROR, $lang_register_php['err_unk_user'], __FILE__, __LINE__);
        $user_data = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);

        $form_data = array('username' => $user_data['username'],
            'reg_date' => localised_date($user_data['user_regdate_cp'], $register_date_fmt),
            'group' => $user_data['group_name'],
            'location' => $user_data['user_from'],
            'interests' => $user_data['user_interests'],
            'website' => $user_data['user_website'],
            'occupation' => $user_data['user_occ'],
            );

        $title = sprintf(X_S_PROFILE, $user_data['username']);
        pageheader($title);
        starttable(-1, $title, 2);
        make_form($display_profile_form_param, $form_data);
        endtable();
        pagefooter();

        break;
}*/ 

?>
