<?php
/*
	Dragonfly™ CMS, Copyright © since 2016
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Modules\Private_Messages;

class Message
{
	const
		STATUS_NEW       = 0,
		STATUS_SENT      = 1,
		STATUS_UNREAD    = 1,
		STATUS_READ      = 2,
		STATUS_SAVED     = 3,
		STATUS_DELETED   = 4;

	public
		// table privatemessages
		$id = 0,
		$status = 0,
		$subject = '',
		$user_id = 0,
		$username = '',
		$date = 0,
		$ip = '',
		$enable_bbcode = true,
		$enable_smilies = true,
		$text = '',
		// table privatemessages_recipients
		$recipients = array();

	function __construct($id = 0)
	{
		if ($id) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$row = $SQL->uFetchAssoc("SELECT
				pm.*,
				username
			FROM {$SQL->TBL->privatemessages} pm
			INNER JOIN {$SQL->TBL->users} u USING (user_id)
			WHERE pm_id = {$id}");
			if (!$row) {
				throw new \Exception('Private message not found');
			}
			$this->id             = (int) $row['pm_id'];
			$this->status         = (int) $row['pm_status'];
			$this->subject        = $row['pm_subject'];
			$this->user_id        = (int) $row['user_id'];
			$this->username       = $row['username'];
			$this->date           = (int) $row['pm_date'];
			$this->ip             = \Dragonfly\Net::decode_ip($row['pm_ip']);
			$this->enable_bbcode  = !!$row['pm_enable_bbcode'];
			$this->enable_smilies = !!$row['pm_enable_smilies'];
			$this->text           = $row['pm_text'];

			$this->recipients = $SQL->uFetchAll("SELECT
				user_id    id,
				pmr_status status,
				username
			FROM {$SQL->TBL->privatemessages_recipients}
			INNER JOIN {$SQL->TBL->users} USING (user_id)
			WHERE pm_id = {$id}");
		} else {
			$ID = \Dragonfly::getKernel()->IDENTITY;
			$this->user_id        = $ID->id;
			$this->date           = time();
			$this->ip             = $_SERVER['REMOTE_ADDR'];
			$this->enable_bbcode  = $ID->allowbbcode;
			$this->enable_smilies = $ID->allowsmile;
		}
	}

	public function getRecipient($user_id)
	{
		foreach ($this->recipients as $recipient) {
			if ($user_id == $recipient['id']) {
				return $recipient;
			}
		}
	}

	public function html()
	{
		$PMCFG = \Dragonfly::getKernel()->CFG->Private_Messages;
		$html = $this->text;
		if ($PMCFG->allow_bbcode && $this->enable_bbcode) {
			$html = \Dragonfly\BBCode::decode($html, 1, false);
		}
		if ($PMCFG->allow_smilies && $this->enable_smilies) {
			$html = \Dragonfly\Smilies::parse($html);
		}
		return \URL::makeClickable($html);
	}

	public function save()
	{
		$recipients_ids = array();
		foreach ($this->recipients as $recipient) {
			$id = (int) (is_array($recipient) ? $recipient['id'] : $recipient);
			if ($id) { $recipients_ids[] = $id; }
		}
		if (!$recipients_ids) {
			throw new \Exception('No recipients');
		}

		$TBL = \Dragonfly::getKernel()->SQL->TBL;
		if ($this->id) {
			$TBL->privatemessages->update(array(
				'pm_subject' => $this->subject,
				'pm_ip' => $_SERVER['REMOTE_ADDR'],
				'pm_enable_bbcode' => $this->enable_bbcode,
				'pm_enable_smilies' => $this->enable_smilies,
				'pm_text' => $this->text,
			), "pm_id = {$this->id}");
/*			foreach ($recipients_ids as $recipient) {
				$TBL->privatemessages_recipients->update(array(
					'pm_id'      => $privmsg_sent_id,
					'user_id'    => $to_userinfo['user_id'],
				));
			}
*/
		} else {
			$this->id = $TBL->privatemessages->insert(array(
				'user_id' => $this->user_id,
				'pm_status' => static::STATUS_NEW,
				'pm_subject' => $this->subject,
				'pm_date' => $this->date,
				'pm_ip' => $_SERVER['REMOTE_ADDR'],
				'pm_enable_bbcode' => $this->enable_bbcode,
				'pm_enable_smilies' => $this->enable_smilies,
				'pm_text' => $this->text,
			), 'pm_id');
			foreach ($recipients_ids as $id) {
				$TBL->privatemessages_recipients->insert(array(
					'pm_id' => $this->id,
					'user_id' => $id,
					'pmr_status' => static::STATUS_NEW
				));
			}
		}
	}

}
