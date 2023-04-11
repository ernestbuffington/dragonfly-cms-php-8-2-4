<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!class_exists('Dragonfly', false)) { exit; }
\Dragonfly\Page::title(_Surveys, false);

if (!isset($_POST['pollid']) && !isset($_GET['pollid'])) {
	$K = Dragonfly::getKernel();

	$querylang = $K->L10N->multilingual ? '' : "AND (planguage='{$K->L10N->lng}' OR planguage='')";

	$K->OUT->surveys = $db->query("SELECT
		poll_id    id,
		poll_title title,
		voters
	FROM {$db->TBL->poll_desc}
	WHERE artid=0 AND poll_ptime<".time()." {$querylang}
	ORDER BY poll_ptime DESC");

	$K->OUT->articles_surveys = $db->query("SELECT
		p.poll_id  id,
		poll_title title,
		voters,
		sid        article_id,
		title      article_title
	FROM {$db->TBL->poll_desc} p
	INNER JOIN {$db->TBL->stories} s ON (s.poll_id=p.poll_id AND s.ptime<=".time().")
	WHERE artid > 0 {$querylang}
	ORDER BY poll_ptime DESC");

	$K->OUT->display('Surveys/index');
	return;
}

$poll = new \Dragonfly\Modules\Surveys\Poll($_POST->uint('pollid') ?: $_GET->uint('pollid'));
if ($poll->ptime > time()) {
	cpg_error(sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_Surveys)));
}

if (isset($_POST['vote_id'])) {
	$poll->voteForOption($_POST->uint('vote_id'));
	$forwarder = isset($_POST['forwarder']) ? $_POST['forwarder'] : 0;
	if (strlen($forwarder)<5) $forwarder = URL::index("&pollid={$poll->id}&op=results");
	URL::redirect($forwarder);
}

if (isset($_POST['moderate'])) {
	\Dragonfly\Modules\Surveys\Comments::moderate();
	\URL::redirect($_SERVER['REQUEST_URI']);
}

if (isset($_POST['comment'])) {
	if (isset($_POST['preview'])) {
		// Preview the reply before storage
		\Dragonfly\Modules\Surveys\Comments::replyPreview();
	} else {
		// store the reply
		\Dragonfly\Modules\Surveys\Comments::replyPost($poll->id);
	}
}

else if (isset($_GET['reply']))
{
	// reply to comment
	\Dragonfly\Modules\Surveys\Comments::reply($poll->id);
}

else if ($poll->voted || ('results' == $_GET->txt('op') && $poll->id > 0))
{
	\Dragonfly\BBCode::pushHeaders();
	\Dragonfly\Page::title(': '.$poll->title, false);
	$OUT = Dragonfly::getKernel()->OUT;
	$OUT->poll = $poll;
	$OUT->poll_comment = '';
	$OUT->poll_comment_preview = false;
	$OUT->poll_comments_mod = ($poll->comments_count && 'nocomments' != $userinfo['umode'] && \Dragonfly\Comments\Comment::canModerate());
	$OUT->display('Surveys/results');
}

else
{
	\Dragonfly\Page::title(': '.$poll->title, false);
	$OUT = Dragonfly::getKernel()->OUT;
	$OUT->poll = $poll;
	$OUT->display('Surveys/poll');
}
