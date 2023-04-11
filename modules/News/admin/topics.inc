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
if (!can_admin('topics')) { die('Access Denied'); }

Dragonfly::getKernel()->L10N->load('Topics');
\Dragonfly\Page::title(_TOPICSMANAGER, false);

function getNewsTopicImages()
{
	$handle = opendir('images/topics');
	$images = array();
	while ($file = readdir($handle)) {
		if (preg_match("#^([a-zA-Z0-9_\-]+)([.]{1})([a-zA-Z0-9_\-]{3})$#", $file) && $file != 'news.png') {
			$images[] = $file;
		}
	}
	closedir($handle);
	natcasesort($images);
	return $images;
}

if (isset($_POST['createTopic']))
{
	$db->TBL->topics->insert(array(
		'topicimage' => $_POST->txt('topicimage'),
		'topictext'  => $_POST->txt('topictext'),
	));
	URL::redirect(URL::admin('&file=topics'));
}

elseif (isset($_POST['saveTopic']))
{
	$topic_id = $_GET->uint('id');
	$db->TBL->topics->update(array(
		'topicimage' => $_POST->txt('topicimage'),
		'topictext'  => $_POST->txt('topictext'),
	), "topicid = {$topic_id}");
	URL::redirect(URL::admin('&file=topics&id='.$topic_id));
}

elseif (isset($_POST['addRelated']))
{
	$topic_id = $_GET->uint('id');
	if (!empty($_POST['name']) && !empty($_POST['url'])) {
		$db->TBL->related->insert(array(
			'tid'  => $topic_id,
			'name' => $_POST->txt('name'),
			'url'  => $_POST->url('url'),
		));
	}
	URL::redirect(URL::admin('&file=topics&id='.$topic_id));
}

elseif (isset($_POST['saveRelated']))
{
	$topic_id = $_GET->uint('id');
	$db->TBL->related->update(array(
		'name' => $_POST->txt('name'),
		'url'  => $_POST->url('url'),
	), 'rid='.$_GET->uint('related'));
	URL::redirect(URL::admin('&file=topics&id='.$topic_id));
}

elseif (isset($_GET['rdelete']))
{
	$related_id = $_GET->uint('rdelete');
	list($topic_id) = $db->uFetchRow("SELECT tid FROM {$db->TBL->related} WHERE rid = {$related_id}");
	if (!$topic_id) { URL::redirect(URL::admin('&file=topics')); }
	if (isset($_POST['cancel'])) { URL::redirect(URL::admin('&file=topics&id='.$topic_id)); }
	if (isset($_POST['confirm'])) {
		$db->query("DELETE FROM {$db->TBL->related} WHERE rid = {$related_id}");
		URL::redirect(URL::admin('&file=topics&id='.$topic_id));
	}
	\Dragonfly\Page::confirm(URL::admin('&file=topics&rdelete='.$related_id), sprintf(_ERROR_DELETE_CONF, 'this related link'));
}

elseif (isset($_GET['delete']))
{
	$topic_id = $_POST->uint('topic_id');
	if (isset($_POST['cancel'])) { URL::redirect(URL::admin('&file=topics')); }
	if (isset($_POST['confirm'])) {
		$db->query("DELETE FROM {$db->TBL->topics} WHERE topicid = {$topic_id}");
		$db->query("DELETE FROM {$db->TBL->related} WHERE tid = {$topic_id}");
//		list($sid) = $db->uFetchRow("SELECT sid FROM {$db->TBL->stories} WHERE topic = {$topic_id}");
//		$db->query("DELETE FROM {$db->TBL->stories} WHERE topic = {$topic_id}");
//		$db->query("DELETE FROM {$db->TBL->comments} WHERE sid = {$sid}");
		URL::redirect(URL::admin('&file=topics'));
	}
	\Dragonfly\Page::confirm(URL::admin('&file=topics&delete='.$topic_id), sprintf(_ERROR_DELETE_CONF, 'this topic'));
}

elseif (isset($_GET['id']))
{
	$topic_id = $_GET->uint('id');
	if (!$topic_id) {
		cpg_error(sprintf(_ERROR_NOT_SET, 'ID'), _SEC_ERROR);
	}

	$K = \Dragonfly::getKernel();
	if (isset($_GET['related'])) {
		$related_id = $_GET->uint('related');
		if (empty($_GET['related'])) {
			cpg_error(sprintf(_ERROR_NOT_SET, 'ID'), _SEC_ERROR);
		}
		\Dragonfly\Page::title(_EDITRELATED, false);
		$K->OUT->topic_link = $K->SQL->uFetchAssoc("SELECT
			name,
			url
		FROM {$K->SQL->TBL->related}
		WHERE rid = {$related_id}");
		$K->OUT->display('News/admin/topic-link');
	} else {
		\Dragonfly\Page::title(_EDITTOPIC, false);
		$K->OUT->topic = $K->SQL->uFetchAssoc("SELECT
			topicid    id,
			topicimage image,
			topictext  text
		FROM {$K->SQL->TBL->topics}
		WHERE topicid = {$topic_id}");
		$K->OUT->topicimages = getNewsTopicImages();
		$K->OUT->topic_links = $db->query("SELECT rid id, name, url FROM {$db->TBL->related} WHERE tid = {$topic_id}");
		$K->OUT->display('News/admin/topic');
	}
}

else
{
	$K = \Dragonfly::getKernel();
	$K->OUT->topics = $K->SQL->query("SELECT
		topicid    id,
		'images/topics/' || topicimage image,
		topictext  text
	FROM {$K->SQL->TBL->topics}
	ORDER BY topictext");
	$K->OUT->topicimages = getNewsTopicImages();
	$K->OUT->display('News/admin/topics');
}
