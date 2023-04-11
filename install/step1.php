<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('INSTALL')) { exit; }

$passed = true;
$dirs = array(
	'config' => array('includes', 0),
	'cache' => array('cache', 1),
);

$h = opendir('uploads');
while (false !== ($dir = readdir($h))) {
	if (is_dir(BASEDIR.'uploads/'.$dir) && false === strpos($dir, '.') && $dir != 'CVS') {
		$dirs[$dir] = array('uploads/'.$dir, 0);
	}
}
closedir($h);

foreach ($dirs as $key => $data) {
	$dirs[$key][2] = is_writeable(BASEDIR.$data[0]);
	if ($data[1] && !$dirs[$key][2]) { $passed = false; }
}

$gd = '';
if (extension_loaded('gd')) {
	if (function_exists('gd_info')) {
		$gd = gd_info();
		$gd = $gd['GD Version'];
	} else if (preg_match('/phpinfo/', ini_get('disable_functions'))) {
		$gd = 2;
	} else {
		ob_start();
		phpinfo(INFO_MODULES);
		preg_match('/\d/', stristr(ob_get_clean(), 'gd version'), $match);
		$gd = $match[0];
	}
}
$gd = preg_replace('#bundled \((.*?) compatible\)#', '\\1', $gd);
$ini['ini_set'] = (false === stripos(ini_get('disable_functions'), 'ini_set'));
$ini['LEO'] = (false !== stripos($_SERVER['SERVER_SOFTWARE'], 'Apache'));

$css_class = array('error', 'warning', 'success');

inst_header();
echo '<table style="text-align:left">
	<tr>
	  <td colspan="4" nowrap="nowrap">
		<span class="'.$css_class[2].'">'.$instlang['s1_dot_ok'].'</span> |
		<span class="'.$css_class[1].'">'.$instlang['s1_dot_failed'].'</span> |
		<span class="'.$css_class[0].'">'.$instlang['s1_dot_critical'].'</span></td>
	</tr><tr>
	  <td colspan="4"><hr noshade="noshade" size="1" /></td>
	</tr><tr>
	  <th colspan="3" nowrap="nowrap">'.$instlang['s1_server_settings'].'</th>
	  <td></td>
	</tr><tr>
	  <td>'.$instlang['s1_setting'].'</td><td>'.$instlang['s1_preferred'].'</td><td>'.$instlang['s1_yours'].'</td>
	</tr><tr>
	  <td>GD</td><td>2.0</td><td class="'.$css_class[((strpos($gd, '2.') === false)? 1 : 2)].'">'.$gd.'</td>
	  <td></td>
	</tr><tr>
	  <td>ini_set()</td><td>'.$instlang['s1_on'].'</td><td class="'.$css_class[($ini['ini_set'] ? 2 : 1)].'">'.($ini['ini_set']?$instlang['s1_on']:$instlang['s1_off']).'</td>
	  <td></td>
	</tr><tr>
	  <td colspan="4"><hr noshade="noshade" size="1" /></td>
	</tr><tr>
	  <th colspan="3" nowrap="nowrap">'.$instlang['s1_directory_write'].'</th>
	  <td><i class="infobox"><span>'.$instlang['s1_directory_write2'].'</span></i></td>
	</tr>';
foreach ($dirs as $key => $data) {
	$c = ($data[2] ? 2 : ($data[1] ? 0 : 1));
	echo '<tr>
	  <td colspan="2" align="left">/'.$data[0].'/</td>
	  <td class="'.$css_class[$c].'">'.($data[2]?$instlang['s1_on']:$instlang['s1_off']).'</td>
	  <td>'.(isset($instlang["s1_{$key}2"])?'<i class="infobox"><span>'.$instlang["s1_{$key}2"].'</span></i>':'').'</td>
	</tr>';
}
?>
	<tr>
		<td colspan="4"><hr noshade="noshade" size="1" /></td>
	</tr>
</table>
<?php
if ($passed) {
	echo $instlang['s1_correct'].'<p align="center"><input type="hidden" name="step" value="'.(!empty($current_version) ? '3' : '2').'" />
	<input type="submit" value="'.$instlang['next'].'" class="formfield" /></p>';
} else {
	echo '<p style="color:#FF0000; font-style:bold">'.$instlang['s1_fixerrors'].'</p>';
}
