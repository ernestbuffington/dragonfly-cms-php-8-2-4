<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2009 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
if (is_user() && \Dragonfly\Modules::isActive('News')) {
	// Last 10 Comments
	$result = $db->query("SELECT
		tid,
		sid,
		title
	FROM {$db->TBL->comments}
	INNER JOIN {$db->TBL->stories} USING (sid)
	WHERE user_id = {$userinfo['user_id']}
	ORDER BY tid DESC LIMIT 10");
	if ($result->num_rows) {
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->assign_vars(array(
			'COMMENTS_TITLE' => $username.'\'s '._LAST10COMMENT,
		));
		while (list($tid, $sid, $subject) = $result->fetch_row()) {
			$OUT->assign_block_vars('comment', array(
				'URL'     => URL::index('News&file=article&sid='.$sid.'#'.$tid),
				'SUBJECT' => $subject
			));
		}
		$OUT->display('your_account/blocks/comments');
	}
}
