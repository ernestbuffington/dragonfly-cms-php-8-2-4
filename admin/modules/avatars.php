<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/* Applied rules:
 * AddDefaultValueForUndefinedVariableRector (https://github.com/vimeo/psalm/blob/29b70442b11e3e66113935a2ee22e165a70c74a4/docs/fixing_code.md#possiblyundefinedvariable)
 */
 
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('settings')) { cpg_error('Access Denied'); }

abstract class Dragonfly_Admin_Avatars
{

	public static function GET()
	{
		$avatar_usage = [];
  $K = \Dragonfly::getKernel();

		// This is the variable that points to the path of the avatars
		// You may need to adjust this to meet your needs ;)
		$real_avatar_dir = $K->CFG->avatar->path;

		// Select all avatars and usernames that have an uploaded avatar currently
		$result = $K->SQL->query("SELECT user_avatar, user_id, username
			FROM {$K->SQL->TBL->users}
			WHERE user_avatar_type = 1
			  AND user_avatar IS NOT NULL");
		while ($row = $result->fetch_row()) {
			$avatar_usage[$row[0]] = array($row[1], $row[2]);
		}

		if (isset($_GET['delete'])) {
			$target = $real_avatar_dir.'/'.$_GET['delete'];
			if (is_file($target)) {
				if (!isset($avatar_usage[$_GET['delete']]) && unlink($target) ) {
					exit('Success, deleted '.htmlspecialchars($target).'!');
				}
			}
			exit('FAILED to delete '.htmlspecialchars($target).'!');
		}

		// This is where we go through the avatar directory and report whether they are not
		// used or if they are used, by who.
		if ($avatar_dir = opendir($real_avatar_dir)) {
			$K->OUT->avatars = array();
			$K->L10N->load('Your_Account');

			$avatars = array();
			while ($file = readdir($avatar_dir)) {
				// This is where the script will filter out any file that doesn't match the patterns
				if ($file != "." && $file != ".." && preg_match("#\.(gif|jpg|jpeg|png)$#",$file)) {
					$avatars[] = (isset($avatar_usage[$file])?'1':'0') . $file;
				}
			}
			$count = count($avatars);

			$start = (int)$_GET->uint('start');
			$limit = 40;

			sort($avatars);
			$avatars = array_slice($avatars, $start, $limit);
			foreach ($avatars as $file)
			{
				$file = substr($file,1);
				$size = filesize($real_avatar_dir.'/'.$file);
				// Alternating row colows code
				$delete_url = $edit_url = null;
				if (isset($avatar_usage[$file])) {
					// Bingo, someone is using this avatar
					// Since we need to supply a link with a valid sid later in html, let's build it now
					$username = $avatar_usage[$file][1];
					$edit_url = URL::admin("users&id={$avatar_usage[$file][0]}&edit=avatar");
				} else {
					// Not used, safe to display delete link for admin
					$username = _NONE;
					$delete_url = URL::admin("avatars&delete={$file}");
				}
				$K->OUT->avatars[] = array(
					'AVATAR_IMG' => "{$real_avatar_dir}/{$file}",
					'FILENAME' => $file,
					'SIZE' => $size,
					'USERNAME' => $username,
					'EDIT' => $edit_url,
					'DELETE' => $delete_url,
				);
			}

			$K->OUT->avatars_pagination = new \Poodle\Pagination(URL::admin('avatars&start=${offset}'), $count, $start, $limit);
			$K->OUT->display('admin/avatars');

		} else {
			// If we made it to this else there was a problem trying to read the avatar directory
			// If you see this error message check this variable:
			// $real_avatar_dir -> This may be set incorrectly for your site.
			cpg_error("Avatar directory unavailable!");
		}
	}
}

Dragonfly_Admin_Avatars::{$_SERVER['REQUEST_METHOD']}();
