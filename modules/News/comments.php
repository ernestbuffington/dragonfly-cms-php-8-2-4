<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Modules\News;

class Comments
{

	public static function moderate()
	{
		if (\Dragonfly\Comments\Comment::canModerate()) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$ID  = \Dragonfly::getKernel()->IDENTITY->id;
			$TBL = $SQL->TBL->comments_scores;
			foreach ($_POST['mod_comments'] as $tid => $val) {
				if (strlen($val)) {
					$tid = (int) $tid;
					$val = max(-1, min(3, $val));
					try {
						$TBL->insert(array(
							'comment_id' => $tid,
							'identity_id' => $ID,
							'comment_score' => $val
						));
					} catch (\Exception $e) {
						$TBL->update(array('comment_score' => $val), array(
							'comment_id' => $tid,
							'identity_id' => $ID
						));
					}
					$SQL->query("UPDATE {$SQL->TBL->comments} SET
						score = (SELECT FLOOR(SUM(comment_score) / COUNT(*)) FROM {$TBL} WHERE comment_id = {$tid})
					WHERE tid = {$tid}");
				}
			}
		}
	}

	public static function reply($sid)
	{
		$K = \Dragonfly::getKernel();
		if (!$K->IDENTITY->isMember()) {
			cpg_error($K->L10N['_NOANONCOMMENTS']);
		}
		$db = $K->SQL;
		$pid = (int)$_GET->uint('reply');
		$reply_to = $db->uFetchAssoc("SELECT
			date, user_id, username nickname, comment body
		FROM {$db->TBL->comments}
		LEFT JOIN {$db->TBL->users} USING (user_id)
		WHERE sid = {$sid} AND tid = {$pid}");
		if (!$reply_to) {
			\Poodle\Report::error(404);
		}
		$reply_to['title'] = $K->L10N['_COMMENTREPLY'];
		\Dragonfly\BBCode::pushHeaders();
		\Dragonfly\Comments\Comment::replyForm('', $reply_to);
	}

	public static function replyPost($sid)
	{
		$K = \Dragonfly::getKernel();
		if (!$K->IDENTITY->isMember()) {
			cpg_error($K->L10N['_NOANONCOMMENTS']);
		}
		$db = $K->SQL;
		$comment = strip_tags(\Dragonfly\BBCode::encode(htmlprepare(check_words($_POST['comment']))));
		if ($comment) {
			$db->TBL->comments->insert(array(
				'pid'       => (int)$_GET->uint('reply'),
				'sid'       => $sid,
				'date'      => time(),
				'remote_ip' => $_SERVER['REMOTE_ADDR'],
				'comment'   => $comment,
				'user_id'   => $K->IDENTITY->id,
			));
			$db->query("UPDATE {$db->TBL->stories} SET comments = comments+1 WHERE sid = {$sid}");
			\URL::redirect(\URL::index("&file=article&sid={$sid}"));
		}
	}

	public static function replyPreview()
	{
		$K = \Dragonfly::getKernel();
		$ID = $K->IDENTITY;
		\Dragonfly\BBCode::pushHeaders();
		\Dragonfly\Comments\Comment::replyForm($_POST['comment'], array(
			'nickname' => $ID->isMember() ? $ID->nickname : _ANONYMOUS,
			'date' => time(),
			'body' => \Dragonfly\BBCode::encode(htmlprepare($_POST['comment'])),
			'title' => $K->L10N['_COMREPLYPRE'],
		));
	}

}
