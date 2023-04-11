<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('surveys')) { die('Access Denied'); }
$OUT = Dragonfly::getKernel()->OUT;
\Dragonfly\Page::title($OUT->L10N['_POLLSADMIN'], false);

if ('POST'===$_SERVER['REQUEST_METHOD'])
{
	if (isset($_GET['edit']))
	{
		$id = $_GET->uint('edit');
		$ptime = $_POST->datetime('ptime');
		$db->TBL->poll_desc->update(array(
			'poll_title' => $_POST->txt('title'),
			'poll_ptime' => $ptime->getTimestamp(),
			'planguage'  => $_POST->txt('language'),
			'anonymous'  => $_POST->bool('anonymous'),
			'comments'   => $_POST->bool('comments'),
		), "poll_id={$id}");
		for ($i = 0; $i < 12; $i++) {
			$db->TBL->poll_data->update(array(
				'option_text'=>$_POST->txt('opt',$i)
			), "vote_id=".($i+1)." AND poll_id={$id}");
		}
	}

	else if (isset($_GET['add']))
	{
		$ptime = $_POST->datetime('ptime');
		$id = $db->TBL->poll_desc->insert(array(
			'poll_title' => $_POST->txt('title'),
			'poll_ptime' => $ptime ? $_POST->datetime('ptime') : time(),
			'planguage'  => $_POST->txt('language'),
			'anonymous'  => $_POST->bool('anonymous'),
			'comments'   => $_POST->bool('comments'),
			'time_stamp' => time(),
			'voters'     => 0,
			'artid'      => 0,
		),'poll_id');
		for ($i = 0; $i <= sizeof($_POST['opt']); ++$i) {
			if (strlen($_POST->txt('opt',$i))) {
				$db->TBL->poll_data->insert(array(
					'poll_id'      => $id,
					'option_text'  => $_POST->txt('opt',$i),
					'option_count' => 0,
					'vote_id'      => $i+1
				));
			}
		}
	}

	else if (isset($_GET['del_comment']))
	{
		function removeSubComments($tid)
		{
			global $db;
			$result = $db->query("SELECT tid FROM {$db->TBL->pollcomments} WHERE pid={$tid}");
			if ($result->num_rows) {
				while (list($ctid) = $result->fetch_row()) {
					removeSubComments($ctid);
				}
				$db->query("DELETE FROM {$db->TBL->pollcomments} WHERE pid={$tid}");
			}
			$result->free();
		}

		$tid = $_GET->uint('del_comment');
		list($poll_id) = $db->uFetchRow("SELECT poll_id FROM {$db->TBL->pollcomments} WHERE tid={$tid}");
		if (isset($_POST['cancel'])) {
			URL::redirect(URL::index("Surveys&pollid={$poll_id}&op=results"));
		}
		if (isset($_POST['confirm'])) {
			removeSubComments($poll_id);
			$db->query("DELETE FROM {$db->TBL->pollcomments} WHERE tid={$tid}");
			URL::redirect(URL::index("Surveys&pollid={$poll_id}&op=results"));
		}
	}

	else if (isset($_GET['delete']) && isset($_POST['confirm']))
	{
		$id = $_GET->uint('delete');
		$db->exec("DELETE FROM {$db->TBL->poll_desc} WHERE poll_id={$id}");
		$db->exec("DELETE FROM {$db->TBL->poll_data} WHERE poll_id={$id}");
		$db->exec("DELETE FROM {$db->TBL->pollcomments} WHERE poll_id={$id}");
		$db->exec("DELETE FROM {$db->TBL->poll_check} WHERE poll_id={$id}");
	}

	URL::redirect(URL::admin(''));
}


require_once('header.php');

if (isset($_GET['delete']))
{
	\Dragonfly\Page::confirm('', sprintf(_ERROR_DELETE_CONF,_POLLS));
}

else if (isset($_GET['del_comment']) && can_admin('surveys'))
{
	\Dragonfly\Page::confirm(URL::admin("&del_comment={$tid}"), _SURETODELCOMMENTS);
}

else if (isset($_GET['edit']))
{
	$id = $_GET->uint('edit');
	$OUT->poll = $db->uFetchAssoc("SELECT
		poll_id id,
		poll_title title,
		poll_ptime ptime,
		time_stamp,
		voters,
		planguage,
		artid,
		comments,
		anonymous
	FROM {$db->TBL->poll_desc}
	WHERE poll_id={$id}");
	$OUT->poll['options'] = $db->query("SELECT
		vote_id id,
		option_text label,
		option_count votes
	FROM {$db->TBL->poll_data}
	WHERE poll_id={$id}
	ORDER BY vote_id");
	$OUT->display('Surveys/admin/edit');
}

else if (isset($_GET['add']))
{
	// _CREATEPOLL
	$K = Dragonfly::getKernel();
	$OUT->poll = array(
		'id'        => 0,
		'title'     => '',
		'ptime'     => null,
		'time_stamp'=> null,
		'voters'    => 0,
		'planguage' => '',
		'artid'     => 0,
		'comments'  => $K->CFG->global->pollcomm,
		'anonymous' => 0,
		'options'   => array()
	);
	for ($i=1; $i<13; ++$i) {
		$OUT->poll['options'][] = array('id'=>$i, 'label'=>'', 'votes'=>0);
	}
	$OUT->display('Surveys/admin/edit');
}

else if (isset($_GET['comments']))
{
	$limit = 25;
	$offset = $limit * (max(1, $_GET->uint('page')) - 1);
	$tbl = $db->TBL->pollcomments;
	$OUT->polls_comments = $db->query("SELECT
		tid     id,
		pid     parent_id,
		poll_id,
		date,
		remote_ip,
		comment,
		score,
		user_id,
		username
	FROM {$tbl}
	LEFT JOIN {$db->TBL->users} USING (user_id)
	ORDER BY date DESC
	LIMIT {$limit} OFFSET {$offset}");
	$OUT->polls_comments_pagination = new \Poodle\Pagination('?admin&op=Surveys&comments&page=${page}',
		$tbl->count(),
		$offset,
		$limit
	);

	$OUT->display('Surveys/admin/comments');
}

else
{
	$OUT->polls = $db->query("SELECT
		poll_id    id,
		poll_title title,
		poll_ptime ptime,
		time_stamp,
		voters,
		planguage,
		artid,
		comments,
		anonymous
	FROM {$db->TBL->poll_desc}
	ORDER BY poll_ptime DESC");
	$OUT->display('Surveys/admin/list');
}
