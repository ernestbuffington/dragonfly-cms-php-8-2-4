<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_PAGES')) { exit; }

$OUT = \Dragonfly::getKernel()->OUT;
\Dragonfly\Page::title($OUT->L10N['_ARTICLEADMIN']);

\Dragonfly\Output\Js::add('includes/poodle/javascript/wysiwyg.js');
\Dragonfly\Output\Css::add('wysiwyg');

if (isset($_POST['mode']))
{
	$story = array(
		'catid'      => intval($_POST['catid']),
		'title'      => $_POST['title'],
		'hometext'   => $_POST->html('hometext'),
		'bodytext'   => $_POST->html('bodytext'),
		'notes'      => empty($_POST['notes']) ? '' : $_POST['notes'],
		'topic'      => intval($_POST['topic']),
		'ihome'      => !empty($_POST['ihome']),
		'alanguage'  => empty($_POST['alanguage']) ? '' : $_POST['alanguage'],
		'acomm'      => !empty($_POST['acomm']),
		'associated' => empty($_POST['assotop']) ? '' : implode(',', $_POST['assotop']),
		'ptime'      => empty($_POST['publish_time']) ? time() : strtotime($_POST['publish_time']),
		'display_order' => intval($_POST['display_order']),
	);

	if ('save' == $_POST['mode'])
	{
		if (isset($_GET['edit']))
		{
			$db->TBL->stories->update($story, 'sid='.intval($_GET['edit']));
			$db->TBL->poll_desc->update(array('poll_ptime' => $story['ptime']), 'artid='.intval($_GET['edit']));
		}
		else
		{
			$story['aid']       = is_admin();
			$story['identity_id'] = $userinfo['user_id'];
			$story['informant'] = ($userinfo['user_id'] > 1) ? $userinfo['username'] : $story['aid'];
			$story['poll_id']   = 0;

			$option_text = !empty($_POST['option_text']) ? $_POST['option_text'] : '';
			if (!empty($_POST['poll_title']) && !empty($option_text[0]) && !empty($option_text[1])) {
				$story['poll_id'] = $db->TBL->poll_desc->insert(array(
					'poll_title' => $_POST['poll_title'],
					'poll_ptime' => $story['ptime'],
					'time_stamp' => time(),
					'voters'     => 0,
					'planguage'  => $story['alanguage'],
					'artid'      => 0,
					'comments'   => $story['acomm']
				),'poll_id');
				foreach ($option_text as $i => $text) {
					$db->TBL->poll_data->insert(array(
						'poll_id'      => $story['poll_id'],
						'option_text'  => $text,
						'option_count' => 0,
						'vote_id'      => $i
					));
				}
			}

			$artid = $db->TBL->stories->insert($story, 'sid');
			if ($story['poll_id']) {
				$db->exec("UPDATE {$db->TBL->poll_desc} SET artid={$artid} WHERE poll_id={$story['poll_id']}");
			}

			if ($userinfo['user_id'] > 1) {
				$db->query("UPDATE {$db->TBL->users} SET counter = counter + 1 WHERE user_id = {$userinfo['user_id']}");
			}
		}
		URL::redirect(URL::admin('News'));
	}

	else if ('preview' == $_POST['mode'])
	{
		\Dragonfly\Page::title($OUT->L10N['_PREVIEWSTORY'], false);

		$story['associated'] = explode(',',$story['associated']);
		list($story['topicimage']) = $db->uFetchRow("SELECT topicimage FROM {$db->TBL->topics} WHERE topicid={$story['topic']}");

		if (\Dragonfly\Modules::isActive('Surveys')) {
			$story['poll'] = array(
				'title'   => $_POST['poll_title'],
				'options' => $_POST['option_text'],
			);
		}

		$OUT->view_story = true;
		$OUT->topics     = $db->query("SELECT topicid id, topictext label FROM {$db->TBL->topics} ORDER BY topictext");
		$OUT->categories = $db->query("SELECT catid id, title label FROM {$db->TBL->stories_cat} ORDER BY title");
		$OUT->story      = $story;
		$OUT->display('News/admin/edit');
	}
}

else if (isset($_GET['edit']) || 'add' == $_GET['mode'])
{
	if (isset($_GET['edit'])) {
		\Dragonfly\Page::title($OUT->L10N['_EDITARTICLE'], false);

		$story = $db->uFetchAssoc("SELECT
			sid, catid, title, hometext, bodytext, topic, notes, ihome, alanguage, acomm, topicimage, associated, display_order, ptime, poll_id
		FROM {$db->TBL->stories}
		LEFT JOIN {$db->TBL->topics} ON (topicid=topic)
		WHERE sid=".intval($_GET['edit']));
		$story['associated'] = explode(',',$story['associated']);
		$story['poll'] = false;

		if (false === strpos($story['hometext'].$story['bodytext'],'<')
		 && false !== strpos($story['hometext'].$story['bodytext'],'['))
		{
			$story['hometext'] = \Dragonfly\BBCode::decode($story['hometext'], 1, true);
			$story['bodytext'] = \Dragonfly\BBCode::decode($story['bodytext'], 1, true);
		}
	} else {
		$story = array(
			'catid'    => 0,
			'topic'    => 0,
			'ihome'    => 1,
			'acomm'    => $MAIN_CFG->global->articlecomm,
			'ptime'    => null,
			'title'    => '',
			'hometext' => '',
			'bodytext' => '',
			'alanguage' => '',
			'notes'    => '',
			'associated' => array(),
			'display_order' => 0,
			'poll_id'  => 0,
			'poll'     => false,
		);
		if (Dragonfly\Modules::isActive('Surveys')) {
			$story['poll'] = array(
				'title' => true,
				'options' => array('','','','','','','','','','','',''),
			);
		}
	}

	$OUT->story      = $story;
	$OUT->view_story = false;
	$OUT->topics     = $db->query("SELECT topicid id, topictext label FROM {$db->TBL->topics} ORDER BY topictext");
	$OUT->categories = $db->query("SELECT catid id, title label FROM {$db->TBL->stories_cat} ORDER BY title");
	$OUT->display('News/admin/edit');
}

else if (isset($_GET['del']))
{
	$sid = intval($_GET['del']);
	if (isset($_POST['cancel'])) { URL::redirect(URL::admin()); }
	if (isset($_POST['confirm']) && \Dragonfly\Output\Captcha::validate($_POST)) {
		$db->exec("DELETE FROM {$db->TBL->stories} WHERE sid={$sid}");
		$db->exec("DELETE FROM {$db->TBL->comments} WHERE sid={$sid}");
		//$db->exec("UPDATE {$db->TBL->poll_desc} SET artid=0 WHERE artid={$sid}");
		list($poll_id) = $db->uFetchRow("SELECT poll_id FROM {$db->TBL->poll_desc} WHERE artid={$sid}");
		if ($poll_id) {
			$db->exec("DELETE FROM {$db->TBL->poll_desc} WHERE poll_id={$poll_id}");
			$db->exec("DELETE FROM {$db->TBL->poll_data} WHERE poll_id={$poll_id}");
		}
		URL::redirect(URL::admin());
	}
	\Dragonfly\Page::confirm(URL::admin('&del='.$sid), sprintf(_ERROR_DELETE_CONF, 'this story'));
}

else if (isset($_GET['del_comment']))
{
	function removeSubComments($tid)
	{
		global $db;
		$result = $db->query("SELECT tid FROM {$db->TBL->comments} WHERE pid={$tid}");
		$numrows = $result->num_rows;
		if ($numrows) {
			while (list($ctid) = $result->fetch_row()) {
				$numrows += removeSubComments($ctid);
			}
			$db->query("DELETE FROM {$db->TBL->comments} WHERE pid={$tid}");
		}
		$result->free();
		return $numrows;
	}

	$tid = intval($_GET['del_comment']);
	list($sid) = $db->uFetchRow("SELECT sid FROM {$db->TBL->comments} WHERE tid={$tid}");
	if (isset($_POST['cancel'])) {
		URL::redirect(URL::index("News&file=article&sid={$sid}"));
	}
	if (isset($_POST['confirm'])) {
		$count = 1;
		$count += removeSubComments($tid);
		$db->query("UPDATE {$db->TBL->stories} SET comments=comments-{$count} WHERE sid={$sid}");
		$db->query("DELETE FROM {$db->TBL->comments} WHERE tid={$tid}");
		URL::redirect(URL::index("News&file=article&sid={$sid}"));
	}

	\Dragonfly\Page::confirm(URL::admin("&del_comment={$tid}"), _SURETODELCOMMENTS);
}

else if (isset($_GET['disporder']))
{
	$db->exec("UPDATE {$db->TBL->stories} SET display_order=".intval($_GET['disporder'])." WHERE sid=".intval($_GET['sidrep']));
	$db->exec("UPDATE {$db->TBL->stories} SET display_order=".intval($_GET['disporderrep'])." WHERE sid=".intval($_GET['sidori']));
	URL::redirect(URL::admin('News'));
}

else if (isset($_GET['unstick']))
{
	$db->exec("UPDATE {$db->TBL->stories} SET display_order=0 WHERE sid=".intval($_GET['unstick']));
	URL::redirect(URL::admin('News'));
}

else if (isset($_GET['stick']))
{
	list($sticky) = $db->uFetchRow("SELECT MAX(display_order) AS maxnumber FROM {$db->TBL->stories}");
	++$sticky;
	$db->exec("UPDATE {$db->TBL->stories} SET display_order={$sticky} WHERE sid=".intval($_GET['stick']));
	URL::redirect(URL::admin('News'));
}

else if (isset($_GET['comments']))
{
	$limit = 25;
	$offset = $limit * (max(1, $_GET->uint('page')) - 1);
	$tbl = $db->TBL->comments;
	$OUT->news_comments = $db->query("SELECT
		tid id,
		sid,
		date,
		remote_ip,
		comment,
		score,
		user_id,
		username
	FROM {$tbl}
	LEFT JOIN {$db->TBL->users} USING (user_id)
	ORDER BY date DESC
	LIMIT {$limit} OFFSET {$offset}");
	$OUT->news_comments_pagination = new \Poodle\Pagination('?admin&op=News&comments&page=${page}',
		$tbl->count(),
		$offset,
		$limit
	);
	$OUT->display('News/admin/comments');
}

else
{
	$OUT->scheduled_stories = $db->query("SELECT sid, title, ptime, alanguage FROM {$db->TBL->stories} WHERE ptime>".time()." ORDER BY ptime ASC");
	$OUT->sticky_stories =  $db->query("SELECT sid, title, alanguage, display_order, poll_id FROM {$db->TBL->stories}
		LEFT JOIN {$db->TBL->topics} AS t ON (topicid=topic)
		WHERE display_order <> '0'
		  AND ptime<=".time()."
		ORDER BY display_order DESC");
	$OUT->stories = $db->query("SELECT sid, title, alanguage, display_order, poll_id FROM {$db->TBL->stories}
		LEFT JOIN {$db->TBL->topics} AS t ON (topicid=topic)
		WHERE display_order = '0'
		  AND ptime<=".time()."
		ORDER BY ptime DESC
		LIMIT 20");
	$OUT->display('News/admin/index');
}
