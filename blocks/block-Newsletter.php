<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2023 by CPG-Nuke Dev Team
  https://www.dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/blocks/block-Newsletter.php,v $
  $Revision: 9.11 $
  $Author: phoenix $
  $Date: 2007/08/30 04:54:29 $
Encoding test: n-array summation ∑ latin ae w/ acute ǽ
********************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $db, $user_prefix, $userinfo, $SESS;

if (is_user()) {
	$newsletter = $userinfo['newsletter'];
	$user_id = $userinfo['user_id'];
	if ($newsletter) {
		$message = _NEWSLETTERBLOCKSUBSCRIBED;
		$action = '<form action="'.get_uri().'" method="post"><div><input type="submit" name="nb_unsubscribe" value="'._NEWSLETTERBLOCKUNSUBSCRIBE.'" /></div></form>';
		if (isset($_POST['nb_unsubscribe'])) {
			$db->sql_query("UPDATE ".$user_prefix."_users SET newsletter='0' WHERE user_id='$user_id'");
			unset($_SESSION['CPG_USER']);
			url_redirect($uri);
		}
	} else {
		$message = _NEWSLETTERBLOCKNOTSUBSCRIBED;
		$action = '<form action="'.get_uri().'" method="post"><div><input type="submit" name="nb_subscribe" value="'._NEWSLETTERBLOCKSUBSCRIBE.'" /></div></form>';
		if (isset($_POST['nb_subscribe'])) {
			$db->sql_query("UPDATE ".$user_prefix."_users SET newsletter='1' WHERE user_id='$user_id'");
			unset($_SESSION['CPG_USER']);
			url_redirect($uri);
		}
	}
} else {
	$message = _NEWSLETTERBLOCKREGISTER;
	$action = '<a href="'.getlink('Your_Account&amp;file=register').'" title="'._NEWSLETTERBLOCKREGISTERNOW.'">'._NEWSLETTERBLOCKREGISTERNOW.'</a>';
}

$content = '<div style="text-align:center;"><img src="images/blocks/newsletter.png" alt="'._NEWSLETTER.'" title="'._NEWSLETTER.'" /><br /><br />'.$message.'<br /><br />'.$action.'</div>';
