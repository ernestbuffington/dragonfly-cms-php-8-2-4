<?php
/*********************************************
  CPG-Dragonfly™ CMS
  ********************************************
  Copyright (c) 2004-2005 by CPG Dev Team
  http://www.dragonflycms.com

  CPG-Nuke is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/coppermine/search.inc,v $
  $Revision: 9.1 $
  $Author: akamu $
  $Date: 2005/12/03 21:10:42 $

***********************************************************************/

class Coppermine_search
{
    var $title;
    var $options;
    var $result = array();
    var $result_count = 0;
    var $link_next;
    var $link_prev;

    // class constructor
    function Coppermine_search() {
        global $db, $prefix;
        $cpg_dir = 'coppermine';
        $cpg_block = true;
        require_once("modules/" . $cpg_dir . "/include/load.inc");
        $cpg_block = false;

        define("_ALLINSTALS", "All Installs");
        define("_INSTALLATION", "Installation");
		define("_COPPERMINE", "Coppermine");

		$this->title = _COPPERMINE;
        $galls = $db->sql_query("SELECT dirname FROM ".$prefix."_cpg_installs ORDER BY cpg_id",false,__FILE__,__LINE__);
        $this->options = _INSTALLATION . ' <select name="coppermine">
        <option value="">'._ALLINSTALS.'</option>';
        while (list($gallery) = $db->sql_fetchrow($galls)) {
            $this->options .= "<option value=\"$gallery\">$gallery</option>\n";
            $sel = '';
        }
        $this->options .= '</select>';
    }

    function search($search, $url_search, $limit=10, $page=0) {
        global $db, $prefix, $multilingual, $currentlang, $CONFIG;
		global $cpg_block, $cpg_dir;

        $cpg_dir = 'coppermine';
        $cpg_block = true;
        require_once("modules/" . $cpg_dir . "/include/load.inc");
        $cpg_block = false;
		
        $first = $page*$limit;

        $ccnt = $db->sql_query("SELECT p.filename, p.title, p.caption, p.keywords, p.user1, p.user2, p.user3, p.user4, p.pid, p.aid FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE MATCH(filename, p.title, caption, keywords,user1,user2,user3,user4) AGAINST ('$search*' IN BOOLEAN MODE) AND approved='1' GROUP BY pid LIMIT $first,".($limit+1),false,__FILE__,__LINE__);
        $nrows = $db->sql_numrows($ccnt);

        while (list($filename, $title, $caption, $keywords, $user1, $user2, $user3, $user4, $pid, $aid) = $db->sql_fetchrow($ccnt)) {
		    if ($title != '') {
			    // do nothing.
			} else if ($caption != '') {
			    $title = $caption;
			} else if ($keywords != '') {
			    $title = $keywords;
			} else if ($user1 != '') {
			    $title = $user1;
			} else if ($user2 != '') {
			    $title = $user2;
			} else if ($user3 != '') {
			    $title = $user3;
			} else if ($user4 != '') {
				$title = $user4;
			} else if ($filename != '') {
				$title = $filename;
			} else {
			    $title = 'Click to view image';
			}
            $item['title'] = substr($title, 0, 64);
            $item['url']= getlink("$cpg_dir&amp;file=displayimage&amp;album=$aid&amp;pid=$pid");
			$item['short_text'] = substr(strip_tags($caption), 0, 256);
			$item['image'] = 'modules/'.$cpg_dir.'/images/slideshow.gif';
            $this->add_result($item);
		}

//        since we link to coppermine search for more, we should never link previous.
//        if ($page>0) {
//            $this->link_prev = '<a href="'.getlink('Search&amp;page='.($page-1)."&amp;topic=$topic&amp;cat=$cat&amp;search=$url_search&amp;news_search_comments=$search_comments&amp;mod=News").'"><strong>'._PREVMATCHES.' '._IN.' '.$this->title.'</strong></a>';
//        }
        if ($nrows > $limit) {
//            $this->link_next = '<a href="'.getlink('Search&amp;page='.($page+1)."&amp;topic=$topic&amp;cat=$cat&amp;search=$url_search&amp;news_search_comments=$search_comments&amp;mod=News").'"><strong>'._NEXTMATCHES.' '._IN.' '.$this->title.'</strong></a>';
              $this->link_next = '<a href="'.getlink("$cpg_dir&amp;file=thumbnails&amp;meta=search&amp;search=$url_search").'">See all search results in '._coppermineLANG.'</a>';
        }

    }

	function add_result($resultarray) {
		$this->result[$this->result_count++] = $resultarray;
	}
}
