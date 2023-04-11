<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $db, $MAIN_CFG;

list($hits) = $db->uFetchRow("SELECT SUM(sc_hits) FROM {$db->TBL->stats_counters} WHERE sc_type=1");

$content = '<div style="text-align:center;">'._WERECEIVED.'<br /><a href="'.URL::index('Statistics').'"><strong>'.$hits.'</strong></a><br />'._PAGESVIEWS.'<br />'.$MAIN_CFG['global']['startdate'].'</div>';
