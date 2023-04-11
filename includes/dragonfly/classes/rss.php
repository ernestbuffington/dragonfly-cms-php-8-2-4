<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly;

abstract class RSS
{

	public static function read($url, $items_limit=10)
	{
		$channeltags = array ('title', 'link', 'description', 'language',
			'generator', 'copyright', 'category', 'pubDate', 'managingEditor',
			'webMaster', 'lastBuildDate', 'rating', 'docs', 'ttl');
		$itemtags = array('title', 'link', 'description', 'author', 'category',
			'comments', 'enclosure', 'guid', 'pubDate', 'source');

		if (!($data = \Poodle\HTTP\URLInfo::get($url, false, true))) { return false; }

		preg_match("#.*?encoding=[\'\"](.*?)[\'\"].*#si", $data['data'], $tag);
		$encoding = (isset($tag[1]) ? strtoupper($tag[1]) : 'UTF-8');

		// Read CHANNEL info
		preg_match("'<channel.*?>(.*?)</channel>'si", $data['data'], $channel);
		if (!isset($channel[1])) { preg_match("'<feed.*?>(.*?)</feed>'si", $data['data'], $channel); }
		$channel = str_replace('&apos;', '&#039;', $channel[1]);
		foreach($channeltags as $channeltag) {
			$tag = static::get_tag($channeltag, $channel, $encoding);
			if (!empty($tag)) { $rss[$channeltag] = $tag; }
		}
		$rss['title'] = strip_tags(urldecode($rss['title']));
		$rss['link'] = strip_tags($rss['link']);
		$rss['desc'] =& $rss['description'];

		if (isset($rss['ttl'])) {
			$rss['ttl'] = intval($rss['ttl']); // seconds
		}

		if (preg_match_all('#<item(| .*?)>(.*?)</item>#si', $data['data'], $items)) {
			$items = $items[2];
		} else {
			preg_match_all('#<entry>(.*?)</entry>#si', $data['data'], $items);
			$items = $items[1];
		}
		for ($i=0;$i<$items_limit;$i++) {
			if (isset($items[$i]) && !empty($items[$i])) {
				$item = array();
				foreach($itemtags as $itemtag) {
					$tag = static::get_tag($itemtag, $items[$i], $encoding);
					if (!empty($tag)) { $item[$itemtag] = $tag; }
				}
				if (!empty($item)) {
					$item['title'] = preg_replace('#^Revision [0-9a-f]+\:\s(.*)#', '$1', $item['title']);
					$item['title'] = strip_tags(urldecode($item['title']));
					$item['link']  = isset($item['link']) ? strip_tags($item['link']) : '';
					$item['desc']  =& $item['description'];
					$rss['items'][] = $item;
				}
			}
		}
		return $rss;
	}

	public static function display($url, $items_limit=10)
	{
		return static::format(static::read($url, $items_limit));
	}

	public static function format($rss)
	{
		if (empty($rss)) return false;
		$data = '';
		foreach ($rss['items'] as $item) {
			$data .= '<strong>·</strong> <a href="'.htmlspecialchars($item['link']).'" target="_blank">'
				.htmlspecialchars($item['title'],ENT_NOQUOTES,'UTF-8',false)
				.'</a><br/>'."\n";
		}
		if (!empty($rss['link'])) {
			$data .= '<br/><a href="'.htmlspecialchars($rss['link']).'" target="_blank"><b>_HREADMORE</b></a>';
		}
		// The named character reference &apos; (the apostrophe, U+0027)
		// was introduced in XML 1.0 but does not appear in HTML. Authors
		// should therefore use &#39; instead of &apos; to work as expected
		// in HTML 4 user agents.
		return str_replace('&apos;', '&#39;', $data);
	}

	private static function get_tag($tagname, &$string, $encoding)
	{
		preg_match("#<{$tagname}.*?>(.*?)</{$tagname}>#si", $string, $tag);
		if (empty($tag[1])) preg_match("#<{$tagname}.*?href=\"(.*?)\"\s*/>#si", $string, $tag);
		// if there is no result return empty
		if (!isset($tag[1])) { return false; }

		$tag = strtr($tag[1], array('<![CDATA['=>'', ']]>'=>''));
		if ($encoding != 'UTF-8') {
			// http://www.php.net/iconv
			$tag = function_exists('iconv') ? iconv($encoding, 'UTF-8', $tag) : utf8_encode($tag);
		}
		return trim($tag);
	}

}
