<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $db, $userinfo;

if (is_user()) {
	$newsletter = $userinfo['newsletter'];
	$user_id = $userinfo['user_id'];
	if ($newsletter) {
		$message = _NEWSLETTERBLOCKSUBSCRIBED;
		$action = '<form action="" method="post"><div><input type="submit" name="nb_unsubscribe" value="'._NEWSLETTERBLOCKUNSUBSCRIBE.'" /></div></form>';
		if (isset($_POST['nb_unsubscribe'])) {
			$db->query("UPDATE {$db->TBL->users} SET newsletter=0 WHERE user_id='$user_id'");
			$userinfo['newsletter'] = 0;
			URL::redirect($uri);
		}
	} else {
		$message = _NEWSLETTERBLOCKNOTSUBSCRIBED;
		$action = '<form action="" method="post"><div><input type="submit" name="nb_subscribe" value="'._NEWSLETTERBLOCKSUBSCRIBE.'" /></div></form>';
		if (isset($_POST['nb_subscribe'])) {
			$db->query("UPDATE {$db->TBL->users} SET newsletter='1' WHERE user_id='$user_id'");
			$userinfo['newsletter'] = 1;
			URL::redirect($uri);
		}
	}
} else if ($url = \Dragonfly\Identity::getRegisterURL()) {
	$message = _NEWSLETTERBLOCKREGISTER;
	$action = '<a rel="nofollow" href="'.htmlspecialchars($url).'" title="'._NEWSLETTERBLOCKREGISTERNOW.'">'._NEWSLETTERBLOCKREGISTERNOW.'</a>';
} else {
	$message = $action = '';
}

$content = '<div style="text-align:center;"><img src="images/blocks/newsletter.png" alt="'._NEWSLETTER.'" title="'._NEWSLETTER.'" /><br /><br />'.$message.'<br /><br />'.$action.'</div>';
