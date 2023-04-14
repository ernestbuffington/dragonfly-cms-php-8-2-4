<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/cssmainmenu.php,v $
  $Revision: 1.7 $
  $Author: phoenix $
  $Date: 2007/06/28 15:25:39 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
global $prefix, $db, $module_name, $language, $currentlang, $mainindex, $userinfo, $adminindex, $MAIN_CFG;
if (!($MAIN_CFG['global']['admingraphic'] & 4)) return '';

$menucats = array();
$modquery = $lnkquery = '';
$setstatus = 1;

if (!is_admin())
{
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
$sql = 'SELECT m.title as link, m.custom_title as title, m.view, m.active, m.inmenu, m.cat_id, m.pos AS linkpos, c.name, c.image, c.pos AS catpos, c.link AS catlnk, c.link_type AS cattype FROM '.$prefix.'_modules AS m LEFT JOIN '.$prefix."_modules_cat c ON (c.cid = m.cat_id) $modquery";
$result = $db->sql_query($sql);
while ($row = $db->sql_fetchrow($result))
{
    if ($row['title'] == '') {
        $row['title'] = (defined("_$row[link]LANG")) ? constant("_$row[link]LANG") : preg_replace('#_#m', ' ', $row['link']);
    }
    $row['link_type'] = -1;
    if (!isset($row['catpos'])) {
		$row['catpos'] = -1;
	}
    $menucats[$row['catpos']][$row['linkpos']] = $row;
}
$db->sql_freeresult($result);

// Load custom links from database
$sql = "SELECT l.title, l.link, l.link_type, l.view, l.active, l.cat_id, l.pos AS linkpos, c.name, c.image, c.pos AS catpos, c.link AS catlnk, c.link_type AS cattype FROM ".$prefix."_modules_links AS l LEFT JOIN ".$prefix."_modules_cat c ON (c.cid = l.cat_id) $lnkquery";
$result = $db->sql_query($sql);
while ($row = $db->sql_fetchrow($result))
{
    if (defined($row['title'])) {
		$row['title'] = constant($row['title']);
	}
    $link = preg_replace('#&amp;#mi', '&', $row['link']);
    if (get_uri() != '') {
        if (preg_match('#' . preg_quote($link, '#') . '#m', get_uri())) {
			$row['status'] = '<span class="sgreen">&#8226;</span>';
			$setstatus = 0;
		}
    }
    $row['link'] = preg_replace('#&#mi', '&amp;', $link);
    $row['catlnk'] = preg_replace('#&#mi', '&amp;', $row['catlnk']);
    $row['inmenu'] = 1;
    if (!isset($row['catpos'])) {
		$row['catpos'] = -1;
	}
    $menucats[$row['catpos']][$row['linkpos']] = $row;
}
$db->sql_freeresult($result);

$nocatcontent = '';
$content = "\n".'<ul id="menuList">';
if (defined('ADMIN_PAGES'))
{
	$content .= '<li><a href="'.$mainindex.'"><b>&raquo;</b> '._HOME.' <b>&laquo;</b></a>'."\n <ul>";
}
elseif (!defined('ADMIN_PAGES') && is_admin())
{
	global $CLASS;
	require_once(CORE_PATH.'classes/cpg_adminmenu.php');
	$content .= '<li><a href="'.$adminindex.'"><b>&raquo;</b> '._ADMIN.' <b>&laquo;</b></a>';
	$content .= $CLASS['adminmenu']->display('all', 'cssmenu');
	$content .='</li>';
}

ksort($menucats);
foreach ($menucats as $ccat => $items) {
    ksort($items);
    $catcontent = $offcontent = $hidcontent = '';
    foreach ($items as $dummy => $item) {
        $status = '<span class="sblack">&#8226;</span>';
        if ($setstatus && $item['link'] == $module_name) {
			$status = '<span class="sgreen">&#8226;</span>';
			$setstatus = 0;
		}
        if (!$item['active']) {
			$status = '<span class="sred">&#8226;</span>';
		} elseif ($item['active'] && !$item['inmenu']) {
			$status = '<span class="sgray">&#8226;</span>';
		}
        $status = $item['status'] ?? $status;
        if ($item['link_type'] <= 0) {
            $item['link'] = getlink($item['link']);
        } elseif ($item['link_type'] == 2) {
            $item['link'] .= '" target="_blank';
        }
        $tmpcontent = '<li>'.$status.'<a href="'.$item['link'].'">'.$item['title'].'</a></li>';
        if (!$item['active'] && !$item['inmenu']) {
			$offcontent .= $tmpcontent;
		} elseif (!$item['active']) {
			$hidcontent .= $tmpcontent;
		} else {
			$catcontent .= $tmpcontent;
		}
        $cattitle = $item['name'];
        $catlnk  = $item['catlnk'];
        $cattype = $item['cattype'];
    }
    $cattitle = (defined($cattitle) ? constant($cattitle) : $cattitle);
    $catcontent .= $hidcontent.$offcontent;
    if (!empty($catlnk)) {
        if ($cattype <= 0) {
            $catlnk = getlink($catlnk);
        } elseif ($cattype == 2) {
            $catlnk .= '" target="_blank';
        }
        $cattitlel = defined('ADMIN_PAGES') ? '<a href="'.$catlnk.'">'.$cattitle.'</a>' : '<a href="'.$catlnk.'"><b>&#8226;</b> '.$cattitle.'</a>';
    } else {
        $cattitlel = defined('ADMIN_PAGES') ? '<a>'.$cattitle.'</a>': '<a><b>&#8226;</b> '.$cattitle.'</a>';
    }
    if ($ccat >= 0) {
        $content .= "\n ".'<li>'.(defined('ADMIN_PAGES') ? '<span>&raquo;</span>': '').$cattitlel."\n <ul>".$catcontent;
		$content .= "\n </ul></li>";
    } else {
		$nocatcontent = "\n ".'<li>'.(defined('ADMIN_PAGES') ? '<span>&raquo;</span><a>None</a>' : '<a><b>&#8226;</b> None</a>')."\n <ul>".$catcontent;
		$nocatcontent .= "\n </ul></li>";
    }
}
$content .= $nocatcontent;

if (defined('ADMIN_PAGES') && is_admin())
{
	$content .= "\n</ul></li>\n";
	global $CLASS;
	require_once(CORE_PATH.'classes/cpg_adminmenu.php');
	$content .= $CLASS['adminmenu']->display($ccat, 'cssmenu');
}

$content .= "\n</ul>\n";
$mmcontent = $content;
