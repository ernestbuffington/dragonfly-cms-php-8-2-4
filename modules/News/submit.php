<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }
$K = Dragonfly::getKernel();
$K->L10N->load('Submit_News');
\Dragonfly\Page::title(_Submit_NewsLANG, false);

class QueueStory extends \Dragonfly\Modules\News\Story
{
	function __construct()
	{
		$CFG = \Dragonfly::getKernel()->CFG;
		if (!$CFG->global->anonpost && !is_user()) {
			cpg_error(_MODULEUSERS . ($CFG->member->allowuserreg ? _MODULEUSERS2 : ''), 401);
		}
	}

	function __set($k, $v)
	{
		if (property_exists($this, $k)) {
			if (is_int($this->$k)) {
				$this->$k = (int)$v;
			} else if (is_bool($this->$k)) {
				$this->$k = !!$v;
			} else {
				$this->$k = trim($v);
			}
		} else {
			trigger_error("Property '{$k}' does not exist");
		}
	}

	function save()
	{
		$K = \Dragonfly::getKernel();
		$story = array(
			'topic'     => $this->topic,
			'subject'   => check_words($this->title),
			'story'     => check_words($this->hometext),
			'storyext'  => check_words($this->bodytext),
			'alanguage' => $this->language,
			'uid'       => $K->IDENTITY->id,
			'uname'     => $K->IDENTITY->nickname,
			'timestamp' => time(),
		);
		$K->SQL->TBL->queue->insert($story);

		$cfg = $K->CFG->global;
		if ($cfg->notify) {
			$notify_message = "{$cfg->notify_message}\n\n\n========================================================\n{$story['subject']}\n\n\n"
				.strip_tags($story['story'])."\n\n"
				.strip_tags($story['storyext'])."\n\n{$story['uname']}";
			if (!\Dragonfly\Email::send($mailer_message, $cfg->notify_subject, $notify_message,
				$cfg->notify_email, $cfg->notify_email, $cfg->notify_from, $story['uname']))
			{
				echo $mailer_message;
			}
		}
	}
}

$story = new QueueStory();
if (isset($_POST)) {
	$story->topic     = $_POST->uint('topic');
	$story->title     = $_POST['title'];
	$story->hometext  = $_POST->html('hometext');
	$story->bodytext  = $_POST->html('bodytext');
	if ($K->L10N->multilingual) {
		$story->language  = $_POST['language'];
	}
	if (isset($_POST['save'])) {
		if (!\Dragonfly\Output\Captcha::validate($_POST)) {
			\Poodle\Notify::error(_SPAMGUARDPROTECTED);
		} else {
			$story->save();
			cpg_error(_SUBTEXT, _Submit_NewsLANG, URL::index('&file=submit'));
		}
	}
}
list($story->topicimage) = $db->uFetchRow("SELECT topicimage FROM {$db->TBL->topics} WHERE topicid={$story->topic}");

\Dragonfly\Output\Js::add('includes/poodle/javascript/wysiwyg.js');
\Dragonfly\Output\Css::add('wysiwyg');

$K->OUT->story = $story;
$K->OUT->view_story = isset($_POST['hometext']);
$K->OUT->topics = $db->query("SELECT topicid id, topictext label FROM {$db->TBL->topics} ORDER BY topictext");
$K->OUT->display('News/add');
