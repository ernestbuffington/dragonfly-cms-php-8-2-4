<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2015 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('blocks')) { die('Access Denied'); }

Dragonfly::getKernel()->L10N->load('dragonfly/blocks');

\Dragonfly\Page::title('Blocks');

if (isset($_GET['change']))
{
	$block = new \Dragonfly\Blocks\Admin\Block($_GET['change']);
	if ($block->bid) {
		$block->active = !$block->active;
		$block->save();
	}
	URL::redirect(URL::admin('blocks'));
}

else if (isset($_GET['show'])) {
	block_show(intval($_GET['show']));
}

else if (isset($_GET['edit'])) {
	if (Security::check_post()) {
		BlocksEditSave($_GET['edit']);
	}
	BlocksEdit($_GET['edit']);
}

else if (isset($_GET['del'])) {
	if (isset($_POST['cancel'])) { URL::redirect(URL::admin()); }
	$block = new \Dragonfly\Blocks\Admin\Block($_GET['del']);
	if (isset($_POST['confirm'])) {
		$block->delete();
		//Dragonfly::getKernel()->CACHE->delete('blocks_list');
		URL::redirect(URL::admin());
	}
//	\Dragonfly\Page::confirm(URL::admin('&del='.$block->bid), sprintf(Dragonfly::getKernel()->L10N->get('Are you sure that you want to delete %s?'), $block->label));
	\Dragonfly\Page::confirm(URL::admin('&del='.$block->bid), sprintf(_ERROR_DELETE_CONF, $block->label));
}

else {
	if (isset($_POST['add']) && Security::check_post()) {
		BlocksEditSave(0);
		return;
	}

	if (isset($_POST['mid']) && Security::check_post()) {
		$mid = intval($_POST['mid']);

		if (0 < $mid) {
			$blocks = empty($_POST['module_blocks']) ? 0 : array_sum($_POST['module_blocks']);
			$db->exec("UPDATE {$db->TBL->modules} SET blocks={$blocks} WHERE mid={$mid}");
		}

		$db->query("DELETE FROM {$db->TBL->blocks_custom} WHERE mid={$mid}");
		foreach ($_POST['side'] as $side => $bids) {
			$bids = array_values($bids);
			foreach ($bids as $weight => $bid) {
				$db->TBL->blocks_custom->insert(array(
					'bid' => $bid,
					'mid' => $mid,
					'side' => $side,
					'weight' => $weight+1
				));
			}
		}

		if (XMLHTTPRequest) { exit; }
		//Dragonfly::getKernel()->CACHE->delete('blocks_list');
		URL::redirect(URL::admin('blocks'));
	}

	BlocksAdmin();
}

function BlocksAdmin()
{
	\Dragonfly\Output\Css::add('adminblocks');
	\Dragonfly\Output\Js::add('themes/admin/javascript/adminblocks.js');

	\Dragonfly\BBCode::pushHeaders();

	$K   = Dragonfly::getKernel();
	$L10N = $K->L10N;
	$TPL  = $K->OUT;
	$SQL  = $K->SQL;

	$TPL->block = new \Dragonfly\Blocks\Admin\Block();

	$blocks = array();
	$result = $SQL->query('SELECT bid id, bkey, title label, url, bposition, weight, active, blanguage, blockfile, view_to
	FROM '.$SQL->TBL->blocks.' ORDER BY title');
	while ($row = $result->fetch_assoc()) {
		if (defined($row['label'])) { $row['label'] = constant($row['label']); }
		$row['view_to'] = explode(',', $row['view_to']);
		$blocks[$row['id']] = $row;
	}

	#
	# javascript blocks table
	#
	$TPL->modules = array('active'=>array(),'inactive'=>array());
	$sides = array('l'=>array(), 'c'=>array(), 'd'=>array(), 'r'=>array());
	$blocks_list = array(
		-1 => array(
			'id' => -1,
			'label' => _ADMINISTRATION,
			'active' => $L10N->get('yes'),
			'blocks_l' => true,
			'blocks_r' => true,
			'blocks_c' => true,
			'blocks_d' => true,
			'blocks' => $sides,
		)
	);
	$result = $SQL->query('SELECT mid, title, blocks FROM '.$SQL->TBL->modules);
	while ($row = $result->fetch_row()) {
		$title = \Dragonfly\Modules\Module::get_title($row[1]);
		$active = \Dragonfly\Modules::isActive($row[1]);
		$blocks_list[$row[0]] = array(
			'id' => (int)$row[0],
			'label' => $title,
			'active' => $L10N->get($active ? 'yes' : 'no'),
			'blocks_l' => !!($row[2] & \Dragonfly\Blocks::LEFT),
			'blocks_r' => !!($row[2] & \Dragonfly\Blocks::RIGHT),
			'blocks_c' => !!($row[2] & \Dragonfly\Blocks::CENTER),
			'blocks_d' => !!($row[2] & \Dragonfly\Blocks::DOWN),
			'blocks' => $sides,
		);
		$TPL->modules[$active?'active':'inactive'][] = array(
			'id' => $row[0],
			'label' => $row[1],
		);
	}
	$result = $SQL->query('SELECT bid, mid, side FROM '.$SQL->TBL->blocks_custom.' ORDER BY mid, weight');
	while ($row = $result->fetch_row()) {
		$bid = (int)$row[0];
		$mid = (int)$row[1];
		if (!isset($blocks_list[$mid]) || !isset($blocks[$bid])) {
			$SQL->query("DELETE FROM {$SQL->TBL->blocks_custom} WHERE bid={$bid} AND mid={$mid}");
		} else {
			$side = &$blocks_list[$mid]['blocks'][$row[2]];
			$side[] = array(
				'id'     => $bid,
				'label'  => $blocks[$bid]['label'],
				'weight' => count($side),
			);
		}
	}
	$result->free();
	usort($blocks_list, function($a, $b){
		if ($a['id'] < 1) return -1;
		if ($b['id'] < 1) return 1;
		return strcasecmp($a['label'], $b['label']);
	});
	$TPL->modules_blocks = $blocks_list;

	#
	# static blocks table
	#
	$groups = \Dragonfly\Groups::getSystem(true);
	$visblocks = array();
	foreach ($blocks as $id => $block) {
		$visblocks[$block['blockfile']] = true;
		$block['allow_del']  = false;
		$block['language']   = ($block['blanguage'] ? $block['blanguage'] : Dragonfly::getKernel()->L10N->get('All'));
		$block['view_group'] = array();
		foreach ($block['view_to'] as $gid) {
			$block['view_group'][] = $groups[(int)($gid > 3)]['groups'][$gid]['label'];
		}
		$block['view_group'] = implode(', ', $block['view_group']);
		if ($block['bkey'] == 'admin' || $block['bkey'] == 'userbox') {
			$block['type'] = $L10N->get('System');
		} else {
			$block['allow_del'] = true;
			if ($block['bkey'] == 'custom')   { $block['type'] = $L10N->get('Custom'); }
			elseif ($block['bkey'] == 'rss')  { $block['type'] = 'RSS/RDF'; }
			elseif ($block['bkey'] == 'file') { $block['type'] = $L10N->get('File'); }
		}
		$blocks[$id] = $block;
	}
	$TPL->blocks = $blocks;

	$TPL->headlines  = $SQL->query("SELECT hid id, sitename label FROM {$SQL->TBL->headlines}");

	$TPL->display('admin/blocks/index');
}

function block_show($bid)
{
	global $Module, $Blocks;
	$Module->sides = 0;
	require('header.php');
	$block = new \Dragonfly\Blocks\Admin\Block($bid);
	\Dragonfly::getKernel()->OUT->assign_vars(array(
		'U_CHANGE' => URL::admin('blocks&change='.$block->bid),
		'U_EDIT' => URL::admin('blocks&edit='.$block->bid),
		'U_DEL' => empty($block->bkey) ? '' : URL::admin('blocks&del='.$block->bid),
		'S_BID' => $block->bid
	));
	$Blocks->preview($block->data);
}

function BlocksEdit($bid)
{
	\Dragonfly\Page::title('Edit Block');

	\Dragonfly\BBCode::pushHeaders();

	$TPL = Dragonfly::getKernel()->OUT;
	$SQL = Dragonfly::getKernel()->SQL;

	$TPL->block = new \Dragonfly\Blocks\Admin\Block($bid);
	$bid = $TPL->block->bid;

	$TPL->modules = array('active'=>array(), 'inactive'=>array());
	$result = $SQL->query("SELECT
		m.mid,
		m.title,
		b.bid
	FROM {$SQL->TBL->modules} m
	LEFT JOIN {$SQL->TBL->blocks_custom} b ON (b.mid = m.mid AND b.bid = {$bid})
	ORDER BY 2");
	while ($row = $result->fetch_row()) {
		$title = defined('_'.$row[1].'LANG') ? '_'.$row[1].'LANG' : (defined('_'.strtoupper($row[1])) ? '_'.strtoupper($row[1]) : $row[1]);
		$TPL->modules[\Dragonfly\Modules::isActive($row[1])?'active':'inactive'][] = array(
			'id' => $row[0],
			'label' => defined($title) ? constant($title) : $title,
			'selected' => !!$row[2],
		);
	}

	return $TPL->display('admin/blocks/edit');
}

function BlocksEditSave($bid)
{
	$SQL = Dragonfly::getKernel()->SQL;

	$block = new \Dragonfly\Blocks\Admin\Block($bid);
	$bid   = $block->bid;
	foreach ($_POST['block'] as $k => $v) { $block->$k = $v; }

	if (!empty($_POST['headline']) && $headline = intval($_POST['headline'])) {
		list($block->title, $block->url) = $SQL->uFetchRow("SELECT sitename, headlinesurl FROM {$SQL->TBL->headlines} WHERE hid={$headline}");
	}

	if (!$block->save()) {
		return false;
	}

	$in_modules = array();
	$count = empty($_POST['in_module']) ? 0 : count($_POST['in_module']);
	for ($i=0; $i<$count; ++$i) {
		if ($mid = intval($_POST['in_module'][$i])) {
			$in_modules[$mid] = 1;
		}
	}

	if (!empty($in_modules)) {
		if ($bid) {
			$result = $SQL->query("SELECT
				a.mid,
				b.bid,
				MAX(a.weight)
			FROM {$SQL->TBL->blocks_custom} a
			LEFT JOIN {$SQL->TBL->blocks_custom} b ON (b.mid=a.mid AND b.bid={$block->bid})
			GROUP BY a.mid, b.bid");
		} else {
			$result = $SQL->query("SELECT
				mid,
				0,
				MAX(weight)
			FROM {$SQL->TBL->blocks_custom}
			WHERE mid IN (".implode(',', array_keys($in_modules)).")
			GROUP BY mid");
		}
		while ($row = $result->fetch_row()) {
			if (isset($in_modules[$row[0]])) {
				if ($row[1]) {
					# module id has been posted and it exists within the table: clearing posteded data
					unset($in_modules[$row[0]]);
				} else {
					# save what we need for later use
					$in_modules[$row[0]] = $row[2]+1;
				}
			} else {
				# block is there but module id has not been posted so delete from it
				$SQL->exec("DELETE FROM {$SQL->TBL->blocks_custom} WHERE bid={$block->bid} AND mid=".$row[0]);
				$SQL->exec("UPDATE {$SQL->TBL->blocks_custom} SET weight=weight-1 WHERE weight>{$row[2]} AND mid=".$row[0]);
			}
		}
		if (!empty($in_modules)) {
			foreach ($in_modules as $mid => $pos) {
				$in_modules[$mid] = "({$block->bid}, {$mid}, '{$block->bposition}', {$pos})";
			}
			$SQL->exec("INSERT INTO {$SQL->TBL->blocks_custom} (bid, mid, side, weight) VALUES ".implode(',', $in_modules));
		}
	}

	//Dragonfly::getKernel()->CACHE->delete('blocks_list');
	URL::redirect(URL::admin('blocks'));
}
