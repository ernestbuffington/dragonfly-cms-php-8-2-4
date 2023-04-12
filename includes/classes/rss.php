<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/rss.php,v $
  $Revision: 1.7 $
  $Author: nanocaiordo $
  $Date: 2007/09/02 14:51:23 $
**********************************************/

class CPG_RSS {

	function read($url, $items_limit=10) {
$rss = [];
  /*
<language>en-us</language>
<pubDate>Sun, 23 Jan 2005 23:03:36 GMT</pubDate>
<docs>http://backend.userland.com/rss</docs>
*/
		$channeltags = array ('title', 'link', 'description', 'language',
			'generator', 'copyright', 'category', 'pubDate', 'managingEditor',
			'webMaster', 'lastBuildDate', 'rating', 'docs', 'ttl');
		$itemtags = array('title', 'link', 'description', 'author', 'category',
			'comments', 'enclosure', 'guid', 'pubDate', 'source');

		if (!($data = get_fileinfo($url, false, true))) { return false; }

		preg_match("#.*?encoding=[\'\"](.*?)[\'\"].*#si", $data['data'], $tag);
		$encoding = (isset($tag[1]) ? strtoupper($tag[1]) : 'ISO-8859-1');

		// Read CHANNEL info
		preg_match("'<channel.*?>(.*?)</channel>'si", $data['data'], $channel);
		// use IE work around for &apos;, thanks to darkgrue
		$channel = str_replace('&apos;', '&#039;', $channel[1]);
		foreach($channeltags as $channeltag) {
			$tag = CPG_RSS::_get_tag($channeltag, $channel, $encoding);
			if (!empty($tag)) { $rss[$channeltag] = $tag; }
		}
		$rss['title'] = strip_tags(urldecode($rss['title']));
		$rss['link'] = strip_tags($rss['link']);
		$rss['desc'] =& $rss['description'];
		if (isset($rss['ttl'])) {
			$rss['ttl'] = intval($rss['ttl']); // seconds
		}

		preg_match_all('#<item(| .*?)>(.*?)</item>#si', $data['data'], $items);
		$items = $items[2];
		for ($i=0;$i<$items_limit;$i++) {
			if (isset($items[$i]) && !empty($items[$i])) {
				$item = array();
				foreach($itemtags as $itemtag) {
					$tag = CPG_RSS::_get_tag($itemtag, $items[$i], $encoding);
					if (!empty($tag)) { $item[$itemtag] = $tag; }
				}
				if (!empty($item)) {
					$item['title'] = strip_tags(urldecode($item['title']));
					$item['link'] = isset($item['link']) ? strip_tags($item['link']) : '';
					$item['desc'] =& $item['description'];
					$rss['items'][] = $item;
				}
			}
		}
		return $rss;
	}
	
	function format($rss)
	{
		if (empty($rss)) return false;
		$items =& $rss['items'];
		$site_link =& $rss['link'];
		$data = '';
		for ($i=0;$i<(is_countable($items) ? count($items) : 0);$i++) {
			$link = $items[$i]['link'];
			$title2 = $items[$i]['title'];
			$data .= '<strong><big>&middot;</big></strong> <a href="'.$link.'" target="new">'.$title2.'</a><br/>'."\n";
		}
		if (!empty($site_link)) {
			$data .= '<br/><a href="'.$site_link.'" target="_blank"><b>'._HREADMORE.'</b></a>';
		}
		// The named character reference &apos; (the apostrophe, U+0027)
		// was introduced in XML 1.0 but does not appear in HTML. Authors
		// should therefore use &#39; instead of &apos; to work as expected
		// in HTML 4 user agents.
		return str_replace('&apos;', '&#039;', $data);
	}

	function _get_tag($tagname, &$string, $encoding) {
		Method::priv( __FILE__ , __CLASS__ , __FUNCTION__ );
		preg_match("#<$tagname.*?>(.*?)</$tagname>#si", $string, $tag);
		// if there is no result return empty
		if (!isset($tag[1])) { return false; }

		$tag = strtr($tag[1], array('<![CDATA['=>'', ']]>'=>''));
		if ($encoding != 'UTF-8') {
			// http://www.php.net/iconv
			$tag = function_exists('iconv') ? iconv($encoding, 'UTF-8', $tag) : mb_convert_encoding($tag, 'UTF-8', 'ISO-8859-1');
		}
		return trim($tag);
	}


}
