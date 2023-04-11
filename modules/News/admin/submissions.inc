<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('news')) { die('Access Denied'); }
Dragonfly::getKernel()->L10N->load('News');

$qid = $_GET->uint('id');
$mode = isset($_POST['mode']) ? $_POST['mode'] : '';

if ($qid) {
	$acomm = \Dragonfly::getKernel()->CFG->global->articlecomm ? 1 : 0;
	$story = $db->uFetchAssoc("SELECT
		0        catid,
		subject  title,
		story    hometext,
		storyext bodytext,
		''       notes,
		topic,
		1        ihome,
		alanguage,
		{$acomm} acomm,
		''       associated,
		NULL     ptime,
		uname    informant,
		0        poll_id,
		0        display_order,
		uid      identity_id
	FROM {$db->TBL->queue}
	WHERE qid = {$qid}");
	if (!$story) {
		cpg_error('Submission not found', 404);
	}

	if (isset($_GET['del']) || 'delete' == $mode) {
		$db->query("DELETE FROM {$db->TBL->queue} WHERE qid={$qid}");
		URL::redirect(URL::admin('News&file=submissions'));
	}

	if (\Dragonfly\Modules::isActive('Surveys')) {
		$story['poll'] = array(
			'title' => '',
			'options' => array('','','','','','','','','','','',''),
		);
	} else {
		$story['poll'] = false;
	}

	if ('POST' == $_SERVER['REQUEST_METHOD']) {
		$story['catid']      = intval($_POST['catid']);
		$story['title']      = $_POST['title'];
		$story['hometext']   = $_POST->html('hometext');
		$story['bodytext']   = $_POST->html('bodytext');
		$story['notes']      = empty($_POST['notes']) ? '' : $_POST['notes'];
		$story['topic']      = intval($_POST['topic']);
		$story['ihome']      = !empty($_POST['ihome']);
		$story['alanguage']  = empty($_POST['alanguage']) ? '' : $_POST['alanguage'];
		$story['acomm']      = !empty($_POST['acomm']);
		$story['associated'] = empty($_POST['assotop']) ? '' : implode(',', $_POST['assotop']);
		$story['ptime']      = empty($_POST['publish_time']) ? null : strtotime($_POST['publish_time']);
		$story['informant']  = $_POST['author'];
		$story['display_order'] = intval($_POST['display_order']);
		if ($story['poll']) {
			$story['poll'] = array(
				'title' => $_POST['poll_title'],
				'options' => $_POST['option_text'],
			);
		}
	}

	if ('save' == $mode) {
		if ($story['poll']) {
			if (!empty($story['poll']['title'])
			 && !empty($story['poll']['options'][0])
			 && !empty($story['poll']['options'][1]))
			{
				$story['poll_id'] = $db->TBL->poll_desc->insert(array(
					'poll_title' => $story['poll']['title'],
					'poll_ptime' => $story['ptime'],
					'time_stamp' => time(),
					'voters'     => 0,
					'planguage'  => $story['alanguage'],
					'artid'      => 0,
					'comments'   => $story['acomm']
				),'poll_id');
				foreach ($story['poll']['options'] as $i => $text) {
					$db->TBL->poll_data->insert(array(
						'poll_id'      => $story['poll_id'],
						'option_text'  => $text,
						'option_count' => 0,
						'vote_id'      => $i
					));
				}
			}
		}

		$uid = (int)$story['identity_id'];
		if (!$story['ptime']) { $story['ptime'] = time(); }
		$story['aid'] = is_admin();
		unset($story['poll']);
		$artid = $db->TBL->stories->insert($story, 'sid');
		if ($story['poll_id']) {
			$db->exec("UPDATE {$db->TBL->poll_desc} SET artid = {$artid} WHERE poll_id={$story['poll_id']}");
		}
		if ($uid > 1) {
			$db->query("UPDATE {$db->TBL->users} SET counter = counter + 1 WHERE user_id = {$uid}");
		}
		$db->query("DELETE FROM {$db->TBL->queue} WHERE qid = {$qid}");
		URL::redirect(URL::admin('News&file=submissions'));
	}

	$TPL = \Dragonfly::getKernel()->OUT;
	\Dragonfly\Page::title($TPL->L10N['_SUBMISSIONSADMIN']);
	\Dragonfly\Output\Js::add('includes/poodle/javascript/wysiwyg.js');
	\Dragonfly\Output\Css::add('wysiwyg');

	$TPL->view_story = ('preview' == $mode);
	$TPL->topics     = $db->query("SELECT topicid id, topictext label FROM {$db->TBL->topics} ORDER BY topictext");
	$TPL->categories = $db->query("SELECT catid id, title label FROM {$db->TBL->stories_cat} ORDER BY title");
	if ($TPL->view_story) {
		list($story['topicimage']) = $db->uFetchRow("SELECT topicimage FROM {$db->TBL->topics} WHERE topicid={$story['topic']}");
	}
	$story['associated'] = explode(',', $story['associated']);
	$TPL->story = $story;
	$TPL->display('News/admin/submission');
}

else if ('all' == $_GET->txt('del')) {
	$db->query("DELETE FROM {$db->TBL->queue}");
	URL::redirect(URL::admin());
}

else {
	$TPL = \Dragonfly::getKernel()->OUT;
	\Dragonfly\Page::title($OUT->L10N['_SUBMISSIONSADMIN']);
	$TPL->news_submissions = $db->query("SELECT qid, subject, timestamp, alanguage FROM {$db->TBL->queue} ORDER BY timestamp DESC");
	$TPL->display('News/admin/submissions');
}
