<?php
/*********************************************
  CPG-Dragonfly™ CMS
  ********************************************
  Copyright (c) 2004-2005 by CPG Dev Team
  http://www.dragonflycms.com

  CPG-Nuke is released under the terms and conditions
  of the GNU GPL version 2 or any later version
***********************************************************************/

define('_ALLINSTALS', 'All Installs');
define('_INSTALLATION', 'Installation');
define('_COPPERMINE', 'Coppermine');

class Coppermine_search
{
	public
		$title,
		$options,
		$result = array(),
		$result_count = 0,
		$link_next,
		$link_prev;

	public function __construct()
	{
		$this->title = _COPPERMINE;
/*
		// List albums/categories?
		global $db;
		$cpg_dir = basename(__DIR__);
		$CONFIG = \Coppermine::getConfigInstance($cpg_dir);
		$galls = $db->query("SELECT dirname FROM {$db->TBL->cpg_installs} ORDER BY cpg_id");
		$this->options = _INSTALLATION . ' <select name="coppermine">
		<option value="">'._ALLINSTALS.'</option>';
		while (list($gallery) = $galls->fetch_row()) {
			$this->options .= "<option value=\"{$gallery}\">{$gallery}</option>\n";
		}
		$this->options .= '</select>';
*/
	}

	public function search($search, $url_search, $limit=10, $page=0)
	{
		global $db;
		$cpg_dir = basename(__DIR__);
		$CONFIG = \Coppermine::getConfigInstance($cpg_dir);
		$USER_DATA = \Coppermine::getCurrentUserData();
		$vis_groups = can_admin($cpg_dir) ? '' : "WHERE visibility IN (0,{$USER_DATA['GROUPS']})";

		$first = $page * $limit;

		$ccnt = $db->query("SELECT
			p.filename, p.title, p.caption, p.keywords, p.user1, p.user2, p.user3, p.user4, p.pid, p.aid
		FROM {$CONFIG['TABLE_PICTURES']} AS p
		WHERE approved = 1
		  AND p.aid IN (SELECT aid FROM {$CONFIG['TABLE_ALBUMS']} {$vis_groups})
		  AND MATCH (filename, p.title, caption, keywords, user1, user2, user3, user4)
		   AGAINST ('{$search}*' IN BOOLEAN MODE)
		LIMIT ".($limit+1)." OFFSET {$first}");

		while (list($filename, $title, $caption, $keywords, $user1, $user2, $user3, $user4, $pid, $aid) = $ccnt->fetch_row()) {
			if ($title) {
				// do nothing.
			} else if ($caption) {
				$title = $caption;
			} else if ($keywords) {
				$title = $keywords;
			} else if ($user1) {
				$title = $user1;
			} else if ($user2) {
				$title = $user2;
			} else if ($user3) {
				$title = $user3;
			} else if ($user4) {
				$title = $user4;
			} else if ($filename) {
				$title = $filename;
			} else {
				$title = 'Click to view image';
			}
			$item['title'] = substr($title, 0, 64);
			$item['url']= URL::index("{$cpg_dir}&file=displayimage&pid={$pid}");
			$item['short_text'] = substr(strip_tags($caption), 0, 256);
			$this->add_result($item);
		}

		// since we link to coppermine search for more, we should never link previous.
//		if ($page>0) {
//			$this->link_prev = '<a href="'.URL::index('Search&page='.($page-1)."&topic=$topic&cat=$cat&search=$url_search&news_search_comments=$search_comments&mod=News").'"><strong>'._PREVMATCHES.' '._IN.' '.$this->title.'</strong></a>';
//		}
		if ($ccnt->num_rows > $limit) {
//			$this->link_next = '<a href="'.URL::index('Search&page='.($page+1)."&topic=$topic&cat=$cat&search=$url_search&news_search_comments=$search_comments&mod=News").'"><strong>'._NEXTMATCHES.' '._IN.' '.$this->title.'</strong></a>';
			$this->link_next = '<a href="'.htmlspecialchars(URL::index("{$cpg_dir}&file=thumbnails&meta=search&search={$url_search}")).'">See all search results in '._coppermineLANG.'</a>';
		}
	}

	public function add_result($resultarray)
	{
		$this->result[$this->result_count++] = $resultarray;
	}

}
