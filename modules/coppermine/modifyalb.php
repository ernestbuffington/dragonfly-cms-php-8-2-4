<?php
/***************************************************************************
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.org/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
  **************************************************************************
  Last modification notes:
  $Source: /cvs/html/modules/coppermine/modifyalb.php,v $
  $Revision: 9.6 $
  $Author: djmaze $
  $Date: 2007/02/17 12:31:01 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }

define('MODIFYALB_PHP', true);
require("modules/" . $module_name . "/include/load.inc");

if ((!GALLERY_ADMIN_MODE) && (!USER_CAN_CREATE_ALBUMS)) {
    cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);
}
// Type 0 => input
// 1 => yes/no
// 2 => Category
// 3 => Textarea
// 4 => Album thumbnail
// 5 => Album visibility
$data = array(GENERAL_SETTINGS,
    array(ALB_TITLE, 'title', 0),
    array(ALB_CAT, 'category', 2),
    array(ALB_DESC, 'description', 3),
    array(ALB_THUMB, 'thumb', 4),
    ALB_PERM,
    array(MOD_CAN_UPLOAD, 'uploads', 1),
    array(CAN_VIEW, 'visibility', 5),
    array(CAN_POST_COMMENTS, 'comments', 1),
    array(MOD_CAN_RATE, 'votes', 1)
);
function get_subcat_data($parent, $ident = '') {
    global $db,$CONFIG, $CAT_LIST;

    $result = $db->sql_query("SELECT cid, catname, description FROM {$CONFIG['TABLE_CATEGORIES']} WHERE parent = '$parent' AND cid != 1 ORDER BY pos");
    if ($db->sql_numrows($result) > 0) {
        $rowset = $db->sql_fetchrowset($result);
        foreach ($rowset as $subcat) {
            $CAT_LIST[] = array($subcat['cid'], $ident . $subcat['catname']);
            get_subcat_data($subcat['cid'], $ident . '&nbsp;&nbsp;&nbsp;');
        }
    }
}

function form_label($text) {
    echo <<<EOT
        <tr>
                <td class="tableh2" colspan="2">
                        <b>$text</b>
                </td>
        </tr>

EOT;
}

function form_input($text, $catname) {
    global $ALBUM_DATA;

    $value = $ALBUM_DATA[$catname];

    echo <<<EOT
        <tr>
            <td width="40%" class="tableb">
                        $text
        </td>
        <td width="60%"  class="tableb" valign="top">
                <input type="text" style="width: 100%" name="$catname" value="$value" class="textinput" />
                </td>
        </tr>

EOT;
}

function form_yes_no($text, $catname) {
    global $ALBUM_DATA;

    if ($catname == 'uploads' && !GALLERY_ADMIN_MODE) {
        echo "<input type=\"hidden\" name=\"$catname\" value=\"{$ALBUM_DATA['uploads']}\" />";
        return;
    }

    $value = isset($ALBUM_DATA[$catname]) ? $ALBUM_DATA[$catname] : false;
    $yes_selected = $value ? 'selected="selected"' : '';
    $no_selected = !$value  ? 'selected="selected"' : '';

    echo '
        <tr>
            <td class="tableb">
                        '.$text.'
        </td>
        <td class="tableb" valign="top">
                        <select name="'.$catname.'" class="listbox">
                                <option value="1" '.$yes_selected.'>'.YES.'</option>
                                <option value="0" '.$no_selected.'>'.NO.'</option>
                        </select>
                </td>
        </tr>

';
}

function form_category($text, $catname) {
    global $ALBUM_DATA, $CAT_LIST, $USER_DATA;

    if (!GALLERY_ADMIN_MODE || $ALBUM_DATA['category'] > FIRST_USER_CAT) {
        echo '
        <tr>
            <td class="tableb">
                        '.$text.'
        </td>
        <td class="tableb" valign="top">
                        <i>'.USER_GAL.'</i>
                        <input type="hidden" name="'.$catname.'" value="'.$ALBUM_DATA['category'].'" />
                </td>

';
        return;
    }

    $CAT_LIST = array();
    $CAT_LIST[] = array(0, NO_CAT);
    get_subcat_data(0, '');

    echo <<<EOT
        <tr>
            <td class="tableb">
                        $text
        </td>
        <td class="tableb" valign="top">
            <form method="post" action="'.getlink("&amp;file=modifyalb",0,1).'" enctype="multipart/form-data" accept-charset="utf-8">
            <select name="$catname" class="listbox">
EOT;
    foreach($CAT_LIST as $category) {
        echo '<option value="' . $category[0] . '"' . ($ALBUM_DATA['category'] == $category[0] ? ' selected': '') . ">" . $category[1] . "</option>\n";
    }
    echo <<<EOT
                        </select>
                </td>
        </tr>

EOT;
}

function form_textarea($text, $catname) {
    global $ALBUM_DATA;

    $value = $ALBUM_DATA[$catname];

    echo <<<EOT
        <tr>
                <td class="tableb" valign="top">
                        $text
                </td>
                <td class="tableb" valign="top">
                        <textarea name="$catname" rows="5" cols="40" wrap="virtual" class="textinput" style="width: 100%;">{$ALBUM_DATA['description']}</textarea>
                </td>
        </tr>
EOT;
}

function form_alb_thumb($text, $catname) {
    global $db,$CONFIG, $ALBUM_DATA, $album, $CPG_M_DIR,$THEME_DIR;

    $results = $db->sql_query("SELECT pid, filepath, filename, url_prefix FROM {$CONFIG['TABLE_PICTURES']} WHERE aid='$album' AND approved='1' ORDER BY filename");
    if ($db->sql_numrows($results) == 0) {
        echo '
        <tr>
            <td class="tableb" valign="top">
                '.$text.'
            </td>
            <td class="tableb" valign="top">
                <i>'.ALB_EMPTY.'</i>
                    <input type="hidden" name="'.$catname.'" value="0" />
            </td>
        </tr>';
        return;
}

    echo <<<EOT
<script language="JavaScript" type="text/JavaScript">
var Pic = new Array()

Pic[0] = '$THEME_DIR/images/nopic.jpg'

EOT;

    $initial_thumb_url = $THEME_DIR . '/images/nopic.jpg';
    $img_list = array(0 => LAST_UPLOADED);
    while ($picture = $db->sql_fetchrow($results)) {
        $thumb_url = get_pic_url($picture, 'thumb');
        echo "Pic[{$picture['pid']}] = '" . $thumb_url . "'\n";
        if ($picture['pid'] == $ALBUM_DATA[$catname]) $initial_thumb_url = $thumb_url;
        $img_list[$picture['pid']] = htmlprepare($picture['filename']);
    } // while
    echo <<<EOT

function ChangeThumb(index) {
        document.images.Thumb.src = Pic[index]
}
</script>

EOT;
    $thumb_cell_height = $CONFIG['thumb_width'] + 17;
    echo '
         <tr>
            <td class="tableb" valign="top">
                '.$text.'
            <td class="tableb" align="center">
                <table cellspacing="0" cellpadding="5" border="0">
                    <tr>
                        <td width="'.$thumb_cell_height.'" height="'.$thumb_cell_height.'" align="center"><img src="'.$initial_thumb_url.'" name="Thumb" class="image" alt="" /><br />
                        </td>
                    </tr>
                </table>
    ';

    echo <<<EOT
<select name="$catname" class="listbox" onchange="if(this.options[this.selectedIndex].value) ChangeThumb(this.options[this.selectedIndex].value);" onkeyup="if(this.options[this.selectedIndex].value) ChangeThumb(this.options[this.selectedIndex].value);">
EOT;
    foreach($img_list as $pid => $pic_name) {
        echo '<option value="' . $pid . '"' . ($pid == $ALBUM_DATA[$catname] ? ' selected':'') . '>' . $pic_name . "</option>\n";
    }
    echo '
         </select>
         </td>
         </tr>
    ';
}

function form_visibility($text, $catname) {
    global $db, $CONFIG, $USER_DATA, $ALBUM_DATA;

    if (!$CONFIG['allow_private_albums'] && !GALLERY_ADMIN_MODE) {
        echo '<input type="hidden" name="' . $catname . '" value="0" />' . "\n";
        return;
    }

    if (GALLERY_ADMIN_MODE) {
        $options = array(0 => PUBLIC_ALB, FIRST_USER_CAT + USER_ID => ME_ONLY);
        if ($ALBUM_DATA['category'] > FIRST_USER_CAT) {
            $result = $db->sql_query("SELECT username FROM {$CONFIG['TABLE_USERS']} WHERE user_id='" . ($ALBUM_DATA['category'] - FIRST_USER_CAT) . "'");
            if ($db->sql_numrows($result)) {
                $user = $db->sql_fetchrow($result);
                $options[$ALBUM_DATA['category']] = sprintf(OWNER_ONLY, $user['username']);
            }
        }
        $result = $db->sql_query("SELECT group_id, group_name FROM {$CONFIG['TABLE_USERGROUPS']}");
        while ($group = $db->sql_fetchrow($result)) {
            $options[$group['group_id']] = sprintf(GROUPP_ONLY, $group['group_name']);
        } // while
    } else {
        $options =
        array(
            0 => PUBLIC_ALB,
            FIRST_USER_CAT + USER_ID => ME_ONLY,
            $USER_DATA['group_id'] => sprintf(GROUPP_ONLY, $USER_DATA['group_name'])
        );
    }

    echo <<<EOT
        <tr>
            <td class="tableb">
                $text
            </td>
            <td class="tableb" valign="top">
                <select name="$catname" class="listbox">

EOT;
    foreach ($options as $value => $caption) {
        echo '<option value ="' . $value . '"' . ($ALBUM_DATA['visibility'] == $value ? ' selected="selected"': '') . '>' . $caption . "</option>\n";
    }
    echo <<<EOT
                </select>
            </td>
        </tr>

EOT;
}

function create_form($data) {
    foreach($data as $element) {
        if ((is_array($element))) {
            switch ($element[2]) {
                case 0 :
                    form_input($element[0], $element[1]);
                    break;
                case 1 :
                    form_yes_no($element[0], $element[1]);
                    break;
                case 2 :
                    form_category($element[0], $element[1]);
                    break;
                case 3 :
                    form_textarea($element[0], $element[1]);
                    break;
                case 4 :
                    form_alb_thumb($element[0], $element[1]);
                    break;
                case 5 :
                    form_visibility($element[0], $element[1]);
                    break;
                default:
                    cpg_die(_CRITICAL_ERROR, 'Invalid action for form creation', __FILE__, __LINE__);
            } // switch
        } else {
            form_label($element);
        }
    }
}

function alb_list_box() {
    global $db,$CONFIG, $album, $CPG_URL, $file, $module_name;

    if (GALLERY_ADMIN_MODE) {
        $result = $db->sql_query('SELECT aid, IF(username IS NOT NULL, CONCAT(\'(\', username, \') \', title), CONCAT(\' - \', title)) AS title '
			."FROM {$CONFIG['TABLE_ALBUMS']} AS a "
			."LEFT JOIN {$CONFIG['TABLE_USERS']} AS u ON user_id = (category - " . FIRST_USER_CAT.') '
			.'ORDER BY title');
    } else {
        $result = $db->sql_query("SELECT aid, title FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = '" . (FIRST_USER_CAT + USER_ID) . "' ORDER BY title");
    }

    if ($db->sql_numrows($result)) {
        $lb = '<form method="post" action="'.getlink("&amp;file=modifyalb",0,1).'" enctype="multipart/form-data" accept-charset="utf-8"><input type="hidden" name="name" value="'.$module_name.'" />
            <input type="hidden" name="file" value="'.$file.'" />';
        $lb .= "<select name=\"album\" class=\"listbox\">\n";
        while ($row = $db->sql_fetchrow($result)) {
            $selected = ($row['aid'] == $album) ? ' selected="selected"' : '';
            $lb .= '<option value="' . $row['aid'] . '"'.$selected.'>' . $row['title'] . "</option>\n";
        }
        $lb .= "</select>\n";
        $lb .= '<input type="submit" class="button" value="'._CHANGE.' '.ALBUM.'" />
         </form>';
        return $lb;
    }
}

if (!isset($_POST['album'])) {
    if (GALLERY_ADMIN_MODE) {
        $results = $db->sql_query("SELECT * FROM {$CONFIG['TABLE_ALBUMS']} LIMIT 1");
    } else {
        $results = $db->sql_query("SELECT * FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = " . (FIRST_USER_CAT + USER_ID) . ' LIMIT 1');
    }
    if ($db->sql_numrows($results) == 0) cpg_die(_ERROR, ERR_NO_ALB_TO_MODIFY, __FILE__, __LINE__);
    $ALBUM_DATA = $db->sql_fetchrow($results);
    $album = $ALBUM_DATA['aid'];
} else {
    $album = $_POST['album'];
    $results = $db->sql_query("SELECT * FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid='$album'");
    if (!$db->sql_numrows($results)) cpg_die(_CRITICAL_ERROR, NON_EXIST_AP, __FILE__, __LINE__);
    $ALBUM_DATA = $db->sql_fetchrow($results);
}

$cat = $ALBUM_DATA['category'];
$actual_cat = $cat;

if (!GALLERY_ADMIN_MODE && $ALBUM_DATA['category'] != FIRST_USER_CAT + USER_ID) {
    cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
}

pageheader(sprintf(UPD_ALB_N, $ALBUM_DATA['title']));
starttable('100%');

$album_lb = alb_list_box();
$chset =_CHARSET;
echo '
        <tr>
            <td colspan="2" class="tableh1">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="statlink"><h2>'.UPDATE.'</h2></td>
                        <td align="right">'.$album_lb.'</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><form method="post" action="'.getlink("&amp;file=db_input").'" enctype="multipart/form-data" accept-charset="utf-8">
            <input type="hidden" name="event" value="album_update" />
            <input type="hidden" name="aid" value="'.$album.'" />

';
create_form($data);
echo '
            <td colspan="2" align="center" class="tablef">
                <input type="submit" class="button" value="'.UPDATE.'" /></form>
            </td>

</tr>
';

endtable();
pagefooter();
