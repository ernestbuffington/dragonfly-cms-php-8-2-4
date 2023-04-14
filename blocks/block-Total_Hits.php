<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Total_Hits.php,v $
  $Revision: 9.6 $
  $Author: phoenix $
  $Date: 2007/09/11 04:51:04 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $db, $prefix, $startdate;

list($hits) = $db->sql_ufetchrow("SELECT SUM(count) FROM ".$prefix."_counter WHERE type='os'", SQL_NUM);

$content = '<div style="text-align:center;">'._WERECEIVED.'<br /><a href="'.getlink('Statistics').'"><strong>'.$hits.'</strong></a><br />'._PAGESVIEWS.'<br />'.$startdate.'</div>';