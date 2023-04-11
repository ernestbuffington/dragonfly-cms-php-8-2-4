<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('cpgmm')) { exit('Access Denied'); }

\Dragonfly::getKernel()->L10N->load('cpgmm');
$OUT = \Dragonfly::getKernel()->OUT;

/*******************
  Manage links
*******************/
if (isset($_GET['editlnk'])) {
	$id = $_GET->int('editlnk');

	if ('POST' === $_SERVER['REQUEST_METHOD'] && \Dragonfly\Output\Captcha::validate($_POST)) {
		if (isset($_GET['delete'])) {
			if (isset($_POST['confirm'])) {
				$db->exec("DELETE FROM {$db->TBL->modules_links} WHERE lid={$id}");
			}
		} else if (0 > $id) {
			$db->TBL->modules->update(array(
				'cat_id' => $_POST->uint('lnkcat'),
				'inmenu' => $_POST->bool('lnkactive'),
			), 'mid = '.abs($id));
		} else {
			if (!$_POST['lnktitle']) { cpg_error(_CPG_MMLNKEMPTY); }
			if (!$_POST['lnklink']) { cpg_error(_CPG_MMURLEMPTY); }
			$data = array(
				'title'     => $_POST['lnktitle'],
				'link_type' => $_POST->uint('lnktype'),
				'link'      => $_POST['lnklink'],
				'active'    => $_POST->bool('lnkactive'),
				'view'      => $_POST->uint('lnkview'),
				'cat_id'    => $_POST->uint('lnkcat'),
			);
			if ($id) {
				$db->TBL->modules_links->update($data, "lid = {$id}");
			} else {
				$db->TBL->modules_links->insert($data);
			}
		}
		for ($i = 1; $i < 100; ++$i) {
			\Dragonfly::getKernel()->CACHE->delete("Dragonfly/Page/Menu/User_anonymous-{$i}");
		}
//		\Dragonfly::getKernel()->CACHE->clear();
		\URL::redirect(\URL::admin('cpgmm'));
	}

	if (0 > $id) {
		$link = $db->uFetchAssoc("SELECT title, inmenu AS active, cat_id FROM {$db->TBL->modules} WHERE mid = ".abs($id));
	} else if ($id) {
		$link = $db->uFetchAssoc("SELECT * FROM {$db->TBL->modules_links} WHERE lid={$id}");
		if ($link && isset($_GET['delete'])) {
			\Dragonfly\Page::title(_CPG_MMDELLNK.' '.$link['title'], false);
			\Dragonfly\Page::confirm('', sprintf(_ERROR_DELETE_CONF, $link['title']));
		}
	} else {
		$link = array('title' => '', 'link' => '', 'link_type' => 0, 'active' => 1, 'cat_id' => 0, 'view' => 0);
	}
	if ($link) {
		$cats = array('0' => '['._NONE.']');
		$qr = $db->query("SELECT cid, name FROM {$db->TBL->modules_cat} ORDER BY name");
		while ($cat = $qr->fetch_row()) {
			$cats[$cat[0]] = defined($cat[1]) ? constant($cat[1]) : $cat[1];
		}
		\Dragonfly\Page::title(_CPG_MMADMIN);
		$OUT->HEAD_TITLE = $id ? 'Edit Link' : _CPG_MMADDLINK;
		$OUT->MOD = (0 > $id);
		$OUT->S_SUBMIT_VALUE = $id ? _SAVECHANGES : _CPG_MMADDLINK;
		$OUT->SEL_CAT = \Dragonfly\Output\HTML::select_box('lnkcat', $link['cat_id'], $cats);
		$OUT->SEL_LINK = (0 > $id) ? null : \Dragonfly\Output\HTML::select_box('lnktype', $link['link_type'], array(0 => 'getlink', 1 => 'link', 2 => 'web'));
		$OUT->cpgmm_item = $link;
		$OUT->display('admin/cpgmm/link');
	} else {
		cpg_error(_CPG_MMNOLINK);
	}
}

/*******************
  Manage categories
*******************/
else if (isset($_GET['cid'])) {
	$cid = $_GET->uint('cid');

	if ('POST' === $_SERVER['REQUEST_METHOD'] && \Dragonfly\Output\Captcha::validate($_POST)) {
		if (isset($_GET['delete'])) {
			if (isset($_POST['confirm'])) {
				$db->exec("UPDATE {$db->TBL->modules_links} SET cat_id=0 WHERE cat_id={$cid}");
				$db->exec("UPDATE {$db->TBL->modules} SET cat_id=0 WHERE cat_id={$cid}");
				$db->exec("DELETE FROM {$db->TBL->modules_cat} WHERE cid={$cid}");
			}
		} else {
			if (!$_POST['catname']) {
				cpg_error(_CPG_MMCATEMPTY);
			}
			$data = array(
				'name'      => $_POST['catname'],
				'image'     => $_POST['catimage'],
				'link'      => $_POST['catlink'],
				'link_type' => $_POST->uint('lnktype'),
			);
			if ($cid) {
				$db->TBL->modules_cat->update($data, "cid = {$cid}");
			} else {
				list($pos) = $db->uFetchRow("SELECT MAX(pos) FROM {$db->TBL->modules_cat}");
				$data['pos'] = empty($pos) ? 0 : ($pos+1);
				$db->TBL->modules_cat->insert($data);
			}
		}
		for ($i = 1; $i < 100; ++$i) {
			\Dragonfly::getKernel()->CACHE->delete("Dragonfly/Page/Menu/User_anonymous-{$i}");
		}
		\URL::redirect(\URL::admin('cpgmm'));
	}

	if ($cid) {
		$cat = $db->uFetchAssoc("SELECT name, image, link_type, link FROM {$db->TBL->modules_cat} WHERE cid={$cid}");
		if ($cat && isset($_GET['delete'])) {
			$cat['name'] = defined($cat['name']) ? constant($cat['name']) : $cat['name'];
			\Dragonfly\Page::title(Dragonfly::getKernel()->L10N->get('Delete Category').": {$cat['name']}", false);
			\Dragonfly\Page::confirm('', sprintf(_ERROR_DELETE_CONF, $cat['name']));
		}
	} else {
		$cat = array('name'=>'', 'image'=>'empty.gif', 'link'=>'', 'link_type'=>0);
	}
	if ($cat) {
		\Dragonfly\Page::title(_CPG_MMADMIN);
		$OUT->HEAD_TITLE = $cid ? _CPG_MMCATEDIT : _CPG_MMCATNEW;
		$OUT->cpgmm_cat = $cat;
		$OUT->S_SUBMIT_VALUE = ($cid ? _SAVECHANGES : _CPG_MMADDCAT);
		$OUT->SEL_LINKTYPE = \Dragonfly\Output\HTML::select_box('lnktype', $cat['link_type'], array(0 => 'getlink', 1 => 'link', 2 => 'web'));
		$OUT->display('admin/cpgmm/category');
	} else {
		cpg_error(_CPG_MMNOCAT);
	}
}

/*******************
  Save the menu
*******************/
else if ('POST' === $_SERVER['REQUEST_METHOD']) {
	if (\Dragonfly\Output\Captcha::validate($_POST) && isset($_POST['updatecpgmm']) && is_array($_POST['id']) && is_array($_POST['cat_id'])) {
		$cat_id = 0;
		$count = count($_POST['id']);
		for ($i = $catpos = $linkpos = 0; $i < $count; ++$i) {
			if ($_POST['cat_id'][$i]) {
				++$linkpos;
				$_POST['id'][$i] > 0
					? $db->update('modules_links', array('pos'=>$linkpos,'cat_id'=> $cat_id), 'lid='.$_POST['id'][$i])
					: $db->update('modules', array('pos'=>$linkpos,'cat_id'=> $cat_id), 'mid='.($_POST['id'][$i] * -1));
			} else {
				$cat_id = (int) $_POST['id'][$i];
				$db->update('modules_cat', array('pos'=>$catpos), 'cid='.$cat_id);
				++$catpos;
			}
		}
	}
	for ($i = 1; $i < 100; ++$i) {
		\Dragonfly::getKernel()->CACHE->delete("Dragonfly/Page/Menu/User_anonymous-{$i}");
	}
	\URL::redirect(\URL::admin('cpgmm'));
}

/*******************
  Show the menu
*******************/
else {
	$categories = array();
	// Load the categories
	$cats = $db->query("SELECT * FROM {$db->TBL->modules_cat} ORDER BY pos");
	while ($cat = $cats->fetch_assoc()) {
		if ($cat['image'] && is_file('images/blocks/CPG_Main_Menu/'.$cat['image'])) {
			$cat['image'] = 'images/blocks/CPG_Main_Menu/'.$cat['image'];
		} else {
			$cat['image'] = 'images/blocks/CPG_Main_Menu/empty.gif';
		}
		if (defined($cat['name'])) {
			$cat['name'] = constant($cat['name']);
		}
		$cat['items'] = array();
		$categories[$cat['cid']] = $cat;
	}
	$categories[0] = array('cid' => 0, 'name' => _NONE, 'image' => 'images/smiles/icon_exclaim.gif', 'pos' => -1, 'items' => array());

	// Load permitted active modules and links
	$items = $db->query('(SELECT -mid AS id, title, active, view, inmenu, cat_id, -1 as link_type, pos FROM '.$db->TBL->modules.')
UNION (SELECT lid AS id, title, active, view, 1 as inmenu, cat_id, link_type, pos FROM '.$db->TBL->modules_links.')
ORDER BY pos');
	$ipos = 0;
	$link_types = array(-1 => '', 'getlink', 'link', 'web');
	while ($item = $items->fetch_assoc()) {
		++$ipos;
		$mod = (0 > $item['id']);
		if ($ipos != $item['pos']) {
			$item['pos'] = $ipos;
			if ($mod) {
				$db->exec("UPDATE {$db->TBL->modules} SET pos={$ipos} WHERE mid=-{$item['id']}");
			} else {
				$db->exec("UPDATE {$db->TBL->modules_links} SET pos={$ipos} WHERE lid={$item['id']}");
			}
		}
		$categories[$item['cat_id']]['items'][] = $item + array(
			'TYPE' => $link_types[$item['link_type']],
			'LINK_SELECT' => ($mod && $item['active'] && $item['inmenu']) || (!$mod && $item['active']),
			'LINK_HIDDEN' => ($mod && !$item['inmenu']) || (!$mod && !$item['active']),
			'LINK_FORBID' => $mod && !$item['active'] && $item['inmenu'],
			'LINK_TYPE'   => $item['link_type'] < 0,
			'ITEM_VARIABLE' => $item['link_type'] >= 0,
		);
	}

	\Dragonfly\Page::title(_CPG_MMADMIN);
	\Dragonfly\Output\Css::add('tabletree');
	\Dragonfly\Output\Js::add('themes/admin/javascript/admincpgmm.js');
	$OUT->HEAD_TITLE = 'The CPG Main Menu Block';
	$OUT->cpgmm_categories = $categories;
	$OUT->display('admin/cpgmm/index');
}
