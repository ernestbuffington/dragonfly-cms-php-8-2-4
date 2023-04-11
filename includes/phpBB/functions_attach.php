<?php
/*********************************************
  CPG-NUKE: Advanced Content Management System
  ********************************************
  Copyright (c) 2004 by CPG-Nuke Dev Team
  http://www.cpgnuke.com

  CPG-Nuke is released under the terms and conditions
  of the GNU GPL version 2 or any later version
***********************************************************************/

//
// Per Forum based Extension Group Permissions
// Stored in table extension_groups.forum_permissions
//
function auth_pack($auth_array)
{
	$auth_array = array_flip(array_map('intval', $auth_array));
	unset($auth_array[0]);
	return implode(',', array_keys($auth_array));
}

// Reverse the auth_pack process
function auth_unpack($auth_cache)
{
	// Old method ?
	if (strpos($auth_cache,'#') || strpos($auth_cache,'.')) {
		$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+-';
		$base = strlen($chars);
		$auth = array();
		$auth_len = 1;
		$auth_cl = strlen($auth_cache);
		for ($pos = 0; $pos < $auth_cl; $pos += $auth_len) {
			$forum_auth = substr($auth_cache, $pos, 1);
			if ('#' == $forum_auth) {
				$auth_len = 1;
			} else if ('.' == $forum_auth) {
				$auth_len = 2;
				--$pos;
			} else {
				$forum_auth = substr($auth_cache, $pos, $auth_len);
				// base64_unpack
				$forum_id = 0;
				for ($i = 1; $i <= $auth_len; ++$i) {
					$forum_id += (strpos($chars, substr($forum_auth, $auth_len-$i, 1)) * pow($base, $i-1));
				}
				$auth[] = $forum_id;
			}
		}
		return $auth;
	}
	return array_map('intval', explode(',', $auth_cache));
}

// Used for determining if Forum ID is authed, please use this Function on all Posting Screens
function is_forum_authed($auth_cache, $check_forum_id)
{
	return in_array($check_forum_id, auth_unpack($auth_cache));
}

function amod_realpath($path)
{
	return realpath($path) ?: $path;
}

function attachment_inc_download_count($attach_id)
{
	\Dragonfly::getKernel()->SQL->query('UPDATE ' . ATTACHMENTS_DESC_TABLE . '
	SET download_count = download_count + 1
	WHERE attach_id = ' . intval($attach_id));
}
