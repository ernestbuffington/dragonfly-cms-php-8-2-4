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
  $Source: /cvs/html/modules/coppermine/upload.php,v $
  $Revision: 9.2 $
  $Author: djmaze $
  $Date: 2005/01/31 10:31:58 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
define('UPLOAD_PHP', true);
require("modules/" . $module_name . "/include/load.inc");
if (!USER_CAN_UPLOAD_PICTURES){cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);}
if ($CLASS['member']->demo){
      cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
}
// Type 0 => input
// 1 => file input
// 2 => album list
$data = array(
    sprintf(MAX_FSIZE, $CONFIG['max_upl_size']),
    array(ALBUM, 'album', 2),
    array(PICTURE, 'userpicture', 1),
    array(PIC_TITLE, 'title', 0, 255),
    array(DESCRIPTION, 'caption', 3, $CONFIG['max_img_desc_length']),
    array(UP_KEYWORDS, 'keywords', 0, 255),
    array($CONFIG['user_field1_name'], 'user1', 0, 255),
    array($CONFIG['user_field2_name'], 'user2', 0, 255),
    array($CONFIG['user_field3_name'], 'user3', 0, 255),
    array($CONFIG['user_field4_name'], 'user4', 0, 255)
);

function form_label($text) {
    echo <<<EOT
        <tr>
            <td class="tableh2" colspan="2">
                <b>$text</b>
            </td>
        </tr>

EOT;
}

function form_input($text, $name, $max_length) {
    if ($text == ''){
        echo "<input type=\"hidden\" name=\"$name\" value=\"\" />\n";
          return;
    }
                
echo <<<EOT
        <tr>
            <td width="40%" class="tableb">
                        $text
            </td>
            <td width="60%" class="tableb" valign="top">
                <input type="text" style="width: 100%" name="$name" maxlength="$max_length" value="" class="textinput" />
            </td>
        </tr>

EOT;
}

function form_file_input($text, $name) {
                 global $CONFIG;
                
                 $max_file_size = $CONFIG['max_upl_size'] << 10;
                
echo <<<EOT
        <tr>
            <td class="tableb">
                        $text
            </td>
            <td class="tableb" valign="top">
                <input type="hidden" name="MAX_FILE_SIZE" value="$max_file_size" />
                <input type="file" name="$name" size="40" class="listbox" />
            </td>
        </tr>

EOT;
}

function form_alb_list_box($text, $name) {
    global $CONFIG, $public_albums_list;
    $sel_album = $_GET['album'] ?? 0;
                
echo <<<EOT
        <tr>
            <td class="tableb">
                $text
            </td>
            <td class="tableb" valign="top">
                <select name="$name" class="listbox">
EOT;
    foreach($public_albums_list as $album){
        echo '<option value="' . $album['aid'] . '"' . ($album['aid'] == $sel_album ? ' selected' : '') . '>' . $album['title'] . "</option>\n";
    }
echo <<<EOT
                </select>
            </td>
        </tr>
EOT;
}

function form_textarea($text, $name, $max_length) {
    global $ALBUM_DATA;
    $value = $ALBUM_DATA[$name];
echo <<<EOT
        <tr>
            <td class="tableb" valign="top">
                $text
            </td>
            <td class="tableb" valign="top">
                <textarea name="$name" rows="5" cols="40" wrap="virtual"  class="textinput" style="width: 100%;" onKeyDown="textCounter(this, $max_length);" onKeyUp="textCounter(this, $max_length);"></textarea>
            </td>
        </tr>
EOT;
}

function create_form(& $data) {
    foreach($data as $element){
        if (is_array($element)){
            switch ($element[2]){
                case 0 :
                    form_input($element[0], $element[1], $element[3]);
                    break;
                case 1 :
                    form_file_input($element[0], $element[1]);
                    break;
                case 2 :
                    form_alb_list_box($element[0], $element[1]);
                    break;
                case 3 :
                    form_textarea($element[0], $element[1], $element[3]);
                    break;
                default:
                    cpg_die(_ERROR, 'Invalid action for form creation', __FILE__, __LINE__);
             } // switch
        } else {
            form_label($element);
        }
    }
}

$public_albums_list = get_albumlist(USER_ID);

if (!count($public_albums_list)){
    $redirect = getlink("&amp;file=albmgr");
    pageheader(_ERROR, $redirect);
    msg_box(INFO, ERR_NO_ALB_UPLOADABLES, CONTINU, $redirect);
    pagefooter();
    //cpg_die (ERROR, ERR_NO_ALB_UPLOADABLES, __FILE__, __LINE__);
}

pageheader(UP_TITLE);
starttable("100%", UP_TITLE, 2);

echo '
    <script language="JavaScript">
        function textCounter(field, maxlimit) {
            if (field.value.length > maxlimit) // if too long...trim it!
            field.value = field.value.substring(0, maxlimit);
        }
    </script>
    <form method="post" action="'.getlink('&amp;file=db_input').'" enctype="multipart/form-data">
    <input type="hidden" name="event" value="picture" />
';
create_form($data);
echo '
    <tr>
        <td colspan="2" align="center" class="tablef">
            <input type="submit" value="'.UP_TITLE.'" class="button" />
        </td>
        </form>
    </tr>
';
endtable();
pagefooter();
