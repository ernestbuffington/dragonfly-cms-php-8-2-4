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
  $Source: /cvs/html/modules/coppermine/catmgr.php,v $
  $Revision: 9.3 $
  $Author: brennor $
  $Date: 2006/02/08 19:12:25 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
define('CATMGR_PHP', true);
require("modules/" . $module_name . "/include/load.inc");

if (!GALLERY_ADMIN_MODE) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);
// Fix categories that have an invalid parent
function fix_cat_table()
{
	global $CONFIG, $db;
	$result = $db->sql_query("SELECT cid FROM {$CONFIG['TABLE_CATEGORIES']}",false, __FILE__, __LINE__);
	if ($db->sql_numrows($result) > 0) {
		$set = array();
		while ($row = $db->sql_fetchrow($result)) $set[] = $row['cid'];
		$db->sql_query('UPDATE '.$CONFIG['TABLE_CATEGORIES'].' SET parent = 0 WHERE parent=cid OR parent NOT IN ('.implode(',',$set).')');
	}
	//$db->sql_freeresult($result);
}

function get_subcat_data($parent, $ident = '')
{
	global $CONFIG, $CAT_LIST, $db;
	$rowset = $db->sql_ufetchrowset('SELECT cid, catname, description FROM '.$CONFIG['TABLE_CATEGORIES']." WHERE parent = '$parent' ORDER BY pos",SQL_BOTH,__FILE__,__LINE__);
	if (($cat_count = is_countable($rowset) ? count($rowset) : 0) > 0) {
		$pos = 0;
		foreach ($rowset as $subcat) {
			if ($pos > 0) {
				$CAT_LIST[] = array('cid' => $subcat['cid'],
					'parent' => $parent,
					'pos' => $pos++,
					'prev' => $prev_cid,
					'cat_count' => $cat_count,
					'catname' => $ident . $subcat['catname']);
				$CAT_LIST[$last_index]['next'] = $subcat['cid'];
			} else {
				$CAT_LIST[] = array('cid' => $subcat['cid'],
					'parent' => $parent,
					'pos' => $pos++,
					'cat_count' => $cat_count,
					'catname' => $ident . $subcat['catname']);
			}
			$prev_cid = $subcat['cid'];
			$last_index = count($CAT_LIST) -1;
			get_subcat_data($subcat['cid'], $ident . '&nbsp;&nbsp;&nbsp;');
		}
	}
}

function update_cat_order()
{
	global $CAT_LIST, $CONFIG, $db,$THEME_DIR;
	foreach ($CAT_LIST as $category)
		$db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET pos='{$category['pos']}' WHERE cid = '{$category['cid']}'",false, __FILE__, __LINE__);
}

//<form method="post" action="'.getlink("&amp;file=dbinput",0,1).'" enctype="multipart/form-data" accept-charset="utf-8">
//</form>
function cat_list_box($highlight = 0, $curr_cat, $on_change_refresh = true)
{
	global $CAT_LIST, $CPG_URL,$module_name,$file;
	$lb ='<form method="post" action="'.getlink("&amp;file=catmgr",0,1).'" enctype="multipart/form-data" accept-charset="utf-8">';
	if ($on_change_refresh) {
		$lb .= '<input type="hidden" name="name" value="'.$module_name.'" />
		<input type="hidden" name="file" value="'.$file.'" />
		<input type="hidden" name="oppe" value="setparent" />
		<input type="hidden" name="cid" value="'.$curr_cat.'" />';
	}
	$lb .= '
		<select name="parent" class="listbox">
		<option value="0"' . ($highlight == 0 ? ' selected="selected"': '') . ">* No Category *</option>\n";
	foreach($CAT_LIST as $category) {
		if ($category['cid'] != 1 && $category['cid'] != $curr_cat) {
			$lb .= '<option value="' . $category['cid'] . '"' . ($highlight == $category['cid'] ? ' selected="selected"': '') . ">" . $category['catname'] . "</option>\n";
		}
	}
	$lb .= '</select>';
	if ($on_change_refresh) {
		$lb .= '<input type="submit" class="button" value="'._CHANGE.' '.CATEGORY.'" />
		</form>';
	}
	return $lb;
} 
function form_alb_thumb()
{
	$lang_catmgr_php = [];
 global $CONFIG, $current_category, $cid, $db;
	$results = $db->sql_query("SELECT pid, filepath, filename, url_prefix FROM {$CONFIG['TABLE_PICTURES']},{$CONFIG['TABLE_ALBUMS']} WHERE {$CONFIG['TABLE_PICTURES']}.aid = {$CONFIG['TABLE_ALBUMS']}.aid AND {$CONFIG['TABLE_ALBUMS']}.category = '$cid' AND approved='1' ORDER BY filename",false, __FILE__, __LINE__);
	if (!$db->sql_numrows($results)) {
		echo '<tr>
			<td class="tableb" valign="top">'.ALB_THUMB.'</td>
			<td class="tableb" valign="top"><i>'.ALB_EMPTY.'</i>
			<input type="hidden" name="thumb" value="0" />
			</td>
		</tr>';
		return;
	}

	echo '
<script language="JavaScript" type="text/JavaScript">
var Pic = new Array();
Pic[0] = \'images/nopic.jpg\';
';
	$initial_thumb_url = 'images/nopic.jpg';
	$img_list = array(0 => LAST_UPLOADED);
	while ($picture = $db->sql_fetchrow($results)) {
		$thumb_url = get_pic_url($picture, 'thumb');
		echo "Pic[{$picture['pid']}] = '" . $thumb_url . "';\n";
		if ($picture['pid'] == $current_category['thumb']) $initial_thumb_url = $thumb_url;
		$img_list[$picture['pid']] = htmlprepare($picture['filename']);
	} // while
	echo '
function ChangeThumb(index) { document.images.Thumb.src = Pic[index]; }
</script>
';
	$thumb_cell_height = $CONFIG['thumb_width'] + 17;
	echo '
	<tr>
		<td class="tableb" valign="top">'.$lang_catmgr_php['cat_thumb'].'</td>
		<td class="tableb" style="float:none;text-align:center">
		<table cellspacing="0" cellpadding="5" border="0">
		<tr>
			<td width="'.$thumb_cell_height.'" height="'.$thumb_cell_height.'" style="float:none;text-align:center">
			<img src="'.$initial_thumb_url.'" alt="" name="Thumb" class="image" /><br />
			</td>
		</tr>
		</table>
		<select name="thumb" class="listbox" onchange="if(this.options[this.selectedIndex].value) ChangeThumb(this.options[this.selectedIndex].value);" onkeyup="if(this.options[this.selectedIndex].value) ChangeThumb(this.options[this.selectedIndex].value);">
';
	foreach($img_list as $pid => $pic_name) {
		echo '<option value="' . $pid . '"' . ($pid == $current_category['thumb'] ? ' selected="selected"':'') . '>' . $pic_name . "</option>\n";
	} 
	echo '
		</select>
	</td>
	</tr>';
}

function display_cat_list()
{
	global $CAT_LIST, $opp, $CPG_M_DIR,$THEME_DIR;

	$CAT_LIST3 = $CAT_LIST;

	foreach ($CAT_LIST3 as $key => $category) {
		echo "<tr>\n";
		echo '<td class="tableb" width="80%"><b>' . $category['catname'] . '</b></td>' . "\n";

		if ($category['pos'] > 0) {
			echo '<td class="tableb" width="4%" style="float:none;text-align:center">
				<form method="post" action="'.getlink("&amp;file=catmgr",0,1).'" enctype="multipart/form-data" accept-charset="utf-8">
				<input type="hidden" name="oppe" value="move" />
				<input type="hidden" name="cid1" value="'.$category['cid'].'" />
				<input type="hidden" name="pos1" value="'.($category['pos']-1).'" />
				<input type="hidden" name="cid2" value="'.$category['prev'].'" />
				<input type="hidden" name="pos2" value="'.$category['pos'].'" />
			   <input name="submit" type="image" src="' . $THEME_DIR . '/images/up.gif" border="0" /></form>'. "\n";
		} else {
			echo '<td class="tableb" width="4%">&nbsp;</td>'."\n";
		}

		if ($category['pos'] < $category['cat_count']-1) {
			echo '<td class="tableb" width="4%" style="float:none;text-align:center">
				<form method="post" action="'.getlink("&amp;file=catmgr",0,1).'" enctype="multipart/form-data" accept-charset="utf-8">
				<input type="hidden" name="oppe" value="move" />
				<input type="hidden" name="cid1" value="'.$category['cid'].'" />
				<input type="hidden" name="pos1" value="'.($category['pos']+1).'" />
				<input type="hidden" name="cid2" value="'.$category['next'].'" />
				<input type="hidden" name="pos2" value="'.$category['pos'].'" />
				<input name="submit" type="image" src="' . $THEME_DIR . '/images/down.gif" border="0" /></form>' . "\n";
		} else {
			echo '<td class="tableb" width="4%">&nbsp;</td>'."\n";
		} 

		if ($category['cid'] != 1) {
			echo '<td class="tableb" width="4%" style="float:none;text-align:center">
			<form method="post" action="'.getlink("&amp;file=catmgr",0,1).'" enctype="multipart/form-data" accept-charset="utf-8">
				<input type="hidden" name="oppe" value="deletecat" />
				<input type="hidden" name="cid" value="'.$category['cid'].'" />
				<input type="hidden" name="catname" value="'.$category['catname'].'" />
				<input name="submit" title="'.DELETE.'" type="image" src="' . $THEME_DIR . '/images/delete.gif" border="0" /></form>' . "\n";
			   
		} else {
			echo '<td class="tableb" width="4%">&nbsp;</td>'."\n";
		} 

		echo '<td class="tableb" width="4%" style="float:none;text-align:center"> 
		<form method="post" action="'.getlink("&amp;file=catmgr",0,1).'" enctype="multipart/form-data" accept-charset="utf-8">
			<input type="hidden" name="oppe" value="editcat" />
			<input type="hidden" name="cid" value="'.$category['cid'].'" />
			<input name="submit" title="'.EDIT.'" type="image" src="' . $THEME_DIR . '/images/edit.gif" border="0" /></form>' . "\n";

		if ($category['cid'] != 1) {
			echo '<td class="tableb" width="4%" style="float: none; text-align: center">' . "\n" .	
			cat_list_box($category['parent'], $category['cid']) . "\n" . '</td>' . "\n";
		} else {
			echo '<td class="tableb" width="4%">&nbsp;</td>'."\n";
		}
		echo "</tr>\n";
	} 
} 

$opp = $_POST['opp'] ?? '';

$current_category = array('cid' => '0', 'catname' => '', 'parent' => '0', 'description' => '');
switch ($opp) {
	case 'move':
		if (!isset($_POST['cid1']) || !isset($_POST['cid2']) || !isset($_POST['pos1']) || !isset($_POST['pos2'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'move'), __FILE__, __LINE__);
		$cid1 = intval($_POST['cid1']) ? $_POST['cid1'] : cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'cid1'), __FILE__, __LINE__);
		$cid2 = intval($_POST['cid2']) ? $_POST['cid2'] : cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'cid2'), __FILE__, __LINE__);
		$pos1 = intval($_POST['pos1']) ? $_POST['pos1'] : cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'pos1'), __FILE__, __LINE__);
		$pos2 = intval($_POST['pos2'])? $_POST['pos2'] : cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'pos2'), __FILE__, __LINE__);
		$db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET pos='$pos1' WHERE cid = '$cid1'",false, __FILE__, __LINE__);
		$db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET pos='$pos2' WHERE cid = '$cid2'",false, __FILE__, __LINE__);
		break;

	case 'setparent':
		if (!isset($_POST['cid']) || !isset($_POST['parent'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'setparent'), __FILE__, __LINE__);
		$cid = intval($_POST['cid']);
		$parent = intval($_POST['parent']);
		$db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET parent='$parent', pos='-1' WHERE cid = '$cid'",false, __FILE__, __LINE__);
		break;

	case 'editcat':
		if (!isset($_POST['cid'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'editcat'), __FILE__, __LINE__);
		$cid = intval($_POST['cid']);
		$result = $db->sql_query("SELECT cid, catname, parent, description FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid = '$cid'",false, __FILE__, __LINE__);
		if (!$db->sql_numrows($result)) cpg_die(_ERROR, UNKNOWN_CAT, __FILE__, __LINE__);
		$current_category = $db->sql_fetchrow($result);
		break;

	case 'updatecat':
		if (!isset($_POST['cid']) || !isset($_POST['parent']) || !isset($_POST['catname']) || !isset($_POST['description'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'updatecat'), __FILE__, __LINE__);
		$cid = intval($_POST['cid']);
		$parent = intval($_POST['parent']);
		$catname = trim($_POST['catname']) ? Fix_Quotes($_POST['catname']) : '&lt;???&gt;';
		$description = Fix_Quotes($_POST['description']);
		$db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET parent='$parent', catname='$catname', description='$description' WHERE cid = '$cid'",false, __FILE__, __LINE__);
		break;

	case 'createcat':
		if (!isset($_POST['parent']) || !isset($_POST['catname']) || !isset($_POST['description'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'createcat'), __FILE__, __LINE__);
		$parent = intval($_POST['parent']);
		$catname = trim($_POST['catname']) ? Fix_Quotes($_POST['catname']) : '&lt;???&gt;';
		$description = Fix_Quotes($_POST['description']);
		$db->sql_query("INSERT INTO {$CONFIG['TABLE_CATEGORIES']} (pos, parent, catname, description) VALUES ('10000', '$parent', '$catname', '$description')",false, __FILE__, __LINE__);
		break;
} 

$oppe = $_POST['oppe'] ?? '';

$current_category = array('cid' => '0', 'catname' => '', 'parent' => '0', 'description' => '');
switch ($oppe) {
	case 'move':
		if (!isset($_POST['cid1']) || !isset($_POST['cid2']) || !isset($_POST['pos1']) || !isset($_POST['pos2'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'move'), __FILE__, __LINE__);
		$cid1 = intval($_POST['cid1']);
		$cid2 = intval($_POST['cid2']);
		$pos1 = intval($_POST['pos1']);
		$pos2 = intval($_POST['pos2']);
		$db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET pos='$pos1' WHERE cid = '$cid1'",false, __FILE__, __LINE__);
		$db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET pos='$pos2' WHERE cid = '$cid2'",false, __FILE__, __LINE__);
		break;

	case 'setparent':
		if (!isset($_POST['cid']) || !isset($_POST['parent'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'setparent'), __FILE__, __LINE__);
		$cid = intval($_POST['cid']);
		$parent = intval($_POST['parent']);
		$db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET parent='$parent', pos='-1' WHERE cid = '$cid'",false, __FILE__, __LINE__);
		break;

	case 'editcat':
		if (!isset($_POST['cid'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'cid'), __FILE__, __LINE__);
		$cid = intval($_POST['cid']);
		$result = $db->sql_query("SELECT cid, catname, parent, description FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid = '$cid'",false, __FILE__, __LINE__);
		if (!$db->sql_numrows($result)) cpg_die(_ERROR, UNKNOWN_CAT, __FILE__, __LINE__);
		$current_category = $db->sql_fetchrow($result);
		break;

	case 'updatecat':
		if (!isset($_POST['cid']) || !isset($_POST['parent']) || !isset($_POST['catname']) || !isset($_POST['description'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'updatecat'), __FILE__, __LINE__);
		$cid = intval($_POST['cid']);
		$parent = intval($_POST['parent']);
		$catname = trim($_POST['catname']) ? Fix_Quotes($_POST['catname']) : '&lt;???&gt;';
		$description = Fix_Quotes($_POST['description']);
		$db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET parent='$parent', catname='$catname', description='$description' WHERE cid = '$cid'",false, __FILE__, __LINE__);
		break;

	case 'createcat':
		if (!isset($_POST['parent']) || !isset($_POST['catname']) || !isset($_POST['description'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'createcat'), __FILE__, __LINE__);
		$parent = intval($_POST['parent']);
		$catname = trim($_POST['catname']) ? Fix_Quotes($_POST['catname']) : '&lt;???&gt;';
		$description = Fix_Quotes($_POST['description']);
		$db->sql_query("INSERT INTO {$CONFIG['TABLE_CATEGORIES']} (pos, parent, catname, description) VALUES ('10000', '$parent', '$catname', '$description')",false, __FILE__, __LINE__);
		break;

	case 'deletecat':
		if (!isset($_POST['cid'])) cpg_die(_CRITICAL_ERROR, sprintf(MISS_PARAM, 'deletecat'), __FILE__, __LINE__);
		$cid = intval($_POST['cid']);
		if(isset($_POST['cancel'])) {
			$redirect = ($CPG_SESS['user']['redirect'] ?? getlink("&file=catmgr"));
			url_redirect($redirect);
		}
		if (!isset($_POST['confirm'])) {
			$msg = CONFIRM_DELETE_CAT.$_POST['catname'];
			cpg_delete_msg(getlink("&amp;file=catmgr"),$msg,'<input type="hidden" name="oppe" value="deletecat" />
			<input type="hidden" name="cid" value="'.$cid.'" />');	   
		} else {
			$result = $db->sql_query("SELECT parent FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid = '$cid'",false, __FILE__, __LINE__);
			if ($cid == 1) cpg_die(_ERROR, USERGAL_CAT_RO, __FILE__, __LINE__);
			if (!$db->sql_numrows($result)) cpg_die(_ERROR, UNKNOWN_CAT, __FILE__, __LINE__);
			$del_category = $db->sql_fetchrow($result);
			$parent = $del_category['parent'];
			$result = $db->sql_query("UPDATE {$CONFIG['TABLE_CATEGORIES']} SET parent='$parent' WHERE parent = '$cid'",false, __FILE__, __LINE__);
			$result = $db->sql_query("UPDATE {$CONFIG['TABLE_ALBUMS']} SET category='$parent' WHERE category = '$cid'",false, __FILE__, __LINE__);
			$result = $db->sql_query("DELETE FROM {$CONFIG['TABLE_CATEGORIES']} WHERE cid='$cid'",false, __FILE__, __LINE__);
		}
		break;
} 

fix_cat_table();
get_subcat_data(0);
update_cat_order();
//define('META_LNK','&cat=0');
pageheader(MANAGE_CAT);
echo '
<script language="javascript">
function confirmDel(catName) { return confirm("'.CONFIRM_DELETE_CAT.' (" + catName + ") ?"); }
</script>
';

starttable('100%');
echo '
	<tr>
		<td class="tableh1"><b><span class="statlink">'.CATEGORY.'</span></b></td>
		<td colspan="4" class="tableh1" style="float: none; text-align: center"><b><span class="statlink">'.OPERATIONS.'</span></b></td>
		<td class="tableh1" style="float: none; text-align: center"><b><span class="statlink">'.MOVE_INTO.'</span></b></td>
	</tr>
';

display_cat_list();

echo '
	</form>
';

endtable();

echo "<br />\n";

starttable('100%', UPDATE_CREATE, 2);
$lb = cat_list_box($current_category['parent'], $current_category['cid'], false);
$opp = $current_category['cid'] ? 'updatecat' : 'createcat';
echo '
	<form method="post" action="'.getlink("&amp;file=catmgr",0,1).'" enctype="multipart/form-data" accept-charset="utf-8">
	<input type="hidden" name="cid" value ="'.$current_category['cid'].'">
	<tr>
		<td width="40%" class="tableb">'.PARENT_CAT.'</td>
		<td width="60%" class="tableb" valign="top">'.$lb.'</td>
	</tr><tr>
		<td width="40%" class="tableb">'.CAT_TITLE.'</td>
		<td width="60%" class="tableb" valign="top">
				<input type="text" style="width: 100%" name="catname" value="'.$current_category['catname'].'" class="textinput" />
		</td>
	</tr><tr>
		<td class="tableb" valign="top">'.CAT_DESC.'</td>
		<td class="tableb" valign="top">
				<textarea name="description" rows="5" cols="40" size="9" wrap="virtual" style="width: 100%;" class="textinput">'.$current_category['description'].'</textarea>
		</td>
	</tr><tr>
		<td colspan="2" style="float: none; text-align: center" class="tablef">
<input type="hidden" name="opp" value="'.$opp.'" /><input type="submit" value="'.UPDATE_CREATE.'" class="button" /></form>
		</td>
	</tr>';
endtable();
pagefooter();
