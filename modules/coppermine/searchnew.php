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
  $Source: /public_html/modules/coppermine/searchnew.php,v $
  $Revision: 9.7 $
  $Author: nanocaiordo $
  $Date: 2007/12/19 21:33:29 $
****************************************************************************/
if (!defined('CPG_NUKE')) { exit; }
define('SEARCHNEW_PHP', true);
require("modules/" . $module_name . "/include/load.inc");

if (!GALLERY_ADMIN_MODE) cpg_die(_ERROR, ACCESS_DENIED, __FILE__, __LINE__);
if ($CLASS['member']->demo){
	pageheader(PERM_DENIED);
	cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
	pagefooter();
}
/**
 * Local functions definition
 */

/**
 * albumselect()
 * 
 * return the HTML code for a listbox with name $id that contains the list
 * of all albums
 * 
 * @param string $id the name of the listbox
 * @return the HTML code
 */
function albumselect($id = 'album')
{
	global $CONFIG;
	static $select = '';
	if ($select == '') {
		$rowset = get_albumlist();
		$select = '<option value="0">' . SELECT_ALBUM . '</option>\n';
		foreach ($rowset as $row) {
			$select .= '<option value="' . $row["aid"] . '">' . $row["title"] . '</option>';
		} 
	} 
	return '<select name="'.$id.'" class="listbox">'.$select.'</select>';
}

/**
 * dirheader()
 * 
 * return the HTML code for the row to be displayed when we start a new
 * directory
 * 
 * @param  $dir the directory
 * @param  $dirid the name of the listbox that will list the albums
 * @return the HTML code
 */
function dirheader($dir, $dirid)
{
	global $CONFIG;
	$warning = '';
	if (!is_writable($CONFIG['fullpath'] . $dir))
		$warning = '<tr><td class="tableh2" valign="middle" colspan="3"><b>
				   '.WARNING.'</b>: '.CHANGE_PERM.'</td></tr>';
	return '<tr><td class="tableh2" valign="middle" colspan="3">' .
		sprintf(TARGET_ALBUM, $dir, albumselect($dirid)) . '</td></tr>' . $warning;
} 

/**
 * picrow()
 * 
 * return the HTML code for a row to be displayed for an image
 * the row contains a checkbox, the image name, a thumbnail
 * 
 * @param  $picfile the full path of the file that contains the picture
 * @param  $picid the name of the check box
 * @return the HTML code
 */
function picrow($picfile, $picid, $albid)
{
	global $db, $CONFIG, $expic_array, $module_name;

	$encoded_picfile = base64_encode($picfile);
	$picname = $CONFIG['fullpath'] . $picfile;
	$pic_url = urlencode($picfile);
	$pic_fname = basename($picfile);
	$pic_dname = substr($picname, 0, -(strlen($pic_fname)));
	if ($CONFIG['samename'] == 1 ){
		$sql = "SELECT * FROM " . $CONFIG['TABLE_PICTURES'] . " WHERE filename='".Fix_Quotes($pic_fname)."' AND filepath='$pic_dname'";
	} else { 
		$sql = "SELECT * FROM " . $CONFIG['TABLE_PICTURES'] . " WHERE filename ='".Fix_Quotes($pic_fname)."'";
	}
	$result = $db->sql_query($sql);

	$exists = $db->sql_numrows($result);

	while ($exists <= 0) {
		$thumb_file = dirname($picname) . '/' . $CONFIG['thumb_pfx'] . $pic_fname;
		if (file_exists($thumb_file)) {
			$thumb_info = getimagesize($picname);
			$thumb_size = compute_img_size($thumb_info[0], $thumb_info[1], 48);
			$img = '<img src="' . path2url($picname) . '" ' . $thumb_size['geom'] . ' class="thumbnail" border="0" alt="" />';
		} else {
			$img = '<img src="' . getlink($module_name . '&amp;file=showthumbbatch&amp;picfile=' . $pic_url . '&amp;size=48',0).'" class="thumbnail" border="0" alt="" />';
		} 
		$piclink = getlink("&file=displayimagepopup&fullsize=1&picfile=$pic_url");	   
		if (filesize($picname) && is_readable($picname)) {
			$fullimagesize = getimagesize($picname);
			$winsizeX = ($fullimagesize[0] + 16);
			$winsizeY = ($fullimagesize[1] + 16);

			$checked = isset($expic_array[$picfile]) || !$fullimagesize ? '' : 'checked';
			return <<<EOT
		<tr>
				<td class="tableb" valign="middle">
						<input name="pics[]" type="checkbox" value="$picid" $checked />
						<input name="album_lb_id_$picid" type="hidden" value="$albid" />
						<input name="picfile_$picid" type="hidden" value="$encoded_picfile" />
				</td>
				<td class="tableb" valign="middle" width="100%">
						<a href="javascript:;" onclick= "MM_openBrWindow('$piclink', 'ImageViewer', 'toolbar=yes, status=yes, resizable=yes, scrollbars=yes, width=$winsizeX, height=$winsizeY')">$pic_fname</a>
				</td>
				<td class="tableb" valign="middle" align="center">
						<a href="javascript:;" onclick= "MM_openBrWindow('$piclink', 'ImageViewer', 'toolbar=yes, status=yes, resizable=yes, scrollbars=yes, width=$winsizeX, height=$winsizeY')">$img<br /></a>
				</td>
		</tr>
EOT;
		} else {
			$winsizeX = (300);
			$winsizeY = (300);
			return <<<EOT
		<tr>
				<td class="tableb" valign="middle">
						&nbsp;
				</td>
				<td class="tableb" valign="middle" width="100%">
						<i>$pic_fname</i>
				</td>
				<td class="tableb" valign="middle" align="center">
						<a href="javascript:;" onclick= "MM_openBrWindow('$piclink', 'ImageViewer', 'toolbar=yes, status=yes, resizable=yes, scrollbars=yes, width=$winsizeX, height=$winsizeY')"><img src="'.getlink(&amp;file=showthumbbatch&amp;picfile=$pic_url&amp;size=48).'" class="thumbnail" border="0" alt="" /><br /></a>
				</td>
		</tr>
EOT;
		} 
	} 
} 

/**
 * getfoldercontent()
 * 
 * return the files and directories of a folder in two arrays
 * 
 * @param  $folder the folder to read
 * @param  $dir_array the array that will contain name of sub-dir
 * @param  $pic_array the array that will contain name of picture
 * @param  $expic_array an array that contains pictures already in db
 * @return 
 */
function getfoldercontent($folder, &$dir_array, &$pic_array, &$expic_array)
{
	global $CONFIG;

	$dir = opendir($CONFIG['fullpath'] . $folder);
	if ($CONFIG['thumb_method'][0] == 'g') {
		 if (function_exists('imagecreatefromgif')) {
				$img_to_find = '(.png)|(.jpg)|(.jpeg)|(.gif)';
		 } else {
				$img_to_find = '(.png)|(.jpg)|(.jpeg)';
		 }
	} else {
			//$CONFIG['allowed_file_extensions'] == GIF/PNG/JPG/JPEG/TIF/TIFF
			$allowed_ext = strtolower($CONFIG['allowed_file_extensions']);
			$result = explode('/', $allowed_ext);
			foreach ($result as $piece) {
				$img_to_find .= "(.$piece)|";
			}
			$img_to_find = substr($img_to_find,0, -1); 
	}
	while ($file = readdir($dir)) {
		if (is_dir($CONFIG['fullpath'] . $folder . $file)) {
			if ($file != "." && $file != "..") {
				$dir_array[] = $file;
			} 
		} elseif (is_file($CONFIG['fullpath'] . $folder . $file) && preg_match('#' . preg_quote($img_to_find, '#') . '#mi', $file)) {
			if (!str_starts_with($file, $CONFIG['thumb_pfx']) && !str_starts_with($file, $CONFIG['normal_pfx']) && $file != 'index.html') {
				$pic_array[] = $file;
			}
		}
	}
	closedir($dir);

	natcasesort($dir_array);
	natcasesort($pic_array);
} 

function display_dir_tree($folder, $ident)
{
	global $CONFIG, $THEME_DIR;

	$dir_path = $CONFIG['fullpath'] . $folder;

	if (!is_readable($dir_path)) return;
	$dir = opendir($dir_path);
	$files = array();
	while ($file = readdir($dir)) {
		$files[] = $file;
	}
	sort($files);
	foreach ($files as $file) {
		if (is_dir($CONFIG['fullpath'] . $folder . $file) && $file != "." && $file != ".." && $file != "CVS") {
			$start_target = $folder . $file;
			$dir_path = $CONFIG['fullpath'] . $folder . $file;

			$warnings = '';
			if (!is_writable($dir_path)) $warnings .= DIR_RO;
			if (!is_readable($dir_path)) $warnings .= DIR_CANT_READ;

			if ($warnings) $warnings = '&nbsp;&nbsp;&nbsp;<b>' . $warnings . '<b>';

			echo '<tr>
				<td class="tableb">
					'.$ident.'<img src="'.$THEME_DIR.'/images/folder.gif" alt="" />&nbsp;<a href= "'.getlink("&amp;file=searchnew&amp;startdir=".$start_target, false).'">'.$file.'</a>'.$warnings.'
				</td>
			</tr>
';
			display_dir_tree($folder . $file . '/', $ident . '&nbsp;&nbsp;&nbsp;&nbsp;');
		} 
	} 
	closedir($dir);
} 

/**
 * getallpicindb()
 * 
 * Fill an array where keys are the full path of all images in the picture table
 * 
 * @param  $pic_array the array to be filled
 * @return 
 */
function getallpicindb(&$pic_array, $startdir)
{
	global $db, $CONFIG;
	$sql = "SELECT filepath, filename FROM {$CONFIG['TABLE_PICTURES']} WHERE filepath LIKE '$startdir%'";
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result)) {
		$pic_file = $row['filepath'] . $row['filename'];
		$pic_array[$pic_file] = 1;
	} 
	$db->sql_freeresult($result);
} 

/**
 * getallalbumsindb()
 * 
 * Fill an array with all albums where keys are aid of albums and values are
 * album title
 * 
 * @param  $album_array the array to be filled
 * @return 
 */
function getallalbumsindb(&$album_array)
{
	global $db, $CONFIG;

	$sql = "SELECT aid, title " . "FROM {$CONFIG['TABLE_ALBUMS']}";
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result)) {
		$album_array[$row['aid']] = $row['title'];
	} 
	$db->sql_freeresult($result);
} 

/**
 * CPGscandir() //renamed because php5 has scandir()func
 * 
 * recursive function that scan a directory, create the HTML code for each
 * picture and add new pictures in an array
 * 
 * @param  $dir the directory to be scanned
 * @param  $expic_array the array that contains pictures already in DB
 * @param  $newpic_array the array that contains new pictures found
 * @return 
 */
function CPGscandir($dir, &$expic_array)
{ 
	// ##	$dir = str_replace(".","" ,$dir);
	static $dir_id = 0;
	static $count = 0;
	static $pic_id = 0;
	$pic_array = array();
	$dir_array = array();

	getfoldercontent($dir, $dir_array, $pic_array, $expic_array);

	if (count($pic_array) > 0) {
		$dir_id_str = sprintf("d%04d", $dir_id++);
		echo dirheader($dir, $dir_id_str);
		foreach ($pic_array as $picture) {
			$count++;
			$pic_id_str = sprintf("i%04d", $pic_id++);
			echo picrow($dir . $picture, $pic_id_str, $dir_id_str);
		} 
	} 
	if (count($dir_array) > 0) {
		foreach ($dir_array as $directory) {
			CPGscandir($dir . $directory . '/', $expic_array);
		} 
	} 
	return $count;
} 

/**
 * Main code
 */

$album_array = array();
getallalbumsindb($album_array);
// We need at least one album
if (!count($album_array)) cpg_die(_ERROR, NEED_ONE_ALBUM, __FILE__, __LINE__);

if (isset($_POST['insert'])) {
	if (!isset($_POST['pics'])) {
		cpg_die(_ERROR, NO_PIC_TO_ADD, __FILE__, __LINE__);
	} 
	foreach ($_POST['pics'] as $pic_id) {
		// check to see if select has changed
		if ($_POST[$_POST['album_lb_id_' . $pic_id]] == 0) {
			cpg_die(_ERROR, NO_ALBUM, "searchnew.php id: " . $_POST['album_lb_id_' . $pic_id] . " pic_id: $pic_id", __LINE__); //return;
		} 
	} // end of die if album not selected
	pageheader(PAGE_TITLE);
	starttable("100%");
	echo '
	<tr>
		<td colspan="4" class="tableh1"><h2>'.INSERT.'</h2></td>
	</tr>
	<tr>
		<td class="tableh2" colspan="4">
			<b>'.BE_PATIENT.'</b>
		</td>
	</tr>
	<tr>
		<td class="tableb" colspan="4">
			'.SN_NOTES.'
		</td>
	</tr>
	<tr>
		<td class="tableh2" valign="middle" align="center"><b>'.FOLDER.'</b></td>
		<td class="tableh2" valign="middle" align="center"><b>'.IMAGE.'</b></td>
		<td class="tableh2" valign="middle" align="center"><b>'.ALBUM.'</b></td>
		<td class="tableh2" valign="middle" align="center"><b>'.RESULT.'</b></td>
	</tr>';

	$count = 0;
	foreach ($_POST['pics'] as $pic_id) {
		// check to see if select has changed
		$album_lb_id = $_POST['album_lb_id_' . $pic_id];
		$album_id = $_POST[$album_lb_id];
		$album_name = $album_array[$album_id];
		$pic_file = base64_decode($_POST['picfile_' . $pic_id]);
		$dir_name = dirname($pic_file) . "/";
		$file_name = basename($pic_file); 
		// To avoid problems with PHP scripts max execution time limit, each picture is
		// added individually using a separate script that returns an image
		$status = '<a href="'.getlink($module_name.'&amp;file=addpic&amp;aid='.$album_id. '&amp;pic_file=' . ($_POST['picfile_' . $pic_id]) . '&amp;reload=' . uniqid(''),0 ). '"><img src="'.getlink($module_name.'&amp;file=addpic&amp;aid='.$album_id.'&amp;pic_file=' . ($_POST['picfile_' . $pic_id]) . '&amp;reload=' . uniqid(''),false). '" class="thumbnail" border="0" width="24" height="24" alt="" /><br /></a>';
		$album_name = $album_array[$album_id];

		echo '<tr>
		<td class="tableb" valign="middle" align="left">'.$dir_name.'</td>
		<td class="tableb" valign="middle" align="left">'.$file_name.'</td>
		<td class="tableb" valign="middle" align="left">'.$album_name.'</td>
		<td class="tableb" valign="middle" align="center">'.$status.'</td>
		</tr>';
		$count++;
	} 
	endtable();
	pagefooter();
}
elseif (isset($_GET['startdir'])) {
	$startdir = $_GET['startdir'];
	if (preg_match('#\.\.#m', $startdir)) die('Access denied: '.$startdir); // thanks to waraxe for finding this admin vulnerability
	pageheader(PAGE_TITLE);
	starttable("100%");
	$action=getlink("&amp;file=searchnew&amp;insert=1");
	echo '
		<form method="post" action="'.$action.'" enctype="multipart/form-data" accept-charset="utf-8">
		<tr>
			<td colspan="3" class="tableh1"><h2>'.LIST_NEW_PIC.'</h2></td>
		</tr>';
	$expic_array = array();

	getallpicindb($expic_array, $startdir);
	if (CPGscandir($startdir.'/', $expic_array)) {
		echo '
		<tr>
			<td colspan="3" align="center" class="tablef">
				<input type="submit" class="button" name="insert" value="'.INSERT_SELECTED.'" />
			</td>
		</tr>
		</form>';
	}
	else {
		echo '
		<tr>
			<td colspan="3" align="center" class="tableb">
				<br /><br />
				<b>'.NO_PIC_FOUND.'</b>
				<br /><br /><br />
			</td>
		</tr>
		</form>';
	}
	endtable();
	pagefooter();
}
else {
	pageheader(PAGE_TITLE);
	starttable(-1, SELECT_DIR);
	display_dir_tree('', '');
	echo '
		<tr>
				<td class="tablef">
						<b>'.SELECT_DIR_MSG.'</b>
				</td>
		</tr>';
	endtable();
	pagefooter();
}
