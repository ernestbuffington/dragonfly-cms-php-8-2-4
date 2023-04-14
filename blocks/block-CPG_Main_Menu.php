<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-CPG_Main_Menu.php,v $
  $Revision: 9.10 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 01:52:34 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $prefix, $db, $module_name, $language, $currentlang, $mainindex;
global $userinfo;

$menucats = array();
$content = $modquery = $lnkquery = '';
$setimage = 1;

if (!is_admin()) {
	$modquery = 'WHERE m.active=1 AND m.inmenu=1';
	$lnkquery = 'WHERE l.active=1';
	$view = array();
	$view[] = 0;
	if (is_user()) {
		$view[] = 1;
		foreach($userinfo['_mem_of_groups'] AS $key => $value) {
			$view[] = $key+3;
		}
	} else {
		$view[] = 3;
	}
	$modquery .= ' AND m.view IN ('.implode(',', $view).')';
	$lnkquery .= ' AND l.view IN ('.implode(',', $view).')';
}

// Load active modules from database
$result = $db->sql_query('SELECT m.title as link, m.custom_title as title, m.view, m.active, m.inmenu, m.cat_id, m.pos AS linkpos, c.name, c.image, c.pos AS catpos, c.link AS catlnk, c.link_type AS cattype FROM '.$prefix.'_modules AS m LEFT JOIN '.$prefix."_modules_cat c ON (c.cid = m.cat_id) $modquery ORDER BY m.pos");
while ($row = $db->sql_fetchrow($result)) {
	if ($row['title'] == '') {
		$row['title'] = (defined("_$row[link]LANG")) ? constant("_$row[link]LANG") : preg_replace('#_#m', ' ', $row['link']);
	}
	$row['link_type'] = -1;
	if (!isset($row['catpos'])) $row['catpos'] = -1;
	$menucats[$row['catpos']][$row['linkpos']] = $row;
}
// Load custom links from database
$result = $db->sql_query("SELECT l.title, l.link, l.link_type, l.view, l.active, l.cat_id, l.pos AS linkpos, c.name, c.image, c.pos AS catpos, c.link AS catlnk, c.link_type AS cattype FROM ".$prefix."_modules_links AS l LEFT JOIN ".$prefix."_modules_cat c ON (c.cid = l.cat_id) $lnkquery ORDER BY l.pos");
while ($row = $db->sql_fetchrow($result)) {
	if (defined($row['title'])) $row['title'] = constant($row['title']);
	$link = preg_replace('#&amp;#mi', '&', $row['link']);
	if (get_uri() != '') {
		if (preg_match('#' . preg_quote($link, '#') . '#m', get_uri())) { $row['lnkimage'] = 'icon_select.gif'; $setimage = 0; }
	}
	$row['link'] = preg_replace('#&#mi', '&amp;', $link);
	$row['catlnk'] = preg_replace('#&#mi', '&amp;', $row['catlnk']);
	$row['inmenu'] = 1;
	if (!isset($row['catpos'])) $row['catpos'] = -1;
	$menucats[$row['catpos']][$row['linkpos']] = $row;
}

ksort($menucats);
$nocatcontent = '';
foreach ($menucats as $cat => $items) {
    ksort($items);
    $catcontent = $offcontent = $hidcontent = $catimage = '';
    foreach ($items as $dummy => $item) {
        $image = 'icon_unselect.gif';
        if ($setimage && $item['link'] == $module_name) { $image = 'icon_select.gif'; $setimage = 0; }
        if (!$item['active']) $image = 'icon_cantselect.gif';
      		elseif ($item['active'] && !$item['inmenu']) $image = 'icon_hideselect.gif';
        $image = $item['lnkimage'] ?? $image;
        if ($item['link_type'] <= 0) {
      			$item['link'] = getlink($item['link']);
      		} elseif ($item['link_type'] == 2) {
      			$item['link'] .= '" target="_blank';
      		}
        $tmpcontent = '<img src="'.Menu::mmimage($image).'" alt="" title="" />&nbsp;<a href="'.$item['link'].'">'.$item['title']."</a><br />\n";
        if (!$item['active'] && !$item['inmenu']) $offcontent .= $tmpcontent;
      		elseif (!$item['active']) $hidcontent .= $tmpcontent;
      		else $catcontent .= $tmpcontent;
        $catimage = $item['image'];
        $cattitle = $item['name'];
        $catlnk	 = $item['catlnk'];
        $cattype = $item['cattype'];
    }
    $cattitle = '<strong>'.(defined($cattitle) ? constant($cattitle) : $cattitle).'</strong>';
    $catcontent .= $hidcontent.$offcontent;
    if (!empty($catlnk)) {
   		if ($cattype <= 0) {
   			$catlnk = getlink($catlnk);
   		} elseif ($cattype == 2) {
   			$catlnk .= '" target="_blank';
   		}
   		$cattitle = '<a href="'.$catlnk.'">'.$cattitle.'</a>';
   	}
    if ($cat >= 0) {
   		$content .= '<img src="'.Menu::mmimage($catimage).'" alt="" />&nbsp;'.$cattitle."<div style=\"margin-left: 8px;\">\n".$catcontent.'</div>';
   	} else {
   		$nocatcontent = "<hr />\n".$catcontent;
   	}
}

$content .= $nocatcontent;
