<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('members')) { die('Access Denied'); }

$OUT = \Dragonfly::getKernel()->OUT;
$L10N = \Dragonfly::getKernel()->L10N;
$L10N->load('dragonfly/ranks');
\Dragonfly\Page::title('Ranks');

if (isset($_GET['edit']) || isset($_POST['add']) || isset($_POST['save']))
{
	//
	// add/edit a rank.
	//

	$rank_id = $_GET->uint('edit');

	if (isset($_POST['save'])) {
		$rank = array(
			'rank_title'   => $_POST->txt('title'),
			'rank_special' => $_POST->bool('rank_special'),
			'rank_min'     => $_POST->bool('rank_special') ? -1 : (int)$_POST->uint('rank_min'),
			'rank_image'   => $_POST->txt('rank_image')
		);

		if (!$rank['rank_title']) {
			cpg_error($L10N['Must_select_rank']);
		}

		//
		// The rank image has to be a jpg, gif or png
		//
		if (!preg_match('/(\.gif|\.png|\.jpg)$/is', $rank['rank_image'])) {
			$rank['rank_image'] = '';
		}

		if ($rank_id) {
			if (!$rank['rank_special']) {
				$db->query("UPDATE {$db->TBL->users} SET user_rank = 0 WHERE user_rank = {$rank_id}");
			}
			$db->TBL->bbranks->update($rank, "rank_id = {$rank_id}");
			$message = $L10N['Rank_updated'];
		} else {
			$db->TBL->bbranks->insert($rank);
			$message = $L10N['Rank_added'];
		}
		\Poodle\Notify::success($message);
		\URL::redirect(\URL::admin($op));
		return;
	}

	if ($rank_id) {
		$rank_info = $db->uFetchAssoc("SELECT * FROM {$db->TBL->bbranks} WHERE rank_id = {$rank_id}");
	} else {
		$rank_info = array(
			'rank_title'   => null,
			'rank_special' => 0,
			'rank_min'     => null,
			'rank_image'   => null,
		);
	}

	$OUT->set_handle('body', 'admin/ranks/edit');

	$OUT->assign_vars(array(
		'RANK_TITLE'   => $rank_info['rank_title'],
		'RANK_SPECIAL' => $rank_info['rank_special'],
		'RANK_MINIMUM' => ($rank_info['rank_special'] ? null : $rank_info['rank_min']),
		'RANK_IMAGE'   => $rank_info['rank_image'],
	));
}
else if (isset($_GET['delete']))
{
	//
	// Ok, delete a rank
	//
	$rank_id = $_GET->uint('delete');
	if ($rank_id) {
		if (isset($_POST['confirm'])) {
			$db->query("DELETE FROM {$db->TBL->bbranks} WHERE rank_id = {$rank_id}");
			$db->query("UPDATE {$db->TBL->users} SET user_rank = 0 WHERE user_rank = {$rank_id}");
			\Poodle\Notify::success($L10N['Rank_removed']);
			\URL::redirect(\URL::admin($op));
		} else if (isset($_POST['cancel'])) {
			\URL::redirect(\URL::admin($op));
		}
		$rank = $db->uFetchRow("SELECT rank_title FROM {$db->TBL->bbranks} WHERE rank_id = {$rank_id}");
		\Dragonfly\Page::confirm('', "Delete rank '{$rank[0]}'?");
	} else {
		cpg_error($L10N['Must_select_rank']);
	}
}
else
{
	//
	// Show the default page
	//
	$OUT->set_handle('body', 'admin/ranks/list');

	$result = $db->query("SELECT * FROM {$db->TBL->bbranks} ORDER BY rank_min ASC, rank_special ASC");

	$OUT->ranks = array();
	foreach ($result as $rank_row)
	{
		$OUT->ranks[] = array(
			'title' => $rank_row['rank_title'],
			'min' => $rank_row['rank_special'] ? null : $rank_row['rank_min'],
			'special' => ($rank_row['rank_special'] ? $L10N['Yes'] : $L10N['No']),
			'U_EDIT' => URL::admin("{$op}&edit={$rank_row['rank_id']}"),
			'U_DELETE' => URL::admin("{$op}&delete={$rank_row['rank_id']}")
		);
	}
}

$OUT->display('body');
