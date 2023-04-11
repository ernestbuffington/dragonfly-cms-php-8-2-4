<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }
\Dragonfly\Page::title(_TopicsLANG, false);

function news_topics()
{
	$K = \Dragonfly::getKernel();
	$db = $K->SQL;

	$result = $db->query("SELECT
		t.topicid    id,
		t.topicimage image,
		t.topictext  title,
		count(s.sid) AS stories,
		SUM(s.counter) AS readcount
	FROM {$db->TBL->topics} t
	LEFT JOIN {$db->TBL->stories} s ON (s.topic = t.topicid AND s.ptime<=".time().")
	GROUP BY 1, 2, 3
	ORDER BY t.topictext");

	$USE_TAL = !$K->OUT->themeV9FileExists('topics/index');

	if (!$result->num_rows) {
		cpg_error(sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_TopicsLANG)));
	}

	$K->OUT->NO_STORIES = sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_ARTICLES));

	while ($topic = $result->fetch_assoc()) {
		$topic['image'] = (is_file('themes/'.$K->OUT->theme.'/images/topics/'.$topic['image']) ? 'themes/'.$K->OUT->theme.'/' : '').'images/topics/'.$topic['image'];
		if (!$USE_TAL) {
			$topic += array(
				'IMAGE'       => $topic['image'],
				'TITLE'       => $topic['title'],
				'B_STORIES'   => $topic['stories'] > 0,
				'TOTAL_NEWS'  => $topic['stories'],
				'TOTAL_READS' => isset($topic['readcount']) ? $topic['readcount'] : 0,
				'B_MORE'      => $topic['stories'] > 10,
			);
		}
		$topic += array(
			'B_MORE'     => $topic['stories'] > 10,
			'URL'        => URL::index('News&topic='.$topic['id']),
			'news_story' => array(),
		);

		if ($topic['stories']) {
			$result2 = $db->query("SELECT
				s.sid,
				s.catid,
				s.title,
				c.title AS cat_title
			FROM {$db->TBL->stories} s
			LEFT JOIN {$db->TBL->stories_cat} c USING (catid)
			WHERE s.topic = {$topic['id']}
			  AND s.ptime <= ".time()."
			ORDER BY s.sid DESC LIMIT 10");
			while ($story = $result2->fetch_assoc()) {
				if (!$USE_TAL) {
					$story += array(
						'B_IN_CATEGORY'  => $story['catid'] > 0,
						'CATEGORY_TITLE' => $story['cat_title'],
						'TITLE'          => $story['title']
					);
				}
				$story += array(
					'U_CATEGORY' => URL::index('News&catid='.$story['catid']),
					'URL'        => URL::index('News&file=article&sid='.$story['sid']),
				);
				$topic['news_story'][] = $story;
			}
		}

		if ($USE_TAL) {
			$K->OUT->assign_block_vars('topics', $topic);
		} else {
			$K->OUT->assign_block_vars('topic', $topic);
		}
	}

	if ($USE_TAL) {
		$K->OUT->display('News/topics');
	} else {
		$K->OUT->display('topics/index');
	}
}

if (\Dragonfly\Modules::isActive('News')) {
	news_topics();
} else {
	cpg_error(sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_TopicsLANG)));
}
