<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/step1.php,v $
  $Revision: 9.14 $
  $Author: djmaze $
  $Date: 2007/05/17 18:45:41 $
**********************************************/
if (!defined('INSTALL')) { exit; }

$passed = (PHPVERS >= 41);
$dirs = array(
	'config' => array('includes', 0),
	'cache' => array('cache', 1),
	'avatars' => array('uploads/avatars', 0),
	'albums' => array('modules/coppermine/albums', 0),
	'userpics' => array('modules/coppermine/albums/userpics', 0),
);
$tips = '';
foreach ($dirs as $key => $data) {
	$dirs[$key][2] = is_writeable(BASEDIR.$data[0]);
	if ($data[1] && !$dirs[$key][2]) { $passed = false; }
	$tips .= "maketip('$key','".$instlang["s1_$key"]."','".$instlang['s1_'.$key.'2']."');\n";
}

$gd = false;
if (extension_loaded('gd')) {
	if (function_exists('gd_info')) {
		$gd = gd_info();
		$gd = $gd['GD Version'];
	} else if (preg_match('/phpinfo/', ini_get('disable_functions'))) {
		$gd = 2;
	} else {
		ob_start();
		phpinfo(INFO_MODULES);
		$info = ob_get_contents();
		ob_end_clean();
		$info = stristr($info, 'gd version');
		preg_match('/\d/', $info, $match);
		$gd = $match[0];
	}
}
$gd = preg_replace('#bundled \((.*?) compatible\)#', '\\1', $gd);
$ini['register_globals'] = ini_get('register_globals');
$ini['magic_quotes'] = get_magic_quotes_gpc();
$ini['magic_quotes_sybase'] = ini_get('magic_quotes_sybase');
$ini['ini_set'] = !preg_match('#ini_set#m', ini_get('disable_functions'));
$ini['LEO'] = preg_match('#Apache#m', $_SERVER['SERVER_SOFTWARE']);

$checks = array(
	'<img src="install/images/red.gif" alt="critical" title="critical" />',
	'<img src="install/images/orange.gif" alt="failed" title="failed" />',
	'<img src="install/images/green.gif" alt="ok" title="ok" />'
);

inst_header();
echo '<script language="JavaScript" type="text/javascript">
<!--'."
maketip('writeaccess','".$instlang['s1_directory_write']."','".$instlang['s1_directory_write2']."');
$tips".'// -->
</script>
<table>
	<tr>
	  <td colspan="5" nowrap="nowrap">
		'.$checks[2].' '.$instlang['s1_dot_ok'].' |
		'.$checks[1].' '.$instlang['s1_dot_failed'].' |
		'.$checks[0].' '.$instlang['s1_dot_critical'].'</td>
	</tr><tr>
	  <td colspan="5"><hr noshade="noshade" size="1" /></td>
	</tr><tr>
	  <th colspan="4" nowrap="nowrap">Server settings</th>
	  <td></td>
	</tr><tr>
	  <td>setting</td><td>prefered</td><td>yours</td>
	</tr><tr>
	  <td>PHP</td><td>4.3</td><td>'.phpversion().'</td>
	  <td align="center">'.$checks[((PHPVERS < 41) ? 0 : ((PHPVERS >= 43) ? 2 : 1))].'</td>
	  <td>'/*.inst_help('php')*/.'</td>
	</tr><tr>
	  <td>GD</td><td>2.0</td><td>'.$gd.'</td>
	  <td align="center">'.$checks[((strpos($gd, '2.') === false)? 1 : 2)].'</td>
	  <td>'/*.inst_help('php')*/.'</td>
	</tr><tr>
	  <td>magic_quotes</td><td>Off</td><td>'.($ini['magic_quotes']?'On':'Off').'</td>
	  <td align="center">'.$checks[($ini['magic_quotes'] ? 1 : 2)].'</td>
	  <td>'/*.inst_help('php')*/.'</td>
	</tr><tr>
	  <td>magic_quotes_sybase</td><td>Off</td><td>'.($ini['magic_quotes_sybase']?'On':'Off').'</td>
	  <td align="center">'.$checks[($ini['magic_quotes_sybase'] ? 1 : 2)].'</td>
	  <td>'/*.inst_help('php')*/.'</td>
	</tr><tr>
	  <td>register_globals</td><td>Off</td><td>'.($ini['register_globals']?'On':'Off').'</td>
	  <td align="center">'.$checks[($ini['register_globals'] ? 1 : 2)].'</td>
	  <td>'/*.inst_help('php')*/.'</td>
	</tr><tr>
	  <td>ini_set()</td><td>On</td><td>'.($ini['ini_set']?'On':'Off').'</td>
	  <td align="center">'.$checks[($ini['ini_set'] ? 2 : 1)].'</td>
	  <td>'/*.inst_help('php')*/.'</td>
	</tr><tr>
	  <td>LEO available</td><td>Yes</td><td>'.($ini['LEO']?'Yes':'No').'</td>
	  <td align="center">'.$checks[($ini['LEO'] ? 2 : 1)].'</td>
	  <td>'/*.inst_help('php')*/.'</td>
	</tr><tr>
	  <td colspan="5"><hr noshade="noshade" size="1" /></td>
	</tr><tr>
	  <th colspan="4" nowrap="nowrap">'.$instlang['s1_directory_write'].'</th>
	  <td>'.inst_help('writeaccess').'</td>
	</tr>';
foreach ($dirs as $key => $data) {
	$data[2] = ($data[2] ? 2 : ($data[1] ? 0 : 1));
	echo '<tr>
	  <td colspan="3">/'.$data[0].'/</td>
	  <td align="center">'.$checks[$data[2]].'</td>
	  <td>'.inst_help($key).'</td>
	</tr>';
}
?>
	<tr>
		<td colspan="5"><hr noshade="noshade" size="1" /></td>
	</tr>
</table>
<?php
if ($passed) {
	echo $instlang['s1_correct'].'<p align="center"><input type="hidden" name="step" value="'.(!empty($current_version) ? '3' : '2').'" />
	<input type="submit" value="'.$instlang['next'].'" class="formfield" /></p>';
} else {
	echo '<p style="color:#FF0000; font-style:bold">'.$instlang['s1_fixerrors'].'</p>';
}
