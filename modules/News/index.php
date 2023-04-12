<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/News/index.php,v $
  $Revision: 9.24 $
  $Author: phoenix $
  $Date: 2007/09/11 15:28:07 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle .= _NewsLANG;

if (isset($_POST['score']) && isset($_POST['sid'])) {
	$sid = intval($_POST['sid']);
	$score = intval($_POST['score']);
	if ($score > 0 && $score < 6) {
		$rcookie = array();
		if (isset($_COOKIE['ratecookie'])) {
			$rcookie = explode(':', base64_decode($_COOKIE['ratecookie']));
		}
		for ($i=0; $i < sizeof($rcookie); $i++) {
			if ($rcookie[$i] == $sid) {
				$rated = _ALREADYVOTEDARTICLE;
				break;
			}
		}
		if (!isset($rated)) {
			$rated = _THANKSVOTEARTICLE;
			$rcookie[] = $sid;
			$db->sql_query("UPDATE ".$prefix."_stories SET score=score+$score, ratings=ratings+1 WHERE sid=$sid");
			$info = base64_encode(implode(':', $rcookie));
			setcookie('ratecookie',$info, ['expires' => gmtime()+3600, 'path' => $MAIN_CFG['cookie']['path']]);
		}
		cpg_error($rated, _ARTICLERATING, getlink('News&file=article&sid='.$sid));
	} else {
		cpg_error(_DIDNTRATE, _ARTICLERATING);
	}
}
$sid = isset($_POST['sid']) ? intval($_POST['sid']) : (isset($_GET['sid']) ? intval($_GET['sid']) : 0);
require_once("modules/$module_name/comments.php");

if (isset($_POST['postreply'])) {
	replyPost($sid); // store the reply
}
else if (isset($_GET['reply'])) {
	reply($sid); // reply to comment
}
elseif (isset($_POST['preview'])) {
	replyPreview($sid); // Preview the reply before storage
}
else if (isset($_GET['comment'])) {
	// Show comment X
	if (!isset($_GET['pid'])) {
		singlecomment(intval($_GET['comment']), $sid);
	} else {
		DisplayComments($sid, '', intval($_GET['pid']), intval($_GET['comment']));
	}
}
else {
	$storynum = (is_user() && $userinfo['storynum'] && $MAIN_CFG['member']['user_news']) ? $userinfo['storynum'] : $MAIN_CFG['global']['storyhome'];
	if (isset($_GET['page']) && intval($_GET['page']) > 1) {
		$page = intval($_GET['page']);
		$pagetitle .= '- '._PAGE.' '.$page;
	} else {
		$page = 1;
	}
	$offset = ($page - 1) * $storynum;
	$querylang = ($multilingual) ? "(alanguage='$currentlang' OR alanguage='')" : '';
	if ($multilingual) $querylang = "AND $querylang";
	$topic = isset($_GET['topic']) ? intval($_GET['topic']) : (isset($_GET['new_topic']) ? intval($_GET['new_topic']) : 0);
	$catid = isset($_GET['catid']) ? intval($_GET['catid']) : 0;
	if ($topic > 0) {
		$qdb = "topic='$topic'";
	} else {
//		$qdb = '(s.ihome=1 OR s.catid=0)';
		$qdb = 's.ihome=1';
	}

	$total = $db->sql_count($prefix.'_stories s', (($catid > 0) ? "s.catid='$catid'" : $qdb)." $querylang");
	$pages = ceil($total/$storynum);
	if ($pages < $page && $storynum > 0) { cpg_error(_PAGE.' '.$page.' does not exist'); }

	require_once('header.php');
	require_once('modules/News/functions.php');
	automated_news();
	if ($topic > 0) {
		$result_a = $db->sql_query("SELECT topictext FROM ".$prefix."_topics WHERE topicid='$topic'");
		$row_a = $db->sql_fetchrow($result_a);
		$topic_title = $row_a['topictext'];
		if ($db->sql_numrows($result_a) < 1) {
			$cpgtpl->assign_block_vars('newsempty', array(
				'S_NOTOPIC'   => _NOINFO4TOPIC,
				'S_GONEWS'    => _GOTONEWSINDEX,
				'S_SELECT'    => _SELECTNEWTOPIC,
				'S_SITENAME'  => $sitename,
				'U_NEWSINDEX' => getlink('News'),
				'U_TOPICS'    => getlink('Topics')
			));
		} else {
			$cpgtpl->assign_block_vars('newscat', array(
				'S_GOHOME'   => _GOTOHOME,
				'S_SEARCH'   => _SEARCH,
				'S_SEARCHON' => _SEARCHONTOPIC,
				'S_SELECT'   => _SELECTNEWTOPIC,
				'S_SITENAME' => $sitename,
				'S_TOPIC_T'  => $topic_title,
				'I_TOPIC'    => $topic,
				'U_HOME'     => $mainindex,
				'U_SEARCH'   => getlink('Search'),
				'U_TOPICS'   => getlink('Topics')
			));
		}
		$db->sql_freeresult($result_a);
	}
	$sql = 'SELECT s.*, sc.title AS cattitle, t.topicimage, t.topictext FROM '.$prefix.'_stories AS s
	LEFT JOIN '.$prefix.'_stories_cat AS sc ON (sc.catid=s.catid)
	LEFT JOIN '.$prefix.'_topics t ON t.topicid=s.topic WHERE ';
	$sql .= ($catid > 0) ? "s.catid='$catid' $querylang ORDER BY" : "$qdb $querylang ORDER BY display_order DESC,";
	$result = $db->sql_query($sql.' sid DESC LIMIT '.$offset.','.$storynum);

//	$sql .= ($catid > 0) ?  "s.catid='$catid' $querylang ORDER BY sid DESC" : "$qdb $querylang ORDER BY display_order DESC, time DESC";
//	$result = $db->sql_query($sql.' LIMIT 0,'.$storynum);

	require_once('includes/nbbcode.php');
	while ($row = $db->sql_fetchrow($result, SQL_ASSOC)) {
		$title = $row['title'];
		$row['hometext'] = decode_bb_all($row['hometext'], 1, true);
		$morecount = strlen($row['bodytext']);
		$comments = $row['comments'];
		$datetime = formatDateTime($row['time'], _DATESTRING);
		$story_link = '<a href="'.getlink('News&amp;file=article&amp;sid='.$row['sid']).'">';
		$morelink = $commentlink = $catlink = '';
		if ($morecount > 0 || $comments > 0) {
			$morelink .= $story_link.'<b>'._READMORE.'</b></a>';
			if ($morecount > 0) { $morelink .= ' ('.filesize_to_human($morecount).') | '; }
			else { $morelink .= ' | '; }
		}
		if ($row['acomm']) {
			if ($comments == 0) { $commentlink = $story_link._COMMENTSQ.'</a> | '; }
			elseif ($comments == 1) { $commentlink = $story_link.$comments.' '._COMMENT.'</a> | '; }
			elseif ($comments > 1) { $commentlink = $story_link.$comments.' '._COMMENTS.'</a> | '; }
		}
		$printlink = '<a href="'.getlink('News&amp;file=print&amp;sid='.$row['sid']).'"><img src="images/news/print.gif" alt="'._PRINTER.'" title="'._PRINTER.'" /></a>';
		$friendlink = '&nbsp;&nbsp;<a href="'.getlink('News&amp;file=friend&amp;sid='.$row['sid']).'"><img src="images/news/friend.gif" alt="'._FRIEND.'" title="'._FRIEND.'" /></a> | ';
		if ($row['catid'] != 0) {
			$title = '<a href="'.getlink('News&amp;catid='.$row['catid']).'">'.$row['cattitle'].'</a> : '.$title;
			$catlink = '<a href="'.getlink('News&amp;catid='.$row['catid']).'">'.$row['cattitle'].'</a> | ';
		}
		$rated = 0;
		if ($row['score'] != 0) {
			$rated = substr($row['score'] / $row['ratings'], 0, 4);
		}
		$scorelink = _SCORE.' '.$rated;
		$row['topicimage'] = ($row['topicimage'] !='') ? $row['topicimage'] : 'AllTopics.gif';
		$row['topictext'] = htmlprepare($row['topictext']);
		$row['informant'] = (($row['informant'] != '') ? '<a href="'.getlink("Your_Account&amp;profile=$row[informant]")."\">$row[informant]</a>" : _ANONYMOUS);
		$cpgtpl->assign_block_vars('newstopic', array(
			'IMG_TOPIC'   => (file_exists("themes/$CPG_SESS[theme]/images/topics/$row[topicimage]") ? "themes/$CPG_SESS[theme]/" : '')."images/topics/$row[topicimage]",
			'S_AUTHOR'    => $row['aid'],
			'S_INFORMANT' => $row['informant'],
			'S_MORELINK'  => $morelink,
			'S_COMMLINK'  => $commentlink,
			'S_PRNTLINK'  => $printlink,
			'S_FRNDLINK'  => $friendlink,
			'S_CATLINK'   => $catlink,
			'S_SCORLINK'  => $scorelink,
			'S_NOTE'      => _NOTE,
			'S_NOTES'     => $row['notes'],
			'S_POSTEDBY'  => _POSTEDBY,
			'S_STORY'     => $row['hometext'],
			'S_ON'        => _ON,
			'S_TEXTCOLOR1' => $textcolor1,
			'S_TEXTCOLOR2' => $textcolor2,
			'S_TIME'      => " $datetime ",
			'S_READS'     => "($row[counter] "._READS.")",
			'S_TITLE'     => $title,
			'S_TOPIC'     => $row['topictext'],
			'S_WRITES'    => _WRITES,
			'S_SID'       => $row['sid'],
			'U_NEWTOPIC'  => getlink("News&amp;topic=$row[topic]")
		));
	}
	$db->sql_freeresult($result);
	$tmp = (0 < $topic) ? '&amp;topic=' . $topic : ((0 < $catid) ? '&amp;catid=' . $catid : '');
	pagination('News'.$tmp.'&amp;page=', $pages, 1, $page);
	$cpgtpl->set_filenames(array('body' => 'news/index.html'));
	$cpgtpl->display('body');
}
