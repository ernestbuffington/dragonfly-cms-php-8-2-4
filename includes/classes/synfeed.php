<?php
/*********************************************
  CPG DragonflyCMS, Copyright (c) 2011 by DragonflyCMS Dev Team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/

class SynFeed {

	public static $description;
	public static $link;
	public static $title;

	public static $category;
	public static $copyright;
	public static $language;
	public static $lastUpdated;
	public static $managingEditor;
	public static $pubDate;
	public static $rating;
	public static $webMaster;
	public static $version;

	public static $items = array();

	public static function template()
	{
		if ($status = \Dragonfly\Net\Http::entityCache(md5(self::$pubDate), self::$pubDate)) {
			\Dragonfly\Net\Http::headersFlush($status);
			exit;
		}
		global $MAIN_CFG, $cpgtpl;
		self::$title = strip_tags(self::$title ?: $MAIN_CFG['global']['sitename']);
		if (ctype_digit((string)self::$pubDate)) {
			self::$pubDate = date('D, d M Y H:i:s \G\M\T', self::$pubDate);
			\Dragonfly\Net\Http::headersPush('Date: '.self::$pubDate);
		} else {
			self::$pubDate = date('D, d M Y H:i:s \G\M\T', time());
		}
		$category = strip_tags(self::$category);

		$cpgtpl->assign_vars(array(
			'S_DESCRIPTION'    => strip_tags(self::$description ?: $MAIN_CFG['global']['backend_title']),
			'S_LINK'           => Filter::domain(self::$link) ? self::$link : BASEHREF,
			'S_TITLE'          => self::$title,

			'S_CATEGORY'       => $category,
			'S_COPYRIGHT'      => self::$copyright ? strip_tags(self::$copyright) : self::$title,
			'S_DOCS'           => 'http://cyber.law.harvard.edu/rss/rss.html',
			'S_IMG'            => BASEHREF.'images/'.$MAIN_CFG['global']['site_logo'],
			'S_GENERATOR'      => 'DragonflyCMS v10',
			'S_LANGUAGE'       => strip_tags(self::$language ?: $MAIN_CFG['global']['backend_language']),
			'S_LASTBUILDDATE'  => self::$pubDate,
			'S_MANAGINGEDITOR' => self::$managingEditor ? strip_tags(self::$managingEditor) : false,
			'S_PUBDATE'        => self::$pubDate,
			'S_TTL'            => 60*24,
			'S_VERSION'        => self::$version && preg_match('#^[0-2]\.[0-9]$#', self::$version) ? self::$version : '2.0',
			'S_WEBMASTER'      => self::$webMaster ? strip_tags(self::$webMaster) : false,
			'S_ATOM_LINK'      => BASEHREF.'?feed='.$category,
		));
		$cpgtpl->items = array();
		$def_item = array(
			'title'       => false, // required
			'link'        => false, // required
			'description' => false, // required
			'guid'        => false,
			'author'      => false,
			'category'    => false,
			'comments'    => false,
			'enclosure'   => false,
			'pubDate'     => false,
			'quid'        => false,
			'source'      => false
		);
		foreach (self::$items as $item) {
			if (empty($item['title']) || Filter::domain($item['link']) || empty($item['description'])) { continue; }
			$item['title'] = strip_tags($item['title']);
			$item['description'] = \Dragonfly\BBCode::decodeAll($item['description'], 1, true);
			$item['description'] = preg_replace('/(href|src)\=\"(?![hftp]{3,4}[^\"])/i', '$1="'.BASEHREF.'$2', $item['description']);
			$item['description'] = htmlspecialchars(preg_replace('#Revision [a-f0-9]+: (.*)#', '\1', strip_tags($item['description'])));
			if (empty($item['pubDate']) && !empty($item['time'])) {
				$item['pubDate'] = gmdate('D, d M Y H:i:s \G\M\T', intval($item['time']));
			}
			$cpgtpl->items[] = array_merge($def_item, $item);
		}
		$cpgtpl->display('feed_rss');
	}
}
