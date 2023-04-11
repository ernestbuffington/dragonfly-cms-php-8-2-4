<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Identity;

abstract class Create
{

	// Send email to admin
	public static function notifyAdmin($nickname)
	{
		$CFG = \Dragonfly::getKernel()->CFG;
		if ($CFG->member->sendaddmail) {
			if (!\Dragonfly\Email::send(
				$mailer_message,
				"{$CFG->global->sitename} - Member Added",
				"{$nickname} has been added to {$CFG->global->sitename}.\n\nIP: {$_SERVER['REMOTE_ADDR']}\nUser agent: {$_SERVER['HTTP_USER_AGENT']}"))
			{
				\Poodle\LOG::error('mail',$mailer_message);
			}
		}
	}

	public static function sendWelcomePM($identity_id)
	{
		$K = \Dragonfly::getKernel();
		if ($K->CFG->member->send_welcomepm && isset($K->SQL->TBL->privatemessages)) {
			$SQL = $K->SQL;
			$pm = new \Dragonfly\Modules\Private_Messages\Message();
			$pm->subject = _WELCOMETO.' '.$K->CFG->global->sitename.'!';
			$pm->user_id = 2;
			$pm->recipients[] = $identity_id;
			$pm->text = \Dragonfly\BBCode::encode($K->CFG->member->welcomepm_msg);
			$pm->save();
			$SQL->query("UPDATE {$SQL->TBL->users} SET user_new_privmsg=1 WHERE user_id={$identity_id}");
		}
	}

}
