<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('CPG_NUKE')) { exit; }
if (\Dragonfly\Modules::isActive('Our_Sponsors') && can_admin() && $MAIN_CFG['global']['banners']) {
	$num = $db->count('banner', 'active=0');
	$content .= '<a href="'.URL::admin('Our_Sponsors').'">'._EDITBANNER."</a>: $num<br />";
}
