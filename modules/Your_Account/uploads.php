<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\Modules\Your_Account;

class Uploads
{

	public static function get()
	{
		require_once(__DIR__ . '/functions.php');
		display_member_block();
		\Dragonfly\Page::title('My Uploads', false);

		$K = \Dragonfly::getKernel();

		$upload_size = $K->IDENTITY->getUploadUsage();
		$upload_limit = $K->IDENTITY->getUploadQuota();
		$upload_limit_pct = $upload_limit ? $upload_size * 100 / $upload_limit : 0;

		$K->OUT->UPLOAD_LIMIT_PERCENT = round($upload_limit_pct,1).'%';
		$K->OUT->UPLOAD_LIMIT_METER   = $upload_limit_pct/100;
		$K->OUT->S_UPLOAD_SIZE        = $K->OUT->L10N->filesizeToHuman($upload_size);
		$K->OUT->S_UPLOAD_LIMIT       = $K->OUT->L10N->filesizeToHuman($upload_limit);

		// Show all uploads
		// Call module_id function to upload location
		$K->OUT->my_uploads = $K->SQL->query("SELECT
			f.upload_id,
			m.title module_title,
			f.upload_time,
			f.upload_size,
			f.upload_file,
			f.upload_name
		FROM {$K->SQL->TBL->users_uploads} f
		LEFT JOIN {$K->SQL->TBL->modules} m ON (m.mid = f.module_id)
		WHERE identity_id = {$K->IDENTITY->id}
		ORDER BY f.upload_time DESC");
		$K->OUT->display('Your_Account/uploads');
	}

}

if (is_user()) {
	Uploads::{$_SERVER['REQUEST_METHOD']}();
} else {
	\URL::redirect(\Dragonfly\Identity::loginURL());
}
