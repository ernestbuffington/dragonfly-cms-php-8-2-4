<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   https://dragonfly.coders.exchange/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

require(__DIR__ . '/include/load.inc');

/**
 * Local functions definition
 */

function delete_picture($pid)
{
	if (Dragonfly::isDemo()){
		cpg_error(PERM_DENIED, 403);
	}

	global $module_name, $CONFIG;
	$db = \Dragonfly::getKernel()->SQL;

	if (is_array($pid)) {
		$pic = $pid;
		$pid = (int) $pic['pid'];
	} else {
		$pid = (int) $pid;
		if (can_admin($module_name)) {
			$pic = $db->uFetchAssoc("SELECT upload_id, aid, filepath, filename FROM {$CONFIG['TABLE_PICTURES']} WHERE pid = {$pid}");
		} else {
			$pic = $db->uFetchAssoc("SELECT upload_id, p.aid, filepath, filename, owner_id, a.user_id album_user_id
			FROM {$CONFIG['TABLE_PICTURES']} p
			LEFT JOIN {$CONFIG['TABLE_ALBUMS']} a ON a.aid = p.aid
			WHERE pid = {$pid}");
			// Deny when picture or album is not from current user
			if ($pic && USER_ID != $pic['owner_id'] && USER_ID != $pic['album_user_id']) {
				cpg_error(PERM_DENIED, 403);
			}
		}
		if (!$pic) {
			cpg_error(NON_EXIST_AP, 404);
		}
	}

	$dir = $pic['filepath'];
	if (!is_writable($dir)) {
		cpg_error(sprintf(DIRECTORY_RO, htmlprepare($dir)));
	}

	$file = $pic['filename'];
	$files = array(
		'f' => $dir . $file,
		'n' => $dir . $CONFIG['normal_pfx'] . $file,
		't' => $dir . $CONFIG['thumb_pfx'] . $file
	);
	$file = preg_replace('/\\.[^.]+$/','.exif', $dir . $file);
	if (is_file($file)) {
		unlink($file);
	}
	foreach ($files as $t => $file) {
		$pic["del_{$t}"] = unlink($file);
	}

	$CONFIG['TABLE_COMMENTS']->delete("pid = {$pid}");
	$CONFIG['TABLE_PICTURES']->delete("pid = {$pid}");
	$db->TBL->users_uploads->delete("upload_id = {$pic['upload_id']}");

	return $pic;
}

function delete_album($id)
{
	global $CONFIG;

	// Delete all pictures
	$result = \Dragonfly::getKernel()->SQL->query("SELECT pid, upload_id, aid, filepath, filename FROM {$CONFIG['TABLE_PICTURES']} WHERE aid = {$id}");
	$pictures = array();
	if ($result->num_rows) {
		while ($pic = $result->fetch_assoc()) {
			$pictures[] = delete_picture($pic);
		}
		speedup_pictures();
	}

	// Delete album
	$CONFIG['TABLE_ALBUMS']->delete("aid = {$id}");

	return $pictures;
}

/**
 * Main code starts here
 */

// User
if (isset($_GET['user'])) {
	if (!can_admin($module_name) || Dragonfly::isDemo()) {
		cpg_error(PERM_DENIED, 403);
	}
	$user_id = $_GET->uint('user');
	if (!$user_id) {
		cpg_error('Not found', 404);
	}

	list($username) = $db->uFetchRow("SELECT username FROM {$db->TBL->users} WHERE user_id = {$user_id}");
	if (!$username) {
		cpg_error('Not found', 404);
	}

	$redirect = URL::admin('&file=users');
	if (isset($_POST['cancel'])) {
		URL::redirect($redirect);
	} else if (isset($_POST['confirm'])) {
		pageheader(DEL_USER);
		echo '<table>
			<thead><tr>
				<th colspan="6">'.DEL_USER." - {$username}".'</th>
			</tr></thead>';
		// First delete the albums
		$result = $db->query("SELECT aid FROM {$CONFIG['TABLE_ALBUMS']} WHERE user_id = {$user_id}");
		while ($album_data = $result->fetch_row()) {
			delete_album($album_data[0]);
		}
		$result->free();

		// Then anonymize comments posted by the user
		$db->exec("UPDATE {$CONFIG['TABLE_COMMENTS']} SET author_id = 0 WHERE author_id = {$user_id}");
		// Do the same for pictures uploaded in public albums
		$db->exec("UPDATE {$CONFIG['TABLE_PICTURES']} SET owner_id = 0 WHERE owner_id = {$user_id}");
		// suspend user instead
		$db->exec("UPDATE {$db->TBL->users} SET user_level=0, susdel_reason='".PHOTOGALLERY."' WHERE user_id = {$user_id}");

		echo '<tfoot><tr>
			<td colspan="6">
				<a class="button" href="' . htmlspecialchars($redirect). '">'.CONTINU.'</a></div>
			</td>
		</tr></tfoot></table>';
		pagefooter();
	} else {
		\Dragonfly\Page::confirm('', DEL_USER . ' - ' . $username . '<br/>'.USER_CONFIRM_DEL);
	}
}

// Album
else if (isset($_POST['album']) || isset($_GET['album'])) {
	$album = $_POST->uint('album') ?: $_GET->uint('album');
	if (!$album) {
		cpg_error(NON_EXIST_AP, 404);
	}
	if (can_admin($module_name)) {
		$album_data = $db->uFetchAssoc("SELECT aid, title, category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = {$album}");
	} else if (USER_ADMIN_MODE) {
		$album_data = $db->uFetchAssoc("SELECT aid, title, category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = {$album} AND user_id = ".USER_ID);
	} else {
		cpg_error(ACCESS_DENIED, 403);
	}
	if (!$album_data) {
		cpg_error(NON_EXIST_AP, 404);
	}
	$redirect = can_admin($module_name) ? URL::index("&file=albmgr&cat={$album_data['category']}") : URL::index("&cat={$album_data['category']}");
	if (isset($_POST['cancel'])) {
		URL::redirect($redirect);
	} else if (isset($_POST['confirm'])) {
		pageheader(DEL_ALB);
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->uri_redirect = $redirect;
		$OUT->del_pictures = delete_album($album_data['aid']);
		$OUT->del_album_msg = sprintf(ALB_DEL_SUCCESS, $album_data['title']);
		$OUT->display('coppermine/delete-album');
		pagefooter();
	} else {
		\Dragonfly\Page::confirm('', CONFIRM_DELETE1 . '<br/>' . CONFIRM_DELETE2, array(array('name'=>'album','value'=>$album)));
	}
}

// Comment
else if (isset($_POST['comment'])) {
	$msg_id = $_POST->uint('comment');
	if (!$msg_id) {
		cpg_error(NON_EXIST_COMMENT, 404);
	}
	$comment_data = $db->uFetchAssoc("SELECT pid FROM {$CONFIG['TABLE_COMMENTS']} WHERE msg_id={$msg_id}");
	if (!$comment_data) {
		cpg_error(NON_EXIST_COMMENT, 404);
	}
	$redirect = URL::index("&file=displayimage&pid={$comment_data['pid']}");
	if (isset($_POST['cancel'])) {
		URL::redirect($redirect);
	} else if (isset($_POST['confirm'])) {
		$where = "msg_id={$msg_id}";
		if (!is_admin()) {
			if (is_user()) {
				$where .= " AND author_id=".is_user();
			} else {
				$where .= " AND author_id=0 AND author_md5_id='".md5(session_id())."'";
			}
		}
		$CONFIG['TABLE_COMMENTS']->delete($where);

		\Poodle\Notify::success(COMMENT_DELETED);
		\URL::redirect($redirect);
	} else {
		\Dragonfly\Page::confirm(URL::index("&file=delete"), CONFIRM_DELETE_COM, array(array('name'=>'comment','value'=>$msg_id)));
	}
}

// Picture
else if (isset($_POST['picture'])) {
	if (!can_admin($module_name) && !USER_ADMIN_MODE) {
		cpg_error(ACCESS_DENIED, 403);
	}
	$pid = $_POST->uint('picture');
	if (!$pid) {
		cpg_error(NON_EXIST_AP, 404);
	}
	if (isset($_POST['cancel'])) {
		URL::redirect(URL::index("&file=displayimage&pid={$pid}"));
	} else if (isset($_POST['confirm'])) {
		pageheader(DEL_PIC);
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->del_picture = delete_picture($pid);
		$OUT->uri_redirect = URL::index("&file=thumbnails&album={$OUT->del_picture['aid']}");
		$OUT->display('coppermine/delete-picture');
		speedup_pictures();
		pagefooter();
	} else {
		\Dragonfly\Page::confirm(URL::index("&file=delete"), PIC_CONFIRM_DEL, array(array('name'=>'picture','value'=>$pid)));
	}
}

else {
	cpg_error("command not found", 404);
}
