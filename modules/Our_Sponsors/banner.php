<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Modules\Our_Sponsors;

class Banner
{
/*
	protected
		$bid         = 0,
		$cid         = 0,
		$imptotal    = 0,
		$impmade     = 0,
		$clicks      = 0,
		$imageurl    = '', // length="100"
		$clickurl    = '', // length="200"
		$alttext     = '', // length="255"
		$date        = 0,
		$dateend     = 0,
		$type        = 0,
		$active      = true,
		$textban     = false,
		$text_width  = 0,
		$text_height = 0,
		$text_title  = '', // length="100"
		$text_bg     = 'FFFFFF',
		$text_clr    = '000000';
*/

	public static function getRandom()
	{
		//if (is_admin()) { URL::admin( ''; }
		$db = \Dragonfly::getKernel()->SQL;
		if (!isset($db->TBL->banner)) { return; }
		$banner = $db->uFetchAssoc("SELECT * FROM {$db->TBL->banner} WHERE type = 0 AND active = 1 ORDER BY RAND()");
		if (!$banner) { return; }
		if (!is_admin()) {
			$db->query("UPDATE {$db->TBL->banner} SET impmade = impmade + 1 WHERE bid = {$banner['bid']}");
		}
		/* Check if this impression is the last one and print the banner */
		if ($banner['imptotal'] && $banner['imptotal'] <= $banner['impmade']) {
			global $MAIN_CFG;
			$db->query("UPDATE {$db->TBL->banner} SET active = 0 WHERE bid = {$banner['bid']}");
			$row = $db->uFetchAssoc('SELECT username, user_email FROM '.$db->TBL->users." WHERE user_id = {$banner['cid']}");
			$to_name = $row['username'];
			$message = _HELLO." $to_name,\n\n"
				._THISISAUTOMATED."\n\n"
				._THERESULTS."\n\n"
				.BANNER_ID.": {$banner['bid']}\n"
				._TOTALIMPRESSIONS." {$banner['imptotal']}\n"
				._CLICKSRECEIVED." {$banner['clicks']}\n"
				._IMAGEURL." {$banner['imageurl']}\n"
				._CLICKURL." {$banner['clickurl']}\n"
				._ALTERNATETEXT." {$banner['alttext']}\n\n"
				._TEXT_TITLE.": {$banner['text_title']}\n\n"
				._HOPEYOULIKED."\n\n"
				._THANKSUPPORT."\n\n"
				."- {$MAIN_CFG->global->sitename}\n{$MAIN_CFG->global->nukeurl}";
			\Dragonfly\Email::send($mailer_message, "{$MAIN_CFG->global->sitename}: "._BANNERSFINNISHED, $message, $row['user_email'], $row['username']);
		}
		if ($banner['textban']) {
			return '<div style="text-align:center; margin:auto; width:'.$banner['text_width'].'px; height:'.$banner['text_height'].'px; '.(!empty($banner['text_bg']) ? 'background-color:#'.$banner['text_bg'].';"' : '"').'><a href="banners.php?bid='.$banner['bid'].'"'.(!empty($banner['text_clr']) ? ' style="color:#'.$banner['text_clr'].';"' : '').' target="_blank">'.$banner['text_title'].'</a></div>';
		} else {
			return '<a href="banners.php?bid='.$banner['bid'].'" target="_blank"><img src="'.$banner['imageurl'].'" style="border:0;" alt="'.$banner['alttext'].'" title="'.$banner['alttext'].'" /></a>';
		}
	}

}

