<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Modules\Your_Account;

class Userinfo
{
	use \Poodle\Events;

	public
		$identity;

	public function display($username)
	{
		$K = \Dragonfly::getKernel();
		$db = $K->SQL;
		$MCFG = $K->CFG->member;
		$OUT = $K->OUT;
		if ($MCFG->private_profile && !is_user()) {
			cpg_error(_ACTDISABLED, 403);
		}
		$owninfo = (is_user() && ($username == is_user() || mb_strtolower($username) == mb_strtolower($K->IDENTITY->nickname)));
		if ($owninfo) {
			$userinfo = $K->IDENTITY;
			display_member_block();
		} else {
			if (is_numeric($username)) {
				$userinfo = \Poodle\Identity\Search::byID($username, true);
			} else {
				$userinfo = \Poodle\Identity\Search::byNickname($username, true);
			}
			if (!$userinfo) {
				cpg_error(_MA_USERNOEXIST, 404);
			}
			if (!$userinfo->level) {
				cpg_error(_ACCSUSPENDED, 404);
			}
			if (0 > $userinfo->level) {
				cpg_error(_ACCDELETED, 410);
			}
		}
		$username = $userinfo->username;

		$imgpath = 'themes/'.$K->OUT->theme.'/images/forums/lang_';
		$imgpath .= (is_file($imgpath.$OUT->L10N->lng.'/icon_email.gif') ? $OUT->L10N->lng : 'en');

		if ($owninfo) {
			\Dragonfly\Page::title($username.', '._THISISYOURPAGE, false);
		} else {
			\Dragonfly\Page::title(_PERSONALINFO, false);
			\Dragonfly\Page::title($username, false);
		}

		if ($userinfo->website) {
			if (false === strpos($userinfo->website, '://')) {
				$userinfo->website = "http://{$userinfo->website}";
			}
			if (!preg_match('#^(http[s]?\:\/\/)?([a-z0-9\-\.]+)?[a-z0-9\-]+\.[a-z]{2,4}$#i', $userinfo->website)) {
				$userinfo->website = '';
			}
		}

		$OUT->custom_field = array();
		if (can_admin('members')||$owninfo){
			$result = $db->query("SELECT field, langdef, type FROM {$db->TBL->users_fields} WHERE section = 2 OR section = 3");
		} else {
			$result = $db->query("SELECT field, langdef, type FROM {$db->TBL->users_fields} WHERE section = 2");
		}
		while ($row = $result->fetch_assoc()) {
			if ($row['type'] == 1) {
				$value = $userinfo[$row['field']] ? _YES : _NO;
			} else {
				$value = \Dragonfly\BBCode::decode($userinfo[$row['field']], 1);
			}
			if (defined($row['langdef'])) $row['langdef'] = constant($row['langdef']);

			$OUT->custom_field[] = array(
				'NAME'  => $row['langdef'],
				'VALUE' => $value
			);
		}

		$show_pm = is_user() && \Dragonfly\Modules::isActive('Private_Messages') && !$owninfo;

		$show_gallery = 0;
		if (\Dragonfly\Modules::isActive('coppermine')) {
			$user_gallery = 10000+$userinfo->id;
			list($ugall) = $db->uFetchRow("SELECT COUNT(p.pid)
				FROM {$db->TBL->cpg_pictures} AS p
				INNER JOIN {$db->TBL->cpg_albums} AS a USING (aid)
				WHERE p.owner_id = {$userinfo->id} AND (a.category = 1 OR a.category = {$user_gallery})");
			if ($ugall) {
				$show_gallery = 1;
			}
		}

		$OUT->assign_vars(array(
			'userinfo'          => $userinfo,
			'USER_RANK'         => \Dragonfly\Identity\Rank::get($userinfo->rank, $userinfo->posts),
			'IMG_PATH'          => $imgpath,
			'OWN_INFO'          => $owninfo,
			'U_PM'              => $show_pm ? \URL::index("Private_Messages&compose&u={$userinfo->id}") : false,
			'USER_GALLERY'      => $show_gallery ? \URL::index("coppermine&file=users&id={$userinfo->id}") : false,
			'U_EDIT_USER'       => \URL::admin("users&id={$userinfo->id}&edit=profile"),
			'U_SUSPEND_USER'    => \URL::admin("users&id={$userinfo->id}&edit=admin"),
		));

		$OUT->display('Your_Account/userinfo');

		$blockslist = array();
		foreach (glob('modules/Your_Account/profile_blocks/*.php') as $block) {
			$blockslist[] = $block;
		}
		natcasesort($blockslist);
		foreach ($blockslist as $block) {
			require_once($block);
		}

		$this->identity = $userinfo;
		$this->loadEventListeners();
		$this->triggerEvent('display');
	}
}
