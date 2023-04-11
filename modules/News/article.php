<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (class_exists('Dragonfly', false)) {
	news_article();
}

function news_article()
{
	$sid = $_GET->uint('sid');
	try {
		$story = new \Dragonfly\Modules\News\Story($sid);
	} catch (\Exception $e) {
		cpg_error($e->getMessage(), 404);
	}
	if (!$story->id) {
		cpg_error('', 404);
	}
	$K = \Dragonfly::getKernel();

	if (!$story->allow_comments && (isset($_POST['comment']) || isset($_POST['preview']) || isset($_GET['reply']) || isset($_POST['moderate']))) {
		\URL::redirect(\URL::index('&file=article&sid='.$story->id));
	}

	if (isset($_POST['moderate'])) {
		\Dragonfly\Modules\News\Comments::moderate();
		\URL::redirect($_SERVER['REQUEST_URI']);
	}

	// Preview the reply before storage
	if (isset($_POST['preview'])) {
		return \Dragonfly\Modules\News\Comments::replyPreview();
	}

	// store the reply
	if (isset($_POST['comment'])) {
		\Dragonfly\Modules\News\Comments::replyPost($story->id);
	}

	// reply to comment
	if (isset($_GET['reply'])) {
		return \Dragonfly\Modules\News\Comments::reply($story->id);
	}

	$OUT = $K->OUT;
	// save article rating
	if (isset($_POST['vote'])) {
		$score = $_POST->uint('score');
		if ($score && $score < 6) {
			$rcookie = array();
			if (isset($_COOKIE['ratecookie'])) {
				$rcookie = explode(':', base64_decode($_COOKIE['ratecookie']));
			}
			if (in_array($story->id, $rcookie)) {
				$rated = $OUT->L10N['_ALREADYVOTEDARTICLE'];
			} else {
				$rated = $OUT->L10N['_THANKSVOTEARTICLE'];
				$rcookie[] = $story->id;
				$K->SQL->query("UPDATE {$K->SQL->TBL->stories} SET score=score+{$score}, ratings=ratings+1 WHERE sid={$story->id}");
				$info = base64_encode(implode(':', $rcookie));
				setcookie('ratecookie',$info,time()+3600, $K->CFG->cookie->path);
			}
			cpg_error($rated, $OUT->L10N['_ARTICLERATING'], URL::index('&file=article&sid='.$story->id));
		} else {
			cpg_error($OUT->L10N['_DIDNTRATE'], $OUT->L10N['_ARTICLERATING']);
		}
	}

	$K->SQL->query("UPDATE {$K->SQL->TBL->stories} SET counter=counter+1 WHERE sid={$story->id}");

	$datetime = Dragonfly::getKernel()->L10N->ISO_dt($story->ptime);
	$hometext = preg_replace('/\\[[^\\]]+\\]/','',strip_tags($story->hometext));
	$theme = $OUT->theme;

	$Social = Dragonfly::getKernel()->SOCIAL;

	# OpenGraph support
	$Social->addImage(BASEHREF.$story->topicimage_uri);

	# Default, also used in "Grouped" mode
	$args = array(
		'desc' => $hometext,
		'image' => BASEHREF.$story->topicimage_uri,
		'title' => $story->title
	);

	$OUT->news_article = $story;
	$OUT->assign_vars(array(
		'S_TIME'         => $datetime,
		'U_NEWTOPIC'     => URL::index('&topic='.$story->topic),
		'S_SHARE_BUTTON' => $Social->getSocial($args)
	));

	// Show Associated topics
	$assoc = '';
	$OUT->associated_topics = array();
	if ($story->associated) {
		$assoc = array();
		$result = $K->SQL->query("SELECT topicid id, topictext title FROM {$K->SQL->TBL->topics}
			WHERE topicid IN ({$story->associated})
			ORDER BY topictext");
		while ($atop = $result->fetch_assoc()) {
			$assoc[] = $atop['title'];
			$OUT->associated_topics[] = $atop;
		}
		$assoc = implode(', ', $assoc);
		$result->free();
	} else {
		$OUT->S_ASSOTOPICS = false;
	}
	//$themeblockside = 'right';
	//$Blocks->showblocks |= 2;
	//echo $Blocks->showblocks;
	$OUT->assign_vars(array('S_RIGHTBLOCKS' => true));

	/* Determine if the article has a poll attached */
	if (\Dragonfly\Modules::isActive('Surveys') && $story->poll_id) {
		$OUT->story_poll = $K->SQL->uFetchAssoc("SELECT
			poll_id    id,
			poll_title title,
			(SELECT SUM(option_count) FROM {$K->SQL->TBL->poll_data} WHERE poll_id={$story->poll_id} AND option_text!='') votes,
			(SELECT COUNT(*) FROM {$K->SQL->TBL->pollcomments} WHERE poll_id={$story->poll_id}) comments
		FROM {$K->SQL->TBL->poll_desc}
		WHERE poll_id={$story->poll_id}");
		$OUT->story_poll['options'] = $K->SQL->query("SELECT
			vote_id id,
			option_text text
		FROM {$K->SQL->TBL->poll_data}
		WHERE poll_id={$story->poll_id} AND option_text!=''
		ORDER BY vote_id");
		\Dragonfly\Blocks::custom(array (
			'bid' => 10001,
			'view_to' => 0,
			'side' => 'r',
			'title' => _SURVEY,
			'content' => $OUT->toString('News/blocks/poll')
		));
		unset($OUT->story_poll);
	}

	$querylang = (Dragonfly::getKernel()->L10N->multilingual ? "AND (alanguage='{$K->L10N->lng}' OR alanguage='')" : '');
	$OUT->story_related = array(
		'items' => $K->SQL->query("SELECT name, url FROM {$K->SQL->TBL->related} WHERE tid={$story->topic}"),
		'mostread' => $K->SQL->uFetchAssoc("SELECT sid id, title
			FROM {$K->SQL->TBL->stories}
			WHERE ptime<".time()." AND topic={$story->topic} {$querylang}
			ORDER BY counter DESC")
	);
	\Dragonfly\Blocks::custom(array (
		'bid' => 10002,
		'view_to' => 0,
		'side' => 'r',
		'title' => $OUT->L10N['_RELATED'],
		'content' => $OUT->toString('News/blocks/related')
	));
	unset($OUT->story_related);

	$rate = 0;
	if ($story->ratings) {
		$rate = substr($story->score / $story->ratings, 0, 4);
	}
	$OUT->story_vote = array(
		'rate' => $rate,
		'stars' => str_repeat('★', round($rate)),
	);
	\Dragonfly\Blocks::custom(array (
		'bid' => 10003,
		'view_to' => 0,
		'side' => 'r',
		'title' => $OUT->L10N['_RATEARTICLE'],
		'content' => $OUT->toString('News/blocks/vote')
	));
	unset($OUT->story_vote);

	\Dragonfly\Page::title(_NewsLANG, false);
	\Dragonfly\Page::title($story->title, false);
	\Dragonfly\Page::metatag('description', $hometext);

	\Dragonfly\BBCode::pushHeaders();

	$OUT->comment_reply = array('comment' => '');
	$OUT->comment_reply_to = null;
	$OUT->news_article_comments_mod = ($story->comments_count && 'nocomments' != $K->IDENTITY->umode && \Dragonfly\Comments\Comment::canModerate());
	$OUT->display('News/article');
}
