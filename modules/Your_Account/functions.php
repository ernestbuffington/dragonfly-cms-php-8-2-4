<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

function display_member_block()
{
	$K = \Dragonfly::getKernel();
	$db = $K->SQL;
	$user_id = $K->IDENTITY->id;
	$K->L10N->load('Your_Account');

	$op = isset($_GET['op']) ? $_GET['op'] : '';
	$mode = isset($_GET['edit']) ? $_GET['edit'] : (isset($_POST['save']) ? $_POST['save'] : '');

	$K->OUT->account_menu = array(
		array(
			'label' => _TB_INFO,
			'items' => array(
				array(
					'name' => _TB_PROFILE_INFO,
					'uri' => \Dragonfly\Identity::getProfileURL($user_id),
					'current' => (isset($_GET['profile']) && $_GET['profile'] == $user_id),
				),
				array(
					'name' => _TB_EDIT_PROFILE,
					'uri' => URL::index('Your_Account&edit=profile'),
					'current' => 'profile' === $mode,
				),
				array(
					'name' => _TB_EDIT_PRIVATE,
					'uri' => URL::index('Your_Account&edit=private'),
					'current' => 'private' === $mode,
				),
				array(
					'name' => _TB_EDIT_REG,
					'uri' => URL::index('Your_Account&edit=reg_details'),
					'current' => 'reg_details' === $mode,
				),
			)
		),
		array(
			'label' => _TB_CONFIG,
			'items' => array(
				array(
					'name' => _TB_EDIT_PREFS,
					'uri' => URL::index('Your_Account&edit=prefs'),
					'current' => 'prefs' === $mode,
				),
				array(
					'name' => _TB_EDIT_HOME,
					'uri' => URL::index('Your_Account&op=edithome'),
					'current' => 'edithome' === $op,
				),
				array(
					'name' => _TB_EDIT_COMM,
					'uri' => URL::index('Your_Account&op=editcomm'),
					'current' => 'editcomm' === $op,
				),
			)
		),
		array(
			'label' => _TB_PERSONAL,
			'items' => array()
		)
	);

	if ($K->CFG->avatar->allow_local || $K->CFG->avatar->allow_remote) {
		$K->OUT->account_menu[2]['items'][] = array(
			'name' => _TB_EDIT_AVATAR,
			'uri' => URL::index('Your_Account&edit=avatar'),
			'current' => 'avatar' === $mode,
		);
	}
	if (\Dragonfly\Modules::isActive('coppermine')) {
		$K->OUT->account_menu[2]['items'][] = array(
			'name' => _TB_PERSONAL_GALLERY,
			'uri' => URL::index("coppermine&file=users&id={$user_id}"),
			'current' => false,
		);
	}
	if (\Dragonfly\Modules::isActive('Blogs')) {
		$K->OUT->account_menu[2]['items'][] = array(
			'name' => _TB_PERSONAL_JOURNAL,
			'uri' => URL::index('Blogs&mode=add'),
			'current' => false,
		);
	}
	$K->OUT->account_menu[2]['items'][] = array(
		'name' => 'My uploads',
		'uri' => URL::index('&file=uploads'),
		'current' => 'uploads' === $_GET['file'],
	);

	$K->OUT->account_pmboxes = array();
	if (\Dragonfly\Modules::isActive('Private_Messages') && isset($db->TBL->privatemessages)) {
		$K->OUT->account_pmboxes[] = array(
			'name' => _TB_PRIVMSGS_INBOX,
			'count' => $db->TBL->privatemessages_recipients->count("user_id={$user_id} AND pmr_status IN (0, 1, 2)"),
			'uri' => URL::index('Private_Messages&folder=inbox'),
		);
		$K->OUT->account_pmboxes[] = array(
			'name' => _TB_PRIVMSGS_OUTBOX,
			'count' => $db->TBL->privatemessages->count("user_id={$user_id} AND pm_status = 0"),
			'uri' => URL::index('Private_Messages&folder=outbox'),
		);
		$K->OUT->account_pmboxes[] = array(
			'name' => _TB_PRIVMSGS_SENTBOX,
			'count' => $db->TBL->privatemessages->count("user_id={$user_id} AND pm_status = 1"),
			'uri' => URL::index('Private_Messages&folder=sentbox'),
		);
		$K->OUT->account_pmboxes[] = array(
			'name' => _TB_PRIVMSGS_SAVEBOX,
			'count' => $db->TBL->privatemessages->count("user_id={$user_id} AND pm_status = 3")
				+ $db->TBL->privatemessages_recipients->count("user_id={$user_id} AND pmr_status = 3"),
			'uri' => URL::index('Private_Messages&folder=savebox'),
		);
	}

	$block = array(
		'bid' => 10000,
		'view_to' => 1,
		'side' => 'l',
		'title' => _TB_BLOCK,
		'content' => $K->OUT->toString('Your_Account/memberblock')
	);
	unset($K->OUT->account_menu, $K->OUT->account_pmboxes);
	\Dragonfly\Blocks::custom($block);
}

function display_admin_account_top_menu($mode, \Dragonfly\Identity $userinfo)
{
	echo "<strong>{$userinfo->username}</strong>";
	if ($userinfo->level == 0) { echo ' ('._ACCTSUSPEND.')'; }
	else if ($userinfo->level < 0) { echo ' ('._ACCTDELETE.')'; }
	echo '<br />
	'.(($mode == 'profile') ? '<strong>'._MA_PROFILE_INFO.'</strong>' : '<a href="'.htmlspecialchars(URL::admin("users&id={$userinfo->id}&edit=profile")).'">'._MA_PROFILE_INFO.'</a>').' |
	'.(($mode == 'reg_details') ? '<strong>'._MA_REGISTRATION_INFO.'</strong>' : '<a href="'.htmlspecialchars(URL::admin("users&id={$userinfo->id}&edit=reg_details")).'">'._MA_REGISTRATION_INFO.'</a>').' |
	'.(($mode == 'avatar') ? '<strong>'._AVATAR_CONTROL.'</strong>' : '<a href="'.htmlspecialchars(URL::admin("users&id={$userinfo->id}&edit=avatar")).'">'._AVATAR_CONTROL.'</a>');
	if ($userinfo->level >= 0) {
		echo ' | '.(($mode == 'admin') ? '<strong>'._MA_PRIVILEGES.'</strong>' : '<a href="'.htmlspecialchars(URL::admin("users&id={$userinfo->id}&edit=admin")).'">'._MA_PRIVILEGES.'</a>');
	}
	echo '<br /><br />';
}
