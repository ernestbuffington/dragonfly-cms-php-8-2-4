<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Search/index.php,v $
  $Revision: 9.8 $
  $Author: phoenix $
  $Date: 2007/08/28 01:33:34 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

$cpg_dir = 'coppermine'; // without this, we get redirected to $referer/file=install ??

$pagetitle .= _SEARCH;
require_once('header.php');
require_once('includes/nbbcode.php');

// Create an array of active modules with search.inc capabilities.
$modlist = array();
$handle = opendir('modules');
while ($file = readdir($handle)) {
	if (is_dir('modules/'.$file) && file_exists("modules/$file/search.inc") && is_active($file)) {
		list($name, $view) = $db->sql_ufetchrow("SELECT custom_title,view FROM ".$prefix."_modules WHERE title='".$file."'");
		if ($view == 0 || ($view == 1 && is_user()) || ($view == 3 && !is_user()) || can_admin() || ($view > 3 && in_group($view-3))) {
			include_once("modules/$file/search.inc");
			$sclass = $file.'_search';
			if (class_exists($sclass)) {
				$modlist[$file]['search_class'] = $sclass;
				$modlist[$file]['module'] = $file;
				$modlist[$file]['title'] = ($name != '') ? $name : $file;
			}
		}
	}
}
asort($modlist);

if (!isset($_POST['search']) && !isset($_GET['search'])) {
	$topicimage = 'AllTopics.gif';
	$topicimage = (file_exists("themes/$CPG_SESS[theme]/images/topics/$topicimage") ? "themes/$CPG_SESS[theme]/" : '')."images/topics/$topicimage";
	OpenTable();
	echo '<form action="'.getlink().'" method="post" enctype="multipart/form-data" accept-charset="utf-8">'
		.'<div><img src="'.$topicimage.'" style="float:right;" alt="" title="" />'
		.'<input size="25" type="text" name="search" value="" />&nbsp;&nbsp;'
		.'<input type="submit" value="'._SEARCH.'" /><br />';
	echo '<br /><strong>'._SEARCHONLYIN.'</strong><br /></div>';
	echo '<table border="0"><tr>';

	$i = 0;
	foreach ($modlist as $mod) {
		if ($i && ($i % 4 == 0)) {
			echo '</tr><tr>';
			echo "\n";
		}
		echo '<td><input type="checkbox" name="modules[]" value="'.$mod['module'].'" /></td>'.
			 '<td>'.$mod['title'].'</td>';

		$i++;
	}

	echo '</tr></table>';
	
	foreach ($modlist as $mod) {
		if (class_exists($mod['search_class'])) {
			$search = new $mod['search_class'];
			if ($search->options) { 
				echo '<hr /><div><strong>'._ADVOPTIONSFOR.' '.$mod['title'].':</strong><br />'. $search->options.'<br /></div>';
			}
		}
	}
	echo '</form>';
	CloseTable();
}
else {
	$page  = isset($_GET['page']) ? intval($_GET['page']) : 0;
	$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
	$query = isset($_POST['search']) ? $_POST['search'] : $_GET['search'];
	$sql_query = Fix_Quotes($query);
	$the_query = htmlprepare($query);
	$url_query = urlencode($query);

	$modules = array();
	if (isset($_POST['modules'])) {
		foreach ($_POST['modules'] as $mod) {
			if (isset($modlist[$mod])) $modules[$mod] = $modlist[$mod];
		}
	} else if (isset($_GET['mod'])) {
		if (isset($modlist[$_GET['mod']])) $modules[$_GET['mod']] = $modlist[$_GET['mod']];
	} else {
		$modules = $modlist;
	}

	OpenTable();
	echo '<div class="genmed">'._SEARCHRESULTS.': '.$the_query.'</div>';
	CloseTable();
	// process all searches
	$total_search_results = 0;
	if ($modules) {
		foreach ($modules as $mod) {
			if (class_exists($mod['search_class'])) {
				$search = new $mod['search_class'];
				$search->search($sql_query, $url_query, $limit, $page);
				if ($search->result_count > 0) {
					$total_search_results += $search->result_count;
					echo '<br />';
					OpenTable();
					echo '<span class="option">'._SEARCHRESULTS.' '._IN.' '.$search->title.':</span><br /><br />';
					foreach ($search->result as $result) {
						if (isset($result['results_header'])) echo $result['results_header'];
						
						if (isset($result['html']) && $result['html'] != '') {
							echo $result['html'];
						} else {
							// layout the search results based on data we are given...
							if (isset($result['header'])) echo $result['header'] . '<br />';

							echo '<img src="'. (isset($result['image']) ? $result['image'] : 'images/folders.gif') . '" alt="" />&nbsp;';
							echo '<a href="'.$result['url'].'" class="option"><strong>'.$result['title'].'</strong></a><br />';
							echo '<div style="padding-left:8px;">';

							if (isset($result['author'])) echo _CONTRIBUTEDBY.' '.$result['author'];
							if (isset($result['date'])) echo  ' '._ON.' '.FormatDateTime($result['date'], _DATESTRING3);
							if (isset($result['author']) || isset($result['date'])) echo '<br />';
							if (isset($result['short_text'])) echo $result['short_text'] . '...<br />';
							if (isset($result['footer'])) echo $result['footer'] . '<br />';

							echo '</div><br />';

							if (isset($result['results_footer'])) { echo $result['results_footer']; }
						}
					}
					if ($search->link_prev || $search->link_next) {
						echo '<div style="text-align:center;">'.
							 ($search->link_prev ? ' [ '.$search->link_prev.' ] ' : '').
							 ($search->link_next ? ' [ '.$search->link_next.' ] ' : '').
							 '</div>';
					}
					CloseTable();
				}
				unset($search);
			}
			unset($modlist[$mod['module']]);
		}
	}
	echo '<br />';
	
	if (!$total_search_results) {
		OpenTable();
		echo '<div style="text-align:center;" class="genmed"><strong>'._NOMATCHES.'</strong></div>';
		CloseTable();
		echo '<br />';
	}
	
	OpenTable();
	echo '<div class="genmed"><strong>'._DIDNOTFIND.'</strong></div><ul>';
	
	$mod = '';
	foreach ($modlist as $leftover) {
		if (class_exists($leftover['search_class'])) {
			$search = new $leftover['search_class'];
			$search->search($sql_query, $url_query, 64, 0);
			if ($search->result_count > 0) {
				echo '<li><a href="'.getlink('Search&amp;search='.$url_query.'&amp;mod='.$leftover['module']).'">'.$search->title.'</a> ('.$search->result_count.' '._SEARCHRESULTS.')</li>';
			}
			unset($search);
		}
	}
	if (is_active('Web_Links')) {
		$cnt = $db->sql_count($prefix.'_links_links', "title LIKE '%$sql_query%' OR description LIKE '%$sql_query%'");
		if ($cnt) echo '<li><a href="'.getlink('Web_Links&amp;l_op=search&amp;query='.$url_query).'">'._WEBLINKS.'</a> ('.$cnt.' '._SEARCHRESULTS.')</li>';
	}
	
	$url_query = urlencode($sitename.' '.$query);
	echo '<li><a href="http://www.google.com/search?q='.$url_query.'" target="new">Google</a></li>';

	$url_query = urlencode($query);
	echo '<li><a href="http://groups.google.com/groups?q='.$url_query.'" target="_blank">Google Groups</a></li>
	<li><a href="http://images.google.com/images?q='.$url_query.'" target="_blank">Google Images</a></li>
	<li><a href="http://news.google.com/news?q='.$url_query.'" target="_blank">Google News</a></li>
	<li><a href="http://froogle.google.com/froogle?q='.$url_query.'" target="_blank">Froogle</a></li>
	</ul>';
	CloseTable();
}
