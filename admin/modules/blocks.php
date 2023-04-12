<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/blocks.php,v $
  $Revision: 9.41 $
  $Author: nanocaiordo $
  $Date: 2007/10/03 13:51:39 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }
$pagetitle .= ' '._BC_DELIM.' '._BLOCKSADMIN;
require_once(CORE_PATH.'nbbcode.php');
if (isset($_GET['change'])) {
	$bid = intval($_GET['change']);
	list($active) = $db->sql_ufetchrow('SELECT active FROM '.$prefix."_blocks WHERE bid=$bid",SQL_NUM);
	if (is_numeric($active)) {
		$active = intval(!$active);
		$result = $db->sql_query('UPDATE '.$prefix.'_blocks SET active=\''.$active.'\' WHERE bid='.$bid);
		Cache::array_delete('blocks_list');
	}
	url_redirect(adminlink('blocks'));
} else if (isset($_GET['show'])) {
	block_show(intval($_GET['show']));
} else if (isset($_GET['edit'])) {
	BlocksEdit(intval($_GET['edit']));
} else if (isset($_GET['del'])) {
	$bid = intval($_GET['del']);
	list($bposition, $weight, $title) = $db->sql_ufetchrow('SELECT bposition, weight, title from '.$prefix.'_blocks where bid='.$bid,SQL_NUM);
	if (!isset($bposition) || isset($_POST['cancel'])) { url_redirect(adminlink()); }
	if (isset($_POST['confirm'])) {
		$db->sql_uquery('UPDATE '.$prefix.'_blocks SET weight=weight-1 WHERE bposition=\''.$bposition.'\' AND weight>'.$weight);
		$db->sql_uquery('DELETE FROM '.$prefix.'_blocks WHERE bid='.$bid);
		$db->sql_uquery('DELETE FROM '.$prefix.'_blocks_custom WHERE bid='.$bid);
		Cache::array_delete('blocks_list');
		url_redirect(adminlink());
	}
	cpg_delete_msg(adminlink('&amp;del='.$bid), sprintf(_ERROR_DELETE_CONF, '<strong>'.(defined($title) ? constant($title) : $title).'</strong>'));
} else if (isset($_GET['save'])) {
	BlocksEditSave(intval($_GET['save']));
} else {
	if (isset($_POST['add']) && !BlocksAdd()) {
		rssfail();
		return;
	}
	if (Security::check_post() && isset($_POST['updateblocks'])) {
		$sides = array('l','c','r','d','n');
		$count = is_countable($_POST['id']) ? count($_POST['id']) : 0;
		$blocks = blocks_list();
		for ($i=0; $i<$count; ++$i) {
			if (!intval($_POST['id'][$i])) continue;
			if ($_POST['id'][$i] < 0 ) {
				$side = $_POST['side'][$i];
				$pos=1;
			} else {
				$bid = intval($_POST['id'][$i]);
				$mid = intval($_POST['mid'][$i]);
				$module = $db->sql_escape_string($_POST['module'][$i]);
				if ($side == 'n') {
					$db->query('DELETE FROM '.$prefix."_blocks_custom WHERE bid=$bid AND mid=$mid");
				} else if (empty($blocks[$module][$bid])) {
					$db->query('INSERT INTO '.$prefix."_blocks_custom (bid, mid, side, weight) VALUES ($bid, $mid, '$side', $pos)", true);
				} else if ($_POST['weight'][$i] != $pos || $_POST['side'][$i] != $side && isset($blocks[$module][$bid])) {
					$db->query('UPDATE '.$prefix."_blocks_custom SET weight=$pos, side='$side' WHERE bid=$bid AND mid=$mid", true);
				}
				++$pos;
			}
		}
		Cache::array_delete('blocks_list');
		url_redirect(adminlink('blocks'));
	}
	BlocksAdmin();
}

function BlocksAdmin()
{
	$all = [];
 $headlines = [];
 $matches = null;
 $blockslist = [];
 $selblocks = [];
 global $bgcolor2, $bgcolor3, $prefix, $db, $currentlang, $multilingual, $cpgtpl, $modheader, $ThemeSel;
	$blocks_theme = 'default';
	# will be removed the next release
	if ($ThemeSel != ' default'
		&& file_exists('themes/'.$ThemeSel.'/style/adminblocks.css')
		&& file_exists('themes/'.$ThemeSel.'/javascript/adminblocks.js')
		&& file_exists('themes/'.$ThemeSel.'/images/drag.png'))
	{
			$blocks_theme = $ThemeSel;
	}
	$modheader .= '
<script type="text/javascript" src="includes/javascript/framework.js"></script>
<script type="text/javascript" src="themes/'.$blocks_theme.'/javascript/adminblocks.js"></script>
<link rel="stylesheet" href="themes/'.$blocks_theme.'/style/tabletree.css" type="text/css" media="screen" />
<link rel="stylesheet" href="themes/'.$blocks_theme.'/style/adminblocks.css" type="text/css" media="screen" />';
	require('header.php');
	GraphicAdmin('_AMENU1');

	$cpgtpl->assign_vars(array(
		'B_MULTILINGUAL' => $multilingual,
		'L_TITLE' => _TITLE,
		'L_POSITION' => _POSITION,
		'L_WEIGHT' => _WEIGHT,
		'L_ACTIVE' => _ACTIVE,
		'L_INACTIVE' => _INACTIVE,
		'L_LANGUAGE' => _LANGUAGE,
		'L_VIEW' => _VIEW,
		'L_FUNCTIONS' => _FUNCTIONS,
		'L_SHOW' => _SHOW,
		'L_EDIT' => _EDIT,
		'L_TYPE' => _TYPE,
		'L_DELETE' => _DELETE,
		'L_LEFT' => _LEFT,
		'L_RIGHT' => _RIGHT,
		'L_CENTERUP' => _CENTERUP,
		'L_CENTERDOWN' => _CENTERDOWN,
		#'S_BLOCKUP' => _BLOCKUP,
		#'S_BLOCKTOP' => _BLOCKTOP,
		#'S_BLOCKDOWN' => _BLOCKDOWN,
		#'S_BLOCKBOTTOM' => _BLOCKBOTTOM,
		'L_RSSFILE' => _RSSFILE,
		'S_BGCOLOR2' => $bgcolor2,
		// Add new block
		'S_BB' => bbcode_table('content', 'addblock', 1),
		'L_NO' => _NO,
		'L_YES' => _YES,
		'L_HOUR' => _HOUR,
		'L_HOURS' => _HOURS,
		'L_CONTENT' => _CONTENT,
		'L_VIEWPRIV' => _VIEWPRIV,
		'L_LANGUAGE' => _LANGUAGE,
		'L_FILENAME' => _FILENAME,
		'L_ACTIVATE2' => _ACTIVATE2,
		'L_ADDNEWBLOCK' => _ADDNEWBLOCK,
		'L_CREATEBLOCK' => _CREATEBLOCK,
		'L_REFRESHTIME' => _REFRESHTIME,
		'L_FILEINCLUDE' => _FILEINCLUDE,
		#'S_IFRSSWARNING' => _IFRSSWARNING,
		#'S_ONLYHEADLINES' => _ONLYHEADLINES,
		'L_SETUPHEADLINES' => _SETUPHEADLINES,
		'U_BLOCKS' => adminlink('blocks'),
		'U_HEADLINES' => adminlink('headlines'),
		'SEL_GROUP' => group_selectbox('view', 0, true),
		'L_VISIBLEINMODULES' => _VISIBLEINMODULES,
		#'S_VIEW_COMMENT' => 'Default: '._ALL,
		'L_AUTHORSADMIN' => _AUTHORSADMIN,
	));
#
# $blocks and $all arrays sharing the same query
#
	$result = $db->sql_query('SELECT bid, bkey, title, url, bposition, weight, active, blanguage, blockfile, view FROM '.$prefix.'_blocks ORDER BY weight');
	$blocks = array();
	while($row = $db->sql_fetchrow($result, SQL_ASSOC)) {
		$blocks[$row['bid']] = $row;
		if (defined($row['title'])) $row['title'] = constant($row['title']);
		$all[$row['title']] = $row;
	}
	$bgcolor = $bgcolor3;
	$visblocks = array();
#
# javascript blocks table
#
	$blocks_list = blocks_list();
	foreach ($blocks_list as $title => $module) {
		$sides = array('l'=> 0, 'c'=>0, 'd'=>0, 'r'=>0);
		$cpgtpl->assign_block_vars('modules', array(
			'S_MODULE_TITLE' => defined($module['title']) ? constant($module['title']) : $title,
			'S_MODULE_OTITLE' => $title,
			'S_MODULE_ID' => $module['mid'],
			'L_MODULE_ACTIVE' => (is_active($title) || $module['mid'] == -1) ? _YES : _NO,
			'L_MODULE_SIDE' => _BLOCKS.': '.(($module['blocks']==0)?_NONE : (($module['blocks']==1)?_LEFT : (($module['blocks']==2)?_RIGHT : _BOTH)))
		));
		foreach ($module as $bid => $side) {
		if (!intval($bid)) continue;
			$bgcolor = ($bgcolor == '') ? $bgcolor3 : '';
			$cpgtpl->assign_block_vars('modules.loop_'.$side, array(
				'BID' => $bid,
				'S_WEIGHT' => ++$sides[$side],
				'S_TITLE' => (defined($blocks[$bid]['title']) ? constant($blocks[$bid]['title']) : $blocks[$bid]['title'])
			));
		}
#
# add new block
#
		if ($module['mid'] == -1) continue;
		$cpgtpl->assign_block_vars((is_active($title)) ? 'active' : 'inactive' , array(
			'S_MOD_VALUE' => $module['mid'],
			'S_MOD_TITLE' => defined($module['title']) ? constant($module['title']) : $module['title'],
		));
	}
	$blocks = NULL;
#
# static blocks table
#
	ksort($all);
	foreach ($all as $block) {
		$bgcolor = ($bgcolor == '') ? $bgcolor3 : '';
		$visblocks[$block['blockfile']] = true;
		$bkey = false;

		if ($block['bkey'] == 'admin' || $block['bkey'] == 'userbox') {
			$type = _BLOCKSYSTEM;
		} else {
			$bkey = true;
			if ($block['bkey'] == 'custom') { $type = 'Custom'; }
			elseif ($block['bkey'] == 'rss') { $type = 'RSS/RDF'; }
			elseif ($block['bkey'] == 'file') { $type = _BLOCKFILE2; }
		}
		if ($block['active']) {
			$active = 'checked.gif';
			$change = _DEACTIVATE;
		} else {
			$active = 'unchecked.gif';
			$change = _ACTIVATE;
		}
		if ($block['view'] == 0) {
			$who_view = _MVALL;
		} elseif ($block['view'] == 1) {
			$who_view = _MVUSERS;
		} elseif ($block['view'] == 2) {
			$who_view = _MVADMIN;
		} elseif ($block['view'] == 3) {
			$who_view = _MVANON;
		} elseif ($block['view'] >3) {		 // <= phpBB User Groups Integration
			list($who_view) = $db->sql_ufetchrow('SELECT group_name FROM '.$prefix.'_bbgroups WHERE group_id='.($block['view'] - 3), SQL_NUM);
		}

		$cpgtpl->assign_block_vars('list', array(
			'S_BID' => $block['bid'],
			'S_BKEY' => $bkey,
			'S_LAST' => !next($all),
			'S_WEIGHT' => $block['weight'],
			'S_BGCOLOR' => $bgcolor,
			'S_TITLE' => $block['title'],
			'S_TYPE' => $type,
			'L_CHANGE' => $change,
			'L_WHO_VIEW' => $who_view,
			'L_BLANGUAGE' => (($block['blanguage'] == '') ? _ALL : ucfirst($block['blanguage'])),
			'S_IMG_ACTIVE' => $active
		));
	}

	$headlines[0] = _CUSTOM;
	$res = $db->sql_query("select hid, sitename from ".$prefix."_headlines");
	while (list($hid, $htitle) = $db->sql_fetchrow($res)) {
		$headlines[$hid] = $htitle;
	}
	$blocksdir = dir('blocks');
	while($func=$blocksdir->read()) {
	   if(preg_match('#block\-(.*).php$#m', $func, $matches)) {
			$blockslist[] = $func;
		}
	}
	closedir($blocksdir->handle);
	sort($blockslist);
	for ($i=0; $i < sizeof($blockslist); $i++) {
		if (!empty($blockslist["$i"]) && !isset($visblocks[$blockslist["$i"]])) {
			$bl = preg_replace('#_#m',' ',(preg_replace('#(block\-)|(.php)#m','',$blockslist["$i"])));
			$selblocks[$blockslist["$i"]]=$bl;
		}
	}
	$selblocks['']= _NONE;
	$cpgtpl->assign_vars(array(
		'SEL_HEADLINES' => select_box('headline', 0, $headlines),
		'SEL_BLOCKS' => select_box('blockfile', '', $selblocks),
		'SEL_LANG' => lang_selectbox($currentlang, 'blanguage')
	));

	$cpgtpl->set_handle('body', 'admin/blocks.html');
	$cpgtpl->display('body');
}

function block_show($bid)
{
	global $prefix, $db, $Blocks;
	$result = $db->sql_query("SELECT bid, bkey, title, content, url, bposition, blockfile, view, refresh, time FROM ".$prefix."_blocks WHERE bid='".$bid."'");
	$row = $db->sql_fetchrow($result, SQL_ASSOC);
	$Blocks->preview = TRUE;
	require('header.php');
	GraphicAdmin('_AMENU1');
	OpenTable();
	$Blocks->preview($row);
	echo '<div align="center" class="option">'._BLOCKSADMIN.': '._FUNCTIONS.'</div><br /><br />'
	.'[ <a href="'.adminlink('blocks&amp;change='.$bid).'">'._ACTIVATE.'</a> | <a href="'.adminlink('blocks&amp;edit='.$bid).'">'._EDIT.'</a> | ';
	if (empty($row['bkey'])) {
		echo '<a href="'.adminlink('blocks&amp;del='.$bid).'">'._DELETE.'</a> | ';
	}
	echo '<a href="'.adminlink('blocks').'">'._BLOCKSADMIN.'</a> ]';
	CloseTable();
}

function rssfail()
{
	$cpgtpl = null;
 require('header.php');
	GraphicAdmin('_AMENU1');
	$cpgtpl->assign_vars(array(
		'S_RSSFAIL' => _RSSFAIL,
		'S_RSSTRYAGAIN' => _RSSTRYAGAIN,
		'S_GOBACK' => _GOBACK
	));
	$cpgtpl->set_handle('body', 'admin/rssfail.html');
	$cpgtpl->display('body');
}

function BlocksEdit($bid) {
	$blockslist = [];
 global $prefix, $db, $multilingual, $pagetitle, $cpgtpl;
	$pagetitle .= ' '._BC_DELIM.' '._EDITBLOCK;
	require('header.php');
	GraphicAdmin('_AMENU1');
	list($title, $bkey, $content, $url, $bposition, $weight, $active, $refresh, $blanguage, $blockfile, $view) = $db->sql_ufetchrow("SELECT title, bkey, content, url, bposition, weight, active, refresh, blanguage, blockfile, view FROM ".$prefix."_blocks WHERE bid='".$bid."'",SQL_NUM);
	$typebb = $typerss = $typefile = false;

	$blocks_edit_vars = array(
		'S_NAME' => $title,
		'S_BID' => $bid,
		'S_POSITION' => _POSITION,
		'S_ACTIVATE2' => _ACTIVATE2,
		'S_VIEWPRIV' => _VIEWPRIV,
		'S_TITLE' => _TITLE,
		'S_SAVECHANGES' => _SAVECHANGES,
		'S_WEIGHT' => $weight,
		'S_NAME_DEF' => (defined($title) ? constant($title):preg_replace('#_#m', ' ',$title)).":",
		'U_BLOCKS' => adminlink('blocks'),
		'MULTILANG' => $multilingual,
		'BPOSITION' => $bposition,
		'SEL_POSITION' => select_box('bposition', $bposition, array('l'=>_LEFT,'c'=>_CENTERUP,'d'=>_CENTERDOWN,'r'=>_RIGHT)),
		'SEL_ACTIVATE' => yesno_option('active', $active),
		'SEL_GROUP' => group_selectbox('view', $view, true),
		'S_VISIBLEINMODULES' => _VISIBLEINMODULES,
		'S_ACTIVE' => _ACTIVE,
		'S_INACTIVE' => _INACTIVE,
		'S_VIEW_COMMENT' => 'Default '._ACTIVE,
		'S_AUTHORSADMIN' => _AUTHORSADMIN
	);

	if ($multilingual) {
		$blocks_edit_vars += array(
			'S_LANGUAGE' => _LANGUAGE,
			'SEL_LANG' => lang_selectbox($blanguage, 'blanguage'),
		);
	}
	switch ($bkey) {
		case 'file':
			$typefile = true;
			$blocksdir = dir('blocks');
			while($func=$blocksdir->read()) {
				if(str_starts_with($func, 'block-')) {
					$bl = preg_replace('#_#m',' ',substr($func,6,-4));
					$blockslist[$func] = $bl;
				}
			}
			closedir($blocksdir->handle);
			ksort($blockslist);
			$blocks_edit_vars += array(
				'S_BLOCK_TYPE' => _FILENAME,
				'S_BLOCK_OP' => select_box('blockfile', $blockfile, $blockslist),
				'S_BLOCK_INFO' => _FILEINCLUDE,
			);
			break;

		case 'rss':
			$typerss = true;
			$blocks_edit_vars += array(
				'S_BLOCK_TYPE' => _RSSFILE,
				'S_BLOCK_OP' => $url,
				'S_BLOCK_INFO' => _ONLYHEADLINES,
				'S_REFRESHTIME' => _REFRESHTIME,
				'SEL_REFRESH' => select_box('refresh', $refresh, array('1800'=>'1/2 '._HOUR,'3600'=>'1 '._HOUR,'18000'=>'5 '._HOURS,'36000'=>'10 '._HOURS,'86400'=>'24 '._HOURS)),
			);
			break;

		case 'admin':
		case 'custom':
			$typebb = true;
			$blocks_edit_vars += array(
				'S_BLOCK_TYPE' => _CONTENT,
				'S_BLOCK_OP' => bbcode_table('content', 'blocksedit', 1),
				'S_BLOCK_INFO' => $content,
			);
			break;

		default :
			break;
	}
	$blocks_list = blocks_list();
	foreach($blocks_list as $module => $data) {
		$cpgtpl->assign_block_vars(($data['mid'] == -1)?'admin':(is_active($module) ? 'active' : 'inactive') , array(
			'S_MOD_VALUE' => $data['mid'],
			'S_MOD_TITLE' => defined($data['title']) ? constant($data['title']) : $data['title'],
			'S_MOD_SELECTED' => isset($data[$bid]) ? ' selected="selected"' : '',
		));
	}
	$blocks_edit_vars += array(
		'TYPEFILE' => $typefile,
		'TYPERSS' => $typerss,
		'TYPEBB' => $typebb,
	);
	$cpgtpl->assign_vars($blocks_edit_vars);
	$cpgtpl->set_handle('body', 'admin/blocks_edit.html');
	$cpgtpl->display('body');
}

function BlocksAdd() {
	$insert = [];
 $in_modules = [];
 global $prefix, $db;
	if (!Security::check_post()) cpg_error(_SEC_ERROR);

	$insert['bkey'] = '';
	$insert['title'] = $_POST['title'];
	$insert['content'] = $_POST['content'];
	$insert['url'] = $_POST['url'];
	$insert['bposition'] = $_POST['bposition'];
	$insert['active'] = $_POST['active'];
	$insert['refresh'] = $_POST['refresh'];
	$headline = intval($_POST['headline']);
	$insert['blanguage'] = $_POST['blanguage'];
	$insert['blockfile'] = $_POST['blockfile'];
	$insert['view'] = intval($_POST['view']);
	$insert['in_module'] = '';

	if ($headline != 0) {
		$result = $db->sql_query('SELECT sitename, headlinesurl FROM '.$prefix."_headlines WHERE hid='$headline'");
		list($insert['title'], $insert['url']) = $db->sql_fetchrow($result);
	}
	# might be removed later on
	$result = $db->sql_query('SELECT weight FROM '.$prefix.'_blocks WHERE bposition=\''.$insert['bposition'].'\' ORDER BY weight DESC');
	list($insert['weight']) = $db->sql_fetchrow($result);
	$insert['weight']++;
	# end
	if ($insert['blockfile'] != '') {
		$insert['bkey'] = 'file';
		if ($insert['title'] == '') {
			$insert['title'] = preg_replace('#(block\-)|(.php)#m','',$insert['blockfile']);
			$insert['title'] = preg_replace('#_#m',' ',$insert['title']);
		}
	} else if ($insert['url'] != '') {
		$insert['bkey'] = 'rss';
		$insert['time'] = gmtime();
		if (!preg_match('#:\/\/#m',$insert['url'])) { $insert['url'] = 'http://'.$insert['url']; }
		require_once(CORE_PATH.'classes/rss.php');
		if (!($insert['content'] = CPG_RSS::format(CPG_RSS::read($insert['url'])))) { return false; }
	} else {
		$insert['bkey'] = 'custom';
	}
	if ($insert['content'] == '' && $insert['blockfile'] == '') { return false;	}
	$db->sql_insert($prefix.'_blocks', $insert);
	$bid = $db->sql_nextid('bid');
	$count = is_countable($_POST['in_module']) ? count($_POST['in_module']) : 0;
	for ($i=0;$i<$count;$i++) {
		if (!intval($_POST['in_module'][$i])) {
			continue;
		} else {
			$in_modules[intval($_POST['in_module'][$i])] = intval($_POST['in_module'][$i]);
		}
	}
	$result = $db->sql_uquery('SELECT mid, MAX(weight) FROM '.$prefix.'_blocks_custom WHERE mid IN ('.implode(',', $in_modules).') GROUP BY mid');
	while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
		$in_modules[$row[0]] = $row[1];
	}

	$values = '';
	foreach ($in_modules as $mid => $pos) {
		$in_modules[$mid] = "($bid, $mid, '{$insert['bposition']}', $pos+1)";
	}
	$db->sql_uquery('INSERT INTO '.$prefix.'_blocks_custom (bid, mid, side, weight) VALUES '.implode(',', $in_modules));
	Cache::array_delete('blocks_list');
	return true;
}
function BlocksEditSave($bid) {
	$update = [];
 $new_in_modules = [];
 global $prefix, $db;
	if (!Security::check_post()) cpg_error(_SEC_ERROR);

	$update['title'] = $_POST['title'];
	$update['content'] = $_POST['content'];
	$update['url'] = empty($_POST['url']) ? '' : $_POST['url'];
	$oldposition = $_POST['oldposition'];
	$update['bposition'] = $_POST['bposition'];
	$update['active'] = $_POST['active'];
	$update['refresh'] = isset($_POST['refresh']) ? intval($_POST['refresh']) : 0;
	$update['blanguage'] = $_POST['blanguage'];
	$update['blockfile'] = empty($_POST['blockfile']) ? '' : $_POST['blockfile'];
	$update['view'] = intval($_POST['view']);
	$update['weight'] = intval($_POST['weight']);
	$update['in_module'] = '';

	if ($update['url'] != '') {
		$update['time'] = gmtime();
		if (!preg_match('#http:\/\/#m',$update['url'])) { $update['url'] = 'http://'.$update['url']; }
		require_once(CORE_PATH.'classes/rss.php');
		if (!($update['content'] = CPG_RSS::format(CPG_RSS::read($update['url'])))) {
			rssfail();
			return;
		}
	}
	# can be removed
	if ($oldposition != $update['bposition']) {
		$db->sql_query('UPDATE '.$prefix.'_blocks SET weight=weight+1 WHERE weight>='.$update['weight']." AND bposition='$update[bposition]'");
		$db->sql_query('UPDATE '.$prefix.'_blocks SET weight=weight-1 WHERE weight>'.$update['weight']." AND bposition='$oldposition'");
	}
	$db->sql_update($prefix.'_blocks', $update, 'bid='.$bid);


	$count = empty($_POST['in_module']) ? 0 : count($_POST['in_module']);
	for ($i=0;$i<$count;$i++) {
		if (!intval($_POST['in_module'][$i])) {
			continue;
		} else {
			$new_in_modules[intval($_POST['in_module'][$i])] = intval($_POST['in_module'][$i]);
		}
	}

	$table_data = array();
	# select all data
	$result = $db->sql_query('SELECT a.mid, a.bid, MAX(b.weight) FROM '.$prefix.'_blocks_custom a, '.$prefix.'_blocks_custom b GROUP BY a.mid, a.bid');
	if ($db->sql_numrows($result)) {
		while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
			# block is there but module id has not been posted so delete from it
			if ($row[1] == $bid && !isset($new_in_modules[$row[0]])) {
				$db->sql_uquery('DELETE FROM '.$prefix."_blocks_custom WHERE bid=$bid AND mid=".$row[0]);
				$db->sql_uquery('UPDATE '.$prefix."_blocks_custom SET weight=weight-1 WHERE weight>{$row[2]} AND mid=".$row[0]);
			} else if ($row[1] == $bid && isset($new_in_modules[$row[0]])) {
				# module id has been posted and it exists within the table: clearing posteded data
				$new_in_modules[$row[0]] = '';
			}
			if (!isset($table_data[$row[0]])) {
				# save what we need for later use
				$table_data[$row[0]] = $row[2];
			}
		}
		$db->sql_freeresult($result);
	}
	$values = array();
	# insert anything left from the posted data
	if (!empty($new_in_modules)) {
		foreach($new_in_modules as $mid) {
			if (!empty($mid)) $values[] = "('$bid', '$mid', '{$update['bposition']}', '".($table_data[$mid]+1)."')";
		}
	}
	if (!empty($values)) {
		$db->sql_uquery('INSERT INTO '.$prefix.'_blocks_custom (bid, mid, side, weight) VALUES '.implode(',', $values));
	}
	Cache::array_delete('blocks_list');
	url_redirect(adminlink('blocks'));
}