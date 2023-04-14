<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/News/search.inc,v $
  $Revision: 9.7 $
  $Author: phoenix $
  $Date: 2007/05/01 11:05:28 $
**********************************************/

class News_search
{
	var $title;
	var $options;
	var $result = array();
	var $result_count = 0;
	var $link_next;
	var $link_prev;

	// class constructor
	function News_search() {
		global $db, $prefix;
		$cats[0] = _ALL;
		$catlist = $db->sql_query("SELECT catid, title FROM ".$prefix."_stories_cat ORDER BY title");
		while (list($id, $title) = $db->sql_fetchrow($catlist)) {
			$cats[$id] = $title;
		}
		$db->sql_freeresult($catlist);

		$topics[0] = _ALLTOPICS;
		$toplist = $db->sql_query("SELECT topicid, topictext FROM ".$prefix."_topics ORDER BY topictext");
		while (list($id, $title) = $db->sql_fetchrow($toplist)) {
			$topics[$id] = $title;
		}
		$db->sql_freeresult($toplist);

		$this->title = _NewsLANG;
		$this->options = '<table border="0"><tr><td>'._TOPIC.'</td><td>'.select_box('topic', 0, $topics).'</td></tr>'
			.'<tr><td>'._CATEGORY.'</td><td>'.select_box('cat', 0, $cats).'</td></tr>'
			.'<tr><td>'._PCOMMENTS.'</td><td>'.yesno_option("news_search_comments", 0).'</td></tr>'
			.'</table>';
	}

	function search($search, $url_search, $limit=10, $page=0) {
		global $db, $prefix, $multilingual, $currentlang;
		$first = $page*$limit;

		$cat   = (isset($_POST['cat']) ? intval($_POST['cat']) : 0);
		$days  = (isset($_POST['days']) ? (intval($_POST['days'])*86400) : 0);
		$topic = (isset($_POST['topic']) ? intval($_POST['topic']) : 0);
		$search_comments = isset($_POST['news_search_comments']) ? intval($_POST['news_search_comments']) : (isset($_GET['news_search_comments']) ? intval($_GET['news_search_comments']) : 0);

		$categ = ($cat > 0) ? "catid=$cat" : 'catid>-1';
		$q	= "SELECT s.sid, s.informant, s.title, s.time, s.comments, s.topic, s.hometext, t.topictext FROM ".$prefix."_stories s LEFT JOIN ".$prefix."_topics t ON s.topic = t.topicid WHERE $categ";
		if ($multilingual) $q .= " AND (s.alanguage='$currentlang' OR s.alanguage='')";
		if ($search != '') $q .= " AND (s.title LIKE '%$search%' OR s.hometext LIKE '%$search%' OR s.bodytext LIKE '%$search%' OR s.notes LIKE '%$search%')";
		if ($topic > 0) $q .= " AND s.topic='$topic'";
		if ($days > 0) $q .= " AND s.time >= ".(gmtime()-$days);
		$q .= " ORDER BY s.time DESC LIMIT $first,".($limit+1);
		$result = $db->sql_query($q);
		$nrows	= $db->sql_numrows($result);
		$crows  = 0;

		$x = ($limit > $nrows) ? $nrows : $limit;
		if ($nrows > 0) {
			while($x > 0) {
				list($sid, $informant, $title, $time, $comments, $ntopic, $hometext, $topictext) = $db->sql_fetchrow($result);
				if ($informant == '') {
					$item['author'] = _ANONYMOUS;
				} else {
					$item['author'] = "<a href=\"".getlink("Your_Account&amp;profile=$informant")."\">$informant</a>";
				}
				$item['title'] = $title;
				$item['image'] = 'images/folders.gif';
				$item['date'] = $time;
				$item['url'] = getlink("News&amp;file=article&amp;sid=$sid");
				$item['short_text'] = substr(strip_tags(decode_bbcode($hometext)), 0, 128);
				$item['footer'] = _TOPIC . ': <a href="'.getlink("&amp;search=$url_search&amp;topic=$ntopic")."\">$topictext</a>";
				if ($comments == 0) {
					$item['footer'] .= ' ('._NOCOMMENTS.')';
				} elseif ($comments == 1) {
					$item['footer'] .= " ($comments "._UCOMMENT.')';
				} elseif ($comments >1) {
					$item['footer'] .= " ($comments "._UCOMMENTS.')';
				}

				if (can_admin('news')) {
					$item['footer'] .= ' [ <a href="'.adminlink("News&amp;edit=$sid").'">'._EDIT.'</a> | <a href="'.adminlink("News&amp;del=$sid").'">'._DELETE.'</a> ]';
				}

				$this->add_result($item);
				unset($item);

				$x--;
			}
		}

		$db->sql_fetchrow($result);
		
		// this would be much nicer if we'd join the comments with the articles and include
		// the comments results as a subset of the article results. Consider fetching comments
		// inside the above loop.
		if ($search_comments == 1) {
			$result = $db->sql_query("SELECT c.tid, c.sid, c.pid, c.subject, c.date, c.name, c.comment, s.topic, t.topictext FROM ".$prefix."_comments c LEFT JOIN ".$prefix."_stories s ON c.sid = s.sid LEFT JOIN ".$prefix."_topics t ON s.topic = t.topicid WHERE (subject like '%$search%' OR comment like '%$search%') ORDER BY date DESC LIMIT $first,".($limit+1));
			$crows = $db->sql_numrows($result);
			
			if ($crows>0) {
				while(list($tid, $sid, $pid, $subject, $date, $name, $comment, $title, $topictext) = $db->sql_fetchrow($result)) {
					$reply = $db->sql_count($prefix."_comments", "pid='$tid'");

					if(!$name) {
						$item['author'] = _ANONYMOUS;
					} else {
						$item['author'] = "<a href=\"".getlink("Your_Account&amp;profile=$name")."\">$name</a>";
					}

					$item['footer'] = _TOPIC . ': <a href="'.getlink("&amp;search=$url_search&amp;topic=$ntopic")."\">$topictext</a>";

					if ($reply == 1) {
						$item['footer'] .= " ($reply "._SREPLY.")";
					} else {
						$item['footer'] .= " ($reply "._SREPLIES.")";
					}
					if (is_admin()) {
						$item['footer'] .= ' [ <a href="'.adminlink("comments&amp;del=$tid").'">'._DELETE.'</a> ]';
					}
					$item['title'] = $subject;
					$item['image'] = 'images/folders.gif';
					$item['date'] = $date;
					$item['url'] = getlink("News&amp;file=article&amp;sid=$sid#$tid");
					$item['short_text'] = substr(strip_tags(decode_bbcode($comment)), 0, 128);
					
					$this->add_result($item);
					unset($item);
				}
			}
		}

		if ($page>0) {
			$this->link_prev = '<a href="'.getlink('Search&amp;page='.($page-1)."&amp;topic=$topic&amp;cat=$cat&amp;search=$url_search&amp;news_search_comments=$search_comments&amp;mod=News").'"><strong>'._PREVMATCHES.' '._IN.' '._NewsLANG.'</strong></a>';
		}
		if (($nrows > $limit) || ($crows > $limit)) {
			$this->link_next = '<a href="'.getlink('Search&amp;page='.($page+1)."&amp;topic=$topic&amp;cat=$cat&amp;search=$url_search&amp;news_search_comments=$search_comments&amp;mod=News").'"><strong>'._NEXTMATCHES.' '._IN.' '._NewsLANG.'</strong></a>';
		}

	}

	function add_result($resultarray) {
		$this->result[$this->result_count++] = $resultarray;
	}
}
