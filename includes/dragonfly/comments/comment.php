<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Comments;

/**
 * Base class for Comments systems
 */

abstract class Comment
{
	public
		$id         = 0,
		$parent_id  = 0,
		$date       = 0,
		$remote_ip  = '',
		$body       = '',
		$score      = 0,

		$author     = null, // array()
		$reply_uri  = '',
		$delete_uri = '';

	protected
		$comments;

	function __construct(array $data=array())
	{
		if ($data) {
			$this->id           = (int) $data['id'];
			$this->parent_id    = (int) $data['parent_id'];
			$this->date         = (int) $data['date'];
			$this->remote_ip    = $data['remote_ip'];
			$this->body         = $data['body'];
			$this->score        = (int) $data['score'];

			if (1 < $data['user_id']) {
				$this->author = getusrdata((int)$data['user_id'], 'username');
				$this->author['profile_uri'] = \Dragonfly\Identity::getProfileURL($data['user_id']);
			}

			if (\Dragonfly::getKernel()->IDENTITY->isAdmin() && preg_match('/.*Dragonfly\\\\Modules\\\\([^\\\\]+)\\\\.+$/D', get_class($this), $m)) {
				$this->delete_uri = \URL::admin("{$m[1]}&del_comment={$this->id}");
			}
		}
	}

	abstract function __get($k);

	public static function canModerate()
	{
		static $allow;
		if (is_null($allow)) {
			$K = \Dragonfly::getKernel();
			$m = $K->CFG->global->moderate;
			$allow = $m && $K->IDENTITY->isMember() && (2 == $m || $K->IDENTITY->isAdmin());
		}
		return $allow;
	}

	public function moderate()
	{
		global $MAIN_CFG;
		if (static::canModerate()) {
			return array(
				'name' => "mod_comments[{$this->id}]",
				'options' => array(
					array('value' => '', 'label' => ''),
					array('value' => -1, 'label' => _REASONS_2),
					array('value' => 0, 'label' => _REASONS_1),
					array('value' => 1, 'label' => 'On-topic'),
					array('value' => 2, 'label' => _REASONS_7),
					array('value' => 3, 'label' => 'Spotlight')
				)
			);
		}
		return false;
	}

	public static function replyForm($comment = '', array $reply_to = array())
	{
		if (isset($reply_to['user_id']) && 2 > $reply_to['user_id']) {
			$reply_to['nickname'] = _ANONYMOUS;
		}
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->comment_reply = array('comment' => $comment);
		$OUT->comment_reply_to = $reply_to;
		$OUT->display('dragonfly/comments/form');
	}

}
