<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004-2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

$L10N = \Dragonfly::getKernel()->L10N;

if (!$L10N->multilingual) {
	$content = 'Multilingual is off';
	return /*trigger_error('Multilingual is off', E_USER_WARNING)*/;
}

// useflags is set in configuration
if (\Dragonfly::getKernel()->CFG->global->useflags) {
	foreach ($L10N->getActiveList() as $lng) {
		$image = 'images/l10n/'.$lng['value'].'.png';
		$content .= '<a href="'.URL::lang($lng['value']).'">';
		if (is_file($image)){
			$content .= "<img src=\"{$image}\" align=\"middle\" alt=\"{$lng['title']}\" title=\"{$lng['title']}\" style=\"border:0; padding:3px\"/>";
		} else {
			$content .= $lng['title'];
		}
		$content .= '</a> ';
	}
} else {
	$content = '<form title="This option will change the language of this website" action="" method="get"><div>
	<select name="newlanguage" onchange="top.location.href=this.options[this.selectedIndex].value">';
	foreach ($L10N->getActiveList() as $lng) {
		$content .= '<option value="'.URL::lang($lng['value']).'"';
		if ($lng['value'] == $L10N->lng) $content .= ' selected="selected"';
		$content .= '>'.$lng['title']."</option>\n";
	}
	$content .= '</select></div></form>';
}
