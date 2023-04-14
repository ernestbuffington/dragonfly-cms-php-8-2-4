<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/rss/forums.php,v $
  $Revision: 9.6 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:38:13 $
***********************************************************************/
define('XMLFEED', 1);
$root_path = dirname(dirname(__FILE__));
if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
    $root_path = str_replace('\\', '/', $root_path); //Damn' windows
}
if (strlen($root_path) > 2) define('BASEDIR', $root_path.'/');
else define('BASEDIR', '../');

require_once(BASEDIR.'includes/cmsinit.inc');
require_once(BASEDIR.'includes/functions/language.php');
require_once(BASEDIR.'includes/nbbcode.php');

if (!is_active('Forums')) { die('Forums not active'); }

$forum = isset($_GET['f']) ? 't.forum_id='.intval($_GET['f']).' AND ' : '';

$result = $db->sql_query('SELECT
 t.topic_last_post_id, t.topic_title, f.forum_name, p.post_time, pt.post_text
 FROM '.$prefix.'_bbforums f, '.$prefix.'_bbtopics t
 LEFT JOIN '.$prefix.'_bbposts p ON (p.post_id = t.topic_last_post_id)
 LEFT JOIN '.$prefix.'_bbposts_text pt ON (pt.post_id = t.topic_last_post_id)
 WHERE '.$forum.' t.forum_id=f.forum_id AND f.auth_view=0
 ORDER BY t.topic_last_post_id DESC
 LIMIT 10');
//f.auth_view = 0); // everyone
//f.auth_view = 1); // member
//f.auth_view = 2); // private
//f.auth_view = 3); // moderator
//f.auth_view = 5); // admin


if ($row = $db->sql_fetchrow($result)) {
    $date = date('D, d M Y H:i:s \G\M\T', $row['post_time']);
    header("Date: $date");
} else {
    $date = date('D, d M Y H:i:s \G\M\T', gmtime());
}

$category = 'Forums'.(isset($_GET['f']) ? ' - '.$row['forum_name'] : '');

$BASEHREF = preg_replace('#\/\/rss.#m', '//', $BASEHREF);
header('Content-Type: text/xml'); // application/rss+xml
//  <ttl>60</ttl> a number of minutes that indicates how long a channel can be cached before refresh.
echo '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
  <title>'.htmlprepare($sitename).'</title>
  <link>'.$BASEHREF.'</link>
  <description>'.htmlprepare($backend_title).'</description>
  <language>'.$backend_language.'</language>
  <pubDate>'.$date.'</pubDate>
  <ttl>'.(60*24).'</ttl>
  <generator>CPG-Nuke Dragonfly</generator>
  <copyright>'.htmlprepare($sitename).'</copyright>
  <category>'.$category.'</category>
  <docs>http://backend.userland.com/rss</docs>
  <image>
    <url>'.$BASEHREF.'images/'.$MAIN_CFG['global']['site_logo'].'</url>
    <title>'.htmlprepare($sitename).'</title>
    <link>'.$BASEHREF."</link>
  </image>\n\n";
if ($row) {
    do {
        $forumname = isset($_GET['f']) ? '' : $row['forum_name'].': ';
        echo '<item>
  <title>'.$forumname.$row['topic_title'].'</title>
  <link>'.getlink("Forums&amp;file=viewtopic&amp;p=$row[topic_last_post_id]#$row[topic_last_post_id]", true, true).'</link>
  <description>'.htmlprepare(decode_bbcode(set_smilies($row['post_text']),1), false, ENT_QUOTES, true).'</description>
  <pubDate>'.date('D, d M Y H:i:s \G\M\T', $row['post_time'])."</pubDate>
</item>\n\n";
    }
    while ($row = $db->sql_fetchrow($result));
}
?>
</channel>
</rss>
