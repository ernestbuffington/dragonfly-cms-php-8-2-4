<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

if (can_admin(strtolower($module))) {
	$menuitems['_AMENU3'][_NewsLANG]['URL'] = URL::admin('News');
	$menuitems['_AMENU3'][_NewsLANG]['IMG'] = 'stories';
	$menuitems['_AMENU3'][_NewsLANG]['MOD'] = 'News';
	$menuitems['_AMENU3'][_NewsLANG]['SUB'][_ADDSTORY] = URL::admin('News&mode=add');
	$menuitems['_AMENU3'][_NewsLANG]['SUB'][_SUBMISSIONS] = URL::admin('News&file=submissions');
	$menuitems['_AMENU3'][_NewsLANG]['SUB'][_TB_CONFIG] = URL::admin('News&file=config');
}

if (can_admin('topics')) {
	$menuitems['_AMENU3'][_TOPICS]['URL'] = URL::admin('News&file=topics');
	$menuitems['_AMENU3'][_TOPICS]['IMG'] = 'topics';
}
