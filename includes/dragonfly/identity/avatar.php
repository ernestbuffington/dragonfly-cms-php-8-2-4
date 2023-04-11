<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Identity;

class Avatar
{
	const
		TYPE_NONE    = 0, // USER_AVATAR_NONE
		TYPE_UPLOAD  = 1, // USER_AVATAR_UPLOAD
		TYPE_REMOTE  = 2, // USER_AVATAR_REMOTE
		TYPE_GALLERY = 3; // USER_AVATAR_GALLERY

	public static function getURL($identity, $return_default = true)
	{
		if ($identity instanceof \Dragonfly\Identity) {
			$type   = $identity->avatar_type;
			$avatar = $identity->avatar;
			$allow  = $identity->allowavatar;
		} else {
			$type   = empty($identity['user_avatar_type']) ? static::TYPE_NONE : $identity['user_avatar_type'];
			$avatar = empty($identity['user_avatar']) ? null : $identity['user_avatar'];
			$allow  = !empty($identity['user_allowavatar']);
		}
		$CFG = \Dragonfly::getKernel()->CFG->avatar;
		if ($allow && $type && $avatar) {
			if (static::TYPE_UPLOAD == $type && $CFG->allow_upload) {
				return DOMAIN_PATH . $CFG->path.'/'.$avatar;
			}
			if (static::TYPE_REMOTE == $type && $CFG->allow_remote) {
				return $avatar;
			}
			if (static::TYPE_GALLERY == $type && $CFG->allow_local) {
				return DF_STATIC_DOMAIN . $CFG->gallery_path.'/'.$avatar;
			}
		}
		# Default Avatar ?
		return $return_default ? DF_STATIC_DOMAIN . $CFG->gallery_path.'/'.$CFG->default : false;
	}

}
