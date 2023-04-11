<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('CPG_NUKE')) { exit; }

global $categories, $cat, $new_topic;

$K    = Dragonfly::getKernel();
$db   = $K->SQL;
$lang = ($K->L10N->multilingual ? "AND (planguage='{$K->L10N->lng}' OR planguage='')" : '');

$content = '';

$query = ($categories == 1) ? "AND catid='$cat' " : (($new_topic != 0) ? "AND topic='$new_topic' " : '');
if ($K->L10N->multilingual) {
	$query .= "AND (alanguage='{$K->L10N->lng}' OR alanguage='')";
}

$storynum = ($K->CFG->member->user_news ? $K->IDENTITY->storynum : $K->CFG->global->storyhome);

$result = $db->query("SELECT sid, title, ptime, comments FROM {$db->TBL->stories} WHERE ptime<=".time()." {$query} ORDER BY ptime DESC LIMIT {$storynum}");

if ($result->num_rows) {
	$content = '';
	$vari = 0;
	while (list($sid, $ntitle, $ptime, $comments) = $result->fetch_row()) {
		$datetime = Dragonfly::getKernel()->L10N->date('DATE_S', $ptime);
		$content .= $datetime.': <a href="'.htmlspecialchars(URL::index('News&file=article&sid='.$sid)).'">'.$ntitle.'</a> ('.$comments.')<br/>';
		$vari++;
	}
	if ($vari >= $K->CFG->global->oldnum) {
		$content .= '<br /><a href="'.URL::index('News&file=archive').'"><strong>'._OLDERARTICLES.'</strong></a>';
	}
}
