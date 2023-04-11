<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

if (!is_admin() && !\Dragonfly::getKernel()->CFG->member->allowusertheme) {
	$content = 'no theme to select';
	return /*trigger_error('Member can\'t change theme', E_USER_WARNING)*/;
}

$qs = $_GET->getArrayCopy();
if (defined('ADMIN_PAGES')) {
	unset($qs['admin']);
	unset($qs['op']);
} else {
	unset($qs['name']);
}

$content = '<form action="" method="get"><div style="text-align:center;">
<select name="themeprev" onchange="top.location.href=this.options[this.selectedIndex].value">';
foreach (\Poodle\TPL::getThemes() as $theme) {
	$qs['prevtheme'] = $theme;
	if (defined('ADMIN_PAGES')) {
		$url = htmlspecialchars(URL::admin('&'.URL::buildQuery($qs)));
	} else {
		$url = htmlspecialchars(URL::index('&'.URL::buildQuery($qs)));
	}
	$content .= '<option value="'.$url.'"';
	if ($theme == \Dragonfly::getKernel()->OUT->theme) $content .= ' selected="selected"';
	$content .= ">{$theme}";
	$content .= "</option>\n";
}
$content .= '</select></div></form>
Each user can view the site with a different theme.<br/>
This option will change the look of this page temporarily.';
