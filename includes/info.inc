<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

if ('credits' === $module_name) {
	define('ADMIN_MOD_INSTALL', 1);
	\Dragonfly\Page::title(_CREDITS_TITLE, false);
	require_once 'header.php';
	echo '<table class="border"><thead>
	<tr><th width="15%">'._CREDITS_PRODUCT.'</th><th>'._CREDITS_DESC.'</th><th width="15%">'._CREDITS_AUTHORS.'</th></tr>
	</thead><tbody>
	<tr><td>Dragonfly™ CMS</td><td>This website engine Copyright &copy; 2003 - '.date('Y').' by CPG-Nuke Dev Team<br />Free Software released under the <a href="https://dragonfly.coders.exchange/GNUGPL.html" target="_blank">GNU GPL</a>; either version 2 of the License, or (at your option) any later version. No warranty is given or implied.</td><td><a href="https://dragonfly.coders.exchange" target="_blank">CPG-Nuke Dev Team</a></td></tr>
	<tr>
		<td>Dragonfly™ Logo</td>
		<td>The logo was designed by Brian Monnone</td>
		<td><a href="http://www.monnone.com" target="_blank">Brian Monnone</a></td>
	</tr><tr>
		<td>Menu Graphics</td>
		<td>A portion of the graphics found are Everaldo\'s "Crystal" icons and KDE\'s "Oxygen" icons</td>
		<td><a href="https://en.wikipedia.org/wiki/Everaldo_Coelho" target="_blank">Everaldo Coelho</a> / <a href="http://www.kde.org/" target="_blank">KDE</a></td>
	</tr>
</tbody><tbody>
	<tr><th colspan="4">'._MODULES.'</th></tr>';
	$files = Dragonfly\Modules::ls('install/cpg_inst.php,cpg_inst.php');
	foreach ($files as $class => $file) {
		$module = \Dragonfly\ModManager\Setup::getModuleClass($class);
		if ($module) {
			echo '
	<tr>
		<td>'.$module->modname.'</td>
		<td>'.$module->description.'</td>
		<td>'.(isset($module->website) ? '<a href="http://'.$module->website.'" target="_blank">'.$module->author.'</a>' : $module->author).'</td>
	</tr>';
			unset($module);
		}
	}

	$result = $db->query("SELECT modname, description, author, url FROM {$db->TBL->credits} ORDER BY modname");
	if ($result->num_rows) {
		echo '
</tbody><tbody>
	<tr><th colspan="4">Other</th></tr>';
	}
	while (list($modname, $description, $author, $url) = $result->fetch_row()) {
		echo '<tr>
		<td>'.$modname.'</td>
		<td>'.$description.'</td>
		<td>'.(isset($url) ? '<a href="http://'.$url.'" target="_blank">'.$author.'</a>' : $author).'</td>
	</tr>';
	}

	echo '</tbody></table>';
} else if ('privacy_policy' === $module_name) {
	\Dragonfly\Page::title(_PP_TITLE, false);
	require('header.php');
	echo '<div class="border"><strong>'._PP_TITLE.'</strong><br /><br />'.$MAIN_CFG['member']['registermsg'];
	if (can_admin()) { echo '<br /><br /><a href="'.URL::admin('users_cfg').'">'._PP_MODIFY.'</a>'; }
	echo '</div>';
}
require('footer.php');
