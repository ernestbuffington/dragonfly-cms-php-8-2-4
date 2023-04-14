<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/admin/modules/cache.php,v $
  $Revision: 9.10 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:33:57 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }

$showblocks = 0;
$pagetitle .= ' '._BC_DELIM.' Caching';

if (isset($_POST['mode']) || isset($_GET['mode'])) {
	$mode = $_POST['mode'] ?? $_GET['mode'];
} else {
	$mode = '';
}

// External cache options
$name = function_exists('mmcache') ? 'mmcache' : 'eaccelerator';
$title = ($name == 'mmcache') ? 'Turck MMCache' : 'eAccelerator';
if ($accel['installed'] = function_exists($name)) {
	$regex = array(
		'#<(wbr|hr|br)>#' => '<\\1/>',
		'/<table.*?bgcolor.*?>/' => '<table class="phptable">',
		'/<table.*?style="table-layout:fixed">/' => '<table>',
		'/ valign="middle" bgcolor="#9999cc"/' => ' class="phphead"',
		'/ valign="baseline" bgcolor="#cccccc"/' => ' class="phptr"',
		'/ bgcolor="#ccccff"/' => ' class="phptd"'
	);
	ob_start();
	$name();
	$cache = ob_get_contents();
	ob_end_clean();
	$cache = preg_split('/(<body>|<\/body>|<form.*?center>|<\/center><\/form>)/i', $cache, -1, PREG_SPLIT_NO_EMPTY);
	$accel['title'] = $cache[1];
	$accel['form'] = str_replace(' style="width:100px"', '', preg_replace('#<input (.*?)>#', '<input class="button" \\1/>', $cache[2]));
	$accel['info'] = preg_replace(array_keys($regex), array_values($regex), $cache[3]);
	$accel['encoder'] = is_callable($name.'_encode');
	unset($cache);
}

require('header.php');
GraphicAdmin('_AMENU1');
OpenTable();

echo '<fieldset><legend>'.$title.'</legend>';
if (!$accel['installed']) {
	echo 'eAccelerator is not installed on this server. You won\'t be able to give you website a tremendous speed boost.<br />
	If you have full access to your server thru SSH you can get it at <a href="http://eaccelerator.net/" target="_blank">eaccelerator.net</a>. Additional information can be found <a href="http://www.vbulletin.com/forum/showthread.php?t=75878" target="_blank">here</a>.';
} else {
	echo '
	<form method="post" action="'.adminlink().'">
	<input type="submit" name="mode" value="Info" title="Current cached" class="button" />&nbsp;';
	if (isset($accel['encoder']) && $accel['encoder']) { // 2.3.10
		echo '
	<input type="submit" name="mode" value="Encode" title="Encode a file/directory" class="button" />&nbsp;';
	}
	echo $accel['form'].'</form>';
}
echo '</fieldset>';
if (!$accel['installed']) {
	echo '<h1 align="center">Accelerator is not installed</h1>';
} else if ($mode == 'Info') {
	echo $accel['title'].$accel['info'];
} else if ($mode == 'Encode') {
	require('admin/modules/cache/'.$name.'.php');
}

CloseTable();
