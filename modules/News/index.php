<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (class_exists('Dragonfly', false)) {
	news_index();
}

function news_index()
{
	if (isset($_POST['preview']) || isset($_GET['comment']) || isset($_POST['postreply'])) {
		\Poodle\Report::error(410);
	} else if (isset($_GET['reply'])) {
		\URL::redirect(\URL::index("&file=article&sid={$_GET['sid']}&reply={$_GET['reply']}"));
	}

	$K = \Dragonfly::getKernel();
	$OUT = $K->OUT;
	\Dragonfly\Page::title(_NewsLANG, false);

	$storynum = ($K->CFG->member->user_news ? $K->IDENTITY->storynum : $K->CFG->global->storyhome);
	if (isset($_GET['page']) && intval($_GET['page']) > 1) {
		$page = intval($_GET['page']);
		\Dragonfly\Page::title('- '._PAGE.' '.$page, false);
	} else {
		$page = 1;
	}
	$offset = ($page - 1) * $storynum;
	$querylang = ($OUT->L10N->multilingual ? "AND (alanguage='{$OUT->L10N->lng}' OR alanguage='')" : '');
	$topic = isset($_GET['topic']) ? intval($_GET['topic']) : (isset($_GET['new_topic']) ? intval($_GET['new_topic']) : 0);
	$catid = isset($_GET['catid']) ? intval($_GET['catid']) : 0;
	if ($topic > 0) {
		$qdb = "topic={$topic}";
	} else {
//		$qdb = '(ihome=1 OR catid=0)';
		$qdb = 'ihome=1';
	}

	$total = $K->SQL->count('stories', (($catid > 0) ? "catid = {$catid}" : $qdb)." {$querylang}");
	$pages = ceil($total/$storynum);
	if ($pages < $page && $storynum > 0) { cpg_error(_PAGE.' '.$page.' does not exist'); }

	$OUT->newsempty = false;
	$OUT->newscat = false;
	$OUT->newsarticles = array();

	if ($topic > 0) {
		list($topic_title) = $K->SQL->uFetchRow("SELECT topictext FROM {$K->SQL->TBL->topics} WHERE topicid = {$topic}");
		$OUT->newsempty = !$topic_title;
		if ($topic_title) {
			$OUT->newscat = array(
				'S_TOPIC_T' => $topic_title,
				'I_TOPIC'   => $topic,
			);
		}
	}

	$result = $K->SQL->query("SELECT s.*, sc.title AS cat_title, t.topicimage AS topic_image, t.topictext AS topic_title
	FROM {$K->SQL->TBL->stories} AS s
	LEFT JOIN {$K->SQL->TBL->stories_cat} AS sc ON (sc.catid=s.catid)
	LEFT JOIN {$K->SQL->TBL->topics} t ON t.topicid=s.topic
	WHERE ptime <= ".time()
	 . " AND " . ($catid > 0 ? "s.catid={$catid} {$querylang}" : "{$qdb} {$querylang}")
	 . " ORDER BY display_order DESC, sid DESC LIMIT {$storynum} OFFSET {$offset}");

//	$sql .= ($catid > 0) ?  "s.catid='$catid' $querylang ORDER BY sid DESC" : "$qdb $querylang ORDER BY display_order DESC, ptime DESC";
//	$result = $K->SQL->query($sql.' LIMIT '.$storynum.' OFFSET 0');

	while ($row = $result->fetch_assoc()) {
		$row['topic_image'] = $row['topic_image'] ?: 'news.png';
		$row['topic_image'] = (is_file("themes/{$OUT->theme}/images/topics/{$row['topic_image']}") ? "themes/{$OUT->theme}/" : '')."images/topics/{$row['topic_image']}";
		$row['hometext'] = \Dragonfly\BBCode::decode($row['hometext'], 1, true);
		$row['rated'] = $row['score'] ? round($row['score'] / $row['ratings'], 2) : 0;
		$row['uri'] = URL::index('News&file=article&sid='.$row['sid']);
		$row['informant_uri'] = ($row['informant'] ? \Dragonfly\Identity::getProfileURL($row['identity_id']?:$row['informant']) : null);
		$row['informant'] = ($row['informant'] ?: _ANONYMOUS);
		$row['datetime'] = $OUT->L10N->ISO_dt($row['ptime']);
		$morecount = strlen($row['bodytext']);

		$row['topic_uri'] = URL::index('News&topic='.$row['topic']);
		$row['more_bytes'] = $morecount;
		$OUT->newsarticles[] = $row;
	}
	$result->free();
	$tmp = (0 < $topic) ? '&topic=' . $topic : ((0 < $catid) ? '&catid=' . $catid : '');
	pagination('News'.$tmp.'&page=', $pages, 1, $page);
	$OUT->display('News/index');
}
