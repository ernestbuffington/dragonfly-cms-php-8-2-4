<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/blocks/block-Preview_theme.php,v $
  $Revision: 9.14 $
  $Author: phoenix $
  $Date: 2007/06/06 23:35:54 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $mainindex, $adminindex, $CPG_SESS, $BASEHREF, $MAIN_CFG;
if (!is_admin() && !$MAIN_CFG['member']['allowusertheme']) {
	$content = 'ERROR';
	return trigger_error('Member can\'t change theme', E_USER_WARNING);
}

$qs = defined('ADMIN_PAGES') ? "$adminindex?" : '&amp;';
foreach($_GET as $var => $value) {
	if ($var != 'prevtheme' && $var != 'name') {
		$qs .= htmlspecialchars($var).'='.htmlspecialchars($value).'&amp;';
	}
}
$qs .= 'prevtheme=';

$handle = opendir('themes');
$themelist = array();
while ($file = readdir($handle)) {
	if (!ereg('[.]', $file) && file_exists("themes/$file/theme.php")) {
		$themelist[] = $file;
	}
}
closedir($handle);
natcasesort($themelist);
$themelist = array_values($themelist);
$content = '<form title="This option will change the look of this page temporarily. Themes marked with a * also change the forum look." action="" method="get"><div style="text-align:center;">
<select name="themeprev" onchange="top.location.href=\''.$BASEHREF.'\'+this.options[this.selectedIndex].value">';
for ($i=0; $i < sizeof($themelist); $i++) {
	if ($themelist[$i] != '') {
		if (defined('ADMIN_PAGES')) {
			$content .= '<option value="'.$qs.$themelist[$i].'"';
		} elseif (!isset($_GET['name']) && !isset($_POST['name'])) {
			$content .= '<option value="'.$mainindex."?prevtheme=$themelist[$i]\"";
		} else {
			$content .= '<option value="'.getlink($qs.$themelist[$i]).'"';
		}
		if ($themelist[$i]==$CPG_SESS['theme']) $content .= ' selected="selected"';
		$content .= ">$themelist[$i]";
		if (is_dir("themes/$themelist[$i]/forums") || is_dir("themes/$themelist[$i]/template/forums")) $content .= ' *';
		$content .= "</option>\n";
	}
}
$content .= '</select></div></form>
Each user can view the site with a different theme.<br />
Themes marked with a <strong>*</strong> also change the forum look.';
