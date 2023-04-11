<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2015 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/
if (!defined('CPG_NUKE')) { exit; }

$K    = Dragonfly::getKernel();
$lang = ($K->L10N->multilingual ? "AND (planguage='{$K->L10N->lng}' OR planguage='')" : '');
$poll = $K->SQL->uFetchRow("SELECT
	poll_id
FROM {$K->SQL->TBL->poll_desc}
WHERE artid=0 AND poll_ptime<".time()." {$lang}
ORDER BY poll_ptime DESC");
if ($poll) {
	$K->OUT->poll = new \Dragonfly\Modules\Surveys\Poll($poll[0]);
	$content = $K->OUT->toString('Surveys/block');
	unset($K->OUT->poll);
} else {
	$content = sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_Surveys));
}
