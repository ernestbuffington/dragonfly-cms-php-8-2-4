<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/News/article.php,v $
  $Revision: 9.13 $
  $Author: phoenix $
  $Date: 2007/09/20 01:20:20 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

global $textcolor1, $textcolor2;
if (isset($_GET['sid'])) {
	$sid = intval($_GET['sid']);
} else {
	url_redirect(getlink());
}

$result = $db->sql_query('SELECT s.*, c.title as cattitle, t.topicimage, t.topictext FROM '.$prefix.'_stories s
  LEFT JOIN '.$prefix.'_stories_cat c ON c.catid=s.catid
  LEFT JOIN '.$prefix.'_topics t ON t.topicid=s.topic WHERE s.sid='.$sid);
if ($db->sql_numrows($result) != 1) { url_redirect(getlink()); }
$story = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

$db->sql_query('UPDATE '.$prefix.'_stories SET counter=counter+1 WHERE sid='.$sid);

$pagetitle .= _NewsLANG.' '._BC_DELIM.' '.$story['title'];

require_once('includes/nbbcode.php');
$datetime = formatDateTime($story['time'], _DATESTRING);
$hometext = decode_bb_all($story['hometext'], 1, true);
$bodytext = decode_bb_all($story['bodytext'], 1, true);
$notes = decode_bb_all($story['notes'], 1, true);

if ($story['catid'] > 0) {
	$story['title'] = '<a href="'.getlink('&amp;catid='.$story['catid']).'"><span class="storycat">'.$story['cattitle'].'</span></a>: '.$story['title'];
}
$code_lang = get_langcode($language);
$story['topicimage'] = ($story['topicimage'] !='') ? $story['topicimage'] : 'AllTopics.gif';
$story['informant'] = (($story['informant'] != '') ? '<a href="'.getlink('Your_Account&amp;profile='.$story['informant'])."\">$story[informant]</a>" : _ANONYMOUS);
$cpgtpl->assign_vars(array(
	'IMG_TOPIC'	  => (file_exists("themes/$CPG_SESS[theme]/images/topics/".$story['topicimage']) ? "themes/$CPG_SESS[theme]/" : '').'images/topics/'.$story['topicimage'],
	'S_ADMIN'	  => (can_admin('News') ? '<a href="'.adminlink('&amp;edit='.$sid).'">'._EDIT.'</a> | <a href="'.adminlink('&amp;del='.$sid).'">'._DELETE.'</a>' : ''),
	'S_AUTHOR'	  => $story['aid'],
	'S_INFORMANT' => $story['informant'],
	'S_NOTE'	  => _NOTE,
	'S_NOTES'	  => $notes,
	'S_POSTEDBY'  => _POSTEDBY,
	'S_STORY'	  => $hometext,
	'S_STORYEXT'  => $bodytext,
	'S_ON'		  => _ON,
	'S_TEXTCOLOR1' => $textcolor1,
	'S_TEXTCOLOR2' => $textcolor2,
	'S_TIME'	  => " $datetime ",
	'S_READS'	  => "($story[counter] "._READS.")",
	'S_TITLE'	  => $story['title'],
	'S_TOPIC'	  => $story['topictext'],
	'S_WRITES'	  => _WRITES,
	'S_SID'       => $sid,
	'U_NEWTOPIC'  => getlink('&amp;topic='.$story['topic'])
));

// Show Associated topics
$assoc = '';
if ($story['associated'] != '') {
	if (substr($story['associated'], -1) == '-') $story['associated'] = substr($story['associated'], 0, -1);
	$story['associated'] = preg_replace('#\-#m', ',', $story['associated']);
	$result = $db->sql_query('SELECT topicid, topicimage, topictext from '.$prefix."_topics WHERE topicid IN ($story[associated])");
	while ($atop = $db->sql_fetchrow($result)) {
		$atop['topicimage'] = (file_exists("themes/$CPG_SESS[theme]/images/topics/$atop[topicimage]") ? "themes/$CPG_SESS[theme]/" : '')."images/topics/$atop[topicimage]";
		$assoc .= '<a href="'.getlink('&amp;topic='.$atop['topicid']).'"><img src="'.$atop['topicimage'].'" style="padding-right:10px;" alt="'.$atop['topictext'].'" title="'.$atop['topictext'].'" /></a>';
	}
	$db->sql_freeresult($result);
	if ($assoc != '') {
		$cpgtpl->assign_vars(array(
			'S_ASSOTOPIC'  => _ASSOTOPIC,
			'S_ASSOTOPICS' => $assoc
		));
	}
} else {
	$cpgtpl->assign_vars(array('S_ASSOTOPICS' => false));
}
$cpgtpl->set_filenames(array('body' => 'news/article.html'));
//$themeblockside = 'right';
//$Blocks->showblocks |= 2;
//echo $Blocks->showblocks;
$cpgtpl->assign_vars(array('S_RIGHTBLOCKS' => true));

// Show comments

$querylang = ($multilingual) ? "AND (blanguage='$currentlang' OR blanguage='')" : '';

/* Determine if the article has a poll attached */
if (is_active('Surveys') && $story['haspoll']) {
	$poll_id = $story['poll_id'];
	$content = '<form action="'.getlink('Surveys').'" method="post">';
	$content .= '<input type="hidden" name="pollid" value="'.$poll_id.'" />';
	$content .= '<input type="hidden" name="forwarder" value="'.getlink('Surveys&amp;op=results&amp;pollid='.$poll_id).'" />';
	list($poll_title, $voters) = $db->sql_ufetchrow("SELECT poll_title, voters FROM ".$prefix."_poll_desc WHERE poll_id='$poll_id'", SQL_NUM);
	$content .= '<span class="content"><b>'.$poll_title.'</b></span><br /><br />';
	$content .= '<table width="100%">';
	$sum = 0;
	$result = $db->sql_query("SELECT option_text, vote_id, option_count FROM ".$prefix."_poll_data WHERE poll_id='$poll_id' AND option_text!='' ORDER BY vote_id");
	while ($row = $db->sql_fetchrow($result)) {
		$content .= '<tr><td valign="top"><input type="radio" name="vote_id" value="'.$row['vote_id'].'" /></td>';
		$content .= '<td width="100%" class="content">'.$row['option_text'].'</td></tr>';
		$sum += $row['option_count'];
	}
	$db->sql_freeresult($result);
	$content .= '</table><br /><div style="text-align:center;" class="content"><input type="submit" value="'._VOTE.'" /><br />';
	$content .= '<br /><a href="'.getlink('Surveys&amp;op=results&amp;pollid='.$poll_id).'"><b>'._RESULTS.'</b></a> <b>::</b> <a href="'.getlink('Surveys').'"><b>'._POLLS.'</b></a><br />';
	$content .= '<br />'._VOTES.': <b>'.$sum.'</b>';
	if ($pollcomm) {
		list($numcom) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$prefix."_pollcomments WHERE poll_id='$poll_id'");
		$content .= '<br />'._PCOMMENTS.' <b>'.$numcom.'</b>';
	}
	$content .= '</div></form>';
	$block = array (
		'bid' => 10001,
		'view' => 0,
		'side' => 'r',
		'title' => _RELATED,
		'content' => $content
	);
	$Blocks->custom($block);
}

$content = '<span class="content">';
$result = $db->sql_query("SELECT name, url FROM ".$prefix."_related WHERE tid=".$story['topic']."");
while (list($name, $url) = $db->sql_fetchrow($result)) {
	$content .= '<b>&#8226;</b>&nbsp;<a href="'.$url.'" target="_blank">'.$name.'</a><br />';
}
$db->sql_freeresult($result);

$querylang = ($multilingual) ? "AND (alanguage='$currentlang' OR alanguage='')" : '';

list($topstory, $ttitle) = $db->sql_ufetchrow("SELECT sid, title FROM ".$prefix."_stories WHERE topic=".$story['topic']." $querylang ORDER BY counter DESC LIMIT 0,1");

$content .= '<b>&#8226;</b>&nbsp;<a href="'.getlink('&amp;topic='.$story['topic']).'">'._MOREABOUT.' '.$story['topictext'].'</a>
</span><br /><hr style="width:95%;" /><div style="text-align:center;" class="content"><b>'._MOSTREAD.' '.$story['topictext'].':</b><br />
<a href="'.getlink('&amp;file=article&amp;sid='.$topstory).'">'.$ttitle.'</a></div>';
$block = array (
	'bid' => 10002,
	'view' => 0,
	'side' => 'r',
	'title' => _RELATED,
	'content' => $content
);
$Blocks->custom($block);

if ($story['ratings'] > 0) {
	$rate = substr($story['score'] / $story['ratings'], 0, 4);
	$r_image = round($rate);
	$the_image = '<br /><br /><img src="'.(file_exists('themes/'.$CPG_SESS['theme'].'/images/news/stars-'.$r_image.'.gif') ? 'themes/'.$CPG_SESS['theme'].'/images/news/stars-'.$r_image.'.gif' : 'images/news/stars-'.$r_image.'.gif').'" alt="" /><br />';
} else {
	$rate = 0;
	$the_image = '<br />';
}
$content = "<div style=\"text-align:center;\">"._AVERAGESCORE.": <b>$rate</b><br />"._VOTES.": <b>".$story['ratings']."</b>$the_image"._RATETHISARTICLE."</div><br />";
$content .= '<form action="'.getlink().'" method="post"><div>';
$content .= '<input type="hidden" name="sid" value="'.$sid.'" />';
$content .= '<input type="radio" name="score" value="5" /> <img src="'.(file_exists('themes/'.$CPG_SESS['theme'].'/images/news/stars-5.gif') ? 'themes/'.$CPG_SESS['theme'].'/images/news/stars-5.gif' : 'images/news/stars-5.gif').'" alt="'._EXCELLENT.'" title="'._EXCELLENT.'" /><br />';
$content .= '<input type="radio" name="score" value="4" /> <img src="'.(file_exists('themes/'.$CPG_SESS['theme'].'/images/news/stars-4.gif') ? 'themes/'.$CPG_SESS['theme'].'/images/news/stars-4.gif' : 'images/news/stars-4.gif').'" alt="'._VERYGOOD.'" title="'._VERYGOOD.'" /><br />';
$content .= '<input type="radio" name="score" value="3" /> <img src="'.(file_exists('themes/'.$CPG_SESS['theme'].'/images/news/stars-3.gif') ? 'themes/'.$CPG_SESS['theme'].'/images/news/stars-3.gif' : 'images/news/stars-3.gif').'" alt="'._GOOD.'" title="'._GOOD.'" /><br />';
$content .= '<input type="radio" name="score" value="2" /> <img src="'.(file_exists('themes/'.$CPG_SESS['theme'].'/images/news/stars-2.gif') ? 'themes/'.$CPG_SESS['theme'].'/images/news/stars-2.gif' : 'images/news/stars-2.gif').'" alt="'._REGULAR.'" title="'._REGULAR.'" /><br />';
$content .= '<input type="radio" name="score" value="1" /> <img src="'.(file_exists('themes/'.$CPG_SESS['theme'].'/images/news/stars-1.gif') ? 'themes/'.$CPG_SESS['theme'].'/images/news/stars-1.gif' : 'images/news/stars-1.gif').'" alt="'._BAD.'" title="'._BAD.'" /><br /><br /></div>';
$content .= '<div style="text-align:center;"><input type="submit" value="'._CASTMYVOTE.'" /></div></form>';
$block = array (
	'bid' => 10003,
	'view' => 0,
	'side' => 'r',
	'title' => _RATEARTICLE,
	'content' => $content
);
$Blocks->custom($block);

$content = '<br />&nbsp;<img src="images/news/print.gif" style="width:16px; height:11px;" alt="'._PRINTER.'" title="'._PRINTER.'" />&nbsp;&nbsp;<a href="'.getlink('&amp;file=print&amp;sid='.$sid).'">'._PRINTER.'</a><br /><br />
&nbsp;<img src="images/news/friend.gif" style="width:16px; height:11px;" alt="'._FRIEND.'" title="'._FRIEND.'" />&nbsp;&nbsp;<a href="'.getlink('&amp;file=friend&amp;sid='.$sid).'">'._FRIEND.'</a><br /><br />';
if (can_admin('news')) {
	$content .= '<div style="text-align:center;"><b>'._ADMIN.'</b><br />[ <a href="'.adminlink('&amp;mode=add').'">'._ADD.'</a> | <a href="'.adminlink('&amp;edit='.$sid).'">'._EDIT.'</a> | <a href="'.adminlink('&amp;del='.$sid).'">'._DELETE.'</a> ]</div>';
}
$block = array (
	'bid' => 10004,
	'view' => 0,
	'side' => 'r',
	'title' => _OPTIONS,
	'content' => $content
);
$Blocks->custom($block);
require_once('header.php');
$cpgtpl->display('body');
if ($story['acomm'] && $MAIN_CFG['global']['articlecomm'] && $userinfo['umode'] != 'nocomments') {
	require_once("modules/$module_name/comments.php");
	DisplayComments($sid, $story['title']);
}
