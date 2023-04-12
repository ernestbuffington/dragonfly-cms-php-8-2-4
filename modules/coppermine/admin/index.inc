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
  $Source: /cvs/html/modules/coppermine/admin/index.inc,v $
  $Revision: 1.7 $
  $Author: nanocaiordo $
  $Date: 2007/05/28 11:38:47 $
****************************************************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($op)) { exit; }
require("modules/" . $op . "/include/load.inc");
function form_label($text) {
    echo <<<EOT
        <tr>
                <td class="tableh2" colspan="2">
                        <b>$text</b>
                </td>
        </tr>
EOT;
} 
function form_input($text, $name) {
    global $CONFIG;
    $value = htmlprepare($CONFIG[$name]);
    echo <<<EOT
        <tr>
            <td width="300" class="tableb">
                        $text
        </td>
        <td  class="tableb" valign="top">
                <input type="text" class="textinput" style="width: 90%" name="$name" value="$value" />
                </td>
        </tr>
EOT;
} 
function form_yes_no($text, $name) {
    global $CONFIG;
    $value = $CONFIG[$name];
    $yes_selected = $value ? 'selected="selected"' : '';
    $no_selected = !$value ? 'selected="selected"' : '';
    echo '
        <tr>
            <td class="tableb">
            '.$text.'
            </td>
            <td class="tableb" valign="top">
                <select name="'.$name.'" class="listbox">
                        <option value="1" '.$yes_selected.'>'.YES.'</option>
                        <option value="0" '.$no_selected.'>'.NO.'</option>
                </select>
            </td>
        </tr>
';
} 
function gdVersion() {
    if (!extension_loaded('gd')) {
        return;
    }
    ob_start();
    phpinfo(8);
    $info=ob_get_contents();
    ob_end_clean();
    $info=stristr($info, 'gd version');
    preg_match('/\d/', $info, $gd);
    return $gd[0];
} // end function gdVersion()

function form_img_pkg($text, $name) {
    global $CONFIG;
    $value = $CONFIG[$name];
    $im_selected = ($value == 'im') ? 'selected="selected"' : '';
    $gd1_selected = $gd2_selected = '';
    if ($gdv = gdVersion()) {
        if ($gdv >=2) {
            $gd2_selected = '<option value="gd2"'.(($value == 'gd2') ? ' selected="selected"' : '').'>GD version 2.x</option>';
        } else {
            $gd1_selected = '<option value="gd1"'.(($value == 'gd1') ? ' selected="selected"' : '').'>GD version 1.x</option>';
        }
    }
    $netpbm_selected = ($value == 'netpbm') ? 'selected="selected"' : '';
    echo <<<EOT
        <tr>
            <td class="tableb">
            $text
            </td>
            <td class="tableb" valign="top">
                <select name="$name" class="listbox">
                        <option value="im" $im_selected>Image Magick</option>
                    $gd1_selected
                    $gd2_selected
                        <option value="netpbm" $netpbm_selected>netpbm</option>
                </select>
           </td>
        </tr>
EOT;
} 
function form_sort_order($text, $name) {
    global $CONFIG, $lang_config_php;
    $value = $CONFIG[$name];
    $ta_selected = ($value == 'ta') ? 'selected="selected"' : '';
    $td_selected = ($value == 'td') ? 'selected="selected"' : '';
    $na_selected = ($value == 'na') ? 'selected="selected"' : '';
    $nd_selected = ($value == 'nd') ? 'selected="selected"' : '';
    $da_selected = ($value == 'da') ? 'selected="selected"' : '';
    $dd_selected = ($value == 'dd') ? 'selected="selectdd"' : '';
    $ra_selected = ($value == 'ra') ? 'selected="selected"' : '';
    $rd_selected = ($value == 'rd') ? 'selected="selected"' : '';
    echo '
        <tr>
            <td class="tableb">
                        '.$text.'
            </td>
            <td class="tableb" valign="top">
                <select name="'.$name.'" class="listbox">
                    <option value="ta" '.$ta_selected.'>'.TITLE_A.'</option>
                    <option value="td" '.$td_selected.'>'.TITLE_D.'</option>
                    <option value="na" '.$na_selected.'>'.NAME_A.'</option>
                    <option value="nd" '.$nd_selected.'>'.NAME_D.'</option>
                    <option value="da" '.$da_selected.'>'.DATE_A.'</option>
                    <option value="dd" '.$dd_selected.'>'.DATE_D.'</option>
                    <option value="ra" '.$ra_selected.'>'.RATING_A.'</option>
                    <option value="rd" '.$rd_selected.'>'.RATING_A.'</option>
                </select>
            </td>
        </tr>
    ';
} 

function form_theme($text, $name) {
    global $CONFIG, $CPG_M_DIR;
    //if ($name == "cpgtheme") $value = $CONFIG["theme"];
    //else 
    $value = $CONFIG['theme'];
    $theme_dir = $CPG_M_DIR . '/themes/';
    $dir = opendir($theme_dir);
    while ($file = readdir($dir)) {
        if (is_dir($theme_dir . $file) && $file != "." && $file != ".." && $file != "CVS") {
            $theme_array[] = $file;
        } 
    } 
    closedir($dir);
    natcasesort($theme_array);
    echo <<<EOT
        <tr>
            <td class="tableb">
                        $text
        </td>
        <td class="tableb" valign="top">
                        <select name="$name" class="listbox">
EOT;
    foreach ($theme_array as $theme) {
        echo "<option value=\"$theme\" " . ($value == $theme ? 'selected="selected"' : '') . ">" . strtr(ucfirst($theme), '_', ' ') . "</option>\n";
    } 
    echo <<<EOT
                        </select>
                </td>
        </tr>
EOT;
} 
// Added for allowing user to select which aspect of thumbnails to scale
function form_scale($text, $name) {
    global $CONFIG, $lang_config_php ;
    $value = $CONFIG[$name];
    $any_selected = ($value == 'max') ? 'selected' : '';
    $ht_selected = ($value == 'ht') ? 'selected' : '';
    $wd_selected = ($value == 'wd') ? 'selected' : ''; 
    // bug fix scott http://coppermine.sourceforge.net/board/viewtopic.php?t=441
    $any_name = $lang_config_php['th_any'];
    $ht_name = $lang_config_php['th_ht'];
    $wd_name = $lang_config_php['th_wd'];
    echo <<<EOT
        <tr>
            <td class="tableb">
                        $text
            </td>
            <td class="tableb" valign="top">
                <select name="$name" class="listbox">
                    <option value="any" $any_selected>$any_name</option>
                    <option value="ht" $ht_selected>$ht_name</option>
                    <option value="wd" $wd_selected>$wd_name</option>
                </select>
            </td>
        </tr>
EOT;
} 
function create_form(&$data) {
    global $CPG_M_DIR;
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
                    form_img_pkg($element[0], $element[1]);
                    break;
                case 3 :
                    form_sort_order($element[0], $element[1]);
                    break;
                case 4 :
                    //form_charset($element[0], $element[1]);
                    break;
                case 5 :
                    break;
                case 6 :
                    form_theme($element[0], $element[1]);
                    break; 
                // Thumbnail scaling
                case 7 :
                    form_scale($element[0], $element[1]);
                    break; 
                // Add triple dropdown later - put back in for compatibility reasons
                case 8 : 
                    // do nothing
                    break;
                default:
                    die('Invalid action');
            } // switch
        } else {
            form_label($element);
        } 
    } 
} 
if (count($_POST) > 0) {
    $msg = UPD_SUCCESS;
    $redirect = getlink();
    if (isset($_POST['update_config'])) {
        $need_to_be_positive = array(
            'albums_per_page',
            'albums_per_page',
            'album_list_cols',
            'max_tabs',
            'picture_width',
            'thumb_width',
            'thumbcols',
            'thumbrows', 
            // Show filmstrip
            'max_film_strip_items'
         );
        foreach ($need_to_be_positive as $parameter)
            $_POST[$parameter] = max(1, intval($_POST[$parameter]));
        $_POST['subcat_level'] = max(0, intval($_POST['subcat_level']));
        foreach($lang_config_data as $element) {
            if ((is_array($element))) {
                if ((!isset($_POST[$element[1]]))) {
                    cpg_die(_CRITICAL_ERROR, sprintf(_ERROR_NOT_SET, $element[1]), __FILE__, __LINE__);
                }
                $value = Fix_Quotes($_POST[$element[1]]);
                $db->sql_query("UPDATE {$CONFIG['TABLE_CONFIG']} SET value='$value' WHERE name='{$element[1]}'",false,__FILE__, __LINE__);
            } 
        }
    } elseif (isset($_POST['restore_config'])) {
        require_once('includes/classes/sqlctrl.php');
        if (!SQLCtrl::query_file('includes/coppermine/restore_cfg.gz', $error, array('cms_cpg_'=>$cpg_prefix))) {
            cpg_error($error);
        }
        $msg = RESTORE_SUCCESS;
        $redirect = getlink('&file=config');
    }
    url_refresh($redirect);
    pageheader(CONFIG_TITLE,$redirect);
    msg_box(INFO, $msg, CONTINU, getlink());
    pagefooter();
}
$pagetitle .= ' '._BC_DELIM.' '.CONFIG_TITLE;
$signature = 'Coppermine Photo Gallery ' . $CPG_VERSION;
require_once('header.php');
GraphicAdmin('_AMENU8');
OpenTable();
echo '<fieldset style="width:45%;float:left;"><legend> '._coppermineLANG.' '._ADMINISTRATION.' </legend>
<form action="'.getlink('', false).'" method="get" accept-charset="utf-8">';
$result = $db->sql_query("SELECT dirname FROM ".$prefix."_cpg_installs", false, __FILE__, __LINE__);
if ($db->sql_numrows($result) > 1) {
    $multiinstalls = true;
    echo '
<label class="ulog" for="name">'._W_INSTALL.'</label>
 <select class="ulog" name="name" id="name">';
    while (list($cpg_dir) = $db->sql_fetchrow($result)) {
        echo '<option value="'.$cpg_dir.'">'.$cpg_dir.'</option>';
    }
    echo '
</select><br />';
} else {
	$multiinstalls = false;
    list($cpg_dir) = $db->sql_fetchrow($result);
    echo '<input type="hidden" name="name" id="name" value="'.$cpg_dir.'" />';
}
$db->sql_freeresult($result);
echo '
<label class="ulog" for="file">'._W_PAGE.'</label>
 <select class="ulog" name="file" id="file">
  <option value="albmgr">'.ALBUMS_LNK.'</option>
  <option value="searchnew">'.SEARCHNEW_LNK.'</option>
  <option value="catmgr">'.CATEGORIES_LNK.'</option>
  <option value="groupmgr">'.GROUPS_LNK.'</option>
  <option value="help">'.HELP_TITLE.'</option>
  <option value="reviewcom">'.COMMENTS_LNK.'</option>
  <option value="search">'.SEARCH_LNK.'</option>
  <option value="upload">'.UPLOAD_PIC_LNK.'</option>
  <option value="usermgr">'.USERS_LNK.'</option>
 </select>
<input type="submit" value="'._GO.'" />
</form></fieldset>';
if($multiinstalls) {
	reset($cpg_dir);
	echo '<fieldset  style="width:45%;float:left;"><legend> '._coppermineLANG.' '.CONFIG_TITLE.' </legend>
			<form action="'.adminlink().'" method="post" accept-charset="utf-8">
				<select class="ulog" name="op" id="op">';
	foreach ($cpg_dir as $cpg_cdir  )  {
	        echo '		<option value="'.$cpg_cdir.'">'.$cpg_cdir.' '.CONFIG_LNK.'</option>';
	    }
	echo '		</select>
				<input type="submit" value="'._GO.'" />
			</form></fieldset>';
}
echo '<br clear="all" />';
echo '
    	<form action="'.adminlink().'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
';
starttable('100%', CONFIG_TITLE." - $signature", 2);
create_form($lang_config_data);
echo '
  <tr>
    <td colspan="2">
      <table width="100%" class="tablef" >
        <tr>
	        <td>
	          <input style="float:right" type="submit" class="button" name="update_config" value="'.SAVE_CFG.'" />
	        </td>
	        <td>
            <input style="float:left"  type="submit" class="button" name="restore_config" value="'.RESTORE_CFG.'" />
	        </td>
        </tr>
      </table>
    </td>
	</tr>';
endtable();
echo '</form>';
CloseTable();
