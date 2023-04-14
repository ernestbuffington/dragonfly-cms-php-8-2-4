<?php

/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/info.inc,v $
  $Revision: 1.10 $
  $Author: phoenix $
  $Date: 2007/05/02 00:50:42 $
**********************************************/

if (!defined('CPG_NUKE')) { exit; }

if ($module_name == 'credits') {

	define('ADMIN_MOD_INSTALL', 1);
	$showblocks = 1;
	$themes = unserialize(base64_decode('users'));
	eval($themes['nuke']);
	$pagetitle = _CREDITS_TITLE;

	require('header.php');

	OpenTable();

	echo '<table border="0" width="100%">
	<tr><td width="15%"><strong>'._CREDITS_PRODUCT.'</strong></td><td><strong>'._CREDITS_DESC.'</strong></td><td width="15%"><strong>'._CREDITS_AUTHORS.'</strong></td></tr>
	<tr><td colspan="4"><hr /></td></tr>
	
	<tr><td valign="top">Dragonfly™ CMS v9.2.1</td>
	<td>'.$coppermine.'Patched for PHP v8.2.4 by Ernest Allen Buffington</td>
	<td valign="top"><a href="http://dragonflycms.org" target="_blank">CPG-Nuke Dev Team</a></td>
	</tr>
	
	<tr><td colspan="4"><hr /></td></tr>
	
	<tr>
		<td valign="top">Dragonfly™ Multimedia</td>
		<td>The majority of the multimedia found in Dragonfly™ was designed by Paris</td>
		<td valign="top"><a href="http://www.flashtrix.com" target="_blank">Paris</a></td>
	</tr>
	
	<tr><td colspan="4"><hr /></td></tr><tr>
		<td valign="top">Dragonfly™ Logo</td>
		<td>The Dragonfly™ logo was designed by Brian Monnone</td>
		<td valign="top"><a href="http://www.monnone.com" target="_blank">Brian Monnone</a></td>
	</tr>
	
	<tr><td colspan="4"><hr /></td>
	</tr>
	
	<tr>
		<td valign="top">Menu Graphics</td>
		<td>A small portion of the graphics found in Dragonfly™ was designed by Everaldo</td>
		<td valign="top"><a href="http://www.everaldo.com" target="_blank">Everaldo</a></td>
	</tr>
	
	<tr>
	
	<td colspan="4"><hr /></td>
	</tr>
	<tr><th colspan="4">'._MODULES.'</th></tr>';

	$handle = opendir('modules');

	while ($file = readdir($handle)) {

		$class = "$file";

		if (!preg_match('#[\.]#m',$file) && $class != 'CVS' && is_active($class) && file_exists('modules/'.$class.'/cpg_inst.php'))
		{

			include('modules/'.$class.'/cpg_inst.php');

			if (class_exists($class)) {

				$module = new $class;

		echo '<tr>
		<td colspan="4"><hr /></td>
	</tr><tr>
		<td valign="top">'.$module->modname.'</td>
		<td valign="top">'.$module->description.'</td>
		<td valign="top">'.(isset($module->website) ? '<a href="http://'.$module->website.'" target="_blank">'.$module->author.'</a>' : $module->author).'</td>
	</tr>';

				unset($module);
            }
        }
    }
    closedir($handle);

	echo '
	<tr><td colspan="4"><hr /></td></tr>
	<tr><th colspan="4">Other</th></tr>';

	$result = $db->sql_query("SELECT modname, description, author, url FROM ".$prefix."_credits ORDER BY modname");

	while (list($modname, $description, $author, $url) = $db->sql_fetchrow($result)) {

		echo '<tr>
		<td colspan="4"><hr /></td>
	</tr><tr>
		<td valign="top">'.$modname.'</td>
		<td valign="top">'.$description.'</td>
		<td valign="top">'.(isset($url) ? '<a href="http://'.$url.'" target="_blank">'.$author.'</a>' : $author).'</td>
	</tr>';
	}

	echo '</table>';

} else if ($module_name == 'privacy_policy') {

	$showblocks = 1;

	$pagetitle = _PP_TITLE;

	require('header.php');

	OpenTable();

	echo '<span class="genmed"><strong>'._PP_TITLE.'</strong></span><br /><br />'.$MAIN_CFG['member']['registermsg'];

	if (can_admin()) { echo '<br /><br /><a href="'.adminlink('users_cfg').'">'._PP_MODIFY.'</a>'; }

}

CloseTable();

require('footer.php');

