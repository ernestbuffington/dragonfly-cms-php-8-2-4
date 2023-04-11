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
if (!can_admin()) { die('Access Denied'); }
\Dragonfly\Page::title(_BANNERS, false);

$K = \Dragonfly::getKernel();
$tbl = $K->SQL->TBL->banner;
if (isset($_GET['id'])) {
	$id = $_GET->uint('id');
	if (isset($_GET['active'])) {
		$tbl->update(array('active' => $_GET->bool('active')), "bid={$id}");
		URL::redirect(URL::admin());

	} else if (isset($_GET['delete'])) {
		if ($_GET->bool('delete')) {
			$tbl->delete("bid={bid}");
			URL::redirect(URL::admin());
		} else {
			$K->OUT->banner = $K->SQL->uFetchAssoc("SELECT
				b.bid,
				b.cid,
				b.imptotal,
				CASE WHEN b.imptotal > 0 THEN imptotal - impmade ELSE {$K->SQL->quote(_UNLIMITED)} END imp_left,
				b.impmade,
				b.clicks,
				b.imageurl,
				b.clickurl,
				b.active,
				b.textban,
				b.text_width,
				b.text_height,
				b.text_title,
				b.text_bg,
				b.text_clr,
				u.username
			FROM {$tbl} b
			LEFT JOIN {$K->SQL->TBL->users} u ON (u.user_id = b.cid)
			WHERE b.bid={$id}");
			$K->OUT->display('Our_Sponsors/admin/delete');
		}

	} else {
		if ('POST' === $_SERVER['REQUEST_METHOD']) {
			if (isset($_POST['imageurl'])) {
				$tbl->update(array(
					'type'        => $_POST->uint('type'),
					'active'      => $_POST->bool('active'),
					'clickurl'    => $_POST['clickurl'],
					'imptotal'    => $_POST->uint('imptotal'),
					'imageurl'    => $_POST['imageurl'],
					'alttext'     => $_POST['alttext'],
				), "bid={$id}");
			} else {
				$tbl->update(array(
					'type'        => $_POST->uint('type'),
					'active'      => $_POST->bool('active'),
					'clickurl'    => $_POST['clickurl'],
					'imptotal'    => $_POST->uint('imptotal'),
					'text_width'  => $_POST->uint('text_width'),
					'text_height' => $_POST->uint('text_height'),
					'text_title'  => $_POST->uint('text_title'),
					'text_bg'     => $_POST->color('text_bg'),
					'text_clr'    => $_POST->color('text_clr')
				), "bid={$id}");
			}
			URL::redirect(URL::admin());
		}
		$K->OUT->banner = $K->SQL->uFetchAssoc("SELECT * FROM {$tbl} WHERE bid={$id}");
		if (!$K->OUT->banner) {
			cpg_error('Banner doesn\'t exist');
		}
		$K->OUT->display('Our_Sponsors/admin/edit');
	}

} else {
	$K->OUT->banners = $K->SQL->query("SELECT
		b.bid,
		b.cid,
		b.imptotal,
		CASE WHEN b.imptotal > 0 THEN imptotal - impmade ELSE {$K->SQL->quote(_UNLIMITED)} END imp_left,
		b.impmade,
		b.clicks,
		b.imageurl,
		b.clickurl,
		b.date,
		CASE WHEN b.type > 0 THEN {$K->SQL->quote(_BLOCK)} ELSE {$K->SQL->quote(_NORMAL)} END type,
		b.active,
		b.textban,
		b.text_width,
		b.text_height,
		b.text_title,
		b.text_bg,
		b.text_clr,
		u.username
	FROM {$tbl} b
	LEFT JOIN {$K->SQL->TBL->users} u ON (u.user_id = b.cid)
	ORDER BY b.active, u.username, b.bid");

	/* Clients List */
	$K->OUT->banner_clients = $K->SQL->query("SELECT
		sum(b.active) as active,
		count(b.bid)-sum(b.active) as inactive,
		u.name,
		u.username,
		u.user_email
	FROM {$tbl} b
	LEFT JOIN {$K->SQL->TBL->users} u ON (u.user_id=b.cid )
	GROUP BY u.name, u.username, u.user_email");

	$K->OUT->display('Our_Sponsors/admin/index');
}
