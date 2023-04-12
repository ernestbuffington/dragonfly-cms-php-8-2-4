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
  $Source: /cvs/html/modules/coppermine/albmgr.php,v $
  $Revision: 9.2 $
  $Author: nanocaiordo $
  $Date: 2007/01/20 02:42:26 $
****************************************************************************/
if (!defined('CPG_NUKE')) { exit; }

define('ALBMGR_PHP', true);
global $THEME_DIR;
require("modules/" . $module_name . "/include/load.inc");
//init.inc $cat = (isset($_GET['cat']) ? $_GET['cat'] : isset($_POST['cat'])? $_POST['cat'] : 0);
$cat = intval(isset($_POST['cat']) ? $_POST['cat'] : (isset($_GET['cat']) ? $_GET['cat'] : 0));
//init.inc $album = (isset($_GET['album']) ? $_GET['album'] : isset($_POST['album'])? $_POST['album'] : NULL);
    if ($cat == USER_GAL_CAT) {
        $thisalbum = 'category > ' . FIRST_USER_CAT;
    } elseif (!is_numeric($album) && is_numeric($cat)) {
        $thisalbum = "category = '$cat'";
    } else if (is_numeric($album)) {
        $thisalbum= "a.aid = $album";
    } else {
        $thisalbum = "category >= '0'";//just something that is true
    }


//if (!(GALLERY_ADMIN_MODE || USER_ADMIN_MODE)) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__, 0, 1);
if  (!USER_CAN_CREATE_ALBUMS) {
    cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);
} 
function get_subcat_data($parent, $ident = '')
{
    global $db, $CONFIG, $CAT_LIST;
    $result = $db->sql_query("SELECT cid, catname, description FROM {$CONFIG['TABLE_CATEGORIES']} WHERE parent = '$parent' AND cid != 1 ORDER BY pos");
    if ($db->sql_numrows($result) > 0) {
        $rowset = $db->sql_fetchrowset($result);
        foreach ($rowset as $subcat) {
            $CAT_LIST[] = array($subcat['cid'], $ident . $subcat['catname']);
            get_subcat_data($subcat['cid'], $ident . '&nbsp;&nbsp;&nbsp;');
        } 
    } 
} 
$move='';
global $db;
if ((isset($_POST['aid']) && intval($_POST['aid'])>0 && isset($_POST['move'])) && ($_POST['move'] == 'up' || $_POST['move'] == 'top')) {
    $result = $db->sql_query("SELECT  title, pos, category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = ".intval($_POST['aid']));
    $album = $db->sql_fetchrow($result);
    $cat = $album['category'];
    if ($album['pos'] > 0) {
        $newpos = ($_POST['move'] == 'top') ? 0 : ($album['pos']-1);
        $db->sql_query("UPDATE {$CONFIG['TABLE_ALBUMS']} SET pos=pos+1 WHERE category = $cat AND pos < $album[pos] AND pos > $newpos-1");
        $db->sql_query("UPDATE {$CONFIG['TABLE_ALBUMS']} SET pos=$newpos WHERE aid = $_POST[aid]");
    }
    url_redirect(getlink("&file=albmgr&cat=$cat"));
} else if ((isset($_POST['aid']) && intval($_POST['aid'])>0 && isset($_POST['move'])) && ($_POST['move'] == 'down' || $_POST['move'] == 'bottom') ) {
    $result = $db->sql_query("SELECT pos, category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = ".intval($_POST['aid']));
    $album = $db->sql_fetchrow($result);
    $cat = $album['category'];
    $result = $db->sql_query("SELECT pos FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = $cat ORDER BY pos DESC LIMIT 1");
    $last = $db->sql_fetchrow($result);
    if ($album['pos'] < $last['pos']) {
        $newpos = ($_POST['move'] == 'down') ? ($album['pos']+1) : $last['pos'];
        $db->sql_query("UPDATE {$CONFIG['TABLE_ALBUMS']} SET pos=pos-1 WHERE category = $cat AND pos > $album[pos] AND pos < $newpos+1");
        $db->sql_query("UPDATE {$CONFIG['TABLE_ALBUMS']} SET pos=$newpos WHERE aid = $_POST[aid]");
    }
    url_redirect(getlink("&file=albmgr&cat=$cat"));
} else if ((isset($_POST['mode']) && $_POST['mode'] == 'addalb') && isset($_POST['cat'])) {
    if (GALLERY_ADMIN_MODE) {
        $cat = intval($_POST['cat']);
    } else {
        $cat = FIRST_USER_CAT + USER_ID;
    }
    $result = $db->sql_query("SELECT pos FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = $cat ORDER BY pos DESC LIMIT 1");
    list($last) = $db->sql_fetchrow($result);
    if ($last == '') $last = 0;
    else $last++;
    $title = Fix_Quotes($_POST['title']);
    if ($title == '') cpg_die(_ERROR, 'Album title can\'t be empty', __FILE__, __LINE__, 0, 1);
    $db->sql_query("INSERT INTO {$CONFIG['TABLE_ALBUMS']} (title, pos, category, description) VALUES ('$title', '$last', '$cat', '')");
    url_redirect(getlink("&file=albmgr&cat=$cat"));
} 
/*if ((isset($_POST['mode']) && $_POST['mode'] == 'delalb') && intval($_POST['aid']) > 0) {
    $message = CONFIRM_DELETE1.'<br /><br />'.CONFIRM_DELETE2.'<br />
    <a href="'.getlink('&amp;file=delete&amp;id='.intval($_POST['aid']).'&amp;what=album').'">'.YES.'</a> / <a href="javascript:history.go(-1)">'.NO.'</a>';
    cpg_die('Delete album', $message, __FILE__, __LINE__, 0, 1);
}*/

pageheader(ALB_MRG);

starttable("100%", ALB_MRG, 1);

echo '<tr>';

//init.inc $cat = isset($_GET['cat']) ? ($_GET['cat']) : 0;

if ($cat == 1) $cat = 0;

if (GALLERY_ADMIN_MODE) {
    $result = $db->sql_query("SELECT aid, title, pos, description, thumb FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = $cat ORDER BY pos ASC");
} else {
    $result = $db->sql_query("SELECT aid, title, pos, description, thumb FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = " . (USER_ID + FIRST_USER_CAT) . " ORDER BY pos ASC");
}/*else cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
*/
$rowset = $db->sql_fetchrowset($result);

echo '<td class="tableb" valign="top" style="float:none;text-align:center">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
';

// Create category select dropdown
if (GALLERY_ADMIN_MODE) {
    $CAT_LIST = array();
    $CAT_LIST[] = array(FIRST_USER_CAT + USER_ID, MY_GALLERY);
    $CAT_LIST[] = array(0, NO_CATEGORY);
    get_subcat_data(0, '');

    echo '<tr>
            <td style="text-align:center"><form name="album_menu" method="post" action="'.getlink('&amp;file=albmgr',0,1).'" enctype="multipart/form-data" accept-charset="'._CHARSET.'">';
    echo '
        
            <b>'.SELECT_CATEGORY.'</b><br /><input type="hidden" name="name" value="'.$module_name.'" />
            <input type="hidden" name="file" value="'.$file.'" />';
echo <<<EOT
            <select name="cat" class="listbox">
EOT;
    foreach($CAT_LIST as $catory) {
        echo '            <option value="' . $catory[0] . '"' . ($cat == $catory[0] ? ' selected="selected"': '') . ">" . $catory[1] . "</option>\n";
    } 
    echo '
                </select><br />
            
            <input type="submit" class="button" value="'.SELECT_CATEGORY.'" /></form><br /><br />
            </td>
        </tr>
        
';
}

?>        <tr>
            <td>
                <table width="100%">
<?php
// Now let's create the list of albums that belong to the choosen category
if (count($rowset) > 0) {
    $result = $db->sql_query("SELECT pos FROM {$CONFIG['TABLE_ALBUMS']} WHERE category = $cat ORDER BY pos DESC LIMIT 0,1");
    list($last) = $db->sql_fetchrow($result);
    foreach ($rowset as $album) {
    $img = '<img src="'.$THEME_DIR.'/images/nopic.jpg" alt="" />';
    if ($album['thumb'] > 0) {
        $result = $db->sql_query("SELECT filepath, filename, pwidth, pheight FROM {$CONFIG['TABLE_PICTURES']} WHERE pid='$album[thumb]'");
        $picture = $db->sql_fetchrow($result);
        $img = '<a onclick="window.open(\''.getlink('&file=displayimagepopup&pid='.$album['thumb'].'&fullsize=1').'\',\'preview\',\'resizable=yes,scrollbars=yes,width='.($picture['pwidth']+30).',height='.($picture['pheight']+40).',left=0,top=0\');return false" target="preview" href="'.getlink('&amp;file=displayimagepopup&amp;pid='.$album['thumb'].'&amp;fullsize=1').'"><img src="'.get_pic_url($picture, 'thumb').'" alt=""  height="75" border="0" /></a>';
    }
    $result = $db->sql_query("SELECT COUNT(*) FROM {$CONFIG['TABLE_PICTURES']} WHERE aid='$album[aid]'");
    list($count) = $db->sql_fetchrow($result);
    /*if ($count > 0) {
        $count .= '<br /><a href="'.getlink('&amp;file=editpics&amp;album='.$album['aid']).'">Edit pictures</a>';
    }*/
    $MOD_ALB = str_replace(' my', '',MODIFYALB_LNK);
    $MOD_ALB = str_replace('s', '',$MOD_ALB);
    echo '<tr>
                <td rowspan="2" width="100" style="float:none;text-align:center">'.$img.'</td>
                <td class="tableh1" colspan="1" height="18"><b>'.$album['title'].'</b></td>
                <td colspan="6" class="tableh1" style="float:none;text-align:center"><b><span class="statlink">'.OPERATIONS.'</span></b></td>
            </tr>
            <tr>
                <td valign="top"><span style="float:left;">'.$album['description'].'</span><br />
                <form method="post" action="'.getlink("&amp;file=editpics",1,1).'" enctype="multipart/form-data" accept-charset="utf-8">
                        <input type="hidden" name="album" value="'.$album['aid'].'" />
                        <span style="float:left;">'.sprintf(N_PIC,$count).'<input name="submit" title="'.EDIT_PICS.'" type="image" src="' . $THEME_DIR . '/images/edit.gif" /></form></span>
                </td>
                <td width="25" style="float:none;text-align:center">
                    <form method="post" action="'.getlink("&amp;file=modifyalb",1,1).'" enctype="multipart/form-data" accept-charset="utf-8">
                        <input type="hidden" name="album" value="'.$album['aid'].'" />
                        <input name="submit" title="'.EDIT.' '.MODIFY.'" type="image" src="' . $THEME_DIR . '/images/edit.gif" /></form>' . "\n".'
                </td>
                <td width="25" style="float:none;text-align:center">
                    <form method="post" action="'.getlink("&amp;file=delete",1,1).'" enctype="multipart/form-data" accept-charset="utf-8">
                        <input type="hidden" name="what" value="album" />
                        <input type="hidden" name="id" value="'.$album['aid'].'" />
                        <input name="submit" title="'.DELETE.' '.ALBUM.'" type="image" src="' . $THEME_DIR . '/images/delete.gif" /></form>' . "\n".'

                    </td><td width="25" style="float:none;text-align:center">';
                if ($album['pos'] > 0) {
                    echo '<form method="post" action="'.getlink("&amp;file=albmgr",1,1).'" enctype="multipart/form-data" accept-charset="utf-8">
               <input type="hidden" name="move" value="top" />
                <input type="hidden" name="aid" value="'.$album['aid'].'" />
               <input name="submit" type="image" src="images/top.gif" /></form>'. "\n".
               
                    '</td><td width="25" style="float:none;text-align:center">
                    <form method="post" action="'.getlink("&amp;file=albmgr",1,1).'" enctype="multipart/form-data" accept-charset="utf-8">
                <input type="hidden" name="move" value="up" />
                <input type="hidden" name="aid" value="'.$album['aid'].'" />
               <input name="submit" type="image" src="images/up.gif" /></form>'. "\n";               
                }else {
                    echo'&nbsp;</td><td width="25" style="float:none;text-align:center">&nbsp;</td>';
                }
                echo'</td><td width="25" style="float:none;text-align:center">';
                if ($album['pos'] < $last) {
                    echo '
                    <form method="post" action="'.getlink("&amp;file=albmgr",1,1).'" enctype="multipart/form-data" accept-charset="utf-8">
                        <input type="hidden" name="move" value="down" />
                        <input type="hidden" name="aid" value="'.$album['aid'].'" />
                        <input name="submit" type="image" src="images/down.gif" /></form>'. "\n". 
                    '</td><td width="25" style="float:none;text-align:center">
                    <form method="post" action="'.getlink("&amp;file=albmgr",1,1).'" enctype="multipart/form-data" accept-charset="utf-8">
               <input type="hidden" name="move" value="bottom" />
                <input type="hidden" name="aid" value="'.$album['aid'].'" />
               <input name="submit" type="image" src="images/bottom.gif" /></form>'. "\n";
                }else {
                    echo'&nbsp;</td><td width="25" style="float:none;text-align:center">&nbsp;';
                }
                echo "</td></tr>\n\n";
                }
            } else {
                echo '<tr><td colspan="7" style="float:none;text-align:center" height="75"><b>No albums in this category</b></td></tr>';
            }
echo '</table><hr />
                </td>
            </tr>
        </table>
        </td>
    </tr>
    <tr><td colspan="2" class="tablef">
    <form name="new_album" method="post" action="'.getlink('&amp;file=albmgr').'" enctype="multipart/form-data" accept-charset="'._CHARSET.'">
    <input type="hidden" name="cat" value="'.$cat.'" />
    <input type="hidden" name="mode" value="addalb" />
    
        
        <input type="text" name="title" size="27" maxlength="80" /> <input type="submit" class="button" value="'.NEW_ALBUM.'" />
        </td>
    </tr>
    </form>';
endtable();
pagefooter();