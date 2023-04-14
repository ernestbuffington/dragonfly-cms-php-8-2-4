<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/News/admin/adlinks.inc,v $
  $Revision: 9.3 $
  $Author: trevor $
  $Date: 2005/03/03 23:35:30 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

if (can_admin('news')) {
	$menuitems['_AMENU3'][_NewsLANG]['URL'] = adminlink('News');
	$menuitems['_AMENU3'][_NewsLANG]['IMG'] = 'stories';
	$menuitems['_AMENU3'][_NewsLANG]['MOD'] = 'News';
	$menuitems['_AMENU3'][_NewsLANG]['SUB'][_ADDSTORY] = adminlink('News&amp;mode=add');
	$menuitems['_AMENU3'][_NewsLANG]['SUB'][_SUBMISSIONS] = adminlink('News&amp;file=submissions');
	$menuitems['_AMENU3'][_NewsLANG]['SUB'][_TB_CONFIG] = adminlink('News&amp;mode=cfg');
}
if (can_admin('topics')) {
	$menuitems['_AMENU3'][_TOPICS]['URL'] = adminlink('News&amp;file=topics');
	$menuitems['_AMENU3'][_TOPICS]['IMG'] = 'topics';
}
