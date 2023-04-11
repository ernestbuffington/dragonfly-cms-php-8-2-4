<?php
/********************************************************************
* CPG DragonflyCMS                                                  *
*********************************************************************
* Dragonfly is released under the terms and conditions              *
* of the GNU GPL version 2 or any later version                     *
* Basic forum stats block v9.1.2 by Phoenix at http://nukebiz.com/  *
* Suitable for ForumsPlus                                           *
********************************************************************/
if (!defined('CPG_NUKE')) { exit; }

require_once(dirname(__DIR__).'/classes/BoardCache.php');
global $db;

$board_config = BoardCache::conf();

list($total_posts) = $db->uFetchRow("SELECT SUM(forum_posts) FROM {$db->TBL->bbforums}");
list($total_topics) = $db->uFetchRow("SELECT SUM(forum_topics) FROM {$db->TBL->bbforums}");
$archived_topics = $db->count('bbtopics', 'topic_archive_flag = 1');
$archived_posts = $db->count('bbposts_archive');

$start_date = \Dragonfly::getKernel()->L10N->date($board_config['default_dateformat'], $board_config['board_startdate']);
$boarddays = ceil((time() - $board_config['board_startdate']) / 86400);
$posts_per_day = sprintf('%.2f', ($total_posts + $archived_posts) / $boarddays);
$topics_per_day = sprintf('%.2f', ($total_topics + $archived_topics) / $boarddays);

$content = '<div style="padding:2px;">
<span style="white-space:nowrap; float:left;">Number of Posts:</span>
<span style="float:right;"><b>'.$total_posts.'</b></span><br />
<span style="white-space:nowrap; float:left;">Archived Posts:</span>
<span style="float:right;"><b>'.$archived_posts.'</b></span><br />
<span style="white-space:nowrap; float:left;">Posts per day:</span>
<span style="float:right;"><b>'.$posts_per_day.'</b></span><br />
<span style="white-space:nowrap; float:left;">Number of Topics:</span>
<span style="float:right;"><b>'.$total_topics.'</b></span><br />
<span style="white-space:nowrap; float:left;">Archived Topics:</span>
<span style="float:right;"><b>'.$archived_topics.'</b></span><br />
<span style="white-space:nowrap; float:left;">Topics per day:</span>
<span style="float:right;"><b>'.$topics_per_day.'</b></span><br />
<span style="white-space:nowrap; float:left;">Days Board Open:</span>
<span style="float:right;"><b>'.$boarddays.'</b></span><br />
</div>';
