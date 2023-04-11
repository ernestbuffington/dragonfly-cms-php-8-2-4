<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Modules\Surveys;

class Comments
{

	public static function moderate()
	{
		if (\Dragonfly\Comments\Comment::canModerate()) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$ID  = \Dragonfly::getKernel()->IDENTITY->id;
			$TBL = $SQL->TBL->pollcomments_scores;
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
					$SQL->query("UPDATE {$SQL->TBL->pollcomments} SET
						score = (SELECT FLOOR(SUM(comment_score) / COUNT(*)) FROM {$TBL} WHERE comment_id = {$tid})
					WHERE tid = {$tid}");
				}
			}
		}
	}

	public static function reply($poll_id)
	{
		$K = \Dragonfly::getKernel();
		if (!$K->IDENTITY->isMember()) {
			cpg_error(_NOANONCOMMENTS);
		}
		$db = $K->SQL;
		$pid = (int)$_GET->uint('reply');
		$reply_to = $db->uFetchAssoc("SELECT
			date, user_id, username nickname, comment body
		FROM {$db->TBL->pollcomments}
		LEFT JOIN {$db->TBL->users} USING (user_id)
		WHERE poll_id = {$poll_id} AND tid = {$pid}");
		if (!$reply_to) {
			\Poodle\Report::error(404);
		}
		$reply_to['title'] = _SURVEYCOM;
		\Dragonfly\BBCode::pushHeaders();
		\Dragonfly\Comments\Comment::replyForm('', $reply_to);
	}

	public static function replyPost($poll_id)
	{
		$K = \Dragonfly::getKernel();
		if (!$K->IDENTITY->isMember()) {
			cpg_error(_NOANONCOMMENTS);
		}
		$db = $K->SQL;
		$comment = strip_tags(\Dragonfly\BBCode::encode(htmlprepare(check_words($_POST['comment']))));
		if ($comment) {
			$db->TBL->pollcomments->insert(array(
				'pid'       => (int)$_GET->uint('reply'),
				'poll_id'   => $poll_id,
				'date'      => time(),
				'remote_ip' => $_SERVER['REMOTE_ADDR'],
				'comment'   => $comment,
				'user_id'   => $K->IDENTITY->id,
			));
			\URL::redirect(\URL::index("&pollid={$poll_id}&op=results"));
		}
	}

	public static function replyPreview()
	{
		$ID = \Dragonfly::getKernel()->IDENTITY;
		\Dragonfly\BBCode::pushHeaders();
		\Dragonfly\Comments\Comment::replyForm($_POST['comment'], array(
			'nickname' => $ID->isMember() ? $ID->nickname : _ANONYMOUS,
			'date' => time(),
			'body' => \Dragonfly\BBCode::encode(htmlprepare($_POST['comment'])),
			'title' => _SURVEYCOMPRE,
		));
	}

}
